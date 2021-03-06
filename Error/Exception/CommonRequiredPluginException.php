<?php
/**
 * CommonRequiredPluginException
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
 * This exception will be thrown when a required plugin is missing.
 *
 * @package       Common.Error.Exception
 */
class CommonRequiredPluginException extends InternalErrorException {

	protected $_messageTemplate = "Plugin '%s' is required.";

}

