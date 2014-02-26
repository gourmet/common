<?php
/**
 * Reveal
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

App::uses('CommonRevealException', 'Common.Error/Exception');

/**
 * Reveal different app and/or plugin specific stuff - condition checking, version, plugin name and more.
 *
 * Makes it simpler to modify certain conditions' checking commonly used by only
 * modifying their rule and allowing to add other rules from the application or any other
 * plugin and take advantage of that functionality.
 *
 * @package       Common.Lib
 */
class Reveal {

/**
 * Parameters matchers to use with mocked instance.
 *
 * @var array
 * @see  Reveal::is()
 */
	public $parametersMatchers = array();

/**
 * Defined rules.
 *
 * @var array
 */
	public static $rules = array(
		'App' => array(
			'online' => array(array('self', 'isOnline')),
		),
		'DebugKit' => array(
			'automated' => array(array('Configure', 'read'), 'DebugKit.autoRun'),
			'enabled' => array(array('self', '__isDebugKitEnabled')),
			'loaded' => array(array('CakePlugin', 'loaded'), 'DebugKit'),
			'requested' => array(array('self', '__isDebugKitRequested')),
			'running' => array(array('self', '__isDebugKitRunning'))
		),
		'Page' => array(
			'front' => array(array('self', '__isPageFront')),
			'login' => array(array('self', '__isPageLogin')),
			'prefixed' => array(array('self', '__isPage'), true),
			'test' => array(array('self', '__isPageTest')),
			'visitor' => array(array('self', '__isPage')),
		),
		'Sapi' => array(
			'cli' => array('php_sapi_name', null, 'cli'),
		)
	);

/**
 * Initial state is populated the first time reload() is called which is at the bottom
 * of this file. This is a cheat as get_class_vars() returns the value of static vars even if they
 * have changed.
 *
 * @var array
 */
	protected static $_initialState = array();

/**
 * List of loaded plugins.
 *
 * @var array
 */
	protected static $_plugins = array();

/**
 * Mocked instance of this class.
 *
 * @var Reveal
 */
	private static $__mockedInstance = null;

/**
 * Adds a new rule definition.
 *
 * @param string $path Hash path to assign rule to.
 * @param mixed $callback Callback function name or array of class and method name.
 * @param array $args Optional. Arguments passed to the callback.
 * @param mixed $successOn Optional. How to determine that a rule passes the check. If it's an array
 *   checks if the result of the callback is `in_array()`, otherwise compares the result
 *   to this value.
 */
	public static function addRule($path, $callback, $args = array(), $successOn = true) {
		return self::$rules = Hash::insert(self::$rules, $path, array($callback, $args, $successOn));
	}

/**
 * Gets the current logged in user's prefix. Can be forced to default to the routed prefix parameter (if any).
 *
 * @param boolean $force Optional. Should default to routed prefix parameter or not. Defaults to false.
 * @return mixed The current requestor's or request's prefix. Empty if none can be found.
 */
	public static function currentPrefix($force = false) {
		$prefix = AuthComponent::user('prefix');
		if ($force && empty($prefix)) {
			$prefix = Router::getParam('prefix');
		}

		return $prefix;
	}

/**
 * Destroy mocked instance of `Reveal`.
 *
 * @return void
 */
	public static function destroyMock() {
		self::$__mockedInstance = null;
	}

/**
 * Workaround to make the `Reveal::is()` static calls, mockable.
 *
 * @param Object $TestCase [description]
 * @param array $methods [description]
 * @param array $parametersMatchers [description]
 * @return Reveal
 */
	public static function getMock($TestCase, $methods, $parametersMatchers) {
		if (empty(self::$__mockedInstance)) {
			self::$__mockedInstance = $TestCase->getMock(__CLASS__, $methods);
		}

		foreach ($parametersMatchers as $parametersMatcher) {
			if (empty(self::$__mockedInstance->parametersMatchers) || !in_array($parametersMatcher, self::$__mockedInstance->parametersMatchers)) {
				self::$__mockedInstance->parametersMatchers[] = (array) $parametersMatcher;
			}
		}

		return self::$__mockedInstance;
	}

/**
 * Run the rules defined by paths. By default, the 'AND' conjunction is assumed when 2 or
 * more paths are passed.
 *
 * Examples:
 *
 *   Reveal::is('DebugKit.enabled')
 *   Reveal::is('App.online', 'DebugKit.enabled')
 *   Reveal::is(array('OR' => array('Page.admin', 'Page.member')))
 *
 * @return boolean True on success, false otherwise.
 */
	public static function is() {
		$args = func_get_args();
		if (!empty(self::$__mockedInstance) && !empty(self::$__mockedInstance->parametersMatchers)) {
			foreach (self::$__mockedInstance->parametersMatchers as $parametersMatcher) {
				if ($args == $parametersMatcher) {
					return call_user_func_array(array(self::$__mockedInstance, __FUNCTION__), $args);
				}
			}
		}
		return self::_is(false, $args);
	}

/**
 * Check if given host is online.
 *
 * @param string $host Optional. Hostname to check.
 * @param boolean $force Optional. Force check even if already checked.
 * @return boolean True if online, false otherwise.
 */
	public static function isOnline($host = 'google.com', $force = false) {
		static $hosts = array();

		if ($force || !array_key_exists($host, $hosts)) {
			$hosts[$host] = !in_array(gethostbyname($host), array($host, false));
		}

		return $hosts[$host];
	}

/**
 * Path of current file or backtrace's file.
 *
 * @param mixed $stack Optional. Stack level to extract file path from.
 * @return string Path of the current or the backtrace's file.
 * @throws Exception
 * @todo replace Exception by custom one.
 */
	public static function path($stack = false) {
		if (false === $stack) {
			return __FILE__;
		}

		if (!defined('DEBUG_BACKTRACE_IGNORE_ARGS')) {
			define('DEBUG_BACKTRACE_IGNORE_ARGS', 2);
		}

		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		if (empty($trace[$stack])) {
			throw new Exception();
		}
		return $trace[$stack]['file'];
	}

/**
 * Identify current plugin.
 *
 * @param path $pwd Optional. Path to working directory.
 * @return mixed The current plugin's name or false.
 */
	public static function plugin($pwd = null) {
		if (empty($pwd)) {
			$pwd = self::path();
		}

		$plugins = CakePlugin::loaded();
		if (array_keys(self::$_plugins) != $plugins) {
			self::$_plugins = array();
			foreach ($plugins as $plugin) {
				self::$_plugins[$plugin] = CakePlugin::path($plugin);
			}
		}

		foreach (self::$_plugins as $plugin => $path) {
			if (false !== strpos($pwd, $path)) {
				return $plugin;
			}
		}

		return false;
	}

/**
 * Gets the custom prefix for any given plugin's built-in prefix. Defaults to
 * the original plugin's prefix if none is defined.
 *
 * @param string $prefix Plugin's built-in prefix.
 * @param string $plugin Plugin's name. Can be identified using `Reveal::plugin()` but slower.
 * @return string
 */
	public static function pluginPrefix($prefix, $plugin = null) {
		if (empty($plugin)) {
			$plugin = self::plugin(self::path(1));
		}

		$prefixes = array_flip((array) Configure::read($plugin . '.routingPrefixes'));
		if (!array_key_exists($prefix, $prefixes)) {
			return $prefix;
		}

		return $prefixes[$prefix];
	}

/**
 * Reloads default Reveal rules. Resets all class variables and
 * removes all non-default rules.
 *
 * @return void
 */
	public static function reload() {
		if (empty(self::$_initialState)) {

			foreach ((array) Configure::read('Routing.prefixes') as $prefix) {
				self::addRule('Page.' . $prefix, array('self', '__isPage'), $prefix);
			}

			self::$_initialState = get_class_vars('Reveal');
			return;
		}
		foreach (self::$_initialState as $key => $val) {
			if ($key != '_initialState') {
				self::${$key} = $val;
			}
		}
	}

/**
 * Used to determine the current plugin's version.
 *
 * @param string $plugin Optional. Plugin's name.
 * @return string Plugin's version.
 */
	public static function version($plugin = null) {
		if (empty($plugin)) {
			try {
				$plugin = self::plugin(self::path(true));
			} catch (Exception $e) {}
		}

		if (empty($plugin)) {
			throw new Exception();
		}

		$name = $plugin . '.version';

		if (!Configure::check($name)) {
			$versionPath = CakePlugin::path($plugin) . 'VERSION';
			if (!file_exists($versionPath)) {
				throw new Exception();
			}
			$content = file($versionPath);
			Configure::write($name, trim(array_pop($content)));
		}

		return Configure::read($name);
	}

/**
 * [_is description]
 * @param boolean $breakOn When to break and exit the conjunction check. On FALSE if 'AND', TRUE if 'OR'.
 * @param mixed $args Can be the path to a single rule, multiple paths as an array or conjunction of paths.
 * @return boolean True on success, false otherwise.
 */
	protected static function _is($breakOn, $args) {
		foreach ((array) $args as $path) {
			if (is_array($path)) {
				foreach ($path as $conj => $path) {
					if (!is_array($path)) {
						throw new CommonRevealException('A conjunction must include 2 or more paths.');
					}
					if ($breakOn === self::_is('AND' != strtoupper($conj), $path)) {
						return $breakOn;
					}
				}
				continue;
			}
			$rule = Hash::extract(self::$rules, $path);
			if (empty($rule)) {
				throw new CommonRevealException(__d('common', "Rule '%s' is not defined.", $path));
			}
			if ($breakOn === self::_execute($rule)) {
				return $breakOn;
			}
		}
		return true;
	}

/**
 * Executes a rule's callback by passing the arguments and comparing the result to the 'successOn' value.
 *
 * @param array $rule The defined rule's callback, arguments and successOn value.
 * @return boolean True on success, false otherwise.
 */
	protected static function _execute($rule) {
		if (!isset($rule[2])) {
			$rule[2] = true;
		}

		if (!isset($rule[1])) {
			$rule[1] = array();
		}

		list($callback, $args, $successOn) = $rule;

		if (!is_callable($callback)) {
			if (is_array($callback)) {
				$callback = implode('::', $callback);
			}
			throw new CommonRevealException(__d('common', "Callback '%s()' is not defined.", $callback));
		}

		if (!is_array($args)) {
			$args = array($args);
		}

		if (count($args)) {
			$result = call_user_func_array($callback, $args);
		} else {
			$result = call_user_func($callback);
		}

		if (is_array($successOn)) {
			return in_array($result, $successOn);
		} else {
			return $result == $successOn;
		}
	}

/**
 * Checks if 'DebugKit' is loaded and if either debug > 0 or 'DebugKit.forceEnable' is set to true.
 *
 * @return boolean True on success.
 */
	private static function __isDebugKitEnabled() {
		return self::is('DebugKit.loaded') && (Configure::read('debug') || Configure::read('DebugKit.forceEnable'));
	}

/**
 * Checks if 'DebugKit' is loaded, if either debug > 0 or 'DebugKit.forceEnable' is set to true, and if either
 * 'DebugKit.autoRun' is set to true or `$_GET['debug']` is passed with value of 'true'
 *
 * @return boolean True on success.
 */
	private static function __isDebugKitRunning() {
		return self::is(
			'DebugKit.enabled',
			array('OR' => array(
				'DebugKit.automated',
				'DebugKit.requested'
			))
		);
	}

/**
 * Checks if the `$_GET['debug']` is passed with value of true.
 *
 * @return boolean True on success.
 */
	private static function __isDebugKitRequested() {
		return isset($_GET['debug']) && 'true' == $_GET['debug'];
	}

/**
 * Checks if the current route's prefix returned by `Router::getParams()` matches the passed `$prefix` or
 * undefined if `$prefix` is null.
 *
 * @param string $prefix Optional. Prefix to match or nothing if checking an un-prefixed route.
 * @return boolean True on success.
 */
	private static function __isPage($prefix = null) {
		$params = Router::getParams();

		return (
			(empty($prefix) && empty($params['prefix']))
			|| (true === $prefix && !empty($params['prefix']) && $params[$params['prefix']])
			|| (isset($params['prefix']) && $params['prefix'] == $prefix && isset($params[$prefix]) && $params[$prefix])
		);
	}

/**
 * Checks if the current page matches the passed `$url`.
 *
 * @param string $url Regular expression or internal URL (full or partial) to check against.
 * @param boolean $exact Optional. Set to false if using regular expression or partial URL.
 * @return boolean True on success.
 */
	private static function __isPageCurrent($url, $exact = true) {
		if (!$exact) {
			return preg_match('/' . preg_quote($url) . '/', Router::url());
		}

		$params = Router::getParams();
		$defaultRoute = Hash::normalize(array('plugin', 'controller', 'action', 'prefix', 'named', 'pass', 'lang'));
		if (!empty($params['prefix'])) {
			$defaultRoute[$params['prefix']] = null;
		}

		return Router::normalize(array_intersect_recursive($params, $defaultRoute)) == $url;
	}

/**
 * Checks if the current route returned by `Router::getParams()` matches '/pages/display/home'.
 *
 * @return boolean True on success.
 */
	private static function __isPageFront() {
		$params = Router::getParams();

		return (
			!empty($params['controller']) && 'pages' == $params['controller']
			&& !empty($params['action']) && 'display' == $params['action']
			&& !empty($params['pass']) && in_array('home', (array) $params['pass'])
		);
	}

/**
 * Checks if the current route return by `Router::getParams()` matches '/users/login'.
 *
 * @return boolean True on success.
 */
	private static function __isPageLogin() {
		$params = Router::getParams();

		return (
			!empty($params['controller']) && 'users' == $params['controller']
			&& !empty($params['action']) && 'login' == $params['action']
		);
	}

	private static function __isPageTest() {
		return '/test.php' == env('SCRIPT_NAME');
	}

}

Reveal::reload();
