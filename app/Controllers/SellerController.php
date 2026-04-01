<?php

class SellerController {
    private $storageDir;
    private $sellerPath;
    private $topupLogPath;

    public function __construct() {
        $this->storageDir = __DIR__ . '/../../storage/';
        $this->sellerPath = $this->storageDir . 'sellerdata.json';
        $this->topupLogPath = $this->storageDir . 'topup_log.json';
        
        if (!is_dir($this->storageDir)) mkdir($this->storageDir, 0777, true);
    }

    // --- FUNGSI DEBUG UTAMA ---
    private function saveAndDebug($path, $data) {
        // 1. Cek apakah folder bisa ditulis
        if (!is_writable(dirname($path))) {
            die("ERROR: Folder '" . dirname($path) . "' tidak memiliki izin tulis (Permission Denied).");
        }

        // 2. Cek apakah file sudah ada dan bisa ditulis
        if (file_exists($path) && !is_writable($path)) {
            die("ERROR: File '" . basename($path) . "' terkunci atau tidak bisa ditulis.");
        }

        // 3. Coba tulis data
        $jsonString = json_encode($data, JSON_PRETTY_PRINT);
        if ($jsonString === false) {
            die("ERROR: Gagal melakukan encode JSON. Cek apakah ada karakter aneh.");
        }

        $result = file_put_contents($path, $jsonString);

        if ($result === false) {
            $error = error_get_last();
            die("ERROR PENULISAN: " . ($error['message'] ?? 'Alasan tidak diketahui'));
        }

        return true;
    }

    public function index() {
        // Fungsi tampil Anda sudah benar, tidak perlu diubah
        $sellerdata = json_decode(file_get_contents($this->sellerPath), true) ?? [];
        $voucherLogs = json_decode(file_get_contents($this->storageDir . 'voucherlog.json'), true) ?? [];
        $topupLogs = json_decode(file_get_contents($this->topupLogPath), true) ?? [];

        $bulanIni = date("Y-m");
        $now = time();
        $lastGenerate = [];
        foreach ($voucherLogs as $log) {
            if (isset($log['seller'], $log['date'])) {
                $ts = strtotime($log['date']);
                if (!isset($lastGenerate[$log['seller']]) || $ts > $lastGenerate[$log['seller']]) {
                    $lastGenerate[$log['seller']] = $ts;
                }
            }
        }

        $finalSellers = [];
        foreach ($sellerdata as $key => $s) {
            if (($s['profile'] ?? '') !== 'seller') continue;
            
            $name = $s['sellername'];
            $topupMonth = 0;
            foreach ($topupLogs as $t) {
                if (isset($t['datetime'], $t['topup'], $t['sellername']) && 
                    substr($t['datetime'], 0, 7) == $bulanIni && $t['sellername'] == $name) {
                    $topupMonth += (int)$t['topup'];
                }
            }

            $status = ($now - ($lastGenerate[$name] ?? 0) <= 3600) ? 'active' : 'inactive';
            
            $finalSellers[$key] = [
                'id'            => $key, 
                'name'          => $name,
                'balance'       => $s['sellerbalance'] ?? 0,
                'topup_month'   => $topupMonth,
                'status'        => $status,
                'phone'         => $s['sellerphone'] ?? '-'
            ];
        }

        include __DIR__ . '/../Views/admin/seller/seller.php';
    }

public function topup() {
        $id = $_POST['sellerid'];
        $amount = (int)$_POST['topup'];
        
        $data = json_decode(file_get_contents($this->sellerPath), true) ?? [];

        if (isset($data[$id])) {
            // Update Saldo
            $data[$id]['sellerbalance'] += $amount;
            $afterBalance = $data[$id]['sellerbalance'];
            $sellerName = $data[$id]['sellername'] ?? 'Unknown';

            // Simpan update saldo seller
            $this->saveAndDebug($this->sellerPath, $data);

            // --- PERBAIKAN DI SINI: Gunakan $this->topupLogPath ---
            $logFile = $this->topupLogPath; 
            
            $logs = [];
            if (file_exists($logFile)) {
                $logs = json_decode(file_get_contents($logFile), true) ?: [];
            }

            $newLog = [
                "datetime" => date('Y-m-d H:i:s'),
                "sellername" => $sellerName,
                "topup" => $amount,
                "after" => $afterBalance,
                "msg" => "Topup Berhasil",
                "read" => false // Tetap false agar dashboard seller berbunyi
            ];

            array_unshift($logs, $newLog);

            // Simpan file log
            file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT), LOCK_EX);

            $_SESSION['success'] = "Berhasil Topup Rp " . number_format($amount);
        } else {
            die("DEBUG: Seller ID '$id' tidak ditemukan.");
        }

        header("Location: " . BASE_URL . "/seller");
        exit;
    }

    public function store() {
        $data = json_decode(file_get_contents($this->sellerPath), true) ?? [];
        $keys = array_keys($data);
        $nextKey = empty($keys) ? 1 : max(array_map('intval', $keys)) + 1;

        $data[$nextKey] = [
            "sellername"    => $_POST['sellname'],
            "sellerpasswd"  => password_hash($_POST['sellpass'], PASSWORD_DEFAULT),
            "profile"       => "seller",
            "sellerphone"   => $_POST['phone'],
            "sellerbalance" => (int)$_POST['sellerbalance'],
            "id"            => $nextKey
        ];

        $this->saveAndDebug($this->sellerPath, $data);
        $_SESSION['success'] = "Tambah Berhasil!";
        header("Location: " . BASE_URL . "/seller");
        exit;
    }
	
	// --- Update Data Seller ---
    public function update() {
        $id = $_POST['id']; // Mengambil ID/Key dari form
        $data = json_decode(file_get_contents($this->sellerPath), true) ?? [];

        // Debug: Jika ID tidak ditemukan di JSON
        if (!isset($data[$id])) {
            die("Gagal Update: ID '$id' tidak ditemukan dalam data. Cek apakah ID di form sudah benar.");
        }

        // Update data pada key yang tepat
        $data[$id]['sellername'] = $_POST['sellername'];
        $data[$id]['sellerphone'] = $_POST['sellerphone'] ?? $data[$id]['sellerphone'];
        $data[$id]['sellerbalance'] = (int)$_POST['sellerbalance'];

        // Update password jika diisi
        if (!empty($_POST['sellpass'])) {
            $data[$id]['sellerpasswd'] = password_hash($_POST['sellpass'], PASSWORD_DEFAULT);
        }

        // Simpan menggunakan fungsi debug yang kita buat tadi
        $this->saveAndDebug($this->sellerPath, $data);

        $_SESSION['success'] = "Data seller " . $data[$id]['sellername'] . " berhasil diperbarui!";
        header("Location: " . BASE_URL . "/seller");
        exit;
    }

    // --- Hapus Seller ---
    public function delete() {
        // Ambil ID dari URL (?id=...)
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $data = json_decode(file_get_contents($this->sellerPath), true) ?? [];

        if ($id && isset($data[$id])) {
            unset($data[$id]); // Hapus key spesifik
            
            // Simpan perubahan
            $this->saveAndDebug($this->sellerPath, $data);
            
            $_SESSION['success'] = "Seller berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Gagal menghapus: ID tidak valid atau data sudah hilang.";
        }

        header("Location: " . BASE_URL . "/seller");
        exit;
    }
}