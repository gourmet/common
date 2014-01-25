<?php
/**
 * FilterableBehavior
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
 * Filterable behavior
 *
 * @package       Common.Model.Behavior
 */
class FilterableBehavior extends ModelBehavior {

/**
 * Default field(s) to filter.
 *
 * @var array
 */
	public $_defaults = array('password');

/**
 * {@inheritdoc}
 */
	public function setup(Model $Model, $config = array()) {
		if (empty($config)) {
			$config = $this->_defaults;
		}

		$this->settings[$Model->alias] = Hash::normalize((array) $config);
	}

/**
 * {@inheritdoc}
 */
	public function afterFind(Model $Model, $results) {
		foreach (array_keys($this->settings[$Model->alias]) as $field) {
			foreach (array_keys(Hash::extract($results, '{n}.' . $Model->alias . '.' . $field)) as $key) {
				unset($results[$key][$Model->alias][$field]);
			}
		}

		return $results;
	}

	public function afterSave(Model $Model, $created) {
		if (empty($Model->data[$Model->alias])) {
			return;
		}

		foreach (array_keys($this->settings[$Model->alias]) as $field) {
			if (array_key_exists($field, $Model->data[$Model->alias])) {
				unset($Model->data[$Model->alias][$field]);
			}
		}
	}

}
