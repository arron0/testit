<?php

namespace Arron\TestIt\Tools;

/**
 * ResultProvider class definition
 *
 * @package Arron
 * @subpackage TestIt
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
class ResultProvider
{
	/** @var array */
	public static $preparedResult = array();

	/**
	 * @param string $name
	 * @param mixed|Exception $result
	 *
	 * @return void
	 */
	public static function addResultForFunction($name, $result)
	{
		self::$preparedResult[$name][] = $result;
	}

	/**
	 * @param string $functionName
	 *
	 * @return mixed
	 *
	 * @throws FunctionsCallLoggerException
	 * @throws \Exception
	 */
	public static function getResultForFunction($functionName)
	{
		if (array_key_exists($functionName, self::$preparedResult)) {
			$result = array_shift(self::$preparedResult[$functionName]);

			if ($result instanceof \Exception) {
				throw $result;
			}
			return $result;
		}
		throw new FunctionsCallLoggerException("Prepared result for function $functionName was not found.");
	}

	/**
	 * @return void
	 */
	public static function reset()
	{
		self::$preparedResult = array();
	}
}
