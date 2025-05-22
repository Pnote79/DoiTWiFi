<?php
include("../class/mt_resources.php");
$user = new User("../json/rate.json");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Generate</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../css/style.css" />
</head>
<body class="bg-dark">
<?php include("navigation.php"); ?>

<div class="container">
  <div class="d-block rounded p-4 mt-5 bg-dark text-light" style="box-shadow:0px 2px 5px rgba(0,0,0,0.5)">
    <span class="d-block border-bottom mb-2">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-piggy-bank" viewBox="0 0 16 16">
        <path d="M5 6.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0zm1.138-1.496A6.613 6.613 0 0 1 7.964 4.5c.666 0 1.303.097 1.893.273a.5.5 0 0 0 .286-.958A7.602 7.602 0 0 0 7.964 3.5c-.734 0-1.441.103-2.102.292a.5.5 0 1 0 .276.962z" />
        <path fill-rule="evenodd" d="M7.964 1.527c-2.977 0-5.571 1.704-6.32 4.125h-.55A1 1 0 0 0 .11 6.824l.254 1.46a1.5 1.5 0 0 0 1.478 1.243h.263c.3.513.688.978 1.145 1.382l-.729 2.477a.5.5 0 0 0 .48.641h2a.5.5 0 0 0 .471-.332l.482-1.351c.635.173 1.31.267 2.011.267.707 0 1.388-.095 2.028-.272l.543 1.372a.5.5 0 0 0 .465.316h2a.5.5 0 0 0 .478-.645l-.761-2.506C13.81 9.895 14.5 8.559 14.5 7.069c0-.145-.007-.29-.02-.431.261-.11.508-.266.705-.444.315.306.815.306.815-.417 0 .223-.5.223-.461-.026a.95.95 0 0 0 .09-.255.7.7 0 0 0-.202-.645.58.58 0 0 0-.707-.098.735.735 0 0 0-.375.562c-.024.243.082.48.32.654a2.112 2.112 0 0 1-.259.153c-.534-2.664-3.284-4.595-6.442-4.595zM2.516 6.26c.455-2.066 2.667-3.733 5.448-3.733 3.146 0 5.536 2.114 5.536 4.542 0 1.254-.624 2.41-1.67 3.248a.5.5 0 0 0-.165.535l.66 2.175h-.985l-.59-1.487a.5.5 0 0 0-.629-.288c-.661.23-1.39.359-2.157.359a6.558 6.558 0 0 1-2.157-.359.5.5 0 0 0-.635.304l-.525 1.471h-.979l.633-2.15a.5.5 0 0 0-.17-.534 4.649 4.649 0 0 1-1.284-1.541.5.5 0 0 0-.446-.275h-.56a.5.5 0 0 1-.492-.414l-.254-1.46h.933a.5.5 0 0 0 .488-.393zm12.621-.857a.565.565 0 0 1-.098.21.704.704 0 0 1-.044-.025c-.146-.09-.157-.175-.152-.223a.236.236 0 0 1 .117-.173c.049-.027.08-.021.113.012a.202.202 0 0 1 .064.199z" />
      </svg> Overall income </span>
    <div class="d-flex justify-content-around">
      <div class="d-inline-block">
        <small>Daily</small>
        <h5 id="dailyOverall"></h5>
      </div>
      <div class="d-inline-block">
        <small>Monthly</small>
        <h5 id="monthlyOverall"></h5>
      </div>
      <div class="d-inline-block">
        <small><a class="text-light" href="#">Overall income</a></small>
        <h5 id="overallIncome"></h5>
      </div>
    </div>

<?php
$getVendo = json_encode($API->comm("/system/script/print"));
$getVendo = json_decode($getVendo, true);

$sumDaily = 0;
$sumMonthly = 0;
$sumOverall = 0;

$HariIni = strtolower(date("M/d/Y"));
$bulanIni = strtolower(date("M"));

if (is_array($getVendo)) {
  foreach ($getVendo as $item) {
    $name = isset($item['name']) ? $item['name'] : '';
    $parts = explode('-|-', $name);

    if (count($parts) < 4) continue;

    $hari = strtolower(trim($parts[0]));
    $amount = floatval(trim($parts[3]));
    $source = isset($item['source']) ? strtolower($item['source']) : '';
    $part = explode("/", $source);
    $bulan = isset($part[0]) ? $part[0] : '';

    if ($HariIni == $hari) {
      $sumDaily += $amount;
    }

    if ($bulan == $bulanIni) {
      $sumMonth += $amount;
    }
  }
}
$sumMonthly = $sumMonth - $sumDaily;
$sumOverall = $sumDaily + $sumMonthly;
?>

  </div>

  <ul class="list-group mt-3">
    <?php
    $toPrint = 0;
    foreach ($user->getUsers() as $rate) {
      $toPrint++;
      echo '<li class="list-group-item list-group-item-dark d-flex justify-content-between align-items-center">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="red" class="bi bi-ticket-perforated" viewBox="0 0 16 16">
                            <path d="M4 4.85v.9h1v-.9H4Zm7 0v.9h1v-.9h-1Zm-7 1.8v.9h1v-.9H4Zm7 0v.9h1v-.9h-1Zm-7 1.8v.9h1v-.9H4Zm7 0v.9h1v-.9h-1Zm-7 1.8v.9h1v-.9H4Zm7 0v.9h1v-.9h-1Z"/>
                            <path d="M1.5 3A1.5 1.5 0 0 0 0 4.5V6a.5.5 0 0 0 .5.5 1.5 1.5 0 1 1 0 3 .5.5 0 0 0-.5.5v1.5A1.5 1.5 0 0 0 1.5 13h13a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 0-.5-.5 1.5 1.5 0 0 1 0-3A.5.5 0 0 0 16 6V4.5A1.5 1.5 0 0 0 14.5 3h-13ZM1 4.5a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v1.05a2.5 2.5 0 0 0 0 4.9v1.05a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-1.05a2.5 2.5 0 0 0 0-4.9V4.5Z"/>
                        </svg> 
                        <b>'.$rate['name'].' -- Harga: Rp'.$rate['amount'].'.00--</b>
                    </div>
                    <button 
                        class="btn btn-success btn-sm py-1 px-2" 
                        style="font-size: 0.85rem;" 
                        onclick="closeModal();" 
                        data-toggle="modal" 
                        data-target="#printModal'.$toPrint.'">
                        Generate
                    </button>
                </li>';

      echo '
      <div class="modal fade" id="printModal' . $toPrint . '" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <div class="modal-content bg-light">
            <div class="modal-header">
              <h5 class="modal-title">Cetak Voucher</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form action="generate.php" method="post">
                <div class="form-group"><label>Paket</label>
                  <input type="text" class="form-control" name="name" value="' . $rate['name'] . '">
                </div>
                <div class="form-group"><label>Jumlah</label>
                  <input type="number" class="form-control" name="quantity" value="' . $rate['quantity'] . '">
                </div>
                <div class="form-group"><label>Harga</label>
                  <input type="text" class="form-control" name="amount" value="' . $rate['amount'] . '">
                </div>
                <div class="form-group"><label>Data</label>
                  <input type="text" class="form-control" name="limitbytes" value="' . $rate['limitbytes'] . '">
                </div>
                <div class="form-group"><label>Panjang</label>
                  <input type="number" class="form-control" name="length" min="4" max="8" value="' . $rate['length'] . '">
                </div>
                <div class="form-group"><label>Aktip</label>
                  <input type="text" class="form-control" name="profile" value="' . $rate['profile'] . '">
                </div>
                <div class="form-group"><label>Nama Penjual</label>
                  <input type="text" class="form-control" name="vendo" placeholder="JuanFi Vendo" value="' . $selldata['sell'] . '">
                </div>
                <button type="submit" class="btn btn-primary mt-4 w-100">Generate</button>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>';
    }
    ?>
  </ul>

  <!-- Tambah Rate Modal -->
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
            <div class="form-group"><label>Name</label>
              <input type="text" class="form-control" name="name" required>
            </div>
            <div class="form-group"><label>Quantity</label>
              <input type="number" class="form-control" name="quantity" required>
            </div>
            <div class="form-group"><label>Amount</label>
              <input type="number" class="form-control" name="amount" required>
            </div>
            <div class="form-group"><label>Data</label>
              <input type="number" class="form-control" name="limitbytes" required>
            </div>
            <div class="form-group"><label>Length</label>
              <input type="number" class="form-control" name="length" min="4" max="6" required>
            </div>
            <div class="form-group"><label>Profile</label>
              <input type="text" class="form-control" name="profile" required>
            </div>
            <div class="form-group"><label>Vendo name</label>
              <input type="text" class="form-control" name="vendo" required>
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
</div>

<!-- JS Section -->
<script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
<script>
  setInterval(function () {
    var time = new Date().toLocaleString();
    $("#timeDiv").html(time);
  }, 1000);

  var dIncome = <?php echo $sumDaily; ?>;
  var mIncome = <?php echo $sumMonthly; ?>;
  var oIncome = <?php echo $sumOverall; ?>;

  $('#dailyOverall').html("Rp " + parseInt(dIncome).toLocaleString() + ".00");
  $('#monthlyOverall').html("Rp " + parseInt(mIncome).toLocaleString() + ".00");
  $('#overallIncome').html("Rp " + parseInt(oIncome).toLocaleString() + ".00");
</script>
</body>
</html>
