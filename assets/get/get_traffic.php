<?php 
session_start();
include("../class/mt_resources.php");

// Ambil interface dari parameter GET atau default ke 'ether1'
$iface = isset($_GET['iface']) ? $_GET['iface'] : 'ether1';

// Ambil data traffic untuk interface yang dipilih
$get_traffic = $API->comm("/interface/monitor-traffic", [
    "interface" => $iface,
    "once" => ""
]);

header('Content-Type: application/json');

if (!empty($get_traffic)) {
    $tx = $get_traffic[0]['tx-bits-per-second'];
    $rx = $get_traffic[0]['rx-bits-per-second'];
    $time = date("H:i:s");

    echo json_encode([
        "time" => $time,
        "tx" => round($tx / 1000000, 2), // Mbps
        "rx" => round($rx / 1000000, 2)  // Mbps
    ]);
} else {
    echo json_encode(["error" => "No traffic data for interface: $iface"]);
}
