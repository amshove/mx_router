<?php
include("../global.php");
$output = "";

$api_ip = "192.168.2.180";
$api_user = "mx_router";
$api_pw = "lkasdasdkifjha980sdujasd";

try{
  $client = new SoapClient("http://$api_ip/soap/SelfService.php?wsdl",array("login"=>$api_user,"password"=>$api_pw));
//  $client = new SoapClient("http://$api_ip/soap/SelfService.php?wsdl");
}catch(Exception $e){
  $output .= "Connect ERROR: ".$e->getMessage();
}

if($client){
  if($_POST["freischalten"]){
    $reason = "dotlan User: ";
    if($CURRENT_USER->nick) $reason .= $CURRENT_USER->nick;
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
  
  $output .= "<hr>";
  if(!$status["online"]){
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
