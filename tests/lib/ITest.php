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

namespace Arron\TestIt\Tests\TestNamespace;


/**
 * ITest interface definition
 *
 * @package Arron
 * @subpackage TestIt
 * @subpackage Tests
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
interface ITest {
	public function doSomething($parameter);
	public function methodWithDefaultParameter($parameter, $defaultParameter = 'defaultValue');
} 