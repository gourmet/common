<?php
App::uses('Component', 'Controller');

/**
 * Persistent Validation Component.
 *
 * When there are errors in a form submission, FormHelper auto-magically shows them next to
 * the associated field. However, after a redirect, the validation errors (and form data) don't
 * persist. This component make them persist by using SessionComponent.
 *
 * @package Common.Controller.Component
 */
class PersistentValidationComponent extends Component {

/**
 * {@inheritdoc}
 */
	public $components = array('Session');

	/**
	 * Session's key.
	 *
	 * @var string
	 */
	public $sessionKey = 'PersistentValidation';

/**
 * {@inheritdoc}
 */
	public function startup(Controller $Controller) {
		$validationErrors = $this->Session->read($this->sessionKey);
		if (empty($validationErrors)) {
			return;
		}

		if (empty($Controller->uses)) {
			$Controller->uses = array();
		}

		$models = $Controller->uses;

		foreach ($models as $k => $model) {
			list(, $models[$k]) = pluginSplit($model);
		}

		foreach ($validationErrors as $modelName => $submission) {
			if (!in_array($modelName, $models)) {
				continue;
			}

			if (empty($Controller->request->data)) {
				$Controller->request->data = array();
			}

			if (empty($Controller->request->data[$modelName])) {
				$Controller->request->data[$modelName] = $submission['data'];
			}
			$Controller->$modelName->validationErrors = $submission['errors'];
			$this->Session->delete("$this->sessionKey.$modelName");
		}
	}

/**
 * {@inheritdoc}
 */
	public function beforeRedirect(Controller $Controller, $url, $status = NULL, $exit = true) {
		if (!empty($Controller->uses)) {
			foreach ($Controller->uses as $modelName) {
				list(, $modelName) = pluginSplit($modelName);

				if (empty($Controller->{$modelName}->validationErrors)) {
					continue;
				}

				$this->Session->write(join('.', array($this->sessionKey, $modelName)), array(
					'data' => $Controller->{$modelName}->data[$modelName],
					'errors' => $Controller->{$modelName}->validationErrors
				));
			}
		} else if (!empty($Controller->modelClass)) {
			$this->Session->write(join('.', array($this->sessionKey, $Controller->modelClass)), array(
				'data' => $Controller->{$Controller->modelClass}->data[$Controller->modelClass],
				'errors' => $Controller->{$Controller->modelClass}->validationErrors
			));
		}
	}

/**
 * Flush all persistent validation session data.
 *
 * @return boolean
 */
	public function flush() {
		return $this->Session->delete($this->sessionKey);
	}

/**
 * Manually force errors to persist.
 *
 * @param string|Model $model
 * @param array $errors
 * @param array $data
 * @return boolean
 */
	public function persist($model, $errors = array(), $data = array()) {
		if (is_a($model, 'Model')) {
			$errors = $model->validationErrors;
			$data = $model->data;
			$model = $model->alias;
		}

		return $this->Session->write("$this->sessionKey.$model", compact('data', 'errors'));
	}

}
