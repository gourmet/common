<?php

App::uses('CommonTestCase', 'Common.TestSuite');
App::uses('Reveal', 'Common.Lib');
App::uses('Router', 'Routing');
App::uses('File', 'Utility');

class RevealTest extends CommonTestCase {

	public function setUp() {
		if (empty($this->initialState)) {
			$this->initialState = Reveal::$rules;
		}
	}

	public function tearDown() {
		Reveal::reload();
		unset($this->initialState);
	}

	public function testAddRule() {
		$initialState = Reveal::$rules;

		Reveal::addRule('Foo.bar', array('Foo', 'bar'), 'foo', 'bar');
		$this->assertTrue(isset(Reveal::$rules['Foo']['bar']));
		$this->assertEqual(Reveal::$rules['Foo']['bar'], array(array('Foo', 'bar'), 'foo', 'bar'));

		Reveal::addRule('Foo.bar', array('Foo', 'bar'));;
		$this->assertTrue(isset(Reveal::$rules['Foo']['bar']));
		$this->assertEqual(Reveal::$rules['Foo']['bar'], array(array('Foo', 'bar'), array(), true));

		$this->assertFalse(Reveal::$rules == $initialState);
		Reveal::reload();
		$this->assertEqual(Reveal::$rules, $initialState);
	}

	public function testIs() {
		$result = Reveal::is('App.online');
		$expected = !in_array(gethostbyname('google.com'), array('google.com', false));
		$this->assertEqual($result, $expected);

		$result = Reveal::is('DebugKit.loaded');
		$expected = CakePlugin::loaded('DebugKit');
		$this->assertEqual($result, $expected);

		$result = Reveal::is(array('OR' => array('DebugKit.enabled', 'DebugKit.automated')));
		$expected = Configure::read('debug') || Configure::read('DebugKit.forceEnable') || Configure::read('DebugKit.autoRun');
		$this->assertEqual($result, $expected);

		$_GET['debug'] = 'true';

		$this->assertTrue(Reveal::is('DebugKit.requested'));

		$result = Reveal::is('DebugKit.loaded', array('OR' => array('DebugKit.enabled', array('AND' => array('DebugKit.automated', 'DebugKit.requested')))));
		$expected = CakePlugin::loaded('DebugKit')
		|| Configure::read('debug')
		|| Configure::read('DebugKit.forceEnable')
		|| (Configure::read('DebugKit.autoRun') && isset($_GET['debug']) && 'true' == $_GET['debug']);
		$this->assertEqual($result, $expected);
		$this->assertEqual(Reveal::is('DebugKit.running'), $expected);

		$request = new CakeRequest();
		Router::setRequestInfo($request->addParams(array('controller' => 'pages', 'action' => 'display', 'pass' => array('home'))));
		$result = Reveal::is('Page.front');
		$this->assertTrue($result);

		Router::reload();

		$request = new CakeRequest();
		Router::setRequestInfo($request->addParams(array('prefix' => 'admin', 'admin' => true)));
		$result = Reveal::is('Page.admin');
		$this->assertTrue($result);

		Router::reload();

		$request = new CakeRequest();
		Router::setRequestInfo($request->addParams(array('controller' => 'users', 'action' => 'login')));
		$result = Reveal::is('Page.login');
		$this->assertTrue($result);

		$this->assertTrue(Reveal::is('Page.test'));
	}

	public function testIsBadConjunction() {
		$this->expectException('CommonRevealException', "A conjunction must include 2 or more paths.");
		Reveal::is(array('OR' => 'Foo.bar'));
	}

	public function testIsUndefinedCallback() {
		Reveal::addRule('Foo.bar', array('Foo', 'bar'));
		$this->expectException('CommonRevealException', "Callback 'Foo::bar()' is not defined.");
		Reveal::is('Foo.bar');
	}

	public function testIsUndefinedRule() {
		$this->expectException('CommonRevealException', "Rule 'Foo.bar' is not defined.");
		Reveal::is('Foo.bar');
	}

	public function testPath() {
		$result = Reveal::path();
		$expected = CakePlugin::path('Common') . 'Lib' . DS . 'Reveal.php';
		$this->assertEqual($result, $expected);

		$result = Reveal::path(0);
		$expected = __FILE__;
		$this->assertEqual($result, $expected);
	}

	public function testPlugin() {
		$result = Reveal::plugin();
		$expected = 'Common';
		$this->assertEqual($result, $expected);
	}

	public function testVersion() {
		$File = new File(CakePlugin::path('Common') . 'VERSION');
		$result = Reveal::version();
		$expected = trim($File->read());
		$this->assertEqual($result, $expected);

		try {
			$File = new File(CakePlugin::path('Affiliates') . 'VERSION');
			$result = Reveal::version();
			$expected = trim($File->read());
			$this->assertEqual($result, $expected);
		} catch (Exception $e) {}
	}

}
