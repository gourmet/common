<?php

App::uses('Model', 'Model');
App::uses('CommonTestCase', 'Common.TestSuite');
App::uses('FilterableBehavior', 'Common.Model/Behavior');

class CommonTestFilterableUser extends Model {

	public $actsAs = array('Common.Filterable');

}

class FilterableBehaviorTest extends CommonTestCase {

	public $fixtures = array(
		'plugin.common.common_test_filterable_user',
	);

	public function setUp() {
		parent::setUp();
		$this->User = ClassRegistry::init('CommonTestFilterableUser');
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->User);
	}

	public function testSetup() {
		$result = $this->User->Behaviors->Filterable->settings[$this->User->alias];
		$expected = array('password' => null);
		$this->assertEqual($result, $expected);
	}

	public function testAfterFind() {
		$result = $this->User->find('first');
		$this->assertTrue(!array_key_exists('password', $result[$this->User->alias]));

		$result = $this->User->find('all');
		$this->assertEmpty(Hash::extract($result, '{n}.' . $this->User->alias . '.password'));
	}

}
