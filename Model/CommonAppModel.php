<?php
/**
 * CommonAppModel
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

App::uses('Model', 'Model');

/**
 * Common application's model
 *
 * This file needs to be extended by `AppModel` in order to take advantage
 * of its built-in features.
 *
 * @package       Common.Model
 */
class CommonAppModel extends Model {

	public $friendly = null;

/**
 * Overrides `Model::__construct()`.
 *
 * - Auto-load plugin behaviors using the 'Model.construct' event.
 *
 * @param integer|string|array $id Set this ID for this model on startup, can also be an array of options, see above.
 * @param string $table Name of database table to use.
 * @param string $ds DataSource connection name.
 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);

		foreach (get_class_methods($this) as $method) {
			$key = lcfirst(str_replace('_find', '', $method));
			if (isset($this->findMethods[$key]) || strpos($method, '_find') !== 0) {
				continue;
			}

			$ReflectionMethod = new ReflectionMethod($this, $method);
			$params = $ReflectionMethod->getParameters();

			foreach (array('state', 'query', 'results') as $k => $name) {
				if ((array) $params[$k] != compact('name')) {
					$key = null;
					break;
				}
			}

			if (!empty($key)) {
				$this->findMethods[$key] = true;
			}
		}

		if (empty($this->friendly)) {
			$this->friendly = Inflector::humanize(Inflector::underscore($this->name));
		}

		$timer = 'modelConstruct' . $this->alias;
		Common::startTimer($timer, __d('common', "Event: Model.construct (%s)", $this->alias));
		$result = array_merge(array('actsAs' => array()), (array) $this->triggerEvent('Model.construct', $this));

		$this->actsAs = Hash::merge(
			Hash::normalize((array) $result['actsAs']),
			Hash::normalize((array) $this->actsAs)
		);

		unset($result['actsAs']);

		foreach ($this->actsAs as $name => $config) {
			list(, $behavior) = pluginSplit($name);
			if (!$this->Behaviors->loaded($behavior)) {
				$this->Behaviors->load($name, $config);
			}
		}

		$this->_set($result);
		Common::stopTimer($timer);
	}

/**
 * {@inheritdoc}
 */
	public function afterFind($results, $primary = false) {
		if (empty($this->belongsToForeignModels)) {
			return $results;
		}

		// Apply filter to result like:
		// array('model' => 'ForeignModel', 'ForeignModel' => array(..), 'AnotherForeignModel' => array(..))
		if (!empty($results['foreign_model'])) {
			return $this->_filterForeignModels($results, $results['foreign_model']);
		}

		// Skip if result not like array(0 => array(..), 1 => array(..), ...)
		if (!Hash::numeric(array_keys($results))) {
			return $results;
		}

		foreach ($results as $k => $result) {

			// Apply filter to result like:
			// array('Affiliate' => array('model' => 'ForeignModel', 'ForeignModel' => array(..), 'AnotherForeignModel' => array(..)))
			if (!empty($result[$this->alias]) && !empty($result[$this->alias]['foreign_model'])) {
				$results[$k] = $this->_filterForeignModels($result, $result[$this->alias]['foreign_model']);
			}
		}

		return $results;
	}

/**
 * [className description]
 * @param boolean $plugin [description]
 * @return [type]
 */
	public function className($plugin = true) {
		return (($plugin && !empty($this->plugin)) ? $this->plugin . '.' : '') . $this->name;
	}

/**
 * Common edit operation.
 *
 * @param string $id Model record's primary key value.
 * @param array $data New model record's data.
 * @return boolean
 * @throws OutOfBoundsException If the record does not exist.
 */
	public function edit($id, $data) {
		$record = $this->read(null, $id);
		if (!$record) {
			throw new OutOfBoundsException('view.fail');
		}

		if (empty($data)) {
			return;
		}

		$data[$this->alias] = array_merge(
			array_diff_key($data[$this->alias], $record[$this->alias]),
			array_diff($data[$this->alias], $record[$this->alias])
		);

		$validate = $this->validate;
		foreach (array_keys($this->validate) as $field) {
			if (!isset($data[$this->alias][$field])) {
				unset($this->validate[$field]);
			}
		}

		$this->id = $id;
		$this->data = $data;

		$result = $this->save(null, false);

		$this->validate = $validate;
		$this->data = Hash::merge($record, $result);

		if (!$result) {
			return false;
		}

		$this->triggerEvent('Model.' . $this->name . '.afterEdit', $this);
		return $data;
	}

/**
 * {@inheritdoc}
 */
	public function find($type = 'first', $query = array()) {
		if (!empty($this->belongsToForeignModels)) {
			// Add contained foreign models prior to calling the `ContainableBehavior::beforeFind()`.
			$query = $this->_containForeignModels($query);
		}

		return parent::find($type, $query);
	}

/**
 * Overrides `Model::getEventManager()`.
 *
 * - Use `CommonEventManager` instead of `CakeEventManager`.
 * - Auto-load the 'Model' and this model's specific events (using scopes).
 *
 * @return CommonEventManager
 */
	public function getEventManager() {
		if (empty($this->_eventManager)) {
			$this->_eventManager = new CommonEventManager();
			$this->_eventManager->loadListeners($this->_eventManager, 'Model');
			$this->_eventManager->loadListeners($this->_eventManager, $this->name);
			$this->_eventManager->attach($this->Behaviors);
			$this->_eventManager->attach($this);
		}
		return $this->_eventManager;
	}

/**
 * Gets the foreign model's display field. Useful in views.
 *
 * @param string $foreignModel Model's name (i.e. 'Model' or 'Plugin.Model').
 * @param boolean $path Return the array's path to the display field in the data
 *   array or just the display field.
 * @return string
 */
	public static function getForeignModelDisplayField($model, $foreignModel, $path = false) {
		$_this = ClassRegistry::init($model);
		$displayField = $_this->displayField;

		if (is_bool($foreignModel)) {
			$path = $foreignModel;
			list(, $name) = pluginSplit($model);
		} else {
			list(, $name) = pluginSplit($foreignModel);
			foreach (array_keys($_this->belongsToForeignModels) as $model) {
				if ($name == $model) {
					$displayField = $_this->{$model}->displayField;
					break;
				}
			}
		}

		if (true === $path || (false !== $path && empty($path))) {
			$displayField = "$name.$displayField";
		} else if (false !== $path) {
			$displayField = "$path.$name.$displayField";
		}

		return $displayField;
	}

/**
 * Overrides Object::log(). Used also in CommonAppController, CommonAppHelper.
 *
 * - Support for Cake's own `scopes` (at the time I wrote this, Object::log() does not).
 * - Defaults all logs to the default scope for less writing ;)
 *
 * @param string $msg Log message.
 * @param integer $type Error type constant. Defined in app/Config/core.php.
 * @return boolean Success of log write.
 */
	public function log($msg, $type = LOG_ERR, $scopes = array('default')) {
		App::uses('CakeLog', 'Log');
		if (!is_string($msg)) {
			$msg = print_r($msg, true);
		}

		$scopes = (array) $scopes;
		if (!in_array('model', $scopes)) {
			$scopes[] = 'model';
		}

		return Common::getLog()->write($type, $msg, $scopes);
	}

/**
 * Extends `Model::saveAssociated()` to add support for saving HABTM data.
 *
 * @param array $data Record data to save. This should be an array indexed by association name.
 * @param array $options Options to use when saving record data, See $options above.
 * @return mixed If atomic: True on success, or false on failure.
 *    Otherwise: array similar to the $data array passed, but values are set to true/false
 *    depending on whether each record saved successfully.
 * @link http://book.cakephp.org/2.0/en/models/saving-your-data.html#model-saveassociated-array-data-null-array-options-array
 */
	public function saveAssociated($data = null, $options = array()) {
		foreach ($data as $alias => $record) {
			if (!empty($this->hasAndBelongsToMany[$alias])) {
				$habtm = array();
				$Model = ClassRegistry::init($this->hasAndBelongsToMany[$alias]['className']);
				foreach ($record as $recordData) {
					if (empty($recordData['id'])) {
						$Model->create();
					}
					$Model->save($recordData);
					$habtm[] = empty($recordData['id']) ? $Model->getInsertID() : $recordData['id'];
				}
				$data[$alias] = array($alias => $habtm);
			}
		}

		return parent::saveAssociated($data, $options);
	}

/**
 * Trigger an event using the 'Model' instead of the `CommonEventManager`.
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
 * Adds the foreign models to the query's `contain`.
 *
 * @param array $query Find query.
 * @return array Modified find query.
 */
	protected function _containForeignModels($query, $assoc = null) {
		if (
			(isset($query['contain']) && empty($query['contain']))
			|| (isset($this->contain) && empty($this->contain))
		) {
			return $query;
		}

		if (!isset($query['contain'])) {
			$query['contain'] = array();
		} else if (!is_array($query['contain'])) {
			$query['contain'] = (array) $query['contain'];
		}

		// Add the foreign models.
		foreach (array_keys($this->belongsToForeignModels) as $model) {
			if (!empty($assoc)) {
				$model = "$assoc.$model";
			}
			if (!in_array($model, $query['contain']) && !isset($query['contain'][$model])) {
				$query['contain'][] = $model;
			}
		}

		return $query;
	}

/**
 * Loops through associated `belongsTo` foreign models to keep only the associated one.
 *
 * @param array $result Original resultset containing all associated `belongsTo` foreign models.
 * @param string $keepModel Full name of the associated model to keep (i.e. 'Plugin.Model' or 'Model').
 * @return array Filtered resultset.
 */
	protected function _filterForeignModels($result, $keep) {
		foreach ($this->belongsToForeignModels as $name => $assoc) {
			if ($keep === $assoc['className']) {
				continue;
			}

			if (isset($result[$name])) {
				unset($result[$name]);
			}
		}

		return $result;
	}

}
