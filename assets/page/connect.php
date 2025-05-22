<?php
session_start();
require('../class/routeros_api.class.php');

$API = new RouterosAPI();

// Path ke file JSON
$mikrotikdata_path = "../json/mikrotikdata.json";
$sellerdata_path   = "../json/sellerdata.json";

// Baca dan decode data MikroTik
$mikrotikdata_json = file_get_contents($mikrotikdata_path);
$mikrotikdata = json_decode($mikrotikdata_json, true);
$mikrotikdata_main = $mikrotikdata[0];
$telebot = $mikrotikdata[1];

$mtip   = $mikrotikdata_main['mtip'] ?? '';
$mtuser = $mikrotikdata_main['mtuser'] ?? '';
$mtpass = $mikrotikdata_main['mtpass'] ?? '';
$dns    = $mikrotikdata_main['dns'] ?? '';

$teletoken = $telebot['teletoken'] ?? '';
$chatid    = $telebot['chatid'] ?? '';

// Baca dan decode semua seller
$sellerdata_json = file_get_contents($sellerdata_path);
$sellerdata = json_decode($sellerdata_json, true);

// Input dari form
$input_user = isset($_POST['mtUsername']) ? trim($_POST['mtUsername']) : '';
$input_pass = isset($_POST['mtPassword']) ? trim($_POST['mtPassword']) : '';

// Simpan session login awal
$_SESSION['sell'] = $input_user;

// Cek apakah input cocok dengan salah satu user di data seller
$found = false;
foreach ($sellerdata as $seller) {
    if ($input_user === $seller['sellername'] && password_verify($input_pass, $seller['sellerpasswd'])) {
        $found = true;

        $_SESSION['username']   = $seller['sellername'];
        $_SESSION['role']       = $seller['profile'];
        $_SESSION['seller_id']  = $seller['id'];

        // Jika admin, langsung masuk
        if ($seller['profile'] === 'admin') {
            header("Location: ../admin/home.php");
            exit();
        }

        // Jika seller, hubungkan ke Mikrotik dulu
        if (!$API->connect($mtip, $mtuser, $mtpass)) {
            header("Location: ../../index.php?error=2"); // Gagal konek mikrotik
            exit();
        }

        // Login seller sukses
        header("Location: dashboard.php");
        exit();
    }
}

// Jika tidak ditemukan
if (!$found) {
    header("Location: ../../index.php?error=1");
    exit();
}
?>
