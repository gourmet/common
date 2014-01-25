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
		// die();
// debug($this->Post->{$alias}->{$a}->getAssociated('hasMany'));
		debug($this->Post->comment(1, $data));
		debug($this->Post->{$alias}->id);
		// debug($this->Post->{$alias}->useTable);
		// $a = $alias . 'Comment';
		// debug($this->Post->{$alias}->{$a}->useTable);die();
		debug($this->Post->{$alias}->comment($this->Post->{$alias}->id, array($this->Post->{$alias}->alias . 'Comment' => $data[$alias])));
		// debug($this->Post->Comment->read());
		debug($this->Post->find('all', array('recursive' => 2)));die();
	}
}
