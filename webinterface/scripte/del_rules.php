<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

$path = substr(dirname($_SERVER["SCRIPT_FILENAME"]),0,-8);
require($path."/config.inc.php");
require($path."/functions.inc.php");

$query = mysql_query("SELECT id, ip FROM history WHERE active = 1 AND end_date > 0 AND end_date < '".time()."'");
while($row = mysql_fetch_assoc($query)){
  rule_del($row["id"],"Cronjob");
}

// Abgelaufene Timeslot-Eintraege loeschen
mysql_query("DELETE FROM timeslots WHERE period_start <= '".(time()-($timeslot_period*60*60))."'");
?>
