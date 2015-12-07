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
 * ContentModel class definition
 *
 * @package Arron
 * @subpackage Examples
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
class ContentModel
{
	/** @var IContentStorage */
	protected $storage;

	public function __construct(IContentStorage $contentStorage)
	{
		$this->storage = $contentStorage;
	}

	public function save($id, $content)
	{
		return $this->storage->save($id, $content);
	}

	public function load($id)
	{
		try {
			$content = $this->storage->load($id);
		} catch (ContentNotFoundException $e) {
			$content = 'There is no content yet.';
		}
		return $content;
	}

	public function delete($id)
	{
		$this->storage->delete($id);
	}
}