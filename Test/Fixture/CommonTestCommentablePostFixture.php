<?php
/**
 * CommonTestCommentablePostFixture
 *
 */
class CommonTestCommentablePostFixture extends CakeTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'title' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'body' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'comment_count' => array('type' => 'integer', 'null' => false, 'default' => 0),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
		),
	);

	public $records = array(
		array(
			'id' => 1,
			'title' => 'Hello',
			'body' => 'Hello world!'
		),
	);

}
