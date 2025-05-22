<!--============v.0.1=============-->
<!--18-Mey-2025 by KWHotspot-->
<?php
session_start();
include("../class/mt_resources.php");

$logPath = "../json/topup_log.json";
$log = file_exists($logPath) ? json_decode(file_get_contents($logPath), true) : [];

$currentUser = $_SESSION['username'] ?? ($_SESSION['sell'] ?? '');
if (!isset($_SESSION['role'])) $_SESSION['role'] = 'seller'; // Default role

$isAdmin = $_SESSION['role'] === 'admin';

// Filter log sesuai role
$filteredLog = $isAdmin
    ? $log
    : array_filter($log, fn($entry) => $entry['sellername'] === $currentUser);

// Ambil username unik dari filtered log
$usernames = array_unique(array_column($filteredLog, 'sellername'));

// Ambil data Mikrotik
$getIncomeRaw = $API->comm("/system/script/print");
$getIncome = json_decode(json_encode($getIncomeRaw), true);

$sumDaily = 0;
$sumMonthly = 0;
$sumOverall = 0;
$HariIni = strtolower(date("M/d/Y"));
$bulanIni = strtolower(date("M"));
$sellerbalance = 0;
$weeklyData = [];

for ($i = 6; $i >= 0; $i--) {
    $dateKey = strtolower(date("M/d/Y", strtotime("-$i days")));
    $weeklyData[$dateKey] = 0;
}

// Ambil saldo seller dari JSON
$sellerdata = "../json/sellerdata.json";
$sellerList = file_exists($sellerdata) ? json_decode(file_get_contents($sellerdata), true) : [];

foreach ($sellerList as $s) {
    if (isset($s['sellername']) && $s['sellername'] == $currentUser) {
        $sellerbalance = isset($s['sellerbalance']) ? $s['sellerbalance'] : 0;
        break;
    }
}

// Hitung income
foreach ($getIncome as $script) {
    if (!isset($script['name'])) continue;
    if (strpos($script['name'], "-|-") === false) continue;

    $parts = explode("-|-", $script['name']);
    if (!isset($parts[8])) continue;

    $commentFields = explode("|", $parts[8]);
    if (count($commentFields) < 4) continue;

    $userseller = trim($commentFields[1]);
    $amountseller = (float) trim($commentFields[2]);
    $tanggalTransaksi = strtolower(trim($commentFields[3]));

    if (!$isAdmin && $currentUser !== $userseller) continue;
    if ($amountseller <= 0) continue;

    $sumOverall += $amountseller;

    if ($tanggalTransaksi === $HariIni) {
        $sumDaily += $amountseller;
    }

    if (strpos($tanggalTransaksi, $bulanIni) !== false) {
        $sumMonthly += $amountseller;
    }

    if (isset($weeklyData[$tanggalTransaksi])) {
        $weeklyData[$tanggalTransaksi] += $amountseller;
    }
}

$voucherLogPath = "../json/voucherlog.json";
$logData = file_exists($voucherLogPath) ? json_decode(file_get_contents($voucherLogPath), true) : [];
$filterSeller = $isAdmin ? ($_GET['seller'] ?? '') : $currentUser;
$sellers = $isAdmin ? array_unique(array_column($logData, 'seller')) : [];
sort($sellers);
$filteredLogs = $filterSeller ? array_filter($logData, fn($log) => $log['seller'] === $filterSeller) : $logData;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>📊 Income Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { background-color: #f8f9fa; }
    .card { margin-bottom: 20px; }
    .table td { vertical-align: middle; }
  </style>
</head>
<body>
<?php include('navigation.php'); ?>
<div class="container mt-5">
  <div style="box-shadow:0px 2px 5px rgba(0,0,0,0.5)" class="p-3 mb-4 bg-white rounded">
    <h5>💰Overall Income</h5>
    <div class="d-flex justify-content-around">
      <div><small>Daily</small><h5 id="dailyOverall"></h5></div>
      <div><small>Monthly</small><h5 id="monthlyOverall"></h5></div>
      <div><small>Balance</small><h5 id="sellerbalance"></h5></div>
    </div>
  </div>

  <div class="card mt-4">
    <h4 class="mb-4">📜 Riwayat TopUp</h4>
    <?php if ($isAdmin): ?>
    <form method="get" class="form-inline mb-3">
      <label class="mr-2">Filter User:</label>
      <select id="usernameFilter" class="form-control mr-2">
        <option value="all">Semua User</option>
        <?php foreach ($usernames as $name): ?>
          <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <?php endif; ?>
    <div class="table-responsive">
      <table id="topupTable" class="table table-bordered table-sm table-hover bg-white">
        <thead>
          <tr>
            <th>Waktu</th>
            <th>Username</th>
            <th>TopUp</th>
            <th>Before</th>
            <th>After</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_reverse($filteredLog) as $entry): ?>
          <tr data-user="<?= htmlspecialchars($entry['sellername']) ?>">
            <td><?= $entry['datetime'] ?></td>
            <td><?= $entry['sellername'] ?></td>
            <td>Rp<?= number_format($entry['topup']) ?></td>
            <td>Rp<?= number_format($entry['before']) ?></td>
            <td>Rp<?= number_format($entry['after']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <h4 class="mb-4">📄 Riwayat Generate Voucher</h4>
  <?php if ($isAdmin): ?>
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
  <?php else: ?>
  <p class="mb-3"><strong>Riwayat voucher Anda:</strong></p>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table table-bordered table-sm table-hover bg-white">
      <thead>
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

<script>
  var dIncome = <?= $sumDaily ?>;
  var mIncome = <?= $sumMonthly ?>;
  var oIncome = <?= $sellerbalance ?>;

  document.getElementById('dailyOverall').innerText = 'Rp ' + dIncome.toLocaleString() + '.00';
  document.getElementById('monthlyOverall').innerText = 'Rp ' + mIncome.toLocaleString() + '.00';
  document.getElementById('sellerbalance').innerText = 'Rp ' + oIncome.toLocaleString() + '.00';

  <?php if ($isAdmin): ?>
  document.getElementById("usernameFilter").addEventListener("change", function () {
    const selectedUser = this.value;
    const rows = document.querySelectorAll("#topupTable tbody tr");

    rows.forEach(row => {
      const user = row.getAttribute("data-user");
      row.style.display = (selectedUser === "all" || user === selectedUser) ? "" : "none";
    });
  });
  <?php endif; ?>
</script>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
</body>
</html>