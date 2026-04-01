<?php

class UserController {
    // Definisi path file penyimpanan data (JSON)
    private $mikrotikFile;
    private $sellerFile;
    private $rateFile;
    private $notifFile;
    private $voucherLogFile; // Tambahan untuk history generate

    public function __construct() {
		if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
        $this->mikrotikFile = __DIR__ . '/../../storage/mikrotikdata.json';
        $this->sellerFile   = __DIR__ . '/../../storage/sellerdata.json';
        $this->rateFile      = __DIR__ . '/../../storage/rate.json';
        $this->notifFile    = __DIR__ . '/../../storage/topup_log.json';
        $this->voucherLogFile = __DIR__ . '/../../storage/voucherlog.json';
    }

    /**
     * HALAMAN UTAMA (DASHBOARD)
     * Menampilkan statistik saldo, stok voucher, dan grafik pendapatan.
     */
    public function index() {
        // Proteksi Halaman: Cek apakah session seller sudah login
        if (!isset($_SESSION['sell'])) {
            header("Location: " . BASE_URL . "/login");
            exit;
        }

        $currentUser = $_SESSION['sell'];
        $currentRole = $_SESSION['role'] ?? 'seller';

        // 1. AMBIL SALDO SELLER
        $sellers = json_decode(file_get_contents($this->sellerFile), true) ?: [];
        $balance = 0;
        foreach ($sellers as $s) {
            if ($s['sellername'] === $currentUser) {
                $balance = $s['sellerbalance'] ?? 0;
                break;
            }
        }

        // 2. AMBIL DATA RATE & MAPPING MARGIN (Untuk perhitungan laba bersih)
        $rates = json_decode(file_get_contents($this->rateFile), true) ?: [];
        $priceMapping = [];
        foreach ($rates as $r) {
            $priceMapping[trim($r['amount'])] = (float)($r['margine'] ?? 0);
        }

        // 3. LOGIKA NOTIFIKASI (Cek top-up yang belum dibaca)
        if (!file_exists($this->notifFile)) {
            file_put_contents($this->notifFile, json_encode([]));
        }
        $notificationsRaw = json_decode(file_get_contents($this->notifFile), true) ?: [];
        $unreadCount = 0;
        $filteredNotifs = [];

        foreach (array_reverse($notificationsRaw) as $notif) {
            if (isset($notif['sellername']) && $notif['sellername'] == $currentUser) {
                if (!isset($notif['read']) || !$notif['read']) {
                    $unreadCount++;
                }
                $filteredNotifs[] = $notif;
            }
        }

        // 4. STATISTIK MIKROTIK & PERHITUNGAN STOK
        $api = $this->get_api();
        $labels = []; $grossData = []; $netData = [];
        $stokPerProfile = [];

        // Penentuan range tanggal grafik (Default: tanggal 6 bulan ini s/d 30 hari ke depan)
        $startDateParam = $_GET['start'] ?? date('Y-m-06');
        $startDate = new DateTime($startDateParam);
        $endDate = (clone $startDate)->modify('+29 days');
        
        $prevDate = (clone $startDate)->modify('-30 days')->format('Y-m-d');
        $nextDate = (clone $startDate)->modify('+30 days')->format('Y-m-d');

        if ($api) {
            $scripts = $api->comm("/system/script/print");
            $mt_hotspotUser = $api->comm("/ip/hotspot/user/print") ?? [];

            // --- Logika Hitung Stok Per Profile ---
            foreach ($rates as $rate) {
                $pCount = 0;
                $targetProfile = trim($rate['profile']);

                foreach ($mt_hotspotUser as $hsUser) {
                    if (empty($hsUser['name'])) continue;
                    
                    $comment = $hsUser['comment'] ?? '';
                    $commentParts = explode("|", $comment);
                    $userSeller = isset($commentParts[1]) ? trim($commentParts[1]) : '';

                    // Filter kepemilikan voucher (Admin bisa lihat semua, seller hanya miliknya)
                    $isOwner = ($currentRole === 'admin' || strcasecmp(trim($currentUser), $userSeller) === 0);

                    if ($isOwner && isset($hsUser['profile']) && strcasecmp(trim($hsUser['profile']), $targetProfile) === 0) {
                        $pCount++;
                    }
                }
                $stokPerProfile[$rate['profile']] = $pCount;
            }

            // --- Logika Grafik Pendapatan ---
            $period = new DatePeriod($startDate, new DateInterval('P1D'), (clone $endDate)->modify('+1 day'));
            foreach ($period as $date) {
                $d = $date->format("Y-m-d");
                $labels[] = $date->format("d M");
                $dailyGross = 0; $dailyNet = 0;

                foreach ($scripts as $s) {
                    if (strpos($s['name'], "-|-") === false) continue;
                    $parts = explode("-|-", $s['name']);
                    $commentFields = explode("|", ($parts[8] ?? ''));

                    if (count($commentFields) >= 4) {
                        $u   = trim($commentFields[1]);
                        $amt = trim($commentFields[2]);
                        $tgl = trim($commentFields[3]);

                        if (strcasecmp($u, $currentUser) === 0 && $tgl === $d) {
                            $valAmt = (float)$amt;
                            $dailyGross += $valAmt;
                            if (isset($priceMapping[$amt])) {
                                $dailyNet += ($valAmt - $priceMapping[$amt]);
                            }
                        }
                    }
                }
                $grossData[] = $dailyGross;
                $netData[]   = $dailyNet;
            }
            $api->disconnect();
        }

        // 5. KONFIGURASI EKSTERNAL (DNS & Telegram)
        $teleConfig = $this->get_telegram_config();
        $dnsName = 'KWHotspot';

        if (file_exists($this->mikrotikFile)) {
            $mikrotikConfig = json_decode(file_get_contents($this->mikrotikFile), true) ?: [];
            $dnsName = $mikrotikConfig[0]['dns'] ?? 'KWHotspot';
        }

        // 6. PACKING DATA KE VIEW
        $data = [
            'dnsName'        => $dnsName,
            'teletoken'      => $teleConfig['token'] ?? '',
            'chatid'         => $teleConfig['id'] ?? '',
            'balance'        => $balance,
            'user'           => $currentUser,
            'rates'          => $rates,
            'stokPerProfile' => $stokPerProfile,
            'colors'         => ['#f26522', '#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6c757d'],
            'labels'         => $labels,
            'grossData'      => $grossData,
            'netData'        => $netData,
            'startDate'      => $startDate,
            'endDate'        => $endDate,
            'prevDate'       => $prevDate,
            'nextDate'       => $nextDate,
            'unread'         => $unreadCount,
            'notifList'      => $filteredNotifs
        ];

        extract($data);
        include __DIR__ . '/../Views/userseller/dashboard.php';
    }

    /**
     * PROSES GENERATE VOUCHER MASSAL (SELLER)
     */
    public function generate() {
        if (!isset($_SESSION['sell'])) {
            header("Location: " . BASE_URL . "/login");
            exit;
        }

        $api = $this->get_api();
        if (!$api) {
            die("Koneksi MikroTik Gagal. Silahkan cek konfigurasi router Anda.");
        }

        // 1. Ambil data dari form
        $sell        = $_SESSION['sell'];
        $quantity    = (int)($_POST['quantity'] ?? 2);
        $amount      = (int)($_POST['amount'] ?? 0);
        $limitbytes  = (int)($_POST['limitbytes'] ?? 0);
        $prefix      = $_POST['prefix'] ?? "";
        $length      = (int)($_POST['length'] ?? 4);
        $profile     = $_POST['profile'] ?? "";
        $margine     = (int)($_POST['margine'] ?? 0);
        $dnsName     = $_POST['dnsName'] ?? ''; 

        // Basic Validation
        if ($quantity <= 0 || empty($profile)) {
            echo "<script>alert('Data form tidak lengkap!'); window.history.back();</script>";
            exit;
        }

        $total_cost  = $margine * $quantity;

        // 2. Cek saldo seller
        $userList = file_exists($this->sellerFile) ? json_decode(file_get_contents($this->sellerFile), true) : [];
        $initial_balance = 0;
        $found_key = null;

        foreach ($userList as $key => $user) {
            if ($user['sellername'] === $sell) {
                $initial_balance = $user['sellerbalance'];
                $found_key = $key;
                break;
            }
        }

        if ($initial_balance < $total_cost) {
            echo "<script>
                alert('⚠️ Saldo tidak cukup.\\nSaldo: Rp" . number_format($initial_balance, 0, ',', '.') . "\\nButuh: Rp" . number_format($total_cost, 0, ',', '.') . "');
                window.history.back();
            </script>";
            exit;
        }

        // 3. Proses generate voucher ke MikroTik
        $generatedCodes = [];
        $date = strtolower(date("Y-m-d"));
        
        for ($i = 0; $i < $quantity; $i++) {
            $str = substr(sha1(mt_rand()), 17, $length);
            $vc  = strtoupper($prefix . $str);

            $create = $api->comm('/ip/hotspot/user/add', [
                "name"              => $vc,
                "password"          => $vc,
                "limit-bytes-total" => (string)($limitbytes * 1024 * 1024 * 1024),
                "profile"           => $profile,
                "comment"           => "vc-apk|$sell|$amount|$date"
            ]);

            if (isset($create['!trap'])) {
                continue; 
            }
            $generatedCodes[] = $vc;
        }

        // 4. Update Saldo & Logging
        if (!empty($generatedCodes) && $found_key !== null) {
            $actual_total_cost = $margine * count($generatedCodes);
            $userList[$found_key]['sellerbalance'] = $initial_balance - $actual_total_cost;
            file_put_contents($this->sellerFile, json_encode($userList, JSON_PRETTY_PRINT));

            $logData = file_exists($this->voucherLogFile) ? json_decode(file_get_contents($this->voucherLogFile), true) : [];
            $logData[] = [
                "seller"  => $sell,
                "profile" => $profile,
                "amount"  => $amount,
                "qty"     => count($generatedCodes),
                "cost"    => $actual_total_cost,
                "codes"   => $generatedCodes,
                "date"    => date("Y-m-d H:i:s")
            ];
            file_put_contents($this->voucherLogFile, json_encode($logData, JSON_PRETTY_PRINT));
        }

        $api->disconnect();
        $dnsName = 'KWHotspot'; // Default
        if (file_exists($this->mikrotikFile)) {
            $mikrotikConfig = json_decode(file_get_contents($this->mikrotikFile), true) ?: [];
            // Mengambil field 'dns' dari index pertama (0) sesuai struktur json Anda
            $dnsName = $mikrotikConfig[0]['dns'] ?? 'KWHotspot';
        }		
        // 5. Packing data untuk view cetak
        $data = [
            'codes'    => $generatedCodes,
            'amount'   => $amount,
            'dnsName'  => $dnsName,//
            'profile'  => $profile,
            'limit'    => $limitbytes,
            'quantity' => count($generatedCodes),
            'sell'     => $sell,
            'balance_after' => $initial_balance - ($margine * count($generatedCodes))
        ];

        extract($data);
        include __DIR__ . '/../Views/userseller/generate.php';
    }

    /**
     * HALAMAN AKTIVITAS (HISTORY PENJUALAN)
     */
    public function activitas($API = null) {
        if ($API === null) {
            $API = $this->get_api();
        }

        if (!$API) {
            die("Koneksi MikroTik Gagal. Silahkan cek sesi login Anda.");
        }

        $data = $this->getHistoryData($API);
        extract($data);
        include __DIR__ . '/../Views/userseller/activitas.php';
    }

    /**
     * HALAMAN VOUCHER SELLER (DATA STOK BELUM TERPAKAI)
     */
    public function voucher_seller($API = null) {
        if (!isset($_SESSION)) { session_start(); }

        if ($API === null) {
            $API = $this->get_api();
        }

        if (!$API) {
            die("Error: Gagal terhubung ke MikroTik.");
        }

        $myLoginName = $_SESSION['sell'] ?? '';
        $myRole      = $_SESSION['role'] ?? 'seller';
        $isAdmin     = ($myRole === 'admin');

        $allUsers = $API->comm("/ip/hotspot/user/print") ?? [];
        $filteredVouchers = [];
        $id = 0;

        foreach ($allUsers as $user) {
            if (!isset($user['comment']) || strpos($user['comment'], "|") === false) continue;

            $commentLines = explode("|", $user['comment']);
            $sellerName   = isset($commentLines[1]) ? trim($commentLines[1]) : '';
            $amount       = isset($commentLines[2]) ? trim($commentLines[2]) : '0';

            if ($isAdmin || strcasecmp($sellerName, $myLoginName) === 0) {
                $id++;
                $filteredVouchers[] = [
                    'id'       => $id,
                    'username' => $user['name'],
                    'profile'  => $user['profile'],
                    'status'   => ($user['disabled'] === 'true' ? 'Disabled' : 'Active'),
                    'amount'   => $amount,
                    'raw'      => $user
                ];
            }
        }

        $data = ['vouchers' => $filteredVouchers, 'role' => $myRole];
        extract($data);
        include __DIR__ . '/../Views/userseller/voucher_seller.php';
    }

    /**
     * LOGIKA INTERNAL: PROSES DATA HISTORY DARI SCRIPT MIKROTIK
     */
    private function getHistoryData($API) {
        if (!isset($_SESSION)) { session_start(); }
        
        $myLoginName = $_SESSION['username'] ?? ($_SESSION['sell'] ?? '');
        $myRole      = $_SESSION['role'] ?? 'seller';
        $isAdmin     = ($myRole === 'admin');

        $targetUser = ($isAdmin && isset($_GET['seller']) && !empty($_GET['seller'])) 
                      ? trim($_GET['seller']) 
                      : trim($myLoginName);

        $getScripts   = $API->comm("/system/script/print") ?? [];
        $allAddresses = $API->comm("/ip/address/print") ?? [];

        $historyVoucher = [];
        $sumDaily        = 0; 
        $sumMonthly     = 0;
        $now            = time(); 
        $HariIni        = date("Y-m-d"); 
        $BulanIni       = date("Y-m");

        foreach ($getScripts as $scr) {
            if (!isset($scr['name']) || strpos($scr['name'], "-|-") === false) continue;
            
            $p = explode("-|-", $scr['name']); 
            if (count($p) < 9) continue;
            
            $commentData = explode("|", $p[8]); 
            $sellerName  = trim($commentData[1] ?? '');
            $harga       = (float)($commentData[2] ?? 0);
            $tglRaw      = $p[0];

            if (strcasecmp($sellerName, $targetUser) === 0) {
                if ($tglRaw === $HariIni) $sumDaily += $harga;
                if (strpos($tglRaw, $BulanIni) !== false) $sumMonthly += $harga;

                $limitStr = $p[6]; 
                $days     = (int) filter_var($limitStr, FILTER_SANITIZE_NUMBER_INT);
                if($days <= 0) $days = 1;

                $expTimestamp = strtotime($tglRaw . " " . $p[1] . " + $days days");
                
                $historyVoucher[] = [
                    'tgl'        => date("d-m-Y", strtotime($tglRaw)),
                    'jam'        => $p[1],
                    'voucher'    => $p[2],
                    'paket'      => $p[7],
                    'harga'      => number_format($harga),
                    'ip'         => $p[4],
                    'iface'      => $this->getInterfaceByIP($p[4], $allAddresses),
                    'exp'        => date('d-m-Y H:i:s', $expTimestamp),
                    'statusHtml' => ($expTimestamp < $now) ? '<span class="badge bg-danger">NON AKTIF</span>' : '<span class="badge bg-success">AKTIF</span>',
                    'msg'        => urlencode("Voucher: {$p[2]}\nPaket: {$p[7]}\nExp: " . date('d-m-Y H:i:s', $expTimestamp))
                ];
            }
        }

        usort($historyVoucher, function($a, $b) {
            return strtotime($b['tgl']. ' ' . $b['jam']) - strtotime($a['tgl']. ' ' . $a['jam']);
        });

        $sellerList = file_exists($this->sellerFile) ? json_decode(file_get_contents($this->sellerFile), true) : [];
        $displayBalance = 0;
        foreach($sellerList as $s) { 
            if(strcasecmp($s['sellername'] ?? '', $targetUser) === 0) { 
                $displayBalance = $s['sellerbalance'] ?? 0; 
                break; 
            } 
        }

        $topupLog = file_exists($this->notifFile) ? json_decode(file_get_contents($this->notifFile), true) : [];
        $filteredTopup = array_filter($topupLog, function($t) use ($targetUser, $BulanIni) {
            return strcasecmp($t['sellername'] ?? '', $targetUser) === 0 && strpos($t['datetime'] ?? '', $BulanIni) !== false;
        });

        return [
            'targetUser'     => $targetUser,
            'isAdmin'        => $isAdmin,
            'historyVoucher' => $historyVoucher,
            'sumDaily'       => $sumDaily,
            'sumMonthly'     => $sumMonthly,
            'displayBalance' => $displayBalance,
            'filteredTopup'  => array_reverse($filteredTopup),
            'sellerList'     => $sellerList
        ];
    }

    private function get_api() {
        require_once __DIR__ . '/../Libraries/routeros_api.class.php';
        $api = new RouterosAPI();
        if ($api->connect($_SESSION['router_ip'], $_SESSION['router_user'], $_SESSION['router_pass'])) {
            return $api;
        }
        return false;
    }

    private function getInterfaceByIP($ip, $addresses) {
        if (!$ip || $ip == '-') return "Unknown";
        $parts = explode('.', $ip);
        if(count($parts) < 3) return "Unknown";
        $prefix = $parts[0].'.'.$parts[1].'.'.$parts[2].'.';
        foreach ($addresses as $addr) {
            if (isset($addr['network']) && strpos($addr['network'], $prefix) !== false) return $addr['interface'];
        }
        return "Unknown";
    }

    public function profile() {
        if (!isset($_SESSION['sell'])) {
            header("Location: " . BASE_URL . "/login");
            exit;
        }
        $sellername = $_SESSION['sell'];
        $sellerData = [];
        if (file_exists($this->sellerFile)) {
            $users = json_decode(file_get_contents($this->sellerFile), true) ?: [];
            foreach ($users as $user) {
                if ($user['sellername'] === $sellername) {
                    $sellerData = $user;
                    break;
                }
            }
        }
        $data = ['sellerData' => $sellerData];
        extract($data);
        include __DIR__ . '/../Views/userseller/profile.php';
    }

    public function update_profile() {
        if (!isset($_SESSION['sell'])) {
            header("Location: " . BASE_URL . "/login");
            exit;
        }
        $currentUser = $_SESSION['sell'];
        $newPassword = $_POST['password'] ?? '';
        $newWhatsapp = $_POST['whatsapp'] ?? '';

        if (file_exists($this->sellerFile)) {
            $users = json_decode(file_get_contents($this->sellerFile), true) ?: [];
            $found = false;
            foreach ($users as &$user) {
                if ($user['sellername'] === $currentUser) {
                    $user['sellerphone'] = $newWhatsapp;
                    if (!empty($newPassword)) { $user['password'] = $newPassword; }
                    $found = true;
                    break;
                }
            }
            if ($found) {
                file_put_contents($this->sellerFile, json_encode($users, JSON_PRETTY_PRINT));
                $_SESSION['msg'] = "Data profil Anda telah diperbarui.";
            } else {
                $_SESSION['error'] = "Gagal memperbarui data. Seller tidak ditemukan.";
            }
        }
        header("Location: " . BASE_URL . "/userseller/profile");
        exit;
    }

    public function get_telegram_config() {
        if (file_exists($this->mikrotikFile)) {
            $data = json_decode(file_get_contents($this->mikrotikFile), true);
            $telegramConfig = $data[1] ?? [];
            return [
                'token'  => $telegramConfig['teletoken'] ?? null,
                'id'     => $telegramConfig['chatid'] ?? null,
                'acsurl' => $telegramConfig['acs_url'] ?? null
            ];
        }
        return ['token' => null, 'id' => null, 'acsurl' => null];
    }

public function check_notification() {
    header('Content-Type: application/json');
    // Tambahkan header anti-cache agar browser tidak menyimpan hasil fetch lama
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    $currentUser = $_SESSION['sell'] ?? '';
    
    if (empty($currentUser)) {
        echo json_encode(['found' => false, 'unread_count' => 0]);
        exit;
    }

    // Paksa PHP membersihkan cache status file
    clearstatcache();

    if (file_exists($this->notifFile)) {
        // Gunakan LOCK_SH (Shared Lock) saat membaca agar tidak bentrok jika sedang ada proses tulis
        $fileHandle = fopen($this->notifFile, "r");
        flock($fileHandle, LOCK_SH);
        $fileContent = fread($fileHandle, filesize($this->notifFile) ?: 1);
        flock($fileHandle, LOCK_UN);
        fclose($fileHandle);

        $logs = json_decode($fileContent, true) ?: [];
        
        $unreadCount = 0;
        $latestUnread = null;

        // Iterasi dari log terbaru (biasanya di atas)
        foreach ($logs as $log) {
            $isSeller = isset($log['sellername']) && strcasecmp($log['sellername'], $currentUser) == 0;
            
            // Cek status read dengan sangat teliti
            $isUnread = (!isset($log['read']) || $log['read'] === false || $log['read'] === "false" || $log['read'] === 0 || $log['read'] === "0");

            if ($isSeller && $isUnread) {
                $unreadCount++;
                
                // Ambil data terbaru untuk notifikasi pop-up (hanya jika belum diset)
                if ($latestUnread === null) {
                    $latestUnread = [
                        'amount' => $log['topup'] ?? 0, 
                        'after' => $log['after'] ?? 0,
                        'msg' => $log['msg'] ?? "Saldo masuk!"
                    ];
                }
            }
        }

        echo json_encode([
            'found' => ($latestUnread !== null),
            'unread_count' => $unreadCount, // Ini yang akan membuat icon pesan update
            'amount' => $latestUnread['amount'] ?? 0,
            'after' => $latestUnread['after'] ?? 0,
            'msg' => $latestUnread['msg'] ?? ""
        ]);
    } else {
        echo json_encode(['found' => false, 'unread_count' => 0]);
    }
    exit;
}
public function mark_all_read() {
    header('Content-Type: application/json');
    $currentUser = $_SESSION['sell'] ?? '';

    if (empty($currentUser)) {
        echo json_encode(['status' => false]);
        exit;
    }

    if (file_exists($this->notifFile)) {
        $logs = json_decode(file_get_contents($this->notifFile), true) ?: [];

        foreach ($logs as &$log) {
            if (isset($log['sellername']) && strcasecmp($log['sellername'], $currentUser) == 0) {
                $log['read'] = true;
            }
        }
        unset($log);

        file_put_contents($this->notifFile, json_encode($logs, JSON_PRETTY_PRINT), LOCK_EX);

        echo json_encode(['status' => true]);
    } else {
        echo json_encode(['status' => false]);
    }
    exit;
}
/**
     * HALAMAN LOGIN SELLER
     */
public function login() {
    // Pastikan session aktif
    if (session_status() === PHP_SESSION_NONE) { session_start(); }

    // Cek apakah user sudah punya session valid (Seller atau Admin)
    $isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
    $isSeller = isset($_SESSION['sell']);

    if ($isAdmin || $isSeller) {
        // Jika Admin masuk tapi session 'sell' kosong, isi dengan username admin
        // Ini supaya dashboard seller tidak error saat mencari variabel $currentUser
        if ($isAdmin && !$isSeller) {
            $_SESSION['sell'] = $_SESSION['username'] ?? 'Admin';
        }

        header("Location: " . BASE_URL . "/userseller/dashboard");
        exit;
    }

    // Jika belum login sama sekali, baru tampilkan halaman login
    include __DIR__ . '/../Views/login.php';
}
}