<?php
// 1. Session Start & Debugging (Aktifkan display_errors jika masih ada masalah)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* --- 2. DETEKSI BASE URL (DINAMIS & PERMANEN) --- */
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// Deteksi folder project secara otomatis
$base_folder = str_replace('/public/index.php', '', $_SERVER['SCRIPT_NAME']);
$base_url = $protocol . $host . $base_folder;

if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}

/* --- 3. LOAD CONTROLLERS (MENGGUNAKAN PATH ABSOLUT) --- */
$controller_path = __DIR__ . '/../app/Controllers/';

require_once $controller_path . 'AuthController.php';
require_once $controller_path . 'DashboardController.php';
require_once $controller_path . 'ACSController.php';
require_once $controller_path . 'MikrotikController.php';
require_once $controller_path . 'VoucherController.php';
require_once $controller_path . 'SellerController.php';
require_once $controller_path . 'ApiController.php';
require_once $controller_path . 'HotspotController.php';
require_once $controller_path . 'UserController.php';
require_once $controller_path . 'IncomeController.php';

/* --- 4. NORMALISASI URI (SMART ROUTING) --- */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Buang folder project dari URI
if ($base_folder !== '' && strpos($uri, $base_folder) === 0) {
    $uri = substr($uri, strlen($base_folder));
}

// Bersihkan index.php dan ekstensi .php agar URL cantik
$uri = str_replace('/index.php', '', $uri);
$uri = preg_replace('/\.php$/', '', $uri);
$uri = rtrim($uri, '/') ?: '/';

/* --- 5. ROUTING TABLE --- */
// Variabel yang digunakan harus $uri (hasil normalisasi di atas)
switch ($uri) {
    // LOGIN & AUTH
    case '/':
    case '/login':
        (new AuthController())->index();
        break;

    case '/auth/login':
        (new AuthController())->login();
        break;

// --- ROUTER UTAMA ---

// Halaman Home (Monitoring)
case '/home':
case '/admin/home':
    (new DashboardController())->home();
    break;

// Halaman Settings (Dashboard Index)
case '/settings':  // Tambahkan case ini
case '/dashboard':
case '/admin/index': (new DashboardController())->index();break;
case '/admin/about': (new DashboardController())->about();break;

   // Simpan Konfigurasi Mikrotik & Telegram
case '/save-mikrotik': (new ApiController())->save_mikro_tele();break;

case '/api/quick-stats': (new ApiController())->get_quick_stats(); break;
case '/api/test-telegram': (new ApiController())->testTelegram(); break;
    // Update Data Admin
    case '/update-admin':
        (new ApiController())->update_admin();
        break;
		
    // SELLER & VOUCHER
    case '/seller/dashboard':
    case '/seller':
        (new SellerController())->index();
        break;

    case '/seller/store': (new SellerController())->store(); break;
    case '/seller/update': (new SellerController())->update(); break;
    case '/seller/topup': (new SellerController())->topup(); break;
    case '/seller/delete': (new SellerController())->delete(); break;

    case '/voucher':
        (new VoucherController())->index();
        break;

    // INCOME
case '/income/income':    (new IncomeController())->index(); break;     // Halaman List Pelanggan
case '/income/dashboard': (new IncomeController())->dashboard(); break; // Halaman Laporan Tab
case '/income/bayar':     (new IncomeController())->bayar(); break;     // Aksi Bayar
case '/income/syncMasal':     (new IncomeController())->syncMasal(); break;     // Aksi syncMasal


// MIKROTIK PPPOE
    case '/mikrotik/pppoe': (new MikrotikController())->pppoe(); break;
    case '/mikrotik/pppoe/store': (new MikrotikController())->store(); break;
    case '/mikrotik/pppoe/update_status': (new MikrotikController())->update_status(); break;
    case '/mikrotik/pppoe/delete': (new MikrotikController())->delete(); break;

    // MIKROTIK NETWATCH (STATIC MONITORING)
    case '/mikrotik/static': (new MikrotikController())->netwatch_monitor(); break;
    case '/mikrotik/netwatch_action': (new MikrotikController())->netwatch_action(); break;
	case '/mikrotik/acs': (new MikrotikController())->acs(); break;

    // HOTSPOT MANAGEMENT
    case '/hotspot/active': (new HotspotController())->active(); break;
    case '/hotspot/user': (new HotspotController())->user(); break;
    case '/hotspot/user/update_status': (new HotspotController())->update_status(); break;
    case '/hotspot/user/delete': (new HotspotController())->delete(); break;
    case '/hotspot/profile': (new HotspotController())->profile(); break;
    case '/hotspot/save_profile': (new HotspotController())->save_profile(); break;
    case '/hotspot/delete_profile': (new HotspotController())->delete_profile(); break;

    // API & AJAX
    case '/api/resources': (new ApiController())->get_resources(); break;
    case '/api/traffic': (new ApiController())->get_traffic(); break;
    case '/api/logs': (new ApiController())->get_logs(); break;
    case '/api/check-conn':
    case '/check-conn':
        (new ApiController())->check_conn();
        break;

    case '/api/pingtest':
    case '/pingtest':
        (new ApiController())->pingtest();
        break;
    // USERSELLER
	case '/userseller/auth/login': (new UserController())->login(); break;
	case '/userseller':
    case '/userseller/dashboard': (new UserController())->index(); break;
    case '/userseller/activitas': (new UserController())->activitas(); break;
	case '/userseller/voucher_seller': (new UserController())->voucher_seller(); break;
	case '/userseller/generate': (new UserController())->generate(); break;
	case '/userseller/profile': (new UserController())->profile(); break;
	case '/userseller/profile/update': (new UserController())->update_profile(); break;
	case '/userseller/dashboard/check-notif': (new UserController())->check_notification(); break;
	case '/userseller/dashboard/mark_all_read': (new UserController())->mark_all_read(); break;
    // SYSTEM
    case '/logout':
        session_destroy();
        header("Location: " . BASE_URL . "/login?action=logout"); 
        exit;

    default:
        header("HTTP/1.0 404 Not Found");
        echo "<h3>404 Not Found</h3>";
        echo "Halaman tidak ditemukan.<br>";
        echo "Debug URI: <b>" . htmlspecialchars($uri) . "</b><br>";
        echo "Base URL: <b>" . BASE_URL . "</b>";
        break;
}