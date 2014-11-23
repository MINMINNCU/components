<?php
/**
* @package 		Facebook Connect Extension (joomla 3.x)
* @copyright	Copyright (C) Computer - http://www.saaraan.com. All rights reserved.
* @license		http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* @author		Saran Chamling
* @download URL	http://www.sanwebe.com
*/

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class fbconnctController extends JControllerLegacy
{
	function display($cachable = false, $urlparams = false) {
			switch (JRequest::getVar('task')) {
				case 'login':
					$this->LoginJUser();
					break;
				case 'create':
					$this->create_user();
					break;
				case 'create_proceed':
					$this->create_proceed();
					break;
				case 'logout':
					$this->logout();
					break;
				case 'switch':
					 $this->distroy_fb_session();
					 break;
				default:
					break;
			}	
			switch (JRequest::getVar( 'view' )){
				default:
					JRequest::setVar('view', 'fbconnct' );
		}
		parent::display();
	}
	
	
	#################### Login User #######################
	function LoginJUser()
	{
		$db = JFactory::getDBO();
		$session = JFactory::getSession();
		jimport( 'joomla.user.helper' );
		JPluginHelper::importPlugin('user');
		$user = clone(JFactory::getUser());
		
		$myparams = JComponentHelper::getParams('com_fbconnct');
		$mainframe = JFactory::getApplication();
		$redirAfterLogin = $myparams->get('redirect-after-login');
		
		$fb 		= $this->try_connect();
		$uid 		= $fb['fbid'];
		$me 		= $fb['fbdetails'];
		$getappid 	= $fb['appid'];
		$uemail 	= $me['email'];
		$intdatetime = time();
		
		if(fbconnctController::count_jemail($uemail))
		{
			
			$db->setQuery("SELECT id,name,username FROM #__users WHERE email='$uemail'");
			$userDetails = $db->loadObjectList();
			$row = $userDetails[0];
			
			$options = array();
			$options['action'] = 'core.login.site';
			
			$response = new stdClass();
			$response->username = $row->username;	
			$j_uid = $row->id;
			if(!fbconnctController::count_j_fb_user($j_uid )) 
			{
				$fbinsertquary="INSERT INTO #__facebook_joomla_connect(joomla_userid,facebook_userid,joined_date,linked) VALUES ($j_uid,$uid,$intdatetime,1)";
				$db->setQuery($fbinsertquary);
				$result = $db->query();
			}
	
			$result = $mainframe->triggerEvent('onUserLogin', array((array)$response, $options));
			
			fbconnctController::closeWindow($redirAfterLogin);
		}else{
				
				$session->set( 'user_details', $me );
				$createUser_url = JRoute::_('index.php?task=create&option=com_fbconnct&format=raw');
				$mainframe->redirect($createUser_url);

		}
	}
	
	######### create a new user ############
	public static function create_proceed()
	{
		
		jimport( 'joomla.application.application' );
		jimport( 'joomla.user.helper' );
		jimport( 'joomla.utilities.utility' );
		JPluginHelper::importPlugin('user');
		jimport( 'joomla.environment.request' );
		
		JRequest::checkToken() or die( 'Invalid Token' );
		//JSession::checkToken() or die( 'Invalid Token' );
		
		$session 		= JFactory::getSession();
		$db 			= JFactory::getDBO();
		$user 			= clone(JFactory::getUser());
		$usersConfig	= JComponentHelper::getParams('com_users');
		$session_me 	= $session->get( 'user_details', '' );
		$myparams 		= JComponentHelper::getParams('com_fbconnct');
		
		$mainframe = JFactory::getApplication();
		$redirAfterReg = $myparams->get('redirect-after-reg');
		$postToFB 	= $myparams->get('post-to-facebook');
		$UserFBmsg 	= $myparams->get('facebook-message');
		
		if(!$user->get('guest'))
		{
			die($user->name.JText::_('COM_FBCONNCT_ALREADY_LOGGED_IN'));
		}

		$uid 				= $session_me['id'];
		$username 			= JRequest::getVar('username', '');
		$username 			= fbconnctController::clean_username($username);
		$createUser_url 	= JRoute::_('index.php?task=create&option=com_fbconnct&format=raw');
		$user_allow_post 	= JRequest::getVar('fbpost', '');
		
		
		if(strlen($username)<5)
		{
			$fb_error = array('<div class="error">'.JText::_('COM_FBCONNCT_USERNAMETOOSHORT').'</div>');
			$session->set( 'fb_custom_error', $fb_error);
			$mainframe->redirect($createUser_url);
			exit();
		}
		
		if(fbconnctController::count_jusername($username)>0)
		{
			$fb_error = array('<div class="error">'.JText::_('COM_FBCONNCT_USERNAMEINUSE').'</div>');
			$session->set( 'fb_custom_error', $fb_error);
			$mainframe->redirect($createUser_url);
			exit();
		}
		
		//proceed to creating user
		$session_me 	= $session->get( 'user_details', '' );
		$pathway    	= $mainframe->getPathway();
		$newUsertype 	= $usersConfig->get( 'new_usertype', 2);
			
		if (!$newUsertype) {
			$newUsertype = 'Registered';
		}	
						
		$authorize  	= JFactory::getACL();
		$document   	= JFactory::getDocument();	
		$fullname 		= $session_me['name'];
		$email 			= $session_me['email'];
		$randomepass	= JUserHelper::genRandomPassword(5);
		$intdatetime 	= time();
		
		//email data
		$emailData = array();
		$emailData['name'] 		= $fullname;
		$emailData['username'] 	= $username;
		$emailData['email'] 	= $email;
		$emailData['temp_pass'] = $randomepass;
		$emailData['fbid'] 		= $uid;
		
		// binding process
		$userData= array();
		$userData['name'] 		= $fullname;
		$userData['username'] 	= $username;
		$userData['email'] 		= $email;
		$userData['password'] 	= $randomepass;
		$userData['password2'] 	= $randomepass;
		$userData['sendEmail'] 	= 0; 
		
		if (!$user->bind($userData, 'usertype' )) {
			$fb_error = array('<div class="error">'.$user->getError().'</div>');
			$session->set( 'fb_custom_error', $fb_error);
			$mainframe->redirect($createUser_url);
			exit();		
		}
		
		$user->set('groups', array($newUsertype));
		
		$user->set('id', 0); 
		$date = JFactory::getDate();   //j3 change
		$user->set('registerDate', $date->toSql()); //j3 change
		 
		if ($user->save())
		{
			$jomuserid = $user->get('id');
			
			if(fbconnctController::count_fb_user($uid)>=1)
			{
				$fbinsertquary="UPDATE #__facebook_joomla_connect SET joomla_userid=$jomuserid,joined_date=$intdatetime WHERE facebook_userid=$uid";
			}
			else
			{
				$fbinsertquary="INSERT INTO #__facebook_joomla_connect(joomla_userid,facebook_userid,joined_date,linked) VALUES ($jomuserid,$uid,$intdatetime,1)";
			}
			$db->setQuery($fbinsertquary);
			$result = $db->query();
			
			if ($result)
			{					
					$options = array();
					$options['action'] = 'core.login.site';
					
					$response = new stdClass();
					$response->username = $username;	
					$response->password = $randomepass;	

					$result = $mainframe->triggerEvent('onUserLogin', array((array)$response, $options));
					
					fbconnctController::emailUsers($emailData);
					fbconnctController::displayMessage(JText::_('COM_FBCONNCT_YOUR_MSG'),JText::_('COM_FBCONNCT_REGSUCCESS'),$redirAfterReg);
			}else{
				fbconnctController::emailUsers($emailData);
				fbconnctController::displayMessage(JText::_('COM_FBCONNCT_YOUR_MSG'),JText::_('COM_FBCONNCT_REGSUCCESS'),$redirAfterReg);
			}
			

			if($postToFB && $user_allow_post){
				fbconnctController::post_to_facebook($UserFBmsg, $emailData);
			}
		}

	}
	
	public static function displayMessage($title,$message,$location)
	{
		echo '<!DOCTYPE html><html><head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		echo '<title>'.$title.'</title>';
		if($location){
		echo '<script type="text/javascript">window.opener.location.href = "'.$location.'"; </script>';
		}else{
		echo '<script type="text/javascript">window.opener.location.href = window.opener.location.href;</script>';
		}
		echo '<style type="text/css">';
		echo 'body {background-color: #F2F2F2;margin-left: 0px;margin-top: 0px;margin-right: 0px;margin-bottom: 0px;color: #333333;}';
		echo '.message_wrap {padding: 20px;text-align: center;}';
		echo '.message_wrap h3 {margin: 5px;padding: 5px;color: #535353;font-size: 25px;border-bottom: 1px solid #DADADA;}';
		echo '.message_wrap .message {display: block;}';
		echo '.message_wrap .close_win {margin-top: 10px;display: block;}';
		echo '.msg_inner_wrap {background: #E7E7E7;padding: 10px;border: 1px solid #FFF;border-radius: 10px;box-shadow: 1px 1px 3px #B8B8B8;text-shadow: 1px 1px 1px #FFF1A8;height: 235px;}';
		echo '</style>';
		echo '</head><body><div class="message_wrap"><div class="msg_inner_wrap"><h3>'.$title.'</h3>';
		echo '<span class="message">'.$message.'</span>';
		echo '<span class="close_win"><a href="#" onClick="window.close();">'.JText::_('COM_FBCONNCT_YOUR_CLOSE_WIN').'</a></span>';
		echo '</div></div></body></html>';
	}	
	######### Generate sign-up page ############
	public static function create_user()
	{
		jimport('joomla.form.helper');
		$session = JFactory::getSession();
		$session_me = $session->get( 'user_details', '' );
		$fb_errors = $session->get( 'fb_custom_error', '' );
		$mainframe = JFactory::getApplication();
			
		$user = JFactory::getUser();
		$myparams 		= JComponentHelper::getParams('com_fbconnct');
		$postToFB 	= $myparams->get('post-to-facebook');
		$usersConfig = JComponentHelper::getParams('com_users');
		
		if (!$usersConfig->get('allowUserRegistration') && !$myparams->get('bypass-signup')){
			die(JText::_('COM_FBCONNCT_REGISTRATION_DISABLED'));
		}
			
		if(!$user->get('guest'))
		{
			die($user->name.JText::_('COM_FBCONNCT_ALREADY_LOGGED_IN'));
		}
		if(empty($session_me))
		{
			$reLogin_url = JURI::base().substr(JRoute::_('index.php?option=com_fbconnct&view=fbconnct&amp;task=login&format=raw'), strlen(JURI::base(true)) + 1);
			die('Session expired, <a href="'.$reLogin_url.'">Login again</a>!');
		}
		
		
		echo '<html>';
		echo '<head>';
		echo '<title>Sign up to Login</title>';
		echo '<meta name="robots" content="noindex, nofollow">';
		echo '<style type="text/css">
		body{font-family: verdana;background-color: #F9F9F9;margin: 0px;padding: 0px;}
		.create_user_wrp {margin: 0px;padding: 10px;}
		.create_user_wrp h3 {margin: 0px 0px 5px 0px;padding: 0px;color: #333;border-bottom: 1px solid #ECECEC;}
		.create_user_wrp label {font-size: 13px;}
		.create_user_wrp .inputbox {margin-left: 10px;border: 1px solid #999999;height: 28px;border-radius: 3px;padding-left: 5px;}
		.create_user_wrp fieldset {padding: 10px;border: 1px solid #DDD;border-radius: 10px;margin-top: 10px;margin-bottom: 10px;}
		.create_user_wrp fieldset label {display: block;font-weight: normal;}
		.create_user_wrp fieldset label span {margin-left: 40px;font-weight: bold;}
		.user_img{float:left;padding:10px;}
		.note{font-size: 11px;font-family: arial;color: #6B6B6B;}
		.note a{color: #6B6B6B;}
		.facebook-msg {font-size: 12px;margin-top: 5px;}
		.error{color: #F00;border: 1px solid #FDCACA;margin-left: 160px;padding: 10px;background: #FFE4E4;font-size: 12px;}
		legend{color: #B4B4B4;font-family: arial;font-size: 12px;text-shadow: 1px 1px 1px #FFF;}
		.username-wrp {margin-top: 10px;background:#A8CEE7;padding: 10px;margin-left:160px;}
		.user_img img{border-radius: 10px;}
		</style>';
		echo '</head>';
		echo '<body>';
		echo '<div class="user_img"><img src="https://graph.facebook.com/'.$session_me['id'].'/picture?width=150&height=270" border="0" width="150" height="270" /></div>';
		echo '<div class="create_user_wrp">';
		
		if(!empty($fb_errors))
		{
			foreach($fb_errors as $fb_error)
			{
				echo $fb_error;
			}
			$session->clear('fb_custom_error'); //clear error texts
		}
		
		echo '<h3>'.JText::_('COM_FBCONNCT_SIGNUP_WELCOME').' '.$session_me['name'].'</h3>';
		echo '<span class="note">'.JText::_('COM_FBCONNCT_SIGNUP_NOTE').'</span>';
		echo '<form action="'.JRoute::_('index.php?task=create&option=com_fbconnct&format=raw').'" method="POST">';
		echo '<div class="username-wrp"><label for="username">'.JText::_('COM_FBCONNCT_USERNAME').'<input type="text" name="username" class="inputbox" value="'.strtolower($session_me['first_name'].'_'.rand(1,100)).'" /></label>';
		echo '<label for="submit-btn">&nbsp;<input type="submit" value="'.JText::_('COM_FBCONNCT_SIGNUP').'" /></div>';
		echo '<input type="hidden" name="task" value="create_proceed" />';
		echo '<input type="hidden" name="option" value="com_fbconnct" />';
		echo '<input type="hidden" name="format" value="raw" />';
		echo JHtml::_( 'form.token' );
		if($postToFB)
		{
			echo '<div class="facebook-msg"><input type="checkbox" name="fbpost" value="1" checked> '.JText::_('COM_FBCONNCT_POST_TO_WALL').'</div>';
		}
		echo '</form>';
		echo '</body>';
		echo '<fieldset>';
		echo '<legend>'.JText::_('COM_FBCONNCT_YOUR_FBDETAILS').'</legend>';
		echo '<label>FB ID<span>'.$session_me['id'].'</span></label>';
		echo '<label>'.JText::_('COM_FBCONNCT_NAME').'<span>'.$session_me['name'].'</span></label>';
		echo '<label>'.JText::_('COM_FBCONNCT_EMAIL').'<span>'.$session_me['email'].'</span></label>';
		echo '</fieldset>';
		
		echo '</div>';
		echo '<span class="note"><a href="'.JRoute::_('index.php?task=switch&option=com_fbconnct&format=raw').'">'.JText::_('COM_FBCONNCT_REFRESH_FBSESSION').'</a> | <a href="http://www.sanwebe.com/2012/04/facebook-connect-2-0-for-joomla" target="_blank">Sanwebe.com</a></span>';
		echo '</html>';

	}
	
	######### Distroy FB session ############
	public static function distroy_fb_session()
	{
		$myparams = JComponentHelper::getParams('com_fbconnct');
		
		$mainframe = JFactory::getApplication();
		$getappid = $myparams->get('appid');
		$getappsec = $myparams->get('appsecret');

		$facebook = new Facebook(array(
			'appId' => $getappid,
			'secret' => $getappsec,
		));
		
		$fbuser = $facebook->getUser();
		$loginUser_url = JRoute::_('index.php?task=login&option=com_fbconnct&format=raw');
		
		if ($fbuser) {
			$facebook->destroySession();
			$mainframe->redirect($loginUser_url);	
		}
	}	
		
	######### try connect to facebook ############
	public static function try_connect() 
	{ 	
		$myparams = JComponentHelper::getParams('com_fbconnct');
		$mainframe = JFactory::getApplication();
		
		$getappid = $myparams->get('appid');
		$getappsec = $myparams->get('appsecret');
		$fbpermissions = $myparams->get('fbpermissions');
		
		$facebook = new Facebook(array(
			'appId' => $getappid,
			'secret' => $getappsec,
		));
		
		$fbuser = $facebook->getUser();
		
		if ($fbuser) {
			try {
				// Proceed knowing you have a logged in user who's authenticated.
				$access_token = $facebook->getAccessToken();
				$me = $facebook->api('/me');
			} catch (FacebookApiException $e) {
			//error_log($e);
			//$mainframe->enqueueMessage('Facebook Says :'. $e->getMessage(), 'error');
			}
		}

		// redirect user to facebook login page if fresh login requires
		if (!$fbuser){
		
			$sef_enabled = $mainframe->getCfg('sef');
			//$sef_rewrite = $mainframe->getCfg('sef_rewrite');
			
			if(!$sef_enabled){
				$fb_redirect_url = JURI::base().JRoute::_('index.php?option=com_fbconnct&amp;task=login&amp;view=fbconnct&amp;format=raw');
			}else{
				$fb_redirect_url = JURI::base().substr(JRoute::_('index.php?option=com_fbconnct&amp;task=login&amp;view=fbconnct&amp;format=raw'), strlen(JURI::base(true)) + 1);
			}
			
			$loginUrl = $facebook->getLoginUrl(array('display'=>'popup','scope'=>$fbpermissions,'redirect_uri'=>$fb_redirect_url));
			$mainframe->redirect($loginUrl);	
		}
		if(!isset($me["email"]))
		{
			die("Could not get Email address, make sure you are using valid email address at Facebook or have granted the email permission!");
		}
		$fbdata = array('fbid'=>$fbuser,'fbdetails'=>$me,'appid'=>$getappid, 'fbtoken'=>$access_token,'fbobject'=>$facebook);
		return $fbdata;
	}
	
	public static function post_to_facebook($message, $phraser) 
	{ 
		
		$mainframe = JFactory::getApplication();
		$myparams = JComponentHelper::getParams('com_fbconnct');
		$getappid = $myparams->get('appid');
		$getappsec = $myparams->get('appsecret');
		$fbpermissions = $myparams->get('fbpermissions');
		$sitename 		= $mainframe->getCfg('sitename');
		
		$facebook = new Facebook(array(
			'appId' => $getappid,
			'secret' => $getappsec,
		));
		
		$fbuser = $facebook->getUser();
		
		$MessageBodyArray = array(
		'{fullname}' => $phraser['name'], 
		'{sitename}' => $sitename, 
		'{siteurl}' => JURI::base(), 
		'{username}' =>$phraser['username'], 
		'{profileid}'=>$phraser['fbid']);
				
		$UserWallPostBody = fbconnctController::mail_body_phraser($message,$MessageBodyArray);
		
		$msg_body = array(
			'message' => $UserWallPostBody,
			);
			
	   if ($fbuser) {
		  try {

		
			$facebook->api('/me/feed', 'post', $msg_body );
			
			} catch (FacebookApiException $e) {
			echo 'Facebook error :'. $e->getMessage();
		  }
		}
	}
	########### get facebook logout URL ######
	public static function getLogout($redirectLocation)
	{
		$myparams = JComponentHelper::getParams('com_fbconnct');
		
		$mainframe = JFactory::getApplication();
		$getappid = $myparams->get('appid');
		$getappsec = $myparams->get('appsecret');

		$facebook = new Facebook(array('appId' => $getappid,'secret' => $getappsec,));
		$fbuser = $facebook->getUser();
		
			if ($fbuser){
				$me = $facebook->api('/me');
				return $facebook->getLogoutUrl(array('next'=>$redirectLocation));
			}else{
				return false;
			}
	}

	######### Logout user from Joomla ############
	public static function logout()
	{
		$myparams = JComponentHelper::getParams('com_fbconnct');
		
		$mainframe = JFactory::getApplication();
		$logout_type = $myparams->get('logout-type');

		$returnUrl 		=  base64_decode(JRequest::getVar('return'));
		
		$facebookLogout = fbconnctController::getLogout($returnUrl);// get logout url from facebook
		$mainframe->logout(); // log out from site
		
		if($logout_type=='fbws' && $facebookLogout) //if user is logged in to facebook
		{
				$mainframe->redirect($facebookLogout); // logout from facebook
		}else{
			$mainframe->redirect($returnUrl); //return back to homepage
		}		
	}

	######### email address #####################
	public static function emailUsers($emailData)
	{
		//$mail 	= JMail::getInstance();
		$mailer = JFactory::getMailer();

		$myparams = JComponentHelper::getParams('com_fbconnct');
		
		$mainframe = JFactory::getApplication();
		$usermailbody = $myparams->get('usermailbody','');
		$adminmailbody = $myparams->get('adminmailbody','');
		$usermailsubject = $myparams->get('usermailsubject','Your Registration Details at {sitename}');
		$adminmailsubject = $myparams->get('adminmailsubject','New Account Details for {fullname}');
		$adminhemails = $myparams->get('admin-email-to-notify');

		// Email 				
		$mailfrom 		= $mainframe->getCfg('mailfrom');
		$fromname 		= $mainframe->getCfg('fromname');

		$sitename 		= $mainframe->getCfg('sitename');
		
		$UserSubject	= JText::sprintf(JText::_('COM_FBCONNCT_EMAILSUBJECT'),$sitename );
		
		$MailBodyArray = array('{fullname}' => $emailData['name'], '{br}' => '<br />', '{sitename}' => $sitename, '{siteurl}' => JURI::base(), '{username}' => $emailData['username'], '{password}' =>$emailData['temp_pass'],'{profileid}'=>$emailData['fbid']);

		$UserBody 		= fbconnctController::mail_body_phraser($usermailbody,$MailBodyArray);
		$AdminBody 		= fbconnctController::mail_body_phraser($adminmailbody,$MailBodyArray); 

		$userMailSub	= fbconnctController::mail_body_phraser($usermailsubject,$MailBodyArray);
		$adminMailSub	= fbconnctController::mail_body_phraser($adminmailsubject,$MailBodyArray);

		$adminEmails = explode(',',$adminhemails);
		
		$sender = array($mailfrom, $fromname);
		$mailer->setSender($sender);

		// to all admins
		if($adminEmails)
		{
			$mailer->addRecipient($adminEmails);
			$mailer->setSubject($adminMailSub);
			$mailer->setBody($AdminBody);
			
						
			$send = $mailer->Send();
			if ( $send !== true ) {
				echo '<div style="color:red;margin:5px">Error sending email: ' . $send->__toString().'</div>';
			}
			
		}
		
		//to user
		$mailer->addRecipient($emailData['email']);
		$mailer->setSubject($userMailSub);
		$mailer->setBody($UserBody);
		$send = $mailer->Send();
		if ( $send !== true ) {
			echo '<div style="color:red;margin:5px">Error sending email: ' . $send->__toString().'</div>';
		}
		//end email		
	}
	
	
	######### mail phraser ############
	public static function mail_body_phraser($string,$ReplaceArray)
	{
		$result = str_replace(array_keys($ReplaceArray), array_values($ReplaceArray),$string);
		return $result;
	}

	#########user exist in fb table ############
	public static function count_juser($joomlaid)
	{
		$db = JFactory::getDBO();
		$query = "SELECT COUNT(*) FROM #__users INNER JOIN #__facebook_joomla_connect ON #__users.id=#__facebook_joomla_connect.joomla_userid WHERE #__facebook_joomla_connect.facebook_userid=$JoomlaUserid AND #__facebook_joomla_connect.linked=1";
		$db->setQuery($query);
		$count_user = $db->loadResult();
		return $count_user;
	}
	
	######### count this user in facebook_joomla_connect table ############
	public static function count_j_fb_user($joomlaid) 
	{
		$db = JFactory::getDBO();
		$query = "SELECT COUNT(*) FROM #__facebook_joomla_connect WHERE joomla_userid=".$joomlaid;
		$db->setQuery($query);
		$count_juserid = $db->loadResult();
		return $count_juserid;
	}

	######### count this user in facebook_joomla_connect table ############
	public static function count_fb_user($fbuserid) 
	{
		$db = JFactory::getDBO();
		$query = "SELECT COUNT(*) FROM #__facebook_joomla_connect WHERE facebook_userid=".$fbuserid;
		$db->setQuery($query);
		$count_fbuserid = $db->loadResult();
		return $count_fbuserid;
	}
	
	######### count this user in users table ############
	public static function count_jusername($username) 
	{
		$db = JFactory::getDBO();
		$query = "SELECT COUNT(*) FROM #__users WHERE username=".$db->Quote($username);
		$db->setQuery($query);
		$count_username = $db->loadResult();
		return $count_username;
	}

	######### count email in user table ############
	public static function count_jemail($useremail) 
	{
		$db = JFactory::getDBO();
		$query = "SELECT COUNT(*) FROM #__users WHERE email=".$db->Quote($useremail);
		$db->setQuery($query);
		$count_email = $db->loadResult();
		return $count_email;
	}

	
	######### check if curl is installed ############
	public static function iscurlinstalled() 
	{
		if  (in_array  ('curl', get_loaded_extensions())) {
			return true;
		}
		else{
			return false;
		}
	}
	
	######### Clean username ############
	public static function clean_username($source)
	{
 		$result = (string) preg_replace( '/[\x00-\x1F\x7F<>"\'%&]/', '', $source );
		return $result;
	}
		
	######### close popup window and refresh main page ############	
	public static function closeWindow($location='')
	{
		if($location){
		die('<script type="text/javascript">window.opener.location.href = "'.$location.'"; window.close();</script><a href="#" onClick="window.close();">Close this Window</a>');
		}else{
		die('<script type="text/javascript">window.opener.location.href = window.opener.location.href; window.close();</script><a href="#" onClick="window.close();">Close this Window</a>');
		}
	}
	
}