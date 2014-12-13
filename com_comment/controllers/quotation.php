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

    public function confirmTransaction()
    {        
        $sid = (int)$_POST["id"];
        $seller = (int)$_POST["seller"];
        $itemid = (int)$_POST["item"];

        $db=JFactory::getDbo();
        $db->setQuery('UPDATE `#__quotation` SET `confirm`=true WHERE `article_id`='.$itemid. ' AND `user_id`='.$seller);
        $db->query();

        //check if seller has contact
        $db=JFactory::getDbo();
        $db->setQuery('SELECT `id` FROM `#__contact` WHERE `account_id`='.$seller);
        $db->query();
        $scontact = $db->loadResult();
        $scount=sizeof($scontact);

        //check if buyer has contact
        $db=JFactory::getDbo();
        $db->setQuery('SELECT `buyer_id` FROM `#__transactions` WHERE `seller_id`='.$seller);
        $db->query();
        $buyer = $db->loadResult();

        $db=JFactory::getDbo();
        $db->setQuery('SELECT `id` FROM `#__contact` WHERE `account_id`='.$buyer);
        $db->query();
        $bcontact = $db->loadResult();
        $bcount=sizeof($bcontact);
    
        if($scount==0 && $bcount==0){

          $buyer_status == '請填寫資料';
          $seller_status == '請填寫資料';

        }
        elseif($scount==1 && $bcount==0){

          $buyer_status == '請填寫資料';
          $seller_status == '交易進行中';

        }
        elseif($scount==0 && $bcount==1){

          $buyer_status == '交易進行中';
          $seller_status == '請填寫資料';

        }
        elseif($scount==1 && $bcount==1){

          $buyer_status == '交易進行中';
          $seller_status == '交易進行中';

        }

          $db = JFactory::getDbo();
          $query = $db->getQuery(true);

          $fields = array(
          $db->quoteName('buyer_status') . ' = ' . $db->quote($buyer_status),
          $db->quoteName('seller_status') . ' = ' . $db->quote($seller_status)
          );

          $conditions = array(
              $db->quoteName('id') . ' = ' . $db->quote($sid)
          );       
          $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
          $db->setQuery($query);        
          $result = $db->execute();
        
    }

       public function cancelTransaction()
    {        
        $sid = (int)$_POST["id"];

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('buyer_status') . ' = ' . $db->quote('交易取消'),
        $db->quoteName('seller_status') . ' = ' . $db->quote('交易取消')
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($sid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();
    }

        public function fillSellerContact()
    {        
        $sid = (int)$_POST["id"];
        $name = (int)$_POST["name"];
        $phone = (int)$_POST["phone"];
        $option_text = (int)$_POST["option_text"];

        // write new contact
        $contact = new stdClass();
        $contact->name = $name;
        $contact->phone = $phone;
        $contact->option_text = $option_text;
        $contact->created=date("Y-m-d H:i:s");
         
        // Insert the object into contact table.
        $result = JFactory::getDbo()->insertObject('#__contact', $contact);

        //Update Seller Status 
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('seller_status') . ' = ' . $db->quote('交易進行中')
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($sid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();
    }

     public function fillBuyerContact()
    {        
        $sid = (int)$_POST["id"];
        $name = (int)$_POST["name"];
        $phone = (int)$_POST["phone"];
        $option_text = (int)$_POST["option_text"];

        // write new contact
        $contact = new stdClass();
        $contact->name = $name;
        $contact->phone = $phone;
        $contact->option_text = $option_text;
        $contact->created=date("Y-m-d H:i:s");
         
        // Insert the object into contact table.
        $result = JFactory::getDbo()->insertObject('#__contact', $contact);

        //Update Buyer Status 
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('buyer_status') . ' = ' . $db->quote('交易進行中')
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($sid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();
    }

 public function receivedCash()
    {        
        $sid = (int)$_POST["id"];

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('received_cash') . ' = ' . $db->quote(1),
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($sid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();

        //確認賣方義務是否完成（買方顯示交易完成）
        $db=JFactory::getDbo();
        $db->setQuery('SELECT `sent_item` FROM `#__transactions` WHERE `id`='.$sid);
        $db->query();
        $sent_item = $db->loadResult();

        $db=JFactory::getDbo();
        $db->setQuery('SELECT `received_cash` FROM `#__transactions` WHERE `id`='.$sid);
        $db->query();
        $received_cash = $db->loadResult();

        if($sent_item==$received_cash){

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('buyer_status') . ' = ' . $db->quote('交易完成')
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($sid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();

        }else{

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('buyer_status') . ' = ' . $db->quote('賣方已收款')
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($sid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();
        }

    }

      public function sentItem()
    {        
        $sid = (int)$_POST["id"];

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('sent_item') . ' = ' . $db->quote(1),
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($sid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();

        //確認賣方義務是否完成（買方顯示交易完成）
        $db=JFactory::getDbo();
        $db->setQuery('SELECT `received_cash` FROM `#__transactions` WHERE `id`='.$sid);
        $db->query();
        $received_cash = $db->loadResult();

        $db=JFactory::getDbo();
        $db->setQuery('SELECT `sent_item` FROM `#__transactions` WHERE `id`='.$sid);
        $db->query();
        $sent_item = $db->loadResult();

        if($received_cash==$sent_item){

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('buyer_status') . ' = ' . $db->quote('交易完成')
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($sid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();

        }else{

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('buyer_status') . ' = ' . $db->quote('賣方已出貨')
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($sid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();
        }

    }



     public function paidCash()
    {        
        $bid = (int)$_POST["id"];

          $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('paid_cash') . ' = ' . $db->quote(1),
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($bid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();

        //確認買方義務是否完成（賣方顯示交易完成）
        $db=JFactory::getDbo();
        $db->setQuery('SELECT `received_item` FROM `#__transactions` WHERE `id`='.$bid);
        $db->query();
        $received_item = $db->loadResult();

        $db=JFactory::getDbo();
        $db->setQuery('SELECT `paid_cash` FROM `#__transactions` WHERE `id`='.$bid);
        $db->query();
        $paid_cash = $db->loadResult();
        if($received_item==$paid_cash){

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('buyer_status') . ' = ' . $db->quote('交易完成')
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($bid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();

        }else{

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('seller_status') . ' = ' . $db->quote('買方已付款')
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($bid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();
        }
    }

     public function receivedItem()
    {        
        $bid = (int)$_POST["id"];

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('received_item') . ' = ' . $db->quote(1),
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($bid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();

        //確認買方義務是否完成（賣方顯示交易完成）
        $db=JFactory::getDbo();
        $db->setQuery('SELECT `paid_cash` FROM `#__transactions` WHERE `id`='.$bid);
        $db->query();
        $paid_cash = $db->loadResult();

        $db=JFactory::getDbo();
        $db->setQuery('SELECT `received_item` FROM `#__transactions` WHERE `id`='.$bid);
        $db->query();
        $received_item = $db->loadResult();
        if($paid_cash==$received_item){

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('seller_status') . ' = ' . $db->quote('交易完成')
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($bid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();

        }else{

            
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
        $db->quoteName('seller_status') . ' = ' . $db->quote('買方已收款')
        );

        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($bid)
        );       
        $query->update($db->quoteName('#__transactions'))->set($fields)->where($conditions);         
        $db->setQuery($query);        
        $result = $db->execute();
        }

    }

  

}