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

session_start();

if (isset($_GET['name' ])) $_SESSION['name' ] = trim(urldecode($_GET['name' ])); else if (isset($_SESSION['name' ])) unset($_SESSION['name' ]);
if (isset($_GET['phone'])) $_SESSION['phone'] = trim(urldecode($_GET['phone'])); else if (isset($_SESSION['phone'])) unset($_SESSION['phone']);

if (isset($_POST['action'], $_POST['lat'], $_POST['lng']) && $_POST['action'] == 'send') {
  $headers = (function_exists('apache_request_headers') ? apache_request_headers() : $_SERVER);
  if ( array_key_exists( 'X-Forwarded-For', $headers ) && filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP ) ) {
    $forward = $headers['X-Forwarded-For'];
  }
  else if ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ) && filter_var( $headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP ) ) {
    $forward = $headers['HTTP_X_FORWARDED_FOR'];
  }

  $sh = shell_exec('whois '.(isset($forward) ? $forward : $_SERVER['REMOTE_ADDR']));

  $mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
  $mysqli->set_charset('utf8');

  $qsz  = "INSERT INTO `help` (";
  $qsz .= " `datetime`";
  $qsz .= ",`position`";
  $qsz .= ",`accuracy`";
  $qsz .= ",`battery`";
  $qsz .= ",`ip`";
  $qsz .= ",`ip_forwarded`";
  $qsz .= ",`whois`";
  $qsz .= ",`name`";
  $qsz .= ",`phone`";
  $qsz .= ") VALUES (";
  $qsz .= " '".date('Y-m-d H:i:s', round(intval($_POST['time']) / 1000))."'";
  $qsz .= ",GeomFromText(CONCAT('POINT(".floatval($_POST['lng'])." ".floatval($_POST['lat']).")'))";
  $qsz .= ",".floatval($_POST['acc']);
  $qsz .= ",".($_POST['batt'] != -1 ? floatval($_POST['batt']) * 100 : "NULL");
  $qsz .= ",'".$_SERVER['REMOTE_ADDR']."'";
  $qsz .= ",".(isset($forward) ? "'".$mysqli->real_escape_string($forward)."'" : "NULL");
  $qsz .= ",'".$mysqli->real_escape_string($sh)."'";
  $qsz .= ",".(isset($_POST['name']) ? "'".$mysqli->real_escape_string($_POST['name'])."'" : "NULL");
  $qsz .= ",".(isset($_POST['phone']) ? "'".$mysqli->real_escape_string($_POST['phone'])."'" : "NULL");
  $qsz .= ")";

  $mysqli->query($qsz) or trigger_error($mysqli->error);

  $_SESSION['id'] = $mysqli->insert_id;
  $_SESSION['lng'] = floatval($_POST['lng']);
  $_SESSION['lat'] = floatval($_POST['lat']);

  $mysqli->close();

  header('Location:more.php');
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
            </div>

            <!--Champ disant qu'on est en contact avec le 112-->
            <div class="champ-info-112">
                <?= _('You are in contact with 112 services.') ?>
            </div>
        </header>
    </div>
    <!--conteneur principal avec les 4 boutons icône représentant le type d'incident-->
    <div class="container container-main">
      <form method="post" action="/index.php">
        <div class="main">
            <input type="hidden" name="batt" id="batt" value="-1" readonly="readonly">
            <input type="hidden" name="time" id="time" readonly="readonly">
            <input type="hidden" name="lat" id="lat" readonly="readonly">
            <input type="hidden" name="lng" id="lng" readonly="readonly">
            <input type="hidden" name="acc" id="acc" readonly="readonly">

<?php if (isset($_SESSION['name'])) { ?>
            <input type="hidden" name="name" id="name" value="<?= htmlentities($_SESSION['name']) ?>" readonly="readonly"><br>
<?php } ?>
<?php if (isset($_SESSION['phone'])) { ?>
            <input type="hidden" name="phone" id="phone" value="<?= htmlentities($_SESSION['phone']) ?>" readonly="readonly"><br>
<?php } ?>

            <button type="submit" name="action" value="send" id="btn-send" disabled="disabled"><?= _('Send my location') ?></button>
          <p class="legal"><?= _('Irresponsible use of this emergency service is punishable under federal law.') ?></p>
        </div>
      </form>
    </div>
    <!--Le footer apparaît après que l'on ait cliqué sur un des boutons-->
    <div class="container container-footer">
        <footer>
            <!--Champ prévenant que l'on géolocalise ou que l'on a pas réussi à géolocaliser ou qui affiche l'adresse obtenu après géolocalisation-->
            <div class="btn-edit-address">
                <div class="icon-geolocation"></div>
            </div>
            <div class="info-geolocalisation">
                <?= _('We are locating you') ?><br><span><?= _('Please wait ...') ?></span>
            </div>
        </footer>
    </div>
    <div class="modal-container">
        <div class="modal-inner">
            <div class="description">
                <!--<?= _('Voulez-vous annuler l\'alerte') ?>-->
            </div>
            <!--
            <div class="btn-action-container">
                <button class="up-action"><?= _('Oui') ?></button>
                <button class="down-action"><?= _('Non') ?></button>
            </div>
            -->
        </div>
    </div>
    <script src="/index.js"></script>
</body>

</html>