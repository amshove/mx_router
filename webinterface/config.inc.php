<?php
############################################################
# Router Webinterface                                      #
# Copyright (C) 2010 Torsten Amshove <torsten@amshove.net> #
############################################################

// MySQL-Settings
$mysql_host = "localhost";
$mysql_user = "torsten";
$mysql_pw = "test123";
$mysql_db = "router";

// Settings
# Auf dem Server muss ein sudo eingerichtet werden:
# www-data ALL=NOPASSWD: /sbin/iptables
$iptables_cmd = "sudo /sbin/iptables";

// Default-PW, was gesetzt wird
$default_pw = "default";

$leitungen = array(
  0 => array(
    "name" => "DSL Kamp",
    "ip" => "80.237.237.160",
    "subnets" => "default"
  ),
  1 => array(
    "name" => "DSL Koch",
    "ip" => "80.237.237.161",
    "subnets" => "Orga, Server, VIP"
  )
);

setlocale(LC_ALL, 'de_DE@euro', 'de_DE.utf8', 'de_DE', 'de', 'ge');
?>
