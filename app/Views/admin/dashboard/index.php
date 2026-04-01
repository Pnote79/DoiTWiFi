<?php
/**
 * Admin Settings Page - KWHotspot
 * Developed by: doitwifi
 * Features: MikroTik Config, Admin Management, Telegram Bot Integration
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/login");
    exit;
}
?>



    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body { background:#0d1117; color:#c9d1d9; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; transition: 0.3s; }
        body.light { background:#f4f7f6; color:#222; }
        .card { background:#161b22; border:1px solid #30363d; border-radius:12px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); margin-bottom: 20px; }
        body.light .card { background:#fff; color:#000; border: 1px solid #ddd; }
        .form-control { background:#0d1117; border:1px solid #30363d; color:#e6edf3; border-radius: 8px; font-size: 0.9rem; }
        .form-control:focus { background:#161b22; color:#fff; border-color: #58a6ff; }
        body.light .form-control { background:#fff; color:#000; border: 1px solid #ccc; }
        .btn { border-radius:8px; font-weight:600; text-transform: uppercase; letter-spacing: 1px; }
        .live-clock { font-size: 0.9rem; font-weight: bold; color: #58a6ff; }
        .nav-footer { position:fixed; bottom:0; left:0; width:100%; background:#161b22; padding:12px; border-top:1px solid #30363d; z-index: 1000; }
        label { font-size: 0.85rem; font-weight: 600; color: #8b949e; }
        body.light label { color: #555; }
    </style>


<div class="d-flex justify-content-between align-items-center p-3">
    <div class="live-clock ml-2">
        <i class="far fa-calendar-alt"></i> <span id="currentDate">Memuat tanggal...</span>
    </div>
    <button onclick="toggleTheme()" class="btn btn-sm btn-outline-info">
        <i class="fas fa-adjust"></i> Switch Mode
    </button>
</div>

<div class="container-fluid mt-2 mb-5 pb-5">
    <div class="row">
        <div class="col-lg-6">
            <div class="card">

                <div class="card-body">
                    <form action="<?= BASE_URL ?>/save-mikrotik" method="post">
                        <div class="form-group">
                            <label><i class="fas fa-network-wired"></i> IP Address / Host</label>
                            <input type="text" id="ipmik" name="ipmik" class="form-control" value="<?= $data['mt']['mtip'] ?? '' ?>" placeholder="192.168.1.1">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-user"></i> API Username</label>
                                    <input type="text" name="usermik" class="form-control" value="<?= $data['mt']['mtuser'] ?? '' ?>" placeholder="admin">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-lock"></i> API Password</label>
                                    <div class="input-group">
                                        <input type="password" id="passmik" name="passmik" class="form-control" value="<?= $data['mt']['mtpass'] ?? '' ?>">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePass()"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr style="border-color: #30363d;">

                       
<div class="row">
    <div class="col-6">
        <div class="form-group">
            <label><i class="fas fa-globe"></i> DNS Hotspot</label>
            <input type="text" name="hotmik" class="form-control" value="<?= $data['mt']['dns'] ?? '' ?>" placeholder="hotspot.net">
        </div>
    </div>
    <div class="col-6">
        <div class="form-group">
            <label><i class="fas fa-microchip"></i> MTDNS</label>
            <input type="text" name="dnsmik" class="form-control" value="<?= $data['mt']['mtdns'] ?? '' ?>" placeholder="1.1.1.1">
        </div>
    </div>
</div>

<div class="form-group">
    <label><i class="fas fa-link"></i> GenieACS URL</label>
    <input type="text" name="acsurl" class="form-control" value="<?= $data['mt']['acsurl'] ?? '' ?>" placeholder="http://10.10.10.1:7547">
    <small class="text-muted">Endpoint untuk provisioning CPE.</small>
</div>

                        <button class="btn btn-success btn-block mb-2"><i class="fas fa-save"></i> Simpan Konfigurasi</button>
                        <button type="button" id="pingBtn" class="btn btn-info btn-block"><i class="fas fa-broadcast-tower"></i> Uji Ping</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header bg-dark text-white"><i class="fas fa-user-shield"></i> LOGIN ADMIN PANEL</div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/update-admin" method="post">
                        <label>Username Baru</label>
                        <input type="text" name="adminname" class="form-control mb-2" value="<?= $data['adminname'] ?>">
                        <label>Password Baru</label>
                        <input type="password" name="adminpass" class="form-control mb-2" placeholder="Kosongkan jika tidak ganti">
                        <button class="btn btn-success btn-block">Update Akun</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-info text-white"><i class="fab fa-telegram-plane"></i> NOTIFIKASI TELEGRAM</div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/save-telegram" method="post">
                        <label>Bot Token</label>
                        <input type="text" name="teletoken" class="form-control mb-2" placeholder="123456:ABC-DEF..." value="<?= $data['tele']['teletoken'] ?? '' ?>">
                        <label>Chat ID</label>
                        <input type="text" name="chat_id" class="form-control mb-2" placeholder="-1001234567" value="<?= $data['tele']['chatid'] ?? '' ?>">
                        <div class="row no-gutters">
                            <div class="col-6 pr-1">
                                <button class="btn btn-success btn-block">Simpan</button>
                            </div>
                            <div class="col-6 pl-1">
                                <button type="button" onclick="testTelegram()" class="btn btn-warning btn-block">Test Kirim</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="nav-footer">
    <div class="container-fluid">
        <button onclick="goHome()" class="btn btn-primary btn-block py-2">
            <i class="fas fa-rocket"></i> MASUK KE DASHBOARD 
        </button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const API = "<?= BASE_URL ?>/api";

function updateClock() {
    const now = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
    document.getElementById('currentDate').innerText = now.toLocaleDateString('id-ID', options);
}
setInterval(updateClock, 1000);
updateClock();

function toggleTheme(){
    $('body').toggleClass('light');
    localStorage.setItem('theme', $('body').hasClass('light') ? 'light' : 'dark');
}

$(document).ready(() => {
    if(localStorage.getItem('theme') === 'light') $('body').addClass('light');
   
});

function togglePass(){
    let p = $('#passmik');
    p.attr('type', p.attr('type') === 'password' ? 'text' : 'password');
}



$('#pingBtn').click(() => {
    let ip = $('#ipmik').val();
    if(!ip) return Swal.fire('Error', 'Isi IP MikroTik dulu!', 'warning');

    Swal.fire({ 
        title: 'Sedang Ping...', 
        text: 'Mengirim 4 paket ke ' + ip,
        allowOutsideClick: false, 
        didOpen: () => Swal.showLoading() 
    });

    $.ajax({
        url: API + '/pingtest',
        data: { ip: ip },
        type: 'GET',
        timeout: 7000, // Beri waktu sedikit lebih lama dari total ping
        success: function(res) {
            Swal.close();
            Swal.fire({ 
                title: 'Hasil Ping', 
                html: '<pre style="text-align:left; background:#222; color:#0f0; padding:10px; border-radius:5px; font-size:0.8rem;">' + res + '</pre>', 
                icon: 'info'
            });
        },
        error: function(xhr) {
            Swal.close();
            // Jika gagal/timeout, ambil respon dari server atau tampilkan pesan default
            let errorRes = xhr.responseText ? xhr.responseText : "RTO (Request Timeout) - Perangkat tidak merespon.";
            Swal.fire({ 
                title: 'Ping Gagal / Timeout', 
                html: '<pre style="text-align:left; background:#222; color:#ff4d4d; padding:10px; border-radius:5px; font-size:0.8rem;">' + errorRes + '</pre>', 
                icon: 'error'
            });
        }
    }); // Tutup AJAX
}); // Tutup Click


function testTelegram(){
    Swal.fire({ title: 'Testing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    $.getJSON(`${API}/test-telegram`, res => {
        Swal.close();
        Swal.fire({ icon: res.status ? 'success' : 'error', title: 'Telegram Report', text: res.msg });
    }).fail(() => Swal.fire('Error', 'API Error', 'error'));
}

function goHome(){
    Swal.fire({ title: 'Menghubungkan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    $.get(API + '/check-conn', res => {
        Swal.close();
        if(res.trim() === 'success'){
            window.location.href = "<?= BASE_URL ?>/home";
        } else {
            Swal.fire({ icon: 'error', title: 'Koneksi Gagal', text: 'Cek settingan MikroTik!' });
        }
    });
}
</script>
<?php
$content = ob_get_clean();
include dirname(__DIR__,2) . '/layouts/layout.php';