<?php

App::uses('Controller', 'Controller');
App::uses('StateableHelper', 'Common.View/Helper');
App::uses('CommonTestCase', 'Common.TestSuite');

class StateableHelperTest extends CommonTestCase {

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testSkipped() {
		$this->markTestSkipped('Helper not created yet');
	}
}
