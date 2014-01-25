<?php
/**
 * CommonAppShell
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

App::uses('Shell', 'Console');

/**
 * Common application's shell
 *
 * This file needs to be extended by `AppShell` in order to take advantage
 * of its built-in features.
 *
 * @package       Common.Console
 */
class CommonAppShell extends Shell {

/**
 * {@inheritdoc}
 */
	public function startup() {
		if (false !== $this->getEventManager()->trigger('Shell.startup', $this)) {
			$this->_welcome();
		}
	}

	public function getEventManager() {
		if (empty($this->_eventManager)) {
			$this->_eventManager = new CommonEventManager();
			$this->_eventManager->loadListeners($this->_eventManager, 'Shell');
			$this->_eventManager->loadListeners($this->_eventManager, $this->name);
		}
		return $this->_eventManager;
	}

/**
 * Overrides Object::log(). Used also in CommonAppController, CommonAppHelper, CommonAppShell.
 *
 * - Support for Cake's own `scopes` (at the time I wrote this, Object::log() does not).
 * - Defaults all logs to the default scope for less writing ;)
 *
 * @param string $msg Log message.
 * @param integer $type Error type constant. Defined in app/Config/core.php.
 * @return boolean Success of log write.
 */
	public function log($msg, $type = LOG_ERR, $scopes = array('default')) {
		App::uses('CakeLog', 'Log');
		if (!is_string($msg)) {
			$msg = print_r($msg, true);
		}

		$scopes = (array) $scopes;
		if (!in_array('shell', $scopes)) {
			$scopes[] = 'shell';
		}

		return Common::getLog()->write($type, $msg, $scopes);
	}

}
