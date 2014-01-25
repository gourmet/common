<?php
/**
 * InteractiveAppShell
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

App::uses('CommonAppShell', 'Common.Console');

/**
 * Interactive application's shell
 *
 * This file needs to be extended by any shell that requires interactive-type of
 * features.
 *
 * @package       Common.Controller
 */
class InteractiveAppShell extends CommonAppShell {

/**
 * Interactive shell options.
 *
 * @var array
 */
	private $__options = array(
		'Q' => 'quit'
	);

/**
 * Exit interactive shell.
 *
 * @return void
 */
	public function quit() {
		$this->_stop();
	}

}
