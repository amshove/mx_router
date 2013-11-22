<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################
include("../global.php");
$output = "";

$api_ip = "10.10.1.1";                // IP des routers
$api_user = "mx_router";              // $soap_user aus config.inc.php auf dem mx_router 
$api_pw = "";                         // $soap_pw aus config.inc.php auf dem mx_router

// Verbindung zum Router herstellen
try{
  $client = new SoapClient("http://$api_ip/soap/SelfService.php?wsdl",array("login"=>$api_user,"password"=>$api_pw));
}catch(Exception $e){
  $output .= "Connect ERROR: ".$e->getMessage();
}

if($client){
  // Internet freischalten lassen
  if($_POST["freischalten"]){
    $reason = "dotlan User: ";
    if($CURRENT_USER->nick) $reason .= urlencode($CURRENT_USER->nick);
    else $reason .= "User not logged in.";

    try{
      $result = $client->setInternet($_SERVER["REMOTE_ADDR"],$reason,$_POST["time"],false); // false steht fuer admin=false - wenn man dort true eingibt, wird die Zeit nicht vom Kontingent abgezogen
    }catch(Exception $e){
      $output .= "setInternet ERROR: ".$e->getMessage();
    }
    // $result[0]              - true/false - Freischaltung erfolgreich/nicht erfolgreich
    // $result[1]              - (Fehler-)meldung
    if(!$result[0]) $output .= $result[1]."<br><br>";
  }
  
  // Aktuellen Status vom Server holen
  try{
    $status = $client->getStatus($_SERVER["REMOTE_ADDR"]);
  }catch(Exception $e){
    $output .= "getStatus ERROR: ".$e->getMessage();
  }
  // $status["used"]           - x Minuten bereits benutzt
  // $status["timeslots"]      - x Minuten Kontingent insgesamt
  // $status["free"]           - x Minuten noch frei vom Kontingent
  // $status["period"]         - Zeitraum in Stunden, wie lange das Kontingent gilt
  // $status["period_reset"]   - Timestamp, wann das Kontingent resettet wird
  // $status["online"]         - true/false
  // $status["online_end"]     - wenn online=true: Timestamp, wann die Online-Zeit endet - 0 = kein Ende
  
  // Webseiten-Inhalt mit Status
  $output .= "<h2>Internet SelfService</h2>";
  $output .= "Hier kannst du dir den Internetzugang selbst freischalten.<br>";
  $output .= "Du hast ein Kontingent von ".$status["timeslots"]." Minuten. Nach ".$status["period"]." Stunden wird dein Account wieder zur&uuml;ckgesetzt und du hast wieder das volle Kontingent.<br><br>";
  
  $output .= "Dein aktueller Internet-Status: ";
  if($status["online"]){
    $output .= "<font color='#00FF00'>online</font> - l&auml;uft ";
    if($status["online_end"] == 0) $output .= "nie";
    else $output .= "in ".round(($status["online_end"]-time())/60)." Minuten";
    $output .= " ab.";
  }else{
    $output .= "<font color='#FF0000'>offline</font>";
  }
  $output .= "<br>";
  $output .= "Bereits verbraucht: ".$status["used"]."/".$status["timeslots"]." Minuten<br>";
  $output .= "Noch frei: ".$status["free"]." Minuten<br>";
  $output .= "Kontingent wird zur&uuml;ckgesetzt: ".date("d.m.Y",$status["period_reset"])." ".date("H:i",$status["period_reset"])." Uhr<br>";
  
  // Status der globalen Freigaben abfragen
  try{
    $global_status = $client->getGlobal($_SERVER["REMOTE_ADDR"]);
  }catch(Exception $e){
    echo "getGlobal ERROR: ".$e->getMessage();
  }
  // $global_status[]["name"]    - Name der globalen Freigabe
  // $global_status[]["online"]  - true/false

  // Wenn globale Freigaben existieren und aktiviert sind, Liste mit aktiven Freischaltungen anzeigen
  $global_exists = false;
  $tmp_output = "<hr><b>Globale Freigaben:</b><br>";
  foreach($global_status as $service){
    if($service["online"]){
      $tmp_output .= $service["name"]."<br>";
      $global_exists = true;
    }
  }
  if($global_exists) $output .= $tmp_output;

  // Wenn nicht bereits online, dann formular zur Selbstfreischaltung anzeigen
  if(!$status["online"]){
    $output .= "<hr>";
    $output .= "<form action='".$_SERVER["PHP_SELF"]."' method='POST'>";
    $output .= "<select name='time'>";
    $output .= "  <option value='5'>5 Minuten</option>";
    $output .= "  <option value='10'>10 Minuten</option>";
    $output .= "  <option value='20'>20 Minuten</option>";
    $output .= "  <option value='30'>30 Minuten</option>";
    $output .= "</select><br>";
    $output .= "<input type='submit' name='freischalten' value='Internet freischalten'>";
    $output .= "</form>";
  }
}else $output .= "<br><br>Es ist ein Fehler aufgetreten.";

$PAGE->render($output);
?>
