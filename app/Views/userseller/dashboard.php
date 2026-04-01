<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['sell'])) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - KWHotspot</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
   <link rel="stylesheet" href="<?= asset('css/doit.css') ?>?v=<?= time() ?>">
     <style>

	/* ===== HEADER ala DANA ===== */
.header {
    background: linear-gradient(135deg, #108ee9, #0a6cd6);
    padding: 18px;
    border-radius: 0 0 20px 20px;
    color: white;
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}

.user-info {
    font-size: 13px;
    opacity: 0.9;
}

/* Saldo besar */
.saldo-label {
    font-size: 11px;
    opacity: 0.8;
}

.saldo-value {
    font-size: 22px;
    font-weight: bold;
    letter-spacing: 1px;
}

/* ===== ICON MENU (Isi Saldo dll) ===== */
.top-menu {
    display: flex;
    justify-content: space-between;
    background: #161b22;
    padding: 12px;
    border-radius: 14px;
    margin-top: -20px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.4);
}

.top-menu a {
    flex: 1;
    text-align: center;
    color: #c9d1d9;
    text-decoration: none;
    font-size: 11px;
}

.top-menu i {
    display: block;
    font-size: 18px;
    margin-bottom: 4px;
    color: #58a6ff;
}

/* Hover smooth */
.top-menu a:hover {
    transform: translateY(-2px);
}

/* ===== VOUCHER CARD ala E-WALLET ===== */
.voucher-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px;
    border-radius: 14px;
    color: white;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease;
}

/* efek glass glow */
.voucher-card::after {
    content: "";
    position: absolute;
    top: 0;
    left: -60%;
    width: 50%;
    height: 100%;
    background: rgba(255,255,255,0.2);
    transform: skewX(-20deg);
}

/* hover animasi */
.voucher-card:hover {
    transform: translateY(-3px) scale(1.01);
    box-shadow: 0 8px 18px rgba(0,0,0,0.4);
}


}
/* info voucher */
.voucher-title {
    font-weight: bold;
    font-size: 13px;
}

.voucher-price {
    font-size: 14px;
    font-weight: bold;
}

/* stok bulat kanan */
.voucher-stok {
    background: rgba(0,0,0,0.3);
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
}

/* ===== NOTIF BADGE ===== */
#notif-badge {
    font-size: 9px;
    padding: 3px 6px;
}

/* ===== CHART CARD ===== */
.chart-container {
    background: #161b22;
    border-radius: 12px;
    padding: 10px;
}

/* ===== BUTTON NAV KECIL ===== */
.btn-nav {
    background: #21262d;
    border-radius: 6px;
    padding: 4px 8px;
    color: white;
}

/* ===== MOBILE FEEL ===== */
body {
	padding-bottom: 90px; /* 🔥 supaya tidak ketutup navbar */
    background: #0d1117;
	font-family: 'Inter', sans-serif;
}

/* spacing antar section */
.voucher-container {
    margin-top: 10px;
}
.card, .voucher-card, .top-menu {
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
}
.voucher-card:active {
    transform: scale(0.96);
}
  </style>
</head>
<body class="seller-dashboard">

<?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="<?= BASE_URL ?>/home" style="text-decoration: none; color: inherit;">
<?php endif; ?>

<div class="header">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <div class="user-info">
            <i class="fa fa-user-circle me-1"></i> <?= htmlspecialchars($data['user']) ?>
        </div>
        
        <div class="dropdown">
            <a href="#" class="text-white position-relative" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-bell fa-lg"></i>

                <?php if ($data['unread'] > 0): ?>
                <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $data['unread'] ?>
                </span>
                <?php endif; ?>
            </a>

            <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width: 280px; max-height: 350px; overflow-y: auto;">
                <li class="p-2 border-bottom fw-bold text-center">Notifikasi</li>

                <?php if (empty($data['notifList'])): ?>
                    <li><span class="dropdown-item text-muted text-center small">Tidak ada pesan</span></li>
                <?php else: ?>
                    <?php foreach ($data['notifList'] as $n): ?>
                        <li>
                            <a class="dropdown-item small border-bottom <?= (!($n['read'] ?? 1)) ? 'bg-light fw-bold' : '' ?>" href="#">
                                <div><?= htmlspecialchars($n['msg'] ?? $n['message'] ?? 'Pesan tidak ditemukan') ?></div>
                                <small class="text-muted" style="font-size: 9px;">
                                    <?= htmlspecialchars($n['datetime'] ?? date('Y-m-d H:i')) ?>
                                </small>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <span class="saldo-label">Saldo Anda</span>
    <span class="saldo-value">
        Rp <span id="realtime-saldo"><?= number_format($data['balance'], 0, ',', '.') ?></span>
    </span>
</div>

<?php if ($_SESSION['role'] === 'admin'): ?>
    </a>
<?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<div class="row g-1">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="font-size: 13px;">Statistik Penjualan</h6>
        <div class="d-flex align-items-center gap-2">
            <a href="?start=<?= $data['prevDate'] ?>" class="btn-nav"><i class="fa fa-chevron-left"></i></a>
            <span style="font-size: 10px; font-weight: bold;"><?= $data['startDate']->format('d M') ?></span>
            <a href="?start=<?= $data['nextDate'] ?>" class="btn-nav"><i class="fa fa-chevron-right"></i></a>
        </div>
    </div>
    
    <div class="chart-container" style="height: 120px;">
        <canvas id="incomeChart"></canvas>
    </div>

    <div class="row mt-2 text-center g-0 border-top pt-2">
        <div class="col">
            <span class="text-muted small d-block" style="font-size: 9px;">PROFIT</span>
            <span class="fw-bold text-success">Rp <?= number_format(array_sum($data['netData']), 0, ',', '.') ?></span>
        </div>
        <div class="col border-start">
            <span class="text-muted small d-block" style="font-size: 9px;">OMSET</span>
            <span class="fw-bold text-primary">Rp <?= number_format(array_sum($data['grossData']), 0, ',', '.') ?></span>
        </div>
    </div>
</div>

<div class="voucher-container">
    <div class="top-menu">
        <a href="#" onclick="openTopUpForm()">
            <i class="fa-solid fa-wallet"></i>
            <span>Isi Saldo</span>
        </a>
        <a href="#" onclick="openDepositForm()">
            <i class="fa-solid fa-hand-holding-dollar"></i>
            <span>Deposit</span>
        </a>
        <a href="#" onclick="showVoucherInfo()">
            <i class="fa-solid fa-share-nodes"></i>
            <span>Kirim WA</span>
        </a>
    </div>
    <div class="row g-1">
        <?php 
        $colors = ['#f26522', '#007bff', '#28a745', '#6f42c1', '#e83e8c', '#fd7e14'];
        foreach ($rates as $index => $rate): 
            $activeColor = $colors[$index % count($colors)];
            $currentStok = $stokPerProfile[$rate['profile']] ?? 0;
        ?>
        <div class="col-6">
            <form method="POST" action="<?= BASE_URL ?>/userseller/generate" id="form-<?= $index ?>">
                <input type="hidden" name="name" value="<?= $rate['name'] ?>">
                <input type="hidden" name="amount" value="<?= $rate['amount'] ?>">

                <input type="hidden" name="quantity" value="1"> <input type="hidden" name="limitbytes" value="<?= $rate['limitbytes'] ?>">
                <input type="hidden" name="profile" value="<?= $rate['profile'] ?>">
                <input type="hidden" name="margine" value="<?= $rate['margine'] ?>">
                <input type="hidden" name="length" value="<?= $rate['length'] ?>">
                
                <div class="voucher-card" style="background: <?= $activeColor ?>" onclick="confirmVoucher('form-<?= $index ?>', '<?= $rate['name'] ?>', '<?= $rate['amount'] ?>')">

                    <div class="voucher-info">
                        <div class="voucher-title"><?= htmlspecialchars($rate['name']) ?></div>
                        <div class="voucher-price">Rp <?= number_format($rate['amount'], 0, ',', '.') ?></div>
                        <div style="font-size: 9px; opacity: 0.9;">Limit: <?= $rate['limitbytes'] ?>GB</div>
                    </div>
                    <div class="voucher-stok"><?= $currentStok ?></div>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <script>
       /**
     * REAL-TIME SALDO & NOTIFIKASI
     * Gabungkan pengecekan dalam satu fungsi untuk efisiensi resource
     */
// 1. Ambil saldo awal dari PHP saat halaman dimuat agar sinkron
let lastProcessedBalance = <?= (int)($data['balance'] ?? 0) ?>; 

function updateDashboard() {
    fetch('<?= BASE_URL ?>/userseller/dashboard/check-notif')
        .then(res => res.json())
        .then(data => {
            
            // --- 1. UPDATE ANGKA PESAN (Selalu Update) ---
            const badge = document.getElementById('notif-badge');
            if (badge) {
                const count = parseInt(data.unread_count) || 0;
                if (count > 0) {
                    badge.innerText = count;
                    badge.style.display = 'inline-block';
                    // Tambahkan animasi getar agar terlihat ada pesan baru
                    badge.classList.add('animate-bounce'); 
                } else {
                    badge.style.display = 'none';
                }
            }

            // --- 2. CEK SALDO BARU ---
            if (data.found && parseInt(data.after) > lastProcessedBalance) {
                
                lastProcessedBalance = parseInt(data.after);

                // Update UI Saldo
                const el = document.getElementById('realtime-saldo');
                if (el) {
                    el.innerText = new Intl.NumberFormat('id-ID').format(data.after);
                    el.classList.add('text-success', 'fw-bold');
                    setTimeout(() => el.classList.remove('text-success', 'fw-bold'), 5000);
                }

                // Play Sound
                let audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
                audio.play().catch(() => {});

                // Pop Up
                Swal.fire({
                    icon: 'success',
                    title: 'Saldo Masuk!',
                    html: `<b>+ Rp ${parseInt(data.amount).toLocaleString('id-ID')}</b>`,
                    timer: 5000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        });
}

// Jalankan tiap 5 detik
setInterval(updateDashboard, 5000);

// Tandai terbaca saat klik lonceng
document.getElementById('notifDropdown').addEventListener('click', function () {
    fetch('<?= BASE_URL ?>/userseller/dashboard/mark_all_read')
        .then(() => {
            const badge = document.getElementById('notif-badge');
            if(badge) badge.style.display = 'none';
        });
});
// Tandai semua terbaca HANYA saat dropdown diklik
document.getElementById('notifDropdown').addEventListener('click', function () {
    fetch('<?= BASE_URL ?>/userseller/dashboard/mark_all_read')
        .then(() => {
            const badge = document.getElementById('notif-badge');
            if(badge) badge.style.display = 'none';
        });
}); </script>
    <script>
    /**
     * FUNGSI KONFIRMASI CETAK VOUCHER
     */
    function confirmVoucher(formId, name, price) {
        Swal.fire({
            title: 'Cetak Voucher?',
            html: `Paket: <b>${name}</b><br>Harga: <b>Rp ${parseInt(price).toLocaleString('id-ID')}</b>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Cetak',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) document.getElementById(formId).submit();
        });
    }


    /**
     * CHART KONFIGURASI
     */
    const ctx = document.getElementById('incomeChart');
    if(ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [
                    { label: 'Net', data: <?= json_encode($netData) ?>, backgroundColor: '#198754', borderRadius: 4 },
                    { label: 'Gross', data: <?= json_encode($grossData) ?>, backgroundColor: 'rgba(0, 140, 255, 0.4)', borderRadius: 4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { display: false }, x: { ticks: { font: { size: 8 } } } }
            }
        });
    }
	/**
 * FUNGSI TOP UP (Pilihan Nominal & Instruksi Bayar)
 */
function openTopUpForm() {
    Swal.fire({
        title: 'Isi Saldo Otomatis',
        html: `
            <div class="text-start">
                <label class="small fw-bold">Pilih Nominal:</label>
                <select id="swal-amount" class="form-select mb-3">
                    <option value="20000">Rp 20.000</option>
                    <option value="50000">Rp 50.000</option>
                    <option value="100000">Rp 100.000</option>
                </select>
                <label class="small fw-bold">Metode:</label>
                <select id="swal-method" class="form-select">
                    <option value="DANA">DANA (081328969125)</option>
                    <option value="BRI">BRI (706101019771530)</option>
                </select>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Lanjut Pembayaran',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#28a745'
    }).then((result) => {
        if (result.isConfirmed) {
            const amt = document.getElementById('swal-amount').value;
            const met = document.getElementById('swal-method').value;
            
            let instruksi = met === 'DANA' ? 'DANA 081328969125 a/n SURATNA' : 'BRI 706101019771530 a/n SURATNA';

            Swal.fire({
                title: 'Konfirmasi Transfer',
                html: `Silakan transfer <b>Rp ${parseInt(amt).toLocaleString('id-ID')}</b> ke:<br><br><b>${instruksi}</b><br><br><small class="text-danger">*Saldo masuk otomatis setelah konfirmasi.</small>`,
                icon: 'info',
                confirmButtonText: 'Konfirmasi via WA'
            }).then(() => {
                // Redirect ke WA Konfirmasi
                const msg = encodeURIComponent(`Halo Admin, saya ingin konfirmasi Top Up Saldo.\nUsername: <?= $data['user'] ?>\nNominal: Rp ${amt}\nMetode: ${met}`);
                window.open(`https://wa.me/6281328969125?text=${msg}`, '_blank');
            });
        }
    });
}

/**
 * FUNGSI DEPOSIT (Kirim Notifikasi ke Telegram Admin)
 */
// --- 2. FITUR FORM DEPOSIT ---

/**
 * Membuka Modal Form Deposit
 * Menggunakan pilihan dropdown untuk nominal dan textarea untuk pesan
 */
function openDepositForm() {
    Swal.fire({
        title: '📥 Permintaan Deposit',
        html: `
            <form id="depositForm" onsubmit="submitDeposit(event)" class="text-start">
                <div class="mb-3">
                    <label for="depositAmount" class="form-label small fw-bold">Jumlah Deposit</label>
                    <select class="form-select" id="depositAmount" required>
                        <option value="">-- Pilih Jumlah --</option>
                        <option value="50000">Rp 50.000</option>
                        <option value="100000">Rp 100.000</option>
                        <option value="150000">Rp 150.000</option>
                        <option value="200000">Rp 200.000</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="depositMessage" class="form-label small fw-bold">Pesan (opsional)</label>
                    <textarea class="form-control" id="depositMessage" rows="2" placeholder="Contoh: Sudah transfer via Dana..."></textarea>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-bold">Kirim ke Telegram Admin</button>
            </form>`,
        showConfirmButton: false,
        showCloseButton: true,
        width: 450,
        customClass: {
            popup: 'rounded-4'
        }
    });
}

/**
 * Menangani Pengiriman Data Deposit ke Telegram
 */
function submitDeposit(event) {
    event.preventDefault();

    // Ambil data dari form
    const amount = document.getElementById('depositAmount').value;
    const message = document.getElementById('depositMessage').value.trim();
    
    // Ambil data PHP (Pastikan variabel ini tersedia di scope global/controller)
    const username = "<?php echo $_SESSION['sell'] ?? 'Unknown'; ?>";
    const token = "<?php echo $teletoken; ?>";
    const chatId = "<?php echo $chatid; ?>";

    if (!amount) return;

    // Tampilkan Indikator Loading
    Swal.fire({
        title: 'Sedang Mengirim...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Menyiapkan Inline Keyboard untuk Admin di Telegram
    // Menggunakan pemisah underscore (_) agar mudah di-explode oleh bot
    const keyboard = {
        inline_keyboard: [
            [
                { text: "✅ ACC", callback_data: `acc_web_${username}_${amount}` },
                { text: "❌ Tolak", callback_data: `rej_web_${username}` }
            ]
        ]
    };

    // Format Pesan HTML untuk Telegram
    const textMsg = `📥 <b>REQ DEPOSIT (WEB)</b>\n\n` +
                    `👤 Seller: <b>${username}</b>\n` +
                    `💰 Nominal: <b>Rp ${parseInt(amount).toLocaleString('id-ID')}</b>\n` +
                    `📝 Pesan: <i>${message || '-'}</i>\n` +
                    `🕒 Waktu: <code>${new Date().toLocaleString('id-ID')}</code>`;

    // Kirim Request ke API Telegram
    fetch(`https://api.telegram.org/bot${token}/sendMessage`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            chat_id: chatId,
            text: textMsg,
            parse_mode: "HTML",
            reply_markup: keyboard
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            Swal.fire({
                icon: 'success',
                title: 'Terkirim!',
                text: 'Permintaan deposit telah diteruskan ke Admin. Silakan tunggu konfirmasi.',
                confirmButtonColor: '#3085d6'
            });
        } else {
            throw new Error(data.description);
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Gagal Mengirim',
            text: `Terjadi kendala: ${error.message}`,
            confirmButtonColor: '#d33'
        });
    });
}

/**
 * FUNGSI KIRIM WA (Pindah ke Halaman Voucher)
 */
function showVoucherInfo() {
    Swal.fire({
        title: 'Kirim Voucher via WA',
        text: 'Anda akan diarahkan ke daftar voucher untuk memilih voucher yang ingin dikirim.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Buka Voucher',
        confirmButtonColor: '#25D366' // Warna Hijau WA
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'voucher_seller.php';
        }
    });
}
    </script>
    
    <?php include dirname(__DIR__, 1) . '/layouts/footer.php'; ?>
</body>
</html>