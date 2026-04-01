<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php
// 1. Definisikan path file JSON (Sesuaikan path-nya)
$json_path = dirname(__DIR__, 2) . '/storage/mikrotikdata.json';

// 2. Set nilai default (Fallback)
$dn = "DOiT";
$ns = "WiFi";
$content = $content ?? ''; // Agar tidak error jika content kosong

// 3. Ambil data langsung dari JSON
if (file_exists($json_path)) {
    $json_raw = file_get_contents($json_path);
    $json_data = json_decode($json_raw, true);
    
    // Ambil field 'dns' dari index [0]
    $dns_value = $json_data[0]['dns'] ?? '';

    if (!empty($dns_value)) {
        if (strpos($dns_value, '@') !== false) {
            $parts = explode('@', $dns_value);
            $dn = $parts[0];
            $ns = $parts[1];
        } else {
            $dn = $dns_value;
            $ns = "";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $dn . '.' . $ns ?? 'DOiTWiFi' ?> Login</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>


<body class="login-page">

    <div id="loading">
        <div class="text-center text-light">
            <div class="spinner-border mb-2"></div><br>
            <span id="loadText">Menghubungkan...</span>
        </div>
    </div>

    <div class="login-container">
        <div id="brandName">
		<svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" fill="#ff4b2b" class="bi bi-incognito" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M10 3.203a.5.5 0 0 1 .5.5c0 .175-.03.351-.088.514L9.13 7.556a5 5 0 1 1-2.26 0L5.588 4.217a.5.5 0 1 1 .912-.414l1.248 2.746a.5.5 0 0 0 .904 0L9.5 3.703a.5.5 0 0 1 .5-.5M15 8c0 3.866-3.134 7-7 7s-7-3.134-7-7 3.134-7 7-7 7 3.134 7 7M6 11a1 1 0 1 0-2 0 1 1 0 0 0 2 0m6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0"/>
        </svg>
            <span id="DoiT"><?= $dn ?? 'DOiT' ?></span><span id="WIfi"><?= $ns ?? 'WiFi' ?></span>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert-custom">
                <?= $_GET['error'] == 1 ? "❌ Username/Password Salah!" : "⚠️ Server Offline!"; ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" action="<?= BASE_URL ?>/auth/login" method="post">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="mtUsername" id="mtUsername" class="form-control" placeholder="Masukkan Username" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="mtPassword" id="mtPassword" class="form-control" placeholder="Masukkan Password" required>
                    <i class="bi bi-eye-slash" id="togglePassword"></i>
                </div>
            </div>

            <button type="submit" id="btnSubmit" class="btn-blue">Login Sekarang</button>
        </form>

        <div class="text-center mt-3">
            <span class="footer-link" onclick="manualReset()">Ganti Akun / Bersihkan Sesi</span>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const sUser = localStorage.getItem("sell");
            const sPass = localStorage.getItem("pass");
            const urlParams = new URLSearchParams(window.location.search);
            
            const isLogout = urlParams.get('action') === 'logout';
            const hasError = urlParams.has('error');

            // 1. Fungsi Show/Hide Password
            $('#togglePassword').on('click', function() {
                const passwordField = $('#mtPassword');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                $(this).toggleClass('bi-eye bi-eye-slash');
            });

            // 2. Isi Form jika data ada
            if (sUser && sPass) {
                $('#mtUsername').val(sUser);
                $('#mtPassword').val(sPass);
            }

            // 3. Logika Auto-Submit
            if (sUser && sPass && !isLogout && !hasError) {
                $('#loading').css('display', 'flex');
                $('#loadText').text("Otomatis Masuk...");
                $('#btnSubmit').prop('disabled', true).text("Otomatis Masuk...");
                
                setTimeout(() => { $('#loginForm').submit(); }, 1200);
            } else {
                // Sembunyikan loading jika tidak auto-submit
                $('#loading').fadeOut(500);
            }

            // 4. Simpan data saat submit
            $('#loginForm').on('submit', function() {
                localStorage.setItem("sell", $('#mtUsername').val());
                localStorage.setItem("pass", $('#mtPassword').val());
                $('#loading').fadeIn(200);
                $('#btnSubmit').prop('disabled', true).text("Memproses...");
            });
        });

        // 5. Fungsi Reset
        function manualReset() {
            if(confirm("Hapus data login yang tersimpan?")) {
                localStorage.clear();
                $('#mtUsername').val('');
                $('#mtPassword').val('');
                // Redirect ke halaman login tanpa parameter error/action
                window.location.href = "<?= BASE_URL ?>/auth/login";
            }
        }
    </script>
</body>
</html>
