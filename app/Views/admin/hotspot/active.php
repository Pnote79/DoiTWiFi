<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/login");
    exit;
}
?>

    <style>
        body { background-color: #0d1117; color: #c9d1d9; }
        .table-dark-custom { background-color: #161b22; border: 1px solid #30363d; color: #c9d1d9; font-size: 0.85rem; }
        .bg-darker { background-color: #010409 !important; }
        .badge-outline { border: 1px solid #30363d; color: #58a6ff; background: transparent; }
        .btn-kick { border-radius: 20px; font-size: 0.75rem; transition: 0.3s; }
        .btn-kick:hover { background-color: #f85149; color: white; }
    </style>


<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-bolt text-warning mr-2"></i> Hotspot Active</h4>
        <span class="badge badge-pill badge-primary p-2">
            <i class="fas fa-users mr-1"></i> <?= count($mt_hotspotUserActive) ?> Online
        </span>
    </div>

    <div class="table-responsive shadow-sm rounded">
        <table class="table table-dark-custom table-hover">
            <thead class="bg-darker">
                <tr class="text-uppercase small">
                    <th>#</th>
                    <th>User / Profile</th>
                    <th>Network Info</th>
                    <th>Device Info</th>
                    <th>Traffic</th>
                    <th>Login Details</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($mt_hotspotUserActive)): ?>
                    <?php $no = 1; foreach($mt_hotspotUserActive as $hs): 
                        $user = $hs['user'] ?? '-';
                        $mac  = $hs['mac-address'] ?? '-';
                        $profile = $user_map[$user] ?? 'N/A';
                        $hostname = $lease_map[$mac] ?? 'Unknown Device';
                        $loginDate = $script_map[$user] ?? '<span class="text-muted">No Data</span>';
                        
                        // Konversi Byte ke MB
                        $down = round(($hs['bytes-in'] / 1048576), 2);
                        $up   = round(($hs['bytes-out'] / 1048576), 2);
                    ?>
                    <tr>
                        <td class="text-muted"><?= $no++ ?></td>
                        <td>
                            <strong class="text-warning d-block"><?= $user ?></strong>
                            <span class="badge badge-outline mt-1"><?= $profile ?></span>
                        </td>
                        <td>
                            <code class="text-info"><?= $hs['address'] ?? '-' ?></code>
                            <small class="d-block text-muted"><?= $hs['server'] ?? '-' ?></small>
                        </td>
                        <td>
                            <small class="text-white d-block"><?= $mac ?></small>
                            <small class="text-success italic"><?= $hostname ?></small>
                        </td>
                        <td>
                            <div class="mb-1"><i class="fas fa-download text-info mr-1"></i> <?= $down ?> MB</div>
                            <div><i class="fas fa-upload text-success mr-1"></i> <?= $up ?> MB</div>
                        </td>
                        <td>
                            <div class="small text-muted mb-1" title="Comment">
                                <i class="fas fa-comment-alt mr-1"></i> <?= $hs['comment'] ?? '-' ?>
                            </div>
                            <div class="small text-info">
                                <i class="fas fa-history mr-1"></i> <?= $loginDate ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <a href="?remove_active=<?= $hs['.id'] ?>" 
                               class="btn btn-outline-danger btn-kick"
                               onclick="return confirm('Putuskan koneksi <?= $user ?>?')">
                                <i class="fas fa-power-off mr-1"></i> Kick
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada user yang login.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<?php
$content = ob_get_clean();
include dirname(__DIR__,2) . '/layouts/layout.php';