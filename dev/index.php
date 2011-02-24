<?php
  if($_POST["add"] && !empty($_POST["ip"])){
    $ip = escapeshellcmd($_POST["ip"]);
    if(!preg_match("/^10\.10\.[0-9]{1,2}\.[0-9]{1,3}$/",$ip)){
      echo "Keine g&uuml;ltige IP.<br><br>";
    }else{
      exec("sudo /sbin/iptables -I FORWARD -s ".$ip." -j ACCEPT",$retarr,$retrc);
      if($retrc == 0) echo "Erfolgreich eingetragen.<br><br>";
      else echo "Fehler.<br><br>";
    }
  }elseif($_GET["cmd"] == "del" && !empty($_GET["ip"])){
    $ip = escapeshellcmd($_GET["ip"]);
    exec("sudo /sbin/iptables -L FORWARD -n --line-numbers | grep $ip",$retarr);
    $fields = preg_split("/\s/",$retarr[0], -1, PREG_SPLIT_NO_EMPTY);
    $num = $fields[0];
    if(is_numeric($num) AND $num > 0){
      unset($retarr,$retrc);
      exec("sudo /sbin/iptables -D FORWARD ".$num,$retarr,$retrc);
      if($retrc == 0) echo "Regel gel&ouml;scht.<br><br>";
      else echo "Fehler2.<br><br>";
    }else{
      echo "Fehler1.<br><br>";
    }
  }

  echo "<form action='index.php' method='POST'>";
  echo "<table border=1>";
  echo "  <tr>";
  echo "    <td>IP:</td>";
  echo "    <td><input type='text' name='ip'></td>";
  echo "  </tr>";
  echo "  <tr>";
  echo "    <td colspan='2' align='center'><input type='submit' name='add' value='add'></td>";
  echo "  </tr>";
  echo "</table>";
  echo "</form><br><br>";

  echo "<table border=1>";
  echo "  <tr>";
  echo "    <th width='100'>IP</th>";
  echo "    <th>&nbsp;</th>";
  echo "  </tr>";
  unset($retarr);
  exec("sudo /sbin/iptables -L FORWARD -n --line-numbers | grep ACCEPT",$retarr);
  foreach($retarr as $line){
    $fields = preg_split("/\s/",$line, -1, PREG_SPLIT_NO_EMPTY);
    if($fields[4] == "0.0.0.0/0") continue;
    echo "<tr>";
    echo "  <td>".$fields[4]."</td>";
    echo "  <td><a onClick='return confirm(\"".$fields[4]." wirklich l&ouml;schen?\");' href='index.php?cmd=del&ip=".$fields[4]."'>del</a></td>";
    echo "</tr>";
  }
  echo "  </table>";
?>
