<?php
header('Content-Type: application/json; charset=utf-8');

$ini = parse_ini_file('../settings.ini', TRUE);

$json = array();
$json['type'] = 'FeatureCollection';
$json['features'] = array();

session_start(); if (!isset($_SESSION['user'])) { echo json_encode($json); exit(); }

$mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
$mysqli->set_charset('utf8');

$qsz  = "SELECT `id`, `indanger`, `urgence`, `accuracy`, `address`, `datetime`, `battery`, X(`position`) AS `lng`, Y(`position`) AS `lat` FROM `help`";
if (isset($_REQUEST['relative']) && $_REQUEST['relative'] > 0) { $qsz .= " WHERE `datetime` >= '".date('Y-m-d H:i:s', time()-intval($_REQUEST['relative']))."'"; }
$qsz .= " ORDER BY `datetime` DESC";

$q = $mysqli->query($qsz) or trigger_error($mysqli->error);
while ($r = $q->fetch_assoc()) {
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

  $datetime1 = new DateTime();
  $datetime2 = new DateTime($r['datetime']);
  $interval = $datetime1->diff($datetime2);
  if ($interval->y > 0) $r['ago'] = sprintf(_('%d year ago'), $interval->y);
  else if ($interval->m > 0) $r['ago'] = sprintf(_('%d month ago'), $interval->m);
  else if ($interval->d > 0) $r['ago'] = sprintf(_('%dd ago'), $interval->d);
  else if ($interval->h > 0) $r['ago'] = sprintf(_('%dh ago'), $interval->h);
  else if ($interval->i > 0) $r['ago'] = sprintf(_('%dm ago'), $interval->i);
  else if ($interval->s > 0) $r['ago'] = sprintf(_('%ds ago'), $interval->s);

  $feature = array(
    'type' => 'Feature',
    'id' => $r['id'],
    'properties' => $r,
    'geometry' => array(
      'type' => 'Point',
      'coordinates' => array(floatval($r['lng']), floatval($r['lat']))
    )
  );
  $json['features'][] = $feature;
}
$q->free();

$mysqli->close();

echo json_encode($json);
exit();