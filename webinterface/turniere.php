<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

if($_SESSION["ad_level"] >= 4){

$soap_client = soap_connect("mx_router",$soap_secret);
try{
  $turniere = $soap_client->getTurniere();
}catch(Exception $e){
  echo "SOAP ERROR: ".$e->getMessage()."<hr>";
}

// Wird fuer das Formular verwendet um zwischen add und edit zu unterscheiden
$submit_name = "add";
$submit_value = "Hinzuf&uuml;gen";
$display = "none";

if($_GET["cmd"] == "edit" && is_numeric($_GET["id"]) && !empty($_GET["id"])){
  // Es wurde auf edit geklickt - hier werden die Daten fuer das Formular eingelesen
  $submit_name = "edit";
  $submit_value = "&Auml;ndern";
  $display = "block";
  $value = mysqli_fetch_assoc(mysqli_query($db,"SELECT * FROM turniere WHERE turnier_id = '".mysqli_real_escape_string($db,$_GET["id"])."' LIMIT 1"));
  $value["leitungen"] = explode(",",$value["leitungen"]);
}elseif($_GET["cmd"] == "del" && is_numeric($_GET["id"]) && !empty($_GET["id"])){
  // loeschen
  mysqli_query($db,"DELETE FROM turniere WHERE turnier_id = '".mysqli_real_escape_string($db,$_GET["id"])."' LIMIT 1");
}

// Formular wurde abgeschickt
if($_POST["add"] || $_POST["edit"]){
  if(count($_POST["leitungen"]) < 1){
    echo "<div class='meldung_error'>Es muss mindestens eine Leitung ausgesucht werden!</div><br>";
    $display = "block";
    $value = $_POST;
    if($_POST["edit"]){
      $submit_name = "edit";
      $submit_value = "&Auml;ndern";
      $display = "block";
    }
  }else{
    $turnier_id_old = mysqli_real_escape_string($db,$_POST["turnier_id_old"]);
    $turnier_id = mysqli_real_escape_string($db,$_POST["turnier_id"]);
    $leitungen_new = array();
    foreach($_POST["leitungen"] as $leitung) $leitungen_new[] = mysqli_real_escape_string($db,$leitung);

    if($_POST["add"]){
      mysqli_query($db,"INSERT INTO turniere SET turnier_id = '".$turnier_id."', leitungen = '".implode(",",$leitungen_new)."'");
      echo "<div class='meldung_ok'>Turnier angelegt - bei diesem Turnier wurde der SelfService aktiviert.</div><br>";
    }elseif($_POST["edit"]){
      mysqli_query($db,"UPDATE turniere SET turnier_id = '".$turnier_id."', leitungen = '".implode(",",$leitungen_new)."' WHERE turnier_id = '".$turnier_id_old."' LIMIT 1");
      echo "<div class='meldung_ok'>Turnier ge&auml;ndert.</div><br>";
    }
  }
}

// Formular
echo "<a href='#' onClick='document.getElementById(\"formular\").style.display = \"block\";'>Turnier hinzuf&uuml;gen</a><br>";

echo "<form action='index.php?page=turniere' method='POST' id='formular' style='display: $display;'>
<input type='hidden' name='turnier_id_old' value='".$value["turnier_id"]."'>
<table>
  <tr>
    <th colspan='2'>&nbsp;</th>
  </tr>
  <tr>
    <td width='50'>Turnier:</td>
    <td><select name='turnier_id'>";
foreach($turniere as $tid => $tname){
  if($tid == $value["turnier_id"]) $selected = "selected = 'selected'";
  else $selected = "";
  echo "<option value='$tid' $selected>$tname</option>";
}
echo "</select></td>
  </tr>
  <tr>
    <td>Leitungen:</td>
    <td><select name='leitungen[]' size='5' multiple>";
foreach($leitungen as $leitung){
  if(in_array($leitung["fw_mark"],$value["leitungen"])) $selected = "selected = 'selected'";
  else $selected = "";

  echo "<option value='".$leitung["fw_mark"]."' $selected>".$leitung["name"]."</option>";
}
echo "</select></td>
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
    <th width='400'>Name</th>
    <th width='200'>Leitungen</th>
    <th width='70'>&nbsp;</th>
  </tr>";

$query = mysqli_query($db,"SELECT * FROM turniere");
while($row = mysqli_fetch_assoc($query)){
  echo "<tr>
    <td valign='top'>".$turniere[$row["turnier_id"]]."</td>
    <td>";
    foreach(explode(",",$row["leitungen"]) as $fw_mark) echo $leitungen_fw[$fw_mark]["name"]."<br>";
    echo "</td>
    <td valign='top' align='center'><a href='index.php?page=turniere&cmd=edit&id=".$row["turnier_id"]."'>edit</a> | <a href='index.php?page=turniere&cmd=del&id=".$row["turnier_id"]."' onClick='return confirm(\"Turnier wirklich l&ouml;schen?\");'>del</a></td>
  </tr>";
}

echo "</table>";
}
?>
