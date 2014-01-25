<?php

App::uses('Model', 'Model');
App::uses('CommonTestCase', 'Common.TestSuite');
App::uses('TotalizableBehavior', 'Common.Model/Behavior');

class CommonTestTotalizableUser extends Model {

	public $hasMany = array('Invoice' => array('className' => 'CommonTestTotalizableInvoice', 'foreignKey' => 'user_id'));

}

class CommonTestTotalizableInvoice extends Model {

	public $actsAs = array('TestTotalizable');

	public $belongsTo = array('User' => array(
		'className' => 'CommonTestTotalizableUser',
		'foreignKey' => 'user_id',
		'totalCache' => true
	));

}

class TestTotalizableBehavior extends TotalizableBehavior {

	public function prepareUpdateFields(Model $Model) {
		return $this->_prepareUpdateFields($Model);
	}

}

class TotalizableBehaviorTest extends CommonTestCase {

	public $fixtures = array(
		'plugin.common.common_test_totalizable_user',
		'plugin.common.common_test_totalizable_invoice'
	);

	public function setUp() {
		parent::setUp();
		$this->User = ClassRegistry::init('CommonTestTotalizableUser');
		$this->Invoice = ClassRegistry::init('CommonTestTotalizableInvoice');
		$this->Totalizable = $this->Invoice->Behaviors->TestTotalizable;
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->User, $this->Invoice);
		ClassRegistry::flush();
	}

	public function testSetup() {
		$result = $this->Invoice->Behaviors->TestTotalizable->settings[$this->Invoice->alias];
		$expected = array('totalQuery' => 'SUM(total)');
		$this->assertEqual($result, $expected);

		$this->Invoice->Behaviors->detach('TestTotalizable');
		$this->Invoice->Behaviors->attach('TestTotalizable', array('SUM(foo)'));

		$result = $this->Invoice->Behaviors->TestTotalizable->settings[$this->Invoice->alias];
		$expected = array('totalQuery' => 'SUM(foo)');
		$this->assertEqual($result, $expected);

		$this->Invoice->Behaviors->detach('TestTotalizable');
		$this->Invoice->Behaviors->attach('TestTotalizable', array('totalQuery' => 'SUM(bar)'));

		$result = $this->Invoice->Behaviors->TestTotalizable->settings[$this->Invoice->alias];
		$expected = array('totalQuery' => 'SUM(bar)');
		$this->assertEqual($result, $expected);
	}

	public function testTotalIncrementOnSaveAndDecrementOnDelete() {
		$this->User->save(array('name' => 'John Doe'));
		$data = array('Invoice' => array('total' => 100, 'user_id' => $this->User->id));
		$this->User->Invoice->save($data);
		$result = $this->User->find('first', array('fields' => array('invoice_total'), 'recursive' => -1));
		$expected = array('CommonTestTotalizableUser' => array('invoice_total' => 100));
		$this->assertEqual($result, $expected);

		$this->User->Invoice->create($data);
		$this->User->Invoice->save($data);
		$result = $this->User->find('first', array('fields' => array('invoice_total'), 'recursive' => -1));
		$expected = array('CommonTestTotalizableUser' => array('invoice_total' => 200));
		$this->assertEqual($result, $expected);

		$this->User->Invoice->delete();
		$result = $this->User->find('first', array('fields' => array('invoice_total'), 'recursive' => -1));
		$expected = array('CommonTestTotalizableUser' => array('invoice_total' => 100));
		$this->assertEqual($result, $expected);
	}

}
