<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

if($_SESSION["ad_level"] >= 5){

// Wird fuer das Formular verwendet um zwischen add und edit zu unterscheiden
$submit_name = "add";
$submit_value = "Hinzuf&uuml;gen";
$display = "none";

if($_GET["cmd"] == "edit" && is_numeric($_GET["id"]) && !empty($_GET["id"])){
  // Es wurde auf edit geklickt - hier werden die Daten fuer das Formular eingelesen
  $submit_name = "edit";
  $submit_value = "&Auml;ndern";
  $display = "block";
  $value = mysqli_fetch_assoc(mysqli_query($db,"SELECT id, login, name, ad_level FROM user WHERE id = '".mysqli_real_escape_string($db,$_GET["id"])."' LIMIT 1"));
}elseif($_GET["cmd"] == "del" && is_numeric($_GET["id"]) && !empty($_GET["id"])){
  // User loeschen
  mysqli_query($db,"DELETE FROM user WHERE id = '".mysqli_real_escape_string($db,$_GET["id"])."' LIMIT 1");
}elseif($_GET["cmd"] == "pw" && is_numeric($_GET["id"]) && !empty($_GET["id"])){
  // PW zuruecksetzen auf default-pw
  mysqli_query($db,"UPDATE user SET pw = '".sha1($default_pw)."' WHERE id = '".mysqli_real_escape_string($db,$_GET["id"])."' LIMIT 1");
  echo "<div class='meldung_ok'>Passwort neu gesetzt: $default_pw</div><br>";
}

// Formular wurde abgeschickt
if($_POST["add"] || $_POST["edit"]){
  if(empty($_POST["login"])){ // Login-Name darf nicht leer sein
    echo "<div class='meldung_error'>Der login-Name muss angegeben werden!</div><br>";
    $display = "block";
    $value = $_POST;
    if($_POST["edit"]){
      $submit_name = "edit";
      $submit_value = "&Auml;ndern";
      $display = "block";
    }
  }else{
    $id = mysqli_real_escape_string($db,$_POST["id"]);
    $login = mysqli_real_escape_string($db,$_POST["login"]);
    $name = mysqli_real_escape_string($db,$_POST["name"]);
    $rechte = mysqli_real_escape_string($db,$_POST["ad_level"]);
    if($_POST["add"]){
      // User mit default-pw anlegen
      mysqli_query($db,"INSERT INTO user SET login = '".$login."', name = '".$name."', ad_level = '".$rechte."', pw = '".sha1($default_pw)."'");
      echo "<div class='meldung_ok'>User angelegt</div><br><div class='meldung_notify'>Passwort: <b>$default_pw</b></div><br>";
    }elseif($_POST["edit"]){
      // User editieren
      mysqli_query($db,"UPDATE user SET login = '".$login."', name = '".$name."', ad_level = '".$rechte."' WHERE id = '".$id."' LIMIT 1");
      echo "<div class='meldung_ok'>User ge&auml;ndert.</div><br>";
    }
  }
}

// Formular
echo "<a href='#' onClick='document.getElementById(\"formular\").style.display = \"block\";'>User hinzuf&uuml;gen</a><br>";

echo "<form action='index.php?page=user' method='POST' id='formular' style='display: $display;'>
<input type='hidden' name='id' value='".$value["id"]."'>
<table>
  <tr>
    <th colspan='2'>&nbsp;</th>
  </tr>
  <tr>
    <td width='50'>Login:</td>
    <td><input type='text' name='login' value='".$value["login"]."'></td>
  </tr>
  <tr>
    <td>Name:</td>
    <td><input type='text' name='name' value='".$value["name"]."'></td>
  </tr>
  <tr>
    <td>Rechte:</td>
    <td><select name='ad_level'>";
foreach($ad_level as $k => $v){
  echo "<option value='$k' ";
  if($value["ad_level"] == $k) echo "selected='selected'";
  echo ">$v</option>";
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
    <th width='100'>Login</th>
    <th width='200'>Name</th>
    <th width='100'>Rechte</th>
    <th width='150'>&nbsp;</th>
  </tr>";

$query = mysqli_query($db,"SELECT id, login, name, ad_level FROM user ORDER BY login");
while($row = mysqli_fetch_assoc($query)){
  echo "<tr>
    <td>".$row["login"]."</td>
    <td>".$row["name"]."</td>
    <td>".$ad_level[$row["ad_level"]]."</td>
    <td align='center'><a href='index.php?page=user&cmd=edit&id=".$row["id"]."'>edit</a> | <a href='index.php?page=user&cmd=pw&id=".$row["id"]."'>reset PW</a> | <a href='index.php?page=user&cmd=del&id=".$row["id"]."' onClick='return confirm(\"User wirklich l&ouml;schen?\");'>del</a></td>
  </tr>";
}

echo "</table>";

echo "<br><br>";
echo "<h3>Rechte</h3>";
echo " - <u>User:</u> User k&ouml;nnen Internet freischalten und Regeln entfernen.<br>";
echo " - <u>Admin:</u> Admins k&ouml;nnen zus&auml;tzlich die globalen Freischaltungen steuern.<br>";
echo " - <u>Superadmin:</u> Superadmins k&ouml;nnen zus&auml;tzlich die User verwalten.<br>";
}
?>
