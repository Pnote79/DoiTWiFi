<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $filePath = '../json/mikrotikdata.json';

    function safe_input($data) {
        return trim(strip_tags($data));
    }

    // Ambil data lama
    $existing_data = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];

    // Pastikan format array tetap
    if (!is_array($existing_data)) $existing_data = [];
    if (!isset($existing_data[0])) $existing_data[0] = [];
    if (!isset($existing_data[1])) $existing_data[1] = [];

    // Jika input Mikrotik dikirim, update index 0
    if (!empty($_POST['ipmik']) && !empty($_POST['usermik']) && !empty($_POST['passmik']) && !empty($_POST['hotmik']) && !empty($_POST['dnsmik'])) {
        if (!filter_var($_POST['ipmik'], FILTER_VALIDATE_IP)) {
            die("❌ IP Mikrotik tidak valid.");
        }

        $existing_data[0] = [
            "mtip"   => safe_input($_POST['ipmik']),
            "mtuser" => safe_input($_POST['usermik']),
            "mtpass" => safe_input($_POST['passmik']),
            "dns"    => safe_input($_POST['hotmik']),
            "mtdns"  => safe_input($_POST['dnsmik'])
        ];
    }

    // Jika input Telegram dikirim, update index 1
    if (!empty($_POST['teletoken']) && !empty($_POST['chat_id'])) {
        $existing_data[1] = [
            "teletoken" => safe_input($_POST['teletoken']),
            "chatid"    => safe_input($_POST['chat_id'])
        ];
    }

    // Simpan ulang semua data
    if (file_put_contents($filePath, json_encode($existing_data, JSON_PRETTY_PRINT))) {
        header('Location: ../admin/admin.php?status=saved');
        exit;
    } else {
        echo "❌ Gagal menyimpan data ke file.";
    }
} else {
    echo "🚫 Akses tidak sah.";
}
?>
