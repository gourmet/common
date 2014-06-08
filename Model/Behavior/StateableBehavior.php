<?php
/**
 * StateableBehavior
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
 * Statable behavior
 *
 * @package       Common.Model.Behavior
 */
class StateableBehavior extends ModelBehavior {

/**
 * {@inheritdoc}
 */
	public $mapMethods = array(
		'/change([A-Z]\w+)/' => '__call',
		'/get([A-Z]\w+)/' => '__call'
	);

/**
 * Defaults.
 *
 * @var array
 */
	protected $_defaults = array(
		'fields' => 'status',
		'values' => array('enabled', 'disabled'),
		'default' => null,
	);

/**
 * {@inheritdoc}
 */
	public function setup(Model $Model, $config = array()) {
		if (isset($config[0])) {
			$config['fields'] = $config[0];
			unset($config[0]);
		}

		$config = array_merge($this->_defaults, $config);
		$config['default'] = reset($config['values']);

		if (is_string($config['fields'])) {
			$config['fields'] = (array) $config['fields'];
		}


		foreach ($config['fields'] as $field => $options) {
			if (is_numeric($field)) {
				unset($config['fields'][$field]);
				$field = $options;
				$options = $config['fields'][$options] = array();
			}

			if (!array_key_exists('values', $options)) {
				$options = array('values' => empty($options) ? $config['values'] : $options);
			}

			$config['fields'][$field] = array_merge(array('default' => reset($options['values'])), $options);

			if (!$Model->hasField($field) && (!Reveal::is('Sapi.cli') || !in_array('Migrations.migration', env('argv')))) {
				$msg = __d('affiliates', "Missing state field '%s' in table '%s'", $field, $Model->useTable);
				throw new FatalErrorException($msg);
			}
		}

		$this->settings[$Model->alias] = $config;
	}

/**
 * Map 'change' and 'get' methods.
 *
 * @param Model $Model Model using this behavior.
 * @param string $method Real method's name.
 * @return mixed For 'change' operations, returns TRUE on success and FALSE otherwise. For
 *   'get' operations, returns a boolean when a state is provided by comparing it to the
 *   record's state field result, or returns the record's state field result.
 * @throws FatalErrorException If the state's field is not configured as such.
 * @see StateableBehaviorTest for examples of how arguments can be passed.
 */
	public function __call(Model $Model, $method) {
		foreach (array('change', 'get') as $do) {
			$field = str_replace($do, '', $method);
			if ($field != $method) {
				$field = Inflector::underscore($field);
				break;
			}
		}

		if (!array_key_exists($field, $this->settings[$Model->alias]['fields'])) {
			throw new FatalErrorException(__d('common', "Missing state field configuration ('%s')", $field));
		}

		$args = func_get_args();

		$id = $Model->getID();
		$state = isset($args[2]) ? $args[2] : null;
		$validate = isset($args[4]) ? $args[4] : true;
		if (isset($args[3])) {
			if (!is_bool($args[3])) {
				$id = $args[2];
				$state = $args[3];
			} else {
				$validate = $args[3];
			}
		} else if (empty($id) && (isset($args[2]) || 'get' == $do) && $Model->exists($state)) {
			$id = $state;
			$state = null;
		}

		if (empty($id) || !$Model->exists($id)) {
			return false;
		}

		$current = $Model->field($field, array($Model->alias . '.' . $Model->primaryKey => $id));
		if ('get' == $do) {
			if (!empty($state)) {
				return $state == $current;
			}
			return $current;
		}

		$Model->id = $id;

		$eventName = 'Model.' . $Model->name . '.';
		$fieldName = Inflector::camelize($field);

		$Event = new CakeEvent($eventName . 'beforeChange' . $fieldName, $Model, compact('field', 'state', 'validate'));
		list($Event->break, $Event->breakOn) = array(true, false);
		$result = $Model->triggerEvent($Event);

		if (false === $result || !$ret = ($state != $current && $Model->saveField($field, $state, $validate))) {
			return false;
		}

		$Model->triggerEvent($eventName . 'afterChange' . $fieldName, $Model);
		return true;
	}

/**
 * {@inheritdoc}
 */
	public function beforeSave(Model $Model, $options = array()) {
		if (!empty($Model->id) || !empty($Model->data[$Model->alias][$Model->primaryKey])) {
			return true;
		}

		// Set default value when no 'DEFAULT' is defined in table's schema.
		foreach ($this->settings[$Model->alias]['fields'] as $field => $options) {
			if (!array_key_exists($field, $Model->data[$Model->alias])) {
				$Model->data[$Model->alias][$field] = $options['default'];
			}
		}

		return true;
	}

/**
 * {@inheritdoc}
 */
	public function beforeValidate(Model $Model, $options = array()) {
		if (empty($Model->data[$Model->alias])){
			return true;
		}

		$ModelValidator = $Model->validator();
		foreach ($this->settings[$Model->alias]['fields'] as $field => $options) {
			if (
				(isset($Model->stateableValidation) && false === $Model->stateableValidation)
				|| !array_key_exists($field, $Model->data[$Model->alias])
				|| $ModelValidator->getField($field)
			) {
				continue;
			}

			$ModelValidator->add($field, new CakeValidationSet($field, array(
					'valid' => array(
						'rule' => 'isValidState',
						'message' => __d('common', "Invalid %s.", $field)
					)
			)));
		}
		return true;
	}

/**
 * Checks if a given value is a valid state.
 *
 * @param Model $Model Model using this behavior.
 * @param array $check A field/value association (i.e. `array('status' => 'active')`).
 * @param array $valid Array of valid values defined in the ruleset. If none defined, `$ruleset` values.
 * @param array $ruleset Validation ruleset that triggered this method. If null, the `$ruleset`
 *   can be found in `$valid`.
 * @return boolean TRUE if it's a valid state, FALSE otherwise.
 * @see StateableBehaviorTest for examples of how the valid values can be defined.
 */
	public function isValidState(Model $Model, $check, $valid = array(), $ruleset = array()) {
		$settings = $this->settings[$Model->alias]['fields'];
		$field = current(array_keys((array) $check));
		$value = current((array) $check);

		if (empty($ruleset)) {
			$ruleset = $valid;
			$valid = $settings[$field]['values'];
		}

		return empty($valid) || in_array($value, (array) $valid);
	}

}
