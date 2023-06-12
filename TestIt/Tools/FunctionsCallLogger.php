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
	 * @var array<string>
	 */
	private static array $loggedCalledFunctions = [];

	/**
	 * @var array<array<mixed>>
	 */
	private static array $loggedCalledArguments = [];

	/**
	 * @var array<string>
	 */
	private static array $exceptedCalledFunctions = [];

	/**
	 * @var array<array<mixed>|null>
	 */
	private static array $expectedCalledArguments = [];

	/**
	 * @var string[]
	 */
	private static array $passedFunctions = [];

	private static ?MockFactory $mockFactory = null;

	protected static function getMockFactory(): MockFactory
	{
		if (self::$mockFactory === null) {
			self::$mockFactory = MockFactory::getInstance();
		}
		return self::$mockFactory;
	}

	/**
	 * @return array<string, mixed>
	 */
	protected static function getFunctionArguments(string $identificator): array
	{
		return self::getMockFactory()->getMethodParameters($identificator);
	}

	/**
	 * @param mixed[] $arguments
	 * @return mixed
	 */
	public static function __callStatic(string $name, array $arguments)
	{
		$functionName = str_replace('-', '::', $name);
		return self::processFunctionCall($functionName, $arguments);
	}

	/**
	 * @return string[]
	 */
	public static function getLoggedFunctions(): array
	{
		return self::$loggedCalledFunctions;
	}

	/**
	 * @return mixed[]
	 */
	public static function getLoggedArgumets(): array
	{
		return self::$loggedCalledArguments;
	}

	/**
	 * @return mixed[]
	 */
	public static function getExpectedFunctionArguments(): array
	{
		return self::$expectedCalledArguments;
	}

	/**
	 * @return string[]
	 */
	public static function getExpectedFunctions(): array
	{
		return self::$exceptedCalledFunctions;
	}

	/**
	 * @param mixed[] $arguments
	 * @return mixed
	 */
	public static function processFunctionCall(string $name, array $arguments)
	{
		self::logFunctionCall($name, $arguments);
		self::validateFunctionCall($name, $arguments);
		return self::getResult($name);
	}

	/**
	 * @param array<mixed> $arguments
	 */
	protected static function logFunctionCall(string $name, array $arguments): void
	{
		self::logFunction($name);
		self::logArguments($arguments);
	}

	protected static function logFunction(string $name): void
	{
		self::$loggedCalledFunctions[] = $name;
	}

	/**
	 * @param mixed[] $arguments
	 */
	protected static function logArguments(array $arguments): void
	{
		self::$loggedCalledArguments[] = $arguments;
	}

	/**
	 * @param mixed[] $arguments
	 * @throws FunctionsCallLoggerException
	 */
	protected static function validateFunctionCall(string $name, array $arguments): void
	{
		self::validateFunctionName($name, $arguments);
		self::validateFunctionArguments($name, $arguments);
	}

	/**
	 * @param mixed[] $arguments
	 */
	protected static function validateFunctionName(string $name, array $arguments): void
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
	 * @param mixed[] $arguments
	 */
	protected static function validateFunctionArguments(string $name, array $arguments): void
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
	 * @param mixed[] $functionArtuments
	 * @return mixed[]
	 */
	protected static function fillDefaultArguments(string $functionName, array $functionArtuments): array
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
	 * @return mixed
	 * @throws \Exception
	 */
	public static function getResult(string $functionName)
	{
		return ResultProvider::getResultForFunction($functionName);
	}

	protected static function getNextExpectedFunction(): ?string
	{
		if (count(self::$exceptedCalledFunctions) < 1) {
			return null;
		}

		return array_shift(self::$exceptedCalledFunctions);
	}

	/**
	 * @return mixed[]|null
	 */
	protected static function getNextExpectedArguments(): ?array
	{
		if (count(self::$expectedCalledArguments) < 1) {
			return null;
		}

		return array_shift(self::$expectedCalledArguments);
	}

	/**
	 * @param array<mixed> $functionArguments
	 * @param mixed $expectedResult
	 */
	public static function expectFunctionCall(string $functionName, array $functionArguments = null, $expectedResult = null): void
	{
		self::addExpectedFunctionCall($functionName, $functionArguments);
		ResultProvider::addResultForFunction($functionName, $expectedResult);
	}

	/**
	 * @param mixed[] $arguments
	 */
	protected static function addExpectedFunctionCall(string $name, array $arguments = null): void
	{
		self::addExpectedFunction($name);
		self::addExpectedArguments($arguments);
	}

	/**
	 * @param string $name
	 */
	protected static function addExpectedFunction(string $name): void
	{
		self::$exceptedCalledFunctions[] = $name;
	}

	/**
	 * @param mixed[] $arguments
	 */
	protected static function addExpectedArguments(array $arguments = null): void
	{
		self::$expectedCalledArguments[] = $arguments;
	}

	public static function reset(): void
	{
		self::$exceptedCalledFunctions = [];
		self::$expectedCalledArguments = [];
		self::$loggedCalledFunctions = [];
		self::$loggedCalledArguments = [];
		self::$passedFunctions = [];
		ResultProvider::reset();
	}
}
