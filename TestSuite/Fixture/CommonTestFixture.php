<?php
/**
 * CommonTestCase
 *
 * PHP 5
 *
 * Copyright 2013, Jad Bitar (http://jadb.io)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2013, Jad Bitar (http://jadb.io)
 * @link          http://github.com/gourmet/common
 * @since         0.1.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakeTestFixture', 'TestSuite/Fixture');

/**
 * Common test fixture
 *
 *
 * @package       Common.TestSuite.Fixture
 */
class CommonTestFixture extends CakeTestFixture {

/**
 * Default field values to use when not defined in the records.
 *
 * @var array
 */
	public $defaults = array();

/**
 * {@inheritdoc}
 */
	public function init() {
		parent::init();

		$autoFill = isset($this->fields['created']) || isset($this->fields['modified']) || isset($this->fields['updated']);

		if (!empty($this->model)) {
			$Model = ClassRegistry::init($this->model);
			$stateable = $Model->Behaviors->attached('Stateable');
			var_dump($Model->alias);
		}

		if (empty($this->defaults) && !$autoFill) {
			return;
		}

		foreach ((array) $this->records as $k => $record) {
			$this->records[$k] += $this->defaults;
			foreach (array('created', 'modified', 'updated') as $field) {
				if (isset($this->fields[$field])) {
					$this->records[$k] += array($field => date('Y-m-d H:i:s'));
				}
			}
		}
	}

}
