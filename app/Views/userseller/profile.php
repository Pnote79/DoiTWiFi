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
  <title>Profil Seller</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="<?= asset('css/doit.css') ?>">  
  <style>
    body {
        padding-bottom: 90px; /* 🔥 supaya tidak ketutup navbar */
    }
  </style>
</head>
<body class="seller-aktivitas">

<div class="header d-flex justify-content-between align-items-center">
  <a href="javascript:history.back()" class="back-button">
    <i class="fa fa-arrow-left"></i> Kembali
  </a>
  <span class="font-weight-bold">Akun Saya</span>
  <div style="width: 70px"></div>
</div>

<div class="container">
  <div class="promo-box">
    <div class="text-center mb-4">
        <i class="fa fa-user-circle fa-4x text-primary"></i>
        <h5 class="mt-2 mb-0"><?= htmlspecialchars($sellerData['sellername'] ?? 'User') ?></h5>
        <small class="text-muted">Seller Partner</small>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/userseller/profile/update">
      <div class="form-group">
        <label class="text-muted"><i class="fa fa-user me-2"></i> Nama Seller</label>
        <input type="text" class="form-control bg-light" name="sellername" value="<?= htmlspecialchars($sellerData['sellername'] ?? '') ?>" readonly>
      </div>

      <div class="form-group">
        <label class="text-muted"><i class="fa fa-lock me-2"></i> Password Baru</label>
        <input type="password" class="form-control" name="password" placeholder="Kosongkan jika tidak ingin diubah">
      </div>

      <div class="form-group">
        <label class="text-muted"><i class="fab fa-whatsapp me-1"></i> No. WhatsApp</label>
        <input type="text" class="form-control" name="whatsapp" value="<?= htmlspecialchars($sellerData['sellerphone'] ?? '') ?>" placeholder="Contoh: 08123xxx">
      </div>

      <button type="submit" class="btn btn-primary btn-block shadow-sm">
        <i class="fa fa-save me-1"></i> Simpan Perubahan
      </button>
    </form>

    <hr>

    <button type="button" onclick="confirmLogout()" class="btn btn-outline-danger btn-block">
        <i class="fa fa-sign-out-alt me-1"></i> Logout
    </button>
  </div>
</div>
<?php include dirname(__DIR__, 1) . '/layouts/footer.php'; ?>
<script>
// 1. Deteksi Pesan Sukses/Gagal dari Session (PHP)
<?php if (isset($_SESSION['msg'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '<?= $_SESSION['msg']; ?>',
        timer: 3000,
        showConfirmButton: false
    });
    <?php unset($_SESSION['msg']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: '<?= $_SESSION['error']; ?>',
    });
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

// 2. Fungsi Konfirmasi Logout
function confirmLogout() {
    Swal.fire({
        title: 'Logout?',
        text: "Anda akan keluar dari sesi ini.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Keluar!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "<?= BASE_URL ?>/logout";
        }
    })
}
</script>

</body>
</html>