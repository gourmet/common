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
	public static $implementedEvents = array();

/**
 * List of loaded listeners by plugin.
 *
 * @var array
 */
	public static $loadedListeners = array();

/**
 * List of loaded scopes by plugin.
 *
 * @var array
 */
	public static $loadedScopes = array();

/**
 * Attach listeners for a specific scope only.
 *
 * @param CakeEventListener $subscriber
 * @param CommonEventManager $manager Optional. Instance to use. Defaults to the global instance.
 * @param string $scope Optional. The scope of events to load.
 * @return void
 */
	public static function attachByScope(CakeEventListener $subscriber, $manager = null, $scope = null) {
		if (!($manager instanceof CakeEventManager)) {
			$scope = $manager;
			$manager = CakeEventManager::instance();
		}

		if (empty($scope)) {
			$manager->attach($subscriber);
			return;
		}

		foreach ($subscriber->implementedEvents() as $eventKey => $function) {
			if (strpos($eventKey, $scope) !== 0) {
				continue;
			}

			$options = array();
			$method = $function;
			if (is_array($function) && isset($function['callable'])) {
				list($method, $options) = $manager->_extractCallable($function, $subscriber);
			} elseif (is_array($function) && is_numeric(key($function))) {
				foreach ($function as $f) {
					list($method, $options) = $manager->_extractCallable($f, $subscriber);
					$manager->attach($method, $eventKey, $options);
				}
				continue;
			}
			if (is_string($method)) {
				$method = array($subscriber, $function);
			}
			$manager->attach($method, $eventKey, $options);
		}
	}

	// public function dispatch($event) {
	// 	parent::dispatch($event);
	// 	self::flush();
	// }

/**
 * Auto-load plugin event listeners.
 *
 * @param CommonEventManager $manager Optional. Instance to use. Defaults to the global instance.
 * @param string $scope Optional. The scope of events to load.
 * @return CakeEventManager
 */
	public static function loadListeners($manager = null, $scope = null) {
		if (!($manager instanceof CakeEventManager)) {
			$manager = CakeEventManager::instance();
		}

		empty($manager::$loadedListeners['Common'])
		&& $manager::$loadedListeners['Common'] = new CommonEventListener();

		(empty($manager::$loadedScopes['Common']) || !in_array($scope, (array) $manager::$loadedScopes['Common']))
		&& $manager::$loadedScopes['Common'][] = $scope
		&& CommonEventManager::attachByScope($manager::$loadedListeners['Common'], $manager, $scope);

		$manager::$implementedEvents['Common'] = array_keys($manager::$loadedListeners['Common']->implementedEvents());

		foreach (CakePlugin::loaded() as $plugin) {
			if (isset($manager::$loadedListeners[$plugin])) {
				if (
					$manager::$loadedListeners[$plugin]
					&& !empty($scope)
					&& !in_array($scope, (array) $manager::$loadedScopes[$plugin])
				) {
					self::$loadedScopes[$plugin][] = $scope;
					CommonEventManager::attachByScope($manager::$loadedListeners[$plugin], $manager, $scope);
				}
				continue;
			}

			$class = $plugin . 'EventListener';

			if (ClassRegistry::isKeySet($class)) {
				$manager::$loadedListeners[$plugin] = ClassRegistry::getObject($class);
			} else if (file_exists(CakePlugin::path($plugin) . 'Event' . DS . $class . '.php')) {
				App::uses($class, $plugin . '.Event');
				$manager::$loadedListeners[$plugin] = new $class();
			} else {
				$manager::$loadedListeners[$plugin] = false;
				continue;
			}

			$manager::$loadedScopes[$plugin] = array();
			$manager::$implementedEvents[$plugin] = array_keys($manager::$loadedListeners[$plugin]->implementedEvents());

			if (empty($scope)) {
				$manager->attach($manager::$loadedListeners[$plugin]);
			} else if (!in_array($scope, $manager::$loadedScopes[$plugin])) {
				$manager::$loadedScopes[$plugin][] = $scope;
				CommonEventManager::attachByScope($manager::$loadedListeners[$plugin], $manager, $scope);
			}
		}

		if (!Reveal::is('Page.test') && !isset($manager::$loadedListeners['App'])) {
			if (file_exists(APP . 'Event' . DS . 'AppEventListener.php')) {
				App::uses('AppEventListener', 'Event');
				$manager::$loadedListeners['App'] = new AppEventListener();
				$manager::$loadedScopes['App'] = array();
				$manager::$implementedEvents['App'] = array_keys($manager::$loadedListeners['App']->implementedEvents());
				if (empty($scope)) {
					$manager->attach($manager::$loadedListeners['App']);
				} else if (!in_array($scope, $manager::$loadedScopes['App'])) {
					self::$loadedScopes['App'][] = $scope;
					CommonEventManager::attachByScope($manager::$loadedListeners['App'], $manager, $scope);
				}
			}
		} else if (
			isset($manager::$loadedListeners['App'])
			&& $manager::$loadedListeners['App']
			&& !empty($scope)
			&& !in_array($scope, $manager::$loadedScopes['App'])
		) {
			$manager::$loadedScopes['App'][] = $scope;
			CommonEventManager::attachByScope($manager::$loadedListeners['App'], $manager, $scope);
		}

		return $manager;
	}

/**
 * Flushes all static properties (useful in tests).
 *
 * @return void
 */
	public static function flush($scope = null) {
		if (empty($scope)) {
			self::$implementedEvents = self::$loadedListeners = self::$loadedScopes = array();
			return;
		}

		// foreach (self::)
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
	public static function trigger($event, $subject = null, $data = null, $manager = null) {
		if (!($event instanceof CakeEvent)) {
			$event = new CakeEvent($event, $subject, $data);
		}

		self::loadListeners($manager)->dispatch($event);
		// self::flush();
		return $event->result;
	}

}
