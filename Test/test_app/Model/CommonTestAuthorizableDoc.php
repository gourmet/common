<?php

class CommonTestAuthorizableDoc extends Model {

	public $actsAs = array(
		'Common.Authorizable' => array(
			'groups' => array('path' => 'Group.{n}.name', 'auth' => 'Group.{n}.name')
		)
	);

	public $hasAndBelongsToMany = array(
		'Group' => array(
			'className' => 'CommonTestAuthorizableGroup',
			'joinTable' => 'common_test_authorizable_docs_groups',
			'foreignKey' => 'doc_id',
			'associationForeignKey' => 'group_id'
		)
	);

}
