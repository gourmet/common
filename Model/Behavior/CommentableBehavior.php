<?php

class CommentableBehavior extends ModelBehavior {

	public $settings = array();

	public function setup(Model $Model, $settings = array()) {

		$defaults = array(
			'commentModel' => 'Comment',
			'hasManyAssoc' => array('dependent' => false),
			'belongsToAssoc' => array('counterCache' => true),
			'userModel' => 'Users.User',
			'allowThreaded' => true
		);

		$this->settings[$Model->alias] = array_merge($defaults, $settings);

		$commentAlias = $Model->alias . 'Comment';
		$assocDefaults = array(
			'foreignKey' => 'foreign_key',
			'conditions' => array($commentAlias . '.foreign_model' => $this->__pluginMerge($Model))
		);

		$Model->bindModel(array('hasMany' => array($commentAlias => array_merge(
			array('className' => $this->settings[$Model->alias]['commentModel']) + $assocDefaults,
			$this->settings[$Model->alias]['hasManyAssoc']
		))), false);


		$Model->{$commentAlias}->bindModel(array('belongsTo' => array($Model->alias => array_merge(
			array('className' => $this->__pluginMerge($Model)) + $assocDefaults,
			$this->settings[$Model->alias]['belongsToAssoc']
		))), false);

		if ($this->settings[$Model->alias]['allowThreaded']) {
			$Model->{$commentAlias}->Behaviors->load('Common.Commentable', array_merge(
				$this->settings[$Model->alias],
				array('allowThreaded' => false)
			));
			$threadedCommentAlias = $commentAlias . 'Comment';
			$Model->{$commentAlias}->{$threadedCommentAlias}->Behaviors->load('Tree', array('scope' => $commentAlias));
		}
	}

	public function comment(Model $Model, $id, $data = array()) {
		$settings = $this->settings[$Model->alias];

		$commentAlias = $Model->alias . 'Comment';

		if (empty($data)) {
			$data = $id;
			$id = $Model->getID();
		}

		if (empty($id) || empty($data[$commentAlias])) {
			return false;
		}

		$data[$commentAlias] = array_merge(
			array('foreign_model' => $this->__pluginMerge($Model), 'foreign_key' => $id),
			$data[$commentAlias]
		);

		if (!empty($settings['validate'])) {

		}

		$Model->{$commentAlias}->create($data);
		if (!$Model->{$commentAlias}->save(null, !empty($settings['validate']))) {
			return false;
		}

		return true;
	}

	private function __pluginMerge(Model $Model) {
		return (!empty($Model->plugin) ? $Model->plugin . '.' : '') . $Model->alias;
	}

}
