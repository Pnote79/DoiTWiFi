<?php
session_start();
include("../class/mt_resources.php");
$iface = $_GET['iface'] ?? ($_SESSION['iface'] ?? 'ether1');
$_SESSION['iface'] = $iface;


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Hotspot</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
	 <link rel="stylesheet" href="../css/style.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card-box {
            padding: 20px;
            color: white;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .bg-red { background-color: #e74c3c; }
        .bg-yellow { background-color: #f1c40f; }
        .bg-green { background-color: #1abc9c; }
        .log-box {
            height: 250px;
            overflow-y: auto;
            font-size: 0.9em;
        }
        .traffic-chart {
            width: 100%;
            height: 100px;
        }
        th, td {
            vertical-align: middle !important;
        }
    </style>
</head>
<body>

<?php include('../page/navigation.php'); ?>
<!--ambil income-->
<?php
$getVendo = json_encode($API->comm("/system/script/print"));
$getVendo = json_decode($getVendo, true);

$sumDaily = 0;
$sumMonthly = 0;
$sumOverall = 0;

$HariIni = strtolower(date("M/d/Y"));
$bulanIni = strtolower(date("M"));

if (is_array($getVendo)) {
  foreach ($getVendo as $item) {
    $name = isset($item['name']) ? $item['name'] : '';
    $parts = explode('-|-', $name);

    if (count($parts) < 4) continue;

    $hari = strtolower(trim($parts[0]));
    $amount = floatval(trim($parts[3]));
    $source = isset($item['source']) ? strtolower($item['source']) : '';
    $part = explode("/", $source);
    $bulan = isset($part[0]) ? $part[0] : '';

    if ($HariIni == $hari) {
      $sumDaily += $amount;
    }

    if ($bulan == $bulanIni) {
      $sumMonth += $amount;
    }
  }
}
$sumMonthly = $sumMonth - $sumDaily;
$sumOverall = $sumDaily + $sumMonthly;
?>

<div class="container-fluid px-3 mt-3">

    <!-- Summary Boxes -->
    <div class="row">
        <div class="col-12 col-md-4">
            <div class="card-box bg-red text-center">
                 <h6 class="mb-1">HOTSPOT</h6>
                <h6 class="mb-1"><?php= $userActive ?> 👥 Active Users </h6>
                <h6 class="mb-1"><?php= $userCount ?> 🧩 Vouchers</h6>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card-box bg-yellow text-center">
                 <h6 class="mb-1">PPOE STATIC ACTIVE</h6>
                <h6 class="mb-1"><?= $ppoeActive ?> 🧪ppoe active </h6>
                <h6 class="mb-1"><?= $jumlah_up ?>  📍Ap/Static active</h6>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card-box bg-green text-center">
                <h6 class="mb-1">This Month</h6>
                <h5 class="mb-1">Rp <?= number_format($sumMonth, 0, ',', '.') ?></h5>
                <h6 class="mb-0">Today: Rp <?= number_format($sumDaily, 0, ',', '.') ?></h6>
            </div>

        </div>
    </div>

<!-- System Info -->
<div class="row mt-3">
    <!-- Resource Info -->
    <div class="col-md-6 mb-3">
        <div class="card h-100 shadow-sm border-0" style="border-left: 5px solid #3498db;">
            <div class="card-header bg-primary text-white font-weight-bold d-flex justify-content-between align-items-center">
                <span>💻 Resource Usage</span>
                <span class="badge badge-light" id="router-identity"><?= $identity ?></span>
            </div>
            <div class="card-body bg-light">
                <p class="mb-1 font-weight-bold">CPU Load: </p>
                <div class="progress mb-3" style="height: 18px;">
                    <div class="progress-bar bg-danger" id="cpu-bar" style="width: <?= $cpu_load ?>%;">
                        <?= $cpu_load ?>%
                    </div>
                </div>

                <p class="mb-1 font-weight-bold">Memory Usage: 
                  
                </p>
                <div class="progress" style="height: 18px;">
                    <div class="progress-bar bg-warning" id="mem-bar" style="width: <?= round((($total_ram - $free_ram) / $total_ram) * 100, 1) ?>%;">
                        <?= round((($total_ram - $free_ram) / $total_ram) * 100, 1) ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Identity & Info -->
    <div class="col-md-6 mb-3">
        <div class="card h-100 shadow-sm border-0" style="border-left: 5px solid #2ecc71;">
            <div class="card-header bg-success text-white font-weight-bold">
                🧾 System Info
            </div>
             <div class="card-body bg-light">
                <p><strong>🕒 Uptime:</strong> <?= $formattedUptime ?></p>
                <p><strong>🖥️ Board Name:</strong> <?= $board ?></p>
                <p><strong>📦 Model:</strong> <?= $cpu ?></p>
                <p><strong>🔖 RouterOS:</strong> <?= $mt_resources[0]['version'] ?? 'N/A' ?></p>
            </div>
        </div>
    </div>
</div>



    <!-- Traffic & Logs -->
	<?php
    $interfaces = $API->comm("/interface/print");
    ?>

    <div class="row mt-3">
<div class="col-md-6 mb-3">
    <div class="card h-100 shadow-sm border-0" style="border-left: 5px solid #1abc9c;">
        <div class="card-header d-flex justify-content-between align-items-center bg-success text-white">
            <span><strong>📶 Traffic Monitor</strong></span>
            <!-- Dropdown Interface Selector -->
            <div class="dropdown">
                <button class="btn btn-sm btn-light text-dark dropdown-toggle font-weight-bold" type="button" id="ifaceDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <?= htmlspecialchars($iface) ?>
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="ifaceDropdown">
                    <?php foreach ($interfaces as $interface): ?>
                        <a class="dropdown-item <?= ($iface == $interface['name']) ? 'active bg-success text-white' : '' ?>" href="?iface=<?= urlencode($interface['name']) ?>">
                            <?= htmlspecialchars($interface['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="card-body bg-light">
            <canvas id="trafficChart" class="traffic-chart"></canvas>
        </div>
    </div>
</div>


        <div class="col-md-6 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-light font-weight-bold">Log Hotspot</div>
                <div class="card-body log-box p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 5%;">#</th>
                                <th scope="col" style="width: 25%;">Time</th>
                                <th scope="col">Message</th>
                            </tr>
                        </thead>
                        <tbody id="failedLogTableBody">
                            <!-- Data log dimuat lewat JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Chart Traffic Script -->
<script>
const ctx = document.getElementById('trafficChart').getContext('2d');
const trafficChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [
            {
                label: 'Rx',
                data: [],
                borderColor: 'red',
                fill: true,
                tension: 0.3
            },
            {
                label: 'Tx',
                data: [],
                borderColor: 'blue',
                fill: true,
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Mbps' }
            }
        }
    }
});

setInterval(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const iface = urlParams.get('iface') || 'ether1';

    fetch(`../get/get_traffic.php?iface=${iface}`)
        .then(res => res.json())
        .then(data => {
            if (!data.tx || !data.rx) return;
            if (trafficChart.data.labels.length > 10) {
                trafficChart.data.labels.shift();
                trafficChart.data.datasets[0].data.shift();
                trafficChart.data.datasets[1].data.shift();
            }
            trafficChart.data.labels.push(data.time);
            trafficChart.data.datasets[0].data.push(data.rx);
            trafficChart.data.datasets[1].data.push(data.tx);
            trafficChart.update();
        });
}, 6000);

</script>

<!-- Fetch Log Script -->
<script>
function fetchFailedLog() {
    fetch('../get/get_login_failed.php')
        .then(res => res.json())
        .then(data => {
            const logBody = document.getElementById('failedLogTableBody');
            logBody.innerHTML = '';
            data.forEach((log, index) => {
                logBody.innerHTML += `
                    <tr>
                        <th scope="row">${index + 1}</th>
                        <td>${log.time}</td>
                        <td class="text-truncate" title="${log.message}">${log.message}</td>
                    </tr>`;
            });
        });
}
setInterval(fetchFailedLog, 60000);
fetchFailedLog();
</script>
<script>
function updateResourceStats() {
    fetch('../get/get_resource.php')
        .then(response => response.json())
        .then(data => {
            const memUsed = data.ram_total - data.ram_free;
            const memPercent = ((memUsed / data.ram_total) * 100).toFixed(1);

            document.getElementById('router-identity').textContent = data.identity;
            document.getElementById('cpu-label').textContent = data.cpu_load;
            document.getElementById('cpu-name').textContent = data.cpu;
            document.getElementById('cpu-bar').style.width = data.cpu_load + '%';
            document.getElementById('cpu-bar').textContent = data.cpu_load + '%';

            document.getElementById('mem-used').textContent = memUsed;
            document.getElementById('mem-total').textContent = data.ram_total;
            document.getElementById('mem-bar').style.width = memPercent + '%';
            document.getElementById('mem-bar').textContent = memPercent + '%';

            document.getElementById('uptime').textContent = data.uptime;
            document.getElementById('board').textContent = data.board;
            document.getElementById('model').textContent = data.cpu;
            document.getElementById('version').textContent = data.version;
        })
        .catch(console.error);
}

setInterval(updateResourceStats, 5000);
updateResourceStats();
</script>

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
