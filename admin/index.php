<?php
header('Content-Type: text/html; charset=utf-8');

$ini = parse_ini_file('../settings.ini', TRUE);

session_start(); if (!isset($_SESSION['user'])) { header('Location:login.php'); exit(); }

$mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
$mysqli->set_charset('utf8');

$relative = 604800; if (isset($_GET['relative'])) $relative = intval($_GET['relative']);

$messages = array(); $count1 = 0; $count2 = 0; $count4 = 0; $count8 = 0;

$qsz  = "SELECT *, X(`position`) AS `lng`, Y(`position`) AS `lat` FROM `help`";
if ($relative > 0) $qsz .= " WHERE `datetime` >= '".date('Y-m-d H:i:s', time()-$relative)."'";
$qsz .= " ORDER BY `datetime` DESC";

$q = $mysqli->query($qsz) or trigger_error($mysqli->error);
while ($r = $q->fetch_assoc()) {
  if ($r['urgence'] & 1) $count1++;
  if ($r['urgence'] & 2) $count2++;
  if ($r['urgence'] & 4) $count4++;
  if ($r['urgence'] & 8) $count8++;

  if (is_null($r['address'])) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.$r['lat'].','.$r['lng'].'&sensor=false');
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $json = curl_exec($ch); $addr = json_decode($json);
    curl_close($ch);

    if ($addr->status == 'OK') {
      $r['address'] = $addr->results[0]->formatted_address;
      $r['address_type'] = implode(',', $addr->results[0]->types);
      $r['address_time'] = date('Y-m-d H:i:s');

      $qsz  = "UPDATE `help` SET";
      $qsz .= " `address` = '".$mysqli->real_escape_string($r['address'])."'";
      $qsz .= ",`address_type` = '".$mysqli->real_escape_string($r['address_type'])."'";
      $qsz .= ",`address_time` = '".$r['address_time']."'";
      $qsz .= " WHERE `id` = ".$r['id']." LIMIT 1";
      $mysqli->query($qsz) or trigger_error($mysqli->error);
    }
  }

  $messages[] = $r;
}
$q->free();

$count = count($messages);

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en" style="height:100%;">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>112 Help - Backend</title>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/ol3/3.15.1/ol.css">
    <link rel="stylesheet" href="../css/admin.css">
  </head>

  <body style="height:100%;">
    <div class="container-fluid" style="height:100%;">
      <div id="menu">
        <div class="row">
          <div class="col-sm-4">
            <form action="index.php" method="get" autocomplete="off">
            <select name="relative" class="form-control input-lg" style="margin-top:7px;">
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
            </form>
          </div>
          <div class="col-sm-4 text-center">
            <?= sprintf(ngettext('%d message', '%d messages', $count), $count) ?> :
            <span class="small" style="color:rgb(0,128,255);"><?= $count1 ?></span>
            <span class="small" style="color:rgb(255,128,0);"><?= $count2 ?></span>
            <span class="small" style="color:rgb(255,128,255);"><?= $count4 ?></span>
            <span class="small" style="color:rgb(128,0,255);"><?= $count8 ?></span>
          </div>
          <div class="col-sm-4 text-center"><time datetime="<?= date('c') ?>"><?= strftime('%e %b %G %H:%M:%S'); ?></time></div>
        </div>
      </div>
      <div class="row" style="padding-top: 60px; height:100%;">
        <div class="col-sm-8" style="height:100%; padding-left:0;">
          <div id="map"></div>
        </div>
        <div class="col-sm-4" style="height:100%; overflow:auto;">
          <form action="index.php" method="post" autocomplete="off">
          <div class="row" style="margin-bottom: 10px;">
            <div class="col-sm-6">
              <label><?= _('Change basemap') ?> :</label>
              <select name="baselayer" class="form-control">
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
            <div class="col-sm-6">
              <label><?= _('Show messages on map') ?> :</label>
              <div class="btn-group btn-group-justified" data-toggle="buttons">
                <label class="btn btn-default active">
                  <input type="radio" name="show" value="1" id="show1" autocomplete="off" checked="checked"> <?= _('Show') ?>
                </label>
                <label class="btn btn-default">
                  <input type="radio" name="show" value="0" id="show0" autocomplete="off"> <?= _('Hide') ?>
                </label>
              </div>
            </div>
          </div>
          </form>
          <ul id="list" class="list-unstyled">
<?php
  foreach ($messages as $m) {
    $datetime1 = new DateTime();
    $datetime2 = new DateTime($m['datetime']);
    $interval = $datetime1->diff($datetime2);

    echo '<li data-id="'.$m['id'].'" id="message-'.$m['id'].'"'.($m['indanger'] == 0 ? ' style="opacity:0.3;" class="small"' : '').'>';
      if ($m['indanger'] == 1) echo '<div class="pull-right text-danger"><i class="glyphicon glyphicon-exclamation-sign"></i> '._('I\'m in danger').'</div>';
      else echo '<div class="pull-right text-success"><i class="glyphicon glyphicon-ok"></i> '._('I\'m ok').'</div>';
      echo '<div>';
        echo '<strong>'.strftime('%e %b %G %H:%M:%S', strtotime($m['datetime'])).'</strong>';
        echo ' (';
        if ($interval->y > 0) echo sprintf(ngettext('%d year ago', '%d years ago', $interval->y), $interval->y);
        else if ($interval->m > 0) echo sprintf(ngettext('%d month ago', '%d months ago', $interval->m), $interval->m);
        else if ($interval->d > 0) echo sprintf(ngettext('%d day ago', '%d days ago', $interval->d), $interval->d);
        else if ($interval->h > 0) echo sprintf(ngettext('%d hour ago', '%d hours ago', $interval->h), $interval->h);
        else if ($interval->i > 0) echo sprintf(ngettext('%d minute ago', '%d minutes ago', $interval->i), $interval->i);
        echo ')';
      echo '</div>';
      if (!is_null($m['name']) || !is_null($m['phone'])) echo '<div class="text-info">'.htmlentities($m['name']).' - '.htmlentities($m['phone']).'</div>';
      echo '<div>'.($m['accuracy'] <= 250 ? $m['address'] : '<i class="text-muted">Not enough precision to display address ('.$m['accuracy'].' m.)</i>').'</div>';
      if (!is_null($m['battery'])) echo '<div>Battery: '.$m['battery'].'%</div>';
      echo '<div>';
        $u = array();
        if ($m['urgence'] & 1) $u[] = 'Incendie';
        if ($m['urgence'] & 2) $u[] = 'Accident de la route';
        if ($m['urgence'] & 4) $u[] = 'Blessure';
        if ($m['urgence'] & 8) $u[] = 'Attentat';
        echo htmlentities(implode(', ', $u));
      echo '</div>';
      if (!is_null($m['infos'])) echo '<div>'.htmlentities($m['infos']).'</div>';
    echo '</li>'.PHP_EOL;
  }
?>
          </ul>
        </div>
      </div>
    </div>
    <script src="//code.jquery.com/jquery-1.12.3.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/ol3/3.16.0/ol.js"></script>
    <script src="index.js"></script>
    <script type="text/javascript">
      var relative = <?= $relative ?>;

/*
      $(document).ready(function() {
        vectorSource = new ol.source.Vector();

        var features = new Array();
<?php
  reset($messages);
  foreach ($messages as $i => $m) {
    echo '        var feature = new ol.Feature({ indanger: '.$m['indanger'].', urgence: '.intval($m['urgence']).', geometry: new ol.geom.Circle(ol.proj.fromLonLat(['.$m['lng'].','.$m['lat'].']),'.$m['accuracy'].') });';
    echo 'feature.setId("'.$m['id'].'");';
    echo 'features.push(feature);'.PHP_EOL;
    echo '        var feature = new ol.Feature({ indanger: '.$m['indanger'].', urgence: '.intval($m['urgence']).', type: "center", geometry: new ol.geom.Point(ol.proj.fromLonLat(['.$m['lng'].','.$m['lat'].'])) });';
    echo 'feature.setId("'.$m['id'].'-center");';
    echo 'features.push(feature);'.PHP_EOL;
  }
?>
        vectorSource.addFeatures(features);

      });
*/
    </script>
  </body>
</html>