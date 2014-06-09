<?php
/**
 * DetailableBehavior
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
 * Detailable behavior
 *
 * @package       Common.Model.Behavior
 */
class DetailableBehavior extends ModelBehavior {

/**
 * {@inheritdoc}
 */
	public $settings = array();

/**
 * Data holder. Used to bypass `Model::validateMany`.
 *
 * @var array
 */
	private $__data = array();

/**
 * {@inheritdoc}
 */
	public function setup(Model $Model, $settings = array()) {
		$defaults = array(
			'class' => 'Common.CommonDetailModel',
			'alias' => $Model->alias . 'Detail',
		);

		$this->settings[$Model->alias] = array_merge($defaults, $settings);
		extract($this->settings[$Model->alias]);


		$DetailModel = ClassRegistry::init(compact('class', 'alias'));

		$belongsTo = array('Parent' => array(
			'className' => $Model->className(),
			'foreignKey' => 'foreign_key',
			'conditions' => array($DetailModel->alias . '.foreign_model' => $Model->className())
		));

		$DetailModel->bindModel(compact('belongsTo'), false);

		foreach (array('schema', 'validate') as $key) {
			$varDetail = 'detail' . ucfirst($key);
			if (!empty($Model->{$varDetail})) {
				$varSection = 'section' . ucfirst($key);
				$DetailModel->{$varSection} = $Model->{$varDetail};
			}
		}
		if (!empty($Model->detailDefaults)) {
			$DetailModel->defaults = $Model->detailDefaults;
		}

		$hasMany = array($DetailModel->alias => array(
			'className' => 'Common.' . $alias,
			'foreignKey' => 'foreign_key',
			'conditions' => array($DetailModel->alias . '.foreign_model' => $Model->className()),
			'order' => array($DetailModel->alias . '.position' => 'ASC')
		));

		$Model->bindModel(compact('hasMany'), false);
	}

/**
 * {@inheritdoc}
 */
	public function afterFind(Model $Model, $results, $primary = false) {
		$DetailModel = ClassRegistry::init($this->settings[$Model->alias]['alias']);

		foreach ($results as $k => $result) {
			if (empty($result[$DetailModel->alias])) {
				continue;
			}

			$results[$k][$DetailModel->alias] = $this->detailsExtractSections($Model, $result[$DetailModel->alias]);
		}

		return $results;
	}

/**
 * {@inheritdoc}
 */
	public function afterValidate(Model $Model) {
		$DetailModel = ClassRegistry::init($this->settings[$Model->alias]['alias']);

		return $this->detailsValidateSections($Model);
	}

/**
 * {@inheritdoc}
 */
	public function afterSave(Model $Model, $created = false, $options = array()) {
		$DetailModel = ClassRegistry::init($this->settings[$Model->alias]['alias']);

		// Reset DetailModel data that was unset before validation.
		if (!empty($this->__data[$Model->alias])) {
			$Model->data[$DetailModel->alias] = $this->__data[$Model->alias];
			unset($this->__data[$Model->alias]);
		}

		if ($created && empty($Model->data[$DetailModel->alias]) && false === $this->detailsCreateDefaults($Model)) {
			return false;
		}

		$this->detailsSaveSections($Model);

		return true;
	}

/**
 * {@inheritdoc}
 */
	public function beforeValidate(Model $Model, $options = array()) {
		$DetailModel = ClassRegistry::init($this->settings[$Model->alias]['alias']);

		// Unset DetailModel data so it is not included in `Model::validateMany()`.
		if (!empty($Model->data[$DetailModel->alias])) {
			$this->__data[$Model->alias] = $Model->data[$DetailModel->alias];
			unset($Model->data[$DetailModel->alias]);
		}

		return true;
	}

/**
 * [detailsCreateDefaults description]
 * @param Model $Model [description]
 * @param [type] $id [description]
 * @return [type]
 */
	public function detailsCreateDefaults(Model $Model, $id = null) {
		if (empty($Model->detailDefaults)) {
			return null;
		}

		$id = empty($id) ? $Model->getID() : $id;
		if (empty($id)) {
			throw new Exception();
		}

		$DetailModel = ClassRegistry::init($this->settings[$Model->alias]['alias']);

		$pos = 1;
		$data = array();
		foreach ($Model->detailDefaults as $record) {
			array_push($data, $record + array('foreign_key' => $Model->id, 'foreign_model' => $Model->className(), 'position' => $pos++));
		}

		if ($DetailModel->saveMany($data, array('validate' => false))) {
			return true;
		}

		return false;
	}

/**
 * Custom method to find all attached details or by section.
 *
 * @param Model $model Model to query.
 * @param string $func
 * @param string $state Either "before" or "after"
 * @param array $query
 * @param array $result
 * @return array
 */
	public function details(Model $Model, $queryData = null, $schema = false, $format = true) {
		$DetailModel = ClassRegistry::init($this->settings[$Model->alias]['alias']);

		if (is_string($queryData)) {
			$queryData = array('section' => $queryData);
		}

		$conditions = array($DetailModel->alias . '.foreign_key' => $Model->getID());
		if (!empty($queryData['section'])) {
			$section = $queryData['section'];
			$conditions[$DetailModel->alias . '.field LIKE'] = $section . '.%';
			unset($queryData['section']);
		}
		if (!empty($queryData['conditions'])) {
			$queryData['conditions'] = Hash::merge($conditions, $queryData['conditions']);
		}

		$fields = array($DetailModel->alias . '.field', $DetailModel->alias . '.value', $DetailModel->alias . '.label', 'foreign_key', 'foreign_model');
		if ($schema) {
			$fields = array($DetailModel->alias . '.field', $DetailModel->alias . '.input', $DetailModel->alias . '.data_type', $DetailModel->alias . '.label');
		}

		$order = array('position' => 'ASC');
		$recursive = -1;

		$results = $DetailModel->find('all', array_merge(compact('conditions', 'fields', 'order', 'recursive'), $queryData));

		return $this->detailsExtractSections($Model, $results, $section, $schema, $format);
	}

/**
 * Extracts model's details data by section(s).
 *
 * @param Model $Model [description]
 * @param [type] $results [description]
 * @param array $sections [description]
 * @param boolean $schema [description]
 * @param boolean $format [description]
 * @return [type]
 */
	public function detailsExtractSections(Model $Model, $results, $sections = array(), $schema = false, $format = true) {
		if (empty($results)) {
			return $results;
		}

		$details = array();
		$sections = (array) $sections;
		$DetailModel = ClassRegistry::init($this->settings[$Model->alias]['alias']);

		foreach ($results as $result) {
			if (isset($result[$DetailModel->alias])) {
				$result = $result[$DetailModel->alias];
			}

			list($section, $field) = explode('.', $result['field']);
			if (!empty($sections) && !in_array($section, $sections)) {
				continue;
			}

			if (!$schema) {
				$details[$section][$field] = $result['value'];
				continue;
			}

			unset($result['id'], $result['field']);
			$defaults = array('type' => 'string', 'length' => 1);
			if (!empty($Model->detailSchema[$section][$field])) {
				$defaults = array_merge($defaults, $Model->detailSchema[$section][$field]);
			}
			$details[$section][$field] = array_merge($defaults, $result);
		}

		return $details;
	}

/**
 * Saves model details data for all (or specific) sections.
 *
 * @param Model $Model [description]
 * @param array $sections [description]
 * @return [type]
 */
	public function detailsSaveSections(Model $Model, $sections = array()) {
		$DetailModel = ClassRegistry::init($this->settings[$Model->alias]['alias']);

		if (empty($Model->data[$DetailModel->alias])) {
			return true;
		} else if (!$this->detailsValidateSections($Model, $sections)) {
			return false;
		}

		if (empty($sections)) {
			$sections = array_keys($Model->data[$DetailModel->alias]);
		}

		foreach ($Model->data[$DetailModel->alias] as $section => $details) {

			if (!in_array($section, $sections)) {
				continue;
			}

			foreach ($details as $key => $val) {
				$field = $section . '.' . $key;
				$data = array(
					'foreign_model' => $Model->className(),
					'foreign_key' => $Model->id,
					'field' => $field,
				);

				$detail = $DetailModel->find('first', array(
					'conditions' => $data,
					'fields' => array('id', 'field'),
					'recursive' => -1
				));

				if (empty($detail)) {
					$DetailModel->create();
					if (!empty($Model->detailsSchema[$section][$key])) {
						$data += $Model->detailsSchema[$section][$key];
					}
				} else if (!empty($detail)) {
					$data[$DetailModel->primaryKey] = $detail[$DetailModel->alias][$DetailModel->primaryKey];
				}

				$data += array('value' => $val);
				if (!$DetailModel->save($data, array('validate' => false, 'bypass' => false))) {
					return false;
				}
			}

		}

		return true;
	}

/**
 * Validates model's details data for all (or specific) sections. Requires the
 * model to have rules defined in `Model::$detailsValidate`.
 *
 * @param Model $Model [description]
 * @param string|array $sections [description]
 * @return [type]
 */
	public function detailsValidateSections(Model $Model, $sections = array()) {
		$DetailModel = ClassRegistry::init($this->settings[$Model->alias]['alias']);

		if (empty($Model->detailsValidate)) {
			return true;
		} else if (empty($Model->data[$DetailModel->alias])) {
			return false;
		} else if (empty($sections)) {
			$sections = array_keys($Model->detailsValidate);
		}

		$validationErrors = array();

		foreach ((array) $sections as $section) {
			if (empty($Model->data[$DetailModel->alias][$section])) {
				continue;
			}

			$DetailModel->validationErrors = array();
			$DetailModel->set($Model->data[$DetailModel->alias][$section]);
			$DetailModel->validate = $Model->detailsValidate[$section];
			if (!$DetailModel->validates()) {
				$validationErrors[$section] = $DetailModel->validationErrors;
			}

		}

		$DetailModel->validationErrors = $validationErrors;

		return empty($DetailModel->validationErrors);
	}

}
