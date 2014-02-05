<?php

App::uses('CommonTestCase', 'Common.TestSuite');
App::uses('CommonAppModel', 'Common.Model');

class TestCommonAppModel extends CommonAppModel {

	public $useTable = false;

}

class CommonAppModelTest extends CommonTestCase {

	public function setUp() {
		parent::setUp();
		$this->Model = ClassRegistry::init('TestCommonAppModel');
	}

	public function tearDown() {
		parent::tearDown();
		$this->Model->getEventManager()->flush();
		ClassRegistry::flush();
		unset($this->Model);
	}

	public function testConstructor() {
		$plugins = CakePlugin::loaded();
		$plugins = array_filter(CakePlugin::loaded(), function($plugin) {
			return 'Common' != $plugin && is_null(CakePlugin::unload($plugin));
		});

		$this->Model->getEventManager()->flush();
		ClassRegistry::flush();
		CakePlugin::load('TestExample');
		$Model = ClassRegistry::init('TestCommonAppModel');

		$result = $Model->actsAs;
		$expected = array('TestExample.TestExample' => null);
		$this->assertEqual($result, $expected);

		array_walk($plugins, function($plugin) { CakePlugin::load($plugin); });
	}

	public function testLog() {
		$stream = CakeLog::stream('error');
		$engine = get_class($stream);
		$config = array_merge($stream->config(), compact('engine'));

		CakeLog::config('error', array_merge($config, array('engine' => 'FileLog', 'path' => TMP . 'tests' . DS)));
		$filepath = TMP . 'tests' . DS . 'error.log';
		if (file_exists($filepath)) {
			unlink($filepath);
		}

		$this->assertTrue($this->Model->log('Test warning 1'));
		$this->assertTrue($this->Model->log(array('Test' => 'warning 2')));
		$result = file($filepath);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Test warning 1$/', $result[0]);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Array$/', $result[1]);
		$this->assertRegExp('/^\($/', $result[2]);
		$this->assertRegExp('/\[Test\] => warning 2$/', $result[3]);
		$this->assertRegExp('/^\)$/', $result[4]);
		unlink($filepath);

		$this->assertTrue($this->Model->log('Test warning 1', LOG_WARNING));
		$this->assertTrue($this->Model->log(array('Test' => 'warning 2'), LOG_WARNING));
		$result = file($filepath);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 1$/', $result[0]);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Array$/', $result[1]);
		$this->assertRegExp('/^\($/', $result[2]);
		$this->assertRegExp('/\[Test\] => warning 2$/', $result[3]);
		$this->assertRegExp('/^\)$/', $result[4]);
		unlink($filepath);

		CakeLog::config('error', array_merge($config, array('engine' => 'FileLog', 'path' => TMP . 'tests' . DS, 'scopes' => array('some_scope'))));
		$this->assertTrue($this->Model->log('Test warning 1', LOG_WARNING));
		$this->assertTrue(!file_exists($filepath));
		$this->assertTrue($this->Model->log('Test warning 1', LOG_WARNING, 'some_scope'));
		$result = file($filepath);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 1$/', $result[0]);

		CakeLog::config('error', $config);
	}

}
