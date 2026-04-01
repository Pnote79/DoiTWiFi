<?php
// app/Controllers/DashboardController.php

class DashboardController {

    // Menampilkan halaman Admin Setting (index.php)
    public function index() {
        $base_path = dirname(__DIR__, 2);
        $mikrotikdata_path = $base_path . "/storage/mikrotikdata.json";
        $sellerdata_path   = $base_path . "/storage/sellerdata.json";

        // Load Config MikroTik
        $mikrotikdata = json_decode(@file_get_contents($mikrotikdata_path), true) ?? [[], []];
        $mt_main = $mikrotikdata[0] ?? [];
        $telebot = $mikrotikdata[1] ?? [];

        // Load Admin Name
        $sellerdata = json_decode(@file_get_contents($sellerdata_path), true) ?? [];
        $adminname = "";
        foreach ($sellerdata as $data) {
            if (($data['profile'] ?? '') === 'admin') {
                $adminname = $data['sellername'];
                break;
            }
        }

        // Variabel untuk digunakan di view
        $data = [
            'mt' => $mt_main,
            'tele' => $telebot,
            'adminname' => $adminname,
            'status' => $_GET['status'] ?? null
        ];

        include __DIR__ . '/../Views/admin/dashboard/index.php';
    }

    // Menampilkan halaman Dashboard Monitoring (home.php)
 // app/Controllers/DashboardController.php

public function home() {
	ob_start();
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    
    require_once __DIR__ . '/../Libraries/routeros_api.class.php';
    $base_path = dirname(__DIR__, 2);
    $json_path = $base_path . "/storage/mikrotikdata.json";
    
    $mt_config = json_decode(@file_get_contents($json_path), true)[0] ?? [];
    
    $API = new RouterosAPI();
    $isConnected = $API->connect($mt_config['mtip'], $mt_config['mtuser'], $mt_config['mtpass']);

    if (!$isConnected) {
        header("Location: " . BASE_URL . "/admin-setting?error=connection_failed");
        exit;
    }

    // 1. Ambil DAFTAR INTERFACE secara dinamis
    $getInterfaces = $API->comm("/interface/print", [
        ".proplist" => "name",
        // "?type" => "!vlan" // Hapus filter ini jika ingin vlan juga muncul
    ]);

    // 2. Logic Statistik (Active Users, etc)
    $stats = [
        'userActive' => count($API->comm("/ip/hotspot/active/print")),
        'userCount'  => count($API->comm("/ip/hotspot/user/print")),
        'ppoeActive' => count($API->comm("/ppp/active/print")),
        'netwatchUp' => count($API->comm("/tool/netwatch/print", ["?status" => "up"])),
        'sumDaily'   => 0,
        'sumMonth'   => 0,
        'iface'      => $_GET['iface'] ?? ($_SESSION['iface'] ?? 'vlan1-INTERNET'),
        'all_ifaces' => $getInterfaces // Simpan daftar interface di sini
    ];
    
    $_SESSION['iface'] = $stats['iface'];

    // ... (Logic Mikhmon Style Anda tetap sama) ...
    // [Bagian penghitungan income tetap di sini]

    $API->disconnect();

    // Kirim $stats ke view
    include __DIR__ . '/../Views/admin/dashboard/home.php';
    $content = ob_get_clean();
    include __DIR__ . '/../Views/layouts/layout.php';
}

public function about() {

    // Tangkap isi view
    ob_start();
    include __DIR__ . '/../Views/admin/dashboard/about.php';
    $content = ob_get_clean();

    // Kirim ke layout
    include __DIR__ . '/../Views/layouts/layout.php';
}
}