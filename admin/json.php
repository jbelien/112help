<?php
header('Content-Type: application/json; charset=utf-8');

$ini = parse_ini_file('../settings.ini', TRUE);

$json = array();
$json['type'] = 'FeatureCollection';
$json['features'] = array();

session_start(); if (!isset($_SESSION['user'])) { echo json_encode($json); exit(); }

$mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
$mysqli->set_charset('utf8');

$qsz  = "SELECT `id`, `indanger`, `urgence`, `accuracy`, X(`position`) AS `lng`, Y(`position`) AS `lat` FROM `help`";
if (isset($_REQUEST['relative']) && $_REQUEST['relative'] > 0) { $qsz .= " WHERE `datetime` >= '".date('Y-m-d H:i:s', time()-intval($_REQUEST['relative']))."'"; }
$qsz .= " ORDER BY `datetime` DESC";

$q = $mysqli->query($qsz) or trigger_error($mysqli->error);
while ($r = $q->fetch_assoc()) {
  $feature = array(
    'type' => 'Feature',
    'id' => $r['id'],
    'properties' => array_map('utf8_encode', $r),
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