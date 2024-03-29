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
use PHPUnit\Framework\MockObject\Generator;

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
		return new MockGenerator(new Generator());
	}

	/**
	 * @dataProvider getMethodParametersWithFunctionsDataProvider
	 */
	public function testGetMethodParametersWithFunctions($functionName, $expectedResult)
	{
		if(defined('HHVM_VERSION')) {
			$this->markTestSkipped('Not testing this in HHVM because of different implementation than in PHP.');
		}
		$reflectionFunction = new \ReflectionFunction($functionName);
		$returnedResult = $this->getTestObject()->getFunctionParameters($reflectionFunction);
		$this->assertEquals($expectedResult, $returnedResult);
	}

	public static function getMethodParametersWithFunctionsDataProvider()
	{
		return array(
				array('strlen', '$str'),
				array('strpos', '$haystack, $needle, $offset = null'),
				array('array_keys', '$arg, $search_value = null, $strict = null'),
				array('next', '&$arg'),
		);
	}
}
