<?php
require_once("routeros_api.class.php");
$API = new RouterosAPI();
error_reporting(1);

session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'];
$role     = $_SESSION['role'];

//Baca LocalStrorage
$local_file = file_get_contents("../json/LocalStorage.json");
$selldata = json_decode($local_file, true);

// Path ke file JSON
$mikrotikdata_path = "../json/mikrotikdata.json";
$sellerdata_path   = "../json/sellerdata.json";

// Baca dan decode data MikroTik
$mikrotikdata_json = file_get_contents($mikrotikdata_path);
$mikrotikdata = json_decode($mikrotikdata_json, true);
$mikrotikdata_main = $mikrotikdata[0];
$telebot = $mikrotikdata[1];
$mtip   = isset($mikrotikdata_main['mtip']) ? $mikrotikdata_main['mtip'] : '';
$mtuser = isset($mikrotikdata_main['mtuser']) ? $mikrotikdata_main['mtuser'] : '';
$mtpass = isset($mikrotikdata_main['mtpass']) ? $mikrotikdata_main['mtpass'] : '';
$dns    = isset($mikrotikdata_main['dns']) ? $mikrotikdata_main['dns'] : '';
         $parts = explode(' ', $dns, 2);
$dn = $parts[0];
$ns = isset($parts[1]) ? $parts[1] : '';

$teletoken = isset($telebot['teletoken']) ? $telebot['teletoken'] : '';
$chatid    = isset($telebot['chatid']) ? $telebot['chatid'] : '';
// Baca dan decode semua seller
$sellerdata_json = file_get_contents($sellerdata_path);
$sellerdata = json_decode($sellerdata_json, true);
 foreach ($sellerdata as $data) {
    if ($role === 'admin' && $data['profile'] === 'admin') {
        $adminname = $data['sellername'];
        $adminpass = $data['sellerpasswd'];
    } elseif ($role === 'user' && $data['profile'] === 'seller' && $data['sellername'] === $username) {
        $sellername     = $data['sellername'];
        $sellerpass     = $data['sellerpasswd'];
        $sellerphone    = $data['sellerphone'];
        $sellerdeposit  = $data['sellerdeposit'];
        $sellerbalance  = $data['sellerbalance'];
    }
}


// ✅Terhubung Ke Mikrotik
$API->connect($mtip, $mtuser, $mtpass);
// ✅Informasi sistem: uptime, cpu, dsb
$mt_resources = $API->comm("/system/resource/print");
foreach($mt_resources as $res){
	    $identity  = $res['identity'] ?? 'NA';
	    $board     = $res['board-name'] ?? 'N/A';
        $cpu       = $res['cpu'] ?? 'N/A';
        $cpu_load  = $res['cpu-load'] ?? 'N/A';
        $total_ram = round(($res['total-memory'] ?? 0) / 1024 / 1024, 2); // MB
        $free_ram  = round(($res['free-memory'] ?? 0) / 1024 / 1024, 2);  // MB
function formatUptime($uptime) {
    $uptime = str_replace(' ', '', $uptime); // Hapus spasi jika ada
    $weeks = $days = $hours = $minutes = 0;

    if (preg_match('/(\d+)w/', $uptime, $w)) {
        $weeks = (int)$w[1];
    }
    if (preg_match('/(\d+)d/', $uptime, $d)) {
        $days = (int)$d[1];
    }
    if (preg_match('/(\d+)h/', $uptime, $h)) {
        $hours = (int)$h[1];
    }
    if (preg_match('/(\d+)m/', $uptime, $m)) {
        $minutes = (int)$m[1];
    }

    $totalDays = ($weeks * 7) + $days;

    return sprintf('%d Day : %02d Hour : %02d Minute', $totalDays, $hours, $minutes);
}


       $rawUptime = $res['uptime'] ?? '0m';
       $formattedUptime = formatUptime($rawUptime);
}
// ✅Hotspot profile
$mt_hotprofile = $API->comm("/ip/hotspot/user profile/print");

// Pastikan tidak kosong dulu
$defaultProfile = isset($mt_hotprofile[0]['name']) ? $mt_hotprofile[0]['name'] : '';

// ✅User Hotspot
$mt_hotspotUser = $API->comm("/ip/hotspot/user/print");
$userCount = 0;

if (is_array($mt_hotspotUser)) {
    foreach ($mt_hotspotUser as $index => $hsUser) {
        // Lewati baris pertama jika perlu
        if ($index === 0) continue;

        $commentLines = explode("|", $hsUser['comment']);
        $userseller = isset($commentLines[1]) ? trim($commentLines[1]) : '';

        if ($_SESSION['role'] === 'admin' || $_SESSION['sell'] === $userseller) {
            $userCount++;
        }
    }
}
//✅Aktip Hotspot
$mt_hotspotUserActive = json_encode($API->comm("/ip/hotspot/active/print"));
$mt_hotspotUserActive = json_decode($mt_hotspotUserActive, true);
$userActive = 0;
foreach($mt_hotspotUserActive as $hsUserActive){$userActive++;}
//✅Active PPOE
$mt_ppoeActive = $API->comm("/ppp/active/print");
$ppoeActive = count($mt_ppoeActive);
//✅UP/DOWN Client/Hotspot AP
$netwatch = json_decode(json_encode($API->comm('/tool/netwatch/print')), true);
$hosts_up = [];
$hosts_down = [];

foreach ($netwatch as $item) {
    if ($item['status'] == 'up') {
        $hosts_up[] = $item['host'];
    } elseif ($item['status'] == 'down') {
        $hosts_down[] = $item['host'];
    }
}
//✅ Hitung jumlah UP/DOWN Client/Hotspot AP
$jumlah_up = count($hosts_up);
$jumlah_down = count($hosts_down);



//🔍Script Mencari Data Nama/Pass Berdasarkan Comment
$mt_script = $API->comm("/system/script/print");
foreach ($mt_script as $datasell) {
	$commen = $datasell['comment'];
    $name = isset($datasell['name']) ? $datasell['name'] : '';
    $parts = explode('-|-', $name);
    $seller = isset($parts[0]) ? $parts[0] : '-';
    $pass = isset($parts[1]) ? $parts[1] : '-';
}

$identity = $API->comm("/system/identity/print")[0]['name'] ?? 'Unknown';
// DHCP Lease
$mt_ipLease = $API->comm('/ip/dhcp-server/lease/print');

// Scheduler
$mt_scheduler = $API->comm('/system/scheduler/print');



?>

