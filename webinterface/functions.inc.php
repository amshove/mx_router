<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

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
if(!empty($_SESSION["user_name"])) $logged_in = true;
else $logged_in = false;

// Status der Leitungen testen
function ping($ip, $eth = ""){
  if(!empty($eth)) $eth = "-I ".escapeshellarg($eth);
  exec("ping -n -q -c 1 -W 1 ".escapeshellarg($ip)." ".$eth." > /dev/null 2>&1",$retarr,$retrc);
  return $retrc;
}

// FW-Freischaltung anlegen
function rule_add($id){
  global $iptables_cmd;
  $id = mysql_real_escape_string($id);
  $values = mysql_fetch_assoc(mysql_query("SELECT * FROM history WHERE id = '".$id."' LIMIT 1"));

  if(mysql_num_rows(mysql_query("SELECT id FROM history WHERE ip = '".$values["ip"]."' AND active = 1 LIMIT 1")) > 0) return false; // Keine doppelten Freischaltungen

  if($values["ip"]){
    $cmd = $iptables_cmd." -A FORWARD --source ".escapeshellarg($values["ip"])." -j ACCEPT";
    exec($cmd,$retarr,$retrc);
    if($retrc != 0){
      if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
      return false;
    }
    mysql_query("UPDATE history SET active = 1 WHERE id = '".$id."' LIMIT 1");

    // Leitungszuordnung
    if($values["leitung"] > 0) return leitung_chg($id,$values["leitung"]);

    return true;
  }
  return false;
}

// FW-Freischaltung entfernen
function rule_del($id,$del_user){
  global $iptables_cmd;
  $id = mysql_real_escape_string($id);
  $del_user = mysql_real_escape_string($del_user);
  $values = mysql_fetch_assoc(mysql_query("SELECT * FROM history WHERE id = '".$id."' LIMIT 1"));

  if($values["ip"]){
    $tmp = iptables_list();
    $iptables_traffic = $tmp[1];

    $cmd = $iptables_cmd." -D FORWARD --source ".escapeshellarg($values["ip"])." -j ACCEPT";
    exec($cmd,$retarr,$retrc);
    if($retrc != 0){
      if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
      $tmp = iptables_list();
      if($tmp[1][$values["ip"]]) return false; // Existiert Regel noch?
    }

    mysql_query("UPDATE history SET active = 0, del_user = '".$del_user."', del_date = '".time()."', traffic = '".$iptables_traffic[$values["ip"]]."' WHERE id = '".$id."' LIMIT 1");
    leitung_chg($id,0); // Leitungs-Regel loeschen
    return true;
  }
  return false;
}

// Leitungszuordnung fuer IP aendern
function leitung_chg($id,$leitung_neu){
  global $iptables_cmd, $max_fw_mark;
  if($leitung_neu > $max_fw_mark) $leitung_neu = 0;

  $id = mysql_real_escape_string($id);
  $values = mysql_fetch_assoc(mysql_query("SELECT * FROM history WHERE id = '".$id."' LIMIT 1"));

  // Alten Eintrag loeschen
  if($values["leitung"] > 0){
    $cmd = $iptables_cmd." -t mangle -D PREROUTING --source ".escapeshellarg($values["ip"])." -j MARK --set-mark ".escapeshellarg($values["leitung"]);
    exec($cmd,$retarr,$retrc);
  }

  mysql_query("UPDATE history SET leitung = '".mysql_real_escape_string($leitung_neu)."' WHERE id = '".$id."' LIMIT 1");

  // Neuer Eintrag wenn noetig
  if($leitung_neu > 0){
    $cmd = $iptables_cmd." -t mangle -I PREROUTING 2 --source ".escapeshellarg($values["ip"])." -j MARK --set-mark ".escapeshellarg($leitung_neu);
    exec($cmd,$retarr,$retrc);
    if($retrc != 0){
      if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
      return false;
    }
  }
  return true;
}

// Ports global freigeben
function ports_add($id){
  global $iptables_cmd;
  $id = mysql_real_escape_string($id);
  $values = mysql_fetch_assoc(mysql_query("SELECT * FROM ports WHERE id = '".$id."' LIMIT 1"));

  if(empty($values["tcp"]) && empty($values["udp"])) return false;

  if(!empty($values["tcp"])){
    $cmd = $iptables_cmd." -A FORWARD -m multiport -p tcp --dports ".escapeshellarg($values["tcp"])." -j ACCEPT -m comment --comment \"Global-Ports: ".escapeshellarg($values["name"])."\"";
    exec($cmd,$retarr,$retrc);
    if($retrc != 0){
      if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
      return false;
    }
  }

  if(!empty($values["udp"])){
    $cmd = $iptables_cmd." -A FORWARD -m multiport -p udp --dports ".escapeshellarg($values["udp"])." -j ACCEPT -m comment --comment \"Global-Ports: ".escapeshellarg($values["name"])."\"";
    exec($cmd,$retarr,$retrc);
    if($retrc != 0){
      if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
      return false;
    }
  }

  mysql_query("UPDATE ports SET active = 1 WHERE id = '".$id."' LIMIT 1");
  return true;
}

// Globale Freigabe entfernen
function ports_del($id){
  global $iptables_cmd;
  $id = mysql_real_escape_string($id);
  $values = mysql_fetch_assoc(mysql_query("SELECT * FROM ports WHERE id = '".$id."' LIMIT 1"));

  if(empty($values["tcp"]) && empty($values["udp"])) return false;

  if(!empty($values["tcp"])){
    $cmd = $iptables_cmd." -D FORWARD -m multiport -p tcp --dports ".escapeshellarg($values["tcp"])." -j ACCEPT -m comment --comment \"Global-Ports: ".escapeshellarg($values["name"])."\"";
    exec($cmd,$retarr,$retrc);
    if($retrc != 0){
      if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
      return false;
    }
  }

  if(!empty($values["udp"])){
    $cmd = $iptables_cmd." -D FORWARD -m multiport -p udp --dports ".escapeshellarg($values["tcp"])." -j ACCEPT -m comment --comment \"Global-Ports: ".escapeshellarg($values["name"])."\"";
    exec($cmd,$retarr,$retrc);
    if($retrc != 0){
      if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
      return false;
    }
  }

  mysql_query("UPDATE ports SET active = 0 WHERE id = '".$id."' LIMIT 1");
  return true;
}

// Leitungszuordnung fuer Ports aendern
function ports_leitung_chg($id,$leitung_neu){
  global $iptables_cmd, $max_fw_mark;
  if($leitung_neu > $max_fw_mark) $leitung_neu = 0;

  $id = mysql_real_escape_string($id);
  $values = mysql_fetch_assoc(mysql_query("SELECT * FROM ports WHERE id = '".$id."' LIMIT 1"));

  // Alten Eintrag loeschen
  if($values["leitung"] > 0){
    if(!empty($values["tcp"])){
      $cmd = $iptables_cmd." -t mangle -D PREROUTING -m multiport -p tcp --dports ".escapeshellarg($values["tcp"])." -j MARK --set-mark ".escapeshellarg($values["leitung"])." -m comment --comment \"Global-Ports: ".escapeshellarg($values["name"])."\"";
      exec($cmd,$retarr,$retrc);
    }
    if(!empty($values["udp"])){
      $cmd = $iptables_cmd." -t mangle -D PREROUTING -m multiport -p udp --dports ".escapeshellarg($values["tcp"])." -j MARK --set-mark ".escapeshellarg($values["leitung"])." -m comment --comment \"Global-Ports: ".escapeshellarg($values["name"])."\"";
      exec($cmd,$retarr,$retrc);
    }
  }

  mysql_query("UPDATE ports SET leitung = '".mysql_real_escape_string($leitung_neu)."' WHERE id = '".$id."' LIMIT 1");

  // Neuer Eintrag wenn noetig
  if($leitung_neu > 0){
    if(!empty($values["tcp"])){
      $cmd = $iptables_cmd." -t mangle -A PREROUTING -m multiport -p tcp --dports ".escapeshellarg($values["tcp"])." -j MARK --set-mark ".escapeshellarg($leitung_neu)." -m comment --comment \"Global-Ports: ".escapeshellarg($values["name"])."\"";
      exec($cmd,$retarr,$retrc);
      if($retrc != 0){
        if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
        return false;
      }
    }
    if(!empty($values["udp"])){
      $cmd = $iptables_cmd." -t mangle -A PREROUTING -m multiport -p udp --dports ".escapeshellarg($values["tcp"])." -j MARK --set-mark ".escapeshellarg($leitung_neu)." -m comment --comment \"Global-Ports: ".escapeshellarg($values["name"])."\"";
      exec($cmd,$retarr,$retrc);
      if($retrc != 0){
        if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
        return false;
      }
    }
  }
  return true;
}

// Status, Traffic und Leitungszuordnung aller Freigaben abfragen
function iptables_list($leitung=false){
  global $iptables_cmd;
  if(!$leitung) $cmd = $iptables_cmd." -vn -L FORWARD | grep ACCEPT";
  else $cmd = $iptables_cmd." -t mangle -n -L PREROUTING | grep MARK";
  exec($cmd,$retarr,$retrc);
  if(!$leitung && $retrc != 0){
    if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
    return false;
  }

  $array = array();
  foreach($retarr as $line){
    $fields = preg_split("/\s/",$line, -1, PREG_SPLIT_NO_EMPTY);
    if(!$leitung){
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
    }else{
      $name = $fields[3];
      if($name != "0.0.0.0/0" && preg_match("/MARK set 0x([0-9]*)/",$line,$matches)) $array[$name] = $matches[1];
    }
  }
  return array($array,$traffic);
}

// Testen, ob globale Freischaltung aktiv ist und auf welcher Leitung sie lauft
function ports_open($id,$leitung=false){
  global $iptables_cmd;
  $id = mysql_real_escape_string($id);
  $values = mysql_fetch_assoc(mysql_query("SELECT * FROM ports WHERE id = '".$id."' LIMIT 1"));

  if(empty($values["tcp"]) && empty($values["udp"])) return false;

  if(!$leitung) $cmd = $iptables_cmd." -n -L FORWARD | grep ACCEPT";
  else $cmd = $iptables_cmd." -t mangle -n -L PREROUTING | grep MARK";
  exec($cmd,$retarr,$retrc);
  if(!$leitung && $retrc != 0){
    if($_SESSION["ad_level"] >= 5) echo "<div class='meldung_error'>$cmd nicht erfolgreich - RC: $retrc</div><br>";
    return false;
  }
  $tcp = false;
  $udp = false;
  $pos = 0;
  $i = 0;
  foreach($retarr as $line){
    $fields = preg_split("/\s/",$line, -1, PREG_SPLIT_NO_EMPTY);
    if($fields[3] == "0.0.0.0/0" && $fields[4] == "0.0.0.0/0" && $fields[5] == "multiport" && $fields[6] == "dports"){
      if($fields[1] == "tcp" && $fields[7] == $values["tcp"]){
        $tcp = true;
        if(preg_match("/MARK set 0x([0-9]*)/",$line,$matches)) $line_nr = $matches[1];
        else $line_nr = 0;
        $pos = $i;
      }elseif($fields[1] == "udp" && $fields[7] == $values["udp"]){
        $udp = true;
        if(preg_match("/MARK set 0x([0-9]*)/",$line,$matches)) $line_nr = $matches[1];
        else $line_nr = 0;
        $pos = $i;
      }
      $i++;
    }
  }
  if(empty($values["tcp"])) $tcp = $udp;
  if(empty($values["udp"])) $udp = $tcp;

  return array($tcp,$udp,$line_nr,$pos);
}

// IP mit CIDR-Angabe matchen lassen
function cidr_match($ip, $range){ // http://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php5
  if(!preg_match("/\//",$range)) $range .= "/32";
  list ($subnet, $bits) = explode('/', $range);
  $ip = ip2long($ip);
  $subnet = ip2long($subnet);
  $mask = -1 << (32 - $bits);
  $subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
  return ($ip & $mask) == $subnet;
}

// Einzelnen Port gegen Range matchen lassen
function port_match($search_port, $range){
  $ports = explode(",",$range);
  foreach($ports as $port){
    if($search_port == $port) return true;
    if(stristr($port,":")){
      $tmp = explode(":",$port);
      for($i=$tmp[0];$i<=$tmp[1];$i++){
        if($search_port == $i) return true;
      }
    }
  }
  return false;
}

############ SOAP-Client #############
// Verbindung herstellen
function soap_connect($user,$pw){
  global $dotlan_soap;

  if(empty($dotlan_soap)) return false;

  try{
    $client = new SoapClient($dotlan_soap."?wsdl",array("login"=>$user,"password"=>$pw,"user_agent"=>"mx_router","encoding"=>"ISO-8859-1"));
    return $client;
  }catch(Exception $e){
    die("SOAP ERROR: ".$e->getMessage());
  }
}
?>
