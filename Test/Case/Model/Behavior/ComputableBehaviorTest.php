<?php

App::uses('Model', 'Model');
App::uses('CommonTestCase', 'Common.TestSuite');
App::uses('ComputableBehavior', 'Common.Model/Behavior');

class CommonTestComputableUser extends Model {

	public $hasMany = array(
		'Invoice' => array(
			'className' => 'CommonTestComputableInvoice',
			'foreignKey' => 'user_id'
		),
	);

}

class CommonTestComputableInvoice extends Model {

	public $actsAs = array(
		'Common.Computable' => 'total'
	);

	public $belongsTo = array(
		'User' => array(
			'className' => 'CommonTestComputableUser',
			'foreignKey' => 'user_id',
			'computedCache' => 'invoice_total'
		),
	);

}

class ComputableBehaviorTest extends CommonTestCase {

	public $fixtures = array(
		'plugin.common.common_test_computable_user',
		'plugin.common.common_test_computable_invoice'
	);

	public function setUp() {
		parent::setUp();
		$this->User = ClassRegistry::init('CommonTestComputableUser');
		$this->Invoice = ClassRegistry::init('CommonTestComputableInvoice');
		$this->Computable = $this->Invoice->Behaviors->Computable;
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->User, $this->Invoice);
		ClassRegistry::flush();
	}

	public function testSetup() {
		$settings = array('method' => 'sum', 'column' => 'total', 'result' => 'computed');

		$result = $this->Invoice->Behaviors->Computable->settings[$this->Invoice->alias];
		$expected = $settings;
		$this->assertEqual($result, $expected);

		$this->Invoice->Behaviors->detach('Computable');
		$this->Invoice->Behaviors->attach('Computable', array('foo'));

		$result = $this->Invoice->Behaviors->Computable->settings[$this->Invoice->alias];
		$expected = array_merge($settings, array('column' => 'foo'));
		$this->assertEqual($result, $expected);

		$this->Invoice->Behaviors->detach('Computable');
		$this->Invoice->Behaviors->attach('Computable', array('method' => 'avg'));

		$result = $this->Invoice->Behaviors->Computable->settings[$this->Invoice->alias];
		$expected = array_merge($settings, array('method' => 'avg', 'column' => 'value'));
		$this->assertEqual($result, $expected);
	}

	public function testTotalIncrementOnSaveAndDecrementOnDelete() {
		$this->User->save(array('name' => 'John Doe'));
		$data = array('Invoice' => array('total' => 100, 'user_id' => $this->User->id));
		$this->User->Invoice->save($data);
		$result = $this->User->find('first', array('fields' => array('invoice_total'), 'recursive' => -1));
		$expected = array('CommonTestComputableUser' => array('invoice_total' => 100));
		$this->assertEqual($result, $expected);

		$this->User->Invoice->create($data);
		$this->User->Invoice->save($data);
		$result = $this->User->find('first', array('fields' => array('invoice_total'), 'recursive' => -1));
		$expected = array('CommonTestComputableUser' => array('invoice_total' => 200));
		$this->assertEqual($result, $expected);

		$this->User->Invoice->delete();
		$result = $this->User->find('first', array('fields' => array('invoice_total'), 'recursive' => -1));
		$expected = array('CommonTestComputableUser' => array('invoice_total' => 100));
		$this->assertEqual($result, $expected);
	}

}
