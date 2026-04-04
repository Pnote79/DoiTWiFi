<?php
/**
 * Nama File    : income.php
 * Project      : KWHotspot - ISP Management Platform
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/login");
    exit;
}

// Inisialisasi variabel hitung
$countSemua = count($queues ?? []);
$countLunas = 0;
$countTagihan = 0;
$totalTunggakan = 0;
$totalLunasKotor = 0;

if (!empty($queues)) {
    foreach ($queues as $q) {
        $parts = explode(" | ", $q['comment'] ?? "");
        $currentBill = (int)($parts[1] ?? 0);
        $harga = $currentBill * 1000;
        $status = $parts[3] ?? '';

        if ($status == 'l') {
            $countLunas++;
            $totalLunasKotor += $harga;
        } elseif ($status == 't') {
            $countTagihan++;
            $totalTunggakan += $harga;
        }
    }
}

$sumOverall = $totalLunasKotor; 
$totalAllBalance = $totalTunggakan;
?>
<?php
// 1. Definisikan path file JSON (Sesuaikan path-nya)
$json_path = dirname(__DIR__, 4) . '/storage/mikrotikdata.json';

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

    <style>
        :root {
            --bg-main: #0d1117;
            --bg-card: #161b22;
            --border-color: #30363d;
            --text-bright: #c9d1d9;
            --text-muted: #8b949e;
            --accent-blue: #58a6ff;
        }
        body { background-color: var(--bg-main); color: var(--text-bright); font-family: 'Segoe UI', sans-serif; }
        .card-stat { border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-card); transition: all 0.2s ease; }
        .stat-clickable:hover { background-color: #1c2128 !important; transform: translateY(-2px); }
        .active-filter { border-bottom: 3px solid var(--accent-blue) !important; background: #1c2128; }
        .table-custom { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
        .table { color: var(--text-bright); margin-bottom: 0; }
        .table td, .table th { padding: 0.6rem 0.75rem; font-size: 0.85rem; vertical-align: middle; border-top: 1px solid var(--border-color); }
        .badge-tunggak { background: #da3633; color: white; font-size: 0.75rem; }
        .badge-lunas { background: #238636; color: white; font-size: 0.75rem; }
        .badge-code { font-family: monospace; font-size: 0.75rem; background: #21262d; padding: 2px 6px; border-radius: 4px; color: var(--accent-blue); }
        .search-container { position: relative; flex-grow: 1; }
        .search-container input { 
            background: var(--bg-card); border: 1px solid var(--border-color); color: white; 
            padding-left: 35px; border-radius: 8px; font-size: 0.9rem; height: 45px;
        }
        .search-container input:focus { background: #1c2128; color: white; border-color: var(--accent-blue); box-shadow: none; }
        .search-container i { position: absolute; left: 12px; top: 14px; color: var(--text-muted); }
        .btn-sync { height: 45px; border-radius: 8px; font-weight: bold; background: #21262d; border: 1px solid var(--border-color); color: var(--text-bright); }
        .btn-sync:hover { background: #30363d; color: var(--accent-blue); }
    </style>

<div class="container mt-4 mb-5">
    <div class="row mb-4">
        <?php
        $stats = [
            ['label' => 'HARI INI(Vouher)', 'val' => $sumDaily ?? 0, 'color' => '#8b949e', 'text' => 'text-muted', 'filter' => 'all', 'sub' => 'Rp'],
            ['label' => 'BULAN INI ', 'val' => $sumMonthly ?? 0, 'color' => '#58a6ff', 'text' => 'text-primary', 'filter' => 'all', 'sub' => 'Rp'],
            ['label' => 'BELUM BAYAR', 'val' => $totalAllBalance, 'color' => '#f85149', 'text' => 'text-danger', 'filter' => 't', 'sub' => 'Rp'],
            ['label' => 'TOTAL LUNAS', 'val' => $sumOverall, 'color' => '#238636', 'text' => 'text-success', 'filter' => 'l', 'sub' => 'Rp']
        ];
        foreach ($stats as $s) : ?>
            <div class="col-md-3 col-6 mb-3">
                <div class="card-stat p-3 text-center stat-clickable" 
                     data-filter="<?= $s['filter'] ?>" 
                     style="border-top: 4px solid <?= $s['color'] ?>; cursor: pointer;">
                    <span class="stat-label d-block mb-1 text-muted small font-weight-bold"><?= $s['label'] ?></span>
                    <h5 class="<?= $s['text'] ?> font-weight-bold mb-0">
                        <small style="font-size: 0.6em;">Rp</small> <?= number_format($s['val'], 0, ',', '.') ?>
                    </h5>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex mb-4">
        <div class="search-container mr-2">
            <i class="fas fa-search"></i>
            <input type="text" id="cariPelanggan" class="form-control shadow-none" placeholder="Cari nama pelanggan...">
        </div>
        <button id="btnBulkSync" class="btn btn-sync px-3">
            <i class="fas fa-sync-alt mr-1"></i> <span class="d-none d-sm-inline">Sinkron PPP</span>
        </button>
    </div>

    <div class="table-custom">
        <div class="table-responsive">
            <table class="table" id="tablePelanggan">
                <thead style="background: #1c2128;">
                    <tr>
                        <th>PELANGGAN</th>
                        <th class="d-none d-sm-table-cell">TARGET</th>
                        <th>STATUS</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($queues)): ?>
                        <?php foreach ($queues as $q): 
                            $parts = explode(" | ", $q['comment'] ?? "");
                            $baseValue    = (int)($parts[0] ?? 0);
                            $currentBill  = (int)($parts[1] ?? 0); 
                            $displayPrice = $currentBill * 1000;
                            $tgl          = $parts[2] ?? '-';
                            $status       = $parts[3] ?? 'f';
                        ?>
                        <tr class="row-data" data-status="<?= $status ?>" data-name="<?= strtolower($q['name']) ?>">
                            <td>
                                <div class="font-weight-bold text-info" style="line-height:1.2;"><?= $q['name'] ?></div>
                                <small class="text-muted" style="font-size: 0.7rem;"><?= $tgl ?></small> 
                            </td>
                            <td class="d-none d-sm-table-cell">
                                   <?php 
                                     $rawTarget = $q['target'] ?? '';
                                     $cleanTarget = explode('/', $rawTarget)[0]; 
                                    ?>
                                <span class="badge-code btn-open-router" 
                                style="cursor: pointer; border: 1px solid #007bff; padding: 2px 8px; border-radius: 4px;" 
                                data-ip="<?= $cleanTarget ?>" 
                                title="Klik untuk akses lokal">
                                <i class="fas fa-desktop mr-1"></i> <?= $cleanTarget ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($status == 't' && $baseValue > 0): ?>
                                    <span class="badge badge-tunggak px-2 py-1">Rp <?= number_format($displayPrice, 0, ',', '.') ?></span>
                                <?php elseif ($status == 'l'): ?>
                                    <span class="badge badge-lunas px-2 py-1">Lunas</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary px-2 py-1">Free</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">

                               <?php if ($status == 't' && $baseValue > 0): ?>

                                   <a href="<?= BASE_URL ?>/income/bayar?id=<?= urlencode($q['.id']) ?>" 
                                   class="btn btn-sm btn-success btn-bayar py-0 px-2"
                                   data-name="<?= $q['name'] ?>"
                                   data-target="<?= $q['target'] ?>"
                                   data-comment="<?= $q['comment'] ?>"
                                   data-price="<?= $displayPrice ?>">
                                   Bayar
                                   </a>

                               <?php elseif ($status == 'l'): ?>

                                 <button 
                                 class="btn btn-sm btn-info btn-invoice py-0 px-2"
                                 data-name="<?= $q['name'] ?>"
                                 data-target="<?= $q['target'] ?>"
                                 data-comment="<?= $q['comment'] ?>">
                                 📄 Invoice
                                </button>

                              <?php else: ?>

                                <i class="fas fa-check text-success small">Free</i>

                              <?php endif; ?>

                             </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // 1. Filter Card
    $('.stat-clickable').on('click', function() {
        const filter = $(this).data('filter');
        $('.card-stat').removeClass('active-filter').css('opacity', '0.5');
        $(this).addClass('active-filter').css('opacity', '1');
        if (filter === 'all') { $('.row-data').fadeIn(200); } 
        else { $('.row-data').hide(); $('.row-data[data-status="' + filter + '"]').fadeIn(200); }
    });

    // 2. Search
    $("#cariPelanggan").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".row-data").filter(function() {
            $(this).toggle($(this).data("name").indexOf(value) > -1);
        });
    });

    // 3. Konfirmasi Bayar
$('.btn-bayar').on('click', function(e) {
    e.preventDefault();

    const url = $(this).attr('href');
    const name = $(this).data('name');
    const target = $(this).data('target');
    const comment = $(this).data('comment');

    const parts = comment.split(" | ");
    const base = parseInt(parts[0] || 0);
    const total = parseInt(parts[1] || 0);
    const rawDate = parts[2] || '';
    const statusCode = parts[3] || 't';

    // 🔥 HITUNG
    const tunggakan = Math.max(0, total - base);

    const tagihanRp = base * 1000;
    const tunggakanRp = tunggakan * 1000;
    const totalRp = total * 1000;

    // 🔥 PAKET
    let paket = "Reguler";
    if (base == 75) paket = "Bronze";
    else if (base == 100) paket = "Silver";
    else if (base == 150) paket = "Gold";
    else if (base == 200) paket = "Fast";

// 🔥 BULAN & TAHUN OTOMATIS
const jumlahBulan = Math.max(1, Math.floor(total / 100));
const namaBulan = ["jan","feb","mar","apr","mei","jun","jul","agu","sep","okt","nov","des"];

const sekarang = new Date();
let bulanIndex = sekarang.getMonth();
let tahunSekarang = sekarang.getFullYear();

let listBulan = [];
for (let i = 0; i < jumlahBulan; i++) {
    let idx = bulanIndex - i;
    let tahunIterasi = tahunSekarang;

    // Logika untuk mundur ke tahun sebelumnya jika idx negatif
    while (idx < 0) {
        idx += 12;
        tahunIterasi -= 1;
    }

    // Menggabungkan nama bulan dengan tahun (ambil 2 angka terakhir tahun: '24)
    // Atau gunakan tahunIterasi penuh jika ingin 2024
    listBulan.unshift(`${namaBulan[idx]} ${tahunIterasi}`);
}

let bulan = listBulan.join("-");

    // 🔥 ID dari IP
    const id = target.split('.').pop().padStart(3,'0');
    // 🔥 Tanggal Tagihan
	let tanggal = '-';

    if (rawDate) {
    const bulanNama = ["jan","feb","mar","apr","mei","jun","jul","agu","sep","okt","nov","des"];

    const t = rawDate.split('-'); // [YYYY, MM, DD]

    if (t.length === 3) {
        const tahun = t[0];
        const bulan = parseInt(t[1], 10) - 1;
        const hari = t[2];

        tanggal = `${hari}-${bulanNama[bulan]}-${tahun}`;
    }
    }
    // 🔥 INVOICE 
const htmlInvoice = `
<div id="invoice" style="font-family:Arial;color:#000;padding:20px;background:#fff;">
    
        <div style="background:#0d6efd;color:white;padding:10px;border-radius:8px;">
            <h3 style="margin:0;">INVOICE PEMBAYARAN</h3>
            <small><?= $dn ?><?= $ns ?></small>
        </div>

    <div style="margin-top:15px;">
        <table style="width:100%;font-size:14px;color:#000;">
            <tr><td><b>ID</b></td><td>: ${id}</td></tr>
            <tr><td><b>Nama</b></td><td>: ${name}</td></tr>
            <tr><td><b>Paket</b></td><td>: ${paket}</td></tr>
            <tr><td><b>Bulan</b></td><td>: ${bulan}</td></tr>
            <tr><td><b>Tanggal</b></td><td>: ${tanggal}</td></tr>
        </table>
    </div>

    <hr style="border:1px solid #000;">

    <table style="width:100%;font-size:14px;color:#000;">
        <tr><td>Tagihan</td><td align="right"><b>Rp ${tagihanRp.toLocaleString()}</b></td></tr>
        <tr><td>Tunggakan</td><td align="right"><b>Rp ${tunggakanRp.toLocaleString()}</b></td></tr>
        <tr><td>Admin</td><td align="right"><b>Rp 0</b></td></tr>
    </table>

    <hr style="border:2px solid #000;">

    <h2 style="text-align:right;font-weight:bold;color:#000;">
        Rp ${totalRp.toLocaleString()}
    </h2>

    <p>Status: <b>LUNAS</b></p>

    <div style="text-align:center;margin-top:30px;opacity:0.08;font-size:50px;font-weight:bold;">
        <?= $dn ?><?= $ns ?>
    </div>
</div>
`;

    Swal.fire({
        title: 'Invoice',
        html: htmlInvoice,
        width: 500,
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: '💾 Download',
        denyButtonText: '⚡ Bayar',
        cancelButtonText: 'Batal'
    }).then((result) => {

        const element = document.getElementById('invoice');

        if (result.isConfirmed) {
            // ✅ DOWNLOAD SAJA
html2pdf().set({
    margin: 10,
    filename: `invoice_${name}.pdf`,
    image: { type: 'jpeg', quality: 1 },
    html2canvas: { 
        scale: 3,           // 🔥 makin tinggi makin tajam
        useCORS: true,
        letterRendering: true
    },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
}).from(element).save();
        }

        else if (result.isDenied) {
            // ✅ BAYAR + AUTO DOWNLOAD
            html2pdf().from(element).save(`invoice_${name}.pdf`);

            setTimeout(() => {
                window.location.href = url;
            }, 800);
        }
    });
});  

   // 4. SINKRON MASAL (PATOKAN SIMPLE QUEUE)
    $('#btnBulkSync').on('click', function() {
        Swal.fire({
            title: 'Sinkronisasi Masal',
            text: "Sesuaikan status PPP Secret & Isolir mengikuti data Simple Queue?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#58a6ff',
            confirmButtonText: 'Ya, Sinkronkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Sedang menyelaraskan data MikroTik',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                // Ganti URL ini sesuai route Controller Sinkron Anda
                $.ajax({
                    url: "<?= BASE_URL; ?>/income/syncMasal", 
                    method: "GET",
                    success: function(response) {
                        Swal.fire('Berhasil!', 'Data PPP & Isolir telah diselaraskan.', 'success')
                        .then(() => { location.reload(); });
                    },
                    error: function() {
                        Swal.fire('Gagal!', 'Terjadi kesalahan koneksi.', 'error');
                    }
                });
            }
        });
    });
});
$('.btn-invoice').on('click', function() {

    const name = $(this).data('name');
    const target = $(this).data('target');
    const comment = $(this).data('comment');

    const parts = comment.split(" | ");
    const base = parseInt(parts[0] || 0);
    const total = parseInt(parts[1] || 0);
    const rawDate = parts[2] || '-';
    const statusCode = parts[3] || 'l';

    const tunggakan = Math.max(0, total - base);

    const tagihanRp = base * 1000;
    const tunggakanRp = tunggakan * 1000;
    const totalRp = total * 1000;

    // 🔥 Paket
    let paket = "Reguler";
    if (base == 75) paket = "Bronze";
    else if (base == 100) paket = "Silver";
    else if (base == 150) paket = "Gold";
    else if (base == 200) paket = "Fast";

    // 🔥 Bulan otomatis
    const jumlahBulan = Math.max(1, Math.floor(total / 100));
    const namaBulan = ["jan","feb","mar","apr","mei","jun","jul","agu","sep","okt","nov","des"];

    const sekarang = new Date();
    let bulanIndex = sekarang.getMonth();

    let listBulan = [];
    for (let i = 0; i < jumlahBulan; i++) {
        let idx = bulanIndex - i;
        if (idx < 0) idx += 12;
        listBulan.unshift(namaBulan[idx]);
    }

    let bulan = listBulan.join("-");

    // 🔥 ID
    const id = target.split('.').pop().padStart(3,'0');
	let tanggal = '-';

    if (rawDate) {
    const bulanNama = ["jan","feb","mar","apr","mei","jun","jul","agu","sep","okt","nov","des"];

    const t = rawDate.split('-'); // [YYYY, MM, DD]

    if (t.length === 3) {
        const tahun = t[0];
        const bulan = parseInt(t[1], 10) - 1;
        const hari = t[2];

        tanggal = `${hari}-${bulanNama[bulan]}-${tahun}`;
    }
	}
    // 🔥 HTML INVOICE
const htmlInvoice = `
<div id="invoice" style="font-family:Arial;color:#000;padding:20px;background:#fff;">
    
        <div style="background:#0d6efd;color:white;padding:10px;border-radius:8px;">
            <h3 style="margin:0;">INVOICE PEMBAYARAN</h3>
            <small><?= $dn ?><?= $ns ?></small>
        </div>

    <div style="margin-top:15px;">
        <table style="width:100%;font-size:14px;color:#000;">
            <tr><td><b>ID</b></td><td>: ${id}</td></tr>
            <tr><td><b>Nama</b></td><td>: ${name}</td></tr>
            <tr><td><b>Paket</b></td><td>: ${paket}</td></tr>
            <tr><td><b>Bulan</b></td><td>: ${bulan}</td></tr>
            <tr><td><b>Tanggal</b></td><td>: ${tanggal}</td></tr>
        </table>
    </div>

    <hr style="border:1px solid #000;">

    <table style="width:100%;font-size:14px;color:#000;">
        <tr><td>Tagihan</td><td align="right"><b>Rp ${tagihanRp.toLocaleString()}</b></td></tr>
        <tr><td>Tunggakan</td><td align="right"><b>Rp ${tunggakanRp.toLocaleString()}</b></td></tr>
        <tr><td>Admin</td><td align="right"><b>Rp 0</b></td></tr>
    </table>

    <hr style="border:2px solid #000;">

    <h2 style="text-align:right;font-weight:bold;color:#000;">
        Rp ${totalRp.toLocaleString()}
    </h2>

    <p>Status: <b>LUNAS</b></p>

    <div style="text-align:center;margin-top:30px;opacity:0.08;font-size:50px;font-weight:bold;">
        <?= $dn ?><?= $ns ?>
    </div>
</div>
`;

    Swal.fire({
        title: 'Invoice',
        html: htmlInvoice,
        width: 500,
        confirmButtonText: '💾 Download PDF'
    }).then((result) => {
        if (result.isConfirmed) {
            const element = document.getElementById('invoice');
html2pdf().set({
    margin: 10,
    filename: `invoice_${name}.pdf`,
    image: { type: 'jpeg', quality: 1 },
    html2canvas: { 
        scale: 3,           // 🔥 makin tinggi makin tajam
        useCORS: true,
        letterRendering: true
    },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
}).from(element).save();
        }
    });
});
/*
*Remote router
*/
$('.btn-open-router').on('click', function () {
    const ip = $(this).data('ip');
    const width = 1100;
    const height = 750;
    const left = (screen.width / 2) - (width / 2);
    const top = (screen.height / 2) - (height / 2);

    // Daftar port yang umum digunakan router
    const ports = [80, 8080, 81, 88];

    // Fungsi untuk membuka popup setelah port ditemukan
    const launch = (port) => {
        const url = `http://${ip}:${port}`;
        window.open(
            url,
            'RouterLocal_' + ip.replace(/\./g, '_'),
            `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`
        );
    };

    // Tampilkan loading sebentar (Opsional, pakai SweetAlert jika ada)
    console.log(`Mengecek akses ke ${ip}...`);

    // Logika Cek Port Tercepat
    let found = false;
    const promises = ports.map(port => {
        return fetch(`http://${ip}:${port}`, { mode: 'no-cors', cache: 'no-cache' })
            .then(() => {
                if (!found) {
                    found = true;
                    launch(port);
                }
            })
            .catch(() => { /* Abaikan port yang tertutup */ });
    });

    // Jika dalam 2 detik tidak ada port yang respon
    setTimeout(() => {
        if (!found) {
            // Fallback: Paksa buka port 80 jika pengecekan gagal
            launch(80);
        }
    }, 2000);
});

</script>
<?php
$content = ob_get_clean();
include dirname(__DIR__,2) . '/layouts/layout.php';