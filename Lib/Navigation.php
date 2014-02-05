<?php
/**
 * Navigation
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
 * Navigation
 *
 * @package       Common.Lib
 */
class Navigation extends Object {

/**
 * Default item options.
 *
 * @var array
 */
	protected static $_defaults = array(
		'icon' => false,
		'url' => false,
		'weight' => 9999,
		'children' => array(),
		'htmlAttributes' => array(),
		'confirmMessage' => false
	);

/**
 * Navigation menu items.
 *
 * @var array
 */
	protected static $_items = array();

/**
 * Add a menu item.
 *
 * @param string $path dot separated path in the array.
 * @param array $options menu options array
 * @return void
 */
	public static function add($path, $options) {
		if (Reveal::is('Sapi.cli')) {
			return;
		}

		if (!empty($options['access'])) {
			foreach ((array) $options['access'] as $rule) {
				$check = strpos($rule, '!') === 0;
				if ($check) {
					$rule = substr($rule, 1);
				}
				if (Reveal::is($rule) == $check) {
					return;
				}
			}
			unset($options['access']);
		}

		$pathE = explode('.', $path);
		$pathE = array_splice($pathE, 0, count($pathE) - 2);
		$parent = join('.', $pathE);
		if (!empty($parent) && !Hash::check(self::$_items, $parent)) {
			$title = Inflector::humanize(end($pathE));
			$o = array('title' => $title);
			self::_setupOptions($o);
			self::add($parent, $o);
		}
		self::_setupOptions($options);
		$current = Hash::extract(self::$_items, $path);
		if (!empty($current)) {
			self::_replace(self::$_items, $path, $options);
		} else {
			self::$_items = Hash::insert(self::$_items, $path, $options);
		}
	}

/**
 * Clear all menus.
 *
 * @return void
 */
	public static function clear($path = null) {
		if (empty($path)) {
			self::$_items = array();
			return;
		}

		if (Hash::check(self::$_items, $path)) {
			self::$_items = Hash::insert(self::$_items, $path, array());
		}
	}

/**
 * Gets default settings for menu items.
 *
 * @return array
 */
	public static function getDefaults() {
		return self::$_defaults;
	}

/**
 * Sets or returns menu data in array.
 *
 * @param $items array if empty, the current menu is returned.
 * @return array
 */
	public static function items($path = null) {
		if (empty($path)) {
			return self::$_items;
		}

		if (is_array($path)) {
			return self::$_items = $path;
		}

		if (Hash::check(self::$_items, $path)) {
			return Hash::extract(self::$_items, $path);
		}

		return array();
	}

/**
 * Orders items by weight.
 *
 * @param array $items
 * @return array
 */
	public static function order($items) {
		if (empty($items)) {
			return array();
		}

		$_items = array_combine(array_keys($items), Hash::extract($items, '{s}.weight'));
		asort($_items);

		foreach (array_keys($_items) as $key) {
			$_items[$key] = $items[$key];
		}

		return $items;
	}

/**
 * Remove a menu item.
 *
 * @param string $path dot separated path in the array.
 * @return void
 */
	public static function remove($path) {
		self::$_items = Hash::remove(self::$_items, $path);
	}

/**
 * Merge $firstArray with $secondArray.
 *
 * Similar to Hash::merge, except duplicates are removed
 * @param array $firstArray
 * @param array $secondArray
 * @return array
 */
	protected static function _merge($firstArray, $secondArray) {
		$merged = Hash::merge($firstArray, $secondArray);
		foreach ($merged as $key => $val) {
			if (is_array($val) && is_int(key($val))) {
				$merged[$key] = array_unique($val);
			}
		}
		return $merged;
	}

/**
 * Replace a menu element
 *
 * @param array $target pointer to start of array
 * @param string $path path to search for in dot separated format
 * @param array $options data to replace with
 * @return void
 */
	protected static function _replace(&$target, $path, $options) {
		$pathE = explode('.', $path);
		$path = array_shift($pathE);
		$fragment = join('.', $pathE);
		if (!empty($pathE)) {
			self::_replace($target[$path], $fragment, $options);
		} else {
			$target[$path] = self::_merge($target[$path], $options);
		}
	}

/**
 * Set default item's options.
 *
 * @param array $options
 * @return void
 */
	protected static function _setupOptions(&$options) {
		$options = self::_merge(self::$_defaults, $options);
		foreach ($options['children'] as &$child) {
			self::_setupOptions($child);
		}
	}

}
