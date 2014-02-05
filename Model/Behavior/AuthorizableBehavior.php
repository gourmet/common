<?php
/**
 * AuthorizableBehavior
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

App::uses('Behavior', 'Model/ModelBehavior');
App::uses('AuthComponent', 'Controller/Component');

/**
 * Authorizable behavior
 *
 * @package       Common.Model.Behavior
 */
class AuthorizableBehavior extends ModelBehavior {

/**
 * {@inheritdoc}
 */
	public $settings = array();

/**
 * Defaults.
 *
 * @var array
 */
	protected $_defaults = array(
		'owner' => array(
			'allow' => true,
			'perms' => 'CRUD',
			'path' => 'user_id',
			'auth' => 'id'
		),

		'groups' => array(
			'allow' => false,
			'perms' => 'CRU',
			'path' => 'Group.name',
			'auth' => 'Group.name'
		),

		'collaborators' => array(
			'allow' => false,
			'perms' => 'CRU',
			'path' => 'User.{n}.id',
		),

		'admin' => array(
			'allow' => true,
			'perms' => 'CRUD',
			'path' => '[prefix=admin]'
		),

		'perms' => array('create', 'read', 'update', 'delete')
	);

/**
 * Authenticated user, if any.
 *
 * @var array
 */
	protected $_user = null;

/**
 * {@inheritdoc}
 */
	public function setup(Model $Model, $config = array()) {
		if (isset($this->settings[$Model->alias])) {
			return;
		}

		if (isset($config[0])) {
			$config['owner']['path'] = $config[0];
			unset($config[0]);
		}

		foreach (array_keys($config) as $key) {
			if ('perms' == $key) {
				continue;
			}

			if (isset($config[$key][0])) {
				$config[$key]['path'] = $config[$key][0];
				unset($config[$key][0]);
			}

			if (!empty($config[$key]['path'])) {
				$config[$key]['allow'] = true;
				if (empty($config[$key]['perms']) && empty($this->_defaults[$key]['perms'])) {
					$config[$key]['perms'] = 'CRUD';
				}
			}
		}

		$config = Hash::merge($this->_defaults, $config);

		if (!empty($config['owner']['allow'])) {
			if (empty($config['owner']['path'])) {
				throw new FatalErrorException(__d(
					'common',
					"Missing the 'path' for the 'owner' authorization rule on '%s'",
					$Model->name
				));
			}

			if (!$Model->hasField($config['owner']['path'])) {
				throw new FatalErrorException(__d(
					'common',
					"Missing owner's field '%s' in table '%s'",
					$config['owner']['path'],
					$Model->useTable
				));
			}
		}

		if (!empty($config['groups']['allow'])) {
			if (empty($config['groups']['path'])) {
				throw new FatalErrorException(__d(
					'common',
					"Missing the 'path' for the 'groups' authorization rule on '%s'",
					$Model->name
				));
			}

			$assocType = 'belongsTo';
			if (strpos($config['groups']['path'], '{n}') != 0) {
				$assocType = 'hasAndBelongsToMany';
			}

			$groupModel = current(explode('.', $config['groups']['path']));

			if (!array_key_exists($groupModel, $Model->{$assocType})) {
				throw new FatalErrorException(__d(
					'common',
					"Missing the '%s' %s association required for '%s' authorization",
					$groupModel,
					'belongsTo' == $assocType ? 'belongsTo' : 'HABTM',
					$Model->name
				));
			}

			if (true === $config['groups']['allow']) {
				$config['groups']['allow'] = array();
			}
		}

		if (!empty($config['collaborators']['allow'])) {
			if (empty($config['collaborators']['path'])) {
				throw new FatalErrorException(__d(
					'common',
					"Missing the 'path' for the 'collaborators' authorization rule on '%s'",
					$Model->name
				));
			}

			if (strpos($config['collaborators']['path'], '{n}') === false) {
				throw new FatalErrorException(__d(
					'common',
					"Incorrect 'collaborators' path definition in '%s' authorization rules.",
					$Model->name
				));
			}

			$collaboratorModel = current(explode('.', $config['collaborators']['path']));

			if (!array_key_exists($collaboratorModel, $Model->hasAndBelongsToMany)) {
				throw new FatalErrorException(__d(
					'common',
					"Missing the '%s' HABTM association required for '%s' authorization",
					$collaboratorModel,
					$Model->name
				));
			}

			if (true === $config['collaborators']['allow']) {
				$config['collaborators']['allow'] = array();
			}
		}

		$this->settings[$Model->alias] = $config;

		// Set permissions per auth type.
		foreach (array_keys($this->settings[$Model->alias]) as $key) {
			if ('perms' == $key || false === $this->settings[$Model->alias][$key]['allow']) {
				continue;
			}

			foreach ($this->settings[$Model->alias]['perms'] as $action) {
				$this->settings[$Model->alias][$key][$action] = false;
				if (strpos(strtolower($config[$key]['perms']), $action[0]) !== false) {
					$this->settings[$Model->alias][$key][$action] = true;
				}
			}
		}
	}

/**
 * [__call description]
 *
 * @param string $method Name of the method called (i.e. 'isSomeRole').
 * @param array $args Parameters passed to the called method.
 * @return boolean
 * @throws FatalErrorException
 */
	public function __call($method, $args = array()) {
		$Model = current($args);

		$role = Inflector::underscore(substr($method, 2));

		if (
			!isset($this->settings[$Model->alias][$role])
			|| false === $this->settings[$Model->alias][$role]['allow']
		) {
			throw new FatalErrorException(__d('common', "Method '%s' does not exist.", $method));
		}

		$user = array('User' => $this->getCurrentUser($Model));
		$path = $this->settings[$Model->alias][$role]['path'];

		$glue = '';
		if (strpos($path, '[') !== 0) {
			$glue = '.';
		}

		$path = implode($glue, array('User', $path));

		if (empty($user['User'])) {
			return true;
		}
		return Hash::check($user, $path);
	}

/**
 * {@inheritdoc}
 */
	public function afterFind(Model $Model, $results, $primary) {
		$this->_cntSkippedAuthorizable($Model);
	}

/**
 * {@inheritdoc}
 */
	public function afterSave(Model $Model, $created) {
		$this->_cntSkippedAuthorizable($Model);
	}

/**
 * {@inheritdoc}
 */
	public function afterValidate(Model $Model) {
		$this->_cntSkippedAuthorizable($Model);
	}

/**
 * {@inheritdoc}
 */
	public function beforeDelete(Model $Model, $cascade = true) {
		if ($this->isAllowed($Model, 'delete')) {
			return true;
		}

		if (method_exists($Model, 'privateBlackhole')) {
			$Model->privateBlackhole();
		}

		return false;
	}

/**
 * {@inheritdoc}
 */
	public function beforeFind(Model $Model, $query) {
		if (false === $Model->requireAuth || $this->isCustomRole($Model, 'read')) {
			return true;
		}

		extract($this->settings[$Model->alias]);

		$currentGroup = $this->getCurrentUser($Model, $groups['auth']);
		$currentUser = $this->getCurrentUser($Model, $owner['auth']);

		$append = array();

		if (
			$owner['allow'] && $owner['read']
			&& empty($query['conditions'][$Model->alias . '.' . $owner['path']])
			&& empty($query['conditions'][$owner['path']])
		) {
			$append[$Model->alias . '.' . $owner['path']] = $currentUser;
		}

		if (
			false !== $groups['allow'] && $groups['read']
			&& empty($query['conditions'][$groups['path']])
		) {
			if (strpos($groups['path'], '{n}') === false) {
				$field = $Model->alias . '.' . $Model->belongsTo[current(explode('.', $groups['path']))]['foreignKey'];
				$append[$field] = $currentGroup;
			} else {
				$field = str_replace('{n}.', '', $groups['path']);
				$habtmModel = current(explode('.', $groups['path']));
				$habtmAssoc = $Model->hasAndBelongsToMany[$habtmModel];
				$habtmAlias = Inflector::classify($habtmAssoc['joinTable']);

				$query['joins'] = array(
					array(
						'table' => $habtmAssoc['joinTable'],
						'alias' => $habtmAlias,
						'type' => 'INNER',
						'conditions' => array(
							$habtmAlias . '.' . $habtmAssoc['foreignKey'] . ' = ' . $Model->alias . '.' . $Model->primaryKey
						)
					),
					array(
						'table' => $Model->{$habtmModel}->useTable,
						'alias' => $habtmModel,
						'type' => 'INNER',
						'conditions' => array(
							$habtmModel . '.' . $Model->{$habtmModel}->primaryKey . ' = ' . $habtmAlias . '.' . $habtmAssoc['associationForeignKey'],
							$field . ' IN("' .  implode('", "', $currentGroup) . '")'
						)
					)
				);
				return $query;
			}
		}

		if (empty($append)) {
			return $query;
		}

		if (empty($query['conditions'])) {
			$query['conditions'] = array('OR' => $append);
		} else {
			$query['conditions'] = array('AND' => array($query['conditions'], 'OR' => $append));
		}

		return $query;
	}

/**
 * {@inheritdoc}
 */
	public function beforeValidate(Model $Model) {
		return $this->beforeSave($Model);
	}

/**
 * {@inheritdoc}
 */
	public function beforeSave(Model $Model) {
		$action = $Model->getID() ? 'update' : 'create';
		$field = $this->settings[$Model->alias]['owner']['path'];

		if (
			('create' == $action && !empty($Model->data[$Model->alias][$field]))
			|| $this->isAllowed($Model, $action)
		) {
			return true;
		}

		return false;
	}

/**
 * [isAllowed description]
 *
 * @param Model $Model Model using this behavior.
 * @return boolean
 */
	public function isAllowed(Model $Model, $action = 'create') {
		if (false === $Model->requireAuth) {
			return true;
		}

		extract($this->settings[$Model->alias]);

		if (!in_array($action, $perms)) {
			throw new FatalErrorException(__d('common', "No '%s' action defined for controlling '%s' authorization", $action, $Model->name));
		}

		$currentGroup = $this->getCurrentUser($Model, $groups['auth']);
		$currentUser = $this->getCurrentUser($Model, $owner['auth']);

		$current = $Model->getID();

		if ($this->isCustomRole($Model, $action)) {
			return true;
		}

		if (!$Model->exists()) {
			if (
				in_array($action, array('create', 'update'))
				&& empty($Model->data[$Model->alias][$owner['path']])
			) {
				$Model->data[$Model->alias][$owner['path']] = $currentUser;
			}
			return true;
		}

		if ($owner[$action] && $this->isOwner($Model)) {
			return true;
		}

		$find = false;
		$unload = false;

		if (
			empty($Model->activeRecord[$Model->alias][$Model->primaryKey])
			|| $current != $Model->activeRecord[$Model->alias][$Model->primaryKey]
		) {
			$Model->activeRecord = null;
		}

		if (empty($Model->activeRecord)) {
			$find = true;
		} else {
			foreach (array('groups', 'collaborators') as $key) {
				if (false !== ${$key}['allow'] && ${$key}[$action] && !Hash::check($Model->activeRecord, ${$key}['path'])) {
					$find = true;
					break;
				}
			}
		}

		if ($find && !$Model->Behaviors->loaded('Containable')) {
			$Model->Behaviors->load('Containable');
			$unload = true;
		}

		if ($find) {
			$contain = array();
			foreach (array('groups', 'collaborators') as $key) {
				if (false !== ${$key}['allow'] && ${$key}[$action]) {
					$contain[] = str_replace('{n}.', '', ${$key}['path']);
				}
			}

			$Model->requireAuth = false;
			$Model->activeRecord = Hash::merge((array) $Model->activeRecord, $Model->find('first', array(
				'conditions' => array($Model->alias . '.' . $Model->primaryKey => $current),
				'fields' => $Model->alias . '.' . $owner['path'],
				'contain' => $contain,
			)));
			$Model->requireAuth = true;
		}

		if ($unload) {
			$Model->Behaviors->unload('Containable');
		}

		foreach (array('groups', 'collaborators') as $key) {
			if (false !== ${$key}['allow'] && $extract = Hash::extract($Model->activeRecord, ${$key}['path'])) {
				${$key}['allow'] = array_merge(${$key}['allow'], $extract);
			}
		}

		return (
			(!empty($collaborators[$action]) && in_array($currentUser, (array) $collaborators['allow']))
			|| (!empty($groups[$action]) && array_intersect((array) $groups['allow'], (array) $currentGroup))
		);
	}

/**
 * The `AuthComponent::user()` being impossible to test in models
 * and because the `AuthorizableBehavior` heavily relies on that to make
 * controllers even slimmer and required to pass less parameters to
 * the models, this method allows to retrieve the currently logged
 * in user from `AppModel::$__currentUser`, which, during tests, can
 * be set using the `AppModel::setCurrentUser()` method.
 *
 * @param Model $Model Model using this behavior.
 * @param string $key
 * @return mixed
 */
	public function getCurrentUser(Model $Model, $key = null) {
		if (method_exists($Model, 'getCurrentUser')) {
			return $Model->getCurrentUser($key);
		}

		if (empty($this->_user)) {
			$this->_user = $this->setCurrentUser($Model);
		}

		if (empty($key) || !Hash::check((array) $this->_user, $key)) {
			return $this->_user;
		}

		if (!strpos($key, '{n}')) {
			return Hash::get($this->_user, $key);
		}

		$result = Hash::extract($this->_user, $key);
		if (count($result) < 2) {
			return current($result);
		}

		return $result;
	}

/**
 * [isCustomRole description]
 *
 * @param Model $Model Model using this behavior.
 * @param string $action
 * @return boolean
 */
	public function isCustomRole(Model $Model, $action = 'create') {
		foreach (array_keys($this->settings[$Model->alias]) as $role) {
			$method = "is" . Inflector::classify($role);
			if (
				!in_array($role, array('owner', 'groups', 'collaborators', 'perms'))
				&& $this->settings[$Model->alias][$role]['allow']
				&& $this->settings[$Model->alias][$role][$action]
				&& $this->{$method}($Model)
			) {
				return true;
			}
		}
		return false;
	}

/**
 * [isOwner description]
 *
 * @param Model $Model Model using this behavior.
 * @return boolean
 */
	public function isOwner(Model $Model) {
		if (!$Model->exists()) {
			return false;
		}

		if (!$owner = Hash::get($Model->data, $Model->alias . '.' . $this->settings[$Model->alias]['owner']['path'])) {
			$requireAuth = $Model->requireAuth;
			$Model->requireAuth = false;
			$owner = $Model->field(
				$this->settings[$Model->alias]['owner']['path'],
				array($Model->alias . '.' . $Model->primaryKey => $Model->getID())
			);
			$Model->requireAuth = $requireAuth;
		}

		return $this->getCurrentUser($Model, $this->settings[$Model->alias]['owner']['auth']) == $owner;
	}

/**
 * Set the `PrivateBehavior::$__user`. Useful in tests to inject logged
 * user dependency.
 *
 * @param Model $Model Model using this behavior.
 * @param array $user
 * @return void
 */
	public function setCurrentUser(Model $Model, $user = null) {
		if (!empty($user)) {
			return $this->_user = $user;
		}

		$this->_user = AuthComponent::user();

		if (empty($this->_user) && !empty($Model->id)) {
			$findMethod = 'findBy' . Inflector::classify($Model->primaryKey);
			$this->_user = $Model->{$findMethod}($Model->id);
		}

		return $this->_user;
	}

	public function skipAuthorizable(Model $Model, $cnt = 1) {
		if (!$cnt) {
			$Model->requireAuth = false;
			$this->_initialState[$Model->alias] = array();
			return;
		}

		$requireAuth = !isset($Model->requireAuth) || $Model->requireAuth;
		if (!$requireAuth) {
			return;
		}

		$this->_initialState[$Model->alias] = compact('cnt', 'requireAuth');

		$Model->requireAuth = false;
	}

	protected function _cntSkippedAuthorizable(Model $Model) {
		if (empty($this->_initialState[$Model->alias])) {
			return;
		}

		$this->_initialState[$Model->alias]['cnt']--;

		if (!$this->_initialState[$Model->alias]['cnt']) {
			$Model->requireAuth = true;
			unset($this->_initialState[$Model->alias]);
		}

	}

}
