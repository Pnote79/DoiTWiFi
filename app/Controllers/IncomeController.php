<?php
/**
 * Controller Income & Dashboard
 * Support: Gemini GPT - doitwifi
 */

class IncomeController {
    private $API;
    private $config;
    private $storage_path;

    public function __construct() {
        require_once __DIR__ . '/../Libraries/routeros_api.class.php';
        $this->storage_path = __DIR__ . '/../../storage/';
        $mikrotik_json = $this->storage_path . 'mikrotikdata.json';

        if (file_exists($mikrotik_json)) {
            $data = json_decode(file_get_contents($mikrotik_json), true);
            $this->config = $data[0] ?? null;
        }

        $this->API = new RouterosAPI();
        $this->API->timeout = 3;
    }

    /**
     * Menampilkan Halaman List Pelanggan & Pembayaran (income.php)
     */
    public function index() {
        date_default_timezone_set('Asia/Jakarta');
        
        // Inisialisasi variabel agar tidak "Undefined variable" di View
        $data = [
            'sumDaily'   => 0,
            'sumMonthly' => 0,
            'sumOverall' => 0,
            'totalAllBalance' => 0, // Diambil dari sellerdata jika ada
            'queues'     => []
        ];

        // Load saldo seller dari JSON
        $sellerList = $this->loadJson('sellerdata.json');
        foreach ($sellerList as $s) {
            $data['totalAllBalance'] += (float)($s['sellerbalance'] ?? 0);
        }

        if ($this->config && $this->API->connect($this->config['mtip'], $this->config['mtuser'], $this->config['mtpass'])) {
            
            // 1. Ambil Data Antrian
            $data['queues'] = $this->API->comm("/queue/simple/print", ["?parent" => "3.PPPOE-STATIC"]) ?? [];

            // 2. Ambil Data Income untuk Statistik di Atas Tabel
            $scripts = $this->API->comm("/system/script/print") ?? [];
            $today = date("Y-m-d");
            $thisMonth = date("Y-m");

            foreach ($scripts as $s) {
                $scriptComment = $s['comment'] ?? '';
                $scriptName = $s['name'] ?? '';
                $amount = 0;
                $tgl = "";

                if ($scriptComment === 'mikhmon') {
                    $p = explode("-|-", $scriptName);
                    if (count($p) >= 4) { $tgl = $p[0]; $amount = (float)$p[3]; }
                } elseif ($scriptComment === 'DoITWifi') {
                    $p = explode("|", $scriptName);
                    if (count($p) >= 3) { $amount = (float)$p[1]; $tgl = rtrim($p[2], "-"); }
                }

                if ($amount > 0) {
                    $data['sumOverall'] += $amount;
                    if ($tgl === $today) $data['sumDaily'] += $amount;
                    if (substr($tgl, 0, 7) === $thisMonth) $data['sumMonthly'] += $amount;
                }
            }
            $this->API->disconnect();
        }

        $this->renderView('admin/income/income', $data);
    }

    /**
     * Menampilkan Halaman Laporan Tab (dashboard.php)
     */
public function dashboard() {
        date_default_timezone_set('Asia/Jakarta');

        // Ambil filter dari URL, default ke hari ini (Format: 26, 03, 2026)
        $f_date  = $_GET['date']  ?? date('d');
        $f_month = $_GET['month'] ?? date('m'); // Menggunakan 'm' (angka 01-12)
        $f_year  = $_GET['year']  ?? date('Y');

        $data = [
            'unpaid'  => 0,
            'daily'   => ['mikhmon' => 0, 'doitwifi' => 0, 'total' => 0],
            'monthly' => ['mikhmon' => 0, 'doitwifi' => 0, 'total' => 0],
            'yearly'  => ['mikhmon' => 0, 'doitwifi' => 0, 'total' => 0],
            'filter'  => [
                'date'  => $f_date, 
                'month' => $f_month, 
                'year'  => $f_year
            ]
        ];

        if ($this->config && $this->API->connect($this->config['mtip'], $this->config['mtuser'], $this->config['mtpass'])) {
            
            // 1. Ambil Tunggakan PPPoE
            $queues = $this->API->comm("/queue/simple/print", ["?parent" => "3.PPPOE-STATIC"]) ?? [];
            foreach ($queues as $q) {
                $parts = explode(" | ", $q['comment'] ?? "");
                if (count($parts) >= 4 && trim($parts[3]) === 't') {
                    $data['unpaid'] += ((int)$parts[1] * 1000);
                }
            }

            // 2. Ambil & Filter Script Income
            $scripts = $this->API->comm("/system/script/print") ?? [];
            foreach ($scripts as $s) {
                $this->processScriptWithFilter($data, $s, $f_date, $f_month, $f_year);
            }

            $this->API->disconnect();
        }

        $this->renderView('admin/income/dashboard', $data);
    }
/**
 * Logika Pemrosesan Data Script dengan Filter
 */
private function processScriptWithFilter(&$data, $s, $f_d, $f_m, $f_y) {
    $scName = $s['name'] ?? '';
    $scComment = $s['comment'] ?? '';
    
    $amt = 0;
    $tgl = ""; // Format target: YYYY-MM-DD
    $type = "";

    // 1. LOGIKA MIKHMON (Pemisah -|-)
    if ($scComment === 'mikhmon') {
        $p = explode("-|-", $scName);
        if (count($p) >= 4) {
            $tgl = trim($p[0]); // Format: 2026-03-26
            $amt = (float)$p[3];   
            $type = 'mikhmon';
        }
    } 
    // 2. LOGIKA DOITWIFI (Pemisah |)
    elseif ($scComment === 'DoITWifi') {
        $p = explode("|", $scName);
        if (count($p) >= 3) {
            $amt = (float)($p[2] ?? 0); 
            $tglRaw = isset($p[3]) ? trim($p[3]) : '';
            $tgl = rtrim($tglRaw, "-"); // Bersihkan dash: 2026-03-26- jadi 2026-03-26
            $type = 'doitwifi';
        }
    }

    // Jika data valid, lakukan pengecekan filter
    if ($type !== "" && $amt > 0 && $tgl !== "") {
        $target_daily   = "$f_y-$f_m-$f_d"; // Contoh: 2026-03-26
        $target_monthly = "$f_y-$f_m";      // Contoh: 2026-03
        $target_yearly  = "$f_y";           // Contoh: 2026

        // Cek Harian
        if ($tgl === $target_daily) {
            $data['daily'][$type] += $amt;
        }
        // Cek Bulanan
        if (substr($tgl, 0, 7) === $target_monthly) {
            $data['monthly'][$type] += $amt;
        }
        // Cek Tahunan
        if (substr($tgl, 0, 4) === $target_yearly) {
            $data['yearly'][$type] += $amt;
        }

        // Kalkulasi Total
        $data['daily']['total']   = $data['daily']['mikhmon'] + $data['daily']['doitwifi'];
        $data['monthly']['total'] = $data['monthly']['mikhmon'] + $data['monthly']['doitwifi'];
        $data['yearly']['total']  = $data['yearly']['mikhmon'] + $data['yearly']['doitwifi'];
    }
}



private function processScriptData(&$data, $s) {
    date_default_timezone_set('Asia/Jakarta');
    $today = date("Y-m-d"); 
    $m = date("Y-m"); 
    $y = date("Y");
    
    $scName = $s['name'] ?? '';
    $scComment = $s['comment'] ?? '';
    $amt = 0;
    $tgl = "";
    $type = "";

    // 1. LOGIKA UNTUK MIKHMON (Pemisah -|-)
    if ($scComment === 'mikhmon') {
        $p = explode("-|-", $scName);
        if (count($p) >= 4) {
            $tgl = trim($p[0]);    
            $amt = (float)$p[3];   
            $type = 'mikhmon';
        }
    } 
    // 2. LOGIKA UNTUK DOITWIFI (Pemisah |)
    // Format: PPPoE|46.queue1|50000|2026-03-26-
    elseif ($scComment === 'DoITWifi') {
        $p = explode("|", $scName);
        if (count($p) >= 3) {
            // Index 0: PPPoE, Index 1: Nama, Index 2: Harga, Index 3: Tanggal
            // Jika formatnya PPPoE|Nama|Harga|Tanggal, maka:
            $amt = (float)($p[2] ?? 0); 
            $tglRaw = isset($p[3]) ? trim($p[3]) : '';
            
            // Bersihkan tanggal dari dash di akhir (2026-03-26-)
            $tgl = rtrim($tglRaw, "-");
            $type = 'doitwifi';
        }
    }

    // Eksekusi kalkulasi hanya jika data valid
    if ($type !== "" && $amt > 0 && $tgl !== "") {
        // Harian
        if ($tgl === $today) {
            $data['daily'][$type] += $amt;
        }
        // Bulanan
        if (substr($tgl, 0, 7) === $m) {
            $data['monthly'][$type] += $amt;
        }
        // Tahunan
        if (substr($tgl, 0, 4) === $y) {
            $data['yearly'][$type] += $amt;
        }

        // Update Total
        $data['daily']['total']   = $data['daily']['mikhmon'] + $data['daily']['doitwifi'];
        $data['monthly']['total'] = $data['monthly']['mikhmon'] + $data['monthly']['doitwifi'];
        $data['yearly']['total']  = $data['yearly']['mikhmon'] + $data['yearly']['doitwifi'];
    }
} 


public function bayar() {
    // 1. SET TIMEZONE KE WIB
    date_default_timezone_set('Asia/Jakarta');

    $id = $_GET['id'] ?? null;
    $date = date("Y-m-d");
    $time = date("H:i:s"); // Jam:Menit:Detik WIB
    $timestamp = date("His"); // Untuk keunikan nama script

    if ($id && $this->config && $this->API->connect($this->config['mtip'], $this->config['mtuser'], $this->config['mtpass'])) {
        
        $getQueue = $this->API->comm("/queue/simple/print", [
            ".proplist" => "name,comment", 
            "?.id" => $id
        ]);

        if (!empty($getQueue)) {
            $q = $getQueue[0];
            $parts = explode(" | ", $q['comment'] ?? "0 | 0 | | t");
            
            $basePrice = (int) ($parts[0] ?? 0);
            $currentBill = (int) ($parts[1] ?? 0);
            
            if ($currentBill > 0) {
                // Bersihkan nama agar tidak ada karakter ilegal bagi MikroTik
                $cleanName = str_replace(['PPPoE-|-', ' ', '/'], ['', '_', '-'], $q['name']);
                $calculatedValue = $currentBill * 1000;

                // 1. Update Queue: Reset akumulasi & Status Lunas (l)
                // Format: HargaDasar | HargaDasar | TanggalBayar | l
                $this->API->comm("/queue/simple/set", [
                    ".id" => $id,
                    "comment" => "$basePrice | $basePrice | $date | l"
                ]);

                // 2. Buat Log Script
                // Menambahkan $timestamp (Jam) di nama agar tidak DUPLICATE jika klik 2x
                $scriptName = "PPPoE|" . $cleanName . "|" . $calculatedValue . "|" . $date;

                $this->API->comm("/system/script/add", [
                    "name"    => $scriptName,
                    "owner"   => $date,
                    "source"  => "Paid at $time WIB", // Keterangan waktu di kolom source
                    "comment" => "DoITWifi"
                ]);
            }
        }
        $this->API->disconnect();
    }
    header("Location: " . BASE_URL . "/income/income");
    exit;
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
 
    private function loadJson($filename) {
        $path = $this->storage_path . $filename;
        return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    }

    private function renderView($view, $data = []) {
        extract($data);
        require_once __DIR__ . '/../Views/' . $view . '.php';
    }
	
	/**
 * Update & Sinkron Masal
 * Menyesuaikan PPP Secret & Address List berdasarkan Status di Simple Queue
 */

public function syncMasal() {
    date_default_timezone_set('Asia/Jakarta');
    
    if ($this->config && $this->API->connect($this->config['mtip'], $this->config['mtuser'], $this->config['mtpass'])) {
        
        // 1. Ambil data Queue (Patokan Utama)
        $queues = $this->API->comm("/queue/simple/print", ["?parent" => "3.PPPOE-STATIC"]) ?? [];
        
        // 2. Ambil data PPP Secret untuk pencocokan IP
        $secrets = $this->API->comm("/ppp/secret/print") ?? [];

        foreach ($queues as $q) {
            $qComment = $q['comment'] ?? "";
            $target = $q['target'] ?? ""; 
            $ip = explode("/", $target)[0]; // Ambil IP (Jembatan)

            // Pecah comment Simple Queue
            $qParts = array_map('trim', explode("|", $qComment));
            if (count($qParts) < 4) continue;

            $qStatus = $qParts[3]; // 'l' atau 't'
            $baseComment = $qParts[0] . " | " . $qParts[1] . " | " . $qParts[2];

            // Cari Secret yang memiliki remote-address sesuai dengan IP Queue ini
            $targetSecret = null;
            foreach ($secrets as $s) {
                if (($s['remote-address'] ?? '') === $ip) {
                    $targetSecret = $s;
                    break;
                }
            }

            if ($targetSecret) {
                $pppName = $targetSecret['name'];
                $pppID = $targetSecret['.id'];

                if ($qStatus === 'l') {
                    // UPDATE COMMENT PPP JADI '!'
                    $pppComment = $baseComment . " | !";
                    $this->API->comm("/ppp/secret/set", [".id" => $pppID, "comment" => $pppComment]);
                    
                    // Lepas Isolir
                    $this->setAddressListStatus($this->API, $ip, $pppName, "yes"); 

                } else if ($qStatus === 't') {
                    // UPDATE COMMENT PPP JADI 't'
                    $pppComment = $baseComment . " | t";
                    $this->API->comm("/ppp/secret/set", [".id" => $pppID, "comment" => $pppComment]);
                    
                    // Pasang Isolir
                    $this->setAddressListStatus($this->API, $ip, $pppName, "no"); 
                    $this->kickActiveUser($this->API, $pppName);
                }

                // --- CEK BALIK LOGIKA 'i' ---
                $pParts = array_map('trim', explode("|", $targetSecret['comment'] ?? ""));
                if (count($pParts) >= 4 && $pParts[3] === 'i') {
                    $newQComment = $pParts[0] . " | " . $pParts[1] . " | " . $pParts[2] . " | t";
                    $this->API->comm("/queue/simple/set", [
                        ".id" => $q['.id'],
                        "comment" => $newQComment
                    ]);
                }
            }
        }
        
        $this->API->disconnect();
        echo json_encode(['status' => 'success', 'message' => 'Sinkronisasi via IP Berhasil']);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Koneksi Gagal']);
    exit;
}
/**
 * Helper: Update Comment di PPP Secret TANPA ganti Profile/Status
 */
private function updatePPPCommentOnly($name, $comment) {
    // Cari .id secret berdasarkan nama untuk memastikan update tepat sasaran
    $secret = $this->API->comm("/ppp/secret/print", ["?name" => $name]);
    if (!empty($secret)) {
        $this->API->comm("/ppp/secret/set", [
            ".id"     => $secret[0]['.id'],
            "comment" => $comment
        ]);
    }
}
/**
 * Remote Ruoter
 */
public function proxyRouter() {
    // 1. Validasi Akses & Input
    if (!isset($_SESSION['username'])) die("Unauthorized");
    
    $target_ip = explode('/', $_GET['ip'] ?? '')[0];
    $port = $_GET['port'] ?? '80';
    $path = $_GET['path'] ?? '';

    if (!filter_var($target_ip, FILTER_VALIDATE_IP)) die("IP Invalid");

    $url = "http://$target_ip:$port/$path";
    
    // 2. Inisialisasi CURL dengan Header Manipulasi
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        // 🔥 KUNCI BYPASS 403: Set Referer ke IP Router itu sendiri
        CURLOPT_REFERER => "http://$target_ip/", 
        CURLOPT_HTTPHEADER => [
            "Host: $target_ip",
            "Origin: http://$target_ip"
        ],
        CURLOPT_HEADER => true,
    ]);

    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $header_size);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    // 3. Kirim Header ke Browser
    if ($contentType) header("Content-Type: $contentType");
    header("X-Frame-Options: ALLOWALL");
    header("Access-Control-Allow-Origin: *");

    // 4. Injeksi Script untuk handle Link Internal (TP-Link WR820)
    $currentProxy = BASE_URL . "/income/proxyRouter?ip=$target_ip&port=$port&path=";
    
    $patch = "
    <script>
        // Mencegah error '$.id' yang sering ada di TP-Link
        window.onload = function() {
            if (typeof $ === 'undefined') { var $ = function(id){return document.getElementById(id)}; }
        };

        // Otomatis arahkan link internal kembali ke proxy
        document.addEventListener('click', function(e) {
            let el = e.target.closest('a');
            if (el) {
                let href = el.getAttribute('href');
                if (href && !href.startsWith('http') && !href.startsWith('#')) {
                    e.preventDefault();
                    window.location.href = '" . $currentProxy . "' + href.replace(/^\//,'');
                }
            }
        });
    </script>
    <base href='http://$target_ip:$port/'>
    <meta name='referrer' content='no-referrer'>
    ";

    // Sisipkan patch sebelum tutup head atau di awal body
    if (strpos($body, '<head>') !== false) {
        $body = str_replace('<head>', '<head>' . $patch, $body);
    } else {
        $body = $patch . $body;
    }

    echo $body;
    exit;
}
}