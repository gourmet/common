<?php
/**
 * CommonEventManager
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

App::uses('CakeEvent', 'Event');
App::uses('CakeEventManager', 'Event');
App::uses('CommonEventListener', 'Common.Event');

/**
 * Common event manager
 *
 * Similar to the `CakeEventManager` but also manages plugins' events defined in the
 * `PluginName/Event/PluginNameEventListener.php` file.
 *
 * @package       Common.Event
 */
class CommonEventManager extends CakeEventManager {

/**
 * List of implemented events by plugin.
 *
 * @var array
 */
	public $implementedEvents = array();

/**
 * List of loaded listeners by plugin.
 *
 * @var array
 */
	public $loadedListeners = array();

/**
 * List of loaded scopes by plugin.
 *
 * @var array
 */
	public $loadedScopes = array();

	public static $triggeredEvents = array();

/**
 * Attach listeners for a specific scope only.
 *
 * @param CakeEventListener $subscriber
 * @param CommonEventManager $manager Optional. Instance to use. Defaults to the global instance.
 * @param string $scope Optional. The scope of events to load.
 * @return void
 */
	public function attachByScope(CakeEventListener $subscriber, $manager = null, $scope = null) {
		if (!($manager instanceof CakeEventManager)) {
			$scope = $manager;
			$_this = $this;
		} else {
			$_this = $manager;
		}

		if (empty($scope)) {
			$_this->attach($subscriber);
			return;
		}

		foreach ($subscriber->implementedEvents() as $eventKey => $function) {
			if (strpos($eventKey, $scope) !== 0) {
				continue;
			}

			$options = array();
			$method = $function;
			if (is_array($function) && isset($function['callable'])) {
				list($method, $options) = $_this->_extractCallable($function, $subscriber);
			} elseif (is_array($function) && is_numeric(key($function))) {
				foreach ($function as $f) {
					list($method, $options) = $_this->_extractCallable($f, $subscriber);
					$_this->attach($method, $eventKey, $options);
				}
				continue;
			}
			if (is_string($method)) {
				$method = array($subscriber, $function);
			}
			$_this->attach($method, $eventKey, $options);
		}
	}

/**
 * Auto-load plugin event listeners.
 *
 * @param CommonEventManager $manager Optional. Instance to use. Defaults to the global instance.
 * @param string $scope Optional. The scope of events to load.
 * @return CakeEventManager
 */
	public function loadListeners($manager = null, $scope = null) {
		if (!($manager instanceof CakeEventManager)) {
			$scope = $manager;
			$_this = $this;
		} else {
			$_this = $manager;
		}


		empty($_this->loadedListeners['Common'])
		&& $_this->loadedListeners['Common'] = new CommonEventListener();

		(empty($_this->loadedScopes['Common']) || !in_array($scope, (array) $_this->loadedScopes['Common']))
		&& $_this->loadedScopes['Common'][] = $scope
		&& $_this->attachByScope($_this->loadedListeners['Common'], $_this, $scope);

		$_this->implementedEvents['Common'] = array_keys($_this->loadedListeners['Common']->implementedEvents());

		foreach (CakePlugin::loaded() as $plugin) {
			if (isset($_this->loadedListeners[$plugin])) {
				if (
					$_this->loadedListeners[$plugin]
					&& !empty($scope)
					&& !in_array($scope, (array) $_this->loadedScopes[$plugin])
				) {
					$_this->loadedScopes[$plugin][] = $scope;
					$_this->attachByScope($_this->loadedListeners[$plugin], $_this, $scope);
				}
				continue;
			}

			$class = $plugin . 'EventListener';

			if (ClassRegistry::isKeySet($class)) {
				$_this->loadedListeners[$plugin] = ClassRegistry::getObject($class);
			} else if (file_exists(CakePlugin::path($plugin) . 'Event' . DS . $class . '.php')) {
				App::uses($class, $plugin . '.Event');
				$_this->loadedListeners[$plugin] = new $class();
			} else {
				$_this->loadedListeners[$plugin] = false;
				continue;
			}

			$_this->loadedScopes[$plugin] = array();
			$_this->implementedEvents[$plugin] = array_keys($_this->loadedListeners[$plugin]->implementedEvents());

			if (empty($scope)) {
				$_this->attach($_this->loadedListeners[$plugin]);
			} else if (!in_array($scope, $_this->loadedScopes[$plugin])) {
				$_this->loadedScopes[$plugin][] = $scope;
				$_this->attachByScope($_this->loadedListeners[$plugin], $_this, $scope);
			}
		}

		if ((!Reveal::is('Page.test') || empty($_GET['plugin'])) && !isset($_this->loadedListeners['App'])) {
			if (file_exists(APP . 'Event' . DS . 'AppEventListener.php')) {
				App::uses('AppEventListener', 'Event');
				$_this->loadedListeners['App'] = new AppEventListener();
				$_this->loadedScopes['App'] = array();
				$_this->implementedEvents['App'] = array_keys($_this->loadedListeners['App']->implementedEvents());
				if (empty($scope)) {
					$_this->attach($_this->loadedListeners['App']);
				} else if (!in_array($scope, $_this->loadedScopes['App'])) {
					$_this->loadedScopes['App'][] = $scope;
					$_this->attachByScope($_this->loadedListeners['App'], $_this, $scope);
				}
			}
		} else if (
			isset($_this->loadedListeners['App'])
			&& $_this->loadedListeners['App']
			&& !empty($scope)
			&& !in_array($scope, $_this->loadedScopes['App'])
		) {
			$_this->loadedScopes['App'][] = $scope;
			$_this->attachByScope($_this->loadedListeners['App'], $_this, $scope);
		}

		return $_this;
	}

/**
 * Flushes all static properties (useful in tests).
 *
 * @return void
 */
	public function flush($scope = null) {
		$this->implementedEvents = $this->loadedListeners = $this->loadedScopes = array();
	}

/**
 * Trigger event.
 *
 * @param string|CakeEvent $event The event key name or instance of CakeEvent.
 * @param object $subject Optional. Event subject.
 * @param mixed $data Optional. Event data.
 * @param CommonEventManager $manager Optional. Instance to use. Defaults to the global instance.
 * @return mixed Result of the triggered event listeners.
 */
	public function trigger($event, $subject = null, $data = null, $manager = null) {
		if (!($event instanceof CakeEvent)) {
			$event = new CakeEvent($event, $subject, $data);
		}

		self::loadListeners($manager)->dispatch($event);

		return $event->result;
	}

}
