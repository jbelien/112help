<?php
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

if (isset($_POST['type'])) {
  $mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
  $mysqli->set_charset('utf8');

  $qsz  = "UPDATE `help` SET";
  $qsz .= " `urgence` = ".intval($_POST['type']);
  //$qsz .= ",`infos` = '".$mysqli->real_escape_string($_POST['details'])."'";
  $qsz .= " WHERE `id` = ".intval($_SESSION['id'])." LIMIT 1";

  $mysqli->query($qsz) or trigger_error($mysqli->error);

  $mysqli->close();

  header('Location:more2.php');
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">

    <link rel="stylesheet" href="/css/css-reset.min.css">
    <link rel="stylesheet" href="/css/front-end-4-picto.css">
    <title><?= _('112Help') ?></title>
</head>

<body>
    <!--Dans le header, le logo, un champ pour avertir que la personne est bien en contact avec le 112 et le bouton annulé-->
    <div class="container container-header">
        <header>
            <!--logo-->
            <div class="logo">
                112
                <div class></div>
            </div>

            <!--Champ disant qu'on est en contact avec le 112-->
            <div class="champ-info-112">
                <?= _('You are in contact with 112 services.') ?>
            </div>

        </header>
    </div>
    <!--conteneur principal avec les 4 boutons icône représentant le type d'incident-->
    <div class="container container-main">
      <form method="post" action="/more.php">
        <div class="main">
            <div class="description-alert-container col-100">
                <div class="info-action-alert">
                    <?= _('Choose the type of incident') ?>
                </div>
                <div class="description-alert">
                    <?= _('La description d\'une alert une fois qu\'on en a sélectionné une.') ?>
                </div>
            </div>
            <div class="btn-container col-50">
                <button type="submit" class="btn sante" name="type" value="4"></button>
                <div class="type-incident"><?= _('Health') ?></div>
            </div>
            <div class="btn-container col-50">
                <button type="submit" class="btn feu" name="type" value="1"></button>
                <div class="type-incident"><?= _('Fire') ?></div>
            </div>
            <div class="btn-container col-50">
                <button type="submit" class="btn accident" name="type" value="2"></button>
                <div class="type-incident"><?= _('Accident') ?></div>
            </div>
            <div class="btn-container col-50">
                <button type="submit" class="btn violence" name="type" value="8"></button>
                <div class="type-incident"><?= _('Violence') ?></div>
            </div>
        </div>
      </form>
    </div>
</body>

</html>