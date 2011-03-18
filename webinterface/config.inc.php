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
# www-data ALL=NOPASSWD: /sbin/iptables, /sbin/ip
$iptables_cmd = "sudo /sbin/iptables";
$ip_cmd = "sudo /sbin/ip";

// Default-PW, was gesetzt wird
$default_pw = "default";

$aliases = array(
  "10.10.0.0/20" => "Alle",
  "10.10.0.0/24" => "Orga",
  "10.10.1.0/24" => "Server",
  "10.10.10.0/24" => "VIP"
);

$leitungen = array(
  0 => array(
    "name" => "DSL Kamp",
    "ip" => "80.237.237.160",
    "table" => "kamp"
  ),
  1 => array(
    "name" => "DSL Koch",
    "ip" => "80.237.237.161",
    "table" => "koch"
  )
);

setlocale(LC_ALL, 'de_DE@euro', 'de_DE.utf8', 'de_DE', 'de', 'ge');
?>
