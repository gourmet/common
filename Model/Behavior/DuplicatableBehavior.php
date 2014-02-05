<?php
/**
 * DuplicatableBehavior
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

App::uses('ModelBehavior', 'Model');

/**
 * Duplicatable behavior
 *
 * @package       Common.Model.Behavior
 */
class DuplicatableBehavior extends ModelBehavior {

/**
 * Defaults.
 *
 * @var array
 */
	protected $_defaults = array(
		'duplicatedKey' => 'duplicated_key',
		'duplicatedModel' => 'duplicated_model',
		'mapFields' => array(),
		'model' => null,
		'scope' => null
	);

/**
 * {@inheritdoc}
 */
	public function setup(Model $Model, $config = array()) {
		if (isset($config[0])) {
			$config['model'] = $config[0];
			unset($config[0]);
		}

		if (empty($config['model'])) {
			throw new Exception(__d('common', "Missing duplicate table for '%s'", $Model->name));
		}

		$this->settings[$Model->alias] = array_merge($this->_defaults, $config);
	}

/**
 * {@inheritdoc}
 */
	public function afterSave(Model $Model, $created) {
		$this->duplicate($Model, $Model->data);
		return;
	}

/**
 * Clone model's data.
 *
 * @param Model $Model
 * @param array $options
 * @return [type]
 */
	public function duplicate(Model $Model, $data, $config = array()) {
		$config = array_merge($this->settings[$Model->alias], $config);

		if (empty($Model->id) && empty($data[$Model->alias][$Model->primaryKey])) {
			throw new Exception(__d('common', "Missing primary key for duplicatable '%s' data", $Model->name));
		}

		$id = $Model->id ? $Model->id : $data[$Model->alias][$Model->primaryKey];
		$conditions = array($Model->primaryKey => $id) + (array) $this->settings[$Model->alias]['scope'];

		if (
			!empty($this->settings[$Model->alias]['scope'])
			&& !$Model->find('count', compact('conditions'))
		) {
			return false;
		}

		$duplicateData = array(
			$config['duplicatedKey'] => $id,
			$config['duplicatedModel'] => (!empty($Model->plugin) ? $Model->plugin . '.' : '') . $Model->name
		);

		$DuplicateModel = ClassRegistry::init($config['model']);
		$DuplicateModel->create();

		$duplicateRecord = $DuplicateModel->find('first', array('conditions' => $duplicateData, 'recursive' => -1));
		if (!empty($duplicateRecord)) {
			$DuplicateModel->id = $duplicateRecord[$DuplicateModel->alias][$DuplicateModel->primaryKey];
		}

		foreach ((array) $config['mapFields'] as $field => $path) {
			$value = Hash::extract($data, $path);
			$duplicateData[$field] = array_pop($value);
			if (
				!empty($duplicateRecord[$DuplicateModel->alias][$field])
				&& $duplicateRecord[$DuplicateModel->alias][$field] === $duplicateData[$field]
			) {
				unset($duplicateData[$field]);
			}
		}

		if (
			(empty($duplicateRecord) || 1 < count($duplicateData))
			&& (!empty($DuplicateModel->id) || $DuplicateModel->create($duplicateData))
			&& !$DuplicateModel->save()
		) {
			return false;
		}

		return true;
	}

}
