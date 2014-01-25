<?php
/**
 * CommonDetailModelFixture
 *
 */
class CommonDetailModelFixture extends CakeTestFixture {

/**
 * {@inheritdoc}
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'foreign_key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'foreign_model' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'position' => array('type' => 'integer', 'null' => false, 'default' => 1000, 'length' => 4),
		'field' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 255),
		'value' => array('type' => 'text', 'null' => false, 'default' => NULL),
		'input' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 16),
		'data_type' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 16),
		'label' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 128),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'FOREIGN_MODELS' => array('column' => 'foreign_model'),
			'FOREIGN_RECORD' => array('column' => array('foreign_key', 'foreign_model'))
		),
	);

/**
 * {@inheritdoc}
 */
	public $records = array(
	);

	public $table = 'common_details';
}
