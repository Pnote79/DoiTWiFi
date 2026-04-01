<?php
/**
 * =========================================================
 * Nama File    : home.php (Dashboard Monitoring Utama)
 * Project      : KWHotspot - doitwifi support Gemini GPT
 * Tanggal Buat : 25 Maret 2026
 * Deskripsi    : Implementasi Tema Dark/Light pada layout fixed
 * =========================================================
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/login");
    exit;
}

?>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ================= THEME VARIABLES ================= */
        :root {
            --bg-body: #0d1117;
            --bg-card: #161b22;
            --bg-header: #21262d;
            --bg-log: #010409;
            --text-main: #c9d1d9;
            --border-color: #30363d;
            --input-bg: #0d1117;
        }

        body.light {
            --bg-body: #f4f7f6;
            --bg-card: #ffffff;
            --bg-header: #f8f9fa;
            --bg-log: #ffffff;
            --text-main: #24292e;
            --border-color: #d1d5da;
            --input-bg: #ffffff;
        }

        /* BASE LAYOUT */
        html, body { height: 100vh; background-color: var(--bg-body); color: var(--text-main); margin: 0; padding: 0; transition: all 0.3s ease; }
        
        @media (min-width: 992px) {
            body { overflow: hidden; }
            .container-fluid { height: calc(100vh - 65px); display: flex; flex-direction: column; }
        }

        .card { background-color: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-main); }
        .card-header { background-color: var(--bg-header) !important; color: var(--text-main) !important; border-bottom: 1px solid var(--border-color); }
        
        .card-box { padding: 12px; border-radius: 8px; color: white; margin-bottom: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .bg-red { background: linear-gradient(45deg, #f53d3d, #d32f2f); }
        .bg-yellow { background: linear-gradient(45deg, #fbc02d, #f57f17); }
        .bg-green { background: linear-gradient(45deg, #2ea043, #238636); }
        
        .stat-label { font-size: 0.7rem; text-transform: uppercase; font-weight: bold; opacity: 0.9; }
        .card-box h3 { margin: 2px 0; font-size: 1.3rem; font-weight: 800; }
        
        .traffic-chart-container { position: relative; height: 100%; width: 100%; min-height: 200px; }
        .iface-selector { background: var(--input-bg); color: #3fb950; border: 1px solid var(--border-color); font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; font-weight: bold; }
        
        .log-box { flex: 1; overflow-y: auto; background: var(--bg-log); border-top: 1px solid var(--border-color); }
        .table-log { color: var(--text-main); }
        .table-log td { font-size: 0.75rem; border-top: 1px solid var(--border-color); vertical-align: middle; }
        
        .progress { background-color: var(--border-color); border-radius: 10px; height: 15px !important; }
        
        /* Floating Theme Switcher */
        .theme-switch { position: fixed; top: 10px; right: 10px; z-index: 1050; }
    </style>


<div class="theme-switch">
    <button onclick="toggleTheme()" class="btn btn-sm btn-outline-info shadow-sm bg-dark text-white">🌗 Mode</button>
</div>


<div class="container-fluid py-2">
    <div class="row px-2 flex-shrink-0">
        <div class="col-6 col-md-4 p-1">
            <div class="card-box bg-red text-center">
                <div class="stat-label">HOTSPOT ACTIVE</div>
                <h3 id="h-active">0</h3>
                <small id="h-total">0 Vouchers</small>
            </div>
        </div>
        <div class="col-6 col-md-4 p-1">
            <div class="card-box bg-yellow text-center">
                <div class="stat-label">PPPOE ACTIVE</div>
                <h3 id="p-active">0</h3>
                <small id="n-up">0 AP Online</small>
            </div>
        </div>
        <div class="col-12 col-md-4 p-1">
            <div class="card-box bg-green text-center">
                <div class="stat-label">MONTHLY INCOME</div>
                <h3 id="i-month">Rp 0</h3>
                <small id="i-today">Today: Rp 0</small>
            </div>
        </div>
    </div>

    <div class="row no-gutters">
        <div class="col-lg-4 col-md-5 mb-3 d-flex flex-column">
            <div class="card h-100 shadow-sm border-0">
                <div class="card shadow-sm mb-2" style="flex: 0 0 auto;">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center small font-weight-bold">
                        <span>💻 RESOURCES</span>
                        <span id="router-identity" class="badge badge-primary">...</span>
                    </div>
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between mb-1"><span class="small">CPU Load</span> <span id="cpu-text" class="small font-weight-bold">0%</span></div>
                        <div class="progress mb-2"><div id="cpu-bar" class="progress-bar bg-danger" style="width: 0%"></div></div>
                        
                        <div class="d-flex justify-content-between mb-1"><span class="small">RAM Usage</span> <span id="mem-text" class="small font-weight-bold">0%</span></div>
                        <div class="progress mb-2"><div id="mem-bar" class="progress-bar bg-warning text-dark" style="width: 0%"></div></div>
                        
                        <div class="row text-center mt-2 no-gutters border-top pt-2">
                            <div class="col-4 border-right small">UPTIME<br><b id="uptime" class="text-info">-</b></div>
                            <div class="col-4 border-right small">BOARD<br><b id="board" class="text-info">-</b></div>
                            <div class="col-4 small">VER<br><b id="version" class="text-info">-</b></div>
                        </div>
                    </div>
                </div>
                <div class="flex-grow-1"></div>
            </div>
        </div>

        <div class="col-lg-8 col-md-7 mb-3">
            <div class="card h-100 shadow-sm overflow-hidden">
                <div class="card-header py-2 font-weight-bold d-flex justify-content-between align-items-center">
                    <span>📶 TRAFFIC MONITOR</span>
                    <select id="interfaceSelect" class="iface-selector" onchange="changeInterface()">
                        <?php foreach ($stats['all_ifaces'] as $iface): ?>
                            <option value="<?= $iface['name'] ?>" <?= ($stats['iface'] == $iface['name']) ? 'selected' : '' ?>>
                                <?= strtoupper($iface['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="card-body p-2 d-flex flex-column">
                    <div class="traffic-chart-container">
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm flex-grow-1 overflow-hidden d-flex flex-column mt-n2">
        <div class="card-header py-2 font-weight-bold small">📜 HOTSPOT LOGS</div>
        <div class="log-box">
            <table class="table table-sm table-hover mb-0 table-log">
                <tbody id="logTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    const API_URL = '<?= BASE_URL ?>/api';
    let currentIface = "<?= $stats['iface'] ?>";

    /* --- Theme Toggle Function --- */
    function toggleTheme() {
        $('body').toggleClass('light');
        localStorage.setItem('theme', $('body').hasClass('light') ? 'light' : 'dark');
        location.reload(); // Reload untuk mereset warna chart agar sesuai tema
    }

    /* --- Initialize Theme --- */
    if(localStorage.getItem('theme') === 'light') {
        $('body').addClass('light');
    }

    const isLight = $('body').hasClass('light');

    /* ================= INITIALIZE CHART ================= */
    const ctx = document.getElementById('trafficChart').getContext('2d');
    const trafficChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: 'RX (Down)', data: [], borderColor: '#f53d3d', backgroundColor: 'rgba(245, 61, 61, 0.1)', borderWidth: 2, fill: true, tension: 0.4 },
                { label: 'TX (Up)', data: [], borderColor: '#58a6ff', backgroundColor: 'rgba(88, 166, 255, 0.1)', borderWidth: 2, fill: true, tension: 0.4 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            elements: { point: { radius: 0 } },
            scales: {
                y: { beginAtZero: true, grid: { color: isLight ? '#eee' : '#30363d' }, ticks: { color: '#8b949e', font: { size: 10 } } },
                x: { grid: { display: false }, ticks: { color: '#8b949e', font: { size: 10 } } }
            },
            plugins: { legend: { labels: { color: isLight ? '#333' : '#c9d1d9', font: { size: 11 } } } }
        }
    });

    function changeInterface() {
        currentIface = $('#interfaceSelect').val();
        trafficChart.data.labels = [];
        trafficChart.data.datasets[0].data = [];
        trafficChart.data.datasets[1].data = [];
        trafficChart.update();
    }

    function formatRupiah(angka) {
        return 'Rp ' + (angka || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function updateQuickStats() {
        $.getJSON(`${API_URL}/quick-stats`, (d) => {
            $('#h-active').html(`${d.userActive || 0} <small>👥</small>`);
            $('#h-total').text(`${d.userCount || 0} Vouchers`);
            $('#p-active').html(`${d.ppoeActive || 0} <small>📡</small>`);
            $('#n-up').text(`${d.netwatchUp || 0} AP Online`);
            $('#i-month').text(formatRupiah(d.sumMonth));
            $('#i-today').text(`Today: ${formatRupiah(d.sumDaily)}`);
        });
    }
function formatUptime(uptime) {
    if (!uptime) return '-';

    let w = uptime.match(/(\d+)w/)?.[1] || 0;
    let d = uptime.match(/(\d+)d/)?.[1] || 0;
    let h = uptime.match(/(\d+)h/)?.[1] || 0;

    let totalDays = (w * 7) + parseInt(d);

    return `${totalDays} hari ${h} jam`;
}
    function updateRealtime() {
        $.getJSON(`${API_URL}/resources`, (d) => {
            let cpu = d.cpu_load || d['cpu-load'] || 0;
            $('#cpu-bar').css('width', cpu + '%');
            $('#cpu-text').text(cpu + '%');

            let total = d.ram_total || 1;
            let free  = d.ram_free || 0;
            let memPercent = Math.round(((total - free) / total) * 100);

            $('#mem-bar').css('width', memPercent + '%');
            $('#mem-text').text(memPercent + '%');
            $('#router-identity').text(d.identity || 'MikroTik');
            $('#uptime').text(formatUptime(d.uptime));
            $('#board').text(d.board || '-');
            $('#version').text(d.version || '-');
        });

        $.getJSON(`${API_URL}/traffic`, { iface: currentIface }, (d) => {
            let now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            if(trafficChart.data.labels.length > 20) {
                trafficChart.data.labels.shift();
                trafficChart.data.datasets[0].data.shift();
                trafficChart.data.datasets[1].data.shift();
            }
            trafficChart.data.labels.push(now);
            trafficChart.data.datasets[0].data.push(d.rx || 0);
            trafficChart.data.datasets[1].data.push(d.tx || 0);
            trafficChart.update('none');
        });
    }

    function updateLogs() {
        $.getJSON(`${API_URL}/logs`, (data) => {
            let rows = '';
            if (data && data.length > 0) {
                data.slice(0, 10).forEach(log => {
                    let msg = log.message || '-';
                    let time = log.time || '-';
                    let lower = msg.toLowerCase();
                    let color = isLight ? 'text-dark' : 'text-light';
                    let icon = '⚪';

                    if (lower.includes('failed') || lower.includes('error')) { color = 'text-danger'; icon = '🔴'; } 
                    else if (lower.includes('logged in')) { color = 'text-success'; icon = '🟢'; } 
                    else if (lower.includes('logged out')) { color = 'text-warning'; icon = '🟡'; }

                    rows += `<tr>
                              <td class="pl-2 text-nowrap">${time}</td>
                              <td class="${color}">${icon} ${msg}</td>
                             </tr>`;
                });
                $('#logTableBody').html(rows);
            }
        });
    }

    setInterval(updateRealtime, 2000);
    setInterval(updateQuickStats, 5000);
    setInterval(updateLogs, 5000);

    $(document).ready(function() {
        updateRealtime();
        updateQuickStats();
        updateLogs();
    });
</script>
