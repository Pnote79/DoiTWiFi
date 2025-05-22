<?php
include("../class/mt_resources.php");

header('Content-Type: application/json');

echo json_encode([
    "identity" => $identity,
    "cpu_load" => $cpu_load,
    "cpu" => $cpu,
    "ram_total" => $total_ram,
    "ram_free" => $free_ram,
    "uptime" => $formattedUptime,
    "board" => $board,
    "version" => $mt_resources[0]['version'] ?? 'N/A'
]);
