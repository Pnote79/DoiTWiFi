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
    <title>Cetak Voucher - Doitwifi</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="<?= asset('css/doit.css') ?>">
<style>
    .container-vouchers {
        display: flex;
        flex-direction: column;
        gap: 15px;
        padding: 20px;
        align-items: center;
    }

    /* Card Utama */
    .voucher-card {
        display: flex;
        align-items: stretch; /* MEMBUAT HARGA OTOMATIS TINGGI FULL */
        background: #fff;
        border: 1.5px dashed #ccc;
        border-radius: 15px;
        width: 320px;
        overflow: hidden; /* MEMOTONG SUDUT HARGA AGAR MENGIKUTI RADIUS CARD */
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        cursor: pointer;
        position: relative;
    }

    /* Bagian Harga (Sisi Kiri Berwarna) */
    .price-side {
        background: #f1f1f1; /* Warna abu muda */
        color: #333;
        width: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
        border-right: 1.5px dashed #ccc;
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        padding: 0; /* Pastikan padding nol agar warna penuh */
        margin: 0;
    }

    /* Isi Utama */
    .main-body {
        flex: 1;
        padding: 20px 15px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .host-name {
        font-size: 30px;
        color: #444;
        margin-bottom: 5px;
    }

    .v-code {
        font-size: 28px;
        font-weight: 800;
        color: #e74c3c;
        margin: 5px 0;
        letter-spacing: 1px;
    }

    .v-details {
        font-size: 13px;
        color: #555;
        line-height: 1.4;
    }

    /* QR Placeholder - Jika ingin diletakkan di dalam */
    .qr-area {
        margin-top: 10px;
        opacity: 0.6;
    }

    @media print {
        .no-print { display: none; }
    }
</style>
</head>
<body>

<div class="no-print" style="background:#fff; padding:15px; border-bottom:1px solid #ccc; margin-bottom:20px;">
    
    <a href="<?= BASE_URL ?>/userseller/dashboard" style="margin-left:10px; text-decoration: none; color: #007bff;">Kembali ke Dashboard</a>
</div>

<div class="container-vouchers">
    <?php foreach ($codes as $vc): 
        $wa_message = "Voucher Hotspot: *$vc*\nPaket: *{$profile}*\nKuota: *{$limit} GB*\nLogin: *http://{$dnsName}*";
        $wa_link = "https://api.whatsapp.com" . urlencode($wa_message);
    ?>
        <div class="voucher-card" onclick="shareWA('<?= $wa_link ?>', '<?= $vc ?>', '<?= $profile ?>', '<?= number_format($amount, 0, ',', '.') ?>')">
            
            <!-- Harga (Sekarang pasti terlihat di kolom kiri) -->
            <div class="price-side">
                Rp <?= number_format($amount, 0, ',', '.') ?>
            </div>

            <!-- Konten Tengah -->
            <div class="main-body">
                <div class="host-name">
				<span><?= $dn ?? 'DOiT' ?></span><span><?= $ns ?? 'WiFi' ?></span>
				</div>
                <div class="v-code"><?= $vc ?></div>
                <div class="v-details">
                    Paket: <?= htmlspecialchars($profile) ?><br>
                    Kuota: <?= $limit ?> GB
                </div>
            </div>

            <!-- Ikon QR Pojok Kiri Bawah -->

        </div>
    <?php endforeach; ?>
</div>

<script>
    // 1. Popup Berhasil (Tetap sama)
    Swal.fire({
        title: 'Berhasil!',
        html: `
            <div style="text-align: center;">
                <p>Pembuatan <b><?= count($codes) ?> voucher</b> sukses.</p>
                <hr>
                <div style="background: #f8f9fa; padding: 10px; border-radius: 8px;">
                    <small>Harga Paket:</small><br>
                    <b style="font-size: 1.2em;">Rp <?= number_format($amount, 0, ',', '.') ?></b><br>
                    <small>Sisa Saldo Anda:</small><br>
                    <b style="color: #28a745;">Rp <?= number_format($balance_after, 0, ',', '.') ?></b>
                </div>
            </div>
        `,
        icon: 'success',
        confirmButtonText: 'Oke',
        confirmButtonColor: '#3085d6'
    });

    // 2. Fungsi Share WhatsApp (Perbaikan sintaks & dinamis)
    function shareWA(link, code, profile, price) {
        Swal.fire({
            title: 'Share Voucher?',
            html: `
                <div style="text-align: center;">
                    <p>Kode Voucher: <br><b style="font-size: 1.5em; color: #e74c3c;">${code}</b></p>
                    <hr>
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 8px;">
                        <div style="margin-bottom: 5px;">
                            <small style="color: #666;">Paket:</small><br>
                            <b>${profile}</b>
                        </div>
                        <div>
                            <small style="color: #666;">Harga:</small><br>
                            <b style="color: #28a745;">Rp ${price}</b>
                        </div>
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#25D366',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Kirim WA',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open(link, '_blank');
            }
        });
    }
</script>
  <?php include dirname(__DIR__, 1) . '/layouts/footer.php'; ?>
</body>
</html>