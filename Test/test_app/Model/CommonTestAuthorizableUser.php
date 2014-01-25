<?php

class CommonTestAuthorizableUser extends Model {

	public $actsAs = array('Common.Authorizable' => array('id'));

	public $hasAndBelongsToMany = array(
		'Company' => array(
			'className' => 'CommonTestAuthorizableCompany',
			'joinTable' => 'common_test_authorizable_companies_users',
			'foreignKey' => 'user_id',
			'associationForeignKey' => 'company_id'
		)
	);

}
