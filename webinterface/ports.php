<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

if($_SESSION["ad_level"] >= 4){

// Wird fuer das Formular verwendet um zwischen add und edit zu unterscheiden
$submit_name = "add";
$submit_value = "Hinzuf&uuml;gen";
$display = "none";

if($_GET["cmd"] == "edit" && is_numeric($_GET["id"]) && !empty($_GET["id"])){
  // Es wurde auf edit geklickt - hier werden die Daten fuer das Formular eingelesen
  $submit_name = "edit";
  $submit_value = "&Auml;ndern";
  $display = "block";
  $value = mysql_fetch_assoc(mysql_query("SELECT * FROM ports WHERE id = '".mysql_real_escape_string($_GET["id"])."' LIMIT 1"));
}elseif($_GET["cmd"] == "del" && is_numeric($_GET["id"]) && !empty($_GET["id"])){
  // loeschen
  if(mysql_result(mysql_query("SELECT active FROM ports WHERE id = '".mysql_real_escape_string($_GET["id"])."' LIMIT 1"),0,"active") == "1") ports_del($_GET["id"]);
  mysql_query("DELETE FROM ports WHERE id = '".mysql_real_escape_string($_GET["id"])."' LIMIT 1");
}

// Formular wurde abgeschickt
if($_POST["add"] || $_POST["edit"]){
  if(empty($_POST["name"])){
    echo "<div class='meldung_error'>Der Name muss angegeben werden!</div><br>";
    $display = "block";
    $value = $_POST;
    if($_POST["edit"]){
      $submit_name = "edit";
      $submit_value = "&Auml;ndern";
      $display = "block";
    }
  }else{
    $id = mysql_real_escape_string($_POST["id"]);
    $name = mysql_real_escape_string($_POST["name"]);
    $tcp = mysql_real_escape_string($_POST["tcp"]);
    $udp = mysql_real_escape_string($_POST["udp"]);

    $tcp = str_replace("-",":",$tcp);
    $udp = str_replace("-",":",$udp);

    $tcp = str_replace(array(" ","\s","\t"),"",$tcp);
    $udp = str_replace(array(" ","\s","\t"),"",$udp);

    if(!preg_match("/^[0-9,:]*$/",$tcp) || !preg_match("/^[0-9,:]*$/",$udp)){
      echo "<div class='meldung_error'>Ung&uuml;ltige Zeichen in der Port-Angabe! Erlaubt: 0-9,:</div><br>";
      $display = "block";
      $value = $_POST;
      if($_POST["edit"]){
        $submit_name = "edit";
        $submit_value = "&Auml;ndern";
        $display = "block";
      }
    }else{
      if($_POST["add"]){
        mysql_query("INSERT INTO ports SET name = '".$name."', tcp = '".$tcp."', udp = '".$udp."'");
        echo "<div class='meldung_ok'>Globale Freischaltung angelegt - aktivieren kann man diese auf der Startseite</div><br>";
      }elseif($_POST["edit"]){
        if(mysql_result(mysql_query("SELECT active FROM ports WHERE id = '".$id."' LIMIT 1"),0,"active") == "1") $active = true;
        else $active = false;

        if($active) ports_del($id);
        mysql_query("UPDATE ports SET name = '".$name."', tcp = '".$tcp."', udp = '".$udp."' WHERE id = '".$id."' LIMIT 1");
        if($active) ports_add($id);
        echo "<div class='meldung_ok'>Globale Freischaltung ge&auml;ndert.</div><br>";
      }
    }
  }
}

// Formular
echo "<a href='#' onClick='document.getElementById(\"formular\").style.display = \"block\";'>Globale Freischaltung hinzuf&uuml;gen</a><br>";

echo "<form action='index.php?page=ports' method='POST' id='formular' style='display: $display;'>
<input type='hidden' name='id' value='".$value["id"]."'>
<table>
  <tr>
    <th colspan='2'>&nbsp;</th>
  </tr>
  <tr>
    <td width='50'>Name:</td>
    <td><input type='text' name='name' value='".$value["name"]."'></td>
  </tr>
  <tr>
    <td>TCP:</td>
    <td><input type='text' name='tcp' value='".$value["tcp"]."'></td>
  </tr>
  <tr>
    <td>UDP:</td>
    <td><input type='text' name='udp' value='".$value["udp"]."'></td>
  </tr>
  <tr>
    <td colspan='2' align='center'><input type='submit' name='".$submit_name."' value='".$submit_value."'></td>
  </tr>
</table>
</form>";

echo "<br><br>";

// Tabelle
echo "<table class='hover_row'>
  <tr>
    <th width='200'>Name</th>
    <th width='250'>TCP-Ports</th>
    <th width='250'>UDP-Ports</th>
    <th width='70'>&nbsp;</th>
  </tr>";

$query = mysql_query("SELECT * FROM ports ORDER BY name");
while($row = mysql_fetch_assoc($query)){
  echo "<tr>
    <td>".$row["name"]."</td>
    <td>".$row["tcp"]."</td>
    <td>".$row["udp"]."</td>
    <td align='center'><a href='index.php?page=ports&cmd=edit&id=".$row["id"]."'>edit</a> | <a href='index.php?page=ports&cmd=del&id=".$row["id"]."' onClick='return confirm(\"Ports wirklich l&ouml;schen?\");'>del</a></td>
  </tr>";
}

echo "</table>";
}
?>
