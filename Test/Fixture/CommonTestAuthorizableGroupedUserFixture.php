<?php
/**
 * CommonTestAuthorizableGroupedUserFixture
 *
 */
class CommonTestAuthorizableGroupedUserFixture extends CommonTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'email' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'email' => array('column' => 'email', 'unique' => 1)
		),
	);

/**
 * {@inheritdoc}
 */
	public $records = array(
		array(
			'id' => 1,
			'email' => 'foo@bar.com'
		),
		array(
			'id' => 2,
			'email' => 'bar@foo.com'
		)
	);
}
