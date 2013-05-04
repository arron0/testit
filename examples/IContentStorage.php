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
 * IContentStorage interface definition
 *
 * @package Arron
 * @subpackage Examples
 * @author Tomáš Lembacher <tomas.lembacher@seznam.cz>
 * @license http://opensource.org/licenses/MIT MIT
 */
interface IContentStorage
{
	public function save($id, $content);

	public function load($id);

	public function delete($id);
}

class ContentException extends \Exception
{
}

class ContentNotFoundException extends ContentException
{
}

class ContentIOException extends ContentException
{
}
