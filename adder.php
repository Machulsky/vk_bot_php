<?php


class Adder 
{
  public $audithories = [];
  public  $last_add_time;
  public  $last_add_key = 0;
  public  $add_timeout = 120;
  public static $filename;

  function __construct($audithories)
  {
    $this->audithories = $audithories;

    
  }

  public static function handle($file = 'users.aud')
  {
    $audithories = file_get_contents('users.aud');
   
    $audithories = unserialize($audithories);
   
    self::$filename = $file;
    return new Adder($audithories);
  }

  public function doAdd()
  {
    if((time() - $this->last_add_time) <= rand($this->last_add_timeout, $this->last_add_timeout+30) && $this->last_add_time != 0) return false;
    $bot = Bot::load();
    $audithories = $this->audithories;
   
    $added_key = '';
    $added_id = 0;
    $au_key = 0;
    foreach($audithories as $key => $user){
      $au_key = $key;
      foreach($user as $k => $v){
        $added_key = $k;
        $added_value = $v;
        break;
      }
      
    break;
    }
 
    $request = $bot->friendRequest($added_value);
    unset($audithories[$au_key][$added_key]);
    $ser = serialize($audithories);
    file_put_contents(self::$filename, $ser);
    $this->last_add_time = time();
    $this->last_add_key++;
  }
 
}