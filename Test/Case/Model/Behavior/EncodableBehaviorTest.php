<?php

App::uses('Model', 'Model');
App::uses('CommonTestCase', 'Common.TestSuite');

class TestModel extends Model {
	public $useTable = false;
	public $actsAs = array('Common.Encodable');
}

class TestUser extends Model {
	public $useTable = false;
	public $actsAs = array('Common.Encodable' => array(
		'fields' => array('address' => array('encoding' => true), 'data')
	));
}

class EncoderBehaviorTest extends CommonTestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Model = ClassRegistry::init('TestModel');
		$this->User = ClassRegistry::init('TestUser');
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Model, $this->User);
		ClassRegistry::flush();
	}

	public function testSetup() {
		$config = 'foo';
		$this->Model->Behaviors->Encodable->setup($this->Model, $config);
		$expected = array('foo' => array('encoding' => 'normal', 'trim' => false));
		$result = $this->Model->Behaviors->Encodable->settings[$this->Model->alias];
		$this->assertEqual($result, $expected);

		$config = array('foo');
		$this->Model->Behaviors->Encodable->setup($this->Model, $config);
		$expected = array('foo' => array('encoding' => 'normal', 'trim' => false));
		$result = $this->Model->Behaviors->Encodable->settings[$this->Model->alias];
		$this->assertEqual($result, $expected);

		$config = array('foo' => array('encoding' => 'json'), 'bar');
		$this->Model->Behaviors->Encodable->setup($this->Model, $config);
		$expected = array('foo' => array('encoding' => 'json', 'trim' => false), 'bar' => array('encoding' => 'normal', 'trim' => false));
		$result = $this->Model->Behaviors->Encodable->settings[$this->Model->alias];
		$this->assertEqual($result, $expected);

		$config = array('foo' => array('encoding' => 'json'), 'bar' => array('trim' => true));
		$this->Model->Behaviors->Encodable->setup($this->Model, $config);
		$expected = array('foo' => array('encoding' => 'json', 'trim' => false), 'bar' => array('encoding' => 'normal', 'trim' => true));
		$result = $this->Model->Behaviors->Encodable->settings[$this->Model->alias];
		$this->assertEqual($result, $expected);
	}

	public function testEncodeEmptyArray() {
		$a = array();

		// Output as array
		$expected = '[]';
		$result = $this->Model->encode($a);
		$this->assertEqual($result, $expected);

		// Output as object
		$expected = '{}';
		$result = $this->Model->encode($a, array('encoding' => JSON_FORCE_OBJECT));
		$this->assertEqual($result, $expected);
	}

	public function testEncodeNonAssociativeArray() {
		$a = array('<foo>',"'bar'",'"baz"','&blong&', "\xc3\xa9");
		$b = array('', 'hello ', 'world');

		// NORMAL

		$expected = '["<foo>","\'bar\'",""baz"","&blong&","é"]';
		$result = $this->Model->encode($a);
		$this->assertEqual($result, $expected);

		$expected = '["","hello ","world"]';
		$result = $this->Model->encode($b, array('encoding' => false));
		$this->assertEqual($result, $expected);

		$expected = '["hello","world"]';
		$result = $this->Model->encode($b, array('trim' => true));
		$this->assertEqual($result, $expected);

		// SERIALIZE

		$expected = 'a:5:{i:0;s:5:"<foo>";i:1;s:5:"\'bar\'";i:2;s:5:""baz"";i:3;s:7:"&blong&";i:4;s:2:"é";}';
		$result = $this->Model->encode($a, array('encoding' => 'serialize'));
		$this->assertEqual($result, $expected);

		// JSON

		// Normal
		$expected = '["<foo>","\'bar\'","\"baz\"","&blong&","\u00e9"]';
		$result = $this->Model->encode($a, array('encoding' => 'json'));
		$this->assertEqual($result, $expected);

		// Tags
		$expected = '["\u003Cfoo\u003E","\'bar\'","\"baz\"","&blong&","\u00e9"]';
		$result = $this->Model->encode($a, array('encoding' => JSON_HEX_TAG));
		$this->assertEqual($result, $expected);

		// Apos
		$expected = '["<foo>","\u0027bar\u0027","\"baz\"","&blong&","\u00e9"]';
		$result = $this->Model->encode($a, array('encoding' => JSON_HEX_APOS));
		$this->assertEqual($result, $expected);

		// Quot
		$expected = '["<foo>","\'bar\'","\u0022baz\u0022","&blong&","\u00e9"]';
		$result = $this->Model->encode($a, array('encoding' => JSON_HEX_QUOT));
		$this->assertEqual($result, $expected);

		// Amp
		$expected = '["<foo>","\'bar\'","\"baz\"","\u0026blong\u0026","\u00e9"]';
		$result = $this->Model->encode($a, array('encoding' => JSON_HEX_AMP));
		$this->assertEqual($result, $expected);

		// All
		$expected = '["\u003Cfoo\u003E","\u0027bar\u0027","\u0022baz\u0022","\u0026blong\u0026","\u00e9"]';
		$result = $this->Model->encode($a, array('encoding' => JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
		$this->assertEqual($result, $expected);

		if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
			// Unicode
			$expected = '["<foo>","\'bar\'","\"baz\"","&blong&","é"]';
			$result = $this->Model->encode($a, array('encoding' => JSON_UNESCAPED_UNICODE));
			$this->assertEqual($result, $expected);
		}

		$a = array(array(1,2,3));

		$expected = '[[1,2,3]]';
		$result = $this->Model->encode($a);
		$this->assertEqual($result, $expected);

		$expected = '{"0":{"0":1,"1":2,"2":3}}';
		$result = $this->Model->encode($a, array('encoding' => JSON_FORCE_OBJECT));
		$this->assertEqual($result, $expected);

	}

	public function testEncodeAssociativeArray() {
		$a = array('foo' => 'bar', 'baz' => 'long');
		$b = array('foo' => array('bar' => 1, 'baz' => 'long '));

		$expected = '{"foo":"bar","baz":"long"}';
		$result = $this->Model->encode($a, array('encoding' => true));
		$this->assertEqual($result, $expected);

		$expected = '{"foo":"bar","baz":"long"}';
		$result = $this->Model->encode($a, array('encoding' => JSON_FORCE_OBJECT));
		$this->assertEqual($result, $expected);

		$expected = '{"foo":{"bar":1,"baz":"long"}}';
		$result = $this->Model->encode($b, array('encoding' => 'json', 'trim' => true));
		$this->assertEqual($result, $expected);
	}

	public function testDecode() {
		$a = '{"foo":"bar","baz":"long"}';
		$expected = array('foo' => 'bar', 'baz' => 'long');
		$result = $this->Model->decode($a);
		$this->assertEqual($result, $expected);

		$a = '{"0":{"0":1,"1":2,"2":3}}';
		$expected = array(array(1,2,3));
		$result = $this->Model->decode($a);
		$this->assertEqual($result, $expected);

		$a = '["\u003Cfoo\u003E","\u0027bar\u0027","\u0022baz\u0022","\u0026blong\u0026","\u00e9"]';
		$expected = array('<foo>',"'bar'",'"baz"','&blong&', "\xc3\xa9");
		$result = $this->Model->decode($a);
		$this->assertEqual($result, $expected);

		$a = 'a:5:{i:0;s:5:"<foo>";i:1;s:5:"\'bar\'";i:2;s:5:""baz"";i:3;s:7:"&blong&";i:4;s:2:"é";}';
		$expected = array('<foo>',"'bar'",'"baz"','&blong&', "\xc3\xa9");
		$result = $this->Model->decode($a);
		$this->assertEqual($result, $expected);

		$a = 'foo';
		$expected = null;
		$result = $this->Model->decode($a);
		$this->assertEqual($result, $expected);

		$a = '';
		$expected = '';
		$result = $this->Model->decode($a);
		$this->assertEqual($result, $expected);
	}

	public function testBeforeSave() {
		$array = array('hello', 'world');
		$expected = '["hello","world"]';

		$data = array('TestUser' => array('data' => array('hello', 'world')));
		$this->User->create($data);
		$this->User->save();
		$result = $this->User->data['TestUser']['data'];
		$this->assertEqual($result, $expected);

		$array = array('first' => 'hello', 'second' => 'world',);
		$expected = '{"first":"hello","second":"world"}';

		$data = array('TestUser' => array('address' => $array));
		$this->User->create($data);
		$this->User->save();
		$result = $this->User->data['TestUser']['address'];
		$this->assertEqual($result, $expected);

		$this->User->data = array();
		$result = $this->User->Behaviors->Encodable->beforeSave($this->User);
		$this->assertTrue($result);

	}

	public function testAfterFind() {
		$a = array(array('TestUser' => array('data' => '["hello","world"]')));
		$b = array(array('TestUser' => array('address' => '{"foo":"bar","baz":"long"}')));
		$c = array(array('TestModel' => array('foo' => 'bar')));
		$d = array(array('TestUser' => array('address' => 'foo')));

		$expected = array(array('TestUser' => array('data' => array('hello', 'world'))));
		$result = $this->User->Behaviors->Encodable->afterFind($this->User, $a, true);
		$this->assertEqual($result, $expected);

		$expected = array(array('TestUser' => array('address' => array('foo' => 'bar', 'baz' => 'long'))));
		$result = $this->User->Behaviors->Encodable->afterFind($this->User, $b, true);
		$this->assertEqual($result, $expected);

		$expected = $c;
		$result = $this->User->Behaviors->Encodable->afterFind($this->User, $c, true);
		$this->assertEqual($result, $expected);

		$expected = $d;
		$result = $this->User->Behaviors->Encodable->afterFind($this->User, $d, true);
		$this->assertEqual($result, $expected);
	}

}
