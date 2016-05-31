<?php
$ini = parse_ini_file('../settings.ini', TRUE);

session_start(); if (!isset($_SESSION['user'])) { header('Location:login.php'); exit(); }

$mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
$mysqli->set_charset('utf8');

$relative = 86400; if (isset($_GET['relative'])) $relative = intval($_GET['relative']);
$type = 0; if (isset($_GET['type'])) $type = intval($_GET['type']);

$messages = array(); $count = 0; $count1 = 0; $count2 = 0; $count4 = 0; $count8 = 0;

$qsz  = "SELECT `id`, `urgence` FROM `help`"; if ($relative > 0) $qsz .= " WHERE `datetime` >= '".date('Y-m-d H:i:s', time()-$relative)."'";

$q = $mysqli->query($qsz) or trigger_error($mysqli->error);
while ($r = $q->fetch_assoc()) {
  if ($r['urgence'] & 1) $count1++;
  if ($r['urgence'] & 2) $count2++;
  if ($r['urgence'] & 4) $count4++;
  if ($r['urgence'] & 8) $count8++;
  $count++;
}
$q->free();

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>112 Help - Backend</title>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/ol3/3.15.1/ol.css">
    <link rel="stylesheet" href="style.css">
  </head>

  <body>

    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <form class="navbar-form navbar-left" style="margin-top:17px;">
            <select name="relative" class="form-control input-lg">
              <option value="300"    <?= ($relative ==     300 ? ' selected="selected"' : '') ?>><?= _('Search in the last 5 minutes') ?></option>
              <option value="900"    <?= ($relative ==     900 ? ' selected="selected"' : '') ?>><?= _('Search in the last 15 minutes') ?></option>
              <option value="1800"   <?= ($relative ==    1800 ? ' selected="selected"' : '') ?>><?= _('Search in the last 30 minutes') ?></option>
              <option value="3600"   <?= ($relative ==    3600 ? ' selected="selected"' : '') ?>><?= _('Search in the last 1 hour') ?></option>
              <option value="7200"   <?= ($relative ==    7200 ? ' selected="selected"' : '') ?>><?= _('Search in the last 2 hours') ?></option>
              <option value="28800"  <?= ($relative ==   28800 ? ' selected="selected"' : '') ?>><?= _('Search in the last 8 hours') ?></option>
              <option value="86400"  <?= ($relative ==   86400 ? ' selected="selected"' : '') ?>><?= _('Search in the last 1 day') ?></option>
              <option value="172800" <?= ($relative ==  172800 ? ' selected="selected"' : '') ?>><?= _('Search in the last 2 days') ?></option>
              <option value="432000" <?= ($relative ==  432000 ? ' selected="selected"' : '') ?>><?= _('Search in the last 5 days') ?></option>
              <option value="604800" <?= ($relative ==  604800 ? ' selected="selected"' : '') ?>><?= _('Search in the last 7 days') ?></option>
              <option value="1209600"<?= ($relative == 1209600 ? ' selected="selected"' : '') ?>><?= _('Search in the last 14 days') ?></option>
              <option value="2592000"<?= ($relative == 2592000 ? ' selected="selected"' : '') ?>><?= _('Search in the last 30 days') ?></option>
              <option value="0"      <?= ($relative ==       0 ? ' selected="selected"' : '') ?>><?= _('Search in all messages') ?></option>
            </select>

            <div class="input-group">
              <input type="text" class="form-control input-lg" placeholder="Municipality" disabled="disabled">
              <span class="input-group-btn">
                <button class="btn btn-default btn-lg" type="button" disabled="disabled">OK</button>
              </span>
            </div>
          </form>
          <ul class="nav navbar-nav navbar-right">
              <li class="badge-112">
                  <a href="?relative=<?= $relative ?>">
                      <img src="../img/admin/all.svg" alt="" height="38px" style="margin: 6px 0;">
                      <span class="badge"><?= $count ?></span>
                  </a>
              </li>
              <li class="badge-112">
                  <a href="?relative=<?= $relative ?>&amp;type=1">
                      <img src="../img/admin/fire.svg" alt="" height="50px">
                      <span class="badge"><?= $count1 ?></span>
                  </a>
              </li>
              <li class="badge-112">
                  <a href="?relative=<?= $relative ?>&amp;type=8">
                      <img src="../img/admin/violence.svg" alt="" height="50px">
                      <span class="badge"><?= $count8 ?></span>
                  </a>
              </li>
              <li class="badge-112">
                  <a href="?relative=<?= $relative ?>&amp;type=4">
                      <img src="../img/admin/health.svg" alt="" height="50px">
                      <span class="badge"><?= $count4 ?></span>
                  </a>
              </li>
              <li class="badge-112">
                  <a href="?relative=<?= $relative ?>&amp;type=2">
                      <img src="../img/admin/route.svg" alt="" height="50px">
                      <span class="badge"><?= $count2 ?></span>
                  </a>
              </li>
          </ul>
          <p class="navbar-text navbar-right"><time></time></p>
        </div>
      </div>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-8 main" id="map">
          <div id="map-addons">
            <div id="map-baselayers" class="alert">
              <label>Baselayer :</label>
              <select class="form-control input-sm" name="baselayer">
                <optgroup label="<?= _('World') ?>">
                  <option value="mapquest" selected="selected">OpenStreetMap</option>
                  <option value="bing">Bing Maps</option>
                  <option value="bing_p">Bing Maps (<?= _('Aerial') ?>)</option>
                </optgroup>
                <optgroup label="<?= _('Belgium') ?>">
                  <option value="cirb">Bruxelles / Brussel</option>
                  <option value="cirb_p">Bruxelles / Brussel (<?= _('Aerial') ?>)</option>
                  <option value="agiv">Vlaanderen</option>
                  <option value="agiv_p">Vlaanderen (<?= _('Aerial') ?>)</option>
                  <option value="spw">Wallonie</option>
                  <option value="spw_p">Wallonie (<?= _('Aerial') ?>)</option>
                </optgroup>
              </select>
            </div>
            <div id="map-hide" class="alert">
              <label><?= _('Show messages on map') ?> :</label>
              <div class="btn-group btn-group-justified" data-toggle="buttons">
                <label class="btn btn-default btn-sm active">
                  <input type="radio" name="show" value="1" id="show1" autocomplete="off" checked="checked"> <?= _('Show') ?>
                </label>
                <label class="btn btn-default btn-sm">
                  <input type="radio" name="show" value="0" id="show0" autocomplete="off"> <?= _('Hide') ?>
                </label>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-4 col-sm-offset-8 sidebar" id="list">
        </div>
      </div>
    </div>

    <div id="message" class="alert112" style="display: none;">
        <div class="row">
            <div class="col-sm-2 text-center icone"></div>
            <div class="col-sm-10">
              <div class="row">
                <div class="col-sm-9"><address style="margin-bottom: 5px;"></address></div>
                <div class="col-sm-3"><button class="btn btn-default btn-block btn-xs" data-toggle="modal">Details</button></div>
              </div>
              <div class="row">
                <div class="col-sm-3"><time class="heure"></time></div>
                <div class="col-sm-3"><span class="distance"></span></div>
                <div class="col-sm-3"><span class="batterie"></span></div>
                <div class="col-sm-3"><span class="social">YES</span></div>
              </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="message-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Incident details</h4>
                </div>
                <div class="modal-body">
                  <table class="table table-condensed table-bordered" style="margin-bottom:0;">
                    <tr class="infos-time"><th>Time</th><td></td></tr>
                    <tr class="infos-name"><th>Name</th><td></td></tr>
                    <tr class="infos-phone"><th>Phone</th><td></td></tr>
                    <tr class="infos-address"><th>Address</th><td></td></tr>
                    <tr class="infos-accuracy"><th>Accuracy</th><td></td></tr>
                    <tr class="infos-battery"><th>Battery</th><td></td></tr>
                    <tr class="infos-ip"><th>IP</th><td></td></tr>
                    <tr class="infos-infos"><th>Infos</th><td></td></tr>
                    <tr class="infos-social"><th>Social Network</th><td></td></tr>
                  </table>
                </div>
            </div>
        </div>
    </div>

    <script src="//code.jquery.com/jquery-1.12.3.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/ol3/3.16.0/ol.js"></script>
    <script src="index.js"></script>
    <script type="text/javascript">
      var relative = <?= $relative ?>;
      var type = <?= $type ?>;
    </script>
  </body>
</html>
