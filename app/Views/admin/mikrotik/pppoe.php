<?php
/**
 * Project: Doitwifi Management System
 * Modified by: Gemini AI
 * Date: 16 Maret 2026
 * Description: View untuk manajeme ppp MikroTik terintegrasi dengan database JSON.
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
        .stat-card { cursor: pointer; transition: transform 0.2s; border: none; border-radius: 10px; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        
        /* Status Dot Styles */
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .bg-online { background-color: #2ea043; box-shadow: 0 0 8px #2ea043; }
        .bg-offline { background-color: #ff4444; box-shadow: 0 0 8px #ff4444; } /* Merah Terang */
        .bg-isolir { background-color: #f1c40f; box-shadow: 0 0 8px #f1c40f; }
        .bg-disabled { background-color: #8b949e; }

        .table-dark { background-color: #161b22; border: 1px solid #30363d; border-radius: 8px; overflow: hidden; }
        .user-row:hover { background-color: #21262d !important; }
        .btn-xs { padding: 2px 6px; font-size: 11px; }
        
        /* Custom Colors */
        .text-danger-bright { color: #ff4444 !important; }
        .text-isolir { color: #f1c40f !important; }
        code { color: #79c0ff; background: rgba(121, 192, 255, 0.1); padding: 2px 4px; border-radius: 4px; }
    </style>


<body class="dark-theme">
    <div class="row mb-4">
        <?php 
        $cards = [
            ['Online', $stats['active'], 'success', 'Active'],
            ['Offline', $stats['non_active'], 'danger', 'Non Active'],
            ['Isolir', $stats['isolir'], 'warning text-dark', 'ISOLIR'],
            ['Belum Bayar', $stats['belum_bayar'], 'secondary', 'Belum Bayar']
        ];
        foreach ($cards as $card): ?>
        <div class="col-md-3 col-6 mb-2">
            <div class="card bg-<?= $card[2] ?> stat-card" onclick="filterByStatus('<?= $card[3] ?>')">
                <div class="card-body p-3 text-center">
                    <small class="text-uppercase font-weight-bold" style="opacity:0.8"><?= $card[0] ?></small>
                    <h3 class="mb-0 font-weight-bold"><?= $card[1] ?></h3>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex justify-content-between mb-3">
        <div class="input-group w-75">
            <div class="input-group-prepend">
                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" id="searchInput" class="form-control bg-dark text-white border-secondary" placeholder="Cari nama, IP, atau komentar...">
        </div>
        <div class="btn-group ml-2">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalAddSecret" title="Tambah User">
                <i class="fas fa-plus"></i>
            </button>
            <button class="btn btn-outline-light" onclick="location.reload()" title="Refresh">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <div class="table-responsive shadow">
        <table id="userTable" class="table table-dark table-hover mb-0" style="font-size: 13px;">
            <thead style="background-color: #21262d;">
                <tr>
                    <th width="50">No</th>
                    <th>Status</th>
                    <th>Username</th>
                    <th>Profile</th>
                    <th>Remote IP</th>
                    <th>Last Logout</th>
                    <th class="text-center" width="180">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($secrets as $u): ?>
                <tr c<tr class="user-row" 
    data-online="<?= $u['isActive'] ? 'yes' : 'no' ?>" 
    data-isolir="<?= $u['isIsolir'] ? 'yes' : 'no' ?>" 
    data-bayar="<?= $u['isBelumBayar'] ? 'yes' : 'no' ?>">
                    <td><?= $no++ ?></td>
                    <td>
                        <?php if($u['isDisabled']): ?>
                            <span class="status-dot bg-disabled"></span> <small class="text-muted">DISABLED</small>
                        <?php elseif($u['isIsolir']): ?>
                            <span class="status-dot bg-isolir"></span> <small class="text-isolir font-weight-bold">ISOLIR</small>
                        <?php else: ?>
                            <span class="status-dot <?= $u['isActive'] ? 'bg-online' : 'bg-offline' ?>"></span> 
                            <small class="<?= $u['isActive'] ? 'text-success' : 'text-danger-bright font-weight-bold' ?>">
                                <?= $u['isActive'] ? 'Online' : 'Offline' ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td class="font-weight-bold <?php 
                        if($u['isIsolir']) echo 'text-isolir';
                        elseif($u['isActive']) echo 'text-info';
                        elseif($u['isDisabled']) echo 'text-muted';
                        else echo 'text-danger-bright'; 
                    ?>">
                        <?= $u['name'] ?>
                    </td>
                    <td><span class="badge badge-dark border border-secondary"><?= $u['profile'] ?></span></td>
                    <td><code><?= $u['ip'] ?: '-' ?></code></td>
                    <td>
                        <?php if($u['isBelumBayar']): ?>
                            <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> TAGIHAN</span>
                        <?php endif; ?>
                        <span class="text-muted"><?= htmlspecialchars($u['logout']) ?></span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group">
                            <button title="Isolir" onclick="handleAction('<?= $u['id'] ?>', 'isolir', '<?= $u['name'] ?>')" class="btn btn-warning btn-xs"><i class="fas fa-user-slash"></i></button>
                            <button title="Disable" onclick="handleAction('<?= $u['id'] ?>', 'disable', '<?= $u['name'] ?>')" class="btn btn-secondary btn-xs"><i class="fas fa-power-off"></i></button>
                            <button title="Buka/Aktifkan" onclick="handleAction('<?= $u['id'] ?>', 'open', '<?= $u['name'] ?>')" class="btn btn-success btn-xs"><i class="fas fa-check"></i></button>
                            <button title="Hapus" onclick="confirmDelete('<?= $u['id'] ?>', '<?= $u['name'] ?>')" class="btn btn-outline-danger btn-xs"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($secrets)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">Data tidak ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalAddSecret">
    <div class="modal-dialog">
        <form action="<?= BASE_URL ?>/mikrotik/pppoe/store" method="POST" class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Tambah User PPPoE</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-light">
                <div class="form-group"><label>Username</label><input type="text" name="name" class="form-control bg-secondary text-white border-0" required></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control bg-secondary text-white border-0" required></div>
                <div class="form-group">
                    <label>Profile</label>
                    <select name="profile" class="form-control bg-secondary text-white border-0">
                        <?php foreach($profiles as $p): ?>
                            <option value="<?= $p['name'] ?>"><?= $p['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Comment</label><input type="text" name="comment" class="form-control bg-secondary text-white border-0" placeholder="Contoh: 01/01/2026 | d"></div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="submit" class="btn btn-primary btn-block">Simpan Pelanggan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // 1. AUTO REFRESH (30 Detik)
        setInterval(function() {
            // Jangan refresh jika user sedang mengetik di search atau modal/alert terbuka
            if (!$('.modal').is(':visible') && !$('.swal2-container').is(':visible') && $('#searchInput').val() === "") {
                location.reload();
            }
        }, 30000);

        // 2. LIVE SEARCH
        $("#searchInput").on("keyup", function() {
            let val = $(this).val().toLowerCase();
            $(".user-row").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1)
            });
        });
    });

    // FILTER KLIK CARD
function filterByStatus(type) {
    // Bersihkan pencarian saat filter kartu diklik
    $("#searchInput").val(""); 
    
    $(".user-row").hide(); // Sembunyikan semua dulu

    switch(type) {
        case 'Active':
            $(".user-row[data-online='yes']").show();
            break;
        case 'Non Active':
            $(".user-row[data-online='no']").show();
            break;
        case 'ISOLIR':
            $(".user-row[data-isolir='yes']").show();
            break;
        case 'Belum Bayar':
            $(".user-row[data-bayar='yes']").show();
            break;
        default:
            $(".user-row").show();
    }
}

    // HANDLER AKSI
    function handleAction(id, act, name) {
        const titles = { 'isolir': 'Isolir User?', 'disable': 'Nonaktifkan User?', 'open': 'Aktifkan User Kembali?' };
        Swal.fire({
            title: titles[act],
            text: "Konfirmasi untuk pelanggan: " + name,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Lanjutkan!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "<?= BASE_URL ?>/mikrotik/pppoe/update_status?id=" + id + "&act=" + act;
            }
        });
    }

    // KONFIRMASI HAPUS
    function confirmDelete(id, name) {
        Swal.fire({
            title: 'Hapus Permanen?',
            text: "Data pelanggan " + name + " akan dihapus dari MikroTik!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "<?= BASE_URL ?>/mikrotik/pppoe/delete?id=" + id;
            }
        });
    }
</script>
<?php
$content = ob_get_clean();
include dirname(__DIR__,2) . '/layouts/layout.php';
