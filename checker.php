<?php


class Checker
{
  public $requested_ids = [];
  public $added_ids = [];
  public $sent_ids = [];
  public $bot;

  function __construct()
  {
    
    $this->bot = Bot::load();
    $this->requested_ids = $this->bot->requested_ids;
  }

  function reloadBot(){
    $this->bot = Bot::load();
    $this->requested_ids = $this->bot->requested_ids;
  }

  function deleteRequested($user_id)
  {
    $key = array_search($user_id, $this->requested_ids);
    unset($this->requested_ids[$key]);

  }

  function deleteAdded($user_id)
  {
    $key = array_search($user_id, $this->added_ids);
    unset($this->added_ids[$key]);

  }

  function addRequested ($user_id)
  {
    $this->requested[] = $user_id;
  }

  function addAdded ($user_id)
  {
    $this->added_ids[] = $user_id;
  }

  function checkRequested()
  {
    foreach ($this->requested_ids as $key => $value){
      echo "Found requested with id: ".$value. "\n";
      $check = $this->bot->checkUser($value);
      $user = $check['user'];
      if($user['is_friend']){
        echo "User ". $value ." was accept friend request\n";
        $this->deleteRequested($value);
        $this->addAdded($value);
      } 

      echo "User ".$value. " now not accept request\n";
      $r = rand(60,120);
      echo "Sleeping ".$r." seconds \n";
      sleep($r);
    }

  }

  function sendFirstMessages()
  {
    foreach($this->added_ids as $key => $value){
      echo "Found accepted with id: ".$value."\n";
      echo "Sending first message...\n";
      $this->bot->sendNewMessage('First message', $value);
      $this->deleteAdded($value);
      $this->sent_ids[] = $value;
      $r = rand(120,180);
      echo "Done. Sleeping ". $r. " seconds\n";
      sleep($r);
    }
  }

  public function store($filename = 'checker.ser')
  {
    return file_put_contents($filename, serialize($this));
  }

  function __destruct ()
  {
    $this->store();
  }

  public static function load($filename = 'checker.ser')
  {
    if (is_file($filename)){
     $saved  = unserialize(file_get_contents($filename));
     $saved->reloadBot();
     return $saved;

    }else{
      return new Checker();
    }

  }

}