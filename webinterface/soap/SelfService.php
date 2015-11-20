<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################
$log_ident = substr(md5(mt_rand()),0,5);
openlog("mx_router[soap_selfservice_$log_ident]",LOG_ODELAY,LOG_USER); // Logging zu Syslog oeffnen

require("../config.inc.php");
require("../functions.inc.php");

if($_SERVER["REMOTE_ADDR"] != "127.0.0.1" && (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $soap_user || $_SERVER['PHP_AUTH_PW'] != $soap_pw)){
  header('WWW-Authenticate: Basic realm="mx_router SOAP"');
  header('HTTP/1.0 401 Unauthorized');
  echo "Don't Panic!";
  exit;
}else{
  if(isset($_GET["wsdl"])){
    echo "<?xml version ='1.0' encoding ='UTF-8' ?>
    <definitions name='SelfService'
      targetNamespace='http://".$_SERVER['HTTP_HOST']."/SelfService'
      xmlns:tns='http://".$_SERVER['HTTP_HOST']."/SelfService'
      xmlns:soap='http://schemas.xmlsoap.org/wsdl/soap/'
      xmlns:xsd='http://www.w3.org/2001/XMLSchema'
      xmlns:soapenc='http://schemas.xmlsoap.org/soap/encoding/'
      xmlns:wsdl='http://schemas.xmlsoap.org/wsdl/'
      xmlns='http://schemas.xmlsoap.org/wsdl/'> 
    
    <message name='getStatusRequest'>
      <part name='ip' type='xsd:string'/>
    </message> 
    <message name='getStatusResponse'>
      <part name='Result' type='xsd:array'/>
    </message> 
    
    <portType name='getStatusPortType'>
      <operation name='getStatus'>
        <input message='tns:getStatusRequest'/>
        <output message='tns:getStatusResponse'/>
      </operation>
    </portType> 
    
    <binding name='getStatusBinding' type='tns:getStatusPortType'>
      <soap:binding style='rpc'
        transport='http://schemas.xmlsoap.org/soap/http'/>
      <operation name='getStatus'>
        <soap:operation soapAction='urn:SelfService#getStatus'/>
        <input>
          <soap:body use='encoded' namespace='urn:SelfService'
            encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
        </input>
        <output>
          <soap:body use='encoded' namespace='urn:SelfService'
            encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
        </output>
      </operation>
    </binding> 
    
    <service name='getStatusService'>
      <port name='getStatusPort' binding='getStatusBinding'>
        <soap:address location='http://".$_SERVER['HTTP_HOST']."/soap/SelfService.php'/>
      </port>
    </service>



    <message name='getGlobalRequest'>
      <part name='ip' type='xsd:string'/>
    </message> 
    <message name='getGlobalResponse'>
      <part name='Result' type='xsd:array'/>
    </message> 
    
    <portType name='getGlobalPortType'>
      <operation name='getGlobal'>
        <input message='tns:getGlobalRequest'/>
        <output message='tns:getGlobalResponse'/>
      </operation>
    </portType> 
    
    <binding name='getGlobalBinding' type='tns:getGlobalPortType'>
      <soap:binding style='rpc'
        transport='http://schemas.xmlsoap.org/soap/http'/>
      <operation name='getGlobal'>
        <soap:operation soapAction='urn:SelfService#getGlobal'/>
        <input>
          <soap:body use='encoded' namespace='urn:SelfService'
            encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
        </input>
        <output>
          <soap:body use='encoded' namespace='urn:SelfService'
            encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
        </output>
      </operation>
    </binding> 
    
    <service name='getGlobalService'>
      <port name='getGlobalPort' binding='getGlobalBinding'>
        <soap:address location='http://".$_SERVER['HTTP_HOST']."/soap/SelfService.php'/>
      </port>
    </service>



    <message name='setInternetRequest'>
      <part name='ip' type='xsd:string'/>
      <part name='reason' type='xsd:string'/>
      <part name='time' type='xsd:int'/>
      <part name='admin' type='xsd:boolean'/>
    </message> 
    <message name='setInternetResponse'>
      <part name='Result' type='xsd:array'/>
    </message> 
    
    <portType name='setInternetPortType'>
      <operation name='setInternet'>
        <input message='tns:setInternetRequest'/>
        <output message='tns:setInternetResponse'/>
      </operation>
    </portType> 
    
    <binding name='setInternetBinding' type='tns:setInternetPortType'>
      <soap:binding style='rpc'
        transport='http://schemas.xmlsoap.org/soap/http'/>
      <operation name='setInternet'>
        <soap:operation soapAction='urn:SelfService#setInternet'/>
        <input>
          <soap:body use='encoded' namespace='urn:SelfService'
            encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
        </input>
        <output>
          <soap:body use='encoded' namespace='urn:SelfService'
            encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
        </output>
      </operation>
    </binding> 
    
    <service name='setInternetService'>
      <port name='setInternetPort' binding='setInternetBinding'>
        <soap:address location='http://".$_SERVER['HTTP_HOST']."/soap/SelfService.php'/>
      </port>
    </service>
    </definitions>";
  }else{
    // Gibt den Status zurueck (freite/benutzte Minuten, Reset-Zeit, Online-Status, ..)
    function getStatus($ip){
      global $timeslots, $timeslot_period;

      $ip = mysql_real_escape_string($ip);
      $status = array();
      $status["used"] = 0;
      $status["timeslots"] = $timeslots;
      $status["period"] = $timeslot_period;
      $status["period_reset"] = time()+($timeslot_period*60*60);
      $status["online"] = false;

      $query = mysql_query("SELECT * FROM timeslots WHERE ip = '".$ip."' LIMIT 1");
      if(mysql_num_rows($query) > 0){
        $status["used"] = mysql_result($query,0,"used");
        $status["period_reset"] = mysql_result($query,0,"period_start")+($timeslot_period*60*60);
      }
      $status["free"] = $timeslots-$status["used"];

      $iptables_list = iptables_list();
      if(in_array($ip,$iptables_list[0])){
        $status["online"] = true;
        $status["online_end"] = @mysql_result(mysql_query("SELECT end_date FROM history WHERE ip = '".$ip."' AND active = 1 LIMIT 1"),0,"end_date");
      }else{
        foreach($iptables_list[0] as $subnet){
          if($subnet != "0.0.0.0/0" && cidr_match($ip,$subnet)){
            $status["online"] = true;
            $status["online_end"] = @mysql_result(mysql_query("SELECT end_date FROM history WHERE ip = '".mysql_real_escape_string($subnet)."' AND active = 1 LIMIT 1"),0,"end_date");
          }
        }
      }

      return $status;
      // $status["used"]           - x Minuten bereits benutzt
      // $status["timeslots"]      - x Minuten Kontingent insgesamt
      // $status["free"]           - x Minuten noch frei vom Kontingent
      // $status["period"]         - Zeitraum in Stunden, wie lange das Kontingent gilt
      // $status["period_reset"]   - Timestamp, wann das Kontingent resettet wird
      // $status["online"]         - true/false
      // $status["online_end"]     - wenn online=true: Timestamp, wann die Online-Zeit endet - 0 = kein Ende
    }

    // Gibt den Status der globalen Freigaben an
    function getGlobal($ip){
      $global_status = array();
      $i = 0;
      $query = mysql_query("SELECT id,name FROM ports WHERE active = 1");
      while($row = mysql_fetch_assoc($query)){
        $global_status[$i]["name"] = $row["name"];

        $status = ports_open($row["id"]);
        if($status[0] == true && $status[1] == true) $global_status[$i]["online"] = true;
        else $global_status[$i]["online"] = false;

        $i++;
      }
      return $global_status;
      // $global_status[]["name"]    - Name der globalen Freigabe
      // $global_status[]["online"]  - true/false
    }

    // Schaltet das Internet fuer eine bestimmte Zeit in Minuten frei
    // Wenn $admin = true, werden die Timeslots nicht berechnet
    function setInternet($ip,$reason,$time,$admin){
      global $timeslots, $timeslot_period;

      $ip = mysql_real_escape_string($ip);
      $reason = mysql_real_escape_string(urldecode($reason));
      $used = 0;
      $period_start = time();
      $return = array();

      $query = mysql_query("SELECT * FROM timeslots WHERE ip = '".$ip."' LIMIT 1");
      if(mysql_num_rows($query) > 0){
        $used = mysql_result($query,0,"used");
        $period_start = mysql_result($query,0,"period_start");
      }

      my_syslog("Freischaltung fuer $ip fuer $time: $reason");
      my_syslog("Used: $used, Period_Start: $peroid_start");
      $iptables_list = iptables_list();
      if(in_array($ip,$iptables_list[0])){
        $return[0] = false;
        $return[1] = "Das Internet ist bereits freigeschaltet.";
        my_syslog($return[1]);
      }elseif(!$admin && $time < 1){
        $return[0] = false;
        $return[1] = "Du darfst keine uneingeschr&auml;nkte Freischaltung vornehmen."; 
        my_syslog($return[1]);
      }elseif(!$admin && $time > ($timeslots-$used)){
        $return[0] = false;
        $return[1] = "Es sind nicht mehr genug Minuten frei.";
        my_syslog($return[1]);
      }else{
        $now = time();
        $end_date = $now + ($time*60);
        mysql_query("INSERT INTO history SET ip = '".$ip."', add_user = 'SelfService', add_date = '".$now."', end_date = '".$end_date."', active = -1, reason = '$reason'");
        $id = mysql_insert_id();
        if($id > 0 && rule_add($id)){
          $used = $used+$time;
          if(!$admin) mysql_query("INSERT INTO timeslots SET ip = '".$ip."', used = '".$used."', period_start = '".$period_start."' ON DUPLICATE KEY UPDATE used = '".$used."'");
          $return[0] = true;
          $return[1] = "Das Internet ist jetzt f&uuml;r $time Minuten freigeschaltet.";
          my_syslog($return[1]);
        }else{
          $return[0] = false;
          $return[1] = "Es ist ein Fehler aufgetreten.";
          my_syslog($return[1]);
        }
      }
      return $return;
      // $return[0]              - true/false - Freischaltung erfolgreich/nicht erfolgreich
      // $return[1]              - (Fehler-)meldung
    }
    
    #$server = new SoapServer("http://".$_SERVER['HTTP_HOST']."/soap/SelfService.php?wsdl");
    $server = new SoapServer("http://127.0.0.1/soap/SelfService.php?wsdl");
    $server->addFunction("getStatus");
    $server->addFunction("getGlobal");
    $server->addFunction("setInternet");
    $server->handle();
  }
}
?>
