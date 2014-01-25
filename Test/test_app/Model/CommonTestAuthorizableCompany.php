<?php

class CommonTestAuthorizableCompany extends Model {

	public $actsAs = array(
		'Common.Authorizable' => array(
			'collaborators' => array('allow' => true)
		)
	);

	public $hasAndBelongsToMany = array(
		'User' => array(
			'className' => 'CommonTestAuthorizableUser',
			'joinTable' => 'common_test_authorizable_companies_users',
			'foreignKey' => 'company_id',
			'associationForeignKey' => 'user_id'
		),
	);

	public $hasMany = array(
		'Department' => array(
			'className' => 'CommonTestAuthorizableDepartment',
			'foreignKey' => 'company_id',
		)
	);

}
