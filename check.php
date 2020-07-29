<?php
ini_set('memory_limit','1600M');
require_once 'bot.php';
require 'checker.php';
require 'adder.php';


$last_action_time = 0;
$action_timeout = 120;
while (true){
  if(time()-$last_action_time < rand($action_timeout, $action_timeout+120)){
    echo "Action timeouting...\n";
    echo time()-$last_action_time." seconds \n";
  }else{
    echo "Action allowed, lets do!\n";
    echo "Sending friend request...\n";
    $c = Checker::load();
    $c->checkRequested();
    if(  !empty ($c->added_ids)  ) $c->sendFirstMessages();
    $last_action_time = time();
  }

}


