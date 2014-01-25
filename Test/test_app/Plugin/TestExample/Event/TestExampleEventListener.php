<?php

App::uses('CakeEventListener', 'Event');

class TestExampleEventListener implements CakeEventListener {

	public function implementedEvents() {
		return array(
			'Controller.constructClasses' => array('callable' => 'controllerConstructClasses'),
			'Model.construct' => array('callable' => 'modelConstruct'),
		);
	}

	public function controllerConstructClasses(CakeEvent $Event) {
		$components = array('TestExample.TestExample', 'Session' => array('foo' => 'bar'), 'Email');
		$helpers = array('TestExample.TestExample');
		$Event->result = Hash::merge((array) $Event->result, compact('components', 'helpers'));
	}

	public function modelConstruct(CakeEvent $Event) {
		$Event->result['actsAs'] = array('TestExample.TestExample');
	}

}
