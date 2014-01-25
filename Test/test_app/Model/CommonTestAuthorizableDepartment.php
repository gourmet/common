<?php

class CommonTestAuthorizableDepartment extends Model {

	public $actsAs = array(
		'Common.Authorizable' => array(
			'groups' => array('path' => 'Company.id', 'auth' => 'Company.id')
		)
	);

	public $belongsTo = array(
		'Company' => array(
			'className' => 'CommonTestAuthorizableCompany',
			'foreignKey' => 'company_id',
		)
	);

}
