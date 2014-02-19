<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

if($_SESSION["ad_level"] >= 1){
  if($_GET["cmd"] == "reset" && $_SESSION["ad_level"] >= 4){
    mysql_query("DELETE FROM timeslots WHERE ip = '".mysql_real_escape_string($_GET["ip"])."'");
    echo "<div class='meldung_ok'>Kontingent f&uuml;r ".$_GET["ip"]." zur&uuml;ckgesetzt.</div><br>";
  }

  echo "<h3>&Uuml;bersicht der SelfService-Kontingente</h3>";
  echo "Kontingent pro IP: $timeslots Min<br>Reset nach: $timeslot_period Std";
  echo "<table class='sortierbar'>";
  echo "  <thead>";
  echo "  <tr>";
  echo "    <th class='sortierbar vorsortiert+' width='100'>IP</th>";
  echo "    <th class='sortierbar' width='130'>DNS</th>";
  echo "    <th class='sortierbar' width='50'>Used</th>";
  echo "    <th class='sortierbar' width='50'>Free</th>";
  echo "    <th class='sortierbar' width='100'>Reset des Kont.</th>";
  if($_SESSION["ad_level"] >= 4) echo "    <th width='30'>&nbsp;</th>";
  echo "  </tr>";
  echo "  </thead>";
  echo "  <tbody>";
  $query = mysql_query("SELECT * FROM timeslots ORDER BY INET_ATON(ip)");
  while($row = mysql_fetch_assoc($query)){
    $dns = gethostbyaddr($row["ip"]);
    echo "<tr>";
    echo "  <td>".$row["ip"]."</td>";
    echo "  <td>".($dns == $row["ip"] ? "" : $dns)."</td>";
    echo "  <td>".$row["used"]." Min</td>";
    echo "  <td>".($timeslots-$row["used"])." Min</td>";
    echo "  <td align='center'>".strftime("%a, %R Uhr",($row["period_start"]+($timeslot_period*60*60)))."</td>";
    if($_SESSION["ad_level"] >= 4) echo "  <td align='center'><a onClick='return confirm(\"Kontingent f&uuml;r ".$row["ip"]." wirklich zur&uuml;cksetzen?\");' href='index.php?page=selfservice&cmd=reset&ip=".$row["ip"]."'>reset</a></td>";
    echo "</tr>";
  }
  echo "  </tbody>";
  echo "</table>";
}
?>
