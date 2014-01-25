<?php
/**
 * EncodableBehavior
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
 * Encodable behavior
 *
 * @package       Common.Model.Behavior
 */
class EncodableBehavior extends ModelBehavior {

	public $settings = array();

	protected $_options = array('encoding' => 'normal', 'trim' => false);

/**
 * Setup
 *
 * @param Model $model
 * @param array $config
 * @return void
 */
	public function setup(Model $Model, $config = array()) {
		if (is_string($config)) {
			$config = array($config);
		}

		$_config = array();

		foreach ((array) $config as $field => $options) {
			if (is_int($field) && is_string($options)) {
				$field = $options;
				$options = $this->_options;
			}
			$_config[$field] = array_merge($this->_options, $options);
		}

		$this->settings[$Model->alias] = $_config;
	}

	public function afterFind(Model $Model, $results, $primary) {
		foreach ((array) $results as $k => $result) {
			foreach ($this->settings[$Model->alias]['fields'] as $field => $options) {
				if (is_numeric($field)) {
					$field = $options;
				}

				if (!isset($result[$Model->alias][$field])) {
					continue;
				}

				$results[$k][$Model->alias][$field] = $this->decode($Model, $result[$Model->alias][$field]);
			}
		}


		return $results;
	}

	public function beforeSave(Model $Model) {
		if (empty($Model->data)) {
			return true;
		}

		foreach ($this->settings[$Model->alias]['fields'] as $field => $options) {
			if (is_numeric($field)) {
				$field = $options;
				$options = array();
			}

			$data = $Model->data[$Model->alias];

			if (!isset($data[$field])) {
				continue;
			}

			if (is_array($data[$field]) || $this->decode($Model, $data[$field]) == $data[$field]) {
				$Model->data[$Model->alias][$field] = $this->encode($Model, $data[$field], $options);
			}
		}

		return true;
	}

/**
 * Decode data
 *
 * @param Model $model
 * @param string $data
 * @return array
 */
	public function decode(Model $Model, $data) {
		if ($data == '') {
			return '';
		}

		$output = @unserialize($data);
		if (is_array($output)) {
			return $output;
		}

		return json_decode($data, true);
	}

/**
 * Encode data
 *
 * Turn array into a JSON
 *
 * @param object $model model
 * @param array $data data
 * @param array $options (optional)
 * @return string
 */
	public function encode(Model $Model, $data, $options = array()) {
		$options = array_merge($this->_options, $options);
		$data = (array) $data;

		$elements = array();
		$output = '[]';

		if (false === $options['encoding']) {
			$options['encoding'] = 'normal';
		} else if (true === $options['encoding']) {
			$options['encoding'] = 'json';
		}

		// trim
		if ($options['trim']) {
			foreach ($data as $id => $d) {
				$d = $this->_trim($d, $options);
				if ($d != '') {
					$elements[$id] = $d;
				}
			}
		} else {
			foreach ($data as $id => $d) {
				$elements[$id] = $d;//'"' . $d . '"';
				if (!is_array($d) && 'normal' == $options['encoding']) {
					$elements[$id] = '"' . $d . '"';
				}
			}
		}

		// encode
		switch ($options['encoding']) {
			case 'normal':
				$output = '[' . $this->_implode(',', $elements) . ']';
			break;

			case 'serialize':
				$output = serialize($elements);
			break;

			case 'json':
				$output = json_encode($elements);
			break;

			default:
				$output = json_encode($elements, $options['encoding']);
		}

		return $output;
	}

	private function _implode($glue, $array) {
		$elements = array();
		foreach ($array as $id => $d) {
			if (is_array($d)) {
				$elements[$id] = '[' . $this->_implode($glue, $d) . ']';
				continue;
			}
			$elements[$id] = $d;
		}

		return implode($glue, $elements);
	}

	private function _trim($data, $options) {
		if (!is_array($data)) {
			if (is_int($data)) {
				return $data;
			}
			$data = trim($data);
			if ($data != '') {
				if ('normal' === $options['encoding']) {
					$data = '"' . $data . '"';
				}
			}
			return $data;
		}

		$elements = array();
		foreach ($data as $id => $d) {
			$d = $this->_trim($d, $options);
			if ($d != '') {
				$elements[$id] = $d;
			}
		}
		return $elements;
	}
}
