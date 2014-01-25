<?php
/**
 * CommonTestAuthorizableCompanyFixture
 *
 */
class CommonTestAuthorizableCompanyFixture extends CakeTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'name' => array('type' => 'string', 'null' => false, 'default' => NULL),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
		),
	);

/**
 * {@inheritdoc}
 */
	public $records = array(
		array(
			'id' => 1,
			'user_id' => 1,
			'name' => 'Foo Company',
		),
		array(
			'id' => 2,
			'user_id' => 2,
			'name' => 'Bar Company',
		),
		array(
			'id' => 3,
			'user_id' => 2,
			'name' => 'Example Company',
		),
	);
}
