<?php
/**
 * Requires PHP Version 5.3 (min)
 *
 * @package Arron
 * @subpackage Examples
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Arron\Examples;

/**
 * ContentFileStorage class definition
 *
 * Class is using Nette SafeStream http://doc.nette.org/en/atomicity
 *
 * @package Arron
 * @subpackage Examples
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
class ContentFileStorage implements IContentStorage
{
	protected $directory;

	public function __construct($dataDir)
	{
		$this->directory = $dataDir . '/content';
		$this->ensureDirectoryCreated();
	}

	public function save($id, $content)
	{
		$file = $this->getFilePath($id);
		$success = file_put_contents('safe://' . $file, $content);
		if ($success === FALSE) {
			throw new ContentIOException('Can not write content into file ' . $file);
		}
	}

	public function load($id)
	{
		$file = $this->getFilePath($id);
		if (file_exists($file)) {
			$content = file_get_contents('safe://' . $file);
			if ($content === FALSE) {
				throw new ContentIOException('Can not read content from file ' . $file);
			}
			return $content;
		}
		throw new ContentNotFoundException('There is no such content as ' . $file);
	}

	public function delete($id)
	{
		$fileName = $this->getFilePath($id);
		if (file_exists($fileName)) {
			if (!unlink($fileName)) {
				throw new ContentIOException('Can not delete file ' . $fileName);
			}
		}
	}

	protected function ensureDirectoryCreated()
	{
		if (!is_dir($this->directory)) {
			umask(0000);
			if (!mkdir($this->directory, 0777)) {
				throw new \RuntimeException('Can not create directory ' . $this->directory);
			}
		}
	}

	protected function getFilePath($id)
	{
		return $this->directory . '/' . $this->formatFileName($id);
	}

	protected function formatFileName($id)
	{
		return $id . '.content';
	}
}
