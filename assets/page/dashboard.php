<?php
session_start();
include("../class/mt_resources.php");
$user = new User("../json/rate.json");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
	
</head>
<body class="bg-dark">
<?php include("navigation.php"); ?>


<div class="container mt-3">
<?php if ($_SESSION['role'] === 'admin') { ?>
<div class="d-block">
  <button type="button" class="btn btn-secondary btn-sm" data-toggle="modal" data-target="#addRateModal"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
  <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
  </svg> Add profile Hotspot </button>
</div>
<?php } ?>
<ul class="list-group mt-3">
<?php
$toPrint = 0;
foreach ($user->getUsers() as $rate) {
    $toPrint++;
    echo '<li class="list-group-item list-group-item-dark d-flex justify-content-between align-items-center">';
    echo '<div>';
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ticket-perforated mr-2" viewBox="0 0 16 16">
        <path d="M4 4.85v.9h1v-.9H4Zm7 0v.9h1v-.9h-1Zm-7 1.8v.9h1v-.9H4Zm7 0v.9h1v-.9h-1Zm-7 1.8v.9h1v-.9H4Zm7 0v.9h1v-.9h-1Zm-7 1.8v.9h1v-.9H4Zm7 0v.9h1v-.9h-1Z"/>
        <path d="M1.5 3A1.5 1.5 0 0 0 0 4.5V6a.5.5 0 0 0 .5.5 1.5 1.5 0 1 1 0 3 .5.5 0 0 0-.5.5v1.5A1.5 1.5 0 0 0 1.5 13h13a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 0-.5-.5 1.5 1.5 0 0 1 0-3A.5.5 0 0 0 16 6V4.5A1.5 1.5 0 0 0 14.5 3h-13ZM1 4.5a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v1.05a2.5 2.5 0 0 0 0 4.9v1.05a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-1.05a2.5 2.5 0 0 0 0-4.9V4.5Z"/>
    </svg>';
    echo '<strong>' . htmlspecialchars($rate['name']) . '</strong>';
    echo '<small class="text-muted ml-2"><i>(Amount: Rp' . number_format($rate['amount'], 0, ',', '.') . ')</i></small>';
    echo '</div>';

    echo '<div class="btn-group">';
    echo '<button class="btn btn-success btn-sm" data-toggle="modal" data-target="#printModal' . $toPrint . '" onclick="closeModal();">Generate</button>';

    if ($_SESSION['role'] === 'admin') {
        echo '<a href="deleterate.php?id=' . urlencode($rate['id']) . '" class="btn btn-danger btn-sm ml-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                </svg> Delete
            </a>';
    }
    echo '</div>';
    echo '</li>';



                    echo '
                    
                    <div class="modal fade" id="printModal'.$toPrint.'" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content bg-light">
      <div class="modal-header">
        <h5 class="modal-title">Cetak Voucher</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <form action="../post/generate.php" method="post">

            <div class="form-group">
                <label for="name">Paket</label>
                <input type="text" class="form-control" name="name" value="'.$rate['name'].'" readonly>
            </div>

            <div class="form-group">
                <label for="quantity">Jumlah</label>
                <input type="number" class="form-control" name="quantity"  value="'.$rate['quantity'].'" >
            </div>

            <div class="form-group">
                <label for="amount">Harga</label>
                <input type="text" class="form-control" name="amount"   value="'.$rate['amount'].'"  readonly >
            </div>
              <input type="hidden" name="margine" value="'.$rate['margine'].'" >
            <div class="form-group">
                <label for="limitbytes">Data</label>
                <input type="text" class="form-control" name="limitbytes" value="'.$rate['limitbytes'].'" readonly>
            </div>

            <div class="form-group">
                <label for="length">Panjang</label>
                <input type="number" class="form-control" name="length" min="4" max="8"  value="'.$rate['length'].'">
            </div>

            <div class="form-group">
                <label for="profile">Aktip</label>
                <input type="text" class="form-control" name="profile"  value="'.$rate['profile'].'" readonly>
            </div>

            <div class="form-group">
                <label for="vendo">Nama Penjual</label>
                <input  class="form-control" name="vendo" placeholder="JuanFi Vendo" value="'.$_SESSION['sell'].'" readonly>
            </div>

            <button type="submit" class="btn btn-primary mt-4 w-100"> Generate </button>

        </form>

      </div>
      <div class="modal-footer">        
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
                    
                    
                    ';
                }
            ?>
        </ul>
<?php if ($_SESSION['role'] === 'seller') { ?>
<!-- Tombol Chat Mengambang -->
<style>
  #floatingChatBtn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    
    padding: 15px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    transition: transform 0.2s ease;
  }

  #floatingChatBtn:hover {
    transform: scale(1.1);
  }

  #floatingChatBtn i {
    font-size: 20px;
  }
</style>

<!-- Tombol Chat dengan Icon -->
<button id="floatingChatBtn" data-toggle="modal" data-target="#depositModal" title="Permintaan Deposit">
  <small>➕💰 Top Up </small>
</button>

<!-- Font Awesome Icon CDN (jika belum ada) -->
<?php } ?>



</div>

<!--tambah user hotspot profile jika login == admin-->
<div class="modal fade" id="addRateModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content bg-light">
      <div class="modal-header">
        <h5 class="modal-title">Rate setting</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <form action="addrate.php" method="post">

            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" name="name" placeholder="1 hour" required>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" class="form-control" name="quantity" placeholder="10" required>
            </div>

            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" class="form-control" name="amount" placeholder="5" required>
            </div>
			
            <div class="form-group">
                <label for="limitbytes">DATA <small></small></label>
                <input type="number" class="form-control" name="limitbytes" placeholder="60" required>
            </div>

           
            <div class="form-group">
                <label for="length">Length</label>
                <input type="number" class="form-control" name="length" min="4" max="*" placeholder="5" required>
            </div>

            <div class="form-group">
                <label for="profile">Profile</label>
                <input type="text" class="form-control" name="profile" placeholder="default" required>
            </div>

            <div class="form-group">
                <label for="vendo">Vendo name</label>
                <input type="text" class="form-control" name="vendo" placeholder="" >
            </div>

            <button type="submit" class="btn btn-primary mt-4 w-100">Save changes</button>

        </form>

      </div>
      <div class="modal-footer">        
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Deposit Modal -->
<div class="modal fade" id="depositModal" tabindex="-1" role="dialog" aria-labelledby="depositModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document">
    <form id="depositForm">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header">
          <h5 class="modal-title" id="depositModalLabel">Permintaan Deposit</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          
          <div class="form-group">
            <label for="depositAmount">Jumlah Deposit</label>
            <select class="form-control" id="depositAmount" required>
              <option value="">-- Pilih Jumlah --</option>
              <option value="50000">Rp 50.000</option>
              <option value="100000">Rp 100.000</option>
              <option value="150000">Rp 150.000</option>
              <option value="200000">Rp 200.000</option>
            </select>
          </div>

          <div class="form-group">
            <label for="depositMessage">Pesan (opsional)</label>
            <textarea class="form-control" id="depositMessage" rows="2" placeholder="Contoh: Tolong cepat dikonfirmasi..."></textarea>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning btn-block">Kirim Permintaan</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $('#depositForm').submit(function(event) {
    event.preventDefault();

    const depositAmount = $('#depositAmount').val();
    const depositMessage = $('#depositMessage').val();
    const username = "<?php echo $_SESSION['sell'] ?? 'Unknown'; ?>";
    const telegramBotToken = "<?php echo $teletoken; ?>";
    const chatId = "<?php echo $chatid; ?>";

    const telegramMessage = `📥 *Permintaan Deposit*\n👤 Seller: ${username}\n💰 Jumlah: Rp ${depositAmount}\n📝 Pesan: ${depositMessage}`;

    $.post(`https://api.telegram.org/bot${telegramBotToken}/sendMessage`, {
      chat_id: chatId,
      text: telegramMessage,
      parse_mode: "Markdown"
    });

    alert('Permintaan deposit Anda telah dikirim!');
    $('#depositModal').modal('hide');
  });
</script>


<script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>    
<script> setInterval(function() { var time = new Date().toLocaleString(); $("#timeDiv").html(time); }, 1000);</script> 
</body>
</html>