<?php
############################################################
# Router Webinterface                                      #
# Copyright (C) 2010 Torsten Amshove <torsten@amshove.net> #
############################################################

if($_SESSION["ad_level"] >= 1){
  echo "<h3>Status der Leitungen</h3>";
  echo "<table>";
  echo "  <tr>";
  echo "    <th width='100'>Leitung:</th>";
  foreach($leitungen as $leitung){
    if(ping($leitung["ip"],$leitung["eth"]) == 0) $class = "meldung_ok";
    else $class = "meldung_error";
    echo "  <th class='$class' width='130'>".$leitung["name"]."</th>";
  }
  echo "  </tr>";
  echo "  <tr>";
  echo "    <th valign='top'>Quell-IPs:</th>";
  $rules = rule_list();
  foreach($leitungen as $leitung){
    echo "  <td valign='top'>";
    foreach($rules[$leitung["table"]] as $k => $v){
      if(!empty($aliases[$k])) echo $aliases[$k]." ($k)";
      else echo $k;
      echo "<br>";
    }
    echo "  </td>";
  }
  echo "  </tr>";
  echo "</table>";

  if($_SESSION["ad_level"] >= 4){
    echo "<br><br>";
    echo "<table class='hover_row'>";
    echo "  <tr>";
    echo "    <th width='250'>Reihenfolge der Regeln</th>";
    echo "  </tr>";
    unset($retarr,$retrc);
    exec($ip_cmd." rule show",$retarr,$retrc);
    foreach($retarr as $line){
      echo "<tr>";
      echo "  <td>".$line."</td>";
      echo "</tr>";
    }
    echo "</table>";
  }
}
?>
