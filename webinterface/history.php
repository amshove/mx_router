<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

if($_SESSION["ad_level"] >= 1){
  if($_GET["cmd"] == "clean" && $_SESSION["ad_level"] >= 5){
    mysqli_query($db,"DELETE FROM history WHERE active < 1");
    my_syslog("History truncated");
  }

  echo "<h3>Alte Freigaben</h3>";
  if($_SESSION["ad_level"] >= 5) echo "<a onClick='return confirm(\"History wirklich leeren?\");' href='index.php?page=history&cmd=clean'>alle l&ouml;schen</a>";
  echo "<table class='hover_row sortierbar'>";
  echo "  <thead>";
  echo "  <tr>";
  echo "    <th class='sortierbar' width='100'>IP</th>";
  echo "    <th class='sortierbar' width='150'>Grund</th>";
  echo "    <th class='sortierbar' width='130'>Angelegt von</th>";
  echo "    <th class='sortierbar' width='130'>Angelegt um</th>";
  echo "    <th class='sortierbar' width='130'>Gel&ouml;scht von</th>";
  echo "    <th class='sortierbar vorsortiert-' width='130'>Gel&ouml;scht um</th>";
  echo "    <th class='sortierbar' width='100'>Zeitraum</th>";
  echo "    <th class='sortierbar' width='80'>Traffic</th>";
  echo "  </tr>";
  echo "  </thead>";
  echo "  <tbody>";
  $query = mysqli_query($db,"SELECT * FROM history WHERE active < 1 ORDER BY del_date DESC");
  while($row = mysqli_fetch_assoc($query)){
    echo "<tr>";
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
      $min = round($time - ($h*60));
      if(!empty($h)) echo "$h std";
      if(!empty($min)) echo " $min min";
    }
    echo "  </td>";
    echo "  <td valign='top' align='center'>".$row["traffic"]."</td>";
    echo "</tr>";
  }
  echo "  </tbody>";
  echo "</table>";
}
?>
