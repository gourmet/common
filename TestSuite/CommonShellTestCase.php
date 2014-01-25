<?php

App::uses('CommonTestCase', 'Common.TestSuite');
App::uses('ShellDispatcher', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');

class CommonShellTestCase extends CommonTestCase {

	public function getMockForShell($shell, $methods = array()) {
		list($plugin, $name) = pluginSplit($shell, true);
		App::uses($name, $plugin . 'Console' . DS . 'Command');

		$methods = array_merge(array('in', 'out', 'hr', 'createFile', 'error', 'err', '_stop'), $methods);

		$this->consoleInput = $this->getMock('ConsoleInput', array(), array(), '', false);;
		$this->consoleOutput = $this->getMock('ConsoleOutput', array(), array(), '', false);

		$mock = $this->getMock($name, $methods, array($this->consoleOutput, $this->consoleOutput, $this->consoleInput));

		$mock->initialize();
		$mock->params = array('help' => false, 'quiet' => false, 'verbose' => false);

		return $mock;
	}

}
