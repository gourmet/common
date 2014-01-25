<?php
/**
 * CommonAppHelper
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

App::uses('Helper', 'View');
App::uses('CakeEventListener', 'Event');

/**
 * Common application's helper
 *
 * This file needs to be extended by `AppHelper` in order to take advantage
 * of its built-in features.
 *
 * @package       Common.View.Helper
 */
class CommonAppHelper extends Helper {

/**
 * Overrides Object::log(). Used also in CommonAppController, CommonAppModel.
 *
 * - Support for Cake's own `scopes` (at the time I wrote this, Object::log() does not).
 * - Defaults all logs to the default scope for less writing ;)
 *
 * @param string $msg Log message
 * @param integer $type Error type constant. Defined in app/Config/core.php.
 * @return boolean Success of log write
 */
	public function log($msg, $type = LOG_ERR, $scopes = array('default')) {
		App::uses('CakeLog', 'Log');
		if (!is_string($msg)) {
			$msg = print_r($msg, true);
		}

		$scopes = (array) $scopes;
		if (!in_array('helper', $scopes)) {
			$scopes[] = 'helper';
		}

		return Common::getLog()->write($type, $msg, $scopes);
	}

/**
 * Url helper function
 *
 *  - Localize URL.
 *
 * @param string $url
 * @param bool $full
 * @return mixed
 * @access public
 */
	public function url($url = null, $full = false) {
		if (CakePlugin::loaded('I18n')) {
			$url = Common::url($url);
			if (is_array($url) && !array_key_exists('lang', $url)) {
				$url['lang'] = Configure::read('Config.language');
			}
		}

		return parent::url($url, $full);
	}

}
