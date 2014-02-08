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

App::uses('CakeTestCase', 'TestSuite');
App::uses('CommonTestFixture', 'Common.TestSuite/Fixture');
App::uses('CakeEmail', 'Network/Email');
App::uses('Validation', 'Utility');

/**
 * Common test case
 *
 *
 * @package       Common.TestSuite
 */
abstract class CommonTestCase extends CakeTestCase {

/**
 * Application's requirements.
 *
 * @var array
 */
	public $appRequirements = array();

/**
 * Plugin's dependencies.
 *
 * @var array
 */
	public $pluginDependencies = array();

/**
 * Fixtures.
 *
 * @var array
 */
	public $fixtures = array();

/**
 * Current plugin's name.
 *
 * @var string
 */
	public static $plugin = null;

/**
 * Stop all tests.
 *
 * @var boolean
 */
	public static $testsStopped = false;

/**
 * Store pre-tests application's paths to reset after tests.
 *
 * @var array
 */
	protected $_paths = array();

/**
 * Tests that require an active internet connection to run.
 * If this attribute is not empty, every test in the list will be skipped if there
 * is no internet connection detected.
 *
 * @var array
 */
	protected $_testsRequireConnection = array();

/**
 * Test to run for the test case (e.g array('testFind', 'testView')).
 * If this attribute is not empty only the tests from the list will be executed.
 *
 * @var array
 */
	protected $_testsToRun = array();

/**
 * Holds instance of test case. Useful to use in static methods like
 * 'tearDownAfterClass'. Only available after `setUp` has been run.
 *
 * @var CommonTestCase
 */
	protected static $_this = null;

	private static $__registry = array();

/**
 * Constructor
 *
 * If a class is extending AppTestCase it will merge these with the extending classes
 * so that you don't have to put the plugin fixtures into the AppTestCase
 *
 * @return void
 */
	public function __construct() {
		parent::__construct();

		$this->checkAppRequirements();

		if (!empty($this->fixtures) || false === $this->fixtures) {
			return;
		}

		$fixtures = $this->loadConfig('fixtures');
		if (!empty($fixtures)) {
			$this->fixtures = array_unique(array_merge($this->fixtures, $fixtures));
		}

		if (is_subclass_of($this, __CLASS__)) {
			$parentClass = get_parent_class($this);
			$parentVars = get_class_vars($parentClass);
			if (isset($parentVars['fixtures'])) {
				$this->fixtures = array_unique(array_merge($parentVars['fixtures'], $this->fixtures));
			}
			if (!empty($this->plugin)) {
				$this->pluginDependencies = $this->solveDependencies($this->plugin);
			}
			if (!empty($this->pluginDependencies)) {
				foreach ($this->pluginDependencies as $plugin) {
					$fixtures = $this->loadConfig('fixtures', $plugin);
					if (!empty($fixtures)) {
						$this->fixtures = array_unique(array_merge($this->fixtures, $fixtures));
					}
				}
			}
		}
	}

/**
 *
 *
 * @param mixed $model
 * @param string $assocModel
 * @param string $assocType
 * @return void
 */
	public function assertAssociatedTo($model, $assocModel, $assocType = null) {
		$model = $this->_getModel($model);
		$assocs = $model->getAssociated();
		if (!isset($assocs[$assocModel])) {
			$this->stopTests("Stopped because the '{$assocModel}' model is not properly associated to `{$model->name}`.");
			parent::fail("Failed asserting that `{$model->name}` is associated to `{$assocModel}` by any association type.");
		}

		$this->addToAssertionCount(1);

		if (!empty($assocType) && $assocs[$assocModel] != $assocType) {
			$this->stopTests("Stopped because the '{$assocModel}' model is not properly associated to `{$model->name}`.");
			parent::fail("Failed asserting that the `{$model->name}` -> `{$assocModel}` association is of '{$assocType}' type.");
		}

		$this->addToAssertionCount(1);
	}

/**
 *
 * @param mixed $model
 * @param string $behavior
 * @param array $options
 * @return void
 */
	public function assertBehaviorAttached($model, $behavior, $options = array()) {
		$model = $this->_getModel($model);

		if (!$model->Behaviors->loaded($behavior)) {
			$this->stopTests("Stopped because the '{$behavior}' behavior is not properly attached to `{$model->name}`.");
			parent::fail("Failed asserting that the '{$behavior}' behavior is attached to `{$model->name}`.");
		}

		$this->addToAssertionCount(1);

		if (empty($options)) {
			return;
		}

		$settings = $model->Behaviors->{$behavior}->settings[$model->alias];
		foreach ((array) $options as $k => $v) {
			if (!isset($settings[$k]) || $settings[$k] != $v) {
				$this->stopTests("Stopped because the '{$behavior}' behavior is not properly attached to `{$model->name}`.");
				parent::fail("Failed asserting that the {$behavior} behavior option '{$k}' on `{$model->name}` is correctly set.");
			}
			$this->addToAssertionCount(1);
		}
	}

/**
 *
 * @param mixed $model
 * @param string $method
 * @return void
 */
	public function assertCustomFindMethodDefined($model, $method) {
		$model = $this->_getModel($model);
		if (!method_exists($model, "_find{$method}")) {
			$this->stopTests("Stopped because the '{$method}' custom find method is not properly defined.");
			parent::fail(
				"Failed asserting that the custom '{$method}' find method is defined.\n" .
				"Fix by creating the `{$model->name}::_find{$method}()` method."
			);
		}
		$this->addToAssertionCount(1);

		if (!isset($model->findMethods[$method])) {
			$this->stopTests("Stopped because the '{$method}' custom find method is not properly defined.");
			parent::fail(
				"Failed asserting that the custom '{$method}' find method is properly setup.\n" .
				"Fix by adding the '{$method}' method to the `{$model->name}::\$findMethods` array."
			);
		}
		$this->addToAssertionCount(1);

		if (true !== $model->findMethods[$method]) {
			$this->stopTests("Stopped because the '{$method}' custom find method is not properly defined.");
			parent::fail(
				"Failed asserting that the custom '{$method}' find method is enabled.\n" .
				"Fix by setting the '{$method}' key in `{$model->name}::\$findMethods` to TRUE"
			);
		}
		$this->addToAssertionCount(1);
	}

/**
 *
 *
 * @param mixed $model
 * @param string|array $listener
 * @return void
 */
	public function assertEventListenerAttached($model, $listener) {
		$model = $this->_getModel($model);
		if (!in_array($listener, $model->eventListeners)) {
			$listenerName = 'given';
			if (isset($listener['type']) && isset($listener['className'])) {
				$listenerName = "'{$listener['type']}.{$listener['className']}'";
			}
			$this->stopTests("Stopped because the '{$listenerName}' listener model is not properly attached to `{$model->name}`.");
			parent::fail("Failed asserting that the '{$listenerName}' listener is attached to `{$model->name}`.");
		}
		$this->addToAssertionCount(1);
	}

/**
 *
 *
 * @param integer|string $check
 * @param string $type
 * @return void
 */
	public function assertId($check, $type = 'uuid') {
		switch ($type) {
			case 'numeric':
				$check = Validation::numeric($check);
				break;
			default:
				$check = Validation::uuid($check);
				$type = strtoupper($type);
		}

		if (!$check) {
			parent::fail("Failed asserting that '{$check}' is a valid {$type} primary key.");
		}

		$this->addToAssertionCount(1);
	}

/**
 * Asserts that data are invalid given Model validation rules
 * Calls the Model::validate() method and asserts the result
 *
 * @param mixed $model Model being tested
 * @param array $data Data to validate
 * @return void
 */
	public function assertInvalid($model, $data) {
		if ($this->_validData($model, $data)) {
			parent::fail("Failed asserting that the given data raises validation errors.");
		}
		$this->addToAssertionCount(1);
	}

/**
 * Aseert that a record was correctly created.
 *
 * @param mixed $model
 * @param array $query
 * @param array $asserts
 * @return void
 */
	public function assertRecordCreated($model, $query = array(), $asserts = array()) {
		$model = $this->_getModel($model);

		$modelName = $model->name;

		$model->ownerId = false;
		$result = $model->find('first', $query);
		$model->ownerId = true;

		if (empty($result)) {
			parent::fail("Failed asserting that the expected {$modelName} was created.");
		}

		$this->addToAssertionCount(1);

		foreach ($asserts as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $i => $j) {
					if ($result[$k][$i] != $j) {
						parent::fail(
							"Failed asserting that the created {$modelName}'s record matches expectation.\n" .
							"Expected: {$j} | Actual: {$result[$k][$i]}"
						);
					}
					$this->addToAssertionCount(1);
				}
				continue;
			}
			if ($result[$k] != $v) {
				parent::fail(
					"Failed asserting that the created {$modelName}'s record matches expectation.\n" .
					"Expected: {$v} | Actual: {$result[$k]}"
				);
			}
			$this->addToAssertionCount(1);
		}
	}

/**
 * Asserts that data are valid given Model validation rules
 * Calls the Model::validate() method and asserts the result
 *
 * @param mixed $model Model being tested
 * @param array $data Data to validate
 * @return void
 */
	public function assertValid($model, $data) {
		if (!$this->_validData($model, $data)) {
			parent::fail("Failed asserting that the given data passes validation.");
		}
		$this->addToAssertionCount(1);
	}

/**
 * Asserts that data are validation errors match an expected value when
 * validation given data for the Model
 * Calls the Model::validate() method and asserts validationErrors
 *
 * @param mixed $model Model being tested
 * @param array $data Data to validate
 * @param array $expectedErrors Expected errors keys
 * @return void
 */
	public function assertValidationErrors($model, $data, $expectedErrors) {
		$this->_validData($model, $data, $validationErrors);

		$fail = array();
		foreach ($validationErrors as $field => $errors) {
			if (!isset($expectedErrors[$field]) || $errors != $expectedErrors[$field]) {
				$fail[] = $field;
			}
		}

		$miss = array_diff(array_keys($expectedErrors), array_keys($validationErrors));

		$this->addToAssertionCount(count($expectedErrors) - count($fail) - count($miss));

		if (!empty($fail) || !empty($miss)) {
			parent::fail(
				"Failed asserting that the correct validation errors were raised. " .
				"The following field(s) expected errors did not match:\n* " .
				implode("\n* ", array_merge($fail, $miss))
			);
		}
	}

/**
 *
 */
	public function checkAppRequirements() {
		$this->appRequirements = array_merge(
			array('PHP' => null, 'OS' => null, 'functions' => array(), 'extensions' => array()),
			$this->appRequirements
		);

		$missingRequirements = array();

		if ($this->appRequirements['PHP'] &&
				version_compare(PHP_VERSION, $this->appRequirements['PHP'], '<')) {
				$missingRequirements[] = sprintf(
					'PHP %s (or later) is required.',
					$this->appRequirements['PHP']
				);
		}

		if ($this->appRequirements['OS'] &&
				!preg_match($this->appRequirements['OS'], PHP_OS)) {
				$missingRequirements[] = sprintf(
					'Operating system matching %s is required.',
					$this->appRequirements['OS']
				);
		}

		foreach ($this->appRequirements['functions'] as $requiredFunction) {
				if (!function_exists($requiredFunction)) {
						$missingRequirements[] = sprintf(
							'Function %s is required.',
							$requiredFunction
						);
				}
		}

		foreach ($this->appRequirements['extensions'] as $requiredExtension) {
				if (!extension_loaded($requiredExtension)) {
						$missingRequirements[] = sprintf(
							'Extension %s is required.',
							$requiredExtension
						);
				}
		}

		if ($missingRequirements) {
				$this->stopTests(
					implode(
						PHP_EOL,
						$missingRequirements
					)
				);
		}
	}

/**
 *
 *
 * @param string $class
 * @param array $asserts
 * @param string $mockMethod
 * @return void
 */
	public function expectAbstract($class, $asserts, $mockMethod = 'getMock', $args = array()) {
		$object = $this->{$mockMethod}($class, array_keys($asserts), $args);

		foreach ((array) $asserts as $method => $assert) {
			if (!is_array($assert)) {
				$assert = array('with' => $assert);
			}
			if (!isset($assert['expects'])) {
				$assert['expects'] = $this->once();
			}
			$expectation = $object->expects($assert['expects'])->method($method);
			unset($assert['expects']);
			foreach ($assert as $key => $val) {
				if (is_string($val) && $v = @unserialize($val)) {
					switch (count($v)) {
						case 6:
							$expectation = $expectation->{$key}($v[0], $v[1], $v[2], $v[3], $v[4], $v[5]);
							break;
						case 5:
							$expectation = $expectation->{$key}($v[0], $v[1], $v[2], $v[3], $v[4]);
							break;
						case 4:
							$expectation = $expectation->{$key}($v[0], $v[1], $v[2], $v[3]);
							break;
						case 3:
							$expectation = $expectation->{$key}($v[0], $v[1], $v[2]);
							break;
						case 2:
							$expectation = $expectation->{$key}($v[0], $v[1]);
							break;
						case 1:
							$expectation = $expectation->{$key}($v[0]);
							break;
						default:
							$expectation = $expectation->{$key}();
					}
				} else {
					$expectation = $expectation->{$key}($val);
				}
			}
			$expectation->will($this->returnValue($object));
		}

		return $object;
	}

/**
 *
 *
 * @param array $asserts
 * @return void
 */
	public function expectEmail($config = null, $asserts = array()) {
		if (is_array($config)) {
			$asserts = $config;
			$config = null;
		}
		list($plugin, $className) = pluginSplit(Common::read('Email.classname', 'CakeEmail'), true);
		App::uses($className, $plugin . 'Network/Email');
		return $this->expectAbstract($className, $asserts, 'getMockForEmail', $config);
	}

/**
 *
 *
 * @param string $model
 * @param array $asserts
 * @return void
 */
	public function expectModel($model, $asserts) {
		return $this->expectAbstract($model, $asserts, 'getMockForModel');
	}

/**
 * Gets the mock of an email object.
 *
 * @param string $email [description]
 * @param array $asserts [description]
 * @param [type] $config [description]
 * @return CakeEmail
 */
	public function getMockForEmail($className = null, $methods = array(), $config = null) {
		if (is_array($className)) {
			$methods = $className;
			$className = null;
		}

		if (empty($className)) {
			list($plugin, $className) = pluginSplit(Common::read('Email.classname', 'CakeEmail'), true);
			App::uses($className, $plugin . 'Network/Email');
		} else {
			App::uses($className, 'Network/Email');
		}

		if (empty($methods)) {
			$methods = array('send');
		}

		if (empty($config)) {
			$config = Common::read('Email.config', 'default');
		}

		if (empty($key)) {
			$key = ucfirst(Inflector::camelize($config)) . 'Email';
		}

		if (ClassRegistry::isKeySet($key)) {
			ClassRegistry::removeObject($key);
		}

		ClassRegistry::addObject($key, $this->getMock($className, $methods, array($config)));
		return ClassRegistry::getObject($key);
	}

/**
 * Gets the mock of an log object.
 *
 * @param string $classname [description]
 * @param array $asserts [description]
 * @return CakeLog
 */
	public function getMockForLog($className = null, $methods = array()) {
		if (empty($className)) {
			$className = Common::read('Log.classname', 'CakeLog');
		}

		if (empty($methods)) {
			$methods = array('write');
		}

		if (ClassRegistry::isKeySet('CommonLog')) {
			ClassRegistry::removeObject('CommonLog');
		}

		list($plugin, $className) = pluginSplit($className, $dotAppend = true);
		App::uses($className, $plugin . 'Log');

		ClassRegistry::addObject('CommonLog', $this->getMock($className, $methods));
		return ClassRegistry::getObject('CommonLog');
	}

/**
 * {@inheritdoc}
 */
	public function getMockForModel($model, $methods = array(), $config = null, $registry = false) {
		$modelOriginal = ClassRegistry::init($model);
		$modelMock = parent::getMockForModel($model, $methods, $config);

		// Fix bug when not merging behaviors and findMethods recursively for mocks.
		// @see Model::__construct() line 717-724
		if (get_parent_class($modelOriginal) !== 'AppModel') {
			$modelMock->findMethods = $modelOriginal->findMethods;
			$modelMock->Behaviors->init($modelMock->alias, $modelOriginal->actsAs);
		}

		if ($registry) {
			if (true === $registry) {
				list(, $registry) = pluginSplit($model);
			}

			if (ClassRegistry::isKeySet($registry)) {
				ClassRegistry::removeObject($registry);
			}

			ClassRegistry::addObject($registry, $modelMock);
		}

		return $modelMock;
	}

/**
 * Workaround to make testing of protected/private methods easier.
 *
 * @param object $Object Instantiated object that we will run method on.
 * @param string $methodName Protected or private method to call.
 * @param array $parameters Array of parameters to pass.
 * @return mixed Method result.
 * @see https://jtreminio.com/2013/03/unit-testing-tutorial-part-3-testing-protected-private-methods-coverage-reports-and-crap/
 */
	public function invokeMethod(&$Object, $methodName, array $parameters = array()) {
		$Reflection = new \ReflectionClass(get_class($Object));
		$method = $Reflection->getMethod($methodName);
		$method->setAccessible(true);

		return $method->invokeArgs($Object, $parameters);
	}


/**
 * Loads a file from app/tests/config/configure_file.php or app/plugins/PLUGIN/tests/config/configure_file.php
 *
 * Config file variables should be formated like:
 *  $config['name'] = 'value';
 * These will be used to create dynamic Configure vars.
 *
 *
 * @param string $fileName name of file to load, extension must be .php and only the name
 *     should be used, not the extenstion.
 * @param string $type Type of config file being loaded. If equal to 'app' core config files will be use.
 *    if $type == 'pluginName' that plugins tests/config files will be loaded.
 * @return mixed false if file not found, void if load successful
 */
	public function loadConfig($fileName, $type = 'app') {
		$found = false;
		if ($type == 'app') {
			$folder = APP . 'Test' . DS . 'Config' . DS;
		} else {
			$folder = App::pluginPath($type);
				if (!empty($folder)) {
				$folder .= 'Test' . DS . 'Config' . DS;
			} else {
				return false;
			}
		}
		if (file_exists($folder . $fileName . '.php')) {
			include($folder . $fileName . '.php');
			$found = true;
		}

		if (!$found) {
			return false;
		}

		if (!isset($config)) {
			$error = __("CommonTestCase::loadConfig() - no variable \$config found in %s.php", true);
			trigger_error(sprintf($error, $fileName), E_USER_WARNING);
			return false;
		}
		return $config;
	}

/**
 * {@inheritdoc}
 */
	public static function markTestIncomplete($msg = 'Needs to be completed') {
		parent::markTestIncomplete($msg);
	}

/**
 * {@inheritdoc}
 */
	public static function markTestSkipped($msg = 'Intentionaly skipped') {
		parent::markTestSkipped($msg);
	}

/**
 * Mock a user login. This requires that the `AppModel` extends `TDDAppModel`.
 *
 * @param integer|string $id
 * @return void
 */
	public function mockUserLogin($id) {
		$User = ClassRegistry::init('User');
		if ($User->Behaviors->loaded('Authorizable')) {
			$User->skipAuthorizable(1);
		}

		$user = current($User->find('first', array(
			'conditions' => compact('id'),
			'recursive' => -1
		)));

		$User->setCurrentUser($user);

		$RevealMock = Reveal::getMock($this, array(), array(array('User.loggedin'), array('User.visitor')));
		$RevealMock->staticExpects($this->any())
			->method('is')
			->will($this->returnValueMap(array(array('User.loggedin', true), array('User.visitor', false))));

		if (isset($this->Auth)) {
			$map = array(array(null, $user));
			foreach (array_keys($user) as $k) {
				$map[] = array($k, $user[$k]);
			}

			$this->Auth->staticExpects($this->any())
				->method('user')
				->will($this->returnValueMap($map));
		}
	}

/**
 * {@inheritdoc}
 */
	public function setUp() {
		parent::setUp();
		$this->_paths = App::paths();

		// Test case static reference.
		self::$_this = $this;

		if ($plugin = Reveal::plugin(Reveal::path(true))) {
			self::$plugin = $plugin;
		}

		self::buildTestAppPaths();
		self::loadTestAppPlugins();
	}

/**
 * {@inheritdoc}
 */
	public static function setUpBeforeClass() {
		self::_restoreSettings();
		Configure::write('Config.language', 'eng');
	}

/**
 * Solves Plugin Fixture dependancies.  Called in AppTestCase::__construct to solve
 * fixture dependancies.  Uses a Plugins tests/config/dependent and tests/config/fixtures
 * to load plugin fixtures. To use this feature set $plugin = 'pluginName' in your test case.
 *
 * @param string $plugin Name of the plugin to load
 * @return array Array of plugins that this plugin's test cases depend on.
 */
	public function solveDependencies($plugin) {
		$found = false;
		$result = array($plugin);
		$add = $result;
		do {
			$changed = false;
			$copy = $add;
			$add = array();
			foreach ($copy as $pluginName) {
				$dependent = $this->loadConfig('dependent', $pluginName);
				if (!empty($dependent)) {
					foreach ($dependent as $parentPlugin) {
						if (!in_array($parentPlugin, $result)) {
							$add[] = $parentPlugin;
							$result[] = $parentPlugin;
							$changed = true;
						}
					}
				}
			}
		} while ($changed);
		return $result;
	}

/**
 * {@inheritdoc}
 */
	public function startTest($method) {
		// Skip if method requires connection and currently not connected.
		if (!empty($this->_testsRequireConnection) && in_array($method, $this->_testsRequireConnection)) {
			$this->skipIf(!Reveal::is('App.online'), "Requires internet connection.");
		}

		// Skip if method is listed in `self::$_testsToRun`.
		$message = "Running selected tests only.";
		if (false !== self::$testsStopped) {
			$this->_testsToRun = array('');
			$message = "Disabled during test run.";
			if (true !== self::$testsStopped) {
				$message = self::$testsStopped;
			}
		}

		$this->skipIf(!empty($this->_testsToRun) && !in_array($method, $this->_testsToRun), $message);
	}

/**
 * Force all remaining tests to be skipped.
 *
 * @param string $message
 * @return void
 */
	public function stopTests($message = null) {
		self::$testsStopped = is_null($message) ? true : $message;
	}

/**
 * {@inheritdoc}
 */
	public function tearDown() {
		parent::tearDown();
		Reveal::destroyMock();
		App::build($this->_paths);
	}

/**
 * {@inheritdoc}
 */
	public static function tearDownAfterClass() {
		self::_restoreSettings();
		Configure::write('Config.language', Common::read('App.locale', 'eng'));

		if (is_a(self::$_this, 'CommonTestCase')) {
			// Drop all fixturized tables to avoid conflict with other test cases.
			self::$_this->fixtureManager->shutDown();
		}
	}

/**
 * Build the application's or plugin's paths required for tests.
 *
 * @param string $plugin Optional. Name of plugin for path's root. When left empty, uses APP path.
 * @param string $path Optional. Path to the test application. Defaults to 'Test/test_app'.
 * @return void
 */
	public static function buildTestAppPaths($plugin = null, $path = null) {
		$_path = self::getTestAppPath($plugin, $path);
		$packages = array(
			'Config',
			'Console', 'Console/Command', 'Console/Command/Task',
			'Controller', 'Controller/Component', 'Controller/Component/Auth', 'Controller/Component/Acl',
			'Lib',
			'Locale',
			'Model', 'Model/Behavior', 'Model/Datasource', 'Model/Datasource/Database', 'Model/Datasource/Session',
			'Plugin',
			'Vendor',
			'View', 'View/Helper'
		);

		$paths = array();
		foreach ($packages as $package) {
			if (is_dir($_path . $package)) {
				$paths[$package] = $_path . $package . DS;
			}
		}

		if (!empty($paths)) {
			App::build($paths);
		}
	}

/**
 * Load all plugins included in the plugin's 'test_app'.
 *
 * @param array $plugins List of plugins to load.
 * @param string $plugin Optional. Name of plugin for path's root. When left empty, uses APP path.
 * @param string $path Optional. Path to the test application. Defaults to 'Test/test_app'.
 * @return void
 */
	public static function loadTestAppPlugins($plugins = array(), $plugin = null, $path = null) {
		if (false === $plugins) {
			return;
		} else if (is_string($plugins)) {
			$plugins = array($plugins);
		}

		$path = self::getTestAppPath($plugin, $path) . 'Plugin';
		if ((empty($plugins) || true === $plugins) && is_dir($path)) {
			App::uses('Folder', 'Utility');
			$Folder = new Folder($path);
			$plugins = current($Folder->read());
		}

		array_walk($plugins, function($plugin) { CakePlugin::load($plugin); });
	}

/**
 * Build test application's path. By default, returns the 'APP/Test/test_app' path.
 *
 * @param string $plugin Optional. Name of plugin for path's root. When left empty, uses APP path.
 * @param string $path Optional. Path to the test application. Defaults to 'Test/test_app'.
 * @return string Full path to the test application.
 */
	public static function getTestAppPath($plugin = null, $path = null) {
		if (empty($path)) {
			$path = 'Test' . DS . 'test_app' . DS;
		}

		$plugin = empty($plugin) ? self::$plugin : $plugin;
		if (!empty($plugin)) {
			return CakePlugin::path($plugin) . $path;
		}

		return APP . $path;
	}

/**
 * Get model from string or object.
 *
 * @param mixed $model
 * @return Model
 */
	protected function _getModel($model) {
		if (is_object($model) && is_subclass_of($model, 'Model')) {
			$this->__models[$model->name] = $model;
			return $model;
		}

		if (!is_string($model)) {
			throw new BadRequestException();
		}

		if (!isset($this->__models[$model])) {
			$this->__models[$model] = ClassRegistry::init($model);
		}

		return $this->__models[$model];
	}

/**
 * [_restoreSettings description]
 *
 * @return void
 */
	protected static function _restoreSettings() {
		$commonPath = CakePlugin::path('Common') . 'Test' . DS . 'test_app' . DS . 'Config' . DS;
		copy($commonPath . 'settings.default', $commonPath . 'settings.json');
	}

/**
 * Convenience method allowing to validate data and return the result
 *
 * @param Model $Model Model being tested
 * @param array $data Profile data
 * @param array $validationErrors Validation errors: this variable will be updated with validationErrors (sorted by key) in case of validation fail
 * @return boolean Return value of Model::validate()
 */
	protected function _validData($model, $data, &$validationErrors = array()) {
		$model = $this->_getModel($model);

		$valid = true;
		$model->create($data);
		if (!$model->validates()) {
			$validationErrors = $model->validationErrors;
			ksort($validationErrors);
			$valid = false;
		} else {
			$validationErrors = array();
		}
		return $valid;
	}

}
