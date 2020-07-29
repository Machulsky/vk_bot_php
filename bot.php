<?php

require_once "vendor/autoload.php";
require_once "finder.php";
const VERSION = '5.65';
const REQUEST_IGNORE_ERROR = [];
use DigitalStar\vk_api\vk_api;


class Bot {
  public $handlers = [];
  public $accounts = [];
  private $api_version;
  private $handler_counter = -1;
  public $requests_limit_per_account = 10;
  public $requests_limit_timeout = 24;//в часах
  public $new_messages_limit_per_account = 20;
  public $new_messages_limit_timeout = 24; //in hours
  private $bot_started_at = 0; //timestamp
  public $requested_ids = [];
  public $handler_users = [];
  

  function __construct ($accounts)
  {
    $this->accounts = $accounts;
    $this->api_version = '5.65';
    $this->createHandlers($accounts);

  }

  private function createHandlers($accounts)
  {
    foreach ($accounts as $key => $value)
    {
      $this->handlers[$key]['instance'] = vk_api::create($value['login'], $value['password'], $this->api_version);

      sleep(1);
    }
  }

  // public function reloadHandlers($accounts)
  // {
  //   foreach ($accounts as $key => $value)
  //   {
  //     $this->handlers[]['instance'] = vk_api::create($value['login'], $value['password'], $this->api_version);
  //     $this->handlers[]['requests_sent'] = 0;
  //     $this->handlers[]['last_request_time'] = time();
  //   }
  // }

  

  private function getAviableHandler()
  {
    $count_of_handlers = count($this->handlers);
   
    if ($count_of_handlers == 1){
      $this->handlers[$this->handler_counter]['requests_sent']++;
      $this->handlers[$this->handler_counter]['last_request_time'] = time();
      return $this->handlers[0]['instance'];
    } 

    if($count_of_handlers <= $this->handler_counter) $this->handler_counter = -1;

    $this->handler_counter ++;
    if($this->handlers[$this->handler_counter]['requests_sent'] > $this->requests_limit_per_account){
      if((time() - $this->handlers[$this->handler_counter]['last_request_time']) < $this->requests_limit_timeout*60*60){
        return false;
      }else{
        $this->handlers[$this->handler_counter]['requests_sent'] = 0;
      }
      
    }
    $this->handlers[$this->handler_counter]['requests_sent']++;
    
    $this->handlers[$this->handler_counter]['last_request_time'] = time();

    return $this->handlers[$this->handler_counter]['instance'];
  }

  private function getRecentHandler()
  {
    if($this->handler_counter < 0) $this->handler_counter = 0;
    return $this->handlers[$this->handler_counter]['instance'];
  }

  private function getUserHandler ($user_id, $is_new = false)
  {
    if(is_object($this->handler_users[$user_id]['instance'])){
      if($is_new){
        if($this->handlers[$this->handler_counter]['new_messages_sent'] > $this->new_messages_limit_per_account){
          if((time()-$this->handler_users[$user_id]['last_message_sent']) < $this->new_messages_limit_timeout*60*60 ){
            return false;
          }else{
            $this->handler_users[$user_id]['new_messages_sent'] = 0;
          }
        }
        
        $this->handler_users[$user_id]['new_messages_sent']++;
        $this->handler_users[$user_id]['last_message_sent'] = time();
      }
      return $this->handler_users[$user_id]['instance'];
    }
    

    return false;
  }

  public function friendRequest ( $user_id )
  {
    $check = $this->checkUser($user_id);
    $user = $check['user'];
    if(! (bool) $user['can_send_friend_request']) return false;
    $handler = $this->getAviableHandler();
   
    $params = ['user_id' => $user['id']];
    
    $add = $handler->request('friends.add', $params);
    if(in_array($user_id, $this->requested_ids)) return false;
    //sleep(1);
    if($this->checkAdd( $user_id )){
      array_push($this->requested_ids, $user_id);
      $this->handler_users[$user_id]['instance'] = $handler;
      $this->handler_users[$user_id]['new_messages_sent'] = 0;
      $this->handler_users[$user_id]['last_message_sent'] = 0;
      return true;
    }

    return false;
  }

  public function sendMessage ( $text, $user_id )
  {
    $check = $this->checkUser($user_id);
    $user = $check['user'];
    if(! (bool) $user['can_write_private_message']) return false;
    $handler = $this->getUserHandler($user_id);
    if(!$handler) $handler = $check['handler'];
    $request = $handler->sendMessage($user['id'], $text, []);
    return true;
  }

  public function sendNewMessage( $text, $user_id )
  {
    $check = $this->checkUser($user_id);
    $user = $check['user'];
    if( !  (bool) $user['is_friend']  ) return false;
    if(! (bool) $user['can_write_private_message']) return false;
    $handler = $this->getUserHandler($user_id, true);
    if(!$handler) $handler = $check['handler'];
    $request = $handler->sendMessage($user['id'], $text);
    $key = array_search($user_id,$this->requested_ids);
    unset($this->requested_ids[$key]);
    return $request;
  }

  private function checkAdd( $user_id )
  {
    $user = $this->checkUser($user_id)['user'];
    $handler = $this->getRecentHandler();
    $params = ['out'=>1];
    $get_requests = $handler->request('friends.getRequests', $params);
    $items = $get_requests['items'];
    if(in_array($user['id'], $items)) return true;
    return false;
  }

  public function isFriend($user_id)
  {
    $user = $this->checkUser($user_id)['user'];
    return (bool) $user['is_friend'];
  }

  public function checkUser ($user_id, $params = [])
  {
    
    $handler = $this->getUserHandler($user_id);
    if(!$handler) $handler = $this->getRecentHandler();
    $params = ['user_ids' => $user_id, 'fields' => 'can_write_private_message,can_send_friend_request,is_friend'];
    $check = $handler->request('users.get', $params);
    return ['user' => $check[0], 'handler' => $handler];
  }


  private function canWriteTo( $user_id )
  {
    $user = $this->checkUser( $user_id)['user'];
    return $user['can_write_private_message'];

  }

  public function store($filename = 'bot.ser')
  {
    return file_put_contents($filename, serialize($this));
  }

  function __destruct ()
   {
     $this->store();
   }

   public static function load($accounts = null, $filename = 'bot.ser')
   {
     if (is_file($filename)){
      $saved  = unserialize(file_get_contents($filename));
      if(!is_null($accounts)) $saved->reloadHandlers($accounts);
      return $saved;

     }else{
       if(is_array($accounts))
      return new Bot($accounts);
      return false;
     }

   }




}