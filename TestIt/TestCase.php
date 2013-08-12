<?php
/**
 * Requires PHP Version 5.3 (min)
 *
 * @package Arron
 * @subpackage TestIt
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Arron\TestIt;

/**
 * TestCase class definition
 *
 * @package Arron
 * @subpackage TestIt
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
	/** @var object */
	private $testObject;

	/** @var \Arron\TestIt\Tools\MockFactory */
	private $mockFactory;

	/** @var bool */
	private $setupCheck;

	/**
	 * @inheritdoc
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->resetFunctionLog();
		$this->initializationExpectations();
		$this->testObject = $this->createTestObject();
		$this->setupCheck = TRUE;
	}

	/**
	 * @inheritdoc
	 * @throws \ErrorException
	 */
	protected function checkRequirements()
	{
		parent::checkRequirements();
		if (!$this->setupCheck) {
			$reflection = new \ReflectionClass($this);
			$class = $reflection->getMethod('setUp')->getDeclaringClass()->getName();
			throw new \ErrorException("Method $class::setUp() or its descendant doesn't call parent::setUp().");
		}
	}

	/**
	 * @return object
	 */
	abstract protected function createTestObject();

	protected function initializationExpectations()
	{
		//intentionally empty, prepared to be overwritten
	}

	/**
	 * @return object
	 */
	protected function getTestObject()
	{
		return $this->testObject;
	}

	protected function mockGlobalFunction($name, $namespace = NULL)
	{
		$namespace = is_null($namespace) ? $this->getReflection($this)->getNamespaceName() : $namespace;
		$this->getMockFactory()->createMockOfGlobalFunctionInNamespace($name, $namespace);
	}

	/**
	 * @param string $className Name of the class
	 * @param string $mockName  Name of the mock
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getMockedClass($className, $mockName)
	{
		return $this->getMockFactory()->getMock($mockName, $className);
	}

	/**
	 * @return \Arron\TestIt\Tools\MockFactory
	 */
	private function getMockFactory()
	{
		if (is_null($this->mockFactory)) {
			$this->mockFactory = $this->createMockFactory();
		}
		return $this->mockFactory;
	}

	/**
	 * @return \Arron\TestIt\Tools\MockFactory
	 */
	private function createMockFactory()
	{
		return Tools\MockFactory::getInstance();
	}

	/**
	 * @param string $dependencyName
	 * @param string $methodName
	 * @param array|null $methodArguments NULL if you want to skip check
	 * @param mixed|\Exception $returnValue If instance of Exception it will be thrown
	 *
	 * @return void
	 */
	protected function expectDependencyCall($dependencyName, $methodName, $methodArguments = array(), $returnValue = NULL)
	{
		$methodName = $dependencyName . '::' . $methodName;
		$this->expectFunctionCall($methodName, $methodArguments, $returnValue);
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 * @param mixed $expectedResult
	 *
	 * @return void
	 */
	private function expectFunctionCall($name, array $arguments = NULL, $expectedResult = NULL)
	{
		Tools\FunctionsCallLogger::expectFunctionCall($name, $arguments, $expectedResult);
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return void
	 */
	protected function setPropertyInTestSubject($name, $value)
	{
		$reflection = $this->getTestSubjectReflection();
		if ($reflection->hasProperty($name)) {
			$property = $reflection->getProperty($name);
			$property->setAccessible(TRUE);
			$property->setValue($this->getTestObject(), $value);
		}
	}

	/**
	 * @param string $name
	 *
	 * @return mixed|null
	 */
	protected function getPropertyFromTestSubject($name)
	{
		$reflection = $this->getTestSubjectReflection();
		if ($reflection->hasProperty($name)) {
			$property = $reflection->getProperty($name);
			$property->setAccessible(TRUE);
			return $property->getValue($this->getTestObject());
		}
		return NULL;
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	protected function callTestSubjectMethod($name, array $arguments = array())
	{
		$reflection = $this->getTestSubjectReflection();
		if ($reflection->hasMethod($name)) {
			$method = $reflection->getMethod($name);
			$method->setAccessible(TRUE);
			return $method->invokeArgs($this->getTestObject(), $arguments);
		}
		throw new \InvalidArgumentException("You are trying to call non-existing '$name' method on testing object.");
	}

	/**
	 * @return \ReflectionClass
	 */
	protected function getTestSubjectReflection()
	{
		return $this->getReflection($this->getTestObject());
	}

	/**
	 * @param object|string $argument
	 * @return \ReflectionClass
	 */
	protected function getReflection($argument)
	{
		return new \ReflectionClass($argument);
	}

	/**
	 * Test any setter in test subject class
	 *
	 * @param string $setterName
	 * @param mixed $testValue
	 * @param null|string $propertyName
	 *
	 * @return void
	 */
	protected function setterTest($setterName, $testValue, $propertyName = NULL)
	{
		if (is_null($propertyName)) {
			$propertyName = substr($setterName, 3);
			$propertyName = lcfirst($propertyName);
		}

		$this->getTestObject()->$setterName($testValue);

		$this->assertEquals($testValue, $this->getPropertyFromTestSubject($propertyName));
	}

	/**
	 * Hook into PHPUnit's test runner to assert more stuff.
	 *
	 * @return void
	 */
	protected function assertPostConditions()
	{
		parent::assertPostConditions();
		$this->assertUncalledDependencies();
	}

	/**
	 * @return void
	 */
	protected function assertUncalledDependencies()
	{
		$uncalledFunctions = $this->getUnalledDependencies();

		if (is_array($uncalledFunctions) && (count($uncalledFunctions) > 0)) {
			$this->fail("'" . implode(', ', $uncalledFunctions) . "' expected to be called but wasn't/weren't.");
		}
	}

	/**
	 * @return void
	 */
	protected function resetFunctionLog()
	{
		Tools\FunctionsCallLogger::reset();
	}

	/**
	 * @return array
	 */
	private function getUnalledDependencies()
	{
		return Tools\FunctionsCallLogger::getExpectedFunctions();
	}
}