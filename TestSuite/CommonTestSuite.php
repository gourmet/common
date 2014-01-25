<?php
/**
 * CommonTestSuite
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

App::uses('CakeTestSuite', 'TestSuite');

/**
 * Common test suite
 *
 * @package       Common.TestSuite
 */
class CommonTestSuite extends PHPUnit_Framework_TestSuite {

/**
 * Recursively add all app and plugin tests.
 *
 * @param CakeTestSuite $Suite CakeTestSuite instance.
 * @param array $plugins Optional. List of plugins from where to add tests.
 *  Defaults to all loaded plugins.
 * @param array $skip List of plugins for which not to add tests.
 * @return CakeTestSuite New or modified CakeTestSuite instance.
 */
	public static function allTests($Suite = null, $plugins = array(), $skip = array()) {
		if (!($Suite instanceOf CakeTestSuite)) {
			$plugins = (array) $Suite;
			$Suite = null;
		}

		if (empty($Suite)) {
			$Suite = new CakeTestSuite('all of the tests');
		}

		$Suite = self::allAppTests($Suite);

		if (empty($plugins)) {
			$plugins = CakePlugin::loaded();
		}

		foreach ($plugins as $plugin) {
			if (in_array($plugin, $skip)) {
				continue;
			}
			$Suite = self::allPluginTests($Suite, $plugin);
		}

		return $Suite;
	}

/**
 * Recursively add all app tests directories.
 *
 * @param CakeTestSuite $Suite CakeTestSuite instance.
 * @return CakeTestSuite New or modified CakeTestSuite instance.
 */
	public static function allAppTests(CakeTestSuite $Suite = null) {
		if (empty($Suite)) {
			$Suite = new CakeTestSuite('all of the `app` tests');
		}

		return self::_addTestDirectoryRecursive($Suite, APP);
	}

/**
 * Recursively add all plugin tests directories.
 *
 * @param CakeTestSuite $Suite CakeTestSuite instance.
 * @param string $plugin Name of the plugin to look for tests in.
 * @return CakeTestSuite New or modified CakeTestSuite instance.
 */
	public static function allPluginTests(CakeTestSuite $Suite = null, $plugin = null) {
		if (empty($plugin)) {
			$plugin = $_GET['plugin'];
		}

		if (empty($Suite)) {
			$Suite = new CakeTestSuite(sprintf('all of the `%s` plugin tests', $plugin));
		}

		return self::_addTestDirectoryRecursive($Suite, CakePlugin::path($plugin) . DS);
	}

/**
 * Recursively add all directories under `$path/Test/Case`.
 *
 * @param CakeTestSuite $Suite CakeTestSuite instance.
 * @param string $path Root path where to find 'Test/Case'.
 * @return CakeTestSuite Modified CakeTestSuite instance.
 */
	protected static function _addTestDirectoryRecursive(CakeTestSuite $Suite, $path) {
		$Folder = new Folder($path . 'Test' . DS . 'Case');
		$dirs = $Folder->tree(null, true, 'dir');
		array_shift($dirs);

		foreach ($dirs as $dir) {
			$Suite->addTestDirectoryRecursive($dir);
		}

		return $Suite;
	}

}
