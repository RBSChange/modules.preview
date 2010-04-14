<?php
/**
 * @package modules.preview.tests
 */
abstract class preview_tests_AbstractBaseIntegrationTest extends preview_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->loadSQLResource('integration-test.sql', true, false);
	}
}