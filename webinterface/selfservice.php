<?php
############################################################
# Router Webinterface                                      #
# Copyright (C) 2010 Torsten Amshove <torsten@amshove.net> #
############################################################

if($_SESSION["ad_level"] >= 1){
  if($_GET["cmd"] == "reset" && $_SESSION["ad_level"] >= 4){
    mysql_query("DELETE FROM timeslots WHERE ip = '".mysql_real_escape_string($_GET["ip"])."'");
    echo "<div class='meldung_ok'>Kontingent f&uuml;r ".$_GET["ip"]." zur&uuml;ckgesetzt.</div><br>";
  }

  echo "<h3>&Uuml;bersicht der SelfService-Kontingente</h3>";
  echo "Kontingent pro IP: $timeslots Min<br>Reset nach: $timeslot_period Std";
  echo "<table>";
  echo "  <tr>";
  echo "    <th width='100'>IP</th>";
  echo "    <th width='130'>DNS</th>";
  echo "    <th width='50'>Used</th>";
  echo "    <th width='50'>Free</th>";
  echo "    <th width='100'>Reset des Kont.</th>";
  if($_SESSION["ad_level"] >= 4) echo "    <th width='30'>&nbsp;</th>";
  echo "  </tr>";
  $i=0;
  $query = mysql_query("SELECT * FROM timeslots ORDER BY INET_ATON(ip)");
  while($row = mysql_fetch_assoc($query)){
    $dns = gethostbyaddr($row["ip"]);
    echo "<tr";
    if(($i % 2) > 0) echo " class='odd_row'";
    echo ">";
    echo "  <td>".$row["ip"]."</td>";
    echo "  <td>".($dns == $row["ip"] ? "" : $dns)."</td>";
    echo "  <td>".$row["used"]." Min</td>";
    echo "  <td>".($timeslots-$row["used"])." Min</td>";
    echo "  <td align='center'>".strftime("%a, %R Uhr",($row["period_start"]+($timeslot_period*60*60)))."</td>";
    if($_SESSION["ad_level"] >= 4) echo "  <td align='center'><a onClick='return confirm(\"Kontingent f&uuml;r ".$row["ip"]." wirklich zur&uuml;cksetzen?\");' href='index.php?page=selfservice&cmd=reset&ip=".$row["ip"]."'>reset</a></td>";
    echo "</tr>";
    $i++;
  }
  echo "</table>";
}
?>
