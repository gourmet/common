<?php

App::uses('ComponentCollection', 'Controller');
App::uses('CommonAppController', 'Common.Controller');
App::uses('CommonTestCase', 'Common.TestSuite');

class AppController extends CommonAppController {

	public $components = array('Email' => array('from' => 'no-reply@domain.com'), 'Session');

	public $helpers = array('Number');

}

class TestCommonAppController extends AppController {

	public $eventManager = null;

	public function getEventManager() {
		return $this->eventManager;
	}
}

class TestCommonController extends AppController {
	public $name = 'Test';
}

class CommonAppControllerTest extends CommonTestCase {

	public function setUp() {
		parent::setUp();

		if (!CakePlugin::loaded('Common')) CakePlugin::load('Common');

		$this->CakeRequest = $this->getMock('CakeRequest', array('is'));
		$this->Controller = $this->getMock(
			'TestCommonController',
			array('redirect', 'referer', 'set'),
			array($this->CakeRequest, new CakeResponse)
		);

		$this->Controller->Components = $this->getMock('ComponentCollection', array('init'));
		$this->Controller->eventManager = $this->getMock('CommonEventManager');
		$this->Controller->Session = $this->getMock('SessionComponent', array('setFlash'), array($this->Controller->Components));
		$this->Controller->constructClasses();
		$this->flashMessage = String::insert(
			$this->Controller->alertMessages['delete.success']['message'],
			array('modelName' => 'test'),
			array('clean' => true)
		);
	}

	public function tearDown() {
		parent::tearDown();
		CommonEventManager::flush();
		unset($this->Controller);
	}

	public function testConstructClasses() {
		$Controller = new TestCommonAppController(new CakeRequest, new CakeResponse);
		$Controller->Components = $this->getMock('ComponentCollection', array('init'));
		$Controller->eventManager = $this->getMock('CommonEventManager');

		$Controller->Components->expects($this->once())
			->method('init');
		$Controller->eventManager->expects($this->once())
			->method('dispatch')
			->with(new CakeEvent('Controller.constructClasses', $Controller, null));

		$Controller->constructClasses();
	}

	public function testConstructedClasses() {
		$plugins = array_filter(CakePlugin::loaded(), function($plugin) {
			return 'Common' != $plugin && is_null(CakePlugin::unload($plugin));
		});

		CommonEventManager::flush();
		CakePlugin::load('TestExample');
		$Controller = new TestCommonAppController(new CakeRequest, new CakeResponse);
		$Controller->Components = $this->getMock('ComponentCollection', array('init'));
		$Controller->constructClasses();

		$result = $Controller->components;
		$expected = array('Email' => array('from' => 'no-reply@domain.com'), 'Session' => null, 'TestExample.TestExample' => null);
		$this->assertEqual($result, $expected);

		$result = $Controller->helpers;
		$expected = array('Number' => null, 'TestExample.TestExample' => null);
		$this->assertEqual($result, $expected);

		array_walk($plugins, function($plugin) { CakePlugin::load($plugin); });
	}

	public function testAlert() {
		$this->Controller->Session->expects($this->once())
			->method('setFlash')
			->with(
				$this->flashMessage,
				'Common.alerts/default',
				array('level' => 'success', 'plugin' => 'Common', 'code' => 0, 'dismiss' => false)
			);

		$this->Controller->alert('delete.success');
	}

	public function testAlertWithException() {
		$this->Controller->Session->expects($this->once())
			->method('setFlash')
			->with(
				'Foo',
				'Common.alerts/default',
				array('level' => 'error', 'plugin' => 'Common', 'code' => 0, 'dismiss' => false)

			);

		$this->Controller->alert(new Exception('Foo'));
	}

	public function testAlertWithCustomMessageAndOptions() {
		$this->Controller->Session->expects($this->once())
			->method('setFlash')
			->with(
				'Foo bar',
				'Common.alerts/default',
				array('level' => 'warning', 'plugin' => 'Common', 'code' => 0, 'dismiss' => true, 'foo' => 'bar')

			);

		$this->Controller->alert('foo bar', array('level' => 'warning', 'dismiss' => true, 'foo' => 'bar'));
	}

	public function testAlertWithOverrideOfDefaultOptions() {
		$this->Controller->Session->expects($this->once())
			->method('setFlash')
			->with(
				$this->flashMessage,
				'Common.alerts/default',
				array('level' => 'warning', 'plugin' => 'Common', 'code' => 0, 'dismiss' => false)

			);

		$this->Controller->alert('delete.success', array('level' => 'warning'));
	}

	public function testAlertWithRedirect() {
		$this->Controller->Session->expects($this->once())
			->method('setFlash')
			->with(
				'Redirect me',
				'Common.alerts/default',
				array('level' => 'warning', 'plugin' => 'Common', 'code' => 0, 'dismiss' => false)
			);

		$this->Controller->expects($this->once())
			->method('redirect')
			->with('/');

		$this->Controller->alert('redirect me', array('level' => 'warning', 'redirect' => '/'));
	}

	public function testAlertWithRedirectToReferer() {
		$this->Controller->Session->expects($this->once())
			->method('setFlash')
			->with(
				'Redirect me',
				'Common.alerts/default',
				array('level' => 'warning', 'plugin' => 'Common', 'code' => 0, 'dismiss' => false)
			);

		$this->Controller->expects($this->once())
			->method('referer')
			->will($this->returnValue('/foo/bar'));

		$this->Controller->expects($this->once())
			->method('redirect')
			->with('/foo/bar');

		$this->Controller->alert('redirect me', array('level' => 'warning', 'redirect' => true));
	}

	public function testFlashWithAjax() {
		$this->Controller->request->expects($this->once())
			->method('is')
			->with('ajax')
			->will($this->returnValue(true));

		$this->Controller->expects($this->once())
			->method('set')
			->with(
				'json',
				array(
					'params' => array(
						'level' => 'success',
						'plugin' => 'Common',
						'code' => 0,
						'message' => $this->flashMessage
						),
					'redirect' => false
			));

		$this->Controller->Session->expects($this->never())
			->method('setFlash');

		$this->Controller->alert('delete.success');
	}

	public function testFlashWithPrefixFromPlugin() {
		$this->Controller->params['prefix'] = 'admin';

		$this->Controller->Session->expects($this->once())
			->method('setFlash')
			->with(
				$this->flashMessage,
				'TestExample.alerts/admin_default',
				array('level' => 'success', 'plugin' => 'TestExample', 'code' => 0, 'dismiss' => false)
			);

		$this->Controller->alert('delete.success', array('plugin' => 'TestExample'));
	}

	public function testFlashWithPrefixAndRedirectButMissingViewElement() {
		$this->Controller->params['prefix'] = 'member';

		$this->Controller->Session->expects($this->once())
			->method('setFlash')
			->with(
				$this->flashMessage,
				'alerts/default',
				array('level' => 'success', 'plugin' => 'TestExample', 'code' => 0, 'dismiss' => false)
			);

		$this->Controller->expects($this->once())
			->method('redirect')
			->with(array('prefix' => 'member', 'member' => true, 'controller' => 'test'));

		$this->Controller->alert('delete.success', array('plugin' => 'TestExample', 'redirect' => array('controller' => 'test')));
	}

	public function testFlashWithMissingViewElement() {
		$this->Controller->Session->expects($this->once())
			->method('setFlash')
			->with(
				$this->flashMessage,
				'alerts/default',
				array('level' => 'success', 'plugin' => 'Common', 'code' => 0, 'dismiss' => false)
			);

		$this->Controller->alert('delete.success', array('element' => 'alerts/foo'));
	}

	public function testFlashFromPredefinedStringNotArray() {
		$this->Controller->alertMessages['foo.bar'] = 'Foo Bar';

		$this->Controller->Session->expects($this->once())
			->method('setFlash')
			->with(
				'Foo Bar',
				'Common.alerts/default',
				array('level' => 'success', 'plugin' => 'Common', 'code' => 0, 'dismiss' => false)
			);

		$this->Controller->alert('foo.bar');
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

		$this->assertTrue($this->Controller->log('Test warning 1'));
		$this->assertTrue($this->Controller->log(array('Test' => 'warning 2')));
		$result = file($filepath);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Test warning 1$/', $result[0]);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Array$/', $result[1]);
		$this->assertRegExp('/^\($/', $result[2]);
		$this->assertRegExp('/\[Test\] => warning 2$/', $result[3]);
		$this->assertRegExp('/^\)$/', $result[4]);
		unlink($filepath);

		$this->assertTrue($this->Controller->log('Test warning 1', LOG_WARNING));
		$this->assertTrue($this->Controller->log(array('Test' => 'warning 2'), LOG_WARNING));
		$result = file($filepath);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 1$/', $result[0]);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Array$/', $result[1]);
		$this->assertRegExp('/^\($/', $result[2]);
		$this->assertRegExp('/\[Test\] => warning 2$/', $result[3]);
		$this->assertRegExp('/^\)$/', $result[4]);
		unlink($filepath);

		CakeLog::config('error', array_merge($config, array('engine' => 'FileLog', 'path' => TMP . 'tests' . DS, 'scopes' => array('some_scope'))));
		$this->assertTrue($this->Controller->log('Test warning 1', LOG_WARNING));
		$this->assertTrue(!file_exists($filepath));
		$this->assertTrue($this->Controller->log('Test warning 1', LOG_WARNING, 'some_scope'));
		$result = file($filepath);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 1$/', $result[0]);

		CakeLog::config('error', $config);
	}

}
