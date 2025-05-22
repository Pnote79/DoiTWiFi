<?php
session_start();
include("../class/mt_resources.php");

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>🧑‍💼Admin Setting</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../css/style.css" />
  <style>
    body { background-color: #f8f9fa; }
    .card { margin-bottom: 20px; }
    .table td { vertical-align: middle; }
    .btn i { pointer-events: none; }
  </style>
</head>
<body>
<?php include('../page/navigation.php'); ?>
 <div class="card-header bg-dark text-white">
 <?php if (isset($_GET['status']) && $_GET['status'] === 'saved'): ?>
  <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
    ✅ Data berhasil disimpan.
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
<?php endif; ?>
  </div>
<div class="container-fluid mt-4">
  <div class="row">
  <!-- Setting Mikrotik di kiri -->
  <div class="col-md-6">
      <form action="../post/addmikrodata.php" method="post">
    <div class="card-body">
  <div class="form-group">
    <label for="ipmik">IP MikroTik</label>
    <input type="text" class="form-control" id="ipmik" name="ipmik" value="<?php echo $mtip ; ?>" required>
  </div>
  <div class="form-group">
    <label for="usermik">Username</label>
    <input type="text" class="form-control" name="usermik" value="<?php echo $mtuser; ?>" required>
  </div>
  <div class="form-group">
    <label for="passmik">Password</label>
    <input type="text" class="form-control" name="passmik" value="****" required>
  </div>
  <div class="form-group">
    <label for="hotmik">Hotspot Name</label>
    <input type="text" class="form-control" name="hotmik" value="<?php echo $dns; ?>" required>
  </div>
  <div class="form-group">
    <label for="dnsmik">DNS Name</label>
    <input type="text" class="form-control" name="dnsmik" value="<?php echo $vendo_main['mtdns']; ?>" required>
  </div>
  <button type="submit" class="btn btn-success">💾 Simpan Mikrotik</button>
  <button type="button" class="btn btn-info ml-2" id="pingBtn">Ping</button>

  <!-- Hasil ping -->
  <div id="pingResult" class="mt-3 p-2 bg-light border rounded small font-monospace"></div>
</div>
</form>
  </div>

  <!-- Setting Admin & Telegram di kanan -->
  <div class="col-md-6">
    <!-- Admin -->
    <form action="../post/addselldata.php" method="post">

       
        <div class="card-body">
          <div class="form-group">
            <label for="adminname">User Admin</label>
            <input type="text" class="form-control" name="adminname" value="<?php echo $adminname; ?>" required>
          </div>
          <div class="form-group">
            <label for="adminpass">Password</label>
            <input type="text" class="form-control" name="adminpass" value="****" required>
          </div>
          <button type="submit" class="btn btn-success">💾 Simpan Admin</button>
          <a class="btn btn-success ml-2" href="createvoucher.php">
            <i class="bi bi-plus-circle"></i> Add Hotspot Profile
          </a>
        </div>
    
    </form>

    <!-- Telegram Bot -->
    <form action="../post/addmikrodata.php" method="post">
      
        <div class="card-body">
          <div class="form-group">
            <label for="teletoken">TokenBot</label>
            <input type="text" class="form-control" name="teletoken" value="<?php echo $teletoken; ?>" required>
          </div>
          <div class="form-group">
            <label for="chat_id">ChatId</label>
            <input type="text" class="form-control" name="chat_id" value="<?php echo $chatid; ?>" required>
          </div>
          <button type="submit" class="btn btn-success">💾 Simpan Telegram</button>
        </div>
     
    </form>
  </div>
</div>

  
</div>
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>

<script>
  $('#pingBtn').click(function () {
    const ip = $('#ipmik').val();
    $('#pingResult').html('⏳ Sedang ping ke ' + ip + '...');

    $.get('pingtest.php', { ip: ip }, function (data) {
      $('#pingResult').html(data);
    }).fail(function () {
      $('#pingResult').html('❌ Gagal menghubungi server.');
    });
  });
</script>

</body> 
</html> 
