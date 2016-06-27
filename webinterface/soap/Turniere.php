<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################
$log_ident = substr(md5(mt_rand()),0,5);
openlog("mx_router[soap_turniere_$log_ident]",LOG_ODELAY,LOG_USER); // Logging zu Syslog oeffnen

require("../config.inc.php");
require("../functions.inc.php");

if($_SERVER["REMOTE_ADDR"] != "127.0.0.1" && (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $soap_user || $_SERVER['PHP_AUTH_PW'] != $soap_pw)){
  header('WWW-Authenticate: Basic realm="mx_router SOAP"');
  header('HTTP/1.0 401 Unauthorized');
  echo "Don't Panic!";
  exit;
}else{
  $soap_url = "http://".$_SERVER['HTTP_HOST']."/soap/Turniere.php";
  $wsdl_funktionen = array(
    "checkTurnier" => array(
      "parameter" => array(
        "tid" => "int",
      ),
      "return" => "array",
    ),
    "getStatus" => array(
      "parameter" => array(
        "tcid" => "int",
      ),
      "return" => "array",
    ),
    "setInternet" => array(
      "parameter" => array(
        "tcid" => "int",
        "tid" => "int",
        "ips" => "array",
        "reason" => "string",
      ),
      "return" => "array",
    ),
  );

  if(isset($_GET["wsdl"])){
    echo "<?xml version ='1.0' encoding ='UTF-8' ?>
    <definitions name='SelfService'
      targetNamespace='http://".$_SERVER['HTTP_HOST']."/Turniere'
      xmlns:tns='http://".$_SERVER['HTTP_HOST']."/Turniere'
      xmlns:soap='http://schemas.xmlsoap.org/wsdl/soap/'
      xmlns:xsd='http://www.w3.org/2001/XMLSchema'
      xmlns:soapenc='http://schemas.xmlsoap.org/soap/encoding/'
      xmlns:wsdl='http://schemas.xmlsoap.org/wsdl/'
      xmlns='http://schemas.xmlsoap.org/wsdl/'>";
  
    foreach($wsdl_funktionen as $fkt => $val){
      echo "<message name='".$fkt."Request'>";
      foreach($val["parameter"] as $name => $type) echo "<part name='$name' type='xsd:$type'/>";
      echo "</message>";
  
      echo "<message name='".$fkt."Response'>
              <part name='Result' type='xsd:array'/>
            </message>";
  
  
      echo "<portType name='".$fkt."PortType'>
              <operation name='".$fkt."'>
                <input message='tns:".$fkt."Request'/>
                <output message='tns:".$fkt."Response'/>
              </operation>
            </portType>";
  
      echo "<binding name='".$fkt."Binding' type='tns:".$fkt."PortType'>
              <soap:binding style='rpc' transport='http://schemas.xmlsoap.org/soap/http'/>
              <operation name='".$fkt."'>
                <soap:operation soapAction='urn:Turniere#".$fkt."'/>
                <input>
                  <soap:body use='encoded' namespace='urn:Turniere' encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
                </input>
                <output>
                  <soap:body use='encoded' namespace='urn:Turniere' encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
                </output>
              </operation>
            </binding>";
  
      echo "<service name='".$fkt."Service'>
              <port name='".$fkt."Port' binding='".$fkt."Binding'>
                <soap:address location='$soap_url'/>
              </port>
            </service>";
    }
    echo "</definitions>";
  }else{
    // Guckt nach, ob das Turnier dem Router bekannt ist
    function checkTurnier($tid){
      global $db;
      return mysqli_num_rows(mysqli_query($db,"SELECT * FROM turniere WHERE turnier_id = '".mysqli_real_escape_string($db,$tid)."' LIMIT 1")) > 0;
     // $return                - true/false, ob das Turnier im Router angelegt wurde
    }

    // Gibt den Status fuer das aktuelle Match zurueck
    function getStatus($tcid){
      global $leitungen_fw, $db;
      $tcid = mysqli_real_escape_string($db,$tcid);

      $query = mysqli_query($db,"SELECT ip, leitung FROM history WHERE tcid = '".$tcid."' AND active = 1");
      if(mysqli_num_rows($query) < 1) return false;

      $return = array();
      while($row = mysqli_fetch_assoc($query)){
        $return[$row["ip"]] = $leitungen_fw[$row["leitung"]]["name"];
      }

      return $return;
      // $return                 - false - wenn keine Freischaltung vorhanden
      // $return["<ip>"]         - Name der Leitung auf die die IP freigeschaltet ist
    }

    // Schaltet das Internet fuer ein Match frei und legt die Teilnehmer auf eine bestimmte Leitung
    function setInternet($tcid, $tid, $ips, $reason){ 
      global $db;
      $return = array();
      $tcid = mysqli_real_escape_string($db,$tcid);
      $tid = mysqli_real_escape_string($db,$tid);
      $reason = mysqli_real_escape_string($db,$reason);
      foreach($ips as $ip){
        if(!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/",$ip)){
          $return[0] = false;
          $return[1] = "$ip hat das falsche Format.";
          return $return;
        }
      }
      $now = time();

      my_syslog("Freischalten: tcid $tcid, tid $tid, ips: ".var_export($ips,true).", reason: $reason");

      // Leitung raussuchen
      $l_anz = 500;
      $leitung = -1;
      $l = @own_mysqli_result(mysqli_query($db,"SELECT leitungen FROM turniere WHERE turnier_id = '$tid' LIMIT 1"),0,"leitungen");
      if(!$l){
        $return[0] = false;
        $return[1] = "Dem Turnier sind keine Leitungen zugeordnet.";
        my_syslog($return[1]);
        return $return;
      }
      $query = mysqli_query($db,"SELECT leitung, COUNT(*) as anz FROM history WHERE leitung IN ($l) AND active = 1 GROUP BY leitung");
      $db_anz = array();
      my_syslog("Vorhandene Freischaltungen pro Leitung:");
      while($row = mysqli_fetch_assoc($query)){
        $db_anz[$row["leitung"]] = $row["anz"];
        my_syslog($row["leitung"]." => ".$row["anz"]);
      }
      foreach(explode(",",$l) as $lt){
        $anz = 0;
        if($db_anz[$lt] > 0) $anz = $db_anz[$lt];

        if($anz < $l_anz){
          $leitung = $lt;
          $l_anz = $anz;
        }
      }
      my_syslog("Leitung rausgesucht: $leitung");
      if($leitung < 1){
        $return[0] = false;
        $return[1] = "Es wurde keine Leitung gefunden.";
        my_syslog($return[1]);
        return $return;
      }
      
      // IPs durchgehen
      foreach($ips as $ip){
        $old_id = 0;
        $query = mysqli_query($db,"SELECT id FROM history WHERE active = 1 AND ip = '$ip' LIMIT 1");
        if(mysqli_num_rows($query) > 0){
          // Bereits freigeschaltet - loesche alte Regel erstmal
          my_syslog("Alte Freischaltung fuer $ip gefunden - wird erst mal geloescht");
          $old_id = own_mysqli_result($query,0,"id");
          if(!rule_del($old_id,"Turniere")){
            $return[0] = false;
            $return[1] = "$ip hatte bereits eine Freischaltung - es ist ein Fehler aufgetreten beim Entfernen";
            my_syslog($return[1]);
            return $return;
          }
        }

        // Neue Regel anlegen
        mysqli_query($db,"INSERT INTO history SET ip = '".$ip."', leitung = '$leitung', add_user = 'Turniere', add_date = '".$now."', active = -1, tcid = '$tcid', old_id = '$old_id', reason = '$reason'");
        my_syslog("Freiscaltung anlegen fuer $ip");
        $id = mysqli_insert_id($db);
        if(!($id > 0 && rule_add($id))){
          $return[0] = false;
          $return[1] = "Es ist ein Fehler aufgetreten beim Freischalten von $ip";
          my_syslog($return[1]);
          return $return;
        }
      }

      $return[0] = true;
      $return[1] = "Freischaltung erfolgreich.";
      my_syslog($return[1]);
      return $return;
      // $return[0]              - true/false - Freischaltung erfolgreich/nicht erfolgreich
      // $return[1]              - (Fehler-)meldung
    }
    
    ## Funktionen registrieren
    $server = new SoapServer("http://127.0.0.1/soap/Turniere.php?wsdl",array('encoding'=>'ISO-8859-1'));
    foreach($wsdl_funktionen as $fkt => $val) $server->addFunction($fkt);
    $server->handle();
  }
}
?>
