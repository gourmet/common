<?php
/**
 * JsonReader
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
 * Json file configuration engine.
 *
 * @package       Common.Configure
 */
class JsonReader implements ConfigReaderInterface {

/**
 * The path this reader finds files on.
 *
 * @var string
 */
	protected $_path = null;

/**
 * Constructor for PHP Config file reading.
 *
 * @param string $path The path to read config files from. Defaults to APP . 'Config' . DS
 */
	public function __construct($path = null) {
		if (!$path) {
			$path = APP . 'Config' . DS;
		}
		$this->_path = $path;
	}

/**
 * {@inheritdoc}
 */
	public function read($key) {
		if (strpos($key, '..') !== false) {
			throw new ConfigureException(__d('cake_dev', 'Cannot load configuration files with ../ in them.'));
		}

		$filename = $this->_getFilePath($key);

		if (!is_file($filename)) {
			if (!is_file(substr($filename, 0, -5))) {
				throw new ConfigureException(__d('cake_dev', 'Could not load configuration files: %s or %s', $filename, substr($filename, 0, -5)));
			}
		}

		$contents = file_get_contents($filename);

		if (empty($contents)) {
			return array();
		}

		$json = json_decode($contents, true);

		if (empty($json)) {
			if ($json === null) {
				throw new ConfigureException(__d('cake_dev', 'There was a problem decoding JSON in file: %s', $filename));
			} else {
				return array();
			}
		}

		return $json;
	}

/**
 * {@inheritdoc}
 */
	public function dump($key, $data) {
		$contents = json_encode($data);
		$filename = $this->_getFilePath($key);
		return file_put_contents($filename, $contents);
	}

/**
 * Get file path
 *
 * @param string $key The identifier to write to. If the key has a . it will be treated
 *  as a plugin prefix.
 * @return string Full file path.
 */
	protected function _getFilePath($key) {
		if (substr($key, -5) === '.json') {
			$key = substr($key, 0, -5);
		}
		list($plugin, $key) = pluginSplit($key);
		$key .= '.json';

		if ($plugin) {
			$file = App::pluginPath($plugin) . 'Config' . DS . $key;
		} else {
			$file = $this->_path . $key;
		}

		return $file;
	}

}
