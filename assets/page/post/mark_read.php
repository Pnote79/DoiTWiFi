<?php
session_start();
if (!isset($_SESSION['sell'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

$notifPath = "../json/topup_log.json";
$sellerId = $_SESSION['sell'];

if (!file_exists($notifPath)) {
    echo "File not found";
    exit;
}

$notifications = json_decode(file_get_contents($notifPath), true);
if (!is_array($notifications)) {
    echo "Invalid JSON";
    exit;
}

// Tandai notifikasi seller sebagai "read"
foreach ($notifications as &$notif) {
    if ($notif['sellername'] == $sellerId && !$notif['read']) {
        $notif['read'] = true;
    }
}

file_put_contents($notifPath, json_encode($notifications, JSON_PRETTY_PRINT));
echo "Marked as read";
