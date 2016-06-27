<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################
$log_ident = substr(md5(mt_rand()),0,5);
openlog("mx_router[script_del_rules_$log_ident]",LOG_ODELAY,LOG_USER); // Logging zu Syslog oeffnen

$path = substr(dirname($_SERVER["SCRIPT_FILENAME"]),0,-8);
require($path."/config.inc.php");
require($path."/functions.inc.php");

// Terminierte Regeln loschen
$query = mysqli_query($db,"SELECT id, ip FROM history WHERE active = 1 AND end_date > 0 AND end_date < '".time()."'");
my_syslog("Loesche abgelaufene Regeln: ".mysqli_num_rows($query));
while($row = mysqli_fetch_assoc($query)){
  rule_del($row["id"],"Cronjob");
}

// Abgelaufene Timeslot-Eintraege loeschen
mysqli_query($db,"DELETE FROM timeslots WHERE period_start <= '".(time()-($timeslot_period*60*60))."'");
my_syslog("Selfservice Timeslots zurueckgesetzt: ".mysqli_affected_rows($db));

// Turnier-Freischaltungen loeschen
if(!empty($dotlan_soap) && !empty($soap_secret)){
  $soap_client = soap_connect("mx_router",$soap_secret);
  
  $tcids = array();
  $query = mysqli_query($db,"SELECT tcid FROM history WHERE active = 1 AND tcid > 0");
  while($row = mysqli_fetch_assoc($query)) $tcids[] = $row["tcid"];

  my_syslog("Turnier-Freischaltungen loeschen: ".count($tcids));
  my_syslog("tcid: ".var_export($tcids,true));
  
  try{
    $finished = $soap_client->checkContestsFinished($tcids);
    if(count($finished) > 0){
      $query = mysqli_query($db,"SELECT id, old_id FROM history WHERE active = 1 AND tcid IN (".implode(",",$finished).")");
      while($row = mysqli_fetch_assoc($query)){
        rule_del($row["id"],"Cronjob");

        // Vorherige Regel wiederherstellen
        if($row["old_id"] > 0){
          my_syslog("Alte Regel wiederherstellen");
          mysqli_query($db,"INSERT INTO history (`ip`, `leitung`, `add_user`, `add_date`, `end_date`, `del_user`, `del_date`, `active`, `reason`) 
                       SELECT `ip`, `leitung`, `add_user`, '".time()."', `end_date`, '', '', '-1', `reason` FROM history WHERE id = '".$row["old_id"]."'");
          $id = mysqli_insert_id($db);
          rule_add($id);
        }
      }
    }
  }catch(Exception $e){
    echo "SOAP ERROR: ".$e->getMessage()."\n";
  }
}
?>
