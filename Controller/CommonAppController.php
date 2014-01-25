<?php
/**
 * CommonAppController
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

App::uses('Controller', 'Controller');
App::uses('CakeLog', 'Log');
App::uses('DebugTimer', 'DebugKit.Lib');

/**
 * Common application's controller
 *
 * This file needs to be extended by `AppController` in order to take advantage
 * of its built-in features.
 *
 * @package       Common.Controller
 */
class CommonAppController extends Controller {

/**
 * List of breadcrumbs. Associative array where the key is the title
 * and it's associated value is the link and/or options.
 *
 * Example:
 *
 *    $this->breadCrumbs = array(
 *      'Home' => array('controller' => 'pages', 'action' => 'display', 'home'),
 *      'Users' => array(
 *        'link' => array('controller' => 'users', 'action' => 'index'),
 *        'options' => array('class' => 'parent')
 *      )
 *    );
 *
 * @var array
 */
	public $breadCrumbs = array();

/**
 * Crumb titles. By default, the friendly plugin, controller and action
 * names are used. This allows to overwrite any of them by setting their
 * original name as the key and the replacement as value.
 *
 * Example:
 *
 *   $this->crumbTitles = array('admin_edit' => 'Edit profile');
 *
 * @var array
 */
	public $crumbTitles = array();

/**
 * List of pre-defined flash messages.
 *
 * @var array
 * @see `CommonAppController::flash()`.
 */
	public $flashMessages = array();

/**
 * User-friendly name for the `Controller::$modelClass`.
 *
 * @var string
 */
	public $modelName = null;

/**
 * {@inheritdoc}
 */
	public function __construct($request = null, $response = null) {
		parent::__construct($request, $response);

		// Mark all the `CommonAppController` methods as private for `Controller::__isPrivateAction()`.
		$this->methods = array_diff($this->methods, get_class_methods('CommonAppController'));
	}

/**
 * {@inheritdoc}
 */
	public function beforeFilter() {
		if (
			$this->uses
			&& !empty($this->modelClass)
			&& empty($this->modelName)
			&& isset($this->{$this->modelClass})
			&& $this->{$this->modelClass} instanceof $this->modelClass
		) {
			$this->modelName = $this->{$this->modelClass}->friendly;
		}

		if (CakePlugin::loaded('Users')) {
			Configure::write('Users.dashboardUrl', Common::read('Users.dashboardUrl', $this->Auth->loginRedirect));
		}

		if (isset($this->Auth) && $this->Auth instanceof AuthComponent && !Reveal::is('Page.prefixed')) {
			$this->Auth->allow($this->request->action);
		}

		if ($this->Components->loaded('Cookie')) {
			$this->Cookie->name = '__' . Inflector::underscore(Common::read('Security.cookieName', Configure::read('App.title')));
		}

		$this->triggerEvent('Controller.beforeFilter', $this);
	}

/**
 * {@inheritdoc}
 */
	public function beforeRender() {
		$this->_constructCrumbs();
		$defaults = array(
			'breadCrumbs' => $this->breadCrumbs,
			'modelClass' => $this->modelClass,
			'modelName' => $this->modelName,
			'title' => Configure::read('App.title'),
			'defaultUrl' => array('prefix' => null) + (!empty($this->request->prefix) ? array($this->request->prefix => false) : array())
		);

		if (CakePlugin::loaded('AssetCompress')) {
			$defaults['assetCompressOptions'] = array();
			if (Reveal::is('DebugKit.running')) {
				$defaults['assetCompressOptions'] = array('raw' => true);
			}
		}

		$this->viewVars = array_merge($defaults, $this->viewVars);
	}

/**
 * Overrides `Controller::constructClasses()` to apply the 'Controller.constructClasses'
 * event's results.
 *
 * @return  void
 */
	public function constructClasses() {
		$timer = 'controllerConstructClasses';
		Common::startTimer($timer, __d('common', "Event: Controller.constructClasses"));

		$properties = array('components', 'helpers');
		$result = $this->triggerEvent('Controller.constructClasses', $this);

		$this->_mergeControllerVars();
		foreach ($properties as $property) {
			if (!isset($result[$property])) {
				continue;
			}
			$this->{$property} = Hash::merge(
				Hash::normalize($result[$property]),
				Hash::normalize((array) $this->{$property})
			);
			unset($result[$property]);
		}

		$this->_set($result);
		Common::stopTimer($timer);
		parent::constructClasses();
	}

/**
 * Blackhole callback for the `SecurityComponent`.
 *
 *  - Automatically redirect secured requests to HTTPS.
 *
 * @param  string $type The type of blackhole.
 * @return void
 */
	public function forceSsl($type) {
		$host = env('SERVER_NAME');
		if (empty($host)) {
			$host = env('HTTP_HOST');
		}
		if ('secure' == $type) {
			$this->redirect('https://' . $host . $this->here);
		}
	}

/**
 * Overrides `Controller::getEventManager()`.
 *
 * - Use `CommonEventManager` instead of `CakeEventManager`.
 * - Auto-load the 'Controller' and this controller's specific events (using scopes).
 *
 * @return CommonEventManager
 */
	public function getEventManager() {
		if (empty($this->_eventManager)) {
			$this->_eventManager = new CommonEventManager();
			$this->_eventManager->loadListeners($this->_eventManager, 'Controller');
			$this->_eventManager->loadListeners($this->_eventManager, $this->name);
			$this->_eventManager->attach($this->Components);
			$this->_eventManager->attach($this);
		}
		return $this->_eventManager;
	}

/**
 * Authorize all requests by prefix.
 *
 * @param array $user User's record returned by `BaseAuthenticate::_findUser()`.
 * @return boolean
 */
	public function isAuthorized($user) {
		if (Configure::read('Routing.prefixes')) {
			return $this->request->prefix == $user['prefix'];
		}
		return true;
	}

/**
 * Overrides Object::log(). Used also in CommonAppModel, CommonAppHelper.
 *
 * - Support for Cake's own `scopes` (at the time I wrote this, Object::log() does not).
 * - Defaults all logs to the default scope for less writing ;)
 *
 * @param string $msg Log message.
 * @param integer $type Error type constant. Defined in app/Config/core.php.
 * @return boolean Success of log write.
 */
	public function log($msg, $type = LOG_ERR, $scopes = array('default')) {
		if (!is_string($msg)) {
			$msg = print_r($msg, true);
		}

		$scopes = (array) $scopes;
		if (!in_array('controller', $scopes)) {
			$scopes[] = 'controller';
		}

		return Common::getLog()->write($type, $msg, $scopes);
	}

/**
 * Create generic flash message to display.
 *
 * The method can be used in two ways, using the `AppController::$flashMessages` and setting
 * up some defaults or direclty passing the message and options.
 *
 * Common defaults are set up by the `Controller.flashMessages` event and can be overridden
 * at anytime before calling this method.
 *
 *		// manually
 *  	$this->flash('foo bar', array('redirect' => true, 'level' => 'warning'));
 *
 *		// pre-defined
 *  	$this->flashMessages['my_message'] = array(
 *   		'message' => 'foo bar',
 *     	'redirect' => true, // false, '', array() '/url'
 *      'level' => 'warning', // success, error etc
 *    );
 *
 *		$this->flash('my_message');
 *
 *		// pre-defined with custom level option
 *  	$this->flash('my_message', array('level' => 'success'));
 *
 * @param string $msg The message to show to the user.
 * @param array $options Array of configuration options.
 * @return void
 * @see CommonEventListener::controllerFlashMessages()
 */
	public function flash($msg, $options = array()) {
		$defaults = array(
			'level' => 'success',
			'redirect' => false,
			'plugin' => 'Common',
			'code' => 0,
			'element' => 'alerts/default',
			'dismiss' => false,
			'key' => 'flash'
		);

		if ($msg instanceof Exception) {
			$options = array_merge(array('level' => 'error'), $options);
			$msg = $msg->getMessage();
		}

		if (!empty($this->flashMessages[$msg])) {
			if (!is_array($this->flashMessages[$msg])) {
				$msg = $this->flashMessages[$msg];
			} else if (!empty($this->flashMessages[$msg]['message'])) {
				$options = array_merge($this->flashMessages[$msg], $options);
				$msg = $options['message'];
				unset($options['message']);
			}
		}

		$insert = array('modelName' => strtolower($this->modelName));
		$msg = ucfirst(String::insert($msg, array_merge($insert, $options), array('clean' => true)));

		$options = array_merge($defaults, $options);
		$params = array('code' => $options['code'], 'plugin' => $options['plugin']);

		// View element
		$element = explode('/', $options['element']);
		$pluginDot = null;
		if (!empty($this->params['prefix'])) {
			array_push($element, $this->params['prefix'] . '_' . array_pop($element));
		}

		$elementPath = 'View' . DS . 'Elements' . DS . implode('/', $element) . '.ctp';
		if (!empty($options['plugin']) && CakePlugin::loaded($options['plugin'])) {
			$elementPath = CakePlugin::path($options['plugin']) . $elementPath;
			$pluginDot = $options['plugin'] . '.';
		}

		if (!is_file($elementPath)) {
			$element = $defaults['element'];
		} else {
			$element = implode('/', $element);
		}

		// Redirect URL
		$redirect = $options['redirect'];
		if (true === $redirect) {
			$redirect = $this->referer();
		} else if (
			!empty($this->params['prefix'])
			&& is_array($redirect)
			&& !isset($redirect['prefix'])
			&& !isset($redirect[$this->params['prefix']])
		) {
			$redirect['prefix'] = $this->params['prefix'];
			$redirect[$this->params['prefix']] = true;
		}

		$key = $options['key'];

		unset($options['element'], $options['key'], $options['redirect']);

		// Ajax rendering
		if ($this->request->is('ajax')) {
			$params['level'] = $options['level'];
			$params['message'] = $msg;
			$redirect = !$redirect ? false : Router::url($redirect);
			$this->set('json', compact('params', 'redirect'));
			return;
		}

		// Normal rendering
		$this->Session->setFlash($msg, $element, $options, $key);
		if (!empty($redirect)) {
			$this->redirect($redirect);
		}
	}

/**
 * Trigger an event using the 'Controller' event manager instance instead of the
 * global one.
 *
 * @param string|CakeEvent $event The event key name or instance of CakeEvent.
 * @param object $subject Optional. Event's subject.
 * @param mixed $data Optional. Event's data.
 * @return mixed Result of the event.
 */
	public function triggerEvent($event, $subject = null, $data = null) {
		return CommonEventManager::trigger($event, $subject, $data, $this->getEventManager());
	}

/**
 * Append `$str` string to given `$key` crumb.
 *
 * @param string $str String to append.
 * @param string $key Optional. Defaults to last crumb.
 * @return void
 */
	protected function _appendToCrumb($str, $key = null) {
		if (empty($key)) {
			$key = end(array_keys($this->breadCrumbs));
		}

		$this->breadCrumbs["$key $str"] = $this->breadCrumbs[$key];
		unset($this->breadCrumbs[$key]);
	}

/**
 * Breadcrumb constructor.
 *
 * @return void
 */
	protected function _constructCrumbs() {
		if (
			$this instanceof CakeErrorController
			|| $this instanceof PagesController
			|| false === Common::read('Layout.showCrumbs', true)
			|| false === $this->breadCrumbs
			|| !empty($this->breadCrumbs)
		) {
			return;
		}

		// Home.
		$this->breadCrumbs = array(__d('common', "Home") => '/');

		// Dashboard.
		if (CakePlugin::loaded('Users') && Reveal::is('User.loggedin')) {
			$this->breadCrumbs = array(
				__d('common', "Dashboard") => $this->Auth->loginRedirect
			);

			if (
				$this->request->controller == $this->Auth->loginRedirect['controller']
				&& preg_match('/' . $this->Auth->loginRedirect['action'] . '$/', $this->action)
				&& (empty($this->Auth->loginRedirect['plugin']) || $this->plugin == Inflector::camelize($this->Auth->loginRedirect['plugin']))
			) {
				$this->breadCrumbs[__d('common', "Dashboard")] = array();
				return;
			}
		}

		// Plugin.
		if (!empty($this->plugin)) {
			$title = empty($this->crumbTitles[$this->plugin]) ? $this->plugin : $this->crumbTitles[$this->plugin];
			$this->breadCrumbs[$title] = array('plugin' => Inflector::underscore($this->plugin), 'controller' => Inflector::underscore($this->plugin), 'action' => 'index');
			if (Router::normalize(Router::url($this->breadCrumbs[$title])) == $this->request->here) {
				$this->breadCrumbs[$title] = array();
			}

			if (($this->plugin == $this->name || (!empty($this->crumbTitles[$this->name]) && $this->plugin == $this->crumbTitles[$this->name])) && in_array('index', explode('_', $this->action))) {
				return;
			}
		}

		// Controller.
		if (!empty($this->crumbTitles[$this->name])) {
			$this->breadCrumbs[$this->crumbTitles[$this->name]] = array('action' => 'index');
		} else if (!array_key_exists($this->name, $this->crumbTitles)) {
			$this->breadCrumbs[!empty($this->modelName) ? Inflector::pluralize($this->modelName) : Inflector::humanize(Inflector::underscore($this->name))] = array('action' => 'index');
		}

		if (array_pop(explode('_', $this->action)) == 'index') {
			$this->breadCrumbs[end(array_keys($this->breadCrumbs))] = array();
			return;
		}

		// Action
		if (!empty($this->crumbTitles[$this->action])) {
			$this->breadCrumbs[$this->crumbTitles[$this->action]] = array();
		} else if (!array_key_exists($this->action, $this->crumbTitles)) {
			$action = str_replace($this->request->prefix, '', $this->action);
			if ($action == $this->action) {
				$action = str_replace(Configure::read($this->plugin . '.routingPrefixes.' . $this->request->prefix), '', $this->action);
			}
			$this->breadCrumbs[Inflector::humanize(Inflector::underscore($action))] = array();
		}

	}

	protected function _create() {
		if ($this->request->is('post') && !empty($this->data)) {
			if ($this->{$this->modelClass}->save($this->data)) {
				return $this->flash('save.success', array('redirect' => true));
			}
			return $this->flash('save.fail', array('redirect' => true));
		}
	}

/**
 * Common edit action.
 *
 * @param string $id Model's record primary key value.
 * @return void
 */
	protected function _edit($id) {
		try {
			$result = $this->{$this->modelClass}->edit($id, $this->request->data);
		} catch (OutOfBoundsException $e) {
			return $this->flash($e, array('redirect' => $this->referer(array('action' => 'list'), true)));
		}

		$this->request->data = $this->{$this->modelClass}->data;

		if (!empty($this->request->data[$this->{$this->modelClass}->alias][$this->{$this->modelClass}->displayField])) {
			$this->_appendToCrumb($this->request->data[$this->{$this->modelClass}->alias][$this->{$this->modelClass}->displayField]);
		}

		if (false === $result) {
			$this->flash('save.fail');
		} else if ($this->request->is('put')) {
			$this->flash('save.success');
		}
	}

/**
 * Common list action.
 *
 * @param Model|string $object Model to paginate (e.g: model instance, or 'Model', or 'Model.InnerModel')
 * @param string|array $scope Additional find conditions to use while paginating
 * @param array $whitelist List of allowed fields for ordering. This allows you to prevent ordering
 *   on non-indexed, or undesirable columns.
 * @return void
 */
	protected function _list($object = null, $scope = array(), $whitelist = array()) {
		$View = $this->_getViewObject();
		if (!$View->Helpers->loaded('Common.Table')) {
			$this->_getViewObject()->loadHelper('Common.Table');
		}
		$this->{$this->modelClass}->recursive = 0;
		$this->set('results', $this->paginate($object, $scope, $whitelist));
	}

/**
 * Common status action.
 *
 * @param string $id Model's record primary key value.
 * @param string $status New status to change to.
 * @return void
 */
	protected function _status($id, $status) {
		if (!$this->{$this->modelClass}->changeStatus($id, $status)) {
			return $this->flash('status.fail');
		}
		$this->flash('status.success');
	}

}
