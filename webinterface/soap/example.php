<?php
$api_ip = "127.0.0.1";
$api_user = "mx_router";
$api_pw = "lkasdasdkifjha980sdujasd";

try{
  $client = new SoapClient("http://$api_ip/soap/SelfService.php?wsdl",array("login"=>$api_user,"password"=>$api_pw));
}catch(Exception $e){
  echo "Connect ERROR: ".$e->getMessage();
}

if($client){
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
  
  echo "<hr>";
  if(!$status["online"]){
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
