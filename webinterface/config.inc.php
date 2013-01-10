<?php
############################################################
# Router Webinterface                                      #
# Copyright (C) 2010 Torsten Amshove <torsten@amshove.net> #
############################################################
require("leitungen.inc.php");

// MySQL-Settings
$mysql_host = "localhost";
$mysql_user = "router";
$mysql_pw = "--MYSQL_PW--";
$mysql_db = "router";

// Settings
# Auf dem Server muss ein sudo eingerichtet werden:
# www-data ALL=NOPASSWD: /sbin/iptables, /sbin/ip
$iptables_cmd = "sudo /sbin/iptables";
$ip_cmd = "sudo /sbin/ip";

// Default-PW, was gesetzt wird
$default_pw = "mx_router";

$aliases = array(
  "10.10.0.0/20" => "Alle",
  "10.10.0.0/24" => "Orga",
  "10.10.1.0/24" => "Server",
  "10.10.10.0/24" => "VIP"
);

$global_ports = array(
  "Steam" => array(  # https://support.steampowered.com/kb_article.php?ref=8571-GLVN-8711
    "tcp" => "27014:27050",
    "udp" => "27000:27030,4380,1500,3005,3101,28960"
  ),
  "Starcraft II" => array(
    "tcp" => "1119",
    "udp" => "1119"
  ),
  "Xfire" => array(  # http://www.xfire.com/faq/#158
    "tcp" => "25999"
  ),
  "ICQ" => array(
    "tcp" => "5190"
  ),
  "CoD MW3" => array(
    "tcp" => "3074,27000:27050",
    "udp" => "3074,8766"
  ),
  "LoL (+ HTTP)" => array(
    "tcp" => "80,443,2099,5223,56000:60000",
    "udp" => "80,2001,3000:6000,10000:60000"
  ),
#  "BF3 (+ HTTP)" => array( # ungetestet
#    "tcp" => "80,443,9988,17502,20000:30000,42127",
#    "udp" => "3659,14000:14016,22990:23006,25200:25300"
#  )
  "HTTP" => array(
    "tcp" => "80,443"
  ),
  "Mails" => array(
    "tcp" => "110,143,25,465,585,993,995"
  )
);

setlocale(LC_ALL, 'de_DE@euro', 'de_DE.utf8', 'de_DE', 'de', 'ge');
?>
