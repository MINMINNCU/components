<?php
/**
 * @package    CComment
 * @author     DanielDimitrov <daniel@compojoom.com>
 * @date       10.11.13
 *
 * @copyright  Copyright (C) 2008 - 2013 compojoom.com . All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die('Restricted access');

/**
 * Class CcommentHelperJs
 *
 * @since  5.0.3
 */
class CcommentHelperJs
{
	/**
	 * Create a JS file with the passed content and returns the path to it
	 *
	 * @param   string  $name     - the name of the file (will be transformed to md5 hash)
	 * @param   string  $content  - the contents of the file
	 *
	 * @return string
	 */
	public static function createFile($name, $content)
	{
		$path = '/com_comment/init/' . md5($name) . '.js';

		if (!file_exists($path))
		{
			jimport('joomla.filesystem.file');
			JFile::write(JPATH_CACHE . $path, $content);
		}

		return 'cache' . $path;
	}
}
