<?php
header('Content-Type: application/json');
include("../class/mt_resources.php");

$logs = $API->comm("/log/print");
$result = [];

if ($logs && is_array($logs)) {
    $count = 0;
    foreach (array_reverse($logs) as $log) {
        if (isset($log['message']) && strpos($log['message'], '->') !== false) {
            $result[] = [
                'time' => $log['time'] ?? '-',
                'message' => $log['message']
            ];
            $count++;
        }
        if ($count >= 100) break; // batasi hanya 10 log terbaru
    }
}

echo json_encode($result);
$API->disconnect();
