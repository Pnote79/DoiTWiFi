<?php
/**
 * Project: Doitwifi Management System
 * Modified by: Gemini AI
 * Date: 16 Maret 2026
 * Description: View manajemen profile hotspot MikroTik dengan Full Dark Theme.
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
    /* Global & Table Dark Style */
    body { background-color: #121212; color: #e0e0e0; }
    .dark-theme { background: #121212; min-height: 100vh; padding-top: 10px; }
    .card-dark { background-color: #1a1c1e; border: 1px solid #343a40; color: #fff; }
    .table-dark-custom { background-color: #1a1c1e !important; }
    .table-dark-custom thead th { border-bottom: 2px solid #343a40; background-color: #25282c; color: #17a2b8; }
    .table-dark-custom td { border-top: 1px solid #2d3135; vertical-align: middle; }
    .text-info-custom { color: #17a2b8 !important; }

    /* Modal Dark Theme */
    .modal-content-dark {
        background-color: #1a1c1e !important;
        color: #e1e1e1;
        border: 1px solid #444;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .modal-header, .modal-footer { border-color: #343a40 !important; }
    
    /* Form Input Dark Style */
    .form-control-dark {
        background-color: #2b2d30 !important;
        border: 1px solid #495057 !important;
        color: #fff !important;
    }
    .form-control-dark:focus {
        background-color: #323539 !important;
        border-color: #17a2b8 !important;
        color: #fff !important;
        box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
    }
    .form-control-dark::placeholder { color: #6c757d; }
    label { font-size: 0.85rem; color: #adb5bd; margin-bottom: 4px; }
    
    /* Custom Scrollbar for Dark Theme */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #1a1c1e; }
    ::-webkit-scrollbar-thumb { background: #343a40; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #454d55; }
</style>

<div class="container-fluid dark-theme">
    <?php if(isset($_SESSION['msg'])): ?>
        <script>
            window.onload = function() {
                Swal.fire({ 
                    icon: 'success', 
                    title: 'Berhasil', 
                    text: '<?= $_SESSION['msg'] ?>', 
                    timer: 2000, 
                    background: '#1a1c1e', 
                    color: '#fff',
                    showConfirmButton: false
                });
            }
        </script>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <div class="card card-dark shadow-lg">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 text-info-custom font-weight-bold">
                <i class="fa fa-layer-group mr-2"></i> PROFILE HOTSPOT
            </h6>
            <button class="btn btn-sm btn-primary px-3 shadow-sm" onclick="addProfile()">
                <i class="fa fa-plus-circle mr-1"></i> Tambah Profile
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 text-center table-dark-custom">
                    <thead>
                        <tr>
                            <th class="text-left">Profile MikroTik</th>
                            <th>Masa Aktif</th>
                            <th>Harga (Jual/Modal)</th>
                            <th>Data / Shared</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($getprofile as $p): 
                            $jr = array_filter($rateData, function($r) use ($p) { 
                                return isset($r['profile']) && $r['profile'] === $p['name']; 
                            });
                            $jr = reset($jr) ?: [];
                        ?>
                        <tr>
                            <td class="text-left text-info-custom font-weight-bold">
                                <?= $p['name'] ?>
                                <small class="d-block text-success" style="font-size: 10px;"><?= $p['rate-limit'] ?? ($jr['p_simple'] ?? '-') ?></small>
                            </td>
                            <td><span class="badge badge-secondary px-2 py-1"><?= $jr['name'] ?? 'Unlimited' ?></span></td>
                            <td>
                                 <code class="d-block text-white" style="font-size: 12px;">Rp <?= number_format($jr['amount'] ?? 0, 0, ',', '.') ?></code>
                                 <small class="text-success font-italic">Untung: Rp <?= number_format(($jr['amount'] ?? 0) - ($jr['margine'] ?? 0), 0, ',', '.') ?></small>
                            </td>
                            <td>
                                 <code class="d-block text-warning"><?= (!empty($jr['limitbytes']) && $jr['limitbytes'] !== "0") ? $jr['limitbytes'] . " GB" : "Unlimited" ?></code>
                                 <small class="text-muted"><i class="fa fa-users mr-1"></i><?= $p['shared-users'] ?? '1' ?> User</small>
                            </td>
                            <td>
                                <button class="btn btn-xs btn-outline-info mr-1" title="Edit" onclick="editProfile(<?= htmlspecialchars(json_encode($jr)) ?>, <?= htmlspecialchars(json_encode($p)) ?>)">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="btn btn-xs btn-outline-danger" title="Hapus" onclick="confirmDel('<?= $p['.id'] ?>', '<?= $p['name'] ?>')">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProfile" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md shadow">
        <form action="<?= BASE_URL ?>/hotspot/save_profile" method="POST">
            <div class="modal-content modal-content-dark">
                <div class="modal-header py-2">
                    <h6 class="modal-title font-weight-bold text-info-custom" id="modalTitle">Form Profile</h6>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body py-3">
                    <input type="hidden" name="p_id" id="p_id">
                    <input type="hidden" name="save_profile" value="1">
                    
                    <div class="row">
                        <div class="col-12 form-group mb-2">
                            <label><i class="fa fa-tag mr-1 text-primary"></i> Nama Voucher (Tampilan)</label>
                            <input type="text" name="p_name" id="p_name" class="form-control form-control-dark" placeholder="Contoh: 2 JAM 2K" required>
                        </div>
                        <div class="col-6 form-group mb-2">
                            <label><i class="fa fa-microchip mr-1 text-primary"></i> Nama Profile (MikroTik)</label>
                            <input type="text" name="p_profile" id="p_profile" class="form-control form-control-dark" placeholder="Tanpa Spasi" required>
                        </div>
                        <div class="col-6 form-group mb-2">
                            <label><i class="fa fa-tachometer-alt mr-1 text-primary"></i> Simple Queue</label>
                            <input type="text" name="p_simple" id="p_simple" class="form-control form-control-dark" placeholder="5M/10M ...">
                        </div>

                        <div class="col-6 form-group mb-2">
                            <label><i class="fa fa-network-wired mr-1 text-primary"></i> Address Pool</label>
                            <select name="ppool" id="ppool" class="form-control form-control-dark">
                                <option value="none">none</option>
                                <?php foreach($getpool as $pool): ?>
                                    <option value="<?= $pool['name'] ?>"><?= $pool['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 form-group mb-2">
                            <label><i class="fa fa-key mr-1 text-primary"></i> Panjang Voucher</label>
                            <input type="number" name="p_length" id="p_length" class="form-control form-control-dark" value="8">
                        </div>

                        <div class="col-6 form-group mb-2">
                            <label><i class="fa fa-clock mr-1 text-primary"></i> Masa Aktif</label>
                            <input type="text" name="p_validity" id="p_validity" class="form-control form-control-dark" placeholder="30d" required>
                        </div>
                        <div class="col-6 form-group mb-2">
                            <label><i class="fa fa-sign-out-alt mr-1 text-primary"></i> Expired Mode</label>
                            <select name="expmode" id="expmode" class="form-control form-control-dark">
                                <option value="rem">Remove</option>
                                <option value="ntf">Notice</option>
                                <option value="remc">Remove & Record</option>
                                <option value="0">None</option>
                            </select>
                        </div>

                        <div class="col-4 form-group mb-2">
                            <label><i class="fa fa-money-bill-wave mr-1 text-primary"></i> Harga Jual</label>
                            <input type="number" name="p_price" id="p_price" class="form-control form-control-dark" required>
                        </div>
                        <div class="col-4 form-group mb-2">
                            <label><i class="fa fa-wallet mr-1 text-primary"></i> Modal</label>
                            <input type="number" name="p_margin" id="p_margin" class="form-control form-control-dark" value="0">
                        </div>
                        <div class="col-4 form-group mb-2">
                            <label><i class="fa fa-database mr-1 text-primary"></i> Kuota (GB)</label>
                            <input type="text" name="p_limitb" id="p_limitb" class="form-control form-control-dark" placeholder="0">
                        </div>

                        <div class="col-12 form-group mb-0">
                            <label><i class="fa fa-lock mr-1 text-primary"></i> Lock User (MAC Binding)</label>
                            <select name="lockunlock" id="lockunlock" class="form-control form-control-dark">
                                <option value="Disable">Disable (Bisa Ganti HP)</option>
                                <option value="Enable">Enable (Kunci MAC Address)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm text-white px-3" data-dismiss="modal">BATAL</button>
                    <button type="submit" name="save_profile" class="btn btn-primary btn-sm px-4 font-weight-bold">SIMPAN DATA</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function addProfile() {
    $('#modalTitle').html('<i class="fa fa-plus-circle text-primary mr-2"></i>Tambah Profile Baru');
    $('#p_id').val('');
    $('#p_name').val('');
    $('#p_profile').val('').attr('readonly', false);
    $('#p_price').val('0');
    $('#p_margin').val('0');
    $('#p_validity').val('30d');
    $('#p_limitb').val('0');
    $('#p_length').val('8');
    $('#p_simple').val('');
    $('#modalProfile').modal('show');
}

function editProfile(json_data, mt_data) {
    $('#modalTitle').html('<i class="fa fa-edit text-info mr-2"></i>Edit Profile: ' + mt_data.name);
    $('#p_id').val(json_data.id || ''); 
    $('#p_name').val(json_data.name || mt_data.name);
    $('#p_price').val(json_data.amount || '0');
    $('#p_margin').val(json_data.margine || '0');
    $('#p_validity').val(json_data.validity || '30d');
    $('#p_limitb').val(json_data.limitbytes || '0');
    $('#p_length').val(json_data.length || '8');
    $('#p_simple').val(json_data.p_simple || mt_data['rate-limit'] || '');
    $('#p_profile').val(mt_data.name || '').attr('readonly', true);
    
    if (mt_data['address-pool']) {
        $('#ppool').val(mt_data['address-pool']);
    } else {
        $('#ppool').val('none');
    }
    $('#modalProfile').modal('show');
}

function confirmDel(id, name) {
    Swal.fire({
        title: 'Hapus Profile?',
        text: "Profile " + name + " akan dihapus dari MikroTik & Database!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        background: '#1a1c1e', 
        color: '#fff'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "<?= BASE_URL ?>/hotspot/profile?del=" + id + "&name=" + name;
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include dirname(__DIR__,2) . '/layouts/layout.php';