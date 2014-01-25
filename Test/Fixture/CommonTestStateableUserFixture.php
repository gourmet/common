<?php
/**
 * CommonTestStateableUserFixture
 *
 */
class CommonTestStateableUserFixture extends CakeTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'status' => array('type' => 'string', 'null' => true, 'default' => 'active'),
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
			'status' => 'active'
		),
		array(
			'id' => 2,
			'status' => 'suspended'
		)
	);
}
