<?php
/**
 * CommonEventListener
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

App::uses('CakeEventListener', 'Event');

/**
 * Common event listener
 *
 * Collection of common events to load.
 *
 * @package       Common.Event
 */
class CommonEventListener implements CakeEventListener {

	public function implementedEvents() {
		return array(
			'Controller.constructClasses' => array('callable' => 'controllerConstructClasses'),
			'Model.construct' => array('callable' => 'modelConstruct'),
		);
	}

	public function controllerConstructClasses(CakeEvent $Event) {
		// Load `Navigation` helper.
		$Event->result = Hash::merge((array) $Event->result, array('helpers' => array('Common.Navigation')));

		// Load `TwitterBootstrap` helpers and components.
		if (
			(!isset($Event->subject->twitterBootstrap) || $Event->subject->twitterBootstrap)
			&& CakePlugin::loaded('TwitterBootstrap')
		) {
			$Event->result = Hash::merge((array) $Event->result, array(
				'helpers' => array(
					'Html' => array('className' => 'TwitterBootstrap.BootstrapHtml'),
					'Form' => array('className' => 'TwitterBootstrap.BootstrapForm'),
					'Paginator' => array('className' => 'TwitterBootstrap.BootstrapPaginator')
				),
				'layout' => 'Common.twitter_bootstrap'
			));
		}

		// Load `BoostCake` helpers and components.
		if (
			(!isset($Event->subject->boostCake) || $Event->subject->boostCake)
			&& CakePlugin::loaded('BoostCake')
		) {
			$Event->result = Hash::merge((array) $Event->result, array(
				'helpers' => array(
					'Html' => array('className' => 'BoostCake.BoostCakeHtml'),
					'Form' => array('className' => 'BoostCake.BoostCakeForm'),
					'Paginator' => array('className' => 'BoostCake.BoostCakePaginator')
				),
				'layout' => 'Common.twitter_bootstrap'
			));
		}

		// Load `DebugKit` component.
		if (Reveal::is('DebugKit.loaded') && !Reveal::is('Page.test')) {
			$Event->result = Hash::merge((array) $Event->result, array('components' => array(
				'DebugKit.Toolbar' => array(
					'autoRun' => Common::read('DebugKit.autoRun', true),
					'forceEnable' => Common::read('DebugKit.forceEnable', false),
					'panels' => array('Common.Common')
				),
			)));
		}

		// Route custom prefixes to associated plugin's routing prefixes.
		if (
			Reveal::is('Page.prefixed')
			&& strpos($Event->subject->request->action, $Event->subject->request->prefix) === 0
			&& $prefix = Configure::read($Event->subject->plugin . '.routingPrefixes.' . $Event->subject->request->prefix)
		) {
			$Event->result['view'] = $prefix . substr($Event->subject->request->action, strlen($Event->subject->request->prefix));
			$Event->subject->request->params['action'] = $Event->result['view'];
		}

		// Append new flash messages.
		$Event->result = Hash::merge((array) $Event->result, array('alertMessages' => array(
			'auth.fail' => array(
				'message' => __d('common', "Authentication is required. Please log in to continue."),
				'level' => 'error',
				'redirect' => array('plugin' => 'users', 'controller' => 'users', 'action' => 'login')
			),
			'create.success' => array(
				'message' => __d('common', ":modelName successfully created."),
				'redirect' => array('action' => 'index'),
			),
			'create.fail' => array(
				'message' => __d('common', "There was a problem creating your :modelName, please try again."),
				'level' => 'warning',
				'redirect' => true
			),
			'delete.success' => array(
				'message' => __d('common', ":modelName successfully deleted."),
			),
			'delete.fail' => array(
				'message' => __d('common', "There was a problem deleting your :modelName, please try again."),
				'level' => 'warning',
				'redirect' => true
			),
			'save.success' => array(
				'message' => __d('common', ":modelName successfully updated."),
			),
			'save.fail' => array(
				'message' => __d('common', "There was a problem updating your :modelName, please try again."),
				'level' => 'warning'
			),
			'status.success' => array(
				'message' => __d('common', ":modelName status successfully changed."),
				'redirect' => true,
				'dismiss' => true
			),
			'status.fail' => array(
				'message' => __d('common', "There was a problem changing the :modelName status, please try again."),
				'level' => 'warning',
				'redirect' => true
			),
			'validation' => array(
				'message' => __d('common', "Some data could not be validated. Please, check the error(s) below."),
				'level' => 'error',
			),
			'view.fail' => array(
				'message' => __d('common', "Invalid :modelName, please try again."),
				'level' => 'error',
				'redirect' => true,
			),
		)));
	}

	public function modelConstruct(CakeEvent $Event) {
		$Model = $Event->subject();

		// Because, by default, the SQL is only logged and displayed if debug > 2; using the `DebugKit`
		// new 'autoRun' and/or 'forceEnable' still doesn't help in profiling SQL calls in production.
		// This will force the current model's datasource to log all SQL calls ONLY when in production
		// mode and DebugKit is used with either 'autoRun' or 'forceEnable'.
		if (Reveal::is('DebugKit.running') && !Reveal::is('Page.test')) {
			$Model->getDatasource()->fullDebug = true;
		}

		if (!isset($Model->belongsToForeignModels)) {
			return;
		}

		// Get all foreign models used if not defined by current model.
		if (empty($Model->belongsToForeignModels)) {
			$foreignModels = $Model->find('all', array('fields' => array('DISTINCT' => 'foreign_model'), 'recursive' => -1, 'callbacks' => false));
			foreach ($foreignModels as $foreignModel) {

				// Rarely, some tokens are not associated with any other model.
				if (empty($foreignModel[$Model->alias]['foreign_model'])) {
					continue;
				}

				list($plugin, $name) = pluginSplit($foreignModel[$Model->alias]['foreign_model']);
				$Model->belongsToForeignModels[$name] = array(
					'className' => $foreignModel[$Model->alias]['foreign_model'],
					'foreignKey' => 'foreign_key',
					'conditions' => null,
					'fields' => null,
					'order' => null,
					'counterCache' => false
				);
			}
		}

		// Associate foreign `belongsTo` models.
		$Event->result = Hash::merge((array) $Event->result, array('belongsTo' => array_merge($Model->belongsTo, $Model->belongsToForeignModels)));
	}
}
