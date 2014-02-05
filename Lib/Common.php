<?php
/**
 * Common class
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

App::uses('Reveal', 'Common.Lib');

/**
 * Common
 *
 * @package       Common.Lib
 */
class Common {

/**
 * URI Schemes.
 *
 * @var array
 * @see http://en.wikipedia.org/wiki/URI_scheme
 */
	public static $uriSchemes = array(
		'adiumxtra',
		'aim',
		'apt',
		'attachment',
		'bitcoin',
		'callto',
		'chrome',
		'chrome-extension',
		'cvs',
		'data',
		'dict',
		'dns',
		'facetime',
		'feed',
		'file',
		'ftp',
		'geo',
		'git',
		'gopher',
		'gtalk',
		'h323',
		'hcp',
		'http',
		'https',
		'icap',
		'icon',
		'im',
		'imap',
		'info',
		'ipn',
		'ipp',
		'irc',
		'irc6',
		'ircs',
		'jabber',
		'jar',
		'jms',
		'lastfm',
		'ldap',
		'ldaps',
		'mailto',
		'maps',
		'market',
		'message',
		'mid',
		'mms',
		'ms-help',
		'msnim',
		'msrp',
		'msrps',
		'mtqp',
		'news',
		'nfs',
		'nntp',
		'palm',
		'paparazzi',
		'pop',
		'query',
		'rtmp',
		'secondlife',
		'session',
		'sftp',
		'sip',
		'sips',
		'skype',
		'sms',
		'snews',
		'spotify',
		'ssh',
		'svn',
		'tag',
		'tel',
		'telnet',
		'webcal',
		'xmpp',
		'ymsgr',
	);

/**
 * Get email instance.
 *
 * @param string $config Email configuration to use. Defaults to value of 'Email.config' and falls back to 'default'.
 * @param boolean $reset Reset configuration or not. Defaults to true.
 * @param string $className Email class to use. Defaults to value of 'Email.classname' and falls back to 'CakeEmail'.
 * @return CakeEmail instance.
 */
	public static function getEmail($config = null, $reset = true, $className = null) {
		if (empty($config)) {
			$config = Common::read('Email.config', 'default');
		}

		if (empty($className)) {
			$className = Common::read('Email.classname', 'CakeEmail');
		}
		list($plugin, $className) = pluginSplit($className, true);

		$key = ucfirst(Inflector::camelize($config)) . 'Email';

		if (!ClassRegistry::isKeySet($key)) {
			App::uses($className, $plugin . 'Network/Email');
			ClassRegistry::addObject($key, new $className($config));
		}

		$Email = ClassRegistry::getObject($key);

		if ($reset) {
			$Email->reset()->config($config);
		}

		return $Email;
	}

/**
 * Get log instance.
 *
 * @param [type] $className [description]
 * @return CakeLog instance.
 */
	public static function getLog($className = null) {
		if (empty($className)) {
			$className = Common::read('Log.classname', 'CakeLog');
		}
		list($plugin, $className) = pluginSplit($className, true);

		$key = 'CommonLog';

		if (!ClassRegistry::isKeySet('CommonLog')) {
			App::uses($className, $plugin . 'Log');
			ClassRegistry::addObject('CommonLog', new $className);
		}

		$Log = ClassRegistry::getObject('CommonLog');

		return $Log;
	}

/**
 * Unique array merge. If the key or value (in case the key is numeric)
 * of the original array exists in the array to merge, overwrite it.
 *
 * @param array $data Array to be merged.
 * @param array $merge Array to merge with.
 * @return array Resulting array.
 */
	public static function merge($data, $merge) {
		foreach ($data as $key => $config) {
			$name = $key;
			if (is_numeric($name)) {
				$name = $config;
			}

			foreach ($merge as $k => $v) {
				if (in_array($name, array($k, $v), true)) {
					unset($data[$key]);
				}
			}

		}

		return array_merge($data, $merge);
	}

/**
* Generates a random string.
*
* @param int $len Optional. String's length.
* @param string $char Optional. Valid characters to randomize from.
* @return string Random string.
*/
	public static function random($len = 20, $char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
		$str = '';
		for ($ii = 0; $ii < $len; $ii++) {
			$str .= $char[mt_rand(0, strlen($char) - 1)];
		}

		return $str;
	}

/**
 * Get configuration environment variable ($_SERVER[...]) and overwrites it
 * with equivalent key from runtime configuration. If none found, uses
 * `$default` value.
 *
 * Example:
 *
 *     `$_SERVER['REDIS_HOST']` would be defined in runtinme as `Redis.host`
 *
 * @param string $name Variable to obtain. Use '.' to access array elements.
 * @param mixed $default Optional. Default value to return if variable not configured.
 * @param string $plugin Optional. Name of plugin that may have over-written the configuration key.
 * @return mixed Variable's value.
 */
	public static function read($name, $default = null, $plugin = null) {
		if (!is_null($plugin)) {
			$result = Common::read("$plugin.$name");
			if (!is_null($result)) {
				return $result;
			}
		}

	  $key = str_replace('.', '_', strtoupper($name));

	  if (isset($_SERVER[$key])) {
	    return $_SERVER[$key];
	  }

	  if (Configure::check($name)) {
	    return Configure::read($name);
	  }

	  return $default;
	}

/**
 * Setup configuration reader.
 *
 * @param mixed $config Optional. Reader configuration options as array or just name (i.e. ini)
 * @param boolean $default Optional. If true, make this configuration the default one.
 * @return array Full configuration settings.
 */
	public static function reader($config = null, $default = false) {
		if (empty($config)) {
			$config = array(
				'id' => 'json',
				'className' => 'JsonReader',
				'location' => 'Common.Configure'
			);
		}

		if (!is_array($config)) {
			$config = array(
				'id' => $config,
				'className' => ucfirst($config) . 'Reader',
				'location' => 'Configure'
			);
		}

		extract($config);

		if (Configure::configured($id)) {
			throw new ConfigureException(__d('_common_', "A reader with the '%s' name already exists.", $id));
		}

		foreach (array('id', 'className', 'location') as $var) {
			if (!isset($$var) || empty($$var)) {
				throw new ConfigureException(__d('_common_', "Reader configuration missing the '%s' key.", $key));
			}
		}

		App::uses($className, $location);
		Configure::config($default ? 'default' : $id, new $className);

		return $config;
	}

/**
 * Starts a benchmarking timer only if DebugKit is enabled.
 *
 * @param string $name The name of the timer to start.
 * @param string $message A message for your timer
 * @return bool Always true
 */
	public static function startTimer($name, $message = null) {
		if (!Reveal::is('DebugKit.running')) {
			return;
		}

		App::uses('DebugTimer', 'DebugKit.Lib');
		DebugTimer::start($name, $message);
	}

/**
 * Stops a benchmarking timer.
 *
 * $name should be the same as the $name used in startTimer().
 *
 * @param string $name The name of the timer to end.
 * @return boolean true if timer was ended, false if timer was not started.
 */
	public static function stopTimer($name) {
		if (!Reveal::is('DebugKit.running')) {
			return;
		}

		DebugTimer::stop($name);
	}

/**
 * Transform CakePHP related URL strings into an array.
 *
 * @param mixed $url URL.
 * @return array
 */
	public static function url($url) {
		if (
			is_array($url)
			|| (is_string($url) && (preg_match('@^' . implode('|', Common::$uriSchemes) . ':@i', $url) || preg_match('@^#@i', $url)))
		) {
			return $url;
		}

		// Reset current language that gets changed by `I18n.I18nRoute::parse()`.
		$reset = Configure::check('Config.language') && $lang = Configure::read('Config.language');
		$parsed = Router::parse($url);
		if ($reset) Configure::write('Config.language', $lang);

		if ($parsed === false) {
			return $url;
		}

		if (!empty($parsed['pass'])) {
			$parsed = array_merge($parsed, $parsed['pass']);
			unset($parsed['pass']);
		}

		if (!empty($parsed['named'])) {
			foreach ($parsed['named'] as $param => $val) {
				$parsed[$param] = $val;
			}
			unset($parsed['named']);
		}

		if (array_key_exists('lang', $parsed) && strpos($url, $parsed['lang']) !== 1) {
			unset($parsed['lang']);
		}

		return $parsed;
	}

/**
 * Generate plugin's wiki uri.
 *
 * @param string $page Optional. Page's name.
 * @param string $repo Optional. Repository's name.
 * @return string Full uri to wiki's page.
 * @throws Exception
 * @todo replace Exception by custom one.
 */
	public static function wiki($page = 'home', $repo = null) {
		if (empty($repo)) {
			try {
				$plugin = Reveal::plugin(Reveal::path(true));
			} catch (Exception $e) {}

			if (empty($plugin)) {
				throw new Exception();
			}

			$repo = 'gourmet/' . strtolower($plugin);
		}
		return sprintf('https://github.com/%s/wiki/%s', $repo, $page);
	}

}
