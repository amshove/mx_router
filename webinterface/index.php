<?php
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################
$log_ident = substr(md5(mt_rand()),0,5);
openlog("mx_router[web_$log_ident]",LOG_ODELAY,LOG_USER); // Logging zu Syslog oeffnen
require("config.inc.php");
require("functions.inc.php");

// Logout
if($_GET["logout"]){
  session_destroy();
  session_start();
  $_SESSION["ad_level"] = 0;
  $logged_in = false;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>Router Webinterface</title>
  <link rel="SHORTCUT ICON" href="favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="css/smoothness/jquery-ui-1.10.4.custom.css">
  <script src="js/jquery-1.10.2.js"></script>
  <script src="js/jquery-ui-1.10.4.custom.min.js"></script>
  <link rel="stylesheet" type="text/css" href="css/style.css" />
  <script type="text/javascript" src="js/TableSort.js"></script>
</head>
<body>

<?php
if($_POST["submit_login"]){
  // Login
  if(empty($_POST["submit_login"]) || empty($_POST["pw"])) echo "<div class='meldung_error'>Nicht alle Felder angegeben.</div><br>";
  else{
    $query = mysql_query("SELECT id, name, ad_level FROM user WHERE LOWER(login) = LOWER('".mysql_escape_string($_POST["login"])."') AND (pw = '".sha1($_POST["pw"])."' OR pw = '".md5($_POST["pw"])."') LIMIT 1");
    if(mysql_num_rows($query) == 1){
      $_SESSION["user_id"] = mysql_result($query,0,"id");
      $_SESSION["user_name"] = mysql_result($query,0,"name");
      $_SESSION["ad_level"] = mysql_result($query,0,"ad_level");
      $logged_in = true;
      if($_POST["pw"] == $default_pw) $set_pw = true; // Wenn das das default-pw war, dann aendern
    }else{
      $soap_client = soap_connect($_POST["login"],$_POST["pw"]);
      if($soap_client){
        try{
          $me = $soap_client->getMe();
          $rights = $soap_client->getRechte();
          if($rights["view"]){
            $logged_in = true;
            $_SESSION["user_name"] = $me["nick"];
            if($rights["admin"]) $_SESSION["ad_level"] = 4;
            else $_SESSION["ad_level"] = 3;

            // User-Liste holen fuer User-Suche
            $users = $soap_client->getUserIps();
            $_SESSION["users"] = $users;
          }
        }catch(Exception $e){ }
      }
    }
  }
}elseif($_POST["submit_pw"]){
  // default-PW aendern
  if(empty($_POST["pw1"]) || empty($_POST["pw2"])){
    echo "<div class='meldung_error'>Nicht alles ausgef&uuml;llt.</div><br>";
    $set_pw = true;
  }elseif($_POST["pw1"] != $_POST["pw2"]){
    echo "<div class='meldung_error'>PWs stimmen nicht &uuml;berein.</div><br>";
    $set_pw = true;
  }else{
    mysql_query("UPDATE user SET pw = '".sha1($_POST["pw1"])."' WHERE id = '".$_SESSION["user_id"]."' LIMIT 1");
  }
}

if(!$logged_in){
  // Login-Formular
  echo "<form action='index.php' method='POST'>
  <table>
    <tr>
      <th colspan='2'>Login</th>
    </tr>
    <tr>
      <td width='60'>Login:</td>
      <td><input type='text' name='login'></td>
    </tr>
    <tr>
      <td>Pw:</td>
      <td><input type='password' name='pw'></td>
    </tr>
    <tr>
      <td colspan='2' align='center'><input type='submit' name='submit_login' value='login'></td>
    </tr>
  </table>
  </form>";
}elseif($set_pw){
  // Default-PW aendern
  echo "<form action='index.php' method='POST'>
  <table>
    <tr>
      <td width='100'>neues PW:</td>
      <td><input type='password' name='pw1'></td>
    </tr>
    <tr>
      <td>Nochmal:</td>
      <td><input type='password' name='pw2'></td>
    </tr>
    <tr>
      <td colspan='2' align='center'><input type='submit' name='submit_pw' value='PW setzen'></td>
    </tr>
  </table>
  </form>";
}else{
  // Eigentliche Seite
  echo "<div class='navi'><a class='navi' href='index.php'>Home</a>";
  echo " | <a class='navi' href='index.php?page=history'>History</a>";
  echo " | <a class='navi' href='index.php?page=selfservice'>Selfservice</a>";
  echo " | <a class='navi' href='index.php?page=leitungen'>Leitungen</a>";
  if($_SESSION["ad_level"] >= 4) echo " | <a class='navi' href='index.php?page=ports'>Ports</a>";
  if($_SESSION["ad_level"] >= 4) echo " | <a class='navi' href='index.php?page=turniere'>Turniere</a>";
  if($_SESSION["ad_level"] >= 4) echo " | <a class='navi' href='index.php?page=arp_table'>ARP Tabelle</a>";
  if($_SESSION["ad_level"] >= 5) echo " | <a class='navi' href='index.php?page=user'>User administrieren</a>";
  echo " | <a class='navi' href='index.php?logout=true'>Logout</a>";
  echo " | Aktueller User: ".$_SESSION["user_name"];
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <font style='font-size: 10px'>Router Webinterface by <a style='color:#FFFFFF; font-size: 10px;' href='http://www.amshove.net/' target='_blank'>Torsten Amshove</a></font>";
  echo "</div>";
  switch($_GET["page"]){
    case "history": include("history.php"); break;
    case "selfservice": include("selfservice.php"); break;
    case "leitungen": include("leitungen.php"); break;
    case "ports": include("ports.php"); break;
    case "turniere": include("turniere.php"); break;
    case "arp_table": include("arp_table.php"); break;
    case "user": include("user.php"); break;
    default: include("home.php"); break;
  }
}
?>

</body>
</html>
