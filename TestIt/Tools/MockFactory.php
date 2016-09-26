<?php
/**
 * Requires PHP Version 5.3 (min)
 *
 * @package Arron
 * @subpackage TestIt
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Arron\TestIt\Tools;

use PHPUnit_Framework_MockObject_Generator;

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
	private static $instance = NULL;

	/** @var array */
	private $mockClasses = array();

	/** @var array */
	private $mockedGlobalFunctions = array();

	/**
	 * @var array
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
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @return MockGenerator
	 */
	protected function getMockGenerator()
	{
		if(is_null($this->mockObjectGenerator)) {
			$this->mockObjectGenerator = new MockGenerator();
		}
		return $this->mockObjectGenerator;
	}

	/**
	 * @param string $identificator
	 * @param string $name
	 * @param mixed  $value
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
	 * @return array
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
	 * @return \PHPUnit_Framework_MockObject_MockObject|null
	 */
	protected function getMockedClass($mockName)
	{
		if (isset($this->mockClasses[$mockName])) {
			return $this->mockClasses[$mockName];
		}
		return NULL;
	}

	/**
	 * @param string $mockName
	 * @param \PHPUnit_Framework_MockObject_MockObject $mock
	 *
	 * @return void
	 */
	protected function addMockedClass($mockName, \PHPUnit_Framework_MockObject_MockObject $mock)
	{
		$this->mockClasses[$mockName] = $mock;
	}

	/**
	 * @param string $mockedGlobalFunctionName
	 */
	public function addMockedGlobalFunction($identificator)
	{
		$this->mockedGlobalFunctions[$identificator] = 1;
	}

	/**
	 * @param $identificator
	 *
	 * @return bool
	 */
	public function isGlobalFunctionMocked($identificator)
	{
		if (isset($this->mockedGlobalFunctions[$identificator])) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * @param string $mockName
	 * @param string $className
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
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
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	public function getNewMock($mockName, $className)
	{
		return $this->mockClass($mockName, $className);
	}

	/**
	 * @param string $mockName
	 * @param string $className
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function mockClass($mockName, $className)
	{
		$reflection = new \ReflectionClass($className);
		$mock =$this->getMockGenerator()->getMock($className, array(), array(), $mockName, FALSE, FALSE, TRUE, FALSE);

		$publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

		foreach ($publicMethods as $method) {
		    if($method->isConstructor()) {
		        //sometimes (not sure when) I get error:
		        //Trying to configure method "__construct" which
                //cannot be configured because it does not exist,
                //has not been specified, is final, or is static
                continue;
            }
			$methodName = $method->getName();
			$methodIdentificator = $mockName . '-' . $method->getName();
			$this->saveMethodParameters($methodIdentificator, $method->getParameters());
			$mock->expects(new \PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount())
					->method($methodName)
					->will(new \PHPUnit_Framework_MockObject_Stub_ReturnCallback('\Arron\TestIt\Tools\FunctionsCallLogger::' . $methodIdentificator));
		}
		return $mock;
	}

	/**
	 * Save method parameters in format of name => default value | instance of RequiredArgument.
	 * Than it can be determined if the named argument has default value or if it is required
	 *
	 * @param string $identificator
	 * @param array $parameters Methods parameters as array of their reflections
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
				$value = NULL;
			} else {
				$value = new RequiredArgument();
			}
			$this->addMethodParameter($identificator, $name, $value);
		}
	}

	public function createMockOfGlobalFunctionInNamespace($name, $namespace)
	{
		$namespace = trim($namespace, '\\');
		$namespacedIdentificator = $namespace . '\\' . $name;
		if ($this->isGlobalFunctionMocked($namespacedIdentificator)) {
			return;
		}
		$mockIdentificator = 'global-' . $name;
		$functionReflection = new \ReflectionFunction($name);
		$functionMockCode = $this->generateCodeForGlobalFunctionMock($name, $namespace, $functionReflection);

		eval($functionMockCode);
		$this->addMockedGlobalFunction($namespacedIdentificator);
		$this->saveMethodParameters($mockIdentificator, $functionReflection->getParameters());
	}

	protected function generateCodeForGlobalFunctionMock($name, $namespace, $reflectionObject)
	{
		$arguments = $this->getMockGenerator()->getFunctionParameters($reflectionObject);
		$argumentsVariables = $this->getMockGenerator()->getFunctionParameters($reflectionObject, TRUE);

		return "namespace $namespace; function $name($arguments){return \\Arron\\TestIt\\Tools\\FunctionsCallLogger::processFunctionCall('global::' . '$name', array($argumentsVariables));}";
	}
}