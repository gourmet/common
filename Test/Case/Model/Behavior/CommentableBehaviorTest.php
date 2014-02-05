<?php

App::uses('Model', 'Model');
App::uses('CommonTestCase', 'Common.TestSuite');
App::uses('CommentableBehavior', 'Common.Model/Behavior');

class CommonTestCommentablePost extends Model {

	public $actsAs = array('Common.Commentable' => array(
		'commentModel' => 'CommonTestCommentableComment',
		'userModel' => 'CommentableUser'
	));

}

class CommentableBehaviorTest extends CommonTestCase {

	public $fixtures = array(
		'plugin.common.common_test_commentable_comment',
		'plugin.common.common_test_commentable_post',
		'plugin.common.common_test_commentable_user',
	);

	public function setUp() {
		parent::setUp();
		$this->Post = ClassRegistry::init('CommonTestCommentablePost');
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->Post);
	}

	public function testSetup() {
		$result = $this->Post->Behaviors->Commentable->settings[$this->Post->alias];
		$expected = array(
			'commentModel' => 'CommonTestCommentableComment',
			'hasManyAssoc' => array('dependent' => false),
			'belongsToAssoc' => array('counterCache' => true),
			'userModel' => 'CommentableUser',
			'allowThreaded' => true
		);
		$this->assertEqual($result, $expected);

		$alias = $this->Post->alias . 'Comment';

		$result = $this->Post->getAssociated('hasMany');
		$expected = array($alias);
		$this->assertEqual($result, $expected);

		$result = $this->Post->{$alias}->getAssociated('belongsTo');
		$expected = array($this->Post->alias);
		$this->assertEqual($result, $expected);

		// $this->assertTrue($this->Post->{$alias}->Behaviors->loaded('Tree'));
	}

	public function testComment() {
		$alias = $this->Post->alias . 'Comment';
		$data = array(
			$alias => array(
				'body' => 'My first comment',
			)
		);

		$this->markTestIncomplete();
	}
}
