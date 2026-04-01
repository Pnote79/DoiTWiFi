<?php
/**
 * =========================================================
 * Nama File    : acs.php 
 * Project      : KWHotspot - doitwifi support Gemini GPT
 * Tanggal Buat : 25 Maret 2026
 * Deskripsi    : Implementasi Tema Dark/Light pada layout fixed
 * =========================================================
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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body.dark-theme { background-color: #0d1117; color: #c9d1d9; }
        .stat-card { cursor: pointer; border: none; border-radius: 10px; transition: 0.3s; }
        .stat-card:hover { transform: scale(1.02); }
        .table-dark { background-color: #161b22; border: 1px solid #30363d; font-size: 13px; }
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; }
        .rx-warning { color: #ff4444; font-weight: bold; animation: blink 1s infinite; }
        @keyframes blink { 50% { opacity: 0.5; } }
    </style>

<body class="dark-theme">
<div class="container-fluid mt-4">
    <div class="row mb-4 text-center">
        <div class="col-md-3 col-6 mb-2">
            <div class="card bg-primary stat-card" onclick="filterAcs('all')">
                <div class="card-body p-3">
                    <small>TOTAL CPE</small>
                    <h3 class="mb-0"><?= $stats['total'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card bg-success stat-card" onclick="filterAcs('online')">
                <div class="card-body p-3">
                    <small>🟢 ONLINE</small>
                    <h3 class="mb-0"><?= $stats['online'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card bg-danger stat-card" onclick="filterAcs('offline')">
                <div class="card-body p-3">
                    <small>🔴 OFFLINE</small>
                    <h3 class="mb-0"><?= $stats['offline'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card bg-warning text-dark stat-card" onclick="filterAcs('lowrx')">
                <div class="card-body p-3">
                    <small>💰 BAD RX (< -27)</small>
                    <h3 class="mb-0"><?= $stats['low_rx'] ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive shadow">
        <table id="acsTable" class="table table-dark table-hover">
            <thead>
                <tr>
                    <th>Serial Number</th>
                    <th>Status</th>
                    <th>IP Address</th>
                    <th>SSID</th>
                    <th>Rx Power</th>
                    <th>Last Inform</th>
                </tr>
            </thead>
<tbody>
    <?php if (empty($devices)): ?>
        <tr><td colspan="6" class="text-center">Koneksi ACS gagal atau data kosong.</td></tr>
    <?php else: ?>
        <?php foreach ($devices as $d): 
            // Pastikan konversi ke float bersih
            $rxValue = floatval($d['rx']);
            // Kriteria: di bawah -27 dBm (makin negatif makin buruk)
            $isBadRx = ($rxValue <= -27 && $rxValue != 0);
        ?>
        <tr class="acs-row" 
            data-online="<?= $d['isOnline'] ? 'yes' : 'no' ?>" 
            data-lowrx="<?= $isBadRx ? 'yes' : 'no' ?>">
            
            <td><code><?= $d['id'] ?></code><br><small class="text-muted"><?= $d['model'] ?></small></td>
            <td>
                <span class="status-dot <?= $d['isOnline'] ? 'bg-success' : 'bg-danger' ?>"></span>
                <?= $d['isOnline'] ? 'Online' : 'Offline' ?>
            </td>
            <td><?= $d['ip'] ?></td>
            <td><i class="fas fa-wifi text-info"></i> <?= $d['ssid'] ?></td>
            <td class="<?= $isBadRx ? 'rx-warning' : 'text-success' ?>">
                <?= $d['rx'] ?> dBm
            </td>
            <td><?= $d['lastInform'] ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</tbody>
        </table>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
function filterAcs(type) {
    const rows = document.querySelectorAll('.acs-row');
    rows.forEach(row => {
        if (type === 'all') {
            row.style.display = '';
        } else if (type === 'online') {
            row.style.display = row.dataset.online === 'yes' ? '' : 'none';
        } else if (type === 'offline') {
            row.style.display = row.dataset.online === 'no' ? '' : 'none';
        } else if (type === 'lowrx') {
            row.style.display = row.dataset.lowrx === 'yes' ? '' : 'none';
        }
    });
}
</script>
</body>
<?php
$content = ob_get_clean();
include dirname(__DIR__,2) . '/layouts/layout.php';