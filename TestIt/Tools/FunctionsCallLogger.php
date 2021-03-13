<?php

namespace Arron\TestIt\Tools;

use PHPUnit\Framework\Assert;

/**
 * FunctionsCallLogger class definition
 *
 * @package Arron
 * @subpackage TestIt
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
class FunctionsCallLogger
{
	/**
	 * @var array
	 */
	private static $loggedCalls = array();

	/**
	 * @var array
	 */
	private static $expectedCalls = array();

	/**
	 * @var array
	 */
	private static $passedFunctions = array();

	/**
	 * @var \Arron\TestIt\Tools\MockFactory
	 */
	private static $mockFactory;

	/**
	 * @return \Arron\TestIt\Tools\MockFactory
	 */
	protected static function getMockFactory()
	{
		if (is_null(self::$mockFactory)) {
			self::$mockFactory = MockFactory::getInstance();
		}
		return self::$mockFactory;
	}

	/**
	 * @param string $identificator
	 *
	 * @return array
	 */
	protected static function getFunctionArguments($identificator)
	{
		return self::getMockFactory()->getMethodParameters($identificator);
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public static function __callStatic($name, array $arguments)
	{
		$functionName = str_replace('-', '::', $name);
		return self::processFunctionCall($functionName, $arguments);
	}

	/**
	 * @return array
	 */
	public static function getLoggedFunctions()
	{
		if (array_key_exists('functions', self::$loggedCalls)) {
			return self::$loggedCalls['functions'];
		}
		return array();
	}

	/**
	 * @return array
	 */
	public static function getLoggedArgumets()
	{
		if (array_key_exists('arguments', self::$loggedCalls)) {
			return self::$loggedCalls['arguments'];
		}
		return array();
	}

	/**
	 * @return array
	 */
	public static function getExpectedFunctionArguments()
	{
		if (array_key_exists('arguments', self::$expectedCalls)) {
			return self::$expectedCalls['arguments'];
		}
		return array();
	}

	/**
	 * @return array
	 */
	public static function getExpectedFunctions()
	{
		if (array_key_exists('functions', self::$expectedCalls)) {
			return self::$expectedCalls['functions'];
		}
		return array();
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public static function processFunctionCall($name, array $arguments)
	{
		self::logFunctionCall($name, $arguments);
		self::validateFunctionCall($name, $arguments);
		return self::getResult($name);
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return void
	 */
	protected static function logFunctionCall($name, array $arguments)
	{
		self::logFunction($name);
		self::logArguments($arguments);
	}

	/**
	 * @param string $name
	 *
	 * @return void
	 */
	protected static function logFunction($name)
	{
		self::$loggedCalls['functions'][] = $name;
	}

	/**
	 * @param array $arguments
	 *
	 * @return void
	 */
	protected static function logArguments(array $arguments)
	{
		self::$loggedCalls['arguments'][] = $arguments;
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return void
	 *
	 * @throws FunctionsCallLoggerException
	 */
	protected static function validateFunctionCall($name, array $arguments)
	{
		self::validateFunctionName($name, $arguments);
		self::validateFunctionArguments($name, $arguments);
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return void
	 *
	 */
	protected static function validateFunctionName($name, array $arguments)
	{
		$expectedName = self::getNextExpectedFunction();
		if (is_null($expectedName)) {
			$argumentsString = '(' . implode(', ', $arguments) . ')';
			throw new FunctionsCallLoggerException("Call of function {$name}{$argumentsString} wasn't expected.");
		}

		self::$passedFunctions[] = $expectedName;

		if ($name != $expectedName) {
			Assert::assertEquals(
				self::$passedFunctions,
				self::getLoggedFunctions(),
				"It was supposed '$expectedName' to be called , '$name' called instead. See expectation difference below."
			);
		}
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return void
	 */
	protected static function validateFunctionArguments($name, array $arguments)
	{
		$expectedArguments = self::getNextExpectedArguments();

		if (is_null($expectedArguments)) {
			return;
		}

		$expectedArguments = self::fillDefaultArguments($name, $expectedArguments);

		$serializedFunctionCalls = implode("\n", self::getLoggedFunctions());
		Assert::assertEquals(
			$expectedArguments,
			$arguments,
			"Arguments validation failed for function '$name' called as last function of this log:\n\n" . $serializedFunctionCalls . "\n\n
            (!!Arguments maight have been filled with default values!!)"
		);
	}

	/**
	 * @param string $functionName
	 * @param array $functionArtuments
	 *
	 * @return array
	 */
	protected static function fillDefaultArguments($functionName, array $functionArtuments)
	{
		$functionArguments = self::getFunctionArguments($functionName);

		$argumentsCount = count($functionArtuments);
		if ($argumentsCount < count($functionArguments)) {
			$missingArguments = array_slice($functionArguments, $argumentsCount);
			foreach ($missingArguments as $missingArgument) {
				if ($missingArgument instanceof RequiredArgument) { //argument is not optional
					break;
				}
				$functionArtuments[] = $missingArgument;
			}
		}

		return $functionArtuments;
	}

	/**
	 * @param string $functionName
	 *
	 * @return mixed
	 */
	public static function getResult($functionName)
	{
		return ResultProvider::getResultForFunction($functionName);
	}

	/**
	 * @return string
	 */
	protected static function getNextExpectedFunction()
	{
		if (!array_key_exists('functions', self::$expectedCalls)) {
			return null;
		}

		if (!array_key_exists(0, self::$expectedCalls['functions'])) {
			return null;
		}
		return array_shift(self::$expectedCalls['functions']);
	}

	/**
	 * @return array|null
	 */
	protected static function getNextExpectedArguments()
	{
		if (!array_key_exists('arguments', self::$expectedCalls)) {
			return null;
		}

		if (!array_key_exists(0, self::$expectedCalls['arguments'])) {
			return null;
		}
		return array_shift(self::$expectedCalls['arguments']);
	}

	/**
	 * @param string $functionName
	 * @param array $functionArguments
	 * @param mixed $expectedResult
	 *
	 * @return void
	 */
	public static function expectFunctionCall($functionName, array $functionArguments = null, $expectedResult = null)
	{
		self::addExpectedFunctionCall($functionName, $functionArguments);
		ResultProvider::addResultForFunction($functionName, $expectedResult);
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return void
	 */
	protected static function addExpectedFunctionCall($name, array $arguments = null)
	{
		self::addExpectedFunction($name);
		self::addExpectedArguments($arguments);
	}

	/**
	 * @param string $name
	 *
	 * @return void
	 */
	protected static function addExpectedFunction($name)
	{
		self::$expectedCalls['functions'][] = $name;
	}

	/**
	 * @param array $arguments
	 *
	 * @return void
	 */
	protected static function addExpectedArguments(array $arguments = null)
	{
		self::$expectedCalls['arguments'][] = $arguments;
	}

	/**
	 * @return void
	 */
	public static function reset()
	{
		self::$expectedCalls = array();
		self::$loggedCalls = array();
		self::$passedFunctions = array();
		ResultProvider::reset();
	}
}
