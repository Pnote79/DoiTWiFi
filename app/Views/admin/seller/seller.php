<?php
/**
 * =========================================================
 * Nama File    : seller.php 
 * Project      : KWHotspot - doitwifi support Gemini GPT
 * Tanggal Buat : 25 Maret 2026
 * Deskripsi    : Managenent Seller Voucher
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
<style>
/* Wrapper tombol */
.action-buttons {
    display: flex;
    gap: 10px;
}

/* Style tombol modern */
.action-buttons .btn {
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.25);
}

/* Hover effect */
.action-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.35);
}

/* Tombol tambah */
.btn-success {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    border: none;
}

/* Tombol topup */
.btn-warning {
    background: linear-gradient(135deg, #f39c12, #e67e22);
    border: none;
    color: white;
}

/* ================= MOBILE ================= */
@media (max-width: 576px) {

    .card-header {
        flex-direction: column;
        align-items: stretch !important;
        gap: 12px;
    }

    .action-buttons {
        width: 100%;
    }

    .action-buttons .btn {
        flex: 1;
        font-size: 0.85rem;
        padding: 10px;
    }

    /* Icon lebih dominan di HP */
    .action-buttons .btn span {
        display: none;
    }
}
</style>

<div class="container-fluid mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">🧑‍💼 Daftar Seller</h5>
            <div class="action-buttons">
                <button class="btn btn-sm btn-success px-3" data-toggle="modal" data-target="#addSellModal">➕ Tambah</button>
                <button class="btn btn-sm btn-warning px-3" data-toggle="modal" data-target="#TopUpModal">💰 Topup</button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">No</th>
                        <th>Nama Seller</th>
                        <th>No WA</th>
                        <th>Saldo Sekarang</th>
                        <th>Topup Bln Ini</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($finalSellers)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data seller.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($finalSellers as $s): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                            <td><?= htmlspecialchars($s['phone']) ?></td>
                            <td class="text-primary font-weight-bold">Rp <?= number_format($s['balance'], 0, ',', '.') ?></td>
                            <td class="text-success font-weight-bold">Rp <?= number_format($s['topup_month'], 0, ',', '.') ?></td>
                            <td>
                                <span class="badge badge-<?= $s['status'] == 'active' ? 'success' : 'secondary' ?>">
                                    <?= strtoupper($s['status']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#editSellerModal<?= $s['id'] ?>">Edit</button>
                                <a href="<?= BASE_URL ?>/seller/delete?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($finalSellers)): ?>
    <?php foreach ($finalSellers as $s): ?>
    <div class="modal fade" id="editSellerModal<?= $s['id'] ?>" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="<?= BASE_URL ?>/seller/update" method="POST" class="modal-content">
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title">Edit Seller: <?= htmlspecialchars($s['name']) ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" name="sellername" value="<?= htmlspecialchars($s['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Ganti Password <small class="text-muted">(Biarkan kosong jika tidak diubah)</small></label>
                        <input type="password" class="form-control" name="sellpass" placeholder="Password baru...">
                    </div>
                    <div class="form-group">
                        <label>No WhatsApp</label>
                        <input type="text" class="form-control" name="sellerphone" value="<?= htmlspecialchars($s['phone']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Saldo</label>
                        <input type="number" class="form-control" name="sellerbalance" value="<?= $s['balance'] ?>">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info px-4">Update Data</button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="modal fade" id="addSellModal" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="<?= BASE_URL ?>/seller/store" method="POST" class="modal-content">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title">➕ Tambah Seller Baru</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" class="form-control" name="sellname" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" class="form-control" name="sellpass" required>
                </div>
                <div class="form-group">
                    <label>No WhatsApp</label>
                    <input type="text" class="form-control" name="phone" placeholder="628xxxx" required>
                </div>
                <div class="form-group">
                    <label>Saldo Awal</label>
                    <input type="number" class="form-control" name="sellerbalance" value="0">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-success btn-block">Simpan Seller</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="TopUpModal" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="<?= BASE_URL ?>/seller/topup" method="POST" class="modal-content">
            <div class="modal-header bg-warning border-0">
                <h5 class="modal-title">💰 Top Up Saldo</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Pilih Seller</label>
                    <select class="form-control" name="sellerid" id="select_seller_topup" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach ($finalSellers as $s): ?>
                            <option value="<?= $s['id'] ?>" data-balance="<?= $s['balance'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Saldo Saat Ini</label>
                    <input type="text" class="form-control" id="topup_balance_display" readonly>
                </div>
                <div class="form-group">
                    <label>Nominal TopUp</label>
                     <select class="form-control" name="topup" required>
                        <option value="">-- Pilih atau Input Nominal --</option>
                        <option value="50000">50.000</option>
                        <option value="100000">100.000</option>
                        <option value="150000">150.000</option>
                        <option value="200000">200.000</option>
                     </select>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-warning btn-block font-weight-bold">PROSES TOP UP</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Memperbaiki masalah modal yang tidak bisa diketik karena tumpang tindih backdrop
    $('.modal').on('shown.bs.modal', function() {
        $(this).find('input:first').focus();
    });

    $('#select_seller_topup').on('change', function() {
        const balance = $(this).find(':selected').data('balance');
        $('#topup_balance_display').val(balance !== undefined ? new Intl.NumberFormat('id-ID').format(balance) : '');
    });

    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        Swal.fire({
            title: 'Hapus Seller?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = href;
        });
    });

    <?php if (isset($_SESSION['success'])): ?>
        Swal.fire({ icon: 'success', title: 'Berhasil', text: '<?= $_SESSION['success'] ?>', timer: 2000, showConfirmButton: false });
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
});
</script>
<?php
$content = ob_get_clean();
include dirname(__DIR__,2) . '/layouts/layout.php';