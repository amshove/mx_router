<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

$path = substr(dirname($_SERVER["SCRIPT_FILENAME"]),0,-8);
require($path."/config.inc.php");
require($path."/functions.inc.php");

$arp_table = `arp -an`;
foreach(explode("\n",$arp_table) as $line){
  if(empty($line)) continue;
  $fields = explode(" ",$line);

  $ip = trim($fields[1],"()");
  $mac = $fields[3];
  $interface = $fields[6];

  if(!empty($ip) && !empty($mac) && !empty($interface) && preg_match("/^[0-9.]*$/",$ip) && preg_match("/^[0-9a-f:]$/",$mac))
    mysql_query("INSERT INTO arp_table SET ip = '$ip', mac = '$mac', interface = '$interface', last_seen = NOW() ON DUPLICATE KEY UPDATE last_seen = NOW()");
}
?>
