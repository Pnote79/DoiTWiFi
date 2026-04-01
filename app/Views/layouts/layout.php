<?php
$content = $content ?? '';
$base_url = "http://localhost/project"; // Sesuaikan dengan BASE_URL kamu
?>
<?php
// 1. Definisikan path file JSON (Sesuaikan path-nya)
$json_path = dirname(__DIR__, 3) . '/storage/mikrotikdata.json';

// 2. Set nilai default (Fallback)
$dn = "DOiT";
$ns = "WiFi";
$content = $content ?? ''; // Agar tidak error jika content kosong

// 3. Ambil data langsung dari JSON
if (file_exists($json_path)) {
    $json_raw = file_get_contents($json_path);
    $json_data = json_decode($json_raw, true);
    
    // Ambil field 'dns' dari index [0]
    $dns_value = $json_data[0]['dns'] ?? '';

    if (!empty($dns_value)) {
        if (strpos($dns_value, '@') !== false) {
            $parts = explode('@', $dns_value);
            $dn = $parts[0];
            $ns = $parts[1];
        } else {
            $dn = $dns_value;
            $ns = "";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $dn . '.' . $ns ?? 'DOiTWiFi' ?> Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        body {
            overflow-x: hidden;
            background: #0d1117;
            color: #c9d1d9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
        }

        /* SIDEBAR LAYOUT WITH FLEXBOX */
        #sidebar-wrapper {
            position: fixed;
            left: -250px;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #161b22;
            border-right: 1px solid #30363d;
            z-index: 1050;
            transition: all 0.3s ease;
            
            /* Kunci agar footer bisa di bawah */
            display: flex;
            flex-direction: column;
        }

        #sidebar-wrapper.active {
            left: 0;
        }

        /* Navigasi Tengah (Scrollable jika menu banyak) */
        .sidebar-nav-container {
            flex-grow: 1;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #30363d transparent;
        }

        /* Footer Sidebar (Mepet Bawah) */
        .sidebar-footer {
            margin-top: auto; /* Ini yang mendorong ke bawah */
            padding: 15px;
            background: #161b22;
            border-top: 1px solid #30363d;
        }

        /* TOGGLE BUTTON */
        #sidebarToggle {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            background: #1abc9c;
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        /* CONTENT AREA */
        #main-content {
            margin-left: 0;
            padding: 40px 0 0 0;
            transition: 0.3s;
        }

        @media (min-width: 769px) {
            #main-content.shift {
                margin-left: 250px;
            }
        }

        /* LINK STYLE */
        .nav-link {
            color: #8b949e !important;
            padding: 10px 20px;
            transition: 0.2s;
        }

        .nav-link:hover {
            background: #21262d;
            color: #58a6ff !important;
        }

        .collapse .nav-link {
            padding-left: 45px;
            font-size: 0.9em;
        }

        hr { border-top: 1px solid #30363d; }
    </style>

</head>

<body>

<button id="sidebarToggle" title="Menu">☰</button>

<div id="sidebar-wrapper">
    <div class="sidebar-header">
        <div id="brandNameDashboard" class="text-center p-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" fill="#1abc9c" class="bi bi-router-fill" viewBox="0 0 16 16">
                <path d="M5.525 3.025a3.5 3.5 0 0 1 4.95 0 .5.5 0 1 0 .707-.707 4.5 4.5 0 0 0-6.364 0 .5.5 0 0 0 .707.707Z"/>
                <path d="M8 7a1 1 0 1 1 0 2 1 1 0 0 1 0-2Z"/>
                <path d="M0 13a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1v-2Z"/>
            </svg>
            <div class="mt-2 h5">
                <span style="font-weight:bold; color:#e74c3c;"><?= $dn ?? 'DOiT' ?></span><span style="font-weight:bold; color:#f1c40f;"><?= $ns ?? 'WiFi' ?></span>
            </div>
            <small id="timeDiv" class="d-block text-muted" style="font-size:10px"></small>
        </div>
    </div>

    <div class="sidebar-nav-container">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>/home">🏠 Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#incomeSub" data-toggle="collapse">🎟️ Pendapatan <span class="float-right small">▾</span></a>
                <div class="collapse" id="incomeSub">
                    <a class="nav-link" href="<?= BASE_URL ?>/income/dashboard">Laporan Income</a>
                    <a class="nav-link" href="<?= BASE_URL ?>/income/income">List Client</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#hotspotSub" data-toggle="collapse">📡 Hotspot <span class="float-right small">▾</span></a>
                <div class="collapse" id="hotspotSub">
                    <a class="nav-link" href="<?= BASE_URL ?>/hotspot/active">User Aktif</a>
                    <a class="nav-link" href="<?= BASE_URL ?>/hotspot/profile">Profil Paket</a>
                    <a class="nav-link" href="<?= BASE_URL ?>/hotspot/user">Hotspot User</a>
					<a class="nav-link" href="<?= BASE_URL ?>/userseller/dashboard">📜Generate Voucher</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#clientSub" data-toggle="collapse">👤 Client <span class="float-right small">▾</span></a>
                <div class="collapse" id="clientSub">
                    <a class="nav-link" href="<?= BASE_URL ?>/mikrotik/pppoe">PPPoE Secrets</a>
                    <a class="nav-link" href="<?= BASE_URL ?>/mikrotik/static">Static / AP</a>
                    <a class="nav-link" href="<?= BASE_URL ?>/mikrotik/acs">ACS Management</a>
                </div>
            </li>
	        <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>/seller">📋 Seller</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>/settings">⚙️ Settings</a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
	        <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>/admin/about">🚀 DOiTWiFi</a>
            </li>
        <div class="mb-3 px-1">
            <small class="text-muted d-block" style="font-size: 11px;">LOGGED IN AS:</small>
            <strong style="color:#1abc9c; font-size: 14px;">
                <?= strtoupper($_SESSION['username'] ?? 'ADMIN') ?>
            </strong>
        </div>
        <button onclick="logOut()" class="btn btn-block btn-outline-warning btn-sm" style="border-radius: 8px;">
            🚪 Logout
        </button>
    </div>
</div>

<div id="main-content">
    <div class="container-fluid">
        <?= $content ?>
    </div>
</div>
<?php
echo "<pre>";
print_r($dn);
print_r($ns);
echo "</pre>";
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Sidebar Toggle Logic
    $("#sidebarToggle").click(function(e){
        e.preventDefault();
        $("#sidebar-wrapper").toggleClass("active");
        $("#main-content").toggleClass("shift");
    });

    // Real-time Clock
    function updateClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        document.getElementById('timeDiv').innerText = now.toLocaleDateString('id-ID', options);
    }
    setInterval(updateClock, 1000);
    updateClock();

    // SweetAlert Logout
    function logOut() {
        Swal.fire({
            title: 'Yakin ingin keluar?',
            text: "Sesi Anda akan diakhiri.",
            icon: 'warning',
            background: '#161b22',
            color: '#c9d1d9',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Logout',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "<?= BASE_URL ?>/logout";
            }
        });
    }
</script>

</body>
</html>