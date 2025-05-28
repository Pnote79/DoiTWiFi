<?php
session_start();
include("../class/mt_resources.php");

$sellerdata = json_decode(file_get_contents("../json/sellerdata.json"), true);

// Baca voucher log untuk cek last generate per seller
$voucherLogs = json_decode(file_get_contents("../json/voucherlog.json"), true);

$lastGenerate = [];
foreach ($voucherLogs as $log) {
    if (!isset($log['seller']) || !isset($log['date'])) continue;
    $seller = $log['seller'];
    $dateStr = $log['date'];
    $timestamp = strtotime($dateStr);
    if (!isset($lastGenerate[$seller]) || $timestamp > $lastGenerate[$seller]) {
        $lastGenerate[$seller] = $timestamp;
    }
}

// Hitung status seller berdasarkan last generate voucher (aktif jika generate dalam 1 jam terakhir)
$statusMap = [];
$now = time();
foreach ($sellerdata as $data) {
    if (!isset($data['profile']) || $data['profile'] !== 'seller') continue;
    $sellername = $data['sellername'];
    $lastTime = isset($lastGenerate[$sellername]) ? $lastGenerate[$sellername] : 0;
    if ($now - $lastTime <= 36) {
        $statusMap[$sellername] = 'active';
    } else {
        $statusMap[$sellername] = 'inactive';
    }
}

$id = 1;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Daftar Seller</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../css/style.css" />
  <style>
    body { background-color: #f8f9fa; }
    .card { margin-bottom: 20px; }
    .table td { vertical-align: middle; }
  </style>
</head>
<body>

<?php include('../page/navigation.php'); ?>

<?php if (isset($_SESSION['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
<?php endif; ?>

<div class="card mt-4">
  <div class="card">
    <div class="card-header bg-dark text-white">
      🧑‍ Daftar Seller
    
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-hover table-striped mb-0">
        <thead class="thead-dark">
          <tr>
            <th>No</th>
            <th>Aksi</th>
            <th>Nama</th>
            <th>No WA</th>
            <th>Voucher</th>
            <th>Balance</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sellerdata as $data): ?>
            <?php if ($data['profile'] !== 'seller') continue; ?>
            <?php
              $sellername = $data['sellername'];
              $countseller = 0;
              foreach ($mt_hotspotUser as $hotsell) {
                $parts = explode("|", $hotsell['comment']);
                if (isset($parts[1]) && trim($parts[1]) === $sellername) $countseller++;
              }
              $status = isset($statusMap[$sellername]) ? $statusMap[$sellername] : 'unknown';
              $badge = $status === 'active' ? 'success' : ($status === 'inactive' ? 'secondary' : 'warning');
            ?>
            <tr>
              <td><?= $id++ ?></td>
              <td><button class="btn btn-sm btn-info" data-toggle="modal" data-target="#editSellerModal<?= $data['id'] ?>">Edit</button></td>
              <td><?= htmlspecialchars($sellername) ?></td>
              <td><?= htmlspecialchars($data['sellerphone']) ?></td>
              <td><?= $countseller ?></td>
              <td><?= htmlspecialchars($data['sellerbalance']) ?></td>
              <td><span class="badge badge-<?= $badge ?>"><?= ucfirst($status) ?></span></td>
            </tr>

            <!-- Modal Edit Seller -->
            <div class="modal fade" id="editSellerModal<?= $data['id'] ?>" tabindex="-1" role="dialog">
              <div class="modal-dialog" role="document">
                <div class="modal-content bg-light">
                  <form action="../post/updateseller.php" method="POST">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Seller - <?= htmlspecialchars($sellername) ?></h5>
                      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="id" value="<?= $data['id'] ?>">
                      <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" name="sellername" value="<?= htmlspecialchars($sellername) ?>" required>
                      </div>
                      <div class="form-group">
                        <label>Password</label>
                        <input type="text" class="form-control" name="sellpass" required>
                      </div>
                      <div class="form-group">
                        <label>No WA</label>
                        <input type="text" class="form-control" name="sellerphone" value="<?= htmlspecialchars($data['sellerphone']) ?>" required>
                      </div>
                      <div class="form-group">
                        <label>Balance</label>
                        <input type="text" class="form-control" name="sellerbalance" value="<?= htmlspecialchars($data['sellerbalance']) ?>" required>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="submit" name="update" class="btn btn-success">Simpan</button>
                      <a href="deleteseller.php?id=<?= $data['id'] ?>" class="btn btn-danger">Hapus</a>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Add Seller -->
<div class="modal fade" id="addSellModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content bg-light">
      <form action="../post/addselldata.php" method="POST">
        <div class="modal-header">
          <h5 class="modal-title">➕ Tambah Seller</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Username Seller</label>
            <input type="text" class="form-control" name="sellname" required>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="text" class="form-control" name="sellpass" required>
          </div>
          <div class="form-group">
            <label>No WA</label>
            <input type="text" class="form-control" name="phone" required>
          </div>
          <div class="form-group">
            <label>Balance</label>
            <input type="text" class="form-control" name="sellerbalance" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">➕ Tambah</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal TopUp -->
<div class="modal fade" id="TopUpModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content bg-light">
      <form action="../post/topup.php" method="POST">
        <div class="modal-header">
          <h5 class="modal-title">💰 Top Up Seller</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Username Seller</label>
            <select class="form-control" name="sellerid" required onchange="updateTopupBalance(this)">
              <option value="">-- Pilih Seller --</option>
              <?php foreach ($sellerdata as $s): ?>
                <option value="<?= $s['id'] ?>" data-balance="<?= htmlspecialchars($s['sellerbalance']) ?>"><?= htmlspecialchars($s['sellername']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Saldo Saat Ini</label>
            <input type="text" class="form-control" id="topup_balance" readonly>
          </div>
          <div class="form-group">
            <label>Jumlah TopUp</label>
            <select class="form-control" name="topup" required>
              <option value="">-- Pilih --</option>
              <option value="50000">Rp 50.000</option>
              <option value="100000">Rp 100.000</option>
              <option value="150000">Rp 150.000</option>
              <option value="200000">Rp 200.000</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Top Up</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- JS -->
<script>
function updateTopupBalance(select) {
  const balance = select.options[select.selectedIndex].getAttribute('data-balance');
  document.getElementById('topup_balance').value = balance || '';
}
</script>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
