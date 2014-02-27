<?php
/**
 * CommonTestAuthorizableCompaniesUserFixture
 *
 */
class CommonTestAuthorizableCompaniesUserFixture extends CommonTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'company_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'indexes' => array(
			'PRIMARY' => array('column' => array('company_id', 'user_id'), 'unique' => 1),
		),
	);

/**
 * {@inheritdoc}
 */
	public $records = array(
		array(
			'company_id' => 1,
			'user_id' => 2,
		),
		array(
			'company_id' => 2,
			'user_id' => 1
		),
		array(
			'company_id' => 2,
			'user_id' => 2
		)
	);
}
