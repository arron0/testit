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
 * AbstractClassToTest class definition
 *
 * @package Arron
 * @subpackage TestIt
 * @subpackage Tests
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
abstract class AbstractDependency implements ITest
{
    public function __construct()
    {
    }
}