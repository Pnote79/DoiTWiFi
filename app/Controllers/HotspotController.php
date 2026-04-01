<?php
/**
 * Project: Doitwifi Management System
 * Created by: Gemini AI
 * Date: 16 Maret 2026
 * Description: Controller untuk mengelola Profile Hotspot MikroTik, 
 * sinkronisasi tarif JSON, dan otomatisasi Dynamic Queue.
 */

require_once __DIR__ . '/../Libraries/routeros_api.class.php';

class HotspotController {
    // Path untuk menyimpan data tarif dan margin tambahan
    private $rateFile = __DIR__ . "/../../storage/rate.json";

    /**
     * Mengambil instance API MikroTik dan melakukan koneksi
     * Fungsi ini memastikan koneksi selalu tersedia untuk method lainnya.
     */
private function get_api() {
	 

    if (!isset($_SESSION['router_ip']))
    // 1. Cek Session atau Load JSON
    if (!isset($_SESSION['router_ip'])) {
        $path = __DIR__ . '/../../storage/mikrotikdata.json';
        
        if (!file_exists($path)) {
            die("Error: File mikrotikdata.json tidak ditemukan di: " . $path);
        }

        $mtData = json_decode(file_get_contents($path), true);
        $mt = $mtData[0] ?? null;

        if ($mt) {
            $_SESSION['router_ip']   = $mt['mtip'];
            $_SESSION['router_user'] = $mt['mtuser'];
            $_SESSION['router_pass'] = $mt['mtpass'];
        } else {
            die("Error: Data di dalam mikrotikdata.json kosong atau format salah.");
        }
    }

    // 2. Mencoba Koneksi
    $API = new \RouterosAPI();
    $API->debug = false; 
    
    // Tambahkan timeout agar tidak loading selamanya
    $API->timeout = 5; 

    if ($API->connect($_SESSION['router_ip'], $_SESSION['router_user'], $_SESSION['router_pass'])) {
        return $API;
    }

    // Jika sampai sini berarti gagal konek ke IP
    die("Gagal Koneksi API ke IP: " . $_SESSION['router_user'] . ". Pastikan API di MikroTik (Port 8728) sudah Aktif.");
}
public function active() {
    $API = $this->get_api();
    if (!$API) die("Koneksi MikroTik Gagal");

    // Jika ada permintaan hapus user aktif
    if (isset($_GET['remove_active'])) {
        $API->comm("/ip/hotspot/active/remove", [".id" => $_GET['remove_active']]);
        $_SESSION['msg'] = "User berhasil diputus!";
        header("Location: " . BASE_URL . "/hotspot/active");
        exit;
    }

    // 1. Ambil data utama
    $mt_hotspotUserActive = $API->comm("/ip/hotspot/active/print");
    $get_users = $API->comm("/ip/hotspot/user/print");
    $get_leases = $API->comm("/ip/dhcp-server/lease/print");
    
    // 2. Ambil data dari Hotspot Script (Biasanya di simpan di scheduler/script oleh sistem voucher)
    // Kita asumsikan data login tersimpan di comment user atau di script list
    $get_scripts = $API->comm("/system/script/print");

    // Mapping Profile
    $user_map = [];
    foreach ($get_users as $u) {
        $user_map[$u['name']] = $u['profile'] ?? 'default';
    }

    // Mapping Hostname
    $lease_map = [];
    foreach ($get_leases as $l) {
        if (isset($l['mac-address'])) {
            $lease_map[$l['mac-address']] = $l['host-name'] ?? 'Unknown Device';
        }
    }

    // Mapping Login Date dari Script/Scheduler
    // Format target: [2025-12-20-|-11:58:47]
    $script_map = [];
    foreach ($get_scripts as $s) {
        if (strpos($s['name'], '-|-') !== false) {
            $part = explode("-|-", $s['name']);
            // Kita cari username di dalam string script (biasanya ada di bagian tengah)
            // Contoh mapping jika name script mengandung username
            foreach ($mt_hotspotUserActive as $act) {
                if (strpos($s['name'], $act['user']) !== false) {
                    $date = $part[0]; // 2025-12-20
                    $time = $part[1]; // 11:58:47
                    $script_map[$act['user']] = "$date | $time";
                }
            }
        }
    }

    $API->disconnect();
    include __DIR__ . '/../Views/admin/hotspot/active.php';
} 


  /**
     * Halaman Utama Profile Hotspot
     * Menangani tampilan daftar profil dan delegasi proses CRUD.
     */
    public function profile() {
        $API = $this->get_api();
        if (!$API) die("Koneksi MikroTik Gagal");

        // Menangani aksi simpan (Update/Add) via POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
            $this->save_profile();
        }
        
        // Menangani aksi hapus profil via GET
        if (isset($_GET['del']) && isset($_GET['name'])) {
            $this->delete_profile($API);
        }

        // Penyiapan data untuk View
        $getprofile = $API->comm("/ip/hotspot/user/profile/print");
        $getpool    = $API->comm("/ip/pool/print");
        $rateData   = file_exists($this->rateFile) ? json_decode(file_get_contents($this->rateFile), true) : [];
        
        $API->disconnect();
        include __DIR__ . '/../Views/admin/hotspot/profile.php';
    }

    /**
     * Proses Simpan/Edit Profile
     * Mengolah data form, mengekstrak variabel Simple Queue, dan injeksi script MikroTik.
     */
    public function save_profile() {
        $lib_path  = __DIR__ . '/../Libraries/routeros_api.class.php';
        $json_path = __DIR__ . '/../../storage/mikrotikdata.json';
        
        if (!file_exists($lib_path)) die("Library RouterOS API tidak ditemukan!");

        require_once $lib_path;
        $mikro_data = json_decode(file_get_contents($json_path), true);
        $mt_config  = $mikro_data[0] ?? null;

        $API = new RouterosAPI();
        if (!$API->connect($mt_config['mtip'], $mt_config['mtuser'], $mt_config['mtpass'])) {
            $_SESSION['msg'] = "Gagal terhubung ke MikroTik!";
            header("Location: " . BASE_URL . "/hotspot/profile");
            exit;
        }

        $currentData = file_exists($this->rateFile) ? json_decode(file_get_contents($this->rateFile), true) : [];
        
        // 1. Ambil & Sanitasi Input Form
        $p_id       = $_POST['p_id'] ?? ''; 
        $p_name     = strtoupper($_POST['p_name']); 
        $p_profile  = preg_replace('/\s+/', '-', $_POST['p_profile']); 
        $p_price    = $_POST['p_price'] ?? "0";
        $p_margin   = $_POST['p_margin'] ?? "0"; 
        $p_limitb   = $_POST['p_limitb'] ?? "0"; 
        $p_limit    = $_POST['p_limit'] ?? ""; 
        $p_shared   = $_POST['p_shared'] ?? "1";
        $p_length   = $_POST['p_length'] ?? "8"; 
        $p_validity = $_POST['p_validity'] ?? "0";
        $expmode    = $_POST['expmode'] ?? "rem";
        $addrpool   = $_POST['ppool'] ?? "none";
        $parent     = $_POST['parent'] ?? "none";
        $getlock    = $_POST['lockunlock'] ?? "Disable";

        // 2. Ekstraksi String p_simple untuk Dynamic Queue (Burst Mode)
        $p_simple = $_POST['p_simple'] ?? ""; 
        if (!empty($p_simple)) {
            $parts = explode(" ", $p_simple);
            $max_limit       = $parts[0] ?? "0/0";
            $burst_limit     = $parts[1] ?? "0/0";
            $burst_threshold = $parts[2] ?? "0/0";
            $burst_time      = $parts[3] ?? "0/0";
            $priority        = $parts[4] ?? "8";
            $limit_at        = $parts[5] ?? "0/0";
        } else {
            $max_limit = $burst_limit = $burst_threshold = $burst_time = $limit_at = "0/0";
            $priority = "8";
        }

        // 3. Bangun Script On-Login (Lock MAC + Simple Queue)
        $lock = ($getlock == "Enable") 
                ? '; [:local mac $"mac-address"; /ip hotspot user set mac-address=$mac [find where name=$user]]' 
                : "";

        $simple_onlogin = ' :local datetime [/system clock get date]; :local timedate [/system clock get time]; :if ([/queue simple find where name=$user] != "") do={ /queue simple remove [find where name=$user]; :log warning ("Queue lama $user dihapus $datetime $timedate"); }; /queue simple add name=$user comment=("[".$interface."]") target=$address burst-limit='.$burst_limit.' burst-threshold='.$burst_threshold.' burst-time='.$burst_time.' max-limit='.$max_limit.' limit-at='.$limit_at.' priority='.$priority.' parent="'.$parent.'";';

        $onlogin = ':put (",'.$expmode.',' . $p_price . ',' . $p_validity . ','.$p_margin.',,' . $getlock . ',"); {:local comment [ /ip hotspot user get [/ip hotspot user find where name="$user"] comment]; :local ucode [:pic $comment 0 2]; :if ($ucode = "vc" or $ucode = "up" or $comment = "") do={ :local date [ /system clock get date ];:local year [ :pick $date 0 4 ];:local month [ :pick $date 5 7 ]; /sys sch add name="$user" disable=no start-date=$date interval="' . $p_validity . '"; :delay 5s; :local exp [ /sys sch get [ /sys sch find where name="$user" ] next-run]; :local getxp [len $exp]; :if ($getxp = 15) do={ :local d [:pic $exp 0 6]; :local t [:pic $exp 7 16]; :local s ("/"); :local exp ("$d$s$year $t"); /ip hotspot user set comment="$exp" [find where name="$user"];}; :if ($getxp = 8) do={ /ip hotspot user set comment="$date $exp" [find where name="$user"];}; :if ($getxp > 15) do={ /ip hotspot user set comment="$exp" [find where name="$user"];};:delay 5s; /sys sch remove [find where name="$user"]' . $simple_onlogin;
        
        $mode = ($expmode == "rem" || $expmode == "remc") ? "remove" : "set limit-uptime=1s";
        $onlogin .= $lock . "}}";

        // 4. Bangun Script On-Logout (Bersihkan Queue)
        $onlogout = ':local datetime [/system clock get date]; :local timedate [/system clock get time]; /queue simple remove [find name="$user"]; :log warning ("--> [ $user ] LogOut at: [ $timedate - $datetime ] Di: [ $interface ]");';

        // 5. Bangun Script Monitoring Scheduler (Cek User Expired)
        $bgservice = ':local dateint do={:local montharray ( "01","02","03","04","05","06","07","08","09","10","11","12" );:local days [ :pick $d 8 10 ];:local month [ :pick $d 5 7 ];:local year [ :pick $d 0 4 ];:local monthint ([ :find $montharray $month]);:local month ($monthint + 1);:if ( [len $month] = 1) do={:local zero ("0");:return [:tonum ("$year$zero$month$days")];} else={:return [:tonum ("$year$month$days")];}}; :local timeint do={ :local hours [ :pick $t 0 2 ]; :local minutes [ :pick $t 3 5 ]; :return ($hours * 60 + $minutes) ; }; :local date [ /system clock get date ]; :local time [ /system clock get time ]; :local today [$dateint d=$date] ; :local curtime [$timeint t=$time] ; :foreach i in [ /ip hotspot user find where profile="'.$p_profile.'" ] do={ :local comment [ /ip hotspot user get $i comment]; :local name [ /ip hotspot user get $i name]; :local gettime [:pic $comment 11 19]; :if ([:pic $comment 4] = "-" and [:pic $comment 7] = "-") do={:local expd [$dateint d=$comment] ; :local expt [$timeint t=$gettime] ; :if (($expd < $today and $expt < $curtime) or ($expd < $today and $expt > $curtime) or ($expd = $today and $expt < $curtime)) do={ [ /ip hotspot user '.$mode.' $i ]; [ /ip hotspot active remove [find where user=$name] ];}}}';

        // 6. Siapkan Parameter Akhir untuk MikroTik
        $profile_params = [
            "name"               => (string)$p_profile,
            "shared-users"       => (string)$p_shared,
            "status-autorefresh" => "1m",
            "on-login"           => (string)$onlogin,
            "on-logout"          => (string)$onlogout
        ];

        if (!empty($addrpool) && $addrpool !== "none") { $profile_params["address-pool"] = $addrpool; }
        if (!empty($p_limit)) { $profile_params["rate-limit"] = $p_limit; }

        // 7. Eksekusi ke MikroTik & Database Lokal
        if (empty($p_id)) {
            // PROSES TAMBAH (ADD)
            $response = $API->comm("/ip/hotspot/user/profile/add", $profile_params);
            if (isset($response['!trap'])) {
                $_SESSION['msg'] = "Gagal: " . $response['!trap'][0]['message'];
                header("Location: " . BASE_URL . "/hotspot/profile"); exit;
            }

            if ($expmode != "0") {
                $API->comm("/system/scheduler/add", [
                    "name" => "MON-" . $p_profile,
                    "start-time" => "0".rand(1,5).":".rand(10,59).":".rand(10,59),
                    "interval" => "00:02:".rand(10,59),
                    "on-event" => $bgservice
                ]);
            }
            
            $newId = count($currentData) > 0 ? max(array_column($currentData, 'id')) + 1 : 1;
            $currentData[] = [
                "id" => $newId, "name" => $p_name, "amount" => $p_price, "margine" => $p_margin, 
                "limitbytes" => $p_limitb, "length" => $p_length, "profile" => $p_profile, 
                "validity" => $p_validity, "p_simple" => $p_simple
            ];
            $_SESSION['msg'] = "Profile $p_name berhasil dibuat!";
        } else {
            // PROSES EDIT (SET)
            $target = $API->comm("/ip/hotspot/user/profile/print", ["?name" => $p_profile]);
            if (!empty($target)) {
                $profile_params[".id"] = $target[0]['.id'];
                $API->comm("/ip/hotspot/user/profile/set", $profile_params);
            }
            
            // Update Scheduler
            $get_sch = $API->comm("/system/scheduler/print", ["?name" => "MON-" . $p_profile]);
            if ($expmode != "0" && !empty($get_sch)) {
                $API->comm("/system/scheduler/set", [".id" => $get_sch[0]['.id'], "on-event" => $bgservice]);
            }

            foreach ($currentData as &$item) {
                if ($item['id'] == $p_id) {
                    $item['name'] = $p_name; $item['amount'] = $p_price; $item['margine'] = $p_margin;
                    $item['limitbytes'] = $p_limitb; $item['profile'] = $p_profile; 
                    $item['length'] = $p_length; $item['validity'] = $p_validity;
                    $item['p_simple'] = $p_simple;
                }
            }
            $_SESSION['msg'] = "Profile $p_name diperbarui!";
        }

        file_put_contents($this->rateFile, json_encode(array_values($currentData), JSON_PRETTY_PRINT));
        $API->disconnect();
        header("Location: " . BASE_URL . "/hotspot/profile");
        exit();
    }

    /**
     * Menghapus Profile Hotspot
     * Membersihkan data di MikroTik, Scheduler Monitoring, dan JSON storage.
     */
    private function delete_profile($API) {
        $id_mt = $_GET['del']; 
        $name_profile = $_GET['name'];

        $API->comm("/ip/hotspot/user/profile/remove", [".id" => $id_mt]);
        
        $get_sch = $API->comm("/system/scheduler/print", ["?name" => "MON-" . $name_profile]);
        if(!empty($get_sch)) { 
            $API->comm("/system/scheduler/remove", [".id" => $get_sch[0]['.id']]); 
        }

        if (file_exists($this->rateFile)) {
            $currentJson = json_decode(file_get_contents($this->rateFile), true);
            $newJson = array_filter($currentJson, function($item) use ($name_profile) {
                return $item['profile'] !== $name_profile;
            });
            file_put_contents($this->rateFile, json_encode(array_values($newJson), JSON_PRETTY_PRINT));
        }

        $_SESSION['msg'] = "Profile $name_profile dihapus!";
        header("Location: " . BASE_URL . "/hotspot/profile");
        exit();
    }
	private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
	
/**
     * Manajemen Daftar User Hotspot (Voucher)
     * Fitur: Search, Filter Profile/Seller, Pagination 10 baris, Toggle Status, dan Delete.
     */
public function user() {
    $API = $this->get_api();
    if (!$API) die("Koneksi MikroTik Gagal");

    // --- 1. HANDLE AKSI API ---
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $id = $_GET['id'];
        switch ($_GET['action']) {
            case 'unmac': $API->comm("/ip/hotspot/user/set", [".id" => $id, "mac-address" => "00:00:00:00:00:00"]); break;
            case 'reset': $API->comm("/ip/hotspot/user/reset-counters", [".id" => $id]); break;
            case 'delete': $API->comm("/ip/hotspot/user/remove", [".id" => $id]); break;
            case 'disable': $API->comm("/ip/hotspot/user/set", [".id" => $id, "disabled" => "yes"]); break;
            case 'enable': $API->comm("/ip/hotspot/user/set", [".id" => $id, "disabled" => "no"]); break;
        }
        header("Location: " . BASE_URL . "/hotspot/user");
        exit;
    }

    // --- 2. AMBIL DATA DARI MIKROTIK ---
    $allUsers     = $API->comm("/ip/hotspot/user/print");
    $allScripts   = $API->comm("/system/script/print");
    $allLeases    = $API->comm("/ip/dhcp-server/lease/print");
    $allAddresses = $API->comm("/ip/address/print"); 
    
    // --- TAMBAHKAN BARIS INI AGAR PILIHAN PROFILE MUNCUL ---
    $getProfile   = $API->comm("/ip/hotspot/user/profile/print"); 
    
    // Load Seller Data
    $json_path = __DIR__ . '/../../storage/sellerdata.json';
    $uniqueSellers = file_exists($json_path) ? array_column(json_decode(file_get_contents($json_path), true) ?? [], 'name') : [];

    $historyVoucher = [];

    foreach ($allUsers as $u) {
        $username = $u['name'];
        $comment  = $u['comment'] ?? '';
        
        // Default Values
        $seller       = 'System';
        $status       = ($u['disabled'] == 'true') ? 'Disabled' : 'Ready';
        $expDate      = 'Belum Aktif';
        $generateDate = '-';
        $loginDate    = 'Never';
        $loginVia     = '-'; 
        $mac          = trim(strtoupper($u['mac-address'] ?? '00:00:00:00:00:00'));
        $ipAddress    = '-';
        $hostname     = 'Unknown Device';

        // --- A. LOGIKA PARSING COMMENT ---
        if (!empty($comment)) {
            if (strpos($comment, 'vc-') !== false) {
                $commentParts = explode('|', $comment);
                $seller       = $commentParts[1] ?? 'System';
                $generateDate = $commentParts[3] ?? '-';
            } else {
                $status  = ($u['disabled'] == 'true') ? 'Disabled' : 'Aktip';
                $expDate = explode('|', $comment)[0];
            }
        }

        // --- B. CARI LOG DI SYSTEM SCRIPT ---
        foreach ($allScripts as $scr) {
            if (strpos($scr['name'], "-|-$username-|-") !== false) {
                $scrParts = explode('-|-', $scr['name']);
                $loginDate = ($scrParts[0] ?? '') . " " . ($scrParts[1] ?? '');
                $ipAddress = $scrParts[4] ?? $ipAddress;

                if ($mac == '00:00:00:00:00:00' || empty($mac)) {
                    $mac = trim(strtoupper($scrParts[5] ?? '00:00:00:00:00:00'));
                }

                if (isset($scrParts[8]) && strpos($scrParts[8], '|') !== false) {
                    $sellerData   = explode('|', $scrParts[8]);
                    $seller       = $sellerData[1] ?? $seller;
                    $generateDate = $sellerData[3] ?? $generateDate;
                }
                break;
            }
        }

        // --- C. LOOKUP HOSTNAME & IP DARI LEASE ---
        if ($mac != '00:00:00:00:00:00') {
            foreach ($allLeases as $lease) {
                if (trim(strtoupper($lease['mac-address'] ?? '')) == $mac) {
                    $hostname  = $lease['host-name'] ?? 'Generic Device';
                    $ipAddress = ($ipAddress == '-') ? ($lease['address'] ?? '-') : $ipAddress;
                    break;
                }
            }
        }

        // --- D. CARI INTERFACE BERDASARKAN IP ADDRESS ---
        if ($ipAddress != '-') {
            foreach ($allAddresses as $addr) {
                $network = $addr['network'] ?? '';
                if ($network != '') {
                    $ipPrefix = substr($ipAddress, 0, strrpos($ipAddress, '.'));
                    $netPrefix = substr($network, 0, strrpos($network, '.'));
                    if ($ipPrefix === $netPrefix) {
                        $loginVia = $addr['interface'];
                        break;
                    }
                }
            }
        }

        // --- E. HITUNG KUOTA ---
        $limit = (float)($u['limit-bytes-total'] ?? 0);
        $used  = (float)($u['bytes-in'] ?? 0) + (float)($u['bytes-out'] ?? 0);
        $sisa  = $limit > 0 ? $this->formatBytes($limit - $used) : "Unlimited";

        $historyVoucher[] = [
            'id'       => $u['.id'],
            'name'     => $username,
            'profile'  => $u['profile'] ?? 'default',
            'seller'   => $seller,
            'status'   => $status,
            'exp'      => $expDate,
            'generate' => $generateDate,
            'login'    => $loginDate,
            'via'      => $loginVia,
            'mac'      => $mac,
            'ip'       => $ipAddress,
            'hostname' => $hostname,
            'uptime'   => $u['uptime'] ?? '0s',
            'sisa'     => $sisa
        ];

        if (!in_array($seller, $uniqueSellers) && $seller !== 'System') {
            $uniqueSellers[] = $seller;
        }
    }

    $API->disconnect();
    include __DIR__ . '/../Views/admin/hotspot/user.php';
}

}