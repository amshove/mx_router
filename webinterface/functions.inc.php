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
  $cmd = $iptables_cmd." -vn -L FORWARD | grep ACCEPT";
  exec($cmd,$retarr,$retrc);
  if($retrc != 0){
    if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
    return false;
  }
  $array = array();
  foreach($retarr as $line){
    $fields = preg_split("/\s/",$line, -1, PREG_SPLIT_NO_EMPTY);
    if($fields[2] == "ACCEPT"){
      $name = $fields[7];

      if($name == "0.0.0.0/0" && $fields[12] == "/*"){
        $name = $fields[14];
        for($i=15; $i<=20; $i++){
          if($fields[$i] != "*/") $name .= " ".$fields[$i];
          else break;
        }
      }

      $traffic[$name] = $fields[1];
      $array[] = $fields[7];
    }
  }
  return array($array,$traffic);
}

function ports_add($name){
  global $iptables_cmd,$global_ports;

  if(empty($global_ports[$name]["tcp"]) && empty($global_ports[$name]["udp"])) return false;

  if(!empty($global_ports[$name]["tcp"])){
    $cmd = $iptables_cmd." -I FORWARD -m multiport -p tcp --dports ".$global_ports[$name]["tcp"]." -j ACCEPT -m comment --comment \"Global-Ports: $name\"";
    exec($cmd,$retarr,$retrc);
    if($retrc != 0){
      if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
      return false;
    }
  }

  if(!empty($global_ports[$name]["udp"])){
    $cmd = $iptables_cmd." -I FORWARD -m multiport -p udp --dports ".$global_ports[$name]["udp"]." -j ACCEPT -m comment --comment \"Global-Ports: $name\"";
    exec($cmd,$retarr,$retrc);
    if($retrc != 0){
      if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
      return false;
    }
  }

  return true;
}

function ports_del($name){
  global $iptables_cmd,$global_ports;

  if(empty($global_ports[$name]["tcp"]) && empty($global_ports[$name]["udp"])) return false;

  if(!empty($global_ports[$name]["tcp"])){
    $cmd = $iptables_cmd." -L FORWARD -n --line-numbers | grep ACCEPT";
    exec($cmd,$retarr,$retrc);
    if($retrc != 0){
      if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
      return false;
    }
    foreach($retarr as $line){
      $fields = preg_split("/\s/",$line, -1, PREG_SPLIT_NO_EMPTY);
      if($fields[1] == "ACCEPT" && $fields[4] == "0.0.0.0/0" && $fields[5] == "0.0.0.0/0" && $fields[6] == "multiport" && $fields[7] == "dports"){
        if($fields[2] == "tcp" && $fields[8] == $global_ports[$name]["tcp"]){
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
    }
  }

  if(!empty($global_ports[$name]["udp"])){
    $cmd = $iptables_cmd." -L FORWARD -n --line-numbers | grep ACCEPT";
    exec($cmd,$retarr,$retrc);
    if($retrc != 0){
      if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
      return false;
    }
    foreach($retarr as $line){
      $fields = preg_split("/\s/",$line, -1, PREG_SPLIT_NO_EMPTY);
      if($fields[1] == "ACCEPT" && $fields[4] == "0.0.0.0/0" && $fields[5] == "0.0.0.0/0" && $fields[6] == "multiport" && $fields[7] == "dports"){
        if($fields[2] == "udp" && $fields[8] == $global_ports[$name]["udp"]){
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
    }
  }
  return true;
}

function ports_open($name){
  global $iptables_cmd,$global_ports;

  if(empty($global_ports[$name]["tcp"]) && empty($global_ports[$name]["udp"])) return false;

  $cmd = $iptables_cmd." -n -L FORWARD | grep ACCEPT";
  exec($cmd,$retarr,$retrc);
  if($retrc != 0){
    if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
    return false;
  }
  $tcp = false;
  $udp = false;
  foreach($retarr as $line){
    $fields = preg_split("/\s/",$line, -1, PREG_SPLIT_NO_EMPTY);
    if($fields[0] == "ACCEPT" && $fields[3] == "0.0.0.0/0" && $fields[4] == "0.0.0.0/0" && $fields[5] == "multiport" && $fields[6] == "dports"){
      if($fields[1] == "tcp" && $fields[7] == $global_ports[$name]["tcp"]) $tcp = true;
      if($fields[1] == "udp" && $fields[7] == $global_ports[$name]["udp"]) $udp = true;
    }
  }
  if(empty($global_ports[$name]["tcp"])) $tcp = $udp;
  if(empty($global_ports[$name]["udp"])) $udp = $tcp;

  return array($tcp,$udp);
}

function rule_list(){
  global $ip_cmd;
  $cmd = $ip_cmd." rule show | grep -v local | grep -v main | grep -v default";
  exec($cmd,$retarr,$retrc);
  if($retrc != 0){
    if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
    return false;
  }
  $array = array();
  foreach($retarr as $line){
    $fields = preg_split("/\s/",$line, -1, PREG_SPLIT_NO_EMPTY);
    if(is_numeric(substr($fields[0],0,-1))){
      $array[$fields[4]][$fields[2]] = substr($fields[3],0,-1);
    }
  }
  return $array;
}
?>
