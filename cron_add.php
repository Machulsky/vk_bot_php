<?php
ini_set('memory_limit','1600M');
require_once 'bot.php';
require 'checker.php';
require 'adder.php';

//  $accounts = [
//    ['id' => 1, 'login' => '79678242896', 'password' => 'zx4NMK0psi'],
  
//  ];
// $users_to_add = ['231555809', 'id7925393'];
// //$bot = new Bot($accounts);
// $bot = Bot::load();

// $bot->friendRequest($users_to_add[0]);

// $c = $bot->handlers[0]['requests_sent'];
// var_dump($c);

$u = Adder::handle()->doAdd();
// $finder = new Finder();


// $audithories =
// [
//   [$finder->getUsers(['php2all'])->city(72)->sex(2)->getAddList()],
// ];


// Finder::storeAudithories($audithories);


