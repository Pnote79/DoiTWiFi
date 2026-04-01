<?php
// app/Controllers/ApiController.php

class ApiController {
    private $API;
    private $config;
    private $json_path;
    private $seller_path;

    public function __construct() {
        require_once __DIR__ . '/../Libraries/routeros_api.class.php';
        $this->json_path = __DIR__ . '/../../storage/mikrotikdata.json';
        $this->seller_path = __DIR__ . '/../../storage/sellerdata.json';

        if (file_exists($this->json_path)) {
            $data = json_decode(file_get_contents($this->json_path), true);
            $this->config = $data[0] ?? null;
        }
        $this->API = new RouterosAPI();
        $this->API->timeout = 3; 
    }

    /**
     * FUNGSI UTAMA: Check Connection (AJAX)
     * Dipanggil oleh /api/check-conn atau /check-conn
     */
    public function check_conn() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: text/plain');

        if (!$this->config) {
            echo "Konfigurasi MikroTik tidak ditemukan.";
            exit;
        }

        if ($this->API->connect($this->config['mtip'], $this->config['mtuser'], $this->config['mtpass'])) {
            $this->API->disconnect();
            echo "success";
        } else {
            // Cek detail kegagalan
            $port_check = @fsockopen($this->config['mtip'], 8728, $errno, $errstr, 2);
            if ($port_check) {
                fclose($port_check);
                echo "Username atau Password MikroTik Salah (WRONG_AUTH)";
            } else {
                echo "Port API (8728) Tertutup atau IP Tidak Terjangkau";
            }
        }
        exit;
    }

    /**
     * Ping Test (AJAX)
     */
public function pingtest() {
    $ip = $_GET['ip'] ?? '';
    if (empty($ip)) {
        echo "IP tidak valid";
        return;
    }

    $output = [];
    $result_code = 0;
    
    // Gunakan 2>&1 untuk menangkap error system ke dalam variabel $output
    exec("ping -c 4 -W 1 " . escapeshellarg($ip) . " 2>&1", $output, $result_code);

    if (empty($output)) {
        echo "Ping Error: Tidak ada output dari system.";
    } else {
        // Ambil 4 baris terakhir (statistik ping)
        // Jika gagal total, biasanya baris terakhir berisi "Summary"
        $akhir = array_slice($output, -4);
        echo implode("\n", $akhir);
    }
}

    /**
     * Simpan Konfigurasi MikroTik & Telegram
     */
public function save_mikro_tele() {
    $data = file_exists($this->json_path) ? json_decode(file_get_contents($this->json_path), true) : [[], []];

    if (isset($_POST['ipmik'])) {
        $data[0] = [
            'mtip'    => $_POST['ipmik'],
            'mtuser'  => $_POST['usermik'],
            'mtpass'  => !empty($_POST['passmik']) ? $_POST['passmik'] : ($data[0]['mtpass'] ?? ''),
            'dns'     => $_POST['hotmik'] ?? '', 
            'mtdns'   => $_POST['dnsmik'] ?? '',
            'acsurl'  => $_POST['acsurl'] ?? '' // Disamakan menjadi 'acsurl'
        ];
    }

    if (isset($_POST['teletoken'])) {
        $data[1] = [
            'teletoken' => $_POST['teletoken'],
            'chatid'    => $_POST['chat_id']
        ];
    }

    if (file_put_contents($this->json_path, json_encode($data, JSON_PRETTY_PRINT))) {
        header("Location: " . BASE_URL . "/settings?msg=success");
    } else {
        header("Location: " . BASE_URL . "/settings?msg=error");
    }
    exit;
} 

  /**
     * Update Akun Admin Web
     */
    public function update_admin() {
        $sellers = file_exists($this->seller_path) ? json_decode(file_get_contents($this->seller_path), true) : [];
        
        foreach ($sellers as &$s) {
            if (($s['profile'] ?? '') === 'admin') {
                $s['sellername'] = $_POST['adminname'];
                if (!empty($_POST['adminpass'])) {
                    $s['password'] = password_hash($_POST['adminpass'], PASSWORD_DEFAULT);
                }
                break;
            }
        }

        file_put_contents($this->seller_path, json_encode($sellers, JSON_PRETTY_PRINT));
        header("Location: " . BASE_URL . "/settings?status=saved");
        exit;
    }

/**
     * API: Get System Resources
     */
    public function get_resources() {
        header('Content-Type: application/json');
        if ($this->API->connect($this->config['mtip'], $this->config['mtuser'], $this->config['mtpass'])) {
            $res = $this->API->comm("/system/resource/print")[0] ?? [];
            $ident = $this->API->comm("/system/identity/print")[0]['name'] ?? 'MikroTik';
            
            // Kalkulasi RAM untuk progress bar
            $total = $res['total-memory'] ?? 1;
            $free = $res['free-memory'] ?? 0;
            $usage_percent = round((($total - $free) / $total) * 100, 1);

            echo json_encode([
                "identity"    => $ident,
                "cpu_load"    => $res['cpu-load'] ?? 0,
                "ram_total"   => round($total / 1048576, 1),
                "ram_free"    => round($free / 1048576, 1),
                "mem_percent" => $usage_percent, // Tambahkan ini agar JS bisa baca
                "uptime"      => $res['uptime'] ?? '-',
                "board"       => $res['board-name'] ?? '-',
                "version"     => $res['version'] ?? '-'
            ]);
            $this->API->disconnect();
        }
        exit;
    }

    /**
     * API: Get Traffic
     */
    public function get_traffic() {
        header('Content-Type: application/json');
        if ($this->API->connect($this->config['mtip'], $this->config['mtuser'], $this->config['mtpass'])) {
            $iface = $_GET['iface'] ?? 'ether1';
            $monitor = $this->API->comm("/interface/monitor-traffic", ["interface" => $iface, "once" => ""]);
            if (!empty($monitor)) {
                echo json_encode([
                    "time" => date("H:i:s"),
                    "tx" => round($monitor[0]['tx-bits-per-second'] / 1000000, 2),
                    "rx" => round($monitor[0]['rx-bits-per-second'] / 1000000, 2)
                ]);
            }
            $this->API->disconnect();
        }
        exit;
    }

/**
 * API: Get Filtered Logs (Hotspot & PPPoE with "->")
 */
public function get_logs() {
    header('Content-Type: application/json');
    
    // Pastikan koneksi menggunakan config yang benar
    if ($this->API->connect($this->config['mtip'], $this->config['mtuser'], $this->config['mtpass'])) {
        
        // Ambil log tanpa filter ketat di query agar tidak kosong
        // Kita ambil log umum saja, nanti difilter di PHP agar lebih akurat
        $logs = $this->API->comm("/log/print");
        
        $result = [];
        // Balik urutan agar yang terbaru di atas
        $reversedLogs = is_array($logs) ? array_reverse($logs) : [];

        foreach ($reversedLogs as $log) {
            $message = $log['message'] ?? '';
            $topics = $log['topics'] ?? '';

            // FILTER LOGIKA:
            // 1. Cek apakah ada prefix "->" 
            // 2. ATAU cek apakah topiknya mengandung 'hotspot' atau 'pppoe'
            $isArrow = (strpos($message, '->') !== false);
            $isHotspot = (strpos($topics, 'hotspot') !== false);
            $isPPPoE = (strpos($topics, 'pppoe') !== false);

            if ($isArrow || $isHotspot || $isPPPoE) {
                $result[] = [
                    'time'    => $log['time'] ?? '-',
                    'message' => $message
                ];
            }

            // Batasi 20 baris agar dashboard tetap ringan dan pas di layar
            if (count($result) >= 10) {
                break;
            }
        }

        echo json_encode($result);
        $this->API->disconnect();
    } else {
        echo json_encode([]); // Kirim array kosong jika gagal konek
    }
    exit;
}
public function get_quick_stats() {
    header('Content-Type: application/json');
    if ($this->API->connect($this->config['mtip'], $this->config['mtuser'], $this->config['mtpass'])) {
        
        // 1. Data Aktif & Jumlah User
        $userActive = count($this->API->comm("/ip/hotspot/active/print"));
        $ppoeActive = count($this->API->comm("/ppp/active/print"));
        $userCount  = count($this->API->comm("/ip/hotspot/user/print"));
        $netwatch   = count($this->API->comm("/tool/netwatch/print", [".proplist" => ".id", "?status" => "up"]));

        // 2. Logika Resource (Memory) - Tambahkan ini agar bar Memory terisi
        $res = $this->API->comm("/system/resource/print")[0] ?? [];
        $totalMem = $res['total-memory'] ?? 1;
        $freeMem = $res['free-memory'] ?? 0;
        $memPercent = round((($totalMem - $freeMem) / $totalMem) * 100, 1);
        $cpuLoad = $res['cpu-load'] ?? 0;

        // 3. Logika Income Mikhmon Style
        $sumDaily = 0;
        $sumMonth = 0;
        $getVendo = $this->API->comm("/system/script/print");
        $HariIni = strtolower(date("Y-m-d"));
        $bulanIni = strtolower(date("m"));

        if (is_array($getVendo)) {
            foreach ($getVendo as $item) {
                $name = $item['name'] ?? '';
                // Format Mikhmon biasanya: tgl-|-user-|-profil-|-harga-|-id
                $parts = explode('-|-', $name);
                if (count($parts) < 4) continue;

                $hari = strtolower(trim($parts[0]));
                $amount = floatval(trim($parts[3]));
                
                // Ambil bulan dari source script (biasanya berisi comment tgl lengkap)
                $source = isset($item['comment']) ? strtolower($item['comment']) : ''; 
                // Jika Mikhmon simpan bulan di comment/source, sesuaikan di sini:
                $partDate = explode("-", $hari); 
                $bulan = $partDate[1] ?? '';

                if ($HariIni == $hari) { 
                    $sumDaily += $amount; 
                }
                if ($bulan == $bulanIni) { 
                    $sumMonth += $amount; 
                }
            }
        }

        // 4. Output JSON Gabungan
        echo json_encode([
            "userActive" => $userActive,
            "ppoeActive" => $ppoeActive,
            "userCount"  => $userCount,
            "netwatchUp" => $netwatch,
            "sumMonth"   => $sumMonth,
            "sumDaily"   => $sumDaily,
            "cpu_load"   => $cpuLoad,     // Tambahan untuk update dashboard
            "mem_percent"=> $memPercent,  // Tambahan untuk update dashboard
            "identity"   => $this->API->comm("/system/identity/print")[0]['name'] ?? 'MikroTik'
        ]);

        $this->API->disconnect();
    } else {
        echo json_encode(["error" => "Disconnected"]);
    }
    exit;
}
    /**
     * API: Test Telegram
     */

public function testTelegram()
{
    header('Content-Type: application/json');

    // 1. Samakan path storage dengan fungsi index()
    $base_path = dirname(__DIR__, 2);
    $mikrotikdata_path = $base_path . "/storage/mikrotikdata.json";

    if (!file_exists($mikrotikdata_path)) {
        echo json_encode(['status' => false, 'msg' => 'File mikrotikdata.json tidak ditemukan']);
        return;
    }

    // 2. Load data dan ambil index [1] untuk Telegram (sesuai logika fungsi index)
    $mikrotikdata = json_decode(@file_get_contents($mikrotikdata_path), true) ?? [[], []];
    $telebot = $mikrotikdata[1] ?? [];

    // 3. Sesuaikan key array (pastikan key ini sesuai dengan isi file .json Anda)
    $token = $telebot['teletoken'] ?? ''; // Jika di json tertulis 'teletoken'
    $chat  = $telebot['chatid'] ?? '';    // Jika di json tertulis 'chatid'

    if (!$token || !$chat) {
        echo json_encode(['status' => false, 'msg' => 'Konfigurasi Telegram (Token/Chat ID) belum diisi']);
        return;
    }

    // Pesan test
    $message = "✅ *KWHotspot Test*\n";
    $message .= "Panel berhasil terhubung ke Telegram 🚀\n";
    $message .= "Waktu: " . date('Y-m-d H:i:s');

    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    $postData = [
        'chat_id' => $chat,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];

    // CURL request
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false // Tambahkan ini jika server lokal tidak ada sertifikat SSL
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo json_encode(['status' => false, 'msg' => 'CURL Error: ' . $error]);
        return;
    }

    $res = json_decode($response, true);

    if (isset($res['ok']) && $res['ok']) {
        echo json_encode(['status' => true, 'msg' => 'Pesan berhasil dikirim ke Telegram']);
    } else {
        echo json_encode([
            'status' => false, 
            'msg' => 'Gagal kirim: ' . ($res['description'] ?? 'Cek Token/ChatID Anda')
        ]);
    }
}
/**
     * LAYOUT UTAMA
     * Digunakan untuk membungkus view dengan sidebar & navbar
     */
public function layout($content = '') {
    $dn = "DO";
    $ns = "iT";

    if (file_exists($this->json_path)) {
        $json = json_decode(file_get_contents($this->json_path), true);
        $dns = $json[0]['dns'] ?? '';

        if (!empty($dns)) {
            if (strpos($dns, '@') !== false) {
                $parts = explode('@', $dns, 2); // Limit 2 bagian
                $dn = $parts[0];
                $ns = $parts[1];
            } else {
                $dn = $dns;
                $ns = ""; // Kosongkan jika tidak ada pemisah
            }
        }
    }

    // PENTING: Gunakan include/require di sini jika render() bermasalah
    return $this->render('layouts/layout', [
        'dn' => $dn,
        'ns' => $ns,
        'content' => $content
    ]);
}   
	
	/**
     * FUNGSI RENDER (Helper)
     * Pastikan fungsi ini ada agar variabel extract berfungsi
     */
private function render($path, $data = []) {
    // 1. Extract data agar ['dn' => 'xxx'] jadi $dn
    extract($data);

    $filePath = __DIR__ . '/../../views/' . $path . '.php';

    if (!file_exists($filePath)) {
        die("View file not found: " . $filePath);
    }

    // 2. Langsung require (jangan di-return sebagai string jika ini adalah Layout Utama)
    require $filePath; 
}

}