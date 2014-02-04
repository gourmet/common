<?php

App::uses('Component', 'Controller/Component');

class OpauthComponent extends Component {

/**
 * Default error message to fall back on.
 *
 * @var string
 */
	public $errorMessage = 'Unknown error';

/**
 * Associative array to map raw array's paths to model's data array.
 *
 * @var array
 */
	public $mapKeysToFields = array(
		'uid' => ':model:.:provider:_id',
		'raw.email' => ':model:.email',
		'raw.email-address' => ':model:.email',
		'raw.name' => ':model:.first_name',
		'raw.first-name' => ':model:.first_name',
		'raw.given_name' => ':model:.first_name',
		'raw.last-name' => ':model:.last_name',
		'raw.family_name' => ':model:.last_name',
		'raw.gender' => ':model:.gender',
		'raw.birthday' => ':model:.birthday',
		'raw.locale' => ':model:.locale',
		'raw.hd' => ':model:.company',
		'raw.picture-url' => ':model:.gravatar',
		'raw.profile_image_url' => ':model:.gravatar',
		'raw.picture' => ':model:.gravatar',
	);

/**
 * An URL (defined as a string or array) to the controller action that handles
 * registrations. Defaults to `/users/register`
 *
 * @var mixed
 */
	public $registerAction = array(
		'controller' => 'users',
		'action' => 'register',
		'plugin' => null
	);

/**
 * The session key name where the current request's Opauth data is stored. If
 * unspecified, it will be "Opauth".
 *
 * @var string
 */
	public $sessionKey = 'Opauth';

/**
 * Force Opauth's session to persist.
 *
 * @var boolean
 */
	public $sessionPersist = false;

/**
 *	The `FormAuthenticate` object configured in `AuthComponent`.
 *
 * @var mixed FormAuthenticate, null or false.
 */
	protected $_authenticateObject = null;

/**
 * Configured providers' strategies.
 *
 * @var array
 */
	protected $_providers = array();

/**
 * Current logged in user.
 *
 * @var array
 */
	protected $_user = null;

/**
 * {@inheritdoc}
 */
	public function initialize(Controller $Controller) {
		$this->errorMessage = __d('opauth', "Unknown error");
		$Controller->request->addDetector('opauth', array('callback' => array($this, 'isOpauth')));

		// Skip, unless `AuthComponent` is loaded.
		if (!$Controller->Components->loaded('Auth')) {
			$this->_authenticateObject = false;
			return;
		}

		// Get `FormAuthenticate` object.
		foreach ($Controller->Auth->constructAuthenticate() as $authenticateObject) {
			if ($authenticateObject instanceof FormAuthenticate) {
				$this->_authenticateObject = $authenticateObject;
				break;
			}
		}
	}

/**
 * {@inheritdoc}
 */
	public function startup(Controller $Controller) {
		// Skip, unless `AuthComponent` is loaded.
		if (!$Controller->Components->loaded('Auth')) {
			return;
		}

		$this->Auth = $Controller->Auth;
		$this->_user = $this->Auth->user();

		// Persist the `Auth.redirect` URL during the `OAuth2` dance.
		$sessionAuth = 'Auth.redirect';
		$sessionOpauth = $this->sessionKey . '.redirect';
		if ($Controller->Session->check($sessionOpauth)) {
			$Controller->Session->write($sessionAuth, $Controller->Session->read($sessionOpauth));
			$Controller->Session->delete($sessionOpauth);
		} else if ($Controller->Session->check($sessionAuth)) {
			$redirectUrl = $Controller->Session->read($sessionAuth);
			$opauthRoute = Router::parse($redirectUrl);
			if (!preg_match('/opauth/i', $opauthRoute['controller'])) {
				$Controller->Session->write($sessionOpauth, $redirectUrl);
			}
			$Controller->Session->delete($sessionAuth);
		}

		// Skip, unless current request part of the 'OAuth2' dance.
		if (
			(!array_key_exists('error', $Controller->data) && !array_key_exists('auth', $Controller->data))
			|| !array_key_exists('validated', $Controller->data)
		) {
			// Set the current provider for dispatched registration.
			if ($Controller->Session->check($this->sessionKey . '.auth.provider')) {
				$this->_provider = $Controller->Session->read($this->sessionKey . '.auth.provider');
			}
			return;
		}

		// Handle 'OAuth2' dance's error.
		if (!$Controller->data['validated']) {
			if (empty($Controller->data['error'])) {
				throw new Exception();
			}

			// Fallback to default error message.
			$message = $this->errorMessage;
			if (isset($Controller->data['error']['message'])) {
				$message = $Controller->data['error']['message'];
			}

			$Controller->Session->setFlash($message);
			$Controller->redirect($Controller->Auth->loginAction);
			return;
		}

		$authData = $Controller->data['auth'];

		$this->_provider = $authData['provider'];

		if (empty($this->_authenticateObject)) {
			throw new Exception();
		}

		if ($this->_user = $this->isAuthenticated($authData)) {
			$this->authorized();
			return;
		}

		// Override the request's referer when it fails to log user based on opauth data.
		$_SERVER['HTTP_REFERER'] = Router::url($Controller->Auth->loginAction);

		$data = $this->mapData($authData);

		if ($this->sessionPersist) {
			$Controller->Session->write($this->sessionKey, $data);
			$Controller->Session->write($this->sessionKey . '.auth', $authData);
		}

		if (is_string($this->registerAction) && method_exists($Controller, $this->registerAction)) {
			$Controller->{$registerAction}($data);
			return;
		}

		// Redirect to complete required fields for registration.
		$CakeRequest = new CakeRequest(Router::url($this->registerAction));
		$CakeRequest->data = $data;

		App::uses('Dispatcher', 'Routing');
		$Dispatcher = new Dispatcher();
		$Dispatcher->dispatch($CakeRequest, new CakeResponse());
	}

	public function authorized($name = null) {
		if (($this->Auth instanceof AuthComponent && !$this->Auth->user()) || !$this->enabled()) {
			return false;
		}

		foreach ($this->_providers as $provider => $config) {
			if (array_key_exists('local', $config)) {
				continue;
			}
			$this->_providers[$provider]['local'] = $this->Auth->user(Inflector::underscore($provider) . '_id');
			if (empty($this->_providers[$provider]['local'])) {
				$this->_providers[$provider]['local'] = false;
			}

			if ($name === $provider) {
				break;
			}
		}

		if (empty($name)) {
			return array_combine(array_keys($this->_providers), Hash::extract($this->_providers, '{s}.local'));
		}

		return $this->_providers[$name]['local'];
	}

/**
 * Provider's strategy configuration.
 *
 * @param string $name Provider's name (i.e. linkedin, facebook, etc.).
 * @return mixed False if provider's isn't loaded. The configuration array otherwise.
 */
	public function config($name) {
		if (!$this->enabled($name)) {
			return false;
		}

		return $this->_providers[$name];
	}

/**
 * Check if a given provider's strategy is enabled.
 *
 * @param string $name Optional. Provider's name (i.e. linkedin, facebook, etc.).
 * @return mixed True if given name is enabled, false if not and the list of enabled providers if no name provided.
 */
	public function enabled($name = null) {
		if (empty($this->_providers)) {
			$this->_providers = (array) Configure::read('Opauth.Strategy');
		}

		if (empty($this->_providers)) {
			return false;
		}

		if (!empty($name)) {
			return isset($this->_providers[$name]);
		}

		return $this->_providers;
	}

/**
 * Checks if OAuth2 user authenticates to local user base.
 *
 * @param array $authData Opauth's data.
 * @return boolean The local user's data if already logged in or when the provider's UID
 *   and/or email match a local user record. False otherwise.
 */
	public function isAuthenticated($authData) {
		// Case where user is locally logged in.
		if (!empty($this->_user)) {
			$this->updateData($this->mapData($authData));
			return $this->_user;
		}

		$Model = ClassRegistry::init($this->_authenticateObject->settings['userModel']);

		$conditions = array($Model->alias . '.' . Inflector::underscore($authData['provider']) . '_id' => $authData['uid']);
		if (isset($authData['info']['email'])) {
			$conditions = array('OR' => array_merge($conditions, array($Model->alias . '.' . 'email' => $authData['info']['email'])));
		}
		if (!empty($this->_authenticateObject->settings['scope'])) {
			$conditions += $this->_authenticateObject->settings['scope'];
		}

		$result = $Model->find('first', array(
			'conditions' => $conditions,
			'contain' => $this->_authenticateObject->settings['contain'],
			'recursive' => $this->_authenticateObject->settings['recursive']
		));

		// Case where no local user matches OAuth's provider UID and/or email.
		if (empty($result) || empty($result[$Model->alias])) {
			return false;
		}

		// Automatically log OAuth user by writing the local user's data to the session to by-pass the
		// `BaseAuthenticate::_findUser()` auto-hashing of password.
		$user = $result[$Model->alias];
		if (array_key_exists($this->_authenticateObject->settings['fields']['password'], $user)) {
			unset($user[$this->_authenticateObject->settings['fields']['password']]);
		}
		unset($result[$Model->alias]);
		$this->_user = array_merge($user, $result);
		$this->updateData($this->mapData($authData));
		return $this->_user;
	}

/**
 * Detector's callback for `CakeRequest::is('opauth')`.
 *
 * @param CakeRequest $CakeRequest Request to check.
 * @return boolean True if it's an OAuth2 dance's step.
 */
	public function isOpauth(CakeRequest $CakeRequest) {
		if (empty($this->_authenticateObject)) {
			return false;
		}


		$provider = Inflector::underscore($this->_provider);
		$keys = array($provider . '_id', $provider . '_credentials');
		$Model = ClassRegistry::init($this->_authenticateObject->settings['userModel']);
		return (bool) (array_intersect_key(Hash::normalize($keys), $CakeRequest->data[$Model->alias]));
	}

/**
 * Maps Opauth's data for use in models.
 *
 * @param array $authData Opauth's data.
 * @return array Mapped data.
 */
	public function mapData($authData) {
		$map = $this->mapKeysToFields;
		list(, $model) = pluginSplit($this->_authenticateObject->settings['userModel']);
		$provider = Inflector::underscore($authData['provider']);
		$data = array($model => array($provider . '_credentials' => serialize($authData['credentials'])));

		foreach ($this->mapKeysToFields as $key => $path) {
			$map[$key] = String::insert($path, compact('model', 'provider'), array('after' => ':'));
			$value = Hash::get($authData, $key);
			$mappedKey = explode('.', $map[$key]);
			if (!empty($this->_user[array_pop($mappedKey)]) || empty($value)) {
				continue;
			}

			$data = Hash::insert($data, $map[$key], Hash::get($authData, $key));
		}

		return $data;
	}

/**
 * Save Opauth's data (uid and credentials) to currently logged in user's session and model record.
 *
 * @param array $data Data to save.
 * @return mixed On success Model::$data if its not empty or true, false on failure
 */
	public function updateData($data) {
		$Model = ClassRegistry::init($this->_authenticateObject->settings['userModel']);
		$Model->id = $this->_user[$Model->primaryKey];
		$userData = $Model->save($data, false);
		$this->_user = array_merge((array) $this->_user, $userData[$Model->alias]);
		$this->Auth->login($this->_user);
		return $userData;
	}

}
