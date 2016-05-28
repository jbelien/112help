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
                <?= _('You are in contact with 112 services.') ?>
            </div>

            <!--Bouton Annulé-->
            <div class="annulation-alert">
                <div class="btn-cancel">x</div>
            </div>

        </header>
    </div>
    <!--Conteneur avec l'adresse qui a été détecté ainsi que la possiblité de la modifier-->
    <div class="container container-adresse-detectee">
        <div class="adresse-detectee">
            <div class="icon"></div>
            <div class="champ-adresse">
                <div class="adresse">
                </div>
                <div class="champ-input">
                    <input id="adresse-geolocalise" type="text" />
                </div>
            </div>
            <div class="btn-edit">
                <button class="icon-edit"></button>
            </div>
        </div>
    </div>
    <!--Conteneur avec le type d'incident et une description-->
    <div class="container container-info-incident">
        <div class="info-incident">
            <div class="icon sante"></div>
            <div class="description">
                Description
            </div>
        </div>
    </div>
    <!--conteneur principal avec les 4 boutons icône représentant le type d'incident-->
    <div class="container container-map">
    </div>
    <!--Le footer apparaît après que l'on ait cliqué sur un des boutons-->
    <div class="container container-footer-map">
        <footer>
            <!--Champ prévenant que l'on géolocalise ou que l'on a pas réussi à géolocaliser ou qui affiche l'adresse obtenu après géolocalisation-->
            <div class="btn-wrapper">
                <div class="btn-reseaux-sociaux"></div>
                <a class="btn-call" href="tel:112"></a>
            </div>

        </footer>
    </div>
    <div class="container container-reseaux-sociaux">
        <div class="reseaux-sociaux">
            <div class="reseau-social whatsapp">
                <div class="icon"></div>
                <div class="form-container">
                    <h3>WhatsApp</h3>
                    <input type="text">
                </div>
                <div class="send"></div>
            </div>
            <div class="reseau-social messenger">
                <div class="icon"></div>
                <div class="form-container">
                    <h3>Messenger</h3>
                    <input type="text">
                </div>
                <div class="send"></div>
            </div>
            <div class="reseau-social viber">
                <div class="icon"></div>
                <div class="form-container">
                    <h3>Viber</h3>
                    <input type="text">
                </div>
                <div class="send"></div>
            </div>
            <div class="message-direct">
                <div class="icon"></div>
                <div class="form-container">
                    <h3>Envoyer un message direct</h3>
                    <textarea name="messageDirect" id="" cols="30" rows="10"></textarea>
                </div>
                <div class="send"></div>
            </div>
        </div>
    </div>
    <div class="modal-container">
        <div class="modal-inner">
            <div class="description">
                Voulez-vous annuler l'alert
            </div>
            <div class="btn-action-container">
                <button class="up-action">Oui</button>
                <button class="down-action">Non</button>
            </div>
        </div>
    </div>
    <script>
      var position = { lng: <?= $_SESSION['lng'] ?>, lat: <?= $_SESSION['lat'] ?> };
    </script>
    <script src="more2.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD_p27IkNE2nxfTCtuf5oxyGUsmz4R7i34&amp;language=<?= $lang ?>&amp;callback=init" async defer></script>
</body>

</html>