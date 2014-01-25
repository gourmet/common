<?php

App::uses('CommonTestSuite', 'Common.TestSuite');

class AllCommonPluginTest extends PHPUnit_Framework_TestSuite {

	public static function suite() {
		return CommonTestSuite::allPluginTests();
	}

}
