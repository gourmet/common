<?php
/**
 * CommonTestAuthorizableDocsGroupFixture
 *
 */
class CommonTestAuthorizableDocsGroupFixture extends CommonTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'group_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'doc_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'indexes' => array(
			'PRIMARY' => array('column' => array('group_id', 'doc_id'), 'unique' => 1),
		),
	);

/**
 * {@inheritdoc}
 */
	public $records = array(
		array(
			'group_id' => 1,
			'doc_id' => 1,
		),
		array(
			'group_id' => 2,
			'doc_id' => 2
		)
	);
}
