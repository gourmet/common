<?php

App::uses('CommonTestCase', 'Common.TestSuite');

class AuthorizableBehaviorTest extends CommonTestCase {

	public $fixtures = array(
		'plugin.common.common_test_authorizable_company',
		'plugin.common.common_test_authorizable_companies_user',
		'plugin.common.common_test_authorizable_department',
		'plugin.common.common_test_authorizable_doc',
		'plugin.common.common_test_authorizable_docs_group',
		'plugin.common.common_test_authorizable_group',
		'plugin.common.common_test_authorizable_grouped_user',
		'plugin.common.common_test_authorizable_groups_grouped_user',
		'plugin.common.common_test_authorizable_record',
		'plugin.common.common_test_authorizable_user',
	);

	public function setUp() {
		parent::setUp();
		$this->Company = ClassRegistry::init('CommonTestAuthorizableCompany');
		$this->Department = ClassRegistry::init('CommonTestAuthorizableDepartment');
		$this->Doc = ClassRegistry::init('CommonTestAuthorizableDoc');
		$this->Record = ClassRegistry::init('CommonTestAuthorizableRecord');
		$this->User = ClassRegistry::init('CommonTestAuthorizableUser');
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->Department, $this->Doc, $this->Record, $this->User);
		ClassRegistry::flush();
	}

	public static function data($type = 'noGroupMember') {
		$user = array('User' => array('id' => 1, 'email' => 'john@doe.com'));

		$data = array(
			'noGroupMember' => $user,
			'adminMember' => Hash::merge($user, array('User' => array('is_admin' => true))),
			'singleGroupMember' => Hash::merge(
				$user,
				array('User' => array('Company' => array('id' => 1, 'name' => 'Foo Company')))
			),
			'mutliGroupsMember' => Hash::merge(
				$user,
				array('User' => array('Group' => array(
					array('id' => 2, 'name' => 'moderator'),
					array('id' => 3, 'name' => 'editor')
				)))
			),
		);

		return $data[$type];
	}

	public function testSetup() {
		$defaults = array(
			'owner' => array(
				'allow' => true,
				'perms' => 'CRUD',
				'path' => 'user_id',
				'create' => true,
				'read' => true,
				'update' => true,
				'delete' => true,
			),

			'groups' => array(
				'allow' => false,
				'perms' => 'CRU',
				'path' => 'Group.name',
				'auth' => 'Group.name'
			),

			'collaborators' => array(
				'allow' => false,
				'perms' => 'CRU',
				'path' => 'User.{n}.id'
			),

			'admin' => array(
				'allow' => true,
				'perms' => 'CRUD',
				'path' => '[is_admin=1]',
				'create' => true,
				'read' => true,
				'update' => true,
				'delete' => true,
			),

			'perms' => array('create', 'read', 'update', 'delete')
		);

		$result = $this->User->Behaviors->Authorizable->settings[$this->User->alias];
		$expected = Hash::merge($defaults, array('owner' => array('path' => 'id')));
		$this->assertEqual($result, $expected);

		$result = $this->Record->Behaviors->Authorizable->settings[$this->Record->alias];
		$expected = $defaults;
		$this->assertEqual($result, $expected);

		$this->Record->Behaviors->detach('Authorizable');

		$this->expectException('FatalErrorException', __d('common', "Missing owner's field 'some_field' in table '%s'", $this->Record->useTable));
		$this->Record->Behaviors->attach('Common.Authorizable', array('some_field'));
	}

	public function testBeforeDelete() {
		CakeSession::write('Auth', self::data());

		$this->assertFalse($this->Company->delete(3));
		$this->assertFalse($this->Company->delete(2));
		$this->assertFalse($this->User->delete(2));
		$this->assertTrue($this->Company->delete(1));
		$this->assertTrue($this->User->delete(1));

		CakeSession::delete('Auth', self::data());
	}

	public function testBeforeFindByOwner() {
		CakeSession::write('Auth', self::data());

		$result = count($this->User->find('all'));
		$this->assertEqual($result, 1);

		CakeSession::delete('Auth');
	}

	public function testBeforeFindByAdmin() {
		CakeSession::write('Auth', self::data('adminMember'));

		$result = count($this->User->find('all'));
		$this->assertEqual($result, 2);

		CakeSession::delete('Auth');
	}

	public function testBeforeFindByMemberOfSingleGroup() {
		CakeSession::write('Auth', self::data('singleGroupMember'));

		$result = count($this->Department->find('all'));
		$this->assertEqual($result, 3);

		CakeSession::delete('Auth');
	}

	public function testBeforeFindByMemberOfMultiGroup() {
		CakeSession::write('Auth', self::data('mutliGroupsMember'));

		$result = count($this->Doc->find('all'));
		$this->assertEqual($result, 1);

		CakeSession::delete('Auth');
	}

	public function testBeforeSaveByOwner() {
		CakeSession::write('Auth', self::data());

		$data = array('email' => 'new@email.com');

		$this->User->id = 2;
		$this->assertFalse($this->User->save($data));

		$this->User->id = null;
		$this->assertFalse($this->User->save(array_merge($data, array('id' => 2))));

		$this->User->id = null;
		$this->assertNotEmpty($this->User->save(array_merge($data, array('id' => 1))));

		CakeSession::delete('Auth');
	}

	public function testBeforeSaveByAdmin() {
		CakeSession::write('Auth', self::data('adminMember'));

		$this->User->id = 2;
		$this->assertNotEmpty($this->User->save(array('email' => 'new@email.com')));

		$this->Doc->id = 2;
		$this->assertNotEmpty($this->Doc->save(array('name' => 'New Name')));

		$this->Department->id = 2;
		$this->assertNotEmpty($this->Department->save(array('name' => 'New IT')));

		CakeSession::delete('Auth');
	}

	public function testBeforeSaveByMemberOfSingleGroup() {
		CakeSession::write('Auth', self::data('singleGroupMember'));

		$data = array('name' => 'New Name');

		$this->Department->id = 2;
		$this->assertFalse($this->Department->save($data));

		$this->Department->id = 3;
		$this->assertNotEmpty($this->Department->save($data));

		CakeSession::delete('Auth');
	}

	public function testBeforeSaveByMemberOfMultiGroup() {
		CakeSession::write('Auth', self::data('mutliGroupsMember'));

		$data = array('name' => 'New Name');

		$this->Doc->id = 1;
		$this->assertNotEmpty($this->Doc->save($data));

		$this->Doc->id = 2;
		$this->assertNotEmpty($this->Doc->save($data));

		CakeSession::delete('Auth');
	}

	public function testBeforeSaveByMemberOfAuthorizedUsers() {
		CakeSession::write('Auth', self::data());

		$data = array('name' => 'New Name');

		$this->Company->id = 2;
		$this->assertNotEmpty($this->Company->save($data));

		$this->Company->id = 3;
		$this->assertFalse($this->Company->save($data));

		CakeSession::delete('Auth');
	}

	public function testIsAllowedByOwner() {
		CakeSession::write('Auth', self::data());

		$this->User->id = 1;
		$this->assertTrue($this->User->isAllowed());

		$this->User->id = 2;
		$this->assertFalse($this->User->isAllowed());

		$this->Record->id = 1;
		$this->assertTrue($this->Record->isAllowed());

		$this->Record->id = 1;
		$this->assertTrue($this->Record->isAllowed('read'));

		$this->Record->id = 1;
		$this->assertTrue($this->Record->isAllowed('update'));

		$this->Record->id = 1;
		$this->assertTrue($this->Record->isAllowed('delete'));

		$this->Record->id = 2;
		$this->assertFalse($this->Record->isAllowed());

		CakeSession::delete('Auth');
	}

	public function testIsAllowedByAdmin() {
		CakeSession::write('Auth', self::data('adminMember'));

		$this->User->id = 2;
		$this->assertTrue($this->User->isAllowed());

		CakeSession::delete('Auth');
	}

	public function testIsAllowedByMemberOfSingleGroup() {
		CakeSession::write('Auth', self::data('singleGroupMember'));

		$this->Department->id = 1;
		$this->assertTrue($this->Department->isAllowed());

		$this->Department->id = 2;
		$this->assertFalse($this->Department->isAllowed());

		$this->Department->id = 3;
		$this->assertTrue($this->Department->isAllowed());

		$this->Department->id = 3;
		$this->assertTrue($this->Department->isAllowed('read'));

		$this->Department->id = 3;
		$this->assertTrue($this->Department->isAllowed('update'));

		$this->Department->id = 3;
		$this->assertFalse($this->Department->isAllowed('delete'));

		CakeSession::delete('Auth');
	}

	public function testIsAllowedByMemberOfMultipleGroups() {
		CakeSession::write('Auth', self::data('mutliGroupsMember'));

		$this->Doc->id = 1;
		$this->assertTrue($this->Doc->isAllowed());

		$this->Doc->id = 2;
		$this->assertTrue($this->Doc->isAllowed());

		CakeSession::delete('Auth');
	}

	public function testIsAllowerdByMemberOfAuthorizedUsers() {
		CakeSession::write('Auth', self::data());

		$this->Company->id = 2;
		$this->assertTrue($this->Company->isAllowed());

		$this->Company->id = 2;
		$this->assertFalse($this->Company->isAllowed('delete'));

		$this->Company->id = 3;
		$this->assertFalse($this->Company->isAllowed());

		CakeSession::delete('Auth');
	}

	public function testIsAllowedSkipped() {
		$this->User->requireAuth = false;
		$this->assertTrue($this->User->isAllowed());
	}

	public function testGetCurrentUser() {
		$user = array('User' => array('id' => 1, 'email' => 'foo@bar.com', 'prefix' => 'admin'));

		CakeSession::write('Auth', $user);
		$result = $this->User->getCurrentUser();
		$expected = $user['User'];
		$this->assertEqual($result, $expected);

		$result = $this->User->getCurrentUser('email');
		$expected = $user['User']['email'];
		$this->assertEqual($result, $expected);
		CakeSession::delete('Auth');
	}

	public function testSetCurrentUser() {
		$user = array('User' => array('id' => 1, 'email' => 'foo@bar.com', 'prefix' => 'admin'));

		CakeSession::write('Auth', $user);
		$result = $this->User->setCurrentUser();
		$expected = $user['User'];
		$this->assertEqual($result, $expected);
		CakeSession::delete('Auth');

		$result = $this->User->setCurrentUser($user['User']);
		$expected = $user['User'];
		$this->assertEqual($result, $expected);
	}

}
