<?php
/**
 * CommonTestAuthorizableDepartmentFixture
 *
 */
class CommonTestAuthorizableDepartmentFixture extends CommonTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'company_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
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
			'company_id' => 1,
			'name' => 'IT',
		),
		array(
			'id' => 2,
			'user_id' => 2,
			'company_id' => 2,
			'name' => 'IT',
		),
		array(
			'id' => 3,
			'user_id' => 2,
			'company_id' => 1,
			'name' => 'H&R',
		),
		array(
			'id' => 4,
			'user_id' => 2,
			'company_id' => 2,
			'name' => 'H&R',
		),
		array(
			'id' => 5,
			'user_id' => 1,
			'company_id' => 1,
			'name' => 'Accounting',
		),
		array(
			'id' => 6,
			'user_id' => 2,
			'company_id' => 2,
			'name' => 'Accounting',
		),
	);
}
