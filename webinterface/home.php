<?php
############################################################
# Router Webinterface                                      #
# Copyright (C) 2010 Torsten Amshove <torsten@amshove.net> #
############################################################

if($_SESSION["ad_level"] >= 1){
  echo "<table>";
  echo "  <tr>";
  echo "    <th width='70'>Status:</th>";
  foreach($leitungen as $leitung){
    if(ping($leitung["ip"]) == 0) $class = "meldung_ok";
    else $class = "meldung_error";
    echo "  <td class='$class' width='70' align='center'>".$leitung["name"]."</td>";
  }
  echo "  </tr>";
  echo "</table><br><br>";



  if($_POST["add"]){
    if(empty($_POST["ip"])){
      echo "<div class='meldung_error'>IP muss angegeben werden!</div><br>";
    }elseif(!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/",$_POST["ip"]) && !preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\/[0-9]{1,2}$/",$_POST["ip"])){
      echo "<div class='meldung_error'>Keine g&uuml;ltige IP-Adresse angegeben.</div><br>";
    }else{
      if(iptables_add($_POST["ip"])){
        $now = time();
        if(empty($_POST["end_date"])) $end_date = 0;
        else{
          $end_date = $now + ($_POST["end_date"]*60);
        }
        mysql_query("INSERT INTO history SET user_id = '0', ip = '".$_POST["ip"]."', add_user = '".$_SESSION["user_name"]."', add_date = '".$now."', end_date = '".$end_date."', active = 1, reason = '".mysql_real_escape_string($_POST["reason"])."'");
        echo "<div class='meldung_ok'>Regel erfolgreich erstellt</div><br>";
      }else{
        echo "<div class='meldung_error'>Regel konnte nicht angelegt werden!</div><br>";
      }
    }
  }

  if($_GET["cmd"] == "del" && !empty($_GET["id"]) && is_numeric($_GET["id"])){
    $id = mysql_real_escape_string($_GET["id"]);
    $ip = @mysql_result(mysql_query("SELECT ip FROM history WHERE id = '".$id."' LIMIT 1"),0,"ip");
    if(!empty($ip) && iptables_del($ip)){
      mysql_query("UPDATE history SET active = 0, del_user = '".$_SESSION["user_name"]."', del_date = '".time()."' WHERE id = '".$id."' LIMIT 1");
      echo "<div class='meldung_ok'>Regel erfolgreich gel&ouml;scht.</div><br>";
    }else{
      echo "<div class='meldung_error'>Regel konnte nicht gel&ouml;scht werden!</div><br>";
    }
  }


  echo "<h3>Neue Freigabe erteilen</h3>";
  echo "<form action='index.php' method='POST'>";
  echo "<table>";
  echo "  <tr>";
  echo "    <th colspan='2'>&nbsp;</th>";
  echo "  </tr>";
  echo "  <tr>";
  echo "    <td>IP:</td>";
  echo "    <td><input type='text' name='ip'></td>";
  echo "  </tr>";
  echo "  <tr>";
  echo "    <td>Wie lange?</td>";
  echo "    <td><select name='end_date'>
    <option value='0'>dauerhaft</option>
    <option value='10'>10 min</option>
    <option value='30' selected>30 min</option>
    <option value='60'>1 std</option>
    <option value='120'>2 std</option>
    </td>";
  echo "  </tr>";
  echo "  <tr>";
  echo "    <td>Grund:</td>";
  echo "    <td><textarea name='reason'></textarea></td>";
  echo "  </tr>";
  echo "  <tr>";
  echo "    <td colspan='2' align='center'><input type='submit' name='add' value='anlegen'></td>";
  echo "  </tr>";
  echo "</table>";
  echo "</form><br><br>";


  echo "<h3>Aktuelle Freigaben</h3>";
  echo "<table class='hover_row'>";
  echo "  <tr>";
  echo "    <th width='100'>IP</th>";
  echo "    <th width='150'>Grund</th>";
  echo "    <th width='130'>Angelegt von</th>";
  echo "    <th width='100'>L&auml;uft ab ..</th>";
  echo "    <th width='30'>&nbsp;</th>";
  echo "  </tr>";

  $iptables_lines = iptables_list();

  $i = 0;
  $query = mysql_query("SELECT * FROM history WHERE active = 1 ORDER BY INET_ATON(ip)");
  while($row = mysql_fetch_assoc($query)){ 
    echo "<tr";
    if(($i % 2) > 0) echo " class='odd_row'";
    if(!in_array($row["ip"],$iptables_lines)) echo " style='background-color: #CC9999;'";
    echo ">";
    echo "  <td valign='top'>".$row["ip"]."</td>";
    echo "  <td valign='top'>".nl2br($row["reason"])."</td>";
    echo "  <td valign='top'>".$row["add_user"]."</td>";
    echo "  <td valign='top' align='center'>";
    if(empty($row["end_date"])) echo "nie";
    else echo strftime("%a, %R Uhr",$row["end_date"]);
    echo "  </td>";
    echo "  <td valign='top'><a onClick='return confirm(\"Freigabe f&uuml;r ".$row["ip"]." wirklich l&ouml;schen?\");' href='index.php?cmd=del&id=".$row["id"]."'>del</a></td>";
    echo "</tr>";
    $i++;
  }
  echo "</table>";
}
?>
