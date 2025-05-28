<!--============v.0.1=============-->
<!--18-Mey-2025 by KWHotspot-->

<?php
session_start();
include("../class/mt_resources.php");
$logPath = "../json/topup_log.json";
$log = file_exists($logPath) ? json_decode(file_get_contents($logPath), true) : [];
$usernames = array_unique(array_column($log, 'sellername'));

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>📜 Riwayat TopUp</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../css/style.css" />
  <style>
    body { background-color: #f8f9fa; }
    .card { margin-bottom: 20px; }
    .table td { vertical-align: middle; }
    .btn i { pointer-events: none; }
  </style>
</head>
<body>
<?php include('../page/navigation.php');?>
<div class="card mt-4">
  <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
    📜 Riwayat TopUp
    <select id="usernameFilter" class="form-control w-auto ml-1">
      <option value="all">Semua User</option>
      <?php foreach ($usernames as $name): ?>
        <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="table-responsive">
    <table class="table table-bordered table-sm" id="topupTable">
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
        <?php foreach (array_reverse($log) as $entry): ?>
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
<script>
document.getElementById("usernameFilter").addEventListener("change", function () {
  const selectedUser = this.value;
  const rows = document.querySelectorAll("#topupTable tbody tr");

  rows.forEach(row => {
    const user = row.getAttribute("data-user");
    if (selectedUser === "all" || user === selectedUser) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  });
});
</script>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script> 
</body> 
</body> 
</html> 
