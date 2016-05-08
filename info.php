<?php
header('Content-Type: text/html; charset=utf-8');

$ini = parse_ini_file('settings.ini', TRUE);

switch ($_SERVER['HTTP_HOST']) {
  case '112help.be': $lang = 'en'; break;
  case '112hulp.be': $lang = 'nl'; break;
  case '112aide.be': $lang = 'fr'; break;
  default:
    $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $lang = strtolower(substr(current($languages), 0, 2));
    break;
}

if ($lang == 'fr' || $lang == 'nl') {
  putenv('LC_TIME='.$lang.'_BE.UTF-8');
  putenv('LC_MESSAGES='.$lang.'_BE.UTF-8');
  setlocale(LC_TIME, $lang.'_BE.UTF-8');
  if (defined('LC_MESSAGES')) setlocale(LC_MESSAGES, $lang.'_BE.UTF-8');
}
else {
  putenv('LC_ALL=en_US.UTF-8');
  setlocale(LC_ALL, 'en_US.UTF-8');
}
bindtextdomain('112help', __DIR__.'/locale');
bind_textdomain_codeset('112help', 'UTF-8');
textdomain('112help');

session_start(); if (!isset($_SESSION['id'])) { header('Location: /index.php'); exit(); }

if (isset($_POST['action'], $_POST['details']) && $_POST['action'] == 'send') {
  $mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
  $mysqli->set_charset('utf8');

  $qsz  = "UPDATE `help` SET";
  $qsz .= " `urgence` = ".(isset($_POST['urgence']) && is_array($_POST['urgence']) ? array_sum($_POST['urgence']) : "NULL");
  $qsz .= ",`infos` = '".$mysqli->real_escape_string($_POST['details'])."'";
  $qsz .= " WHERE `id` = ".intval($_SESSION['id'])." LIMIT 1";

  $mysqli->query($qsz) or trigger_error($mysqli->error);

  $mysqli->close();

  $message1 = _('Informations bien reçues.');
}
else if (isset($_POST['action']) && $_POST['action'] == 'iamok') {
  $mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
  $mysqli->set_charset('utf8');

  $qsz  = "UPDATE `help` SET";
  $qsz .= " `indanger` = 0";
  $qsz .= " WHERE `id` = ".intval($_SESSION['id'])." LIMIT 1";

  $mysqli->query($qsz) or trigger_error($dblink->error);

  $mysqli->close();

  $message2 = _('Information bien reçue. Vous n\'êtes plus en danger.');
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>112 Help</title>
    <link href="css/style.css" rel="stylesheet">
  </head>
  <body>
  <div class="container">
    <h1 id="logo" style="margin-bottom:0;"><?= strtoupper($_SERVER['HTTP_HOST']) ?></h1>
    <h2 style="color:#ffff00; margin:0;">** PROTOTYPE **</h2>
<?php if (isset($message2)) { ?>
    <p style="color:#0f0; font-weight:bold;"><?= htmlentities($message2) ?></p>
<?php } else { ?>
    <p><?= _('If able, please inform us more about your current situation.') ?></p>
<?php if (isset($message1)) { ?>
      <p style="color:#0f0; font-weight:bold;"><?= htmlentities($message1) ?></p>
<?php } ?>
    <form id="info" method="post" action="info.php">
      <div class="row" id="urgence">
        <div class="col-xs-6"><label><input type="checkbox" name="urgence[]" value="1" id="urgence_1"> <?= _('Fire') ?></label></div>
        <div class="col-xs-6"><label><input type="checkbox" name="urgence[]" value="2" id="urgence_2"> <?= _('Road accident') ?></label></div>
        <div class="col-xs-6"><label><input type="checkbox" name="urgence[]" value="4" id="urgence_4"> <?= _('Injury') ?></label></div>
        <div class="col-xs-6"><label><input type="checkbox" name="urgence[]" value="8" id="urgence_8"> <?= _('Violence') ?></label></div>
      </div>

      <div>
        <label for="details"><?= _('Details') ?> :</label>
        <textarea name="details" id="details" style="width:100%;"></textarea>
      </div>

      <div id="send-info"><button name="action" value="send"><?= _('SEND DETAILS') ?></button></div>

      <p class="itsok"><?= _('Are you out of danger ? Please let us know by clicking the following button') ?> :</p>
      <div id="send-ok"><button name="action" value="iamok"><?= _('I\'M SAFE NOW') ?></button></div>
    </form>
<?php } ?>

    <p class="legal">* <?= _('Irresponsible use of this emergency service is punishable under federal law.') ?></p>
  </div>
  </body>
</html>