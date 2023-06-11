<?php

namespace Arron\TestIt;

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

/**
 * TestCase class definition
 *
 * @package Arron
 * @subpackage TestIt
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
	/** @var object */
	private $testObject;

	/** @var \Arron\TestIt\Tools\MockFactory */
	private $mockFactory;

	/** @var bool */
	private $setupCheck;

	/**
	 * @inheritdoc
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->resetFunctionLog();
		$this->initializationExpectations();
		$this->testObject = $this->createTestObject();
		$this->setupCheck = true;
	}

	/**
	 * @return object
	 */
	abstract protected function createTestObject();

	/**
	 * @return void
	 */
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

	/**
	 * @param object $object
	 *
	 * @return void
	 */
	protected function setTestObject($object)
	{
		if ($this->testObject === null) {
			$this->testObject = $object;
			return;
		}
		throw new \LogicException('Test object is already set. If you want to create it externaly, you have to return null from createTestObject method.');
	}

	/**
	 * @param string $name
	 * @param string $namespace
	 *
	 * @return void
	 */
	protected function mockGlobalFunction($name, $namespace = null)
	{
		$namespace = is_null($namespace) ? $this->getReflection($this)->getNamespaceName() : $namespace;
		$this->getMockFactory()->createMockOfGlobalFunctionInNamespace($name, $namespace);
	}

	/**
	 * @param string $className
	 * @param string $mockName
	 *
	 * @return MockObject
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
		if ($this->mockFactory === null) {
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
	 * @param mixed[]|null $methodArguments null if you want to skip check
	 * @param mixed|\Exception $returnValue If instance of Exception it will be thrown
	 *
	 * @return void
	 */
	protected function expectDependencyCall($dependencyName, $methodName, $methodArguments = array(), $returnValue = null)
	{
		$methodName = $dependencyName . '::' . $methodName;
		$this->expectFunctionCall($methodName, $methodArguments, $returnValue);
	}

	/**
	 * @param string $name
	 * @param mixed[] $arguments
	 * @param mixed $expectedResult
	 *
	 * @return void
	 */
	private function expectFunctionCall($name, array $arguments = null, $expectedResult = null)
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
			$property->setAccessible(true);
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
			$property->setAccessible(true);
			return $property->getValue($this->getTestObject());
		}
		return null;
	}

	/**
	 * @param string $name
	 * @param mixed[] $arguments
	 *
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	protected function callTestSubjectMethod($name, array $arguments = array())
	{
		$reflection = $this->getTestSubjectReflection();
		if ($reflection->hasMethod($name)) {
			$method = $reflection->getMethod($name);
			$method->setAccessible(true);
			return $method->invokeArgs($this->getTestObject(), $arguments);
		}
		throw new \InvalidArgumentException("You are trying to call non-existing '$name' method on testing object.");
	}

	/**
	 * @return ReflectionClass<object>
	 */
	protected function getTestSubjectReflection()
	{
		return $this->getReflection($this->getTestObject());
	}

	/**
	 * @param object|class-string $argument
	 *
	 * @return ReflectionClass<object>
	 */
	protected function getReflection($argument)
	{
		return new ReflectionClass($argument);
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
	protected function setterTest($setterName, $testValue, $propertyName = null)
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
	protected function assertPostConditions(): void
	{
		parent::assertPostConditions();
		$this->assertUncalledDependencies();
	}

	/**
	 * @return void
	 */
	protected function assertUncalledDependencies()
	{
		$uncalledFunctions = $this->getUncalledDependencies();

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
	 * @return string[]
	 */
	private function getUncalledDependencies()
	{
		return Tools\FunctionsCallLogger::getExpectedFunctions();
	}
}
