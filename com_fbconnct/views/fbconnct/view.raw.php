<?php
/**
* @package 		Facebook Connect Extension (joomla 3.x)
* @copyright	Copyright (C) Computer - http://www.sanwebe.com. All rights reserved.
* @license		http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* @author		Saran Chamling
* @download URL	http://www.sanwebe.com
*/

defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.application.component.view');

class fbconnctViewfbconnct extends JViewLegacy {
	function display($tpl = null)
	{	
		parent::display('raw');
	}

}

?>