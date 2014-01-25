<?php
/**
 * CommonTestCommentableCommentFixture
 *
 */
class CommonTestCommentableCommentFixture extends CakeTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'user_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36),
		'foreign_key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'foreign_model' => array('type' => 'string', 'null' => false, 'default' => NULL),
		'parent_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36),
		'lft' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 10),
		'rght' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 10),
		'status' => array('type' => 'string', 'null' => false, 'default' => 'active'),
		'body' => array('type' => 'text', 'null' => false, 'default' => NULL),
		'name' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 64),
		'email' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 64),
		'website' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 128),
		'comment_count' => array('type' => 'integer', 'null' => false, 'default' => 0),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'FOREIGN_MODELS' => array('column' => 'foreign_model'),
			'FOREIGN_RECORD' => array('column' => array('foreign_key', 'foreign_model'), 'unique' => 1)
		),
	);

	public $records = array();

}
