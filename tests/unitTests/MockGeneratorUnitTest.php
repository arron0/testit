<?php
/**
 * Requires PHP Version 5.3 (min)
 *
 * @package
 * @subpackage
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license
 */
namespace Arron\TestIt\Tests;

use Arron\TestIt\Tools\MockGenerator;

/**
 * MockGeneratorUnitTest class definition
 *
 * @package
 * @subpackage
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license
 */
class MockGeneratorUnitTest extends \Arron\TestIt\TestCase
{
	
	/**
	 * @return MockGenerator
	 */
	protected function createTestObject()
	{
		return new MockGenerator();
	}

	/**
	 * @dataProvider getMethodParametersWithFunctionsDataProvider
	 */
	public function testGetMethodParametersWithFunctions($functionName, $expectedResult)
	{
		$reflectionFunction = new \ReflectionFunction($functionName);
		$returnedResult = $this->getTestObject()->getMethodParameters($reflectionFunction);
		$this->assertEquals($expectedResult, $returnedResult);
	}

	public function getMethodParametersWithFunctionsDataProvider()
	{
		return array(
				array('strlen', '$str'),
				array('strpos', '$haystack, $needle, $offset = null'),
				array('array_keys', '$arg, $search_value = null, $strict = null'),
				array('next', '&$arg'),
				array('array_map', '$callback, $arg, $arg2 = null'),
		);
	}

	/**
	 * @dataProvider getMethodParametersWithMethodsDataProvider
	 */
	public function testGetMethodParametersWithMethods($class, $method, $expectedResult)
	{
		$reflectionMethod = new \ReflectionMethod($class, $method);
		$returnedResult = $this->getTestObject()->getMethodParameters($reflectionMethod);
		$this->assertEquals($expectedResult, $returnedResult);
	}

	public function getMethodParametersWithMethodsDataProvider()
	{
		return array(
				array('DateTime', 'add', '$interval'),
		);
	}
}