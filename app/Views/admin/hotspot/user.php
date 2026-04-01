<?php
/**
 * Project: Doitwifi Management System
 * Modified by: Gemini AI
 * Date: 16 Maret 2026
 * Description: View untuk manajemen profile hotspot MikroTik terintegrasi dengan database JSON.
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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #1a1d20; color: #e0e0e0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; }
        .filter-box { background: #25282c; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #333; }
        .form-control-sm { background: #2b3035; border: 1px solid #444; color: white; height: 30px; font-size: 12px; }
        
        /* Compact Table Styling */
        .table-dark { background-color: #25282c; border-radius: 8px; overflow: hidden; border: none; }
        .table-dark thead th { border-bottom: 2px solid #333; padding: 8px; font-size: 11px; text-transform: uppercase; }
        .table-dark td { border-top: 1px solid #333; padding: 6px 8px !important; vertical-align: middle !important; }
        
        .vc-row { cursor: pointer; transition: 0.1s; }
        .vc-row:hover { background-color: #2c3136 !important; }
        
        /* Status Badges Compact */
        .badge { padding: 3px 6px; font-size: 10px; min-width: 55px; }
        .bg-aktip { background-color: #28a745 !important; }
        .bg-ready { background-color: #6c757d !important; }
        .bg-disabled { background-color: #dc3545 !important; }
        
        .user-code { font-size: 13px; font-weight: bold; color: #17a2b8; line-height: 1.2; }
        .sub-info { font-size: 10px; color: #888; display: block; }
        .text-interface { color: #fd7e14; font-size: 10px; font-weight: bold; }
        
        /* SweetAlert Custom Dark */
        .swal2-popup { font-size: 0.85rem !important; }
    </style>

<div class="container-fluid mt-4">
    <div class="filter-box">
        <div class="row">
            <div class="col-md-3 col-6 mb-2">
                <label class="small text-muted">Cari User/Voucher</label>
                <input type="text" id="vcInput" onkeyup="applyFilters()" class="form-control form-control-sm" placeholder="Ketik kode voucher...">
            </div>
            <div class="col-md-3 col-6 mb-2">
                <label class="small text-muted">Filter Seller</label>
                <select id="filterSeller" onchange="applyFilters()" class="form-control form-control-sm">
                    <option value="">Semua Seller</option>
                    <?php foreach($uniqueSellers as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <label class="small text-muted">Filter Profile</label>
                <select id="filterProfile" onchange="applyFilters()" class="form-control form-control-sm">
                    <option value="">Semua Profile</option>
                    <?php if(!empty($getProfile)): foreach($getProfile as $p): ?>
                        <option value="<?= htmlspecialchars($p['name']) ?>"><?= $p['name'] ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <label class="small text-muted">Status</label>
                <select id="filterStatus" onchange="applyFilters()" class="form-control form-control-sm">
                    <option value="">Semua Status</option>
                    <option value="Aktip">Aktip</option>
                    <option value="Ready">Ready</option>
                    <option value="Disabled">Disabled</option>
                </select>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-hover" id="vcTable">
            <thead>
                <tr class="text-muted small uppercase">
                    <th>Status</th>
                    <th>Voucher Info</th>
                    <th>Profile</th>
                    <th>Seller</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($historyVoucher)): foreach($historyVoucher as $v): 
                    // Tentukan class badge berdasarkan status
                    $badgeClass = 'bg-ready';
                    if($v['status'] == 'Aktip') $badgeClass = 'bg-aktip';
                    if($v['status'] == 'Disabled') $badgeClass = 'bg-disabled';
                ?>
                <tr class="vc-row" 
                    data-seller="<?= htmlspecialchars(trim($v['seller'])) ?>" 
                    data-profile="<?= htmlspecialchars(trim($v['profile'])) ?>" 
                    data-status="<?= $v['status'] ?>"
                    onclick='showPopup(<?= json_encode($v) ?>)'>
                    
                    <td>
                        <span class="badge <?= $badgeClass ?>"><?= $v['status'] ?></span>
                    </td>
                    <td>
                        <div class="user-code"><?= $v['name'] ?></div>
                    </td>
                    <td>
                        <div class="small fw-bold text-light"><?= $v['profile'] ?></div>
                    </td>
                    <td>
                        <div class="small text-muted"><?= $v['seller'] ?></div>
                    </td>
                    <td class="text-right px-4">

                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" class="text-center py-4">Tidak ada data voucher ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/** * Fungsi Filter Client-Side
 * Mencocokkan data- atribut pada baris <tr>
 */
function applyFilters() {
    const search = $("#vcInput").val().toLowerCase().trim();
    const seller = $("#filterSeller").val();
    const profile = $("#filterProfile").val();
    const status = $("#filterStatus").val();

    $(".vc-row").each(function() {
        const row = $(this);
        const rowSeller = String(row.data('seller') || "");
        const rowProfile = String(row.data('profile') || "");
        const rowStatus = String(row.data('status') || "");
        
        const textMatch = search === "" || row.text().toLowerCase().indexOf(search) > -1;
        const sellerMatch = seller === "" || rowSeller === seller;
        const profileMatch = profile === "" || rowProfile === profile;
        const statusMatch = status === "" || rowStatus === status;

        if (textMatch && sellerMatch && profileMatch && statusMatch) {
            row.show();
        } else {
            row.hide();
        }
    });
}

/** * Modal Detail SweetAlert2
 */
function showPopup(v) {
    let statusColor = '#6c757d';
    if(v.status === 'Aktip') statusColor = '#28a745';
    if(v.status === 'Disabled') statusColor = '#dc3545';

    Swal.fire({
        title: `<span style="color:#17a2b8">Detail User: ${v.name}</span>`,
        background: '#25282c',
        color: '#fff',
        width: '380px',
        html: `
            <div class="text-left" style="font-size:13px; line-height:1.6">
                <div class="d-flex justify-content-between border-bottom border-secondary pb-1 mb-1">
                    <span>Status:</span><b style="color:${statusColor}">${v.status}</b>
                </div>
                <div class="d-flex justify-content-between"><span>Profile:</span><b>${v.profile}</b></div>
                <div class="d-flex justify-content-between"><span>Seller:</span><b>${v.seller}</b></div>
                <div class="d-flex justify-content-between"><span>Tgl Generate:</span><span>${v.generate}</span></div>
                
                <div class="mt-2 p-2 bg-dark rounded border border-secondary">
                    <div class="small text-muted">Informasi Koneksi:</div>
                    <div style="font-size:11px" class="text-info">
                        <i class="fa fa-link"></i> ${v.via || 'Direct'} 
                        <span class="text-white mx-1">|</span> 
                        <i class="fa fa-clock"></i> ${v.login}
                    </div>
                </div>

                <div class="mt-2 p-2 bg-dark rounded border border-danger">
                    <div class="small text-muted">Masa Aktif (Expired):</div>
                    <b class="text-danger">${v.exp}</b>
                </div>

                <div class="mt-2 small">
                    <div class="d-flex justify-content-between"><span>MAC Address:</span><span class="text-info font-weight-bold">${v.mac}</span></div>
                    <div class="d-flex justify-content-between"><span>IP Address:</span><span>${v.ip}</span></div>
                    <div class="d-flex justify-content-between"><span>Nama Perangkat:</span><span class="text-truncate" style="max-width:160px">${v.hostname}</span></div>
                    <div class="d-flex justify-content-between mt-1 pt-1 border-top border-secondary">
                        <span>Sisa Kuota:</span><b class="text-warning">${v.sisa}</b>
                    </div>
                    <div class="d-flex justify-content-between"><span>Uptime:</span><b>${v.uptime}</b></div>
                </div>

                <div class="mt-4 row no-gutters">
                    <div class="col-6 p-1"><button onclick="confirmAction('unmac','${v.id}')" class="btn btn-sm btn-outline-info btn-block">Reset MAC</button></div>
                    <div class="col-6 p-1"><button onclick="confirmAction('reset','${v.id}')" class="btn btn-sm btn-outline-warning btn-block">Reset Count</button></div>
                    <div class="col-6 p-1">
                        ${v.status === 'Disabled' 
                            ? `<button onclick="confirmAction('enable','${v.id}')" class="btn btn-sm btn-success btn-block">Enable</button>` 
                            : `<button onclick="confirmAction('disable','${v.id}')" class="btn btn-sm btn-secondary btn-block">Disable</button>`
                        }
                    </div>
                    <div class="col-6 p-1"><button onclick="confirmAction('delete','${v.id}')" class="btn btn-sm btn-danger btn-block">Hapus</button></div>
                </div>
            </div>
        `,
        showConfirmButton: false,
        showCloseButton: true
    });
}

/** * Konfirmasi Aksi
 */
function confirmAction(act, id) {
    let title = 'Konfirmasi';
    let icon = 'question';
    
    if(act === 'delete') { title = 'Hapus Voucher?'; icon = 'error'; }
    if(act === 'unmac') { title = 'Reset Lock MAC?'; }

    Swal.fire({
        title: title,
        text: "Apakah Anda yakin ingin melakukan tindakan ini?",
        icon: icon,
        showCancelButton: true,
        background: '#25282c',
        color: '#fff',
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#444',
        confirmButtonText: 'Ya, Jalankan!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `?action=${act}&id=${id}`;
        }
    });
}
</script>
<?php
$content = ob_get_clean();
include dirname(__DIR__,2) . '/layouts/layout.php';