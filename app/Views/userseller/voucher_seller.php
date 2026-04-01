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
  <title>Voucher</title>

  <!-- CSS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/doit.css') ?>">

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    body {
        padding-bottom: 90px; /* 🔥 supaya tidak ketutup navbar */
    }
@media (max-width: 576px) {
    /* Pastikan container tidak scroll samping */
    .table-responsive {
        overflow-x: hidden !important;
    }

    /* Paksa tabel muat dalam 1 layar */
    #vcTable {
        display: table !important; /* Kembalikan ke format tabel asli */
        table-layout: fixed; /* Kunci lebar kolom */
        width: 100% !important;
        font-size: 10px; /* Perkecil font agar muat 1 baris */
    }

    #vcTable thead {
        display: table-header-group !important; /* Munculkan kembali header */
    }

    #vcTable tr {
        display: table-row !important; /* Kembalikan baris */
    }

    #vcTable td, #vcTable th {
        display: table-cell !important; /* Kembalikan sel */
        padding: 5px 2px !important;
        white-space: nowrap; /* Mencegah teks turun ke bawah */
        overflow: hidden;
        text-overflow: ellipsis; /* Jika teks terlalu panjang, akan jadi titik-titik (...) */
    }

    /* Hilangkan teks/icon yang memakan tempat */
    .share-wa i { display: none; } 
    .badge { font-size: 9px; padding: 2px 4px; }
}


  </style>
</head>

<body>
<div class="header d-flex justify-content-between align-items-center bg-primary">
    <a href="javascript:history.back()" class="text-success text-decoration-none"></a>
    <span class="fw-bold fs-6">Voucher Yang Belum Terpakai</span>
    <div style="width: 50px"></div>
</div>

<!-- CONTENT -->
<div class="container-fluid mt-3">

  <small>Bagikan/Kirim Voucher lewat wa klik kode Voucher</small>

  <!-- SEARCH -->
  <input id="vcInput"
         onkeyup="searchVoucher()"
         type="text"
         class="form-control mb-3"
         placeholder="Cari berdasarkan Paket...">

  <!-- TABLE -->
  <div class="table-responsive">
    <table id="vcTable" class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th class="d-none d-md-table-cell">Id</th> 

          <th class="d-none d-md-table-cell">Status</th>
          <th>Voucher</th>
          <th>Profile</th>
          <th>Harga</th>
        </tr>
      </thead>

      <tbody>
      <?php if (!empty($vouchers)): ?>
        <?php foreach ($vouchers as $v): ?>
          <tr>

            <td class="d-none d-md-table-cell" data-label="Id"><?= $v['id'] ?></td>

            <td class="d-none d-md-table-cell" data-label="Status">
              <span class="badge <?= $v['status'] == 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                <?= $v['status'] ?>
              </span>
            </td>

            <td data-label="Voucher">
              <a href="#"
                 class="share-wa fw-bold text-primary text-decoration-none"
                 data-username="<?= $v['username'] ?>"
                 data-profile="<?= $v['profile'] ?>"
                 data-amount="<?= $v['amount'] ?>">
                <i class="fab fa-whatsapp me-1"></i><?= $v['username'] ?>
              </a>
            </td>

            <td data-label="Profile">
              <span class="badge bg-light text-dark border">
                <?= $v['profile'] ?>
              </span>
            </td>

            <td data-label="Harga" class="fw-bold text-success">
              Rp<?= number_format($v['amount']) ?>
            </td>

          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="5" class="text-center py-3">
            Tidak ada voucher tersedia
          </td>
        </tr>
      <?php endif; ?>
      </tbody>

    </table>
  </div>

  <!-- PAGINATION -->
  <div id="buttons" class="d-flex flex-wrap justify-content-center mt-3"></div>

</div>

<?php include dirname(__DIR__, 1) . '/layouts/footer.php'; ?>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>

<script>
// =========================
// SHARE WA
// =========================
$(document).on("click", ".share-wa", function(e) {
  e.preventDefault();

  const username = $(this).data("username");
  const profile  = $(this).data("profile");
  const amount   = $(this).data("amount");

  const pesan = `Kode: ${username}\nPaket: ${profile}\nHarga: Rp ${amount}`;
  const linkWA = `https://wa.me/?text=${encodeURIComponent(pesan)}`;

  Swal.fire({
    title: "Bagikan via WhatsApp",
    text: pesan,
    icon: "info",
    showCancelButton: true,
    confirmButtonText: "Bagikan",
    cancelButtonText: "Tutup"
  }).then((result) => {
    if (result.isConfirmed) {
      window.open(linkWA, "_blank");
    }
  });
});


// =========================
// SEARCH
// =========================
function searchVoucher() {
  const input = document.getElementById("vcInput");
  const filter = input.value.toUpperCase();
  const tr = document.querySelectorAll("#vcTable tbody tr");

  tr.forEach(row => {
    const text = row.innerText.toUpperCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
}


// =========================
// PAGINATION
// =========================
const table = document.getElementById("vcTable");
const perPage = 10;
const rows = table.querySelectorAll("tbody tr");
const pageCount = Math.ceil(rows.length / perPage);

function showPage(page) {
  rows.forEach((row, index) => {
    row.style.display =
      (index >= (page-1)*perPage && index < page*perPage) ? "" : "none";
  });

  renderButtons(page);
}

function renderButtons(current) {
  let html = '';

  for (let i = 1; i <= pageCount; i++) {
    html += `<button class="btn btn-sm ${i===current?'btn-primary':'btn-secondary'} mx-1"
              onclick="showPage(${i})">${i}</button>`;
  }

  document.getElementById("buttons").innerHTML = html;
}

// init
if (rows.length > 0) showPage(1);

</script>

</body>
</html>