<?php
/**
 * Requires PHP Version 5.3 (min)
 *
 * @package Arron
 * @subpackage TestIt
 * @subpackage Tests
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Arron\TestIt\Tests;

use Arron\TestIt\Tests\TestNamespace\ClassToTest;
use Arron\TestIt\Tests\TestNamespace\TestException;
use Arron\TestIt\Tools\FunctionsCallLoggerException;
use PHPUnit_Framework_ExpectationFailedException;

/**
 * TestItTest class definition
 *
 * @package Arron
 * @subpackage TestIt
 * @subpackage Tests
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
class TestItTest extends \Arron\TestIt\TestCase
{

	/*
	 * Specific behaviour for testExpectingMoreCallsThanCalled that should fail.
	 */
	protected function onNotSuccessfulTest(\Exception $e)
	{
		if(($this->getName() != "testExpectingMoreCallsThanCalled") && ($e->getMessage() != "'dependency2::doSomething' expected to be called but wasn't/weren't.")) {
			throw $e;
		}
	}

	/**
	 * @return object
	 */
	protected function createTestObject()
	{
		return new ClassToTest($this->getMockedClass('\Arron\TestIt\Tests\TestNamespace\ITest', 'dependency1'), $this->getMockedClass('\Arron\TestIt\Tests\TestNamespace\ITest', 'dependency2'));
	}

	public function testDependenciesExpectAndReturnValues()
	{
		$this->expectDependencyCall('dependency1', 'doSomething', array(3), 5);
		$this->expectDependencyCall('dependency2', 'doSomething', array(4), 6);

		$returnValue = $this->getTestObject()->callDependencies(3, 4);

		$this->assertEquals(array(5, 6), $returnValue);
	}

	public function testWrongDependencyCalled()
	{
		$success = FALSE;

		$this->expectDependencyCall('dependency1', 'doSomething', array(3), 5);
		$this->expectDependencyCall('dependency1', 'doSomething', array(4), 6);

		try {
			$this->getTestObject()->callDependencies(3, 4);
		} catch (PHPUnit_Framework_ExpectationFailedException $e) {
			$success = TRUE;
		}

		if (!$success) {
			$this->fail('Fails in failing with wrong dependency call exception.');
		}
	}

	public function testExpectingMoreCallsThanCalled()
	{
		$this->expectDependencyCall('dependency1', 'doSomething', NULL, 5);
		$this->expectDependencyCall('dependency2', 'doSomething', NULL, 6);

		$this->getTestObject()->callDependency(1);
	}

	public function testExpectingLessCallsThanCalled()
	{
		$success = FALSE;

		$this->expectDependencyCall('dependency1', 'doSomething', NULL, 5);

		try {
			$this->getTestObject()->callDependencies(3, 4);
		} catch (FunctionsCallLoggerException $e) {
			$success = TRUE;
		}

		if (!$success) {
			$this->fail('Fails in failing with less expectations than actually called.');
		}
	}

	public function testCalledDependencyParameterCheck()
	{
		$parameter = 'someParameter';
		$expectedResult = 'anyReturnValue';

		$this->expectDependencyCall('dependency1', 'doSomething', array($parameter), $expectedResult);

		$returnedResult = $this->getTestObject()->callDependency($parameter);

		$this->assertEquals($expectedResult, $returnedResult);
	}

	public function testCalledDependencyParameterCheckMissingParameter()
	{
		$success = FALSE;
		$this->expectDependencyCall('dependency1', 'doSomething', array());

		try {
			$this->getTestObject()->callDependency('anyParameter');
		} catch (PHPUnit_Framework_ExpectationFailedException $e) {
			$success = TRUE;
		}

		if (!$success) {
			$this->fail('Fails in failing with wrong dependency call exception.');
		}
	}

	public function testCalledDependencyParameterCheckFails()
	{
		$success = FALSE;
		$parameter = 'someParameter';
		$expectedResult = 'anyReturnValue';

		$this->expectDependencyCall('dependency1', 'doSomething', array('anyParameter'), $expectedResult);

		try {
			$this->getTestObject()->callDependency($parameter);
		} catch (PHPUnit_Framework_ExpectationFailedException $e) {
			$success = TRUE;
		}

		if (!$success) {
			$this->fail('Fails in failing with wrong parameters in dependency call.');
		}
	}

	public function testCalledDependencyParameterNotChecked()
	{
		$this->expectDependencyCall('dependency1', 'doSomething', NULL, 'anyValue');
		$this->getTestObject()->callDependency('anyParameter');
	}

	public function testCalledDependencyParameterCheckWithDefaultParameter()
	{
		$mandatoryParameter = 'someParameterValue';

		$this->expectDependencyCall('dependency1', 'methodWithDefaultParameter', array($mandatoryParameter));

		$this->getTestObject()->callMethodWithDefaultParameter($mandatoryParameter);
	}

	public function testCalledDependencyThrowException()
	{
		$this->expectDependencyCall('dependency1', 'doSomething', NULL, new TestException());

		$this->setExpectedException('\Arron\TestIt\Tests\TestNamespace\TestException');

		$this->getTestObject()->callDependency('anyParameter');
	}

	public function testMockOfGlobalFunction()
	{
		$this->mockGlobalFunction('rand', 'Arron\TestIt\Tests\TestNamespace');
		$this->mockGlobalFunction('rand', '\Arron\TestIt\Tests\TestNamespace');

		$this->expectDependencyCall('global', 'rand', array(1, 10), 5);

		$returnedResult = $this->getTestObject()->callGlobalFunctionRand(1, 10);

		$this->assertEquals(5, $returnedResult);
	}

	public function testProtectedPropertyGet()
	{
		$this->getTestObject()->setProtectedProperty(42);

		$returnedResult = $this->getPropertyFromTestSubject('protectedProperty');

		$this->assertEquals(42, $returnedResult);
	}

	public function testUnknownProtectedPropertyGet()
	{
		$this->assertNull($this->getPropertyFromTestSubject('unknown'));
	}

	public function testProtectedPropertySet()
	{
		$this->setPropertyInTestSubject('protectedProperty', 24);

		$this->assertEquals(24, $this->getTestObject()->getProtectedProperty());
	}

	public function testCallOfProtectedMethodOnTestObject()
	{
		$parameter = 'someParameter';
		$expectedResult = 'anyReturnValue';

		$this->expectDependencyCall('dependency1', 'doSomething', array($parameter), $expectedResult);

		$returnedResult = $this->callTestSubjectMethod('callDependencyFromProtectedMethod', array($parameter));

		$this->assertEquals($expectedResult, $returnedResult);
	}

	public function testCallUnknownMethodOnTestObject()
	{
		$this->setExpectedException('\InvalidArgumentException');

		$this->callTestSubjectMethod('unknownMethod', array());
	}

	public function testSetterTest()
	{
		$this->setterTest('setProtectedProperty', 142);
	}




}