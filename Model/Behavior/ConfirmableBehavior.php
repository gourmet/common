<?php
/**
 * ConfirmableBehavior
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
 * Confirmable behavior
 *
 * @package       Common.Model.Behavior
 */
class ConfirmableBehavior extends ModelBehavior {

/**
 * {@inheritdoc}
 */
	public function beforeValidate(Model $Model, $options = array()) {
		$ModelValidator = $Model->validator();
		foreach ($Model->data[$Model->alias] as $field => $value) {
			if (!preg_match('/^([a-z0-9_]+)_confirm$/i', $field, $match)) {
				continue;
			}

			if (!array_key_exists($match[1], $Model->data[$Model->alias])) {
				continue;
			}

			if (!$Ruleset = $ModelValidator->getField($match[1])) {
				$Ruleset = new CakeValidationSet($match[1], array());
			}

			$ruleset = array();
			foreach ($Ruleset->getRules() as $name => $Rule) {
				$ruleset[$name] = (array) $Rule;
				foreach (array_keys($ruleset[$name]) as $key) {
					if (!preg_match('/^[a-z]/i', $key)) {
						unset($ruleset[$name][$key]);
					}
				}
			}

			$ModelValidator->add($field, new CakeValidationSet($field, array()));
			$ModelValidator->getField($field)->setRule('confirmed', array('rule' => 'isConfirmed', 'message' => __d('common', "No match.")));
		}
		return true;
	}

/**
 * Checks if two `Model::$data` keys' values match.
 *
 * @param Model $Model Model using this behavior.
 * @param array $check A field/value association (i.e. `array('password_confirm' => 'somepassword')`).
 * @param array $valid Original field's name. If none defined, `$ruleset` values.
 * @return boolean TRUE if values match, FALSE otherwise.
 */
	public function isConfirmed(Model $Model, $check, $field = null) {
		if (empty($field) || !is_string($field)) {
			$field = str_replace('_confirm', '', current(array_keys((array) $check)));
		}

		// @todo Make sure this really unsets the '*_confirm' key from the SQL and from the returned data.
		unset($Model->data[$Model->alias][$field . '_confirm']);
		return strcmp($check[$field . '_confirm'], $Model->data[$Model->alias][$field]) === 0;
	}

}
