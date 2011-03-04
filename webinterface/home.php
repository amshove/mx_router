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
    echo "  <td class='$class' width='70' title='".$leitung["subnets"]."' align='center'>".$leitung["name"]."</td>";
  }
  echo "  </tr>";
  echo "</table><br><br>";

  echo "<h3>Neue Freigabe erteilen</h3>";
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
  echo "    <td><select name='time'>
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
  echo "    <td colspan='2'><input type='submit' name='add' value='anlegen'></td>";
  echo "  </tr>";
  echo "</table>";
}
?>
