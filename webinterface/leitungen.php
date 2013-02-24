<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

if($_SESSION["ad_level"] >= 1){
  $test_ip = array();
  $test_port = array();
  $test_proto = "tcp";
  if($_POST["proto"] == "udp") $test_proto = "udp";

  $ip_status = array();
  $iptables_leitungen = iptables_list(true);
  foreach($iptables_leitungen[0] as $ip => $leitung){
    $ip_status[$leitung][] = $ip;
  }

  $port_status = array();
  $query = mysql_query("SELECT * FROM ports");
  while($row = mysql_fetch_assoc($query)){
    $tmp = ports_open($row["id"],true);
    $port_status[$tmp[2]][] = $row;
  }

  echo "<h3>Zuordnung der Leitungen</h3>";
  echo "Die Portzuordnung ist dominierend.";
  echo "<table>";
  echo "  <tr>";
  echo "    <th width='100'>Leitung:</th>";
  foreach($leitungen as $leitung){
    if(ping($leitung["ip"],$leitung["eth"]) == 0) $class = "meldung_ok";
    else $class = "meldung_error";
    echo "  <th class='$class' width='250'>".$leitung["name"]."</th>";
  }
  echo "  </tr>";
  echo "  <tr>";
  echo "    <th valign='top'>IPs:</th>";
  foreach($leitungen as $leitung){
    echo "  <td valign='top'>";
    if($ip_status[$leitung["fw_mark"]]){
      foreach($ip_status[$leitung["fw_mark"]] as $ip){
        if($ip == $local_net) $ip .= " (default)";
        if($_POST["src"] && cidr_match($_POST["src"],$ip)) $test_ip[$ip] = $leitung["name"];
        echo "$ip<br>";
      }
    }
    echo "  </td>";
  }
  echo "  </tr>";
  echo "  <tr>";
  echo "    <th valign='top'>Ports:</th>";
  foreach($leitungen as $leitung){
    echo "  <td valign='top'>";
    if($port_status[$leitung["fw_mark"]]){
      foreach($port_status[$leitung["fw_mark"]] as $ports){
        if($_POST["dport"] && port_match($_POST["dport"],$ports[$test_proto])) $test_port[$ports["name"]] = $leitung["name"];
        echo "<b>".$ports["name"].":</b><br>T: ".$ports["tcp"]."<br>U: ".$ports["udp"]."<br>";
      }
    }
    echo "  </td>";
  }
  echo "  </tr>";
  echo "</table>";

  echo "<br><br>";

  if($_POST["src"] || $_POST["dport"]){
    $i=0;
    foreach($test_ip as $ip => $line){
      if($i > 0) echo "Die vorherige Regel wird &uuml;berschrieben von: ";
      echo "IP-Regel <b>$ip</b> greift und schickt ".htmlentities($_POST["src"])." auf $line<br>";
      $i++;
    }
    $i=0;
    foreach($test_port as $name => $line){
      if($i > 0 || count($test_ip) > 0) echo "Die vorherige Regel wird &uuml;berschrieben von: ";
      echo "Port-Regel <b>$name</b> greift und schickt alles mit Zielport ".htmlentities($_POST["dport"])." ($test_proto) auf $line<br>";
      $i++;
    }
    if(count($test_ip) == 0 && count($test_port) == 0) echo "Es wird die default-Leitung verwendet.<br>";
    echo "<br>";
  }

  echo "<form action='index.php?page=leitungen' method='POST'>";
  echo "<table>";
  echo "  <tr>";
  echo "    <th colspan='2'>Leitungszuordnung testen</th>";
  echo "  </tr>";
  echo "  <tr>";
  echo "    <td>Quell-IP:</td>";
  echo "    <td><input type='text' name='src'></td>";
  echo "  </tr>";
  echo "  <tr>";
  echo "    <td colspan='2' align='center'>UND / ODER</td>";
  echo "  </tr>";
  echo "  <tr>";
  echo "    <td>Ziel-Port:</td>";
  echo "    <td><input type='text' name='dport'><br><input type='radio' name='proto' value='tcp' checked='checked'>TCP <input type='radio' name='proto' value='udp'>UDP</td>";
  echo "  </tr>";
  echo "  <tr>";
  echo "    <td colspan='2' align='center'><input type='submit' name='test' value='testen'></td>";
  echo "  </tr>";
  echo "</table>";
  echo "</form>";
}
?>
