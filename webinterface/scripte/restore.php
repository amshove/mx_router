<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

$path = substr(dirname($_SERVER["SCRIPT_FILENAME"]),0,-8);
require($path."/config.inc.php");
require($path."/functions.inc.php");

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
