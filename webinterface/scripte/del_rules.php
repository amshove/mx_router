<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

$path = substr(dirname($_SERVER["SCRIPT_FILENAME"]),0,-8);
require($path."/config.inc.php");
require($path."/functions.inc.php");

// Terminierte Regeln loschen
$query = mysql_query("SELECT id, ip FROM history WHERE active = 1 AND end_date > 0 AND end_date < '".time()."'");
while($row = mysql_fetch_assoc($query)){
  rule_del($row["id"],"Cronjob");
}

// Abgelaufene Timeslot-Eintraege loeschen
mysql_query("DELETE FROM timeslots WHERE period_start <= '".(time()-($timeslot_period*60*60))."'");

// Turnier-Freischaltungen loeschen
if(!empty($dotlan_soap) && !empty($soap_secret)){
  $soap_client = soap_connect("mx_router",$soap_secret);
  
  $tcids = array();
  $query = mysql_query("SELECT tcid FROM history WHERE active = 1 AND tcid > 0");
  while($row = mysql_fetch_assoc($query)) $tcids[] = $row["tcid"];
  
  try{
    $finished = $soap_client->checkContestsFinished($tcids);
    if(count($finished) > 0){
      $query = mysql_query("SELECT id, old_id FROM history WHERE active = 1 AND tcid IN (".implode(",",$finished).")");
      while($row = mysql_fetch_assoc($query)){
        rule_del($row["id"],"Cronjob");

        // Vorherige Regel wiederherstellen
        if($row["old_id"] > 0){
          mysql_query("INSERT INTO history (`ip`, `leitung`, `add_user`, `add_date`, `end_date`, `del_user`, `del_date`, `active`, `reason`) 
                       SELECT `ip`, `leitung`, `add_user`, '".time()."', `end_date`, '', '', '-1', `reason` FROM history WHERE id = '".$row["old_id"]."'");
          $id = mysql_insert_id();
          rule_add($id);
        }
      }
    }
  }catch(Exception $e){
    echo "SOAP ERROR: ".$e->getMessage()."\n";
  }
}
?>
