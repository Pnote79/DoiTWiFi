<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report - <?= htmlspecialchars($targetUser) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="<?= asset('css/doit.css') ?>">
  <style>
    body {
        padding-bottom: 90px; /* 🔥 supaya tidak ketutup navbar */
    }
	/* ===== HEADER ===== */
.header {
    padding: 12px 15px;
    border-radius: 0 0 18px 18px;
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    color: white;
    font-size: 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.header a {
    font-size: 13px;
}

/* ===== STAT BOX ===== */
.stat-box {
    background: #161b22;
    border-radius: 12px;
    padding: 10px;
    border: 1px solid #30363d;
    box-shadow: 0 3px 10px rgba(0,0,0,0.25);
    transition: 0.2s;
}

.stat-box:hover {
    transform: translateY(-2px);
}

/* ===== CARD ===== */
.card {
    border-radius: 14px;
    background: #161b22;
    border: 1px solid #30363d;
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    margin-bottom: 12px;
}

.card-header {
    border-bottom: 1px solid #30363d;
    font-size: 13px;
}

/* ===== SEARCH ===== */
.search-container {
    position: relative;
    margin: 12px 0;
}

.search-input {
    padding-left: 35px;
    border-radius: 10px;
    background: #161b22;
    border: 1px solid #30363d;
    color: #c9d1d9;
    height: 42px;
}

.search-input:focus {
    border-color: #58a6ff;
    box-shadow: none;
}

.fa-search-icon {
    position: absolute;
    top: 12px;
    left: 12px;
    color: #8b949e;
}

/* ===== TABLE ===== */
.table {
    color: #c9d1d9;
    font-size: 12px;
}

.table thead {
    background: #0d6efd;
    color: white;
    font-size: 12px;
}

.table td,
.table th {
    padding: 10px;
    vertical-align: middle;
    white-space: nowrap;
}

/* hover row */
.table-hover tbody tr:hover {
    background: #21262d;
}

/* klik efek */
.table tbody tr:active {
    transform: scale(0.99);
}

/* ===== BADGE ===== */
.badge {
    font-size: 10px;
    padding: 4px 7px;
    border-radius: 8px;
}

/* ===== TOPUP TABLE ===== */
.table-responsive {
    scrollbar-width: thin;
}

/* ===== MOBILE OPTIMIZE ===== */
@media (max-width: 576px) {

    .header {
        font-size: 13px;
    }

    .stat-box {
        padding: 8px;
    }

    .table td,
    .table th {
        font-size: 11px;
        padding: 6px;
    }

    .search-input {
        height: 38px;
        font-size: 12px;
    }
}
/* ===== BODY ===== */
body {
    background: #f5f7fb; /* abu terang */
    color: #1f2937;
}

/* ===== CARD ===== */
.card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

/* ===== HEADER ===== */
.header {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    color: white;
}

/* ===== STAT BOX ===== */
.stat-box {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* ===== TABLE ===== */
.table {
    background: #ffffff;
    color: #1f2937;
}

.table thead {
    background: #0d6efd;
    color: white;
}

.table-hover tbody tr:hover {
    background: #f1f5f9;
}

/* ===== SEARCH ===== */
.search-input {
    background: #ffffff;
    border: 1px solid #d1d5db;
    color: #111827;
}

.search-input:focus {
    border-color: #0d6efd;
}

/* ===== BADGE ===== */
.badge.bg-light {
    background: #f3f4f6 !important;
    color: #111827 !important;
}
  </style>
</head>
<body class="seller-aktivitas">

<div class="header d-flex justify-content-between align-items-center bg-light">
    <a href="javascript:history.back()" class="text-success text-decoration-none"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
    <span class="fw-bold fs-6"><?= htmlspecialchars($targetUser) ?></span>
    <div style="width: 50px"></div>
</div>

<div class="container mt-3">
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="stat-box text-center">
                <small class="text-muted d-block" style="font-size: 10px;">HARIAN</small>
                <b class="text-primary">Rp<?= number_format($sumDaily) ?></b>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-box text-center">
                <small class="text-muted d-block" style="font-size: 10px;">BULANAN</small>
                <b class="text-success">Rp<?= number_format($sumMonthly) ?></b>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-box text-center">
                <small class="text-muted d-block" style="font-size: 10px;">SALDO</small>
                <b class="text-dark">Rp<?= number_format($displayBalance) ?></b>
            </div>
        </div>
    </div>

    <?php if($isAdmin): ?>
    <div class="card p-2 mb-3">
        <form method="GET">
            <select name="seller" class="form-select border-0 bg-light" onchange="this.form.submit()">
                <option value="">-- Pilih Seller --</option>
                <?php foreach(array_unique(array_column($sellerList, 'sellername')) as $sn): ?>
                    <option value="<?= $sn ?>" <?= ($targetUser==$sn)?'selected':'' ?>><?= $sn ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-white fw-bold py-2"><i class="fa fa-wallet me-2 text-warning"></i>Topup <?= date('M Y') ?></div>
        <div class="table-responsive" style="max-height: 120px;">
            <table class="table table-sm mb-0">
                <tbody>
                    <?php foreach(array_reverse($filteredTopup) as $tp): ?>
                    <tr>
                        <td class="ps-3"><?= date('d/m H:i', strtotime($tp['datetime'])) ?></td>
                        <td class="text-success fw-bold text-end pe-3">+<?= number_format($tp['topup']) ?></td>
                    </tr>
                    <?php endforeach; if(empty($filteredTopup)) echo "<tr><td colspan='2' class='text-center py-3 text-muted'>Tidak ada topup bulan ini</td></tr>"; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="search-container">
        <i class="fa fa-search fa-search-icon"></i>
        <input type="text" id="findVoc" class="form-control search-input shadow-sm" placeholder="Cari Kode Voucher...">
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="vocTable">
                <thead class="table-primary text-white">
                    <tr>
                        <th class="ps-3">Tgl</th>
                        <th>Paket</th>
                        <th>Voucher</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $limit = 0;
                    foreach($historyVoucher as $hv): 
                        $limit++;
                        $hide = ($limit > 10) ? 'style="display:none"' : '';
                    ?>
                    <tr <?= $hide ?> onclick="showPop('<?= $hv['tgl'] ?>','<?= $hv['voucher'] ?>','<?= $hv['paket'] ?>','<?= $hv['ip'] ?>','<?= $hv['iface'] ?>','<?= $hv['exp'] ?>','<?= $hv['msg'] ?>', '<?= htmlspecialchars($hv['statusHtml']) ?>')" style="cursor:pointer">
                        <td class="ps-3 small text-muted"><?= $hv['tgl'] ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $hv['paket'] ?></span></td>
                        <td class="fw-bold text-primary"><?= $hv['voucher'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="noMatch" class="p-4 text-center text-muted" style="display:none;">Data tidak ditemukan...</div>
    </div>
</div>

<?php include dirname(__DIR__, 1) . '/layouts/footer.php'; ?>

<script>
// Live Search Logic
document.getElementById('findVoc').addEventListener('keyup', function(){
    let val = this.value.toUpperCase();
    let rows = document.querySelectorAll("#vocTable tbody tr");
    let matchFound = false;

    rows.forEach(row => {
        let text = row.innerText.toUpperCase();
        if(text.includes(val)){
            row.style.display = "";
            matchFound = true;
        } else {
            row.style.display = "none";
        }
    });
    document.getElementById('noMatch').style.display = matchFound ? "none" : "block";
});

// Helper Decode HTML dari PHP
function decodeEntities(encodedString) {
    var textArea = document.createElement('textarea');
    textArea.innerHTML = encodedString;
    return textArea.value;
}

// SweetAlert Popup Detail
function showPop(tgl, v, p, ip, iface, exp, wa, statusHtmlRaw) {
    const statusHtml = decodeEntities(statusHtmlRaw);
    const copyTxt = `Detail Penjualan\nTanggal: ${tgl}\nVoucher: ${v}\nPaket: ${p}\nLogin: ${ip} (${iface})\nAktif: ${exp}`;

    Swal.fire({
        title: '<span style="font-size:18px">Detail Voucher</span>',
        html: `
            <div class="text-center mb-3">${statusHtml}</div>
            <div class="text-start px-2" style="font-size:14px; line-height:1.8">
                <div class="d-flex justify-content-between border-bottom pb-1"><span>Tanggal</span><small><b>${tgl}</b></div>
                <div class="d-flex justify-content-between border-bottom pb-1"><span>Voucher</span><b class="text-primary">${v}</b></div>
                <div class="d-flex justify-content-between border-bottom pb-1"><span>Paket</span><b>${p}</b></div>
                <div class="d-flex justify-content-between border-bottom pb-1"><span>Login</span><small><b>${iface}</b></div>
                <div class="mt-3 text-center p-2 bg-light rounded border">
                    <small class="text-muted d-block">Masa Aktif Sampai:</small>
                    <b class="text-danger" style="font-size:15px">${exp}</b>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-copy"></i> Salin',
        cancelButtonText: '<i class="fab fa-whatsapp"></i> WhatsApp',
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#25D366',
        width: '90%'
    }).then((res) => {
        if(res.isConfirmed) {
            navigator.clipboard.writeText(copyTxt);
            Swal.fire({icon:'success', title:'Teks Dicopy!', timer:800, showConfirmButton:false});
        } else if(res.dismiss === Swal.DismissReason.cancel) {
            window.open('https://api.whatsapp.com/send?text=' + wa, '_blank');
        }
    });
}
</script>
</body>
</html>