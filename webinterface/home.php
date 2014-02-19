<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

if($_SESSION["ad_level"] >= 1){
  echo "<table>";
  echo "  <tr>";
  echo "    <th width='70'>Status:</th>";
  foreach($leitungen as $leitung){
    if(ping($leitung["ip"],$leitung["eth"]) == 0) $class = "meldung_ok";
    else $class = "meldung_error";
    if($leitung["fw_mark"] == $default_leitung) $leitung["name"] .= " (default)";
    echo "  <td class='$class' width='150' align='center'>".$leitung["name"]."</td>";
  }
  echo "  </tr>";
  echo "</table>";
  if(exec("cat /proc/sys/net/ipv4/ip_forward") == 0) echo "<br><h2>Der Router ist gestoppt - bitte erst mit \"start mx_router\" starten!</h2>";
  echo "<br><br>";



  if($_POST["add"]){
    if(empty($_POST["ip"])){
      echo "<div class='meldung_error'>IP muss angegeben werden!</div><br>";
    }elseif(!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/",$_POST["ip"]) && !preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\/[0-9]{1,2}$/",$_POST["ip"])){
      echo "<div class='meldung_error'>Keine g&uuml;ltige IP-Adresse angegeben.</div><br>";
    }elseif(mysql_num_rows(mysql_query("SELECT id FROM history WHERE ip = '".$_POST["ip"]."' AND active = 1")) > 0){
      echo "<div class='meldung_error'>Es gibt bereits eine Regel f&uuml;r die IP.</div><br>";
    }else{
      $now = time();
      if(empty($_POST["end_date"])) $end_date = 0;
      else $end_date = $now + ($_POST["end_date"]*60);

      mysql_query("INSERT INTO history SET ip = '".mysql_real_escape_string($_POST["ip"])."', leitung = '".mysql_real_escape_string($_POST["leitung"])."', add_user = '".mysql_real_escape_string($_SESSION["user_name"])."', add_date = '".$now."', end_date = '".$end_date."', active = -1, reason = '".mysql_real_escape_string($_POST["reason"])."'");
      $id = mysql_insert_id();

      if(rule_add($id)){
        echo "<div class='meldung_ok'>Regel erfolgreich erstellt</div><br>";
      }else{
        echo "<div class='meldung_error'>Regel konnte nicht angelegt werden!</div><br>";
      }
    }
  }

  if($_GET["cmd"] == "del"){
    $ids = array();
    if(!empty($_GET["id"]) && is_numeric($_GET["id"])) $ids[] = $_GET["id"];
    elseif(is_array($_POST["id"])) $ids = $_POST["id"];

    if(count($ids) > 0){
      foreach($ids as $id){
        if(rule_del($id,$_SESSION["user_name"])){
          echo "<div class='meldung_ok'>Regel erfolgreich gel&ouml;scht.</div><br>";
        }else{
          echo "<div class='meldung_error'>Regel konnte nicht gel&ouml;scht werden!</div><br>";
        }
      }
    }
  }elseif($_GET["cmd"] == "ports" && $_GET["id"] > 0 && $_SESSION["ad_level"] >= 4){
    $status = false;
    if($_GET["do"] == "on"){
      $status = ports_add($_GET["id"]);
    }elseif($_GET["do"] == "off"){
      $status = ports_del($_GET["id"]);
    }
    if($status) echo "<div class='meldung_ok'>Regel erfolgreich ge&auml;ndert.</div><br>";
    else echo "<div class='meldung_error'>Regel nicht erfolgreich ge&auml;ndert.</div><br>";
  }elseif($_POST["port_id"] > 0 && $_SESSION["ad_level"] >= 4){
    if(ports_leitung_chg($_POST["port_id"],$_POST["leitung"])) echo "<div class='meldung_ok'>Regel erfolgreich ge&auml;ndert.</div><br>";
    else echo "<div class='meldung_error'>Regel nicht erfolgreich ge&auml;ndert.</div><br>";
  }

  if($_GET["cmd"] == "chg" && $_GET["id"]){
    if(leitung_chg($_GET["id"],$_GET["leitung"])) echo "<div class='meldung_ok'>Regel erfolgreich ge&auml;ndert.</div><br>";
    else echo "<div class='meldung_error'>Regel nicht erfolgreich ge&auml;ndert.</div><br>";
  }


  echo "<table style='border: 0px;'>";
  echo "  <tr><td valing='top'>";

  if($_SESSION["users"]){
    echo " <script>
    $(function() {
      var availableTags = [";
    foreach($_SESSION["users"] as $user) echo "{label: '".str_replace("'","\\'",$user["nick"]." (".$user["vorname"]." ".$user["nachname"].") ".$user["sitz_nr"])."', ip: '".$user["ip"]."'},";
    echo "  ];
      $( '#search' ).autocomplete({
        source: availableTags,
        select: function( event, ui ) {
          $('#ip').val(ui.item.ip);
        }
      })._renderItem = function( ul, item ) {
        return item.label;
      };
    });
    </script>";
  }
  echo "<h3>Neue Freigabe erteilen</h3>";
  echo "<form action='index.php' method='POST'>";
  echo "<table>";
  echo "  <tr>";
  echo "    <th colspan='2'>&nbsp;</th>";
  echo "  </tr>";
  echo "  <tr>";
  echo "    <td>IP:</td>";
  echo "    <td><input type='text' name='ip' id='ip'></td>";
  echo "  </tr>";
  if($_SESSION["users"]){
    echo "<tr>";
    echo "  <td colspan='2' align='center'><b>oder</b></td>";
    echo "</tr>";
    echo "<tr>";
    echo "  <td>User:</td>";
    echo "  <td><input type='text' id='search'></td>";
    echo "</tr>";
  }
  echo "  <tr>";
  echo "    <td colspan='2' align='center'>&nbsp;</td>";
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
  echo "    <td>Leitung:</td>";
  echo "    <td><select name='leitung'><option value='0'>default</option>";
  foreach($leitungen as $leitung) echo "<option value='".$leitung["fw_mark"]."'>".$leitung["name"]."</option>";
  echo "    </select></td>";
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

  echo "  </td><td width='50'>&nbsp;</td><td valign='top'>";

  $iptables_leitungen = iptables_list(true);
  $iptables_leitungen = $iptables_leitungen[0];

  $iptables_lines = iptables_list();
  $iptables_ips = $iptables_lines[0];
  $iptables_traffic = $iptables_lines[1];

  echo "<h3>Globale Freigaben</h3>";
  echo "<table>";
  echo "  <tr>";
  echo "    <th width='100'>&nbsp;</th>";
  echo "    <th width='100'>Traffic</th>";
  echo "    <th width='100'>Leitung</th>";
  echo "  </tr>";
  $query = mysql_query("SELECT * FROM ports ORDER BY name");
  while($row = mysql_fetch_assoc($query)){
    $status = ports_open($row["id"]);
    $status_leitung = ports_open($row["id"],true);
    if($status[0] == true && $status[1] == true){
      $class = "meldung_ok";
      $do = "off";
    }elseif($status[0] == false && $status[1] == false){
      $class = "meldung_error";
      $do = "on";
    }else{ 
      $class = "meldung_notify";
      $do = "off";
    }

    echo "<form action='index.php' method='POST'>";
    echo "<input type='hidden' name='port_id' value='".$row["id"]."'>";
    echo "<tr>";
    echo "  <th class='$class'>";
    if($_SESSION["ad_level"] >= 4) echo "<a href='index.php?cmd=ports&do=$do&id=".$row["id"]."' onClick='return confirm(\"Die globale Regel f&uuml;r ".$row["name"]." wirklich &auml;ndern?\");'>";
    echo $row["name"];
    if($_SESSION["ad_level"] >= 4) echo "</a>";
    echo "    <td align='center'>".$iptables_traffic[escapeshellarg($row["name"])]."</td>";
    if($_SESSION["ad_level"] >= 4) $disabled = "";
    else $disabled = "disabled='disabled'";
    echo "    <td align='center'><select $disabled name='leitung' onChange='if(confirm(\"Leitungszuordnung f&uuml;r ".$row["name"]." wirklich &auml;ndern?\\nACHTUNG: Die Regel gilt auch, wenn die globale Freischaltung deaktiviert ist!\")) this.form.submit();'><option value='0'>default</option>";
    foreach($leitungen as $leitung){
      if($status_leitung[2] == $leitung["fw_mark"]) $select = "selected='selected'";
      else $select = "";
      echo "<option $select value='".$leitung["fw_mark"]."'>".$leitung["name"]."</option>";
    }
    echo "    </select></td>";
    echo "  </th>";
    echo "</tr>";
    echo "</form>";
  }
  echo "</table>";

  echo "  </td></tr>";
  echo "</table>";


  echo "<script>";
  echo "  function select_all(){";
  echo "    var status = document.getElementById('chk_all').checked;";
  echo "    var boxes = document.getElementsByTagName('input');";
  echo "    for (var i=0; i<boxes.length; i++){";
  echo "      if(boxes[i].name == 'id[]'){";
  echo "        boxes[i].checked = status;";
  echo "      }";
  echo "    }";
  echo "  }";
  echo "</script>";
  echo "<form action='index.php?cmd=del' method='POST' style='display: inline;'>";
  echo "<h3>Aktuelle Freigaben</h3>";
  echo "<table class='hover_row sortierbar'>";
  echo "  <thead>";
  echo "  <tr>";
  echo "    <th width='10'><input type='checkbox' id='chk_all' onClick='select_all();' style='margin: 0px;'></th>";
  echo "    <th class='sortierbar vorsortiert+' width='100'>IP</th>";
  echo "    <th class='sortierbar' width='130'>DNS</th>";
  echo "    <th class='sortierbar' width='80'>Traffic</th>";
  echo "    <th class='sortierbar' width='200'>Grund</th>";
  echo "    <th class='sortierbar' width='130'>Angelegt von</th>";
  echo "    <th class='sortierbar' width='100'>L&auml;uft ab ..</th>";
  echo "    <th class='sortierbar' width='100'>Leitung</th>";
  echo "    <th class='sortierbar' width='30'>&nbsp;</th>";
  echo "  </tr>";
  echo "  </thead>";
  echo "  <tbody>";

  $error = false;
  $query = mysql_query("SELECT * FROM history WHERE active = 1 ORDER BY INET_ATON(ip)");
  while($row = mysql_fetch_assoc($query)){ 
    $dns = @gethostbyaddr($row["ip"]);
    echo "<tr";
    if(!in_array($row["ip"],$iptables_ips)){ echo " style='background-color: #CC9999;'"; $error = true; }
    echo ">";
    echo "  <td valign='top'><input type='checkbox' name='id[]' value='".$row["id"]."' style='margin: 0px;'></td>";
    echo "  <td valign='top'>".$row["ip"]."</td>";
    echo "  <td valign='top' align='center'>".($dns == $row["ip"] ? "" : $dns)."</td>";
    echo "  <td valign='top' align='center'>".$iptables_traffic[$row["ip"]]."</td>";
    echo "  <td valign='top'>".nl2br($row["reason"])."</td>";
    echo "  <td valign='top'>".$row["add_user"]."</td>";
    echo "  <td valign='top' align='center'>";
    if(empty($row["end_date"])) echo "nie";
    else echo strftime("%a, %R Uhr",$row["end_date"]);
    echo "  </td>";
    echo "  <td valign='top' align='center'><select name='leitung' onChange='if(confirm(\"Leitungszuordnung f&uuml;r ".$row["ip"]." wirklich &auml;ndern?\")) document.location.href = \"index.php?cmd=chg&id=".$row["id"]."&leitung=\"+this.value;'><option value='0'>default</option>";
    foreach($leitungen as $leitung){
      if($iptables_leitungen[$row["ip"]] == $leitung["fw_mark"]) $select = "selected='selected'";
      else $select = "";
      echo "<option $select value='".$leitung["fw_mark"]."'>".$leitung["name"]."</option>";
    }
    echo "  </select></td>";
    echo "  <td valign='top'><a onClick='return confirm(\"Freigabe f&uuml;r ".$row["ip"]." wirklich l&ouml;schen?\");' href='index.php?cmd=del&id=".$row["id"]."'>del</a></td>";
    echo "</tr>";
  }
  echo "  </tbody>";
  echo "</table>";
  if($error) echo "Rot hinterlegte Zeilen sind Regeln, die ohne Wissen des Webinterfaces gel&ouml;scht wurden. Diese m&uuml;ssen von Hand entfernt und neu angelegt werden.<br>";
  echo "<input type='submit' name='del_more' value='Ausgew&auml;hlte l&ouml;schen' onClick='return confirm(\"Ausgew&auml;hlte Freigaben wirklich l&ouml;schen?\");'>";
  echo "</form>";
}
?>
