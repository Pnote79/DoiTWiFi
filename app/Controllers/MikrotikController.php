<?php
/**
 * =========================================================
 * Nama File    : MikrotikController.php
 * Project      : KWHotspot / DoITWiFi
 * Deskripsi    : Controller untuk manajemen PPPoE Secrets MikroTik
 * Fitur        : List User, Stats Card, Isolir, Disable, & Kick Session
 * =========================================================
 */

require_once __DIR__ . '/../Libraries/routeros_api.class.php';

class MikrotikController {

    /**
     * Helper untuk koneksi ke API MikroTik
     */
    private function get_api_connection() {
        $API = new \RouterosAPI();
        $ip   = $_SESSION['router_ip'] ?? '';
        $user = $_SESSION['router_user'] ?? '';
        $pass = $_SESSION['router_pass'] ?? '';

        if (empty($ip)) {
            $json_path = __DIR__ . '/../../storage/mikrotikdata.json';
            if (file_exists($json_path)) {
                $config = json_decode(file_get_contents($json_path), true);
                $ip   = $config[0]['mtip'] ?? '';
                $user = $config[0]['mtuser'] ?? '';
                $pass = $config[0]['mtpass'] ?? '';
            }
        }

        return $API->connect($ip, $user, $pass) ? $API : false;
    }

    /**
     * Halaman Utama PPPoE Monitoring
     */
public function pppoe() {
    $API = $this->get_api_connection();
    if (!$API) die("Gagal terhubung ke MikroTik.");

    // 1. Ambil Data Mentah dari MikroTik
    $secrets_raw  = $API->comm('/ppp/secret/print') ?? [];
    $active_list  = $API->comm('/ppp/active/print') ?? [];
    $address_list = $API->comm('/ip/firewall/address-list/print', ["?list" => "POOL-ISOLIR"]) ?? [];
    $profiles     = $API->comm('/ppp/profile/print') ?? [];

    // 2. Mapping Data untuk Efisiensi (O(1) lookup)
    
    // Mapping Isolir berdasarkan IP
    $isolir_map = [];
    foreach ($address_list as $al) {
        // 'false' pada disabled berarti rule isolir sedang AKTIF (user terblokir)
        $isolir_map[$al['address']] = $al['disabled'];
    }

    // Mapping IP Aktif berdasarkan Nama User (KUNCI UTAMA)
    $active_map = [];
    foreach ($active_list as $a) {
        // Simpan alamat IP asli yang didapat user saat terkoneksi
        $active_map[$a['name']] = $a['address']; 
    }

    // 3. Inisialisasi Statistik & Penampung Data
    $processed_secrets = [];
    $stats = [
        'total'       => 0, 
        'active'      => 0, 
        'non_active'  => 0, 
        'isolir'      => 0, 
        'belum_bayar' => 0
    ];

    // 4. Transformasi Data & Hitung Statistik
    foreach ($secrets_raw as $s) {
        $name    = $s['name'];
        $comment = $s['comment'] ?? '';
        
        // Cek apakah user sedang online
        $isActive = isset($active_map[$name]);

        // AMBIL IP: Prioritas IP dari tabel Active (Real-time), 
        // jika offline ambil dari tabel Secret (Remote Address)
        $ip = $isActive ? $active_map[$name] : ($s['remote-address'] ?? '');

        $isDisabled   = ($s['disabled'] === 'true');
        
        // Cek Isolir: Jika IP ada di address-list POOL-ISOLIR dan tidak di-disable
        $isIsolir     = (!empty($ip) && isset($isolir_map[$ip]) && $isolir_map[$ip] === 'false');
        
        // Cek Belum Bayar: Berdasarkan string khusus di comment (contoh format Anda: "| d")
        $isBelumBayar = (strpos($comment, '| d') !== false);

        // Update Statistik
        $stats['total']++;
        if ($isBelumBayar) $stats['belum_bayar']++;
        if ($isIsolir)     $stats['isolir']++;
        
        if ($isActive) {
            $stats['active']++;
        } else {
            $stats['non_active']++;
        }

        // Masukkan ke array untuk dikirim ke View
        $processed_secrets[] = [
            'id'           => $s['.id'],
            'name'         => $name,
            'profile'      => $s['profile'],
            'ip'           => $ip ?: '-', // Tampilkan strip jika tidak ada IP sama sekali
            'comment'      => $comment ?: '-',
            'isDisabled'   => $isDisabled,
            'isActive'     => $isActive,
            'isIsolir'     => $isIsolir,
            'isBelumBayar' => $isBelumBayar
        ];
    }

    // 5. Tutup Koneksi & Render View
    $API->disconnect();
    
    // Variabel yang akan digunakan di file pppoe.php: $secrets, $stats, $profiles
    $secrets = $processed_secrets;
    
    include __DIR__.'/../Views/admin/mikrotik/pppoe.php';
}
    /**
     * Update Status: Isolir, Disable, atau Aktifkan Kembali
     */
public function update_status() {
    date_default_timezone_set('Asia/Jakarta');
    $id   = $_GET['id'] ?? ''; 
    $act  = $_GET['act'] ?? ''; // isolir, disable, open
    $API  = $this->get_api_connection();
    $date = date('Y-m-d'); // Format ISO untuk konsistensi script

    if ($API && $id) {
        $u = $API->comm('/ppp/secret/print', ["?.id" => $id])[0] ?? null;
        if ($u) {
            $username = $u['name'];
            $ipBridge = $u['remote-address'] ?? ''; 
            
            // Pemetaan Status sesuai permintaan
            // act: isolir -> i (t), disable -> d (t), open -> ! (l)
            $statusMark = ($act == 'isolir') ? 'i' : (($act == 'disable') ? 'd' : '!');
            $qStatus    = ($act == 'open' || $act == '!') ? 'l' : 't';
            $fwDisabled = ($act == 'open' || $act == '!') ? 'yes' : 'no';

            // 1. Update PPP Secret Comment (format: 150 | 150 | date | mark)
            // Kita coba ambil data comment lama untuk mempertahankan harga dasar jika ada
            $oldComment = explode(" | ", $u['comment'] ?? "0 | 0 | | !");
            $newSecretComment = ($oldComment[0] ?? '0') . " | " . ($oldComment[1] ?? '0') . " | $date | $statusMark";
            
            $API->comm('/ppp/secret/set', [
                ".id" => $id, 
                "comment" => $newSecretComment
            ]);

            // 2. Update Firewall Address List (Jembatan IP)
            if ($ipBridge) {
                $this->setAddressListStatus($API, $ipBridge, $username, $fwDisabled);
            }

            // 3. Update Simple Queue (Jembatan Nama & IP)
            $qIdent = $API->comm("/queue/simple/print", ["?name" => $username]);
            if (empty($qIdent) && $ipBridge) {
                $qIdent = $API->comm("/queue/simple/print", ["?target" => "$ipBridge/32"]);
            }

            if (!empty($qIdent)) {
                $q = $qIdent[0];
                $p = explode(" | ", $q['comment'] ?? "0 | 0 | | t");
                $newQComment = ($p[0] ?? '0') . " | " . ($p[1] ?? '0') . " | $date | $qStatus";
                
                $API->comm("/queue/simple/set", [
                    ".id" => $q['.id'], 
                    "comment" => $newQComment
                ]);
            }

            // 4. Kick Session
           // $this->kickActiveUser($API, $username);
        }
        $API->disconnect();
    }
    header("Location: " . BASE_URL . "/mikrotik/pppoe");
}


private function setAddressListStatus($API, $address, $name, $disabled) {
    // Cari apakah IP sudah ada di list
    $exist = $API->comm('/ip/firewall/address-list/print', [
        "?list" => "POOL-ISOLIR", 
        "?address" => $address
    ]);

    if (empty($exist)) {
        // Jika belum ada dan statusnya ingin mengisolir (disabled=no), maka ADD
        if ($disabled == 'no') {
            $API->comm('/ip/firewall/address-list/add', [
                "list" => "POOL-ISOLIR", 
                "address" => $address, 
                "comment" => $name, 
                "disabled" => "no"
            ]);
        }
    } else {
        // Jika sudah ada, cukup update status disabled (yes/no)
        $API->comm('/ip/firewall/address-list/set', [
            ".id" => $exist[0]['.id'], 
            "disabled" => $disabled
        ]);
    }
}

private function createIncomeLog($API, $name, $value, $date, $time) {
    // Format Name: PPPoE|30.KWHotspot@tegar|50000|2026-03-27
    $cleanName = str_replace([' ', '/'], ['_', '-'], $name);
    $scriptName = "PPPoE|" . $cleanName . "|" . $value . "|" . $date;

    $API->comm("/system/script/add", [
        "name"    => $scriptName,
        "owner"   => $date,
        "source"  => "Auto-Pay at $time WIB",
        "comment" => "DoITWifi"
    ]);
}

private function kickActiveUser($API, $name) {
    $active = $API->comm('/ppp/active/print', ["?name" => $name]);
    foreach ($active as $a) {
        $API->comm('/ppp/active/remove', [".id" => $a['.id']]);
    }
}


    public function delete() {
        $id = $_GET['id'] ?? '';
        $API = $this->get_api_connection();
        if ($API && $id) {
            $API->comm('/ppp/secret/remove', [".id" => $id]);
            $API->disconnect();
        }
        header("Location: " . BASE_URL . "/mikrotik/pppoe");
    }
	
	/**
     * Monitoring Netwatch Pelanggan (Static)
     */
    public function netwatch_monitor() {
        $API = $this->get_api_connection();
        if (!$API) die("Gagal terhubung ke MikroTik.");

        // Ambil data Netwatch dan Address List
        $netwatch_raw = $API->comm('/tool/netwatch/print') ?? [];
        $address_list = $API->comm('/ip/firewall/address-list/print', ["?list" => "POOL-ISOLIR"]) ?? [];

        // Mapping isolir untuk indikator warna
        $isolir_ips = [];
        foreach ($address_list as $al) {
            if ($al['disabled'] === 'false') {
                $isolir_ips[] = $al['address'];
            }
        }

        $processed_netwatch = [];
        foreach ($netwatch_raw as $nw) {
            $comment = $nw['comment'] ?? '';
            // Pecah komentar: Nama | Tanggal | Status
            $parts = explode('|', $comment);
            
            $processed_netwatch[] = [
                'id'       => $nw['.id'],
                'host'     => $nw['host'],
                'status'   => $nw['status'], // up / down
                'nama'     => trim($parts[0] ?? 'No Name'),
                'tanggal'  => trim($parts[1] ?? '-'),
                'kode'     => trim($parts[2] ?? ''),
                'isIsolir' => in_array($nw['host'], $isolir_ips)
            ];
        }

        $API->disconnect();
        
        $netwatch = $processed_netwatch;
        // Load view static.php
        include __DIR__.'/../Views/admin/mikrotik/static.php';
    }

    /**
     * Aksi untuk Netwatch (Isolir / Open / Auto)
     */
    public function netwatch_action() {
        $id   = $_GET['id'] ?? '';
        $host = $_GET['host'] ?? '';
        $act  = $_GET['act'] ?? ''; // isolir / auto
        $name = $_GET['name'] ?? '';
        $date = date('Y-m-d');

        $API = $this->get_api_connection();
        if ($API && $id && $host) {
            if ($act == 'isolir') {
                // Update Comment Netwatch ke | i
                $API->comm('/tool/netwatch/set', [
                    ".id" => $id,
                    "comment" => "$name | $date | i"
                ]);
                // Masukkan ke Address List (Status Active)
                $this->setAddressListStatus($API, $host, $name, 'no');

            } elseif ($act == 'auto') {
                // Update Comment Netwatch ke | ! atau d
                $API->comm('/tool/netwatch/set', [
                    ".id" => $id,
                    "comment" => "$name | $date | !"
                ]);
                // Matikan di Address List
                $this->setAddressListStatus($API, $host, $name, 'yes');
            }
            $API->disconnect();
        }
        header("Location: " . BASE_URL . "/mikrotik/static");
    }
	/**
     * Monitoring Perangkat via GenieACS
     */
public function acs() {
    $json_path = __DIR__ . '/../../storage/mikrotikdata.json';
    $acs_url = "";
    if (file_exists($json_path)) {
        $config = json_decode(file_get_contents($json_path), true);
        $acs_url = $config[0]['acsurl'] ?? '';
    }

    if (empty($acs_url)) die("Konfigurasi ACS URL tidak ditemukan.");

    // Projection untuk mengambil data spesifik
    $proj = "_id,_productClass,_lastInform,VirtualParameters.get_active_ip,VirtualParameters.rx_power,InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID";
    $ch = curl_init($acs_url . "?projection=" . urlencode($proj));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $resACS = json_decode($response, true) ?? [];
    curl_close($ch);

    $processed_acs = [];
    $now = time();
    $stats = ['total' => 0, 'online' => 0, 'offline' => 0, 'low_rx' => 0];

    foreach ($resACS as $d) {
        // 1. Status Online/Offline
        $lastIn = isset($d['_lastInform']) ? strtotime($d['_lastInform']) : 0;
        $isOnline = ($now - $lastIn < 300);

// 2. Rx Power (Mencari di VirtualParameters atau fallback ke string)
        $rxRaw = '0';
        if (isset($d['VirtualParameters']['rx_power']['_value'])) {
            $rxRaw = $d['VirtualParameters']['rx_power']['_value'];
        } elseif (isset($d['VirtualParameters']['rx_power']) && !is_array($d['VirtualParameters']['rx_power'])) {
            $rxRaw = $d['VirtualParameters']['rx_power'];
        }

        // BERSIHKAN DATA: Hanya ambil angka, titik, dan tanda minus
        $rxClean = preg_replace('/[^0-9.-]/', '', $rxRaw);
        $rxVal = floatval($rxClean);

        // 3. IP Address (Mencari di VP atau WanDevice)
        $ip = $d['VirtualParameters']['get_active_ip']['_value'] ?? '-';
        if ($ip == '-') {
            $ip = $d['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['ExternalIPAddress']['_value'] ?? '-';
        }

        // 4. SSID
        $ssid = $d['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['SSID']['_value'] ?? '-';

        // Update Statistik untuk View
        $stats['total']++;
        if ($isOnline) $stats['online']++; else $stats['offline']++;
        
        // LOGIKA FILTER LOW RX: Pastikan kriteria sesuai
        $isBadRx = ($rxVal <= -27 && $rxVal != 0); 
        if ($isBadRx) $stats['low_rx']++;

        $processed_acs[] = [
            'id'         => $d['_id'] ?? 'Unknown',
            'model'      => $d['_productClass'] ?? '-',
            'ip'         => $ip,
            'rx'         => $rxClean, // Kirim angka bersih ke View
            'ssid'       => $ssid,
            'isOnline'   => $isOnline,
            'lastInform' => ($lastIn > 0) ? date('d/m H:i', $lastIn) : 'Never'
        ];
    }

    // Mengirim variabel ke file view yang Anda buat
    $devices = $processed_acs;
    include __DIR__.'/../Views/admin/mikrotik/acs.php';
}

 }