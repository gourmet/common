<?php
/**
 * CommonTestFilterableUserFixture
 *
 */
class CommonTestFilterableUserFixture extends CommonTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'password' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'email' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
		),
	);

	public $records = array(
		array(
			'id' => 1,
			'password' => 'foo',
			'email' => 'foo@example.com'
		),
		array(
			'id' => 2,
			'password' => 'bar',
			'email' => 'bar@example.com'
		),
	);

}
