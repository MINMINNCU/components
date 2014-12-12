<?php
/**
 * @author Daniel Dimitrov - compojoom.com
 * @date: 11.04.13
 *
 * @copyright  Copyright (C) 2008 - 2013 compojoom.com . All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerlegacy');

class ccommentControllerQuotation extends JControllerLegacy
{
  
    public function acceptQuotation()
    {
        
      $quotation_id = (int)$_POST["id"];
      $buyer = (int)$_POST["buyer"];

      $db=JFactory::getDbo();
      $db->setQuery('UPDATE `#__quotation` SET `accept`=true WHERE `id`='.$quotation_id);
      $db->query();
      //get seller id
      $db=JFactory::getDbo();
      $db->setQuery('SELECT `user_id` FROM `#__quotation` WHERE `id`='.$quotation_id);
      $db->query();
      $seller = $db->loadResult();
      //notify

      //get article id
      $db=JFactory::getDbo();
      $db->setQuery('SELECT `article_id` FROM `#__quotation` WHERE `id`='.$quotation_id);
      $db->query();
      $item = $db->loadResult();

      // Create and populate an object.
      $profile = new stdClass();
      $profile->from_id = $buyer;
      $profile->to_id=$seller;
      $profile->type=2;
      $profile->detail="有人接受你的報價";
      $profile->created=date("Y-m-d H:i:s");
       
      // Insert the object into the user profile table.
      $result = JFactory::getDbo()->insertObject('#__notify', $profile);
      
      echo 'you got new quotation!';

      // Create and populate an object.
      $transaction = new stdClass();
      $transaction->item_id = $item;
      $transaction->buyer_id = $buyer;
      $transaction->seller_id = $seller;
      $transaction->paid_cash = 0;
      $transaction->received_item = 0;
      $transaction->received_cash = 0;
      $transaction->sent_item = 0;
      $transaction->buyer_status = '等待對方確認';
      $transaction->seller_status = '對方已接受報價';
      $transaction->created=date("Y-m-d H:i:s");
       
      // Insert the object into transaction table.
      $result = JFactory::getDbo()->insertObject('#__transactions', $transaction);

    }

    public function confirmQuotation($quotation_id,$confirm)
    {        
    $date=date("Y-m-d H:i:s");
        $db=JFactory::getDbo();
        $db->setQuery('UPDATE `#__quotation` SET `confirm`='.$confirm.',`confirmTime`='.$date.'WHERE `id`='.$quotation_id);
    }

    public function setStatus($accept,$confirm,$quotation_id)
    {
              
        if($accept==true && $confirm=true){
      $status==true;
        }
        else{
          $status==false;
        }

        $db=JFactory::getDbo();
        $db->setQuery('UPDATE `#__quotation` SET `status`='.$status.'WHERE `id`='.$quotation_id);

    }
}