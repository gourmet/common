<?php

App::uses('CommonTestCase', 'Common.TestSuite');
App::uses('CommonAppHelper', 'Common.View/Helper');
App::uses('View', 'View');

class TestCommonAppHelper extends CommonAppHelper {
	public $helpers = array('Html');
}

class CommonAppHelperTest extends CommonTestCase {

	public function setUp() {
		parent::setUp();
		$this->Helper = new TestCommonAppHelper(new View());
		Router::reload();
	}

	public function tearDown() {
		parent::tearDown();
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

		$this->assertTrue($this->Helper->log('Test warning 1'));
		$this->assertTrue($this->Helper->log(array('Test' => 'warning 2')));
		$result = file($filepath);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Test warning 1$/', $result[0]);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Array$/', $result[1]);
		$this->assertRegExp('/^\($/', $result[2]);
		$this->assertRegExp('/\[Test\] => warning 2$/', $result[3]);
		$this->assertRegExp('/^\)$/', $result[4]);
		unlink($filepath);

		$this->assertTrue($this->Helper->log('Test warning 1', LOG_WARNING));
		$this->assertTrue($this->Helper->log(array('Test' => 'warning 2'), LOG_WARNING));
		$result = file($filepath);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 1$/', $result[0]);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Array$/', $result[1]);
		$this->assertRegExp('/^\($/', $result[2]);
		$this->assertRegExp('/\[Test\] => warning 2$/', $result[3]);
		$this->assertRegExp('/^\)$/', $result[4]);
		unlink($filepath);

		CakeLog::config('error', array_merge($config, array('engine' => 'FileLog', 'path' => TMP . 'tests' . DS, 'scopes' => array('some_scope'))));
		$this->assertTrue($this->Helper->log('Test warning 1', LOG_WARNING));
		$this->assertTrue(!file_exists($filepath));
		$this->assertTrue($this->Helper->log('Test warning 1', LOG_WARNING, 'some_scope'));
		$result = file($filepath);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 1$/', $result[0]);

		CakeLog::config('error', $config);
	}

	public function testUrl() {
		$reload = CakePlugin::loaded('I18n') && is_null(CakePlugin::unload('I18n'));

		$result = $this->Helper->url(array('controller' => 'posts', 'action' => 'edit', 1));
		$expected = '/posts/edit/1';
		$this->assertEqual($result, $expected);

		$result = $this->Helper->url('/posts/edit/3');
		$expected = '/posts/edit/3';
		$this->assertEqual($result, $expected);

		try {
			CakePlugin::load('I18n', array('routes' => true));
		} catch (MissingPluginException $e) {
			$this->markTestSkipped($e->getMessage());
		}

		Router::reload();

		$defaultLang = Configure::read('Config.language');
		$defaultLangs = Configure::read('Config.languages');

		Configure::write('Config.language', 'spa');
		Configure::write('Config.languages', array('eng', 'spa'));

		if (!defined('DEFAULT_LANGUAGE')) define('DEFAULT_LANGUAGE', 'eng');

		$result = $this->Helper->url(array('controller' => 'posts', 'action' => 'edit', 1));
		$expected = '/spa/posts/edit/1';
		$this->assertEqual($result, $expected);

		$result = $this->Helper->url('/posts/edit/3');
		$expected = '/spa/posts/edit/3';
		$this->assertEqual($result, $expected);

		$result = $this->Helper->url('#comments');
		$expected = '#comments';
		$this->assertEqual($result, $expected);

		$result = $this->Helper->url('/' . DEFAULT_LANGUAGE . '/posts/edit/3');
		$expected = '/posts/edit/3';
		$this->assertEqual($result, $expected);

		$result = $this->Helper->url('/posts/spa/3');
		$expected = '/spa/posts/spa/3';
		$this->assertEqual($result, $expected);

		Configure::write('Config.language', $defaultLang);
		Configure::write('Config.languages', $defaultLangs);

		if (!$reload) {
			CakePlugin::unload('I18n');
		}
	}
}
