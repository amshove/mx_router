<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################
// Include wichtiger Funktionen
include_once("../global.php");

// Config
$api_ip = "10.10.1.1";            // IP des routers
$api_user = "mx_router";              // $soap_user aus config.inc.php auf dem mx_router 
$api_pw = "";                         // $soap_pw aus config.inc.php auf dem mx_router

$ip = "10.10.";
$subnetz = array(
  "A" => "2",
  "B" => "3",
  "C" => "4",
  "D" => "5",
  "E" => "6",
  "F" => "7",
  "G" => "8",
  "H" => "9",
  "V" => "10"
);

////////////////////////////////////////////////
// Seitettietel
$PAGE->sitetitle = $PAGE->htmltitle = _("Contest Internetfreischaltung"); // Tietel der als THML-Überschrift der Seite angezeigt wird

// genutzte Variablen
$event_id   = $EVENT->next;      // ID des anstehenden Event's
$user_id    = $CURRENT_USER->id;
$date       = date("Y.m.d");
$time       = date("H:i:s");

if(!$user_id ){ $PAGE->error_die($HTML->gettemplate("error_logintopost"));}

// Verbindung zum mx_router herstellen
try{
  $client = new SoapClient("http://$api_ip/soap/Turniere.php?wsdl",array("login"=>$api_user,"password"=>$api_pw));
}catch(Exception $e){
  $output .= "Connect ERROR: ".$e->getMessage();
}

if($client && !empty($_GET["tcid"]) && is_numeric($_GET["tcid"])){
  // DB Abfragen
  $out_turnier = $DB->fetch_array($DB->query("SELECT * FROM t_contest WHERE tcid = '".mysql_real_escape_string($_GET['tcid'])."'"));
  $out_contest = $DB->fetch_array($DB->query("SELECT * FROM t_turnier WHERE tid = '".$out_turnier['tid']."' "));
  $out_contest_a = $DB->fetch_array($DB->query("SELECT * FROM t_teilnehmer WHERE tnid = '".$out_turnier['team_a']."'"));
  $out_contest_b = $DB->fetch_array($DB->query("SELECT * FROM t_teilnehmer WHERE tnid = '".$out_turnier['team_b']."'"));

  $allowed_users = array($out_contest_a['tnleader'],$out_contest_b['tnleader']);
  if(!empty($out_contest_a['tnname'])){
    $query = $DB->query("SELECT user_id FROM t_teilnehmer_part WHERE tnid IN ('".$out_turnier['team_a']."','".$out_turnier['team_b']."')");
    while($row = $DB->fetch_array($query)) $allowed_users[] = $row["user_id"];
  }

  // Gucken, ob der User ueberhaupt mitspielt
  if(!$ADMIN->check(IS_ADMIN) && !in_array($user_id,$allowed_users)){
    $output = "Du bist nicht Teilnehmer dieser Begegnung.";
  }else{
    $output = "<table width='100%' cellspacing='1' cellpadding='2' border='0' class='msg2'>
      <tbody>
        <tr>";
    
    if($out_contest['tlogo'] <> ""){
      $output .= "<td colspan='2' class='msghead'>";
    }else{
      $output .= "<td class='msghead'>";
    }
    
    $output .= "<b>".$out_contest['tname']." --> ".htmlentities($_GET['round'])."</b></td>
        </tr>
        <tr class='msgrow2'>";
    
    if($out_contest['tlogo'] <> ""){
      $output .= "<td><img height='75' src='/images/turnier_logo/".$out_contest['tlogo']."'></td>";
    }
    
    $output .= "<td width='100%' valign='top'><div style='padding-top: 5px; padding-left: 5px;'>
      <b>Contest-ID: ".$out_turnier['tcid']."</b><br><br>";
    
    if(!empty($out_contest_a['tnname'])) $team_a = $out_contest_a['tnname'];
    else{
      $out_contest_name_a = $DB->fetch_array($DB->query("SELECT * FROM user WHERE id = '".$out_contest_a['tnleader']."'"));
      $team_a = $out_contest_name_a['nick'];
    }
  
    if(!empty($out_contest_b['tnname'])) $team_b = $out_contest_b['tnname'];
    else{
      $out_contest_name_b = $DB->fetch_array($DB->query("SELECT * FROM user WHERE id = '".$out_contest_b['tnleader']."'"));
      $team_b = $out_contest_name_b['nick'];
    }
  
    $output .= $team_a." vs. ".$team_b;
  
    $output .= " (".substr($out_turnier['starttime'], 0, 10)." ".substr($out_turnier['starttime'], 11).")</div>";

    $output .= "<br>Bei dieser Funktion werden alle Spieler einer Turnierbegegnung f&uuml;r das Internet freigeschaltet und auf die gleiche Internetleitung gelegt.";
    $output .= "<br>Es reicht aus, wenn ein Spieler die Freischaltung vornimmt - damit werden auch alle weiteren Spieler freigeschaltet.<br>";
  
    //// Ab hier der Kram mit dem Router - vorher war nur Dotlan DB Inhalte ////
    // Ist das Turnier dem Router bekannt?
    try{
      $check = $client->checkTurnier($out_turnier["tid"]);
    }catch(Exception $e){
      $output .= "checkTurnier ERROR: ".$e->getMessage();
    }
    if(!$check){
      $output .= "<br><br><b>Dieses Turnier ben&ouml;tigt keine Internetfreischaltung - oder die Ports sind bereits global freigeschaltet.";
    }else{
      // Internet freischalten
      if($_POST["setInternet"]){
        $reason =  "Contest ".$out_turnier['tcid']." - ".$team_a." vs ".$team_b." - ".$_GET['round'];
  
        // IPs raussuchen
        $ips = array();
        $query = mysql_query("SELECT sitz_nr FROM event_teilnehmer WHERE event_id = '$event_id' AND user_id IN (".implode(",",$allowed_users).")");
        while($row = mysql_fetch_assoc($query)){
          if(preg_match("/([A-HV])\-([0-9][0-9]?)$/",$row['sitz_nr'],$matches) && $matches[1] && $matches[2]){
            $block = $matches[1];
            $platz = (int) $matches[2];
            $ips[] = $ip.$subnetz[$block].".".$platz;
          }
        }
    
        try{
          $result = $client->setInternet($out_turnier['tcid'],$out_turnier['tid'],$ips,$reason);
        }catch(Exception $e){
          $output .= "setInternet ERROR: ".$e->getMessage();
        }
    
        if($result[0]){
          $output .= "<br>Freischaltung erfolgreich durchgef&uuml;hrt.<br>";
        }else{
          $output .= "<br>Beim Freischalten ist ein Fehler aufgetreten: ".$result[1]."<br>";
        }
      }
    
      // Aktuellen Status holen
      try{
        $status = $client->getStatus($_GET["tcid"]);
      }catch(Exception $e){
        $output .= "getStatus ERROR: ".$e->getMessage();
      }

      $output .= "<br>";
      if(!$status){
        if($out_turnier["won"] > 0){
          $output .= "Die Begegnung wurde bereits ausgetragen und das Ergebnis eingetragen.";
        }elseif($out_turnier['ready_a'] <> "0000-00-00 00:00:00" && $out_turnier['ready_b'] <> "0000-00-00 00:00:00" ){
          $output .= "<form action='".$_SERVER["REQUEST_URI"]."' method='POST'><input type='submit' name='setInternet' value='Internet freischalten'></form>";
        }else{
          $output .= "Es sind nicht alle Spieler bereit.";
        }
      }elseif(is_array($status)){
        $output .= "<div style='padding-top: 5px; padding-left: 5px;'>";
        $output .= "<b>Folgende IPs sind freigeschaltet und laufen auf der gleichen Leitung:</b><br>";
        foreach($status as $ip => $leitung){
          $output .= "  ".$ip."<br>";
        }
        $output .= "<br><b>Die Freischaltung wird automatisch entfernt, sobald das <a href='/turnier/?do=contest&id=".$_GET["tcid"]."'>Ergebnis</a> eingetragen wurde.</b>";
        $output .= "</div>";
      }else{
        $output .= "Der Status konnte nicht abgefragt werden.";
      }
    }
    $output .= "</td>
        </tr>
      </tbody>
    </table>";
  }
}else $output .= "<br><br>Es ist ein Fehler aufgetreten.";

$PAGE->render( utf8_decode(utf8_encode($output) ) );
?>
