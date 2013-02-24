<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################
require("leitungen.inc.php");

// MySQL-Settings
$mysql_host = "localhost";
$mysql_user = "mx_router";
$mysql_pw = "--MYSQL_PW--";
$mysql_db = "mx_router";

// Default-PW, was gesetzt wird wenn User angelegt werden
$default_pw = "mx_router";

// Timeslot-Einstellungen, fuer den SelfService per SOAP API
$timeslots = 30;        // x Minuten pro $timeslot_period darf der User sich selbst Internet geben
$timeslot_period = 24;  // nach x Stunden wird der Counter resettet und der User bekommt wieder $timeslot Minuten

// SOAP-API Daten
$soap_user = "mx_router";              // User fuer die API
$soap_pw = "--API_PW--"; // PW fuer die API

// Befehle - muss nicht geaendert werden
$iptables_cmd = "sudo /sbin/iptables";

setlocale(LC_ALL, 'de_DE@euro', 'de_DE.utf8', 'de_DE', 'de', 'ge');
?>
