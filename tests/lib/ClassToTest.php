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
 * ClassToTest class definition
 *
 * @package Arron
 * @subpackage TestIt
 * @subpackage Tests
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
class ClassToTest
{
	/**
	 * @var ITest
	 */
	protected $dependency1;

	/**
	 * @var ITest
	 */
	protected $dependency2;

    /**
     * @var ITest
     */
	protected $dependency3;

	protected $protectedProperty = 1;

	public function __construct(ITest $dependency1, ITest $dependency2, AbstractDependency $dependecy3)
	{
		$this->dependency1 = $dependency1;
		$this->dependency2 = $dependency2;
	}

	public function callDependency($parameter)
	{
		return $this->dependency1->doSomething($parameter);
	}

	protected function callDependencyFromProtectedMethod($parameter)
	{
		return $this->dependency1->doSomething($parameter);
	}


	public function callDependencies($parameter1, $parameter2)
	{
		$returnValue1 = $this->dependency1->doSomething($parameter1);
		$returnValue2 = $this->dependency2->doSomething($parameter2);

		return array($returnValue1, $returnValue2);
	}

	public function callMethodWithDefaultParameter($mandatoryParameter)
	{
		$this->dependency1->methodWithDefaultParameter($mandatoryParameter);
	}

	public function callGlobalFunctionRand($min, $max)
	{
		return rand($min, $max);
	}

	public function setProtectedProperty($value)
	{
		$this->protectedProperty = $value;
	}

	public function getProtectedProperty()
	{
		return $this->protectedProperty;
	}



}


class TestException extends \Exception
{

}
 