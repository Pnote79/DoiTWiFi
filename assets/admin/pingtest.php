<?php
if (isset($_GET['ip'])) {
    $ip = $_GET['ip'];

    // Amankan input
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        echo "❌ IP tidak valid.";
        exit;
    }

    // Ping via shell (Linux & Windows kompatibel)
    $os = strtoupper(substr(PHP_OS, 0, 3));
    $cmd = ($os === 'WIN') ? "ping -n 2 $ip" : "ping -c 2 $ip";

    $output = shell_exec($cmd);
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
} else {
    echo "🚫 IP tidak diberikan.";
}
?>
