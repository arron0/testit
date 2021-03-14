<?php

namespace Arron\TestIt\Tools;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Matcher\AnyInvokedCount;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;

/**
 * MockFactory class definition
 *
 * @package Arron
 * @subpackage TestIt
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
class MockFactory
{
	/**
	 * @var MockFactory
	 */
	private static $instance = null;

	/** @var MockObject[] */
	private $mockClasses = array();

	/** @var int[] */
	private $mockedGlobalFunctions = array();

	/**
	 * @var array<string, mixed>
	 */
	private $methodsParameters = array();

	/**
	 * @var MockGenerator
	 */
	private $mockObjectGenerator;

	/**
	 * Protected constructor (class is a singleton)
	 */
	protected function __construct()
	{
	}

	/**
	 * @return MockFactory
	 */
	public static function getInstance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @return MockGenerator
	 */
	protected function getMockGenerator()
	{
		if ($this->mockObjectGenerator === null) {
			$this->mockObjectGenerator = new MockGenerator();
		}
		return $this->mockObjectGenerator;
	}

	/**
	 * @param string $identificator
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return void
	 */
	protected function addMethodParameter($identificator, $name, $value)
	{
		$this->methodsParameters[$identificator][$name] = $value;
	}

	/**
	 * @param string $identificator
	 *
	 * @return array<string, mixed>
	 */
	public function getMethodParameters($identificator)
	{
		$identificator = str_replace('::', '-', $identificator);
		if (isset($this->methodsParameters[$identificator])) {
			return $this->methodsParameters[$identificator];
		}
		return array();
	}

	/**
	 * @param string $mockName
	 *
	 * @return MockObject|null
	 */
	protected function getMockedClass($mockName)
	{
		if (isset($this->mockClasses[$mockName])) {
			return $this->mockClasses[$mockName];
		}
		return null;
	}

	/**
	 * @param string $mockName
	 * @param MockObject $mock
	 *
	 * @return void
	 */
	protected function addMockedClass($mockName, MockObject $mock)
	{
		$this->mockClasses[$mockName] = $mock;
	}

	/**
	 * @param string $identificator
	 *
	 * @return void
	 */
	public function addMockedGlobalFunction($identificator)
	{
		$this->mockedGlobalFunctions[$identificator] = 1;
	}

	/**
	 * @param string $identificator
	 *
	 * @return bool
	 */
	public function isGlobalFunctionMocked($identificator)
	{
		if (isset($this->mockedGlobalFunctions[$identificator])) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $mockName
	 * @param string $className
	 *
	 * @return MockObject
	 */
	public function getMock($mockName, $className)
	{
		$mock = $this->getMockedClass($mockName);
		if (is_null($mock)) {
			$mock = $this->getNewMock($mockName, $className);
			$this->addMockedClass($mockName, $mock);
		}
		return $mock;
	}

	/**
	 * @param string $mockName
	 * @param string $className
	 *
	 * @return MockObject
	 */
	public function getNewMock($mockName, $className)
	{
		if (class_exists($className) || interface_exists($className)) {
			return $this->mockClass($mockName, $className);
		}
		throw new ReflectionException('Class ' . $className . ' does not exists.');
	}

	/**
	 * @param string $mockName
	 * @param class-string $className
	 *
	 * @return MockObject
	 */
	protected function mockClass($mockName, $className)
	{
		$reflection = new ReflectionClass($className);
		$mock = $this->getMockGenerator()->getMock($className, array(), array(), $mockName, false, false, true, false);

		$publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

		foreach ($publicMethods as $method) {
			if ($method->isConstructor()) {
				//sometimes (not sure when) I get error:
				//Trying to configure method "__construct" which
				//cannot be configured because it does not exist,
				//has not been specified, is final, or is static
				continue;
			}
			$methodName = $method->getName();
			$methodIdentificator = $mockName . '-' . $method->getName();
			$this->saveMethodParameters($methodIdentificator, $method->getParameters());
			$mock->expects(new AnyInvokedCount())
				->method($methodName)
				->will(new ReturnCallback('\Arron\TestIt\Tools\FunctionsCallLogger::' . $methodIdentificator));
		}
		return $mock;
	}

	/**
	 * Save method parameters in format of name => default value | instance of RequiredArgument.
	 * Than it can be determined if the named argument has default value or if it is required
	 *
	 * @param string $identificator
	 * @param ReflectionParameter[] $parameters Methods parameters as array of their reflections
	 *
	 * @return void
	 */
	protected function saveMethodParameters($identificator, array $parameters)
	{
		foreach ($parameters as $parameter) {
			$name = $parameter->getName();
			if ($parameter->isDefaultValueAvailable()) {
				$value = $parameter->getDefaultValue();
			} elseif ($parameter->isOptional()) {
				$value = null;
			} else {
				$value = new RequiredArgument();
			}
			$this->addMethodParameter($identificator, $name, $value);
		}
	}

	/**
	 * @param string $name
	 * @param string $namespace
	 *
	 * @return void
	 * @throws ReflectionException
	 */
	public function createMockOfGlobalFunctionInNamespace($name, $namespace)
	{
		$namespace = trim($namespace, '\\');
		$namespacedIdentificator = $namespace . '\\' . $name;
		if ($this->isGlobalFunctionMocked($namespacedIdentificator)) {
			return;
		}
		$mockIdentificator = 'global-' . $name;
		$functionReflection = new ReflectionFunction($name);
		$functionMockCode = $this->generateCodeForGlobalFunctionMock($name, $namespace, $functionReflection);

		eval($functionMockCode);
		$this->addMockedGlobalFunction($namespacedIdentificator);
		$this->saveMethodParameters($mockIdentificator, $functionReflection->getParameters());
	}

	/**
	 * @param string $name
	 * @param string $namespace
	 * @param ReflectionFunction $reflectionObject
	 * @return string
	 */
	protected function generateCodeForGlobalFunctionMock($name, $namespace, $reflectionObject)
	{
		$arguments = $this->getMockGenerator()->getFunctionParameters($reflectionObject);
		$argumentsVariables = $this->getMockGenerator()->getFunctionParameters($reflectionObject, true);

		return "namespace $namespace; function $name($arguments){return \\Arron\\TestIt\\Tools\\FunctionsCallLogger::processFunctionCall('global::' . '$name', array($argumentsVariables));}";
	}
}
