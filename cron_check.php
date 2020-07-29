<?php
require 'bot.php';
require 'checker.php';


$c = Checker::load();
$c->checkRequested();
if(  !empty ($c->added_ids)  ) $c->sendFirstMessages();