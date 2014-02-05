<?php

App::uses('CommonAppModel', 'Common.Model');
App::uses('CommonTestCase', 'Common.TestSuite');

class CommonTestDetailableUser extends CommonAppModel {

	public $actsAs = array('Common.Detailable');

	public $detailSchema = array(
		'user' => array(
			'fname' => array('type' => 'string', 'length' => 16),
			'lname' => array('type' => 'string', 'length' => 16),
			'password' => array('type' => 'string', 'length' => 64)
		),
		'demographics' => array(
			'country' => array('type' => 'string', 'length' => 32),
			'city' => array('type' => 'string', 'length' => 32),
			'age' => array('type' => 'integer', 'length' => 3),
			'marital_status' => array('type' => 'string', 'length' => 16),
		),
	);

}

class DetailableBehaviorTest extends CommonTestCase {

/**
 * {@inheritdoc}
 */
	public $fixtures = array(
		'plugin.common.common_test_detailable_user',
	);

/**
 * {@inheritdoc}
 */
	public function setUp() {
		parent::setUp();
		$this->User = ClassRegistry::init('CommonTestDetailableUser');
	}

/**
 * {@inheritdoc}
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->User);
		ClassRegistry::flush();
	}

	public function testSetup() {
		$detailModel = $this->User->alias . 'Detail';

		$expected = array(
			'className' => 'Common.' . $detailModel,
			'foreignKey' => 'foreign_key',
			'conditions' => array(
				'CommonTestDetailableUserDetail.foreign_model' => 'CommonTestDetailableUser'
			),
			'order' => array(
				'CommonTestDetailableUserDetail.position' => 'ASC'
			),
			'fields' => '',
			'limit' => '',
			'offset' => '',
			'dependent' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => '',
			'association' => 'hasMany'
		);
		$result = $this->User->getAssociated($detailModel);
		$this->assertEqual($result, $expected);

		$expected = array(
			'className' => 'CommonTestDetailableUser',
			'foreignKey' => 'foreign_key',
			'conditions' => array(
				'CommonTestDetailableUserDetail.foreign_model' => 'CommonTestDetailableUser'
			),
			'fields' => '',
			'order' => '',
			'counterCache' => '',
			'association' => 'belongsTo'
		);
		$result = $this->User->{$detailModel}->getAssociated('Parent');
		$this->assertEqual($result, $expected);

		$expected = array(
			'class' => 'Common.CommonDetailModel',
			'alias' => 'CommonTestDetailableUserDetail'
		);
		$result = $this->User->Behaviors->Detailable->settings[$this->User->alias];
		$this->assertEqual($result, $expected);
	}

	public function testAfterSave() {
		$detailModel = $this->User->alias . 'Detail';

		$data = array(
			$this->User->alias => array('email' => 'test@example.com'),
			$detailModel => array('user' => array('fname' => 'test', 'lname' => 'example'))
		);

		$this->User->create($data);
		$this->User->save();

		$expected = 2;
		$result = $this->User->{$detailModel}->find('count', array('conditions' => array('foreign_model' => $this->User->alias, 'foreign_key' => $this->User->id)));
		$this->assertEqual($result, $expected);

		$data = array(
			$detailModel => array('user' => array('fname' => 'test2', 'lname' => 'example2'))
		);
		$this->User->save($data);

		$expected = 2;
		$result = $this->User->{$detailModel}->find('count', array('conditions' => array('foreign_model' => $this->User->alias, 'foreign_key' => $this->User->id)));
		$this->assertEqual($result, $expected);

		$data = array(
			$this->User->alias => array('id' => 1),
			$detailModel => array('user' => array('fname' => 'test', 'lname' => 'example'))
		);

		$this->User->create($data);
		$this->User->save();

		$expected = 2;
		$result = $this->User->{$detailModel}->find('count', array('conditions' => array('foreign_model' => $this->User->alias, 'foreign_key' => 1)));
		$this->assertEqual($result, $expected);
	}

}
