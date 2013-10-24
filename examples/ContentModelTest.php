<?php
/**
 * Requires PHP Version 5.3 (min)
 *
 * @package Arron
 * @subpackage Examples
 * @subpackage Tests
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Arron\Examples\Tests;

use Arron\Examples\ContentModel;

require_once 'IContentStorage.php';
require_once 'ContentModel.php';
/**
 * ContentModelTest class definition
 *
 * @package Arron
 * @subpackage Examples
 * @subpackage Tests
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
class ContentModelTest extends \Arron\TestIt\TestCase
{

	/**
	 * @return object
	 */
	protected function createTestObject()
	{
		return new ContentModel($this->getMockedClass('\Arron\Examples\IContentStorage', 'storage'));
	}

	public function testSave()
	{
		$id = 'idForTest';
		$content = 'content for testing';

		$this->expectDependencyCall('storage', 'save', array($id, $content));

		$this->getTestObject()->save($id, $content);
	}

	public function testLoad()
	{
		$id = 'idForTest';
		$expectedResult = 'some test content';

		$this->expectDependencyCall('storage', 'load', array($id), $expectedResult);

		$returnedResult = $this->getTestObject()->load($id);

		$this->assertEquals($expectedResult, $returnedResult);
	}

	public function testLoadNotFound()
	{
		$id = 'idForTest';
		$expectedResult = 'There is no content yet.';

		$this->expectDependencyCall('storage', 'load', array($id), new ContentNotFoundException());

		$returnedResult = $this->getTestObject()->load($id);

		$this->assertEquals($expectedResult, $returnedResult);
	}

	public function testDelete()
	{
		$id = 'idForTest';

		$this->expectDependencyCall('storage', 'delete', array($id));

		$this->getTestObject()->delete($id);
	}

	public function testPhpUnitMockNoExpectationSetPass()
	{
		$storageMock = $this->getMock('\Arron\Examples\IContentStorage');
		$testObject = new ContentModel($storageMock);

		$returnedResult = $testObject->load('testID');
	}

	public function testPhpUnitMock()
	{
		$storageMock = $this->getMock('\Arron\Examples\IContentStorage');
		$testObject = new ContentModel($storageMock);

		$storageMock->expects($this->at(0))->method('load');
		$storageMock->expects($this->at(1))->method('load');

		$returnedResult = $testObject->load('testID');
	}
}
