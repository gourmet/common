<?php

App::uses('Model', 'Model');
App::uses('Controller', 'Controller');
App::uses('AuthComponent', 'Controller/Component');
App::uses('FormAuthenticate', 'Controller/Component/Auth');
App::uses('CommonTestCase', 'Common.TestSuite');


class CommonTestOpauthUser extends Model {

}

class TestUsersController extends Controller {

	public $components = array(
		'Auth' => array(
			'userModel' => 'CommonTestOpauthUser',
			'authenticate' => array(
				'Form' => array('fields' => array('username' => 'email'))
			),
			'allowedActions' => array('register')
		),
		'Common.Opauth' => array(
			'registerAction' => array(
				'controller' => 'TestUsers',
				'action' => 'register'
			)
		),
		'Session'
	);

	public $uses = array('CommonTestOpauthUser');

	public function render() {
		$this->request['return'] = false;
		return $this->response;
	}

	public function register() {
		ClassRegistry::init('CommonTestOpauthUser')->save($this->data);
	}

}

class OpauthComponentTest extends CommonTestCase {

	public $fixtures = array('plugin.common.common_test_opauth_user');

	public function setUp() {
		$this->_initialState = array(
			'Security' => Configure::read('Security'),
			'Opauth' => Configure::read('Opauth')
		);
		Configure::write('Security.salt', 'someSalt');
		Configure::write('Security.cipherSeed', 'someCipher');
		Configure::write('Opauth.Strategy.Google', array(
			'client_id' => 'googleId',
			'client_secret' => 'googleSecret',
		));

		$this->Controller = $this->getMock('TestUsersController', array('redirect', 'register', 'render'), array(new CakeRequest(null, false), new CakeResponse()));
		$this->Controller->constructClasses();
		$this->Controller->Components = $this->getMock('ComponentCollection', array('loaded'));
		$this->Controller->Auth = $this->getMock('AuthComponent', array('constructAuthenticate', 'login', 'user'), array($this->Controller->Components, $this->Controller->Auth->settings));
		$this->Controller->Session = $this->getMock('SessionComponent', array('setFlash', 'write'), array($this->Controller->Components));
		// $this->Controller->View = $this->getMock('View', array('render'), array($this->Controller));
	}

	public function tearDown() {
		parent::tearDown();
		Configure::write('Security', $this->_initialState['Security']);
		Configure::write('Opauth', $this->_initialState['Opauth']);
		unset($this->Controller);
	}

	public function testStartupNoOpauthRequestData() {
		$this->Controller->data = array();

		$this->Controller->Components->expects($this->once())
			->method('loaded')
			->with('Auth')
			->will($this->returnValue(true));

		$this->Controller->Auth->staticExpects($this->once())
			->method('user');

		$this->Controller->Session->expects($this->any())
			->method('check')
			->will($this->returnValue(false));

		$this->Controller->Opauth->startup($this->Controller);
	}

	public function testStartupInvalidOpauth() {
		$this->Controller->data = array(
			'error' => array(
				'code' => 'access_token_error',
				'message' => 'Failed when attempting to obtain access token'
			),
			'validated' => false
		);

		$this->Controller->Components->expects($this->once())
			->method('loaded')
			->with('Auth')
			->will($this->returnValue(true));

		$this->Controller->Auth->staticExpects($this->once())
			->method('user')
			->with()
			->will($this->returnValue(array()));

		$this->Controller->Session->expects($this->once())
			->method('setFlash');

		$this->Controller->expects($this->once())
			->method('redirect');

		$this->Controller->Opauth->startup($this->Controller);
	}

	public function testStartupNoAuthComponentLoaded() {
		$this->Controller->data = array(
			'auth' => array(
				'uid' => 'uniqueid',
				'info' => array(
					'name' => 'John Doe',
					'email' => 'john@doe.com',
					'first_name' => 'John',
					'last_name' => 'Doe',
					'image' => '/path/to/image',
				),
				'credentials' => array(
					'token' => 'sampletoken',
					'expires' => 'sampleexpiredate'
				),
				'raw' => array(
					'id' => 'uniqueid',
					'email' => 'john@doe.com',
					'verified_email' => 1,
					'name' => 'John Doe',
					'given_name' => 'John',
					'family_name' => 'Doe',
					'link' => '/some/link',
					'picture' => '/path/to/image',
					'gender' => 'male',
					'birthday' => 'yyyy-mm-dd',
					'locale' => 'en',
					'hd' => 'company.com'
				),
				'provider' => 'Google',
			),
			'timestamp' => '',
			'signature' => '',
			'validated' => true
		);

		$this->Controller->Components->expects($this->once())
			->method('loaded')
			->with('Auth')
			->will($this->returnValue(false));

		$this->Controller->Auth->staticExpects($this->never())
			->method('user');

		$this->Controller->Session->expects($this->never())
			->method('check');

		$this->Controller->Opauth->startup($this->Controller);
	}

	public function testStartupByRegisteredAndAuthenticatedUser() {
		$this->Controller->data = array(
			'auth' => array(
				'uid' => 'uniqueid',
				'info' => array(
					'name' => 'John Doe',
					'email' => 'john@doe.com',
					'first_name' => 'John',
					'last_name' => 'Doe',
					'image' => '/path/to/image',
				),
				'credentials' => array(
					'token' => 'sampletoken',
					'expires' => 'sampleexpiredate'
				),
				'raw' => array(
					'id' => 'uniqueid',
					'email' => 'john@doe.com',
					'verified_email' => 1,
					'name' => 'John Doe',
					'given_name' => 'John',
					'family_name' => 'Doe',
					'link' => '/some/link',
					'picture' => '/path/to/image',
					'gender' => 'male',
					'birthday' => 'yyyy-mm-dd',
					'locale' => 'en',
					'hd' => 'company.com'
				),
				'provider' => 'Google',
			),
			'timestamp' => '',
			'signature' => '',
			'validated' => true
		);

		$Model = $this->getMockForModel('CommonTestOpauthUser', array('save'));
		ClassRegistry::addObject('CommonTestOpauthUser', $Model);

		$this->Controller->Components->expects($this->exactly(2))
			->method('loaded')
			->with('Auth')
			->will($this->returnValue(true));

		$FormAuthenticateMock = $this->getMock('FormAuthenticate', array(), array($this->Controller->Components, $this->Controller->components['Auth']));
		$this->Controller->Auth->expects($this->once())
			->method('constructAuthenticate')
			->with()
			->will($this->returnValue(array($FormAuthenticateMock)));

		$this->Controller->Auth->staticExpects($this->at(0))
			->method('user')
			->with()
			->will($this->returnValue(array('id' => 1, 'email' => 'john@doe.com', 'google_id' => null)));

		$this->Controller->Session->expects($this->any())
			->method('check')
			->will($this->returnValue(false));

		$expectedData = array('CommonTestOpauthUser' => array(
			'google_credentials' => serialize($this->Controller->data['auth']['credentials']),
			'google_id' => 'uniqueid',
			'first_name' => 'John',
			'last_name' => 'Doe',
			'gender' => 'male',
			'birthday' => 'yyyy-mm-dd',
			'locale' => 'en',
			'company' => 'company.com',
			'gravatar' => '/path/to/image'
		));
		$Model->expects($this->once())
			->method('save')
			->with($expectedData)
			->will($this->returnValue($expectedData));

		$this->Controller->Auth->expects($this->once())
			->method('login');

		$this->Controller->Auth->staticExpects($this->at(1))
			->method('user')
			->with()
			->will($this->returnValue(array('id' => 1, 'email' => 'john@doe.com', 'google_id' => null)));

		$this->Controller->Auth->staticExpects($this->at(2))
			->method('user')
			->with('google_id')
			->will($this->returnValue('uniqueid'));

		$this->Controller->Opauth->initialize($this->Controller);
		$this->Controller->Opauth->startup($this->Controller);

	}

	public function testStartupByRegisteredAndUnauthenticatedUser() {
		$this->Controller->data = array(
			'auth' => array(
				'uid' => 'uniqueid',
				'info' => array(
					'name' => 'John Doe',
					'email' => 'john@doe.com',
					'first_name' => 'John',
					'last_name' => 'Doe',
					'image' => '/path/to/image',
				),
				'credentials' => array(
					'token' => 'sampletoken',
					'expires' => 'sampleexpiredate'
				),
				'raw' => array(
					'id' => 'uniqueid',
					'email' => 'john@doe.com',
					'verified_email' => 1,
					'name' => 'John Doe',
					'given_name' => 'John',
					'family_name' => 'Doe',
					'link' => '/some/link',
					'picture' => '/path/to/image',
					'gender' => 'male',
					'birthday' => 'yyyy-mm-dd',
					'locale' => 'en',
					'hd' => 'company.com'
				),
				'provider' => 'Google',
			),
			'timestamp' => '',
			'signature' => '',
			'validated' => true
		);

		ClassRegistry::addObject('CommonTestOpauthUser', $this->getMockForModel('CommonTestOpauthUser', array('find', 'save')));

		$this->Controller->Components->expects($this->exactly(2))
			->method('loaded')
			->with('Auth')
			->will($this->returnValue(true));

		$FormAuthenticateMock = $this->getMock('FormAuthenticate', array(), array($this->Controller->Components, $this->Controller->components['Auth']));
		$this->Controller->Auth->expects($this->once())
			->method('constructAuthenticate')
			->with()
			->will($this->returnValue(array($FormAuthenticateMock)));

		$this->Controller->Auth->staticExpects($this->at(0))
			->method('user')
			->with()
			->will($this->returnValue(null));

		$expectedQueryData = array(
			'conditions' => array('OR' => array(
				'CommonTestOpauthUser.google_id' => 'uniqueid',
				'CommonTestOpauthUser.email' => 'john@doe.com'
			)),
			'recursive' => 0,
			'contain' => null
		);

		ClassRegistry::init('CommonTestOpauthUser')->expects($this->once())
			->method('find')
			->with('first', $expectedQueryData)
			->will($this->returnValue(array('CommonTestOpauthUser' => array('id' => 2, 'email' => 'john@doe.com', 'password' => '123456'))));

		$expectedData = array('CommonTestOpauthUser' => array(
			'google_credentials' => serialize($this->Controller->data['auth']['credentials']),
			'google_id' => 'uniqueid',
			'first_name' => 'John',
			'last_name' => 'Doe',
			'gender' => 'male',
			'birthday' => 'yyyy-mm-dd',
			'locale' => 'en',
			'company' => 'company.com',
			'gravatar' => '/path/to/image'
		));
		ClassRegistry::init('CommonTestOpauthUser')->expects($this->once())
			->method('save')
			->with($expectedData)
			->will($this->returnValue($expectedData));

		$this->Controller->Auth->expects($this->once())
			->method('login');

		$this->Controller->Auth->staticExpects($this->at(1))
			->method('user')
			->with()
			->will($this->returnValue(array('id' => 1, 'email' => 'john@doe.com', 'google_id' => 'uniqueid')));

		$this->Controller->Auth->staticExpects($this->at(2))
			->method('user')
			->with('google_id')
			->will($this->returnValue('uniqueid'));

		$this->Controller->Opauth->initialize($this->Controller);
		$this->Controller->Opauth->startup($this->Controller);
	}

	public function testStartupByUnregisteredUser() {
		$this->Controller->data = array(
			'auth' => array(
				'uid' => 'uniqueid',
				'info' => array(
					'name' => 'John Doe',
					'email' => 'john@doe.com',
					'first_name' => 'John',
					'last_name' => 'Doe',
					'image' => '/path/to/image',
				),
				'credentials' => array(
					'token' => 'sampletoken',
					'expires' => 'sampleexpiredate'
				),
				'raw' => array(
					'id' => 'uniqueid',
					'email' => 'john@doe.com',
					'verified_email' => 1,
					'name' => 'John Doe',
					'given_name' => 'John',
					'family_name' => 'Doe',
					'link' => '/some/link',
					'picture' => '/path/to/image',
					'gender' => 'male',
					'birthday' => 'yyyy-mm-dd',
					'locale' => 'en',
					'hd' => 'company.com'
				),
				'provider' => 'Google',
			),
			'timestamp' => '',
			'signature' => '',
			'validated' => true
		);

		ClassRegistry::addObject('CommonTestOpauthUser', $this->getMockForModel('CommonTestOpauthUser', array('find', 'save')));

		$this->Controller->Components->expects($this->exactly(2))
			->method('loaded')
			->with('Auth')
			->will($this->returnValue(true));

		$FormAuthenticateMock = $this->getMock('FormAuthenticate', array(), array($this->Controller->Components, $this->Controller->components['Auth']));
		$this->Controller->Auth->expects($this->once())
			->method('constructAuthenticate')
			->with()
			->will($this->returnValue(array($FormAuthenticateMock)));

		$this->Controller->Auth->staticExpects($this->at(0))
			->method('user')
			->with()
			->will($this->returnValue(null));

		$expectedQueryData = array(
			'conditions' => array('OR' => array(
				'CommonTestOpauthUser.google_id' => 'uniqueid',
				'CommonTestOpauthUser.email' => 'john@doe.com'
			)),
			'recursive' => 0,
			'contain' => null
		);

		ClassRegistry::init('CommonTestOpauthUser')->expects($this->once())
			->method('find')
			->with('first', $expectedQueryData)
			->will($this->returnValue(null));

		$expectedData = array('CommonTestOpauthUser' => array(
			'google_credentials' => serialize($this->Controller->data['auth']['credentials']),
			'google_id' => 'uniqueid',
			'first_name' => 'John',
			'last_name' => 'Doe',
			'gender' => 'male',
			'birthday' => 'yyyy-mm-dd',
			'locale' => 'en',
			'company' => 'company.com',
			'gravatar' => '/path/to/image',
			'email' => 'john@doe.com'
		));
		ClassRegistry::init('CommonTestOpauthUser')->expects($this->once())
			->method('save')
			->with($expectedData)
			->will($this->returnValue($expectedData));

		$this->Controller->Opauth->initialize($this->Controller);
		$this->Controller->Opauth->startup($this->Controller);

		$expected = '/users/login';
		$result = $_SERVER['HTTP_REFERER'];
		$this->assertEqual($result, $expected);
	}

	public function testConfig() {
		$result = $this->Controller->Opauth->config('Google');
		$expected = array('client_id' => 'googleId', 'client_secret' => 'googleSecret');
		$this->assertEqual($result, $expected);

		$this->assertFalse($this->Controller->Opauth->config('Twitter'));
	}

	public function testEnabled() {
		$this->assertTrue($this->Controller->Opauth->enabled('Google'));
		$this->assertFalse($this->Controller->Opauth->enabled('Twitter'));

		$result = array_keys($this->Controller->Opauth->enabled());
		$expected = array('Google');
		$this->assertEqual($result, $expected);
	}

	public function testEnabledNone() {
		Configure::delete('Opauth.Strategy.Google');
		$this->assertFalse($this->Controller->Opauth->enabled());
	}

}
