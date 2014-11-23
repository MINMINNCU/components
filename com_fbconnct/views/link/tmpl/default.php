<?php 
/**
* @package 		Facebook Connect Extension (joomla 3.x)
* @copyright	Copyright (C) Computer - http://www.sanwebe.com. All rights reserved.
* @license		http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* @author		Saran Chamling
* @download URL	http://www.sanwebe.com
*/

// no direct access
defined('_JEXEC') or die('Restricted access');
$document = JFactory::getDocument();
$document->addStyleSheet(JRoute::_('components/com_fbcnt/assets/css/style.css'));
$user = JFactory::getUser();

$mainframe = JFactory::getApplication();
$fb 		= fbcntController::try_connect();
$uid 		= $fb['fbid'];
$me 		= $fb['fbdetails'];
$getappid 	= $fb['appid'];

$fbflnames = $me["first_name"]." ".$me["last_name"];
$FacebookUserInBothTable = fbcntController::count_fbuserid($uid);

$welcometext	= JText::_('COM_FBCNT_FOUNDANACCOUNT');

if(!$uid)
{
	$mainframe->enqueueMessage(JText::_('COM_FBCNT_NOTCONNECTED'), 'error');
}
elseif(!$user->get('guest'))
{
	$mainframe->enqueueMessage(JText::_('COM_FBCNT_ALREADYLOGGEDIN'), 'error');
	$mainframe->redirect(JRoute::_(JURI::base()));
}
elseif($FacebookUserInBothTable>0)
{
	$mainframe->enqueueMessage(JText::_('COM_FBCNT_ERROROCC'), 'error');
	$mainframe->redirect(JRoute::_(JURI::base()));
}
else
{
?>
<div id="form-wapper">
    <div id="form-inner">
        <div id="MainFormContent">
        <fieldset>
            <legend>Link to Existing Account</legend>
            <div align="center" class="fbconnectNote"><?php echo $welcometext; ?></div>
            <form id="NewUserReg" name="NewUserReg" method="post" action="">
            <label for="username" id="usernameLB"><?php echo JText::_('COM_FBCNT_USERNAME'); ?></label>
            <input type="text" name="username" id="username" />
            <label for="password" id="passwordLB"><?php echo JText::_('COM_FBCNT_PASSWORD'); ?></label>
            <input type="password" name="password" id="password" />
            <label for="email" id="emailLB"><?php echo JText::_('COM_FBCNT_YOUREMAIL'); ?></label><input type="text" name="email" id="email" value="<?php echo $me['email']; ?>" disabled="disabled" />
            <button type="submit"><?php echo JText::_('COM_FBCNT_LINKBUTLVL'); ?></button>
            <input type="hidden" name="task" value="linkuser" />
            <input type="hidden" name="re" value="<?php echo JRequest::getVar('re'); ?>" />
            <?php echo JHTML::_( 'form.token' ); ?>           
             </form>
            <div align="center" style="margin-top:10px;font-size:12px;color:#666666;">Powered by : <a href="http://www.saaraan.com" target="_blank">saaraan</a></div>
        </fieldset>
        </div>

    </div>
</div>
<?php
}
?>