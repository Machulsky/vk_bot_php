<?php
ini_set('memory_limit','1600M');
require_once 'bot.php';
require 'checker.php';
require 'adder.php';


$last_action_time = 0;
$action_timeout = 120;
while (true){
  $rand = rand($action_timeout, $action_timeout+120);
  $time_spent = time()-$last_action_time;
  if($time_spent < $rand && $last_action_time != 0){
    echo "Action timeouting...\n";
    echo $rand-$time_spent." seconds \n";
    sleep($rand-$time_spent);
  }else{
    echo "Action allowed, lets do!\n";
    echo "Sending friend request...\n";
    Adder::handle()->doAdd();
    $last_action_time = time();
  }

}


