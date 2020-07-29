<?php

use DigitalStar\vk_api\vk_api;

class Finder 
{
  private $vk;
  public $group_users = [];
  private $global_count = [];
  private $parsed_count = [];
  public $pool = [];
  public $filtered_pool = [];

  function __construct($key = 'e700ba9de700ba9de700ba9d21e76ff396ee700e700ba9db92d5774fb4080b4f8bc8fa3')
  {
    $this->vk = vk_api::create($key, '5.65');
  }

  public static function shortnameFromUrl($group_url)
  {
    $short = str_ireplace("https://" , "", $group_url);
    $short = str_ireplace("vk.com", "", $short);
    $short = str_ireplace ("/", "", $short);
    return $short;
  }

  public function getMembers($group_short, $offset = 0)
  {
    $params = [
      'group_id' => $group_short,
      'fields' => 'city,country,bdate,sex,relation',
      'offset' => $offset
  ];
    $info = $this->vk->request('groups.getMembers', $params);

    $users = $info['items'];
    $this->global_count[$group_short] = $info['count'];
    if(!isset($this->parsed_count[$group_short])) $this->parsed_count[$group_short] = 0;
    $this->parsed_count[$group_short] += count($info['items']);
    if( !isset($this->group_users[$group_short])) $this->group_users[$group_short] = [];
    $this->group_users[$group_short] += $users;
    $this->indexes($group_short);
    
    return $this;
  }


  private function indexes($group_short)
  {
    foreach($this->group_users[$group_short] as $key => $user){
    
        $this->group_users[$group_short][$key+ $this->global_count[$group_short] - $this->parsed_count[$group_short]] = $user;
     
      
    }

   

    return $this;
  }

  public function allMembers($group_short)
  {
    $this->getMembers($group_short)->cleanDeactivated();
    while($this->parsed_count[$group_short] != $this->global_count[$group_short]){
      $this->getMembers($group_short, $this->parsed_count[$group_short])->cleanDeactivated();
     
    }

    return $this;
  }

  public function getUsers($group_urls = [])
  {
    
    foreach($group_urls as $key => $url){
      $group_short = self::shortnameFromUrl($url);
      $this->allMembers($group_short);
    }
    $this->makePool();
   

    return $this;

  }

  public function makePool()
  {
    $i = 0;
    foreach($this->group_users as $group => $users){
      foreach ($users as $key => $user){
        $this->pool[$i] = $user;
        $i++;
      }
    }

    return $this;
  }


  public function cleanDeactivated()
  {
    foreach($this->group_users as $group_short => $users)
    {
      foreach($users as $key=>$user){
        if(isset($user['deactivated'])){
          unset($this->group_users[$group_short][$key]);
        }

      }
      
    }

    return $this;
  }

  public function sex($id)
  {
    $result = [];
    foreach ($this->pool as $key => $user)
    {
      if(isset($user['sex']) && $user['sex'] == $id) $result[$key] = $user;
    }

    $this->pool = $result;
    return $this;


  }
  public function relation($id)
  {
    $result = [];
    foreach ($this->pool as $key => $user)
    {
      if(isset($user['relation']) && $user['relation'] == $id) $result[$key] = $user;
    }

    $this->pool = $result;
    return $this;

  }
  public function city ($id)
  {
    $result = [];
    foreach ($this->pool as $key => $user)
    {
      if(isset($user['city']['id']) && $user['city']['id'] == $id) $result[$key] = $user;
    }

    $this->pool = $result;
    return $this;
  }

  public function getFiltered()
  {
    return $this->pool;
  }

  public function filter($users)
  {
    $this->group_users = $users;
    return $this;


  }
  private static function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
  } 

  public static function getRegDate($user_id)
  {

    $url = 'https://vk.com/foaf.php?id='.$user_id;
    $context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
    $xml = mb_convert_encoding(file_get_contents($url), 'utf-8', 'windows-1251');
    $date = self::get_string_between($xml, ' <ya:created dc:date="', '"/>');
    $date = stristr($date, "+", true);
    $date = str_replace("T", " ", $date);
    return strtotime($date);

  }

  public function age($years_from, $years_to)
  {
    $result = [];
    foreach ($this->pool as $key => $user)
    {
      if(isset($user['bdate'])){
        $t = strtotime($user['bdate']);
        $age = (time()-$t)/60/60/24/30/12;
        $age = (int) $age;
        if($age >= $years_from && $age <= $years_to) $result[$key] = $user;

      }
    }

    $this->pool = $result;
    return $this;

  }

  public function getAddList()
  {
    $add_list = [];
    $i=0;
    foreach($this->pool as $key => $user)
    {
      $add_list[$i] = $user['id'];
      $i++;
    }
    return $add_list;
  }

  public function regDays($amount)
  {
    $result = [];
    foreach ($this->pool as $key => $user)
    {
      $reg = self::getRegDate($user['id']);
      $reg = intval((time()-$reg)/60/60/24);
      if($reg >= $amount)$result[$key] = $user;
    }

    $this->pool = $result;
    return $this;

  }

  public static function storeAudithories($audithories)
  {
    $i = 0;
    foreach($audithories as $k => $audithory){
      foreach($audithory as $key=>$user){

        $result[$i] = $user;
        $i++;
      }
    }
    $audithories = $result;
    $audithories = serialize($audithories);
    file_put_contents('users.aud', $audithories);

  }



  
}