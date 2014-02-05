<?php
/**
 * Common bootstrap.
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

/**
 * Preload common classes.
 */
	App::uses('Common', 'Common.Lib');
	App::uses('CommonRouter', 'Common.Routing');
	App::uses('Navigation', 'Common.Lib');
	App::uses('CommonEventManager', 'Common.Event');

/**
 * Setup configuration reader engine.
 */
	Configure::write('Common.reader', Common::reader(Configure::read('Common.reader')));

/**
 * Load configuration.
 */
	Configure::load('Common.config', Configure::read('Common.reader.id'));
	try {
		Configure::load('config', Configure::read('Common.reader.id'));
	} catch (ConfigureException $e) {
		$configPath = APP . DS . 'Config' . DS . 'config';
		if (file_exists($configPath) || file_exists($configPath . '.' . Configure::read('Common.reader.id'))) {
			throw new ConfigureException($e->getMessage());
		}
		unset($configPath);
	}

/**
 * Configure routing default class.
 */
	if (CakePlugin::loaded('I18n')) {
		App::uses('I18nRoute', 'I18n.Routing/Route');
		Router::defaultRouteClass('I18nRoute');
		Configure::write('Config.language', Configure::read('L10n.language'));
		Configure::write('Config.languages', Configure::read('L10n.languages'));
		if (!defined('DEFAULT_LANGUAGE')) {
			define('DEFAULT_LANGUAGE', Configure::read('L10n.language'));
		}
	}

/**
 * Configure `CakeNumber` currencies.
 */
	if (class_exists('CakeNumber')) {
		CakeNumber::defaultCurrency(Common::read('L10n.currency', 'USD'));
		foreach (Common::read('L10n.currencies', array()) as $currencyName => $currencyFormat) {
			CakeNumber::addFormat($currencyName, $currencyFormat);
		}
	}


if (!function_exists('__t')) {
/**
 * Translates different type of strings depending on the number of arguments it is passed and their types. Supports:
 *
 *  - all of `__()`, `__n()`, `__d()`, `__dn()`
 *  - placeholders for `String::insert()`
 *
 * Examples:
 *
 * 	- __t('Hello world!')
 * 	- __t('Hello :name!', array('name' => 'world'))
 * 	- __t('Hello mate!', 'Hello mates!', 2)
 * 	- __t(':salutation mate!', ':salutation mates!', 2, array('salutation' => 'Hello'))
 * 	- __t('myapp', 'Hello world!')
 * 	- __t('myapp', 'Hello :name!', array('name' => 'world'))
 * 	- __t('myapp', 'Hello mate!', 'Hello mates!', 2)
 * 	- __t('myapp', ':salutation mate!', ':salutation mates!', 2, array('salutation' => 'Hello'))
 *
 * @return string
 */
	function __t() {
		$args = func_get_args();

		$data = $options = array();

		switch (count($args)) {
			case 1:
				return __($args[0]);
			break;
			case 2:
				if (is_array($args[1])) {
					$result = __($args[0]);
					$data = $args[1];
				} else if (is_string($args[1])) {
					return __d($args[0], $args[1]);
				}
			break;
			case 3:
				if (is_array($args[2])) {
					$result = __d($args[0], $args[1]);
					$data = $args[2];
				} else if (is_numeric($args[2])) {
					return __n($args[0], $args[1], $args[2]);
				}
			break;
			case 4:
				if (is_array($args[2]) && is_array($args[3])) {
					$result = __d($args[0], $args[1]);
					$data = $args[2];
					$options = $args[3];
				} else if (is_numeric($args[2]) && is_array($args[3])) {
					$result = __n($args[0], $args[1], $args[2]);
					$data = $args[3];
				} else if (is_string($args[2]) && is_numeric($args[3])) {
					return __dn($args[0], $args[1], $args[2], $args[3]);
				}
			break;
		}

		if (empty($data)) {
			return $result;
		}

		return String::insert($result, $data, $options);
	}
}

/**
 * Recursively computes the intersection of arrays.
 *
 * @param $array1 array The array with master values to check.
 * @param $array2 array An array to compare values against.
 * @return array
 */
if (!function_exists('array_intersect_recursive')) {
	function array_intersect_recursive($array1, $array2) {
		$array1 = array_intersect_key($array1, $array2);
		foreach (array_keys($array1) as $k) {
			if (!is_array($array1[$k]) || !is_array($array2[$k])) {
				continue;
			}
			$array1[$k] = array_intersect_recursive($array1[$k], $array2[$k]);
		}
		return $array1;
	}
}
