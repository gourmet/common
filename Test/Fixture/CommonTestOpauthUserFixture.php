<?php
/**
 * CommonTestOpauthUserFixture
 *
 */
class CommonTestOpauthUserFixture extends CommonTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'google_id' => array('type' => 'string', 'null' => false, 'default' => NULL),
		'google_credentials' => array('type' => 'string', 'null' => false, 'default' => NULL),
		'email' => array('type' => 'string', 'null' => false, 'default' => NULL, 'key' => 'unique'),
		'password' => array('type' => 'string', 'null' => false, 'default' => NULL),
		'first_name' => array('type' => 'string', 'null' => false, 'default' => NULL),
		'last_name' => array('type' => 'string', 'null' => false, 'default' => NULL),
		'company' => array('type' => 'string', 'null' => false, 'default' => NULL),
		'gravatar' => array('type' => 'string', 'null' => false, 'default' => NULL),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'email' => array('column' => 'email', 'unique' => 1)
		),
	);

	public $records = array(
		array(
			'id' => 1,
			'google_id' => '',
			'google_credentials' => '',
			'email' => 'user@example.com',
		),
		array(
			'id' => 2,
			'google_id' => '',
			'google_credentials' => '',
			'email' => 'john@doe.com',
		),
	);

}
