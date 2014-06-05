<?php
/**
 * Requires PHP Version 5.3 (min)
 *
 * @package
 * @subpackage
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license
 */
/**
 * TestItUnitTestWithExternalTestObject class definition
 *
 * @package
 * @subpackage
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license
 */
class TestItUnitTestWithExternalTestObject extends \Arron\TestIt\TestCase
{
	
	/**
	 * @return object
	 */
	protected function createTestObject()
	{
		return NULL;
	}

	protected function createTestObjectWithParameter($param)
	{
		$testObject = new stdClass();
		$testObject->param = $param;
		$this->setTestObject($testObject);
	}

	public function testExternalTestObjectCreation()
	{
		$this->createTestObjectWithParameter('someParam');

		$this->assertInstanceOf('\stdClass', $this->getTestObject());
		$this->assertEquals('someParam', $this->getTestObject()->param);
	}
}