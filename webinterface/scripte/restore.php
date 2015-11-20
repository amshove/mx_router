<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################
$log_ident = substr(md5(mt_rand()),0,5);
openlog("mx_router[script_restore_$log_ident]",LOG_ODELAY,LOG_USER); // Logging zu Syslog oeffnen

$path = substr(dirname($_SERVER["SCRIPT_FILENAME"]),0,-8);
require($path."/config.inc.php");
require($path."/functions.inc.php");

my_syslog("Einstellungen aus DB restoren ...");
$query = mysql_query("SELECT id FROM history WHERE active = 1");
while($row = mysql_fetch_assoc($query)){
  rule_add($row["id"],true);
}

$query = mysql_query("SELECT id, active, leitung FROM ports");
while($row = mysql_fetch_assoc($query)){
  if($row["active"] == 1) ports_add($row["id"]);
  ports_leitung_chg($row["id"],$row["leitung"]);
}
?>
