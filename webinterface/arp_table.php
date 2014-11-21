<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

if($_SESSION["ad_level"] >= 4){
  if($_GET["cmd"] == "clean" && $_SESSION["ad_level"] >= 5){
    mysql_query("TRUNCATE arp_table");
  }

  echo "<h3>Historische ARP-Tabelle</h3>";
  if($_SESSION["ad_level"] >= 5) echo "<a onClick='return confirm(\"History wirklich leeren?\");' href='index.php?page=arp_table&cmd=clean'>alle l&ouml;schen</a>";

  echo "<h4>Eintr&auml;ge mit unterschiedlichen MAC-Adressen zu einer IP</h4>";
  echo "<table class='hover_row'>";
  echo "  <thead>";
  echo "  <tr>";
  echo "    <th width='100'>IP</th>";
  echo "    <th width='100'>MAC</th>";
  echo "    <th width='70'>Interface</th>";
  echo "    <th width='130'>Zuletzt gesehen</th>";
  echo "  </tr>";
  echo "  </thead>";
  echo "  <tbody>";
  $last = "";
  $query = mysql_query("SELECT * FROM arp_table WHERE ip IN (SELECT ip AS count FROM arp_table GROUP BY ip HAVING COUNT(*) > 1) ORDER BY INET_ATON(ip)");
  while($row = mysql_fetch_assoc($query)){
    if($last != $row["ip"] && !empty($last)) echo "<tr><td colspan='4'>&nbsp;</td></tr>";
    $last = $row["ip"];

    echo "<tr>";
    echo "  <td valign='top'>".$row["ip"]."</td>";
    echo "  <td valign='top'>".$row["mac"]."</td>";
    echo "  <td valign='top' align='center'>".$row["interface"]."</td>";
    echo "  <td valign='top' align='center'>".strftime("%a, %R Uhr",$row["last_seen"])."</td>";
    echo "</tr>";
  }
  echo "  </tbody>";
  echo "</table>";


  echo "<h4>Eintr&auml;ge mit unterschiedlichen IP-Adressen zu einer MAC</h4>";
  echo "<table class='hover_row'>";
  echo "  <thead>";
  echo "  <tr>";
  echo "    <th width='100'>IP</th>";
  echo "    <th width='100'>MAC</th>";
  echo "    <th width='70'>Interface</th>";
  echo "    <th width='130'>Zuletzt gesehen</th>";
  echo "  </tr>";
  echo "  </thead>";
  echo "  <tbody>";
  $last = "";
  $query = mysql_query("SELECT * FROM arp_table WHERE mac IN (SELECT mac AS count FROM arp_table GROUP BY mac HAVING COUNT(*) > 1) ORDER BY mac");
  while($row = mysql_fetch_assoc($query)){
    if($last != $row["mac"] && !empty($last)) echo "<tr><td colspan='4'>&nbsp;</td></tr>";
    $last = $row["mac"];

    echo "<tr>";
    echo "  <td valign='top'>".$row["ip"]."</td>";
    echo "  <td valign='top'>".$row["mac"]."</td>";
    echo "  <td valign='top' align='center'>".$row["interface"]."</td>";
    echo "  <td valign='top' align='center'>".strftime("%a, %R Uhr",$row["last_seen"])."</td>";
    echo "</tr>";
  }
  echo "  </tbody>";
  echo "</table>";


  echo "<h4>Komplette Tabelle</h4>";
  echo "<table class='hover_row sortierbar'>";
  echo "  <thead>";
  echo "  <tr>";
  echo "    <th class='sortierbar' width='100'>IP</th>";
  echo "    <th class='sortierbar' width='100'>MAC</th>";
  echo "    <th class='sortierbar' width='70'>Interface</th>";
  echo "    <th class='sortierbar' width='130'>Zuletzt gesehen</th>";
  echo "  </tr>";
  echo "  </thead>";
  echo "  <tbody>";
  $query = mysql_query("SELECT * FROM arp_table");
  while($row = mysql_fetch_assoc($query)){
    echo "<tr>";
    echo "  <td valign='top'>".$row["ip"]."</td>";
    echo "  <td valign='top'>".$row["mac"]."</td>";
    echo "  <td valign='top' align='center'>".$row["interface"]."</td>";
    echo "  <td valign='top' align='center'>".strftime("%a, %R Uhr",$row["last_seen"])."</td>";
    echo "</tr>";
  }
  echo "  </tbody>";
  echo "</table>";
}
?>
