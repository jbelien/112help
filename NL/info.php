<?php
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Europe/Brussels');

session_start(); if (!isset($_SESSION['id'])) { header('Location: /index.php'); exit(); }

if (isset($_POST['action'], $_POST['infos']) && $_POST['action'] == 'send') {
  $mysqli = new MySQLi('localhost', 'riikc_112', '2c4;i+mu7CJ;', 'riikc_help');
  $mysqli->set_charset('utf8');

  $qsz  = "UPDATE `help` SET";
  $qsz .= " `urgence` = ".(isset($_POST['urgence']) && is_array($_POST['urgence']) ? array_sum($_POST['urgence']) : "NULL");
  $qsz .= ",`infos` = '".$mysqli->real_escape_string($_POST['infos'])."'";
  $qsz .= " WHERE `id` = ".intval($_SESSION['id'])." LIMIT 1";

  $mysqli->query($qsz) or trigger_error($mysqli->error);

  $mysqli->close();

  $message1 = 'Informations bien reçues.';
}
else if (isset($_POST['action']) && $_POST['action'] == 'iamok') {
  $mysqli = new MySQLi('localhost', 'riikc_112', '2c4;i+mu7CJ;', 'riikc_help');
  $mysqli->set_charset('utf8');

  $qsz  = "UPDATE `help` SET";
  $qsz .= " `indanger` = 0";
  $qsz .= " WHERE `id` = ".intval($_SESSION['id'])." LIMIT 1";

  $mysqli->query($qsz) or trigger_error($dblink->error);

  $mysqli->close();

  $message2 = 'Information bien reçue. Vous n\'êtes plus en danger.';
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>112 Hulp</title>
    <link href="/style.css" rel="stylesheet">
  </head>
  <body>
  <div id="content">
    <div id="logo">112HULP.be</div>
<?php if (isset($message2)) { ?>
    <p style="color:#0f0; font-weight:bold;"><?= htmlentities($message2) ?></p>
<?php } else { ?>
    <p>Noodgeval type.</p>
<?php if (isset($message1)) { ?>
      <p style="color:#0f0; font-weight:bold;"><?= htmlentities($message1) ?></p>
<?php } ?>
    <form method="post" action="/info.php">
      <ul class="checkbox">
        <li><label><input type="checkbox" name="urgence[]" value="1" id="urgence_1" /> Brand</label></li>
        <li><label><input type="checkbox" name="urgence[]" value="2" id="urgence_2" /> Verkeersongeval</label></li>
        <li><label><input type="checkbox" name="urgence[]" value="4" id="urgence_4" /> Verwonding</label></li>
        <li><label><input type="checkbox" name="urgence[]" value="8" id="urgence_8" /> Aanslag</label></li>
      </ul>
      <div>
        <label for="infos">Meer details</label>
        <textarea name="infos" id="infos"></textarea>
      </div>

      <div id="send-info"><button name="action" value="send">DETAILS VERZENDEN</button></div>
	    <p class="itsok">Ik heb geen hulp meer nodig ? Klik hier</p>

      <div id="send-ok"><button name="action" value="iamok">IK BEN NIET MEER IN GEVAAR</button></div>
    </form>
<?php } ?>

    <p class="legal">*Alle personen gebruikers die deze dienst misbruiken worden blootgesteld aan rechtszaken die kunnen leiden tot strafrechtelijke veroordelingen.</p>
  </div>
  </body>
</html>