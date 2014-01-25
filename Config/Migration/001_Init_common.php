<?php
/**
 * Common
 *
 * PHP 5
 *
 * Copyright 2013, Jad Bitar (http://jadb.io)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2013, Jad Bitar (http://jadb.io)
 * @link          http://github.com/gourmet/affiliates
 * @since         0.1.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class InitCommon extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = 'Init common tables';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(
			'create_table' => array(
				'common_comments' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'user_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36),
					'foreign_key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
					'foreign_model' => array('type' => 'string', 'null' => false, 'default' => NULL),
					'parent_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36),
					'lft' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 10),
					'rght' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 10),
					'status' => array('type' => 'string', 'null' => false, 'default' => 'active'),
					'body' => array('type' => 'text', 'null' => false, 'default' => NULL),
					'name' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 64),
					'email' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 64),
					'website' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 128),
					'comment_count' => array('type' => 'integer', 'null' => false, 'default' => 0),
					'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
					'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'FOREIGN_MODELS' => array('column' => 'foreign_model'),
						'FOREIGN_RECORD' => array('column' => array('foreign_key', 'foreign_model'), 'unique' => 1)
					),
				),
				'common_details' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'foreign_key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
					'foreign_model' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
					'position' => array('type' => 'integer', 'null' => false, 'default' => 1000, 'length' => 4),
					'field' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 255),
					'value' => array('type' => 'text', 'null' => true, 'default' => NULL),
					'input' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 16),
					'data_type' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 16),
					'label' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 128),
					'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
					'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'FOREIGN_MODELS' => array('column' => 'foreign_model'),
						'FOREIGN_RECORD' => array('column' => array('foreign_key', 'foreign_model'))
					),
				),
			)
		),
		'down' => array(
			'drop_table' => array(
				'common_comments',
				'common_details',
			)
		)
	);

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function after($direction) {
		return true;
	}

}
