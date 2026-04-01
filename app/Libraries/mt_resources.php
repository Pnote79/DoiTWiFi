<?php
// Gunakan Output Buffering untuk menahan output agar header() tidak error
ob_start();

require_once("routeros_api.class.php");
$API = new RouterosAPI();

// Matikan display error untuk mencegah teks error merusak header redirect
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'];
$role     = $_SESSION['role'];

// --- LOAD DATA JSON ---
$local_file = @file_get_contents("../json/LocalStorage.json");
$selldata = json_decode($local_file, true);

$mikrotikdata_path = "../json/mikrotikdata.json";
$sellerdata_path   = "../json/sellerdata.json";

$mikrotikdata_json = @file_get_contents($mikrotikdata_path);
$mikrotikdata = json_decode($mikrotikdata_json, true);
$mikrotikdata_main = $mikrotikdata[0] ?? [];
$telebot = $mikrotikdata[1] ?? [];

$mtip   = $mikrotikdata_main['mtip'] ?? '';
$mtuser = $mikrotikdata_main['mtuser'] ?? '';
$mtpass = $mikrotikdata_main['mtpass'] ?? '';
$dns    = $mikrotikdata_main['dns'] ?? '';
$parts  = explode(' ', $dns, 2);
$dn     = $parts[0] ?? '';
$ns     = $parts[1] ?? '';

$teletoken = $telebot['teletoken'] ?? '';
$chatid    = $telebot['chatid'] ?? '';

// --- LOAD SELLER DATA ---
$sellerdata_json = @file_get_contents($sellerdata_path);
$sellerdata = json_decode($sellerdata_json, true) ?? [];
foreach ($sellerdata as $data) {
    if ($role === 'admin' && ($data['profile'] ?? '') === 'admin') {
        $adminname = $data['sellername'];
        $adminpass = $data['sellerpasswd'];
    } elseif ($role === 'user' && ($data['profile'] ?? '') === 'seller' && $data['sellername'] === $username) {
        $sellername    = $data['sellername'];
        $sellerpass    = $data['sellerpasswd'];
        $sellerphone   = $data['sellerphone'];
        $sellerdeposit = $data['sellerdeposit'];
        $sellerbalance = $data['sellerbalance'];
    }
}

// --- FUNGSI HELPER (Ditaruh di luar loop) ---
function formatUptime($uptime) {
    $uptime = str_replace(' ', '', $uptime);
    $weeks = $days = $hours = $minutes = 0;
    if (preg_match('/(\d+)w/', $uptime, $w)) { $weeks = (int)$w[1]; }
    if (preg_match('/(\d+)d/', $uptime, $d)) { $days = (int)$d[1]; }
    if (preg_match('/(\d+)h/', $uptime, $h)) { $hours = (int)$h[1]; }
    if (preg_match('/(\d+)m/', $uptime, $m)) { $minutes = (int)$m[1]; }
    $totalDays = ($weeks * 7) + $days;
    return sprintf('%d Hari : %02d Jam : %02d Menit', $totalDays, $hours, $minutes);
}

// --- KONEKSI MIKROTIK ---
if ($API->connect($mtip, $mtuser, $mtpass)) {
    // Resource
    $mt_resources = $API->comm("/system/resource/print");
    if (!empty($mt_resources)) {
        $res = $mt_resources[0];
        $identity  = $res['identity'] ?? 'NA';
        $board     = $res['board-name'] ?? 'N/A';
        $cpu       = $res['cpu'] ?? 'N/A';
        $cpu_load  = $res['cpu-load'] ?? '0';
        $total_ram = round(($res['total-memory'] ?? 0) / 1024 / 1024, 2);
        $free_ram  = round(($res['free-memory'] ?? 0) / 1024 / 1024, 2);
        $formattedUptime = formatUptime($res['uptime'] ?? '0m');
    }

    // Hotspot Profiles
    $mt_hotprofile = $API->comm("/ip/hotspot/user/profile/print");
    $defaultProfile = $mt_hotprofile[0]['name'] ?? '';

    // Count Users
    $mt_hotspotUser = $API->comm("/ip/hotspot/user/print");
    $userCount = 0;
    if (is_array($mt_hotspotUser)) {
        foreach ($mt_hotspotUser as $hsUser) {
            $commentLines = explode("|", ($hsUser['comment'] ?? ''));
            $userseller = trim($commentLines[1] ?? '');
            if ($role === 'admin' || ($_SESSION['sell'] ?? '') === $userseller) {
                $userCount++;
            }
        }
    }

    // Active Sessions
    $userActive = count($API->comm("/ip/hotspot/active/print") ?? []);
    $ppoeActive = count($API->comm("/ppp/active/print") ?? []);

    // Netwatch
    $netwatch = $API->comm('/tool/netwatch/print') ?? [];
    $jumlah_up = 0; $jumlah_down = 0;
    foreach ($netwatch as $item) {
        if (($item['status'] ?? '') == 'up') { $jumlah_up++; } 
        else { $jumlah_down++; }
    }

    $mt_ipLease = $API->comm('/ip/dhcp-server/lease/print');
    $mt_scheduler = $API->comm('/system/scheduler/print');
} else {
    // Jika gagal konek, definisikan variabel default agar tidak error di UI
    $identity = $board = $cpu = $formattedUptime = "OFFLINE";
    $userCount = $userActive = $ppoeActive = 0;
}

// Hapus penutup tag PHP jika file ini hanya berisi kode PHP 
// untuk menghindari pengiriman whitespace tak sengaja.