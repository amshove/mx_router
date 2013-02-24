<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################
$api_ip = "127.0.0.1";                // IP des routers
$api_user = "mx_router";              // $soap_user aus config.inc.php auf dem mx_router 
$api_pw = ""; // $soap_pw aus config.inc.php auf dem mx_router

// Verbindung zum Router herstellen
try{
  $client = new SoapClient("http://$api_ip/soap/SelfService.php?wsdl",array("login"=>$api_user,"password"=>$api_pw));
}catch(Exception $e){
  echo "Connect ERROR: ".$e->getMessage();
}

if($client){
  // Internet freischalten lassen
  if($_POST["freischalten"]){
    try{
      $result = $client->setInternet($_SERVER["REMOTE_ADDR"],"Hier kann das Begruendungsfeld gefuellt werden.",$_POST["time"],false); // false steht fuer admin=false - wenn man dort true eingibt, wird die Zeit nicht vom Kontingent abgezogen
    }catch(Exception $e){
      echo "setInternet ERROR: ".$e->getMessage();
    }
    // $result[0]              - true/false - Freischaltung erfolgreich/nicht erfolgreich
    // $result[1]              - (Fehler-)meldung
    if(!$result[0]) echo $result[1]."<br><br>";
  }
  
  // Aktuellen Status vom Server holen
  try{
    $status = $client->getStatus($_SERVER["REMOTE_ADDR"]);
  }catch(Exception $e){
    echo "getStatus ERROR: ".$e->getMessage();
  }
  // $status["used"]           - x Minuten bereits benutzt
  // $status["timeslots"]      - x Minuten Kontingent insgesamt
  // $status["free"]           - x Minuten noch frei vom Kontingent
  // $status["period"]         - Zeitraum in Stunden, wie lange das Kontingent gilt
  // $status["period_reset"]   - Timestamp, wann das Kontingent resettet wird
  // $status["online"]         - true/false
  // $status["online_end"]     - wenn online=true: Timestamp, wann die Online-Zeit endet - 0 = kein Ende
  

  // Webseiten-Inhalt mit Status
  echo "<h2>Internet SelfService</h2>";
  echo "Hier kannst du dir den Internetzugang selbst freischalten.<br>";
  echo "Du hast ein Kontingent von ".$status["timeslots"]." Minuten. Nach ".$status["period"]." Stunden wird dein Account wieder zur&uuml;ckgesetzt und du hast wieder das volle Kontingent.<br><br>";
  
  echo "Dein aktueller Internet-Status: ";
  if($status["online"]){
    echo "<font color='#00FF00'>online</font> - l&auml;uft ";
    if($status["online_end"] == 0) echo "nie";
    else echo "in ".round(($status["online_end"]-time())/60)." Minuten";
    echo " ab.";
  }else{
    echo "<font color='#FF0000'>offline</font>";
  }
  echo "<br>";
  echo "Bereits verbraucht: ".$status["used"]."/".$status["timeslots"]." Minuten<br>";
  echo "Noch frei: ".$status["free"]." Minuten<br>";
  echo "Kontingent wird zur&uuml;ckgesetzt: ".date("d.m.Y",$status["period_reset"])." ".date("H:i",$status["period_reset"])." Uhr<br>";

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
  $output = "<hr><b>Globale Freigaben:</b><br>";
  foreach($global_status as $service){
    if($service["online"]){
      $output .= $service["name"]."<br>";
      $global_exists = true;
    }
  }
  if($global_exists) echo $output;
  
  // Wenn nicht bereits online, dann formular zur Selbstfreischaltung anzeigen
  if(!$status["online"]){
    echo "<hr>";
    echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST'>";
    echo "<select name='time'>";
    echo "  <option value='5'>5 Minuten</option>";
    echo "  <option value='10'>10 Minuten</option>";
    echo "  <option value='20'>20 Minuten</option>";
    echo "  <option value='30'>30 Minuten</option>";
    echo "</select><br>";
    echo "<input type='submit' name='freischalten' value='Internet freischalten'>";
    echo "</form>";
  }
}else echo "<br><br>Es ist ein Fehler aufgetreten.";
?>
