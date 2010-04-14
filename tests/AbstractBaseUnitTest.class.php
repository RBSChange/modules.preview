<?php
/**
 * @package modules.preview.tests
 */
abstract class preview_tests_AbstractBaseUnitTest extends preview_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->resetDatabase();
	}
}