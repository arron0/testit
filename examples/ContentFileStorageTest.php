<?php
/**
 * Requires PHP Version 5.3 (min)
 *
 * @package
 * @subpackage
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license
 */
namespace Arron\Examples;

require_once 'IContentStorage.php';
require_once 'ContentFileStorage.php';
/**
 * ContentFileStorageTest class definition
 *
 * @package
 * @subpackage
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license
 */
class ContentFileStorageTest extends \Arron\TestIt\TestCase
{

	protected function setUp()
	{
		//global functions are mocked before parent setUp call, because they are used during test object creation
		$this->mockGlobalFunction('is_dir');
		$this->mockGlobalFunction('umask');
		$this->mockGlobalFunction('mkdir');
		$this->mockGlobalFunction('file_put_contents');
		$this->mockGlobalFunction('file_exists');
		$this->mockGlobalFunction('file_get_contents');
		$this->mockGlobalFunction('unlink');
		parent::setUp();
	}

	protected function inicializationExpectations()
	{
		$this->expectDependencyCall('global', 'is_dir', array('testDir/content'), TRUE);
	}

	/**
	 * @return object
	 */
	protected function createTestObject()
	{
		return new ContentFileStorage('testDir');
	}

	public function testConstructorEnsureDirectoryDirectoryCreated()
	{
		$this->expectDependencyCall('global', 'is_dir', array('testDir/content'), TRUE);

		$this->createTestObject();
	}

	public function testConstructorEnsureDirectoryDirectoryNeedsToBeCreated()
	{
		$this->expectDependencyCall('global', 'is_dir', array('testDir/content'), FALSE);
		$this->expectDependencyCall('global', 'umask', array());
		$this->expectDependencyCall('global', 'mkdir', array('testDir/content', 0777), TRUE);

		$this->createTestObject();
	}

	public function testConstructorEnsureDirectoryDirectoryCreationFailed()
	{
		$this->expectDependencyCall('global', 'is_dir', array('testDir/content'), FALSE);
		$this->expectDependencyCall('global', 'umask', array());
		$this->expectDependencyCall('global', 'mkdir', array('testDir/content', 0777), FALSE);

		$this->setExpectedException('\RuntimeException');

		$this->createTestObject();
	}

	public function testSave()
	{
		$id = 'testId';
		$content = 'some fancy test content';

		$this->expectDependencyCall('global', 'file_put_contents', array('safe://testDir/content/testId.content', $content), TRUE);
		$this->getTestObject()->save($id, $content);
	}

	public function testSaveThrowException()
	{
		$id = 'testId';
		$content = 'some fancy test content';

		$this->expectDependencyCall('global', 'file_put_contents', array('safe://testDir/content/testId.content', $content), FALSE);

		$this->setExpectedException('\Arron\Examples\ContentIOException');

		$this->getTestObject()->save($id, $content);
	}

	public function testLoad()
	{
		$id = 'testId';
		$expectedContent = 'some test content';

		$this->expectDependencyCall('global', 'file_exists', array('testDir/content/testId.content'), TRUE);
		$this->expectDependencyCall('global', 'file_get_contents', array('safe://testDir/content/testId.content'), $expectedContent);

		$returnedResult = $this->getTestObject()->load($id);

		$this->assertEquals($expectedContent, $returnedResult);
	}

	public function testLoadFileNotFound()
	{
		$id = 'testId';

		$this->expectDependencyCall('global', 'file_exists', array('testDir/content/testId.content'), FALSE);

		$this->setExpectedException('\Arron\Examples\ContentNotFoundException');

		$this->getTestObject()->load($id);
	}

	public function testLoadFileReadError()
	{
		$id = 'testId';

		$this->expectDependencyCall('global', 'file_exists', array('testDir/content/testId.content'), TRUE);
		$this->expectDependencyCall('global', 'file_get_contents', array('safe://testDir/content/testId.content'), FALSE);

		$this->setExpectedException('\Arron\Examples\ContentIOException');

		$this->getTestObject()->load($id);
	}

	public function testDelete()
	{
		$id = 'testId';

		$this->expectDependencyCall('global', 'file_exists', array('testDir/content/testId.content'), TRUE);
		$this->expectDependencyCall('global', 'unlink', array('testDir/content/testId.content'), TRUE);

		$this->getTestObject()->delete($id);
	}

	public function testDeleteFileNotFound()
	{
		$id = 'testId';

		$this->expectDependencyCall('global', 'file_exists', array('testDir/content/testId.content'), FALSE);

		$this->getTestObject()->delete($id);
	}

	public function testDeleteFileDeleteError()
	{
		$id = 'testId';

		$this->expectDependencyCall('global', 'file_exists', array('testDir/content/testId.content'), TRUE);
		$this->expectDependencyCall('global', 'unlink', array('testDir/content/testId.content'), FALSE);

		$this->setExpectedException('\Arron\Examples\ContentIOException');

		$this->getTestObject()->delete($id);
	}
}