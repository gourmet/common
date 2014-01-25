<?php

App::uses('Model', 'Model');
App::uses('CommonTestCase', 'Common.TestSuite');
App::uses('ConfirmableBehavior', 'Common.Model/Behavior');

class CommonTestConfirmableUser extends Model {

	public $actsAs = array('Common.Confirmable');

	public $validate = array(
		'email' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'required' => true,
				'allowEmpty' => false,
				'message' => "Can not be empty.",
			),
			'email' => array(
				'rule' => array('email'),
				'message' => "Invalid email address.",
			),
			'unique' => array(
				'rule' => array('isUnique', 'email'),
				'message' => "Already exists.",
			)
		),
	);

}

class ConfirmableBehaviorTest extends CommonTestCase {

	public $fixtures = array(
		'plugin.common.common_test_confirmable_user',
	);

	public function setUp() {
		parent::setUp();
		$this->User = ClassRegistry::init('CommonTestConfirmableUser');
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->User);
	}

	public function testBeforeValidate() {
		$this->User->data = array($this->User->alias => array(
			'email' => 'foo@bar.com',
			'email_confirm' => 'foo@bar.com'
		));
		$this->User->Behaviors->Confirmable->beforeValidate($this->User);

		$ModelValidator = $this->User->validator();
		$result = $ModelValidator->getField('email_confirm')->getRule('confirmed');
		$this->assertInstanceOf('CakeValidationRule', $result);

		$result = array_keys($ModelValidator->getField('email_confirm')->getRules());
		$expected = array('notEmpty', 'email', 'unique', 'confirmed');
		$this->assertEqual($result, $expected);

		$this->User->data = array($this->User->alias => array(
			'lorem' => 'foo@bar.com',
			'lorem_confirm' => 'foo@bar.com'
		));
		$this->User->Behaviors->Confirmable->beforeValidate($this->User);

		$ModelValidator = $this->User->validator();
		$result = $ModelValidator->getField('lorem_confirm')->getRule('confirmed');
		$this->assertInstanceOf('CakeValidationRule', $result);

		$result = array_keys($ModelValidator->getField('lorem_confirm')->getRules());
		$expected = array('confirmed');
		$this->assertEqual($result, $expected);
	}

	public function testIsConfirmed() {
		$this->User->data = array($this->User->alias => array(
			'email' => 'foo@bar.com',
			'email_confirm' => 'foo@bar.com'
		));
		$result = $this->User->isConfirmed(array('email_confirm' => 'foo@bar.com'));
		$this->assertTrue($result);

		$this->User->data = array($this->User->alias => array(
			'email' => 'foo@bar.com',
			'email_confirm' => 'bar@foo.com'
		));
		$result = $this->User->isConfirmed(array('email_confirm' => 'bar@foo.com'));
		$this->assertFalse($result);
	}

}
