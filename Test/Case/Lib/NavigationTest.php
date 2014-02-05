<?php
App::uses('Navigation', 'Common.Lib');
App::uses('CommonTestCase', 'Common.TestSuite');

class NavigationTest extends CommonTestCase {

	protected static $_menus = array();

	public function setUp() {
		parent::setUp();
		self::$_menus = Navigation::items();
	}

	public function tearDown() {
		parent::tearDown();
		Navigation::items(self::$_menus);
	}

	public function testNav() {
		$saved = Navigation::items();

		// test clear
		Navigation::clear();
		$items = Navigation::items();
		$this->assertEqual($items, array());

		// test first level addition
		$defaults = Navigation::getDefaults();
		$extensions = array('title' => 'Extensions');
		Navigation::add('extensions', $extensions);
		$result = Navigation::items();
		$expected = array('extensions' => Hash::merge($defaults, $extensions));
		$this->assertEqual($result, $expected);

		// tested nested insertion (1 level)
		$plugins = array('title' => 'Plugins');
		Navigation::add('extensions.children.plugins', $plugins);
		$result = Navigation::items();
		$expected['extensions']['children']['plugins'] = Hash::merge($defaults, $plugins);
		$this->assertEqual($result, $expected);

		// 2 levels deep
		$example = array('title' => 'Example');
		Navigation::add('extensions.children.plugins.children.example', $example);
		$result = Navigation::items();
		$expected['extensions']['children']['plugins']['children']['example'] = Hash::merge($defaults, $example);
		$this->assertEqual($result, $expected);
	}

	public function testNavOverwrite() {
		$defaults = Navigation::getDefaults();

		Hash::merge($defaults, array(
			'title' => 'Permissions',
			'url' => array(
				'admin' => true,
				'plugin' => 'acl',
				'controller' => 'acl_permissions',
				'action' => 'index',
				),
			'weight' => 30,
			));

		$item = array(
			'title' => 'Permissions',
			'url' => array(
				'admin' => true,
				'plugin' => 'acl_extras',
				'controller' => 'acl_extras_permissions',
				'action' => 'index',
				),
			'weight' => 30,
			);
		Navigation::add('users.children.permissions', $item);
		$items = Navigation::items();

		$expected = Hash::merge($defaults, array(
			'title' => 'Permissions',
			'url' => array(
				'admin' => true,
				'plugin' => 'acl_extras',
				'controller' => 'acl_extras_permissions',
				'action' => 'index',
				),
			'weight' => 30,
			));

		$this->assertEquals($expected, $items['users']['children']['permissions']);
	}

}
