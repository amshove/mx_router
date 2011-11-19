<?php
$path = substr(dirname($_SERVER["SCRIPT_FILENAME"]),0,-8);
require($path."/config.inc.php");
require($path."/functions.inc.php");

$query = mysql_query("SELECT id, ip FROM history WHERE active = 1 AND end_date > 0 AND end_date < '".time()."'");
while($row = mysql_fetch_assoc($query)){
  if(iptables_del($row["ip"])){
    mysql_query("UPDATE history SET active = 0, del_user = 'Cronjob', del_date = '".time()."' WHERE id = '".$row["id"]."'");
  }
}
?>
