<?php

App::uses('Model', 'Model');
App::uses('CommonTestCase', 'Common.TestSuite');

class CommonTestOriginal extends Model {

	public $actsAs = array('Common.Duplicatable' => array('CommonTestDuplicate'));

}

class CommonTestDuplicate extends Model {

}

class DuplicatableBehaviorTest extends CommonTestCase {

	public $fixtures = array(
		'plugin.common.common_test_original',
		'plugin.common.common_test_duplicate'
	);

	public function setUp() {
		parent::setUp();
		$this->Original = ClassRegistry::init('CommonTestOriginal');
		$this->Duplicate = ClassRegistry::init('CommonTestDuplicate');
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->Original, $this->Duplicate);
		ClassRegistry::flush();
	}

	public function testSetup() {
		$result = $this->Original->Behaviors->Duplicatable->settings[$this->Original->alias]['model'];
		$expected = 'CommonTestDuplicate';
		$this->assertEqual($result, $expected);

		$this->Original->Behaviors->detach('Duplicatable');
		$this->Original->Behaviors->attach('Common.Duplicatable', array('AnyOtherModel', 'duplicatedKey' => 'foreign_key', 'duplicatedModel' => 'model', 'scope' => null));

		$result = $this->Original->Behaviors->Duplicatable->settings[$this->Original->alias];
		$expected = array('duplicatedKey' => 'foreign_key', 'duplicatedModel' => 'model', 'mapFields' => array(), 'model' => 'AnyOtherModel', 'scope' => null);
		$this->assertEqual($result, $expected);

		$this->expectException('Exception', __d('common', "Missing duplicate table for '%s'", $this->Original->name));
		$this->Original->Behaviors->detach('Duplicatable');
		$this->Original->Behaviors->attach('Common.Duplicatable', array('duplicatedKey' => 'foreign_key', 'duplicatedModel' => 'model'));
	}

	public function testAfterSave() {
		$data = array('CommonTestOriginal' => array(
			'name' => 'TestDuplicate',
		));

		$result = $this->Original->save($data);
		$this->assertNotEmpty($result);

		$result = $this->Duplicate->findByDuplicatedKey($this->Original->id);
		$this->assertNotEmpty($result);
	}

	public function testDuplicate() {
		$data = array('CommonTestOriginal' => array(
			'id' => 1,
			'name' => 'TestDuplicate',
		));

		$result = $this->Original->duplicate($data);
		$this->assertTrue($result);

		$result = $this->Duplicate->findByDuplicatedKey($data['CommonTestOriginal']['id']);
		$this->assertNotEmpty($result);
	}

	public function testDuplicateWithMapFields() {
		$data = array(
			'CommonTestOriginal' => array(
				'id' => 1,
				'name' => 'TestDuplicate',
			),
			'AnotherModel' => array('field' => 'test_value')
		);

		$result = $this->Original->duplicate($data, array('mapFields' => array('test_field' => 'AnotherModel.field')));
		$this->assertTrue($result);

		$result = $this->Duplicate->findByTestField('test_value');
		$this->assertNotEmpty($result);
	}

	public function testDuplicateMissingPrimaryKey() {
		$data = array('CommonTestOriginal' => array(
			'name' => 'TestDuplicate',
		));

		$this->expectException('Exception', __d('common', "Missing primary key for duplicatable '%s' data", $this->Original->name));
		$this->Original->duplicate($data);
	}

}
