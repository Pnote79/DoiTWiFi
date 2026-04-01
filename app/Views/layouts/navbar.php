<div id="sidebar-wrapper">
    <div class="sidebar-header">
        <div id="brandNameDashboard" class="text-center p-3">
             <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-router-fill" viewBox="0 0 16 16">
                <path d="M5.525 3.025a3.5 3.5 0 0 1 4.95 0 .5.5 0 1 0 .707-.707 4.5 4.5 0 0 0-6.364 0 .5.5 0 0 0 .707.707Z"/>
                <path d="M8 7a1 1 0 1 1 0 2 1 1 0 0 1 0-2Z"/>
            </svg>
            <div class="mt-2">
                <span style="font-weight:bold; color:#1abc9c;">KWH</span><span style="font-weight:bold; color:#f1c40f;">otspot</span>
            </div>
            <small id="timeDiv" class="d-block text-muted" style="font-size:10px"></small>
        </div>
    </div>

    <ul class="nav flex-column mt-3">
	     <li class="nav-item">
            <a class="nav-link text-light" href="<?= BASE_URL ?>/home">🏠 Dashboard</a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-light" href="#incomeSub" data-toggle="collapse">💰 Pendapatan ▾</a>
            <div class="collapse" id="incomeSub">
                <a class="nav-link small ml-3" href="<?= BASE_URL ?>/income/dashboard">Laporan Income</a>
                <a class="nav-link small ml-3" href="<?= BASE_URL ?>/income/income">List Client</a>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link text-light" href="#hotspotSub" data-toggle="collapse">📡 Hotspot ▾</a>
            <div class="collapse" id="hotspotSub">
                <a class="nav-link small ml-3" href="<?= BASE_URL ?>/hotspot/active">User Aktif</a>
                <a class="nav-link small ml-3" href="<?= BASE_URL ?>/hotspot/profile">Profil Paket</a>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link text-light" href="#clientSub" data-toggle="collapse">👤 Client ▾</a>
            <div class="collapse" id="clientSub">
                <a class="nav-link small ml-3" href="<?= BASE_URL ?>/mikrotik/pppoe">PPPoE Secrets</a>
                <a class="nav-link small ml-3" href="<?= BASE_URL ?>/mikrotik/static">Static / AP</a>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link text-light" href="<?= BASE_URL ?>/settings">⚙️ Settings</a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <hr border-color="#444">
        <div class="px-3 mb-3 text-light">
            <small class="text-muted d-block">User:</small>
            <strong style="color:#1abc9c"><?= strtoupper($_SESSION['username'] ?? 'Guest') ?></strong>
        </div>
        <button onclick="logOut()" class="btn btn-block btn-outline-warning btn-sm">Logout</button>
    </div>
</div>

<style>
    /* Reset & Body Adjust */
    body { overflow-x: hidden; }

    /* Sidebar di Kanan */
    #sidebar-wrapper {
        position: fixed;
        left: -210px; /* Sembunyi ke kanan (sisakan sedikit untuk pemicu) */
        top: 0;
        width: 250px;
        height: 100%;
        background: #212529;
        z-index: 1050;
        transition: all 0.4s ease;
        box-shadow: -2px 0 10px rgba(0,0,0,0.5);
        padding-right: 10px;
    }

    /* Muncul saat di hover */
    #sidebar-wrapper:hover {
        left: 0;
    }

    /* Dekorasi Link */
    .nav-link {
        color: #bdc3c7 !important;
        padding: 12px 20px;
        transition: 0.3s;
    }
    .nav-link:hover {
        background: #2c3e50;
        color: #1abc9c !important;
    }
    .sidebar-footer {
        position: absolute;
        bottom: 20px;
        width: 100%;
        padding: 0 20px;
    }
    
    /* Pemicu Visual di tepi layar */
    #sidebar-wrapper::before {
        content: "☰";
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        background: #1abc9c;
        color: white;
        padding: 10px 5px;
        border-radius: 5px 0 0 5px;
        cursor: pointer;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Jam Realtime
    function updateTime() {
        const now = new Date();
        const options = { 
            hour: '2-digit', minute: '2-digit', second: '2-digit' 
        };
        const el = document.getElementById('timeDiv');
        if(el) el.textContent = now.toLocaleTimeString('id-ID', options);
    }
    setInterval(updateTime, 1000);
    updateTime();

    // Auto-close collapse menu saat kursor keluar dari sidebar
    $("#sidebar-wrapper").mouseleave(function(){
        $('.collapse').collapse('hide');
    });
});

function logOut() {
    Swal.fire({
        title: 'Yakin ingin keluar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Logout'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "<?= BASE_URL ?>/logout";
        }
    });
}
</script>
