<?php
############################################################
# Router Webinterface                                      #
# Copyright (C) 2010 Torsten Amshove <torsten@amshove.net> #
############################################################

if($_SESSION["ad_level"] >= 1){
  if($_GET["cmd"] == "clean" && $_SESSION["ad_level"] >= 5){
    mysql_query("DELETE FROM history WHERE active = 0");
  }

  echo "<h3>Alte Freigaben</h3>";
  if($_SESSION["ad_level"] >= 5) echo "<a onClick='return confirm(\"History wirklich leeren?\");' href='index.php?page=history&cmd=clean'>alle l&ouml;schen</a>";
  echo "<table class='hover_row'>";
  echo "  <tr>";
  echo "    <th width='100'>IP</th>";
  echo "    <th width='150'>Grund</th>";
  echo "    <th width='130'>Angelegt von</th>";
  echo "    <th width='130'>Angelegt um</th>";
  echo "    <th width='130'>Gel&ouml;scht von</th>";
  echo "    <th width='130'>Gel&ouml;scht um</th>";
  echo "    <th width='100'>Zeitraum</th>";
  echo "    <th width='80'>Traffic</th>";
  echo "  </tr>";
  $i=0;
  $query = mysql_query("SELECT * FROM history WHERE active = 0 ORDER BY del_date DESC");
  while($row = mysql_fetch_assoc($query)){
    echo "<tr";
    if(($i % 2) > 0) echo " class='odd_row'";
    echo ">";
    echo "  <td valign='top'>".$row["ip"]."</td>";
    echo "  <td valign='top'>".nl2br($row["reason"])."</td>";
    echo "  <td valign='top'>".$row["add_user"]."</td>";
    echo "  <td valign='top' align='center'>".strftime("%a, %R Uhr",$row["add_date"])."</td>";
    echo "  <td valign='top'>".$row["del_user"]."</td>";
    echo "  <td valign='top' align='center'>".strftime("%a, %R Uhr",$row["del_date"])."</td>";
    echo "  <td valign='top' align='center'>";
    if(empty($row["end_date"])) echo "dauerhaft";
    else{
      $time = ($row["end_date"]-$row["add_date"])/60;
      $h = floor($time/60);
      $min = $time - ($h*60);
      if(!empty($h)) echo "$h std";
      if(!empty($min)) echo " $min min";
    }
    echo "  </td>";
    echo "  <td valign='top' align='center'>".$row["traffic"]."</td>";
    echo "</tr>";
    $i++;
  }
  echo "</table>";
}
?>
