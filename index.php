<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KWHotspot Login</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class=bg-dark>
<div class="login-container bg-dark text-light">
    <div id="brandName">
      <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" fill="currentColor" class="bi bi-incognito" viewBox="0 0 640 512">
        <path d="M634.9 154.9C457.7-9 182.2-8.9 5.1 154.9c-6.7 6.2-6.8 16.6-.4 23l34.2 34c6.1 6.1 16 6.2 22.4 .4 145.9-133.7 371.3-133.7 517.3 0 6.4 5.9 16.3 5.7 22.4-.4l34.2-34c6.4-6.4 6.3-16.8-.4-23zM320 352c-35.4 0-64 28.7-64 64s28.7 64 64 64 64-28.7 64-64-28.7-64-64-64zm202.7-83.6c-115.3-101.9-290.2-101.8-405.3 0-6.9 6.1-7.1 16.7-.6 23.2l34.4 34c6 5.9 15.7 6.3 22.1 .8 84-72.6 209.7-72.4 293.5 0 6.4 5.5 16.1 5.1 22.1-.8l34.4-34c6.6-6.5 6.3-17.1-.6-23.2z"/>
      </svg>
      <span id="JuanFi">KWH</span><span id="Man">otspot</span>
    </div>

    <?php
    // Cek jika ada parameter error
    $error_message = '';
    if (isset($_GET['error'])) {
      if ($_GET['error'] == 1) {
        $error_message = '❌ Username atau Password salah!';
      } elseif ($_GET['error'] == 2) {
        $error_message = '⚠️ Gagal terhubung ke Mikrotik!';
      }
    }
    ?>

    <?php if ($error_message): ?>
      <div class="alert alert-danger text-center" role="alert">
        <?= $error_message ?>
      </div>
    <?php endif; ?>

    <form id="loginForm" action="assets/page/connect.php" method="post">
      <div class="mb-3">
        <label for="mtUsername" class="form-label">Username</label>
        <input type="text" class="form-control" name="mtUsername" id="mtUsername" required>
      </div>
      <div class="mb-3">
        <label for="mtPassword" class="form-label">Password</label>
        <input type="password" class="form-control" name="mtPassword" id="mtPassword" required>
      </div>
      <button type="submit" id="submitBtn" class="btn btn-success w-100">
        <span class="spinner-border spinner-border-sm d-none" id="loadingSpinner" role="status" aria-hidden="true"></span>
        <span id="btnText">Login</span>
      </button>
    </form>
  </div>

  <script src="assets/js/jquery.min.js"></script>
  <script>
    $('#loginForm').on('submit', function () {
      $('#submitBtn').prop('disabled', true);
      $('#loadingSpinner').removeClass('d-none');
      $('#btnText').text(' Logging in...');
    });
  </script>
 
<script>
  $('#loginForm').on('submit', function () {
    $('#submitBtn').prop('disabled', true);
    $('#loadingSpinner').removeClass('d-none');
    $('#btnText').text(' Logging in...');

    const username = document.getElementById('mtUsername').value;
    const password = document.getElementById('mtPassword').value;

    // Simpan ke localStorage
    localStorage.setItem("sell", username);
    localStorage.setItem("pass", password);

    // Masukkan juga ke input hidden (jika perlu diproses di PHP)
    document.getElementById("sellerHidden").value = username;
  });
</script>

</body>
</html>
