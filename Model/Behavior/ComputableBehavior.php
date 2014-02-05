<?php
/**
 * ComputableBehavior
 *
 * PHP 5
 *
 * Copyright 2013, Jad Bitar (http://jadb.io)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2013, Jad Bitar (http://jadb.io)
 * @link          http://github.com/gourmet/common
 * @since         0.1.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ModelBehavior', 'Model');

/**
 * Computable behavior
 *
 * @package       Common.Model.Behavior
 */
class ComputableBehavior extends ModelBehavior {

/**
 * {@inheritdoc}
 */
	public $mapMethods = array(
		'/\b_findComputed\b/' => '_findComputed',
	);

/**
 * Defaults.
 *
 *  - method string Type of computation. Only `sum` and `average` supported for now.
 *  - column string Computable field name.
 *  - result string Computed result's virtual field name.
 *
 * @var array
 */
	protected $_defaults = array(
		'method' => 'sum',
		'column' => 'value',
		'result' => 'computed',
	);

/**
 * Supported computation methods.
 *
 * @var array
 */
	protected $_methods = array(
		'average' => 'avg',
		'avg' => 'avg',
		'sum' => 'sum',
		'total' => 'sum',
	);

/**
 * {@inheritdoc}
 */
	public function setup(Model $Model, $config = array()) {
		if (isset($config[0])) {
			$config['column'] = $config[0];
			unset($config[0]);
		}

		$this->settings[$Model->alias] = array_merge($this->_defaults, $config);
		$Model->findMethods['computed'] = true;
	}

/**
 * {@inheritdoc}
 */
	public function afterSave(Model $Model, $created) {
		$foreignKeys = array();
		foreach ($Model->belongsTo as $parent => $assoc) {
			if (!empty($assoc['computedCache'])) {
				$foreignKeys[$parent] = $assoc['foreignKey'];
			}
		}
		$foreignKeys = array_intersect($foreignKeys, array_keys($Model->data[$Model->alias]));

		if (empty($foreignKeys) || empty($Model->id)) {
			$keys = array();
		}

		$old = $Model->find('first', array(
			'conditions' => array($Model->alias . '.' . $Model->primaryKey => $Model->id),
			'fields' => array_values($foreignKeys),
			'recursive' => -1
		));

		$keys = array_merge($Model->data[$Model->alias], array('old' => $old[$Model->alias]));

		$this->updateComputedCache($Model, $keys, $created);
		return true;
	}

/**
 * {@inheritdoc}
 */
	public function afterDelete(Model $Model) {
		if (!empty($this->__foreignKeys[$Model->alias])) {
			$this->updateComputedCache($Model, $this->__foreignKeys[$Model->alias]);
		}
	}

/**
 * {@inheritdoc}
 */
	public function beforeDelete(Model $Model, $cascade = true) {
		if (!empty($Model->belongsTo)) {
			foreach ($Model->belongsTo as $assoc) {
				if (empty($assoc['computedCache'])) {
					continue;
				}

				$foreignKeys = array();

				foreach ($Model->belongsTo as $parent => $assoc) {
					if (isset($assoc['foreignKey']) && is_string($assoc['foreignKey'])) {
						$foreignKeys[$parent] = $assoc['foreignKey'];
					}
				}

				$this->__foreignKeys = $Model->find('first', array(
					'fields' => $foreignKeys,
					'conditions' => array($Model->alias . '.' . $Model->primaryKey => $Model->id),
					'recursive' => -1,
					'callbacks' => false
				));
				break;
			}
		}

		return true;
	}

/**
 * Updates the computed cache of belongsTo associations after a save or delete operation.
 *
 * @param Model $Model
 * @param array $keys Optional foreign key data, defaults to the information `$this->data`.
 * @param boolean $created True if a new record was created, otherwise only associations with
 *   'computedScope' defined get updated
 * @return void
 */
	public function updateComputedCache(Model $Model, $keys = array(), $created = false) {
		$keys = empty($keys) ? $Model->data[$Model->alias] : $keys;
		$keys['old'] = isset($keys['old']) ? $keys['old'] : array();

		foreach ($Model->belongsTo as $parent => $assoc) {
			if (empty($assoc['computedCache'])) {
				continue;
			}
			if (!is_array($assoc['computedCache'])) {
				if (isset($assoc['computedScope'])) {
					$assoc['computedCache'] = array($assoc['computedCache'] => $assoc['computedScope']);
				} else {
					$assoc['computedCache'] = array($assoc['computedCache'] => array());
				}
			}

			$foreignKey = $assoc['foreignKey'];
			$fkQuoted = $Model->escapeField($assoc['foreignKey']);

			foreach ($assoc['computedCache'] as $field => $conditions) {
				if (!is_string($field)) {
					$field = sprintf('%s_%s_computed'
						, Inflector::underscore($Model->alias)
						, $this->_methods[strtolower($this->settings[$Model->alias]['method'])]
					);
				}
				if (!$Model->{$parent}->hasField($field)) {
					continue;
				}

				if ($conditions === true) {
					$conditions = array();
				} else {
					$conditions = (array)$conditions;
				}

				if (!array_key_exists($foreignKey, $keys)) {
					$keys[$foreignKey] = $Model->field($foreignKey);
				}
				$recursive = (empty($conditions) ? -1 : 0);

				$computeProp = sprintf('last%sComputation', Inflector::classify($field));

				if (isset($keys['old'][$foreignKey])) {
					if ($keys['old'][$foreignKey] != $keys[$foreignKey]) {
						$conditions[$fkQuoted] = $keys['old'][$foreignKey];
						$Model->{$computeProp} = array_pop(array_pop(array_pop($Model->find('computed', compact('conditions', 'recursive')))));

						$Model->{$parent}->updateAll(
							array($field => $Model->{$computeProp}),
							array($Model->{$parent}->escapeField() => $keys['old'][$foreignKey])
						);
					}
				}
				$conditions[$fkQuoted] = $keys[$foreignKey];

				if ($recursive === 0) {
					$conditions = array_merge($conditions, (array)$conditions);
				}
				$Model->{$computeProp} = current(current(current($Model->find('computed', compact('conditions', 'recursive')))));

				$Model->{$parent}->updateAll(
					array($field => $Model->{$computeProp}),
					array($Model->{$parent}->escapeField() => $keys[$foreignKey])
				);
			}
		}
	}

/**
 * Custom find method to compute resultset's computable field.
 *
 * @param Model $model Model to query.
 * @param string $func
 * @param string $state Either "before" or "after"
 * @param array $query
 * @param array $result
 * @return array
 */
	public function _findComputed(Model $Model, $func, $state, $query, $result = array()) {
		if ('after' == $state) {
			return $result;
		}

		$query['fields'] = array(sprintf(
			'%s(%s) AS %s'
			, strtoupper($this->_methods[strtolower($this->settings[$Model->alias]['method'])])
			, $Model->alias . '.' . $this->settings[$Model->alias]['column']
			, $this->settings[$Model->alias]['result']
		));

		return $query;
	}

}
