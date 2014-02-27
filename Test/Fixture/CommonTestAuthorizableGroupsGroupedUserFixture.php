<?php
/**
 * CommonTestAuthorizableGroupsGroupedUserFixture
 *
 */
class CommonTestAuthorizableGroupsGroupedUserFixture extends CommonTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'group_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'indexes' => array(
			'PRIMARY' => array('column' => array('group_id', 'user_id'), 'unique' => 1),
		),
	);

/**
 * {@inheritdoc}
 */
	public $records = array(
		array(
			'group_id' => 1,
			'user_id' => 1,
		),
		array(
			'group_id' => 2,
			'user_id' => 2
		)
	);
}
