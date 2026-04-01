<?php
/**
 * Project: Doitwifi Management System
 * Modified by: Gemini AI
 * Date: 16 Maret 2026
 * Description: View untuk manajemen Client  Static MikroTik terintegrasi dengan database JSON.
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

    <meta charset="UTF-8">
    <title>Netwatch Monitoring | DoITWiFi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body.dark-theme { background-color: #0d1117; color: #c9d1d9; }
        .table-dark { background-color: #161b22; border: 1px solid #30363d; }
        .status-up { color: #2ea043; font-weight: bold; }
        .status-down { color: #ff4444; font-weight: bold; }
        .btn-xs { padding: 2px 5px; font-size: 11px; }
    </style>

<body class="dark-theme">



<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h4><i class="fas fa-microchip"></i> Monitoring Netwatch Pelanggan</h4>
        <button class="btn btn-outline-light btn-sm" onclick="location.reload()"><i class="fas fa-sync"></i> Refresh</button>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-hover table-sm">
            <thead>
                <tr>
                    <th>No</th>
                    <th>IP Host</th>
                    <th>Nama User</th>
                    <th>Update Terakhir</th>
                    <th>Status Real</th>
                    <th>Status Sistem</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($netwatch as $nw): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><code><?= $nw['host'] ?></code></td>
                    <td class="font-weight-bold"><?= $nw['nama'] ?></td>
                    <td><small><?= $nw['tanggal'] ?></small></td>
                    <td>
                        <?php if($nw['status'] == 'up'): ?>
                            <span class="status-up"><i class="fas fa-arrow-up"></i> UP</span>
                        <?php else: ?>
                            <span class="status-down"><i class="fas fa-arrow-down"></i> DOWN</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($nw['isIsolir']): ?>
                            <span class="badge badge-warning text-dark">TERISOLIR (i)</span>
                        <?php else: ?>
                            <span class="badge badge-success">AKTIF (!)</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="<?= BASE_URL ?>/mikrotik/netwatch_action?act=isolir&id=<?= $nw['id'] ?>&host=<?= $nw['host'] ?>&name=<?= urlencode($nw['nama']) ?>" 
                               class="btn btn-warning btn-xs <?= $nw['isIsolir'] ? 'disabled' : '' ?>">
                                <i class="fas fa-user-slash"></i> Isolir
                            </a>
                            <a href="<?= BASE_URL ?>/mikrotik/netwatch_action?act=auto&id=<?= $nw['id'] ?>&host=<?= $nw['host'] ?>&name=<?= urlencode($nw['nama']) ?>" 
                               class="btn btn-success btn-xs <?= !$nw['isIsolir'] ? 'disabled' : '' ?>">
                                <i class="fas fa-check"></i> Auto
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
<?php
$content = ob_get_clean();
include dirname(__DIR__,2) . '/layouts/layout.php';