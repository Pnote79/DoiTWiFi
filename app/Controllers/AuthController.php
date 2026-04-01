<?php
class AuthController {
    private $sellerFile = __DIR__ . '/../../storage/sellerdata.json';
    private $mikrotikFile = __DIR__ . '/../../storage/mikrotikdata.json';

    public function index() {
        // Tampilkan view login
        include __DIR__ . '/../Views/login.php';
    }

public function login() {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $user_in = $_POST['mtUsername'] ?? '';
    $pass_in = $_POST['mtPassword'] ?? '';

    $sellers = json_decode(file_get_contents($this->sellerFile), true);
    $found = null;

    foreach ($sellers as $s) {
        if ($s['sellername'] === $user_in) {
            $found = $s;
            break;
        }
    }

    if ($found && password_verify($pass_in, $found['sellerpasswd'])) {

        $_SESSION['sell']     = $found['sellername'];
        $_SESSION['username'] = $found['sellername']; 
        $_SESSION['role']     = $found['profile'];

        session_regenerate_id(true);

        // =========================
        // MIKROTIK CHECK
        // =========================
        $mtData = json_decode(file_get_contents($this->mikrotikFile), true);
        $mt = $mtData[0] ?? null;

        $isOnline = false;

        if (
            $mt &&
            !empty($mt['mtip']) &&
            !empty($mt['mtuser']) &&
            !empty($mt['mtpass'])
        ) {
            $_SESSION['router_ip']   = $mt['mtip'];
            $_SESSION['router_user'] = $mt['mtuser'];
            $_SESSION['router_pass'] = $mt['mtpass'];

            $isOnline = $this->fastCheck($mt['mtip']);
        } else {
            $_SESSION['router_ip'] = null;
        }

        $_SESSION['mt_status'] = $isOnline ? 'online' : 'offline';

        // =========================
        // REDIRECT
        // =========================
        if ($found['profile'] === 'admin') {

            $target = $isOnline ? "/admin/home" : "/admin/index";
            header("Location: " . BASE_URL . $target);

        } else {

            if ($isOnline) {
                header("Location: " . BASE_URL . "/userseller/dashboard");
            } else {
                session_destroy();
                header("Location: " . BASE_URL . "/login?error=2");
            }
        }

        exit;

    } else {
        header("Location: " . BASE_URL . "/login?error=1");
        exit;
    }
}

 private function fastCheck($ip) {
        if (empty($ip) || $ip === '0.0.0.0') return false;
        $fp = @fsockopen($ip, 8728, $errno, $errstr, 2);
        if ($fp) { fclose($fp); return true; }
        return false;
    }
}