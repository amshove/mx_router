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

$global_ports = array(
  "Steam" => array(  # https://support.steampowered.com/kb_article.php?ref=8571-GLVN-8711
    "tcp" => "27014:27050",
    "udp" => "27000:27030,4380,1500,3005,3101,28960"
  ),
  "Starcraft II" => array(
    "tcp" => "6112:6119,4000,3724",
    "udp" => "6112:6119,4000,3724"
  ),
  "Xfire" => array(  # http://www.xfire.com/faq/#158
    "tcp" => "25999"
  ),
  "ICQ" => array(
    "tcp" => "5190"
  )
);

setlocale(LC_ALL, 'de_DE@euro', 'de_DE.utf8', 'de_DE', 'de', 'ge');
?>
