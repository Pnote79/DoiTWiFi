<?php
$current_uri = $_SERVER['REQUEST_URI'];
$path = trim(parse_url($current_uri, PHP_URL_PATH), '/');

// Ambil segment terakhir
$segments = explode('/', $path);
$current_page = end($segments);

// Fungsi active
function is_active($name, $current_page) {
    return ($current_page === $name) ? 'active' : '';
}
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  * {
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
  }

  .bottom-nav {
    position: fixed;
    bottom: 0;
    width: 100%;
    background: #ffffff;
    border-top: 1px solid #eeeeee;
    display: flex;
    justify-content: space-around;
    align-items: center; /* Memastikan semua menu sejajar secara vertikal */
    padding: 8px 0;
    z-index: 999;
    height: 65px; /* Ketinggian tetap agar rapi */
  }

  .bottom-nav a {
    text-decoration: none;
    color: #888888; /* Warna default abu-abu agar menu aktif lebih menonjol */
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: all 0.2s ease;
  }

  .bottom-nav div.label {
    font-size: 11px;
    margin-top: 4px;
  }

  .bottom-nav i {
    font-size: 20px;
    display: block;
  }

  /* Warna ikon saat tidak aktif */
  .bottom-nav a i {
    color: #A0AEC0;
  }

  /* Warna menu saat AKTIF */
  .bottom-nav a.active i,
  .bottom-nav a.active div.label {
    color: #008CFF;
    font-weight: 600;
  }

  /* Gaya Khusus Tombol QR Scan (Tengah) */
  .pay-wrapper {
    position: relative;
    top: -15px; /* Mengangkat tombol ke atas */
  }

  .pay {
    background: linear-gradient(135deg, #008CFF, #005999);
    color: white !important;
    width: 55px;
    height: 55px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    box-shadow: 0 4px 10px rgba(0, 140, 255, 0.4);
    transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: 4px solid #fff; /* Memberikan border putih agar terlihat terpisah dari nav */
  }

  .pay:active {
    transform: scale(0.85);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
  }

  .pay i {
    color: white !important;
  }
</style>

<div class="bottom-nav">
  <a href="<?= BASE_URL ?>/userseller/dashboard" 
     class="<?= is_active('dashboard', $current_page) ?>">
    <i class="fa-solid fa-house"></i>
    <div class="label">Beranda</div>
  </a>

  <a href="<?= BASE_URL ?>/userseller/activitas" 
     class="<?= is_active('activitas', $current_page) ?>">
    <i class="fa-solid fa-clock-rotate-left"></i>
    <div class="label">Aktivitas</div>
  </a>

  <div class="pay-wrapper">
    <a href="#" class="pay" onclick="konfirmasiBot(event)">
      <i class="fa-solid fa-qrcode"></i>
    </a>
  </div>

  <a href="<?= BASE_URL ?>/userseller/voucher_seller" 
     class="<?= is_active('voucher_seller', $current_page) ?>">
    <i class="fa-solid fa-wallet"></i>
    <div class="label">Voucher</div>
  </a>

  <a href="<?= BASE_URL ?>/userseller/profile" 
     class="<?= is_active('profile', $current_page) ?>">
    <i class="fa-solid fa-user"></i>
    <div class="label">Aku</div>
  </a>
</div>
<script>
function konfirmasiBot(event) {
    event.preventDefault();

    Swal.fire({
        title: 'Beli Voucher?',
        text: "Daftar atau Beli Voucher Lewat aplikasi Telegram",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#008CFF',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Tampilkan Loading Spinner
            Swal.fire({
                title: 'Mengalihkan...',
                text: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Beri jeda 1.5 detik agar efek loading terasa, lalu buka Telegram
            setTimeout(() => {
                window.open("https://t.me/KaW2_bot", "_blank");
                // Tutup loading setelah dialihkan
                Swal.close();
            }, 1500);
        }
    });
}
</script>