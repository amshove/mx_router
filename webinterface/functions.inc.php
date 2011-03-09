<?php
############################################################
# Router Webinterface                                      #
# Copyright (C) 2010 Torsten Amshove <torsten@amshove.net> #
############################################################

// Bezeichnungen der admin-level
$ad_level = array(
  3 => "User",
  4 => "Admin",
  5 => "Superadmin"
);

// Mit MySQL verbinden
mysql_connect($mysql_host,$mysql_user,$mysql_pw) or die(mysql_error());
mysql_select_db($mysql_db) or die(mysql_error());

// Session starten
session_start();
if(!empty($_SESSION["user_id"])) $logged_in = true;
else $logged_in = false;

function ping($ip){
  exec("ping -n -q -c 1 -W 1 $ip > /dev/null 2>&1",$retarr,$retrc);
  return $retrc;
}

function iptables_add($ip){
  global $iptables_cmd;
  $cmd = $iptables_cmd." -I FORWARD --source $ip -j ACCEPT";
  exec($cmd,$retarr,$retrc);
  if($retrc != 0){
    if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
    return false;
  }
  return true;
}

function iptables_del($ip){
  global $iptables_cmd;
  $cmd = $iptables_cmd." -L FORWARD -n --line-numbers | grep ACCEPT";
  exec($cmd,$retarr,$retrc);
  if($retrc != 0){
    if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
    return false;
  }
  foreach($retarr as $line){
    $fields = preg_split("/\s/",$line, -1, PREG_SPLIT_NO_EMPTY);
    if($fields[4] == $ip){
      $num = $fields[0];
      if(is_numeric($num)){
        $cmd = $iptables_cmd." -D FORWARD ".$num;
        unset($retarr,$retrc);
        exec($cmd,$retarr,$retrc);
        if($retrc != 0){
          if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
          return false;
        }
      }
    }
  }
  return true;
}

function iptables_list(){
  global $iptables_cmd;
  $cmd = $iptables_cmd." -n -L FORWARD | grep ACCEPT";
  exec($cmd,$retarr,$retrc);
  if($retrc != 0){
    if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
    return false;
  }
  $array = array();
  foreach($retarr as $line){
    $fields = preg_split("/\s/",$line, -1, PREG_SPLIT_NO_EMPTY);
    if($fields[0] == "ACCEPT"){
      $array[] = $fields[3];
    }
  }
  return $array;
}
?>
