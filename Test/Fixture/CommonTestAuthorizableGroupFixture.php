<?php
/**
 * CommonTestAuthorizableGroupFixture
 *
 */
class CommonTestAuthorizableGroupFixture extends CakeTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'name' => array('column' => 'name', 'unique' => 1)
		),
	);

/**
 * {@inheritdoc}
 */
	public $records = array(
		array(
			'id' => 1,
			'name' => 'admin'
		),
		array(
			'id' => 2,
			'name' => 'moderator'
		),
		array(
			'id' => 3,
			'name' => 'editor'
		)
	);
}
