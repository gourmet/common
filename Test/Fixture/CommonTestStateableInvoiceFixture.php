<?php
/**
 * CommonTestStateableInvoiceFixture
 *
 */
class CommonTestStateableInvoiceFixture extends CommonTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'status' => array('type' => 'string', 'null' => false, 'default' => 'active'),
		'is_paid' => array('type' => 'integer', 'null' => true, 'default' => NULL),
		'is_due' => array('type' => 'integer', 'null' => true, 'default' => NULL),
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
			'status' => 'active',
			'is_paid' => 1,
			'is_due' => 0,
		),
		array(
			'id' => 2,
			'status' => 'active',
			'is_paid' => 0,
			'is_due' => 1
		)
	);
}
