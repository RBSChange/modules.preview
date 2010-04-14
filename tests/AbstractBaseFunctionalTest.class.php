<?php
/**
 * @package modules.preview.tests
 */
abstract class preview_tests_AbstractBaseFunctionalTest extends preview_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->loadSQLResource('functional-test.sql', true, false);
	}
}