<?php
/**
 * Requires PHP Version 5.3 (min)
 *
 * @package
 * @subpackage
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license
 */

namespace Arron\TestIt\Tools;

use PHPUnit\Framework\MockObject\Generator;
use PHPUnit\Framework\MockObject\RuntimeException;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * MockGenerator class definition
 *
 * @package
 * @subpackage
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license
 */
class MockGenerator extends Generator
{
	/**
	 * Returns the parameters of a function or method.
	 *
	 * @param  ReflectionFunction $function
	 * @param  boolean $forCall
	 *
	 * @return string
	 * @since  Method available since Release 2.0
	 */
	public function getFunctionParameters(ReflectionFunction $function, $forCall = FALSE)
	{
		$parameters = array();

		foreach ($function->getParameters() as $i => $parameter) {
			$name = '$' . $parameter->getName();

			/* Note: PHP extensions may use empty names for reference arguments
			 * or "..." for methods taking a variable number of arguments.
			 */
			if ($name === '$' || $name === '$...') {
				$name = '$arg' . $i;
			}

			$default = '';
			$reference = '';
			$typeHint = '';

			if (!$forCall) {
				if ($parameter->isArray()) {
					$typeHint = 'array ';
				} elseif ((defined('HHVM_VERSION') || version_compare(PHP_VERSION, '5.4.0', '>='))
						&& $parameter->isCallable()
				) {
					$typeHint = 'callable ';
				} else {
					try {
						$class = $parameter->getClass();
					} catch (ReflectionException $e) {
						throw new RuntimeException(
								sprintf(
										'Cannot mock %s::%s() because a class or ' .
										'interface used in the signature is not loaded',
										'global',
										$function->getName()
								),
								0,
								$e
						);
					}

					if ($class !== NULL) {
						$typeHint = $class->getName() . ' ';
					}
				}

				if ($parameter->isDefaultValueAvailable()) {
					$value = $parameter->getDefaultValue();
					$default = ' = ' . var_export($value, TRUE);
				} else {
					if ($parameter->isOptional()) {
						$default = ' = null';
					}
				}
			}

			if ($parameter->isPassedByReference()) {
				$reference = '&';
			}

			$parameters[] = $typeHint . $reference . $name . $default;
		}

		return join(', ', $parameters);
	}
}
