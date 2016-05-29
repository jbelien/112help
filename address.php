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

if (isset($_REQUEST['new-lat'], $_REQUEST['new-lng'])) {
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

    if (!isset($_SESSION['id'])) {
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
        $qsz .= " '".date('Y-m-d H:i:s')."'";
        $qsz .= ",GeomFromText(CONCAT('POINT(".floatval($_REQUEST['new-lng'])." ".floatval($_REQUEST['new-lat']).")'))";
        $qsz .= ",-1";
        $qsz .= ",".($_REQUEST['batt'] != -1 ? floatval($_REQUEST['batt']) * 100 : "NULL");
        $qsz .= ",'".$_SERVER['REMOTE_ADDR']."'";
        $qsz .= ",".(isset($forward) ? "'".$mysqli->real_escape_string($forward)."'" : "NULL");
        $qsz .= ",'".$mysqli->real_escape_string($sh)."'";
        $qsz .= ",".(isset($_SESSION['name']) ? "'".$mysqli->real_escape_string($_SESSION['name'])."'" : "NULL");
        $qsz .= ",".(isset($_SESSION['phone']) ? "'".$mysqli->real_escape_string($_SESSION['phone'])."'" : "NULL");
        $qsz .= ")";
    } else {
        $qsz  = "UPDATE `help` SET";
        $qsz .= " `position` = GeomFromText(CONCAT('POINT(".floatval($_REQUEST['new-lng'])." ".floatval($_REQUEST['new-lat']).")'))";
        $qsz .= ",`address` = NULL";
        $qsz .= " WHERE `id` = ".intval($_SESSION['id'])." LIMIT 1";
    }

    $mysqli->query($qsz) or trigger_error($mysqli->error);

    $_SESSION['id'] = $mysqli->insert_id;
    $_SESSION['lng'] = floatval($_REQUEST['new-lng']);
    $_SESSION['lat'] = floatval($_REQUEST['new-lat']);

    $mysqli->close();

    header('Location:index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">

    <link rel="stylesheet" href="/css/css-reset.min.css">
    <link rel="stylesheet" href="/css/front-end-4-picto.min.css">
    <title>112Help</title>
</head>
<!--Ajouter une classe "message" une fois que l'on a cliqué sur envoyer un message-->
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
                Vous êtes en contact avec les services du 112
            </div>

            <!--Bouton Annulé-->
            <div class="annulation-alert">
                <div class="btn-cancel">x</div>
            </div>

        </header>
    </div>

    <!--Conteneur pour entrer l'adresse manuellement-->
    <div class="container container-select-adresse">
        <form action="/address.php" method="get" id="form-address">
        <input type="hidden" name="batt" id="batt" value="-1" readonly="readonly">
        <input type="hidden" name="new-lat" id="lat"<?= (isset($_SESSION['lat']) ? ' value="'.floatval($_SESSION['lat']).'"' : '') ?> readonly="readonly">
        <input type="hidden" name="new-lng" id="lng"<?= (isset($_SESSION['lng']) ? ' value="'.floatval($_SESSION['lng']).'"' : '') ?> readonly="readonly">
        <div class="adresse-detectee">
            <div class="icon"></div>
            <div class="champ-adresse">
                <div class="adresse">
                    <input type="text" name="address" id="address">
                </div>
            </div>
            <!--
            <div class="btn-edit">
                <div class="icon-edit"></div>
            </div>
            -->
        </div>
        </form>
    </div>
    <!--Conteneur avec le type d'incident et une description-->
    <!--<div class="container container-info-incident">
        <div class="info-incident">
            <div class="icon sante"></div>
            <div class="description">
                Description
            </div>
        </div>
    </div>-->
    <!--conteneur principal avec les 4 boutons icône représentant le type d'incident-->
    <div class="container container-map">
        <div class="map" id="map"></div>
        <!--<div class="btn-edit-marker"></div>-->
    </div>
   <!--Validation de l'adresse-->
    <div class="container container-validation-adresse">
        <div class="validation-adresse">
            <button type="submit" class="texte-validation">
                <span>Valider l'adresse</span>
            </button>
        </div>
    </div>
    <div class="modal-container">
        <div class="modal-inner">
            <div class="description">
                Voulez-vous annuler l'appel ?
            </div>
            <div class="btn-action-container">
                <button class="up-action">Oui</button>
                <button class="down-action">Non</button>
            </div>
        </div>
    </div>

    <script>
        var position = <?= (isset($_SESSION['lat'], $_SESSION['lng']) ? '{ lng: '.floatval($_SESSION['lng']).', lat: '.floatval($_SESSION['lat']).' }' : 'null') ?>;
    </script>
    <script src="/js/address.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD_p27IkNE2nxfTCtuf5oxyGUsmz4R7i34&amp;language=<?= $lang ?>&amp;libraries=places&amp;callback=initMap" async defer></script>
</body>

</html>