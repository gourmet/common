<?php

App::uses('Model', 'Model');
App::uses('CommonTestCase', 'Common.TestSuite');
App::uses('TotalizableBehavior', 'Common.Model/Behavior');

class CommonTestStateableUser extends Model {

	public $actsAs = array('Common.Stateable' => array('fields' => array('status' => array('active', 'suspended'))));

}

class CommonTestStateableInvoice extends Model {

	public $actsAs = array(
		'Common.Stateable' => array(
			'fields' => array('status', 'is_paid', 'is_due' => array(0, 1)),
		)
	);

	public $validate = array(
		'status' => array(
			'state' => array(
				'rule' => array('isValidState', array('active', 'suspended')),
				'message' => 'Not a valid state',
			),
		),
		'is_paid' => array(
			'numeric' => array(
				'rule' => 'numeric',
				'message' => 'Only numeric values allowed',
				'allowEmpty' => false,
			),
		),
	);
}

class CommonTestStateableInvalid extends Model {

}

class StateableBehaviorTest extends CommonTestCase {

	public $fixtures = array(
		'plugin.common.common_test_stateable_user',
		'plugin.common.common_test_stateable_invoice',
		'plugin.common.common_test_stateable_invalid'
	);

	public function setUp() {
		parent::setUp();
		$this->User = ClassRegistry::init('CommonTestStateableUser');
		$this->Invoice = ClassRegistry::init('CommonTestStateableInvoice');
		$this->Invalid = ClassRegistry::init('CommonTestStateableInvalid');
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->User, $this->Invoice, $this->Invalid);
		ClassRegistry::flush();
	}

	public function testSetup() {
		$result = $this->User->Behaviors->Stateable->settings[$this->User->alias];
		$expected = array('fields' => array('status' => array('values' => array('active', 'suspended'), 'default' => 'active')), 'values' => array('enabled', 'disabled'), 'default' => 'enabled');
		$this->assertEqual($result, $expected);

		$result = $this->Invoice->Behaviors->Stateable->settings[$this->Invoice->alias];
		$expected = array('fields' => array('status' => array('values' => array('enabled', 'disabled'), 'default' => 'enabled'), 'is_paid' => array('values' => array('enabled', 'disabled'), 'default' => 'enabled'), 'is_due' => array('values' => array(0, 1), 'default' => 0)), 'values' => array('enabled', 'disabled'), 'default' => 'enabled');
		$this->assertEqual($result, $expected);

		$this->Invoice->Behaviors->detach('Stateable');
		$this->Invoice->Behaviors->attach('Stateable', array('fields' => array('status', 'is_paid'), 'values' => array('active', 'suspended')));
		$result = $this->Invoice->Behaviors->Stateable->settings[$this->Invoice->alias];
		$expected = array('fields' => array('status' => array('values' => array('active', 'suspended'), 'default' => 'active'), 'is_paid' => array('values' => array('active', 'suspended'), 'default' => 'active')), 'values' => array('active', 'suspended'), 'default' => 'active');
		$this->assertEqual($result, $expected);

		$this->Invoice->Behaviors->detach('Stateable');
		$this->Invoice->Behaviors->attach('Stateable', array('fields' => array('status' => array('active', 'suspended'), 'is_paid'), 'values' => array()));
		$result = $this->Invoice->Behaviors->Stateable->settings[$this->Invoice->alias];
		$expected = array('fields' => array('status' => array('values' => array('active', 'suspended'), 'default' => 'active'), 'is_paid' => array('values' => array(), 'default' => false)), 'values' => array(), 'default' => false);
		$this->assertEqual($result, $expected);

		$this->expectException('FatalErrorException', __d('affiliates', "Missing state field 'status' in table '%s'", $this->Invalid->useTable));
		$this->Invalid->Behaviors->attach('Common.Stateable');
	}

	// protected $_testsToRun = array('testCallOnChange');
	public function testCallOnChange() {
		$this->User->stateableValidation = false;

		// Fail status change for undefined record ID.
		$result = $this->User->changeStatus('suspended');
		$this->assertFalse($result);

		// Valid `status` state for record with ID 1.
		$result = $this->User->changeStatus(1, 'suspended');
		$this->assertTrue($result);
		$this->assertEqual($this->User->id, 1);

		// Nullify `status` state for record with ID 1.
		$this->User->id = null;
		$result = $this->User->changeStatus(1);
		$this->assertTrue($result);
		$this->assertEqual($this->User->id, 1);

		// Already existing `status` state value for record with ID 2.
		$this->Invoice->id = 2;
		$result = $this->Invoice->changeStatus('active');
		$this->assertFalse($result);

		// Valid `status` state value for record with ID 2 (valid fields defined in ruleset).
		$this->Invoice->id = 2;
		$result = $this->Invoice->changeStatus('suspended');
		$this->assertTrue($result);

		// Valid `is_paid` state value for record with ID 1.
		$result = $this->Invoice->changeIsPaid(1, 3);
		$this->assertTrue($result);
		$this->assertEqual($this->Invoice->id, 1);

		// Invalid `is_paid` state value ('numeric' rule).
		$result = $this->Invoice->changeIsPaid(1, 'paid');
		$this->assertFalse($result);
		$this->assertEqual($this->Invoice->id, 1);

		// Do not validate `is_paid` state value.
		$this->Invoice->id = 2;
		$result = $this->Invoice->changeIsPaid('paid', false);
		$this->assertTrue($result);
		$this->assertEqual($this->Invoice->id, 2);

		// Invalid `is_due` state value (field's valid values defined in the behavior settings).
		$this->Invoice->id = 2;
		$result = $this->Invoice->changeIsDue(3);
		$this->assertFalse($result);

		// Invalid state field.
		$this->expectException('FatalErrorException', __d('affiliates', "Missing state field configuration ('invalid_field')"));
		$this->Invoice->changeInvalidField('some_value');
	}

	public function testCallOnGet() {
		$result = $this->User->getStatus(1);
		$this->assertEqual($result, 'active');
		$this->assertTrue(empty($this->User->id));

		$result = $this->User->getStatus(1, 'active');
		$this->assertTrue($result);
		$this->assertTrue(empty($this->User->id));

		$result = $this->User->getStatus(2, 'active');
		$this->assertFalse($result);
		$this->assertTrue(empty($this->User->id));

		$result = $this->Invoice->getStatus(1);
		$this->assertEqual($result, 'active');
		$this->assertTrue(empty($this->User->id));

		$result = $this->Invoice->getIsPaid(1);
		$this->assertEqual($result, 1);
		$this->assertTrue(empty($this->User->id));

		$this->expectException('FatalErrorException', __d('affiliates', "Missing state field configuration ('invalid_field')"));
		$this->Invoice->getInvalidField(1);
	}

	public function testBeforeValidate() {
		$this->User->data = array($this->User->alias => array('status' => 'enabled'));
		$this->User->Behaviors->Stateable->beforeValidate($this->User);

		$result = $this->User->validator()->getField('status');
		$this->assertInstanceOf('CakeValidationSet', $result);
	}

}
