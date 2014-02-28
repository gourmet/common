<?php

App::uses('AppModel', 'Model');

/**
 * DetailModel.
 *
 * @package App.Model
 */
class CommonDetailModel extends AppModel {

	public $defaults = array();

	public $displayField = 'field';

/**
 * Name of the model this "CommonDetailModel" belongs to.
 *
 * @var string
 */
  public $parentModel = null;

/**
 * Data schema by section.
 *
 * @var array
 */
  public $sectionSchema = array();

/**
 * Validation rules by section.
 *
 * @var array
 */
  public $sectionValidate = array();

/**
 * Don't use table.
 *
 * @var boolean
 */
  public $useTable = 'common_details';

/**
 * Constructor.
 *
 * Guesses the primary "AppModel" to which this "DetailModel" is associated.
 *
 * @param mixed $id Set this ID for this model on startup, can also be an array of options, see above.
 * @param string $table Name of database table to use.
 * @param string $ds DataSource connection name.
 * @todo Check if there isn't a better way (maybe from `Model::$belongsTo`?).
 */
  public function __construct($id = false, $table = null, $ds = null) {
    parent::__construct($id, $table, $ds);
    if (empty($this->parentModel)) {
	    $this->parentModel = str_replace('Detail', '', $this->alias);
	  }

	 //  if (!$this->getAssociated($this->parentModel)) {
		//   $this->bindModel(array('belongsTo' => array($this->parentModel => array('foreignKey' => 'parent_id'))));
		// }
  }

/**
 * Creates default "details" entries for associated model's record.
 *
 * @param integer|string $assocId Associated record ID.
 * @return void
 */
  public function createDefaults($assocId) {
    $i = 1;
    $data = array();

    $foreignModel = current(Hash::extract($this->getAssociated('Parent'), 'conditions'));

    foreach ($this->defaults as $entry) {
      $data[$this->alias] = $entry;
      $data[$this->alias]['foreign_key'] = $assocId;
      $data[$this->alias]['foreign_model'] = $foreignModel;
      $data[$this->alias]['position'] = $i++;
      $this->create();
      $this->save($data);
    }
  }

/**
 * Extracts given section(s) from resultset.
 *
 * @param array $results Details.
 * @param array|string $sections Required section(s). Leave empty for all sections.
 * @param boolean $schema TRUE to return the schema for every section's field.
 * @return array Extracted section(s) details.
 */
  public function extractSection($results, $sections = array(), $schema = false) {
    if (!empty($results)) {
      $sections = (array) $sections;
      foreach ($results as $result) {
        if (isset($result[$this->alias])) {
          $result = $result[$this->alias];
        }
        list($section, $field) = explode('.', $result['field']);
        if (!empty($sections) && !in_array($section, $sections)) {
          continue;
        }
        if (true === $schema) {
          unset($result['id'], $result['field']);
          $defaults = array('type' => 'string', 'length' => 1);
          if (isset($this->sectionSchema[$section]) && isset($this->sectionSchema[$section][$field])) {
            $defaults = array_merge($defaults, $this->sectionSchema[$section][$field]);
          }
          $details[$section][$field] = array_merge($defaults, $result);
        } else {
          $details[$section][$field] = $result['value'];
        }
      }
      $results = $details;
    }

    return $results;
  }

/**
 * Gets detailed model record's associated details by section.
 *
 *    Example:
 *
 *    $result = array(
 *      'address' => array('street-number' => '4820', 'street-name' => 'St Marc')
 *      'interior' => array('bathroom' => 3, 'bedroom' => 5, 'kitchen' => 1, 'other' => '')
 *    );
 *
 * @param integer|string $id Associated record ID.
 * @param string $section Name of section.
 * @param boolean $schema TRUE to return the section's schema.
 * @param boolean $format FALSE if you don't want to format the resultset.
 * @return array
 */
  public function getSection($id, $section = null, $schema = false, $format = true) {
    $foreignKey = $this->belongsTo[$this->parentModel]['foreignKey'];
    $conditions = array("{$this->alias}.{$foreignKey}" => $id);
    if (!is_null($section)) {
      $conditions["{$this->alias}.field LIKE"] = "$section.%";
    }

    $fields = array("{$this->alias}.field", "{$this->alias}.value");
    if (true === $schema) {
      $fields = array("{$this->alias}.field", "{$this->alias}.input", "{$this->alias}.data_type", "{$this->alias}.label");
    }

    $results = $this->find(
        'all', array(
          'conditions' => $conditions,
          'fields' => $fields,
          'order' => array("{$this->alias}.position ASC"),
          'recursive' => -1
        )
    );

    return $this->extractSection($results, $section, $schema, $format);
  }

/**
 * By-pass the save operation by default. This is a workaround so only the `DetailableBehavior`
 * can save records after settings the required fields.
 *
 * @param array $data Data to save.
 * @param boolean|array $validate Either a boolean, or an array.
 *   If a boolean, indicates whether or not to validate before saving.
 *   If an array, can have following keys:
 *
 *   - validate: Set to true/false to enable or disable validation.
 *   - fieldList: An array of fields you want to allow for saving.
 *   - callbacks: Set to false to disable callbacks. Using 'before' or 'after'
 *      will enable only those callbacks.
 *   - bypass: Set to false to force the save operation.
 *
 * @param array $fieldList List of fields to allow to be saved
 * @return mixed On success Model::$data if its not empty or true, false on failure
 */
  public function save($data = null, $validate = true, $fieldList = array()) {
    if (!is_array($validate)) {
      $validate = compact('validate', 'fieldList');
    }

    $defaults = array('validate' => true, 'fieldList' => $fieldList, 'callbacks' => true, 'bypass' => true);
    $options = array_merge($defaults, $validate);
    if ($options['bypass']) {
      return true;
    }

    return parent::save($data, $options);
  }
  
/**
 * Saves associated details. Validates data only if the data is saved by section (not all sections together).
 *
 *    Usage:
 *    ------
 *
 *    $data = array('PropertyDetail' => array('street-number' => '4820', 'street-name' => 'St Marc'));
 *    DetailModel::save('uuid-of-record', $data, 'address'); // section name is passed.
 *
 *    $data = array('PropertyDetail' => array('address.street-number' => '4820', 'address.street-name' => 'St Marc'));
 *    DetailModel::save('uuid-of-record', $data); // section name is not passed.
 *
 * @param integer|string $id Associated record ID.
 * @param array $data Record data.
 * @param string $section Name of the section.
 * @throws RuntimeException On validation errors.
 * @return boolean
 * @todo Make it validate even if more than one section is being saved at once.
 */
  public function saveSection($id, $data = array(), $section = null) {
    $foreignKey = $this->belongsTo[$this->parentModel]['foreignKey'];
    if (!empty($this->sectionSchema[$section])) {
      $modelSchema = $this->_schema;
      $this->_schema = $this->sectionSchema[$section];
      foreach ($data as $model => $details) {
        if ($model != $this->alias) {
          continue;
        }
        foreach ($details as $key => $value) {
          $data[$model][$key] = $this->deconstruct($key, $value);
        }
      }
    }

    if (!$this->validateSection($data, $section)) {
      throw new RuntimeException(__d('alert', "Some data could not be validated. Please, check the error(s) below."));
    }

    if (isset($modelSchema)) {
      $this->_schema = $modelSchema;
    }

    if (empty($data) || !is_array($data)) {
      return false;
    }

    foreach ($data as $model => $details) {
      if ($model == $this->alias) {
        foreach ($details as $key => $value) {
          $newDetail = array();
          $field = $key;
          if (!is_null($section)) {
            $field = "$section.$field";
          }
          $detail = $this->find(
              'first', array(
                'conditions' => array(
                  "{$this->alias}.{$foreignKey}" => $id,
                  "{$this->alias}.field" => $field
                ),
                'fields' => array('id', 'field'),
                'recursive' => -1
              )
          );

          if (empty($detail)) {
            $this->create();
            $newDetail[$model][$foreignKey]   = $id;
            $newDetail[$model]['input']     = '';
            $newDetail[$model]['data_type'] = '';
            $newDetail[$model]['label']     = '';
          } else {
            $newDetail[$model]['id'] = $detail[$model]['id'];
          }
          $newDetail[$model] = array_merge(
              $newDetail[$model], array(
                'field' => $field,
                'value' => $value
              )
          );
          $this->save($newDetail, false);
        }
      } else if (isset($this->{$model})) {
        $newModelRecord[$model] = $details;
        if (!empty($userId)) {
          if ($model == $this->alias) {
            $newModelRecord[$model][$this->primaryKey] = $id;
          } else if ($this->{$model}->hasField($foreignKey)) {
            $newModelRecord[$model][$foreignKey] = $id;
          }
        }
        $this->{$model}->save($newModelRecord, false);
      }
    }

    $detailModel = ClassRegistry::init($this->parentModel);
    // Run only if the Traceable behavior is attached.
    if ($detailModel->hasMethod('mark')) {
      $detailModel->id = $id;
      $detailModel->mark('update' . ucwords($section));
    }

    return true;
  }

/**
 * Validates data for a given section.
 *
 * @param array $data Record data.
 * @param string $section Name of the section.
 * @return boolean
 */
  public function validateSection($data, $section) {
    $pass = true;
    if (!empty($this->sectionValidation[$section])) {
      $modelValidation = $this->validate;
      $this->validate = $this->sectionValidation[$section];
      $this->set($data);
      if (!$this->validates()) {
        $pass = false;
      }
      $this->validate = $modelValidation;
    }
    return $pass;
  }

}
