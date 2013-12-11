<?php

// JSONP handler for index.html retrieving the required information
// from p2pool host and returns a JSONP to the caller.

// $debug= true;
$debug= false;

// Defines the access to the p2pool

$p2pool= array(
  'http' => 'http', // can be http or https
  'host' => 'localhost',
  'port' => '9332',
);

// API url
$p2pool_url= $p2pool['http'].'://'.$p2pool['host'].":".$p2pool['port'];

// APIs
$p2pool_api= array(
//'name'                => 'path'
  'rate'                => 'rate',
  'fee'                 => 'fee',
  'difficulty'          => 'difficulty',
  'users'               => 'users',
  'user_stales'         => 'user_stales',
  'current_payouts'     => 'current_payouts',
  'local_stats'         => 'local_stats',
  'global_stats'        => 'global_stats',
  'recent_blocks'       => 'recent_blocks',
  'uptime'              => 'uptime',
  'currency_info'       => '/web/currency_info',
);

// ======================================================================

// return a result set as JSONP
function sendJSON($callback, $r) {
  echo sprintf('%s(%s);',
    $callback,
    json_encode($r, JSON_PRETTY_PRINT));
  exit(0);
}

function fetchJSON($path) {
  global $p2pool_url;
  return json_decode(
    file_get_contents(
      $p2pool_url.'/'.$path), true);
}

function getActiveMiners() {
  global $p2pool_api;
  $local_stats= fetchJSON($p2pool_api['local_stats']);
  $current_payouts= fetchJSON($p2pool_api['current_payouts']);

  // can someone please write a more descriptive p2pool JSON API
  // documentation? WTF does these values exaclty mean?
  // --------------------------------------------------------------------
  // $users= fetchJSON($p2pool_api['users']);
  // $user_stales= fetchJSON($p2pool_api['user_stales']);
  // --------------------------------------------------------------------

  $miner= array();
  foreach ($local_stats['miner_hash_rates'] as $key => $value) {
    $hash_rate= $local_stats['miner_hash_rates'][$key];
    $dead_hash_rate= $local_stats['miner_dead_hash_rates'][$key];

    $miner[$key]= array(
      'hashrate' => $hash_rate,
      'doa_hashrate' => $dead_hash_rate,
      'doa_prop' => ($dead_hash_rate / $hash_rate),
    );

    if(isset($current_payouts[$key])) {
      $miner[$key]['payout']= $current_payouts[$key];
    }
  }
  return $miner;
}

function getRecentBlocks() {
  global $p2pool_api;
  $recent_blocks= fetchJSON($p2pool_api['recent_blocks']);
  return $recent_blocks;
}

function getLocalStats() {
  global $p2pool_api;
  $local_stats= fetchJSON($p2pool_api['local_stats']);
  $share_diff= fetchJSON($p2pool_api['difficulty']);
  $fee= fetchJSON($p2pool_api['fee']);
  $rate= fetchJSON($p2pool_api['rate']);

  $local= array();
  // time to block in seconds
  $local['time_to_block']= $local_stats['attempts_to_block'] / $rate;
  $local['time_to_share']= $local_stats['attempts_to_share'] / $rate;
  return $local;
}

// ======================================================================

if($debug) {
  $_GET['callback']= 'foobar';
  $_GET['t']= 'local_stats';
}

// client specifies, what to fetch

if(isset($_GET['t'])) {
  switch($_GET['t']) {
    case 'local_stats':
      $local_stats= getLocalStats();
      sendJSON($_GET['callback'], $local_stats);
      break;
    case 'active_miners':
      $miner= getActiveMiners();
      sendJSON($_GET['callback'], $miner);
      break;
    case 'recent_blocks':
      $recent_blocks= getRecentBlocks();
      sendJSON($_GET['callback'], $recent_blocks);
      break;
    default:
      sendJSON($_GET['callback'],
        array('etx' => 'unknown type', 'ecd' => 1));
  }
}
else {
  // type undefined -- send an error
  sendJSON($_GET['callback'],
    array('etx' => 'type undefined', 'ecd' => 42));
}

// $rate= json_decode(file_get_contents($url . '/rate'), true);
// $local_stats= json_decode(file_get_contents($url . '/local_stats'), true);
// $users= json_decode(file_get_contents($url . '/users'), true);

// // $fee= json_decode(file_get_contents($url . '/fee'), true);
// // $current_payouts= json_decode(file_get_contents($url . '/current_payouts'), true);

// $global_stats= json_decode(file_get_contents($url . '/global_stats'), true);

// // $payout_addr= json_decode(file_get_contents($url . '/payout_addr'), true);
// $recent_blocks= json_decode(file_get_contents($url . '/recent_blocks'), true);

// $uptime= json_decode(file_get_contents($url . '/uptime'), true);




// $hashrate_graph_day= json_decode(
//     file_get_contents(
//         $url . '/web/graph_data/local_hash_rate/last_day'));
// $doa_graph_day= json_decode(
//     file_get_contents(
//         $url . '/web/graph_data/local_dead_hash_rate/last_day'));

// $result= array(
//   'rate' => $rate,
//   'local' => $local_stats,
//   'global' => $global_stats,
//   'users' => $users,
//   'recent_blocks' => $recent_blocks,
//   'graph1' => $hashrate_graph_day,
//   'graph2' => $doa_graph_day,
// );

// $json= json_encode($result);

?>
