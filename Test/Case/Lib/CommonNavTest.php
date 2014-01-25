<?php
App::uses('CommonNav', 'Common.Core');
App::uses('CommonTestCase', 'Common.TestSuite');

class CommonNavTest extends CommonTestCase {

	protected static $_menus = array();

	public function setUp() {
		parent::setUp();
		self::$_menus = CommonNav::items();
	}

	public function tearDown() {
		parent::tearDown();
		CommonNav::items(self::$_menus);
	}

	public function testNav() {
		$saved = CommonNav::items();

		// test clear
		CommonNav::clear();
		$items = CommonNav::items();
		$this->assertEqual($items, array());

		// test first level addition
		$defaults = CommonNav::getDefaults();
		$extensions = array('title' => 'Extensions');
		CommonNav::add('extensions', $extensions);
		$result = CommonNav::items();
		$expected = array('extensions' => Hash::merge($defaults, $extensions));
		$this->assertEqual($result, $expected);

		// tested nested insertion (1 level)
		$plugins = array('title' => 'Plugins');
		CommonNav::add('extensions.children.plugins', $plugins);
		$result = CommonNav::items();
		$expected['extensions']['children']['plugins'] = Hash::merge($defaults, $plugins);
		$this->assertEqual($result, $expected);

		// 2 levels deep
		$example = array('title' => 'Example');
		CommonNav::add('extensions.children.plugins.children.example', $example);
		$result = CommonNav::items();
		$expected['extensions']['children']['plugins']['children']['example'] = Hash::merge($defaults, $example);
		$this->assertEqual($result, $expected);
	}

	public function testNavMerge() {
		$foo = array('title' => 'foo', 'access' => array('public', 'admin'));
		$bar = array('title' => 'bar', 'access' => array('admin'));
		CommonNav::clear();
		CommonNav::add('foo', $foo);
		CommonNav::add('foo', $bar);
		$items = CommonNav::items();
		$expected = array('admin', 'public');
		sort($expected);
		sort($items['foo']['access']);
		$this->assertEquals($expected, $items['foo']['access']);
	}

	public function testNavOverwrite() {
		$defaults = CommonNav::getDefaults();

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
		CommonNav::add('users.children.permissions', $item);
		$items = CommonNav::items();

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
