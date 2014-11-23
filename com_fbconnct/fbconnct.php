<?php
/**
* @package 		Facebook Connect Extension (joomla 3.x)
* @copyright	Copyright (C) Computer - http://www.sanwebe.com. All rights reserved.
* @license		http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* @author		Saran Chamling
* @download URL	http://www.sanwebe.com
*/
defined('_JEXEC') or die('Restricted access');

// Require the base controller
require_once( JPATH_COMPONENT.DIRECTORY_SEPARATOR.'controller.php' );

// check curl before we move in
if(!fbconnctController::iscurlinstalled())
{
	$mainframe =& JFactory::getApplication();
	$mainframe->enqueueMessage(JText::_('Facebook Connect requires Curl PHP Extension!'), 'error');
	$mainframe->redirect(JURI::base());	
}

if (!class_exists('FacebookApiException')) {
	require_once( JPATH_COMPONENT.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'facebook.php' );
}

$controller = JControllerLegacy::getInstance('fbconnct');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
?>