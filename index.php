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

if (isset($_GET['clear'], $_SESSION['id'])) {
    $mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
    $mysqli->set_charset('utf8');

    $mysqli->query("UPDATE `help` SET `indanger` = 0 WHERE `id` = ".intval($_SESSION['id'])." LIMIT 1") or trigger_error($mysqli->error);

    $mysqli->close();

    unset($_SESSION);
}

if (isset($_REQUEST['action'], $_REQUEST['lat'], $_REQUEST['lng']) && $_REQUEST['action'] == 'send') {
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
    $qsz .= " '".date('Y-m-d H:i:s', round(intval($_REQUEST['time']) / 1000))."'";
    $qsz .= ",GeomFromText(CONCAT('POINT(".floatval($_REQUEST['lng'])." ".floatval($_REQUEST['lat']).")'))";
    $qsz .= ",".floatval($_REQUEST['acc']);
    $qsz .= ",".($_REQUEST['batt'] != -1 ? floatval($_REQUEST['batt']) * 100 : "NULL");
    $qsz .= ",'".$_SERVER['REMOTE_ADDR']."'";
    $qsz .= ",".(isset($forward) ? "'".$mysqli->real_escape_string($forward)."'" : "NULL");
    $qsz .= ",'".$mysqli->real_escape_string($sh)."'";
    $qsz .= ",".(isset($_REQUEST['name']) ? "'".$mysqli->real_escape_string($_REQUEST['name'])."'" : "NULL");
    $qsz .= ",".(isset($_REQUEST['phone']) ? "'".$mysqli->real_escape_string($_REQUEST['phone'])."'" : "NULL");
    $qsz .= ")";

    $mysqli->query($qsz) or trigger_error($mysqli->error);

    $_SESSION['id'] = $mysqli->insert_id;
    $_SESSION['lng'] = floatval($_REQUEST['lng']);
    $_SESSION['lat'] = floatval($_REQUEST['lat']);

    $mysqli->close();
}
else if (isset($_SESSION['id'], $_REQUEST['type'])) {
    $mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
    $mysqli->set_charset('utf8');

    $qsz  = "UPDATE `help` SET";
    $qsz .= " `urgence` = ".intval($_REQUEST['type']);
    //$qsz .= ",`infos` = '".$mysqli->real_escape_string($_REQUEST['details'])."'";
    $qsz .= " WHERE `id` = ".intval($_SESSION['id'])." LIMIT 1";

    $mysqli->query($qsz) or trigger_error($mysqli->error);

    $mysqli->close();

    $_SESSION['type'] = intval($_REQUEST['type']);
}
else if (isset($_REQUEST['action'], $_REQUEST['whatsapp']) && $_REQUEST['action'] == 'send-whatsapp') {
    $mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
    $mysqli->set_charset('utf8');
    $qsz  = "UPDATE `help` SET `social` = '".$mysqli->real_escape_string('whatsapp:'.$_REQUEST['whatsapp'])."' WHERE `id` = ".intval($_SESSION['id'])." LIMIT 1";
    $mysqli->query($qsz) or trigger_error($mysqli->error);
    $mysqli->close();

    $close = TRUE;
}
else if (isset($_REQUEST['action'], $_REQUEST['messenger']) && $_REQUEST['action'] == 'send-messenger') {
    $mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
    $mysqli->set_charset('utf8');
    $qsz  = "UPDATE `help` SET `social` = '".$mysqli->real_escape_string('messenger:'.$_REQUEST['messenger'])."' WHERE `id` = ".intval($_SESSION['id'])." LIMIT 1";
    $mysqli->query($qsz) or trigger_error($mysqli->error);
    $mysqli->close();

    $close = TRUE;
}
else if (isset($_REQUEST['action'], $_REQUEST['viber']) && $_REQUEST['action'] == 'send-viber') {
    $mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
    $mysqli->set_charset('utf8');
    $qsz  = "UPDATE `help` SET `social` = '".$mysqli->real_escape_string('viber:'.$_REQUEST['viber'])."' WHERE `id` = ".intval($_SESSION['id'])." LIMIT 1";
    $mysqli->query($qsz) or trigger_error($mysqli->error);
    $mysqli->close();

    $close = TRUE;
}
else if (isset($_REQUEST['action'], $_REQUEST['infos']) && $_REQUEST['action'] == 'send-infos') {
    $mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
    $mysqli->set_charset('utf8');
    $qsz  = "UPDATE `help` SET `infos` = '".$mysqli->real_escape_string($_REQUEST['infos'])."' WHERE `id` = ".intval($_SESSION['id'])." LIMIT 1";
    $mysqli->query($qsz) or trigger_error($mysqli->error);
    $mysqli->close();

    $close = TRUE;
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

<body class="<?= (!isset($_SESSION['id']) ? 'red-button' : 'types-incident') ?>">
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
                <?= _('You are connected with 112 emergency services') ?>
            </div>

            <!--Bouton Annulé-->
<?php if (isset($_SESSION['id'])) { ?>
            <div class="annulation-alert">
                <div class="btn-cancel">x</div>
            </div>
<?php } ?>
        </header>
    </div>
    <!--conteneur principal avec gros bouton rouge-->
    <div class="container container-btn-send">
        <form action="index.php" method="post">
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

            <button id="btn-send" name="action" value="send" disabled="disabled"><?= _('Locate me') ?></button>
        </form>
    </div>
     <!--Conteneur pour entrer l'adresse manuellement-->
    <div class="container container-adresse-detectee">
        <div class="adresse-detectee">
            <div class="icon"></div>
            <div class="champ-adresse">
                <div class="adresse"></div>
            </div>
            <a href="address.php?lat=<?= $_SESSION['lat'] ?>&amp;lng=<?= $_SESSION['lng'] ?>" class="btn-edit">
                <div class="icon-edit"></div>
            </a>
        </div>
    </div>
    <!--conteneur principal avec les 4 boutons icône représentant le type d'incident-->
    <div class="container container-main">
        <form method="post" action="/index.php">
            <div class="main">
                <div class="description-alert-container col-100">
                    <div class="info-action-alert">
                        <?= _('Choose incident type') ?>
                    </div>
                </div>
                <div class="btn-container col-50">
                    <button class="btn sante<?= (isset($_SESSION['type']) && $_SESSION['type'] == 4 ? ' active' : '') ?>" name="type" value="4"></button>
                    <div class="type-incident"><?= _('Health') ?></div>
                </div>
                <div class="btn-container col-50">
                    <button class="btn feu<?= (isset($_SESSION['type']) && $_SESSION['type'] == 1 ? ' active' : '') ?>" name="type" value="1"></button>
                    <div class="type-incident"><?= _('Fire') ?></div>
                </div>
                <div class="btn-container col-50">
                    <button class="btn accident<?= (isset($_SESSION['type']) && $_SESSION['type'] == 2 ? ' active' : '') ?>" name="type" value="2"></button>
                    <div class="type-incident"><?= _('Accident') ?></div>
                </div>
                <div class="btn-container col-50">
                    <button class="btn violence<?= (isset($_SESSION['type']) && $_SESSION['type'] == 8 ? ' active' : '') ?>" name="type" value="8"></button>
                    <div class="type-incident"><?= _('Violence') ?></div>
                </div>
            </div>
        </form>
    </div>
    <!--Conteneur pour entrer l'adresse manuellement-->
    <div class="container container-select-adresse">
        <div class="adresse-detectee">
            <div class="icon"></div>
            <div class="champ-adresse">
                <div class="adresse">
                    <?= _('Define your address') ?>
                </div>
                <div class="champ-input">
                    <input id="adresse-geolocalise" type="text" />
                </div>
            </div>
            <a href="/address.php" class="btn-edit">
                <div class="icon-edit"></div>
            </a>
        </div>
    </div>
    <!--Le footer apparaît après que l'on ait cliqué sur un des boutons-->
    <div class="container container-footer-map active">
        <footer>
            <!--Champ prévenant que l'on géolocalise ou que l'on a pas réussi à géolocaliser ou qui affiche l'adresse obtenu après géolocalisation-->
            <div class="btn-wrapper">
                <div class="btn-reseaux-sociaux"></div>
                <a class="btn-call" href="tel:112"></a>
            </div>

        </footer>
    </div>
    <!--Le footer apparaît après que l'on ait cliqué sur un des boutons-->
    <div class="container container-footer active">
        <footer>
            <!--Champ prévenant que l'on géolocalise ou que l'on a pas réussi à géolocaliser ou qui affiche l'adresse obtenu après géolocalisation-->
            <div class="btn-edit-address">
                <div class="icon-geolocation"></div>
            </div>
            <div class="info-geolocalisation">
                <?= _('We are locating you') ?><br><?= _('Please wait...') ?>
            </div>
        </footer>
    </div>

    <!--Container pour le formulaire des choix des réseaux sociaux-->
    <div class="container container-reseaux-sociaux">
        <div class="back">
            <div class="arrow"></div>
        </div>
        <form action="/index.php" method="post" class="reseaux-sociaux">
            <div class="reseau-social whatsapp">
                <div class="icon whatsapp"></div>
                <div class="form-container">
                    <h3>WhatsApp</h3>
                    <input type="tel" name="whatsapp" placeholder="<?= _('Your phone number') ?>"<?= (isset($_SESSION['phone']) ? ' value="'.htmlentities($_SESSION['phone']).'"' : '') ?>>
                </div>
                <button class="send" name="action" value="send-whatsapp"></button>
            </div>
            <div class="reseau-social messenger">
                <div class="icon messenger"></div>
                <div class="form-container">
                    <h3>Messenger</h3>
                    <input type="tel" name="messenger" placeholder="<?= _('Your phone number') ?>"<?= (isset($_SESSION['phone']) ? ' value="'.htmlentities($_SESSION['phone']).'"' : '') ?>>
                </div>
                <button class="send" name="action" value="send-messenger"></button>
            </div>
            <div class="reseau-social viber">
                <div class="icon viber"></div>
                <div class="form-container">
                    <h3>Viber</h3>
                    <input type="tel" name="viber" placeholder="<?= _('Your phone number') ?>"<?= (isset($_SESSION['phone']) ? ' value="'.htmlentities($_SESSION['phone']).'"' : '') ?>>
                </div>
                <button class="send" name="action" value="send-viber"></button>
            </div>
            <div class="reseau-social message-direct">
                <div class="icon btn-112">112</div>
                <div class="form-container">
                    <h3><?= _('Send direct message') ?></h3>
                    <textarea name="infos" cols="30" rows="10" placeholder="<?= _('Enter your message') ?>"></textarea>
                </div>
                <button class="send" name="action" value="send-infos"></button>
            </div>
        </form>
    </div>

    <!--Container pour la modal d'annulation-->
    <div class="modal-container">
        <div class="modal-inner">
            <div class="description">
                <?= _('Do you want to cancel 112 call ?') ?>
            </div>
            <div class="btn-action-container">
                <button class="up-action"><?= _('Yes') ?></button>
                <button class="down-action"><?= _('No') ?></button>
            </div>
        </div>
    </div>

    <div class="modal-container confirmation-appel<?= (isset($close) && $close === TRUE ? ' active' : '') ?>">
        <div class="modal-inner">
            <div class="description">
                <?= _('We have taken your data into account, we contact you as soon as possible.') ?>
            </div>
            <!--
            <div class="btn-wrapper">
                <button onclick="window.close();"><?= _('Close') ?></button>
            </div>
            -->
        </div>
    </div>
    <div class="modal-container confirmation-appel<?= (isset($_GET['clear']) ? ' active' : '') ?>">
        <div class="modal-inner">
            <div class="description">
                <?= _('Call well ended !') ?>
            </div>
            <!--
            <div class="btn-wrapper">
                <button onclick="window.close();"><?= _('Close') ?></button>
            </div>
            -->
        </div>
    </div>
<?php
if (isset($_SESSION['id'], $_SESSION['lng'], $_SESSION['lat'])) {
?>
    <script>
        var position = { lng: <?= $_SESSION['lng'] ?>, lat: <?= $_SESSION['lat'] ?> };
    </script>
    <script src="/js/index2.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD_p27IkNE2nxfTCtuf5oxyGUsmz4R7i34&amp;language=<?= $lang ?>&amp;callback=init" async defer></script>
<?php
} else if (!isset($_GET['clear'])) {
?>
    <script src="/js/index.js"></script>
<?php
}
?>
</body>

</html>