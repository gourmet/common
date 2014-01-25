<?php
/**
 * CommonBadConfigurationException
 *
 * PHP 5
 *
 * Copyright 2013, Jad Bitar (http://jadb.io)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2013, Jad Bitar (http://jadb.io)
 * @link          http://github.com/gourmet/url_shortener
 * @since         0.1.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * This exception will be thrown when a configuration key needs to be changed to perform
 * action.
 *
 * @package       Common.Error.Exception
 */
class CommonBadConfigurationException extends CakeException {

	protected $_messageTemplate = "Bad configuration value for the '%s' key.";

	public function __construct($message = null, $code = 404) {
		if (Configure::read('debug')) {
			$code = 500;
		}

		parent::__construct($message, $code);
	}

}

