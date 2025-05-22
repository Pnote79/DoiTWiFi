<?php
session_start();
include("../class/mt_resources.php");
$logFile = "../json/voucherlog.json";
$logData = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];

// Ambil daftar seller unik dari log
$sellers = array_unique(array_map(function($entry) {
    return $entry['seller'];
}, $logData));
sort($sellers);

// Filter berdasarkan seller (dari dropdown)
$filterSeller = $_GET['seller'] ?? '';
$filteredLogs = $filterSeller ? array_filter($logData, fn($log) => $log['seller'] === $filterSeller) : $logData;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Riwayat Generate Voucher</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css" >
  <style>
    body { font-family: Verdana; background-color: #f5f5f5; }
    .container { margin-top: 30px; }
    .badge-code {
        font-family: monospace;
        font-size: 13px;
        margin: 2px;
    }
  </style>
</head>
<body>
<?php include('navigation.php'); ?>
<div class="container">
  <h4 class="mb-4">📄 Riwayat Generate Voucher</h4>

  <form method="get" class="form-inline mb-3">
    <label class="mr-2">Filter Seller:</label>
    <select name="seller" class="form-control mr-2" onchange="this.form.submit()">
      <option value="">Semua Seller</option>
      <?php foreach ($sellers as $seller): ?>
        <option value="<?= htmlspecialchars($seller) ?>" <?= $filterSeller === $seller ? 'selected' : '' ?>>
          <?= htmlspecialchars($seller) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if ($filterSeller): ?>
      <a href="voucherlog.php" class="btn btn-secondary btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered table-sm table-hover bg-white">
      <thead class="thead-dark">
        <tr>
          <th>Tanggal</th>
          <th>Seller</th>
          <th>Paket</th>
          <th>Harga</th>
          <th>Jumlah</th>
          <th>Total Biaya</th>
          <th>Voucher</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($filteredLogs)): ?>
          <tr><td colspan="10" class="text-center text-muted">Tidak ada data.</td></tr>
        <?php else: ?>
          <?php foreach (array_reverse($filteredLogs) as $log): ?>
          <tr>
            <td><?= htmlspecialchars($log['date']) ?></td>
            <td><?= htmlspecialchars($log['seller']) ?></td>
            <td><?= htmlspecialchars($log['profile']) ?></td>
            <td>Rp<?= number_format($log['amount'], 0, ',', '.') ?></td>
            <td><?= $log['qty'] ?></td>
            <td>Rp<?= number_format($log['cost'], 0, ',', '.') ?></td>
            <td>
              <?php foreach ($log['codes'] as $code): ?>
                <span class="badge badge-light badge-code"><?= htmlspecialchars($code) ?></span>
              <?php endforeach; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
