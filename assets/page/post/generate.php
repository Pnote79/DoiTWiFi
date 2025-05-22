<?php
session_start();
include("../class/mt_resources.php");

$API = new RouterosAPI();
$API->connect($mtip, $mtuser, $mtpass);

// Ambil data dari form
$name        = $_POST['name'];
$quantity    = (int)$_POST['quantity'];
$amount      = (int)$_POST['amount'];
$limitbytes  = (int)$_POST['limitbytes'];
$prefix      = $_POST['prefix'];
$length      = (int)$_POST['length'];
$profile     = $_POST['profile'];
$vendo       = $_POST['vendo'];
$margine     = (int)$_POST['margine'];
$sell        = $_SESSION['sell'];
$dns         = $dns ?? 'default-dns.com'; 

$total_cost  = $margine * $quantity;

// Ambil data seller
$dataFile    = "../json/sellerdata.json";
$userList    = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
$initial_balance = 0;

foreach ($userList as $user) {
    if ($user['sellername'] === $sell) {
        $initial_balance = $user['sellerbalance'];
        break;
    }
}

// Cek saldo
if ($initial_balance < $total_cost) {
    echo "<script>
        alert('⚠️ Saldo tidak cukup.\\nSaldo: Rp" . number_format($initial_balance, 0, ',', '.') . "\\nButuh: Rp" . number_format($total_cost, 0, ',', '.') . "');
        window.location='../page/dashboard.php';
    </script>";
    exit;
}

// Redirect jika kembali ke dashboard
if (isset($_POST['dashboardBtn'])) {
    header('Location: ../page/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cetak Voucher</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../css/style.css" />
  <style>
    body { font-family: Verdana; }
    .voucher-div {
        float: left;
        border: 2px dashed #b5b5b5;
        width: 195px;
        height: 115px;
        margin: 5px;
    }
    .amount {
        text-indent: 5px;
        display: block;
        background-color: rgb(220, 220, 220);
        width: 105px;
        transform: rotate(90deg);
        position: relative;
        top: 43px;
        left: -40px;
    }
    .voucher {
        position: relative;
        left: 25px;
        top: -18px;
        background-color: #f8f8f8;
        padding: 5px;
        height: 75px;
        width: 160px;
    }
    .hotspotName {
        text-align: center;
        letter-spacing: 1px;
        background-color: #2a2a2a;
        color: white;
        padding: 2px;
        border-radius: 3px;
    }
    .voucherCode {
        text-align: center;
        font-size: 20px;
        font-weight: bold;
        margin: 4px;
        border: 1px solid #a8a8a8;
        color: red;
    }
    .voucherData {
        font-size: 11px;
    }
    .voucherData span {
        display: block;
    }
    .icon {
        position: relative;
        transform: rotate(-45deg);
        top: -72px;
        left: 113px;
        color: #ffd1d1;
    }
    @media print { body { -webkit-print-color-adjust: exact; } }
  </style>
</head>
<body>
<?php include('../page/navigation.php'); ?>

<?php
// Proses generate voucher
$generatedCodes = [];
$date = strtolower(date("M/d/Y"));
$i = 0;

while ($i < $quantity) {
    $str = substr(sha1(mt_rand()), 17, $length);
    $vc  = strtoupper($prefix . $str);

    $create = $API->comm('/ip/hotspot/user/add', [
        "name"              => $vc,
        "password"          => $vc,
        "limit-bytes-total"=> $limitbytes * 1024 * 1024 * 1024,
        "profile"           => $profile,
        "comment"           => "vc-apk|$sell|$amount|$date"
    ]);

    if (isset($create['!trap'][0]['message'])) {
        $error = $create['!trap'][0]['message'];
        echo "<script>alert('Gagal membuat voucher: $error');</script>";
        break;
    }

    $generatedCodes[] = $vc;
    $i++;
}

// Tampilkan voucher
foreach ($generatedCodes as $vc) {
    echo '
	
    <div class="voucher-div"> 
        <div class="amount">Rp' . number_format($amount, 0, ',', '.') . '</div>
        <div class="voucher">
            <div class="hotspotName">' . htmlspecialchars($dns) . '</div>
            <div class="voucherCode">' . $vc . '</div>
            <div class="voucherData">
                <span>Paket: ' . htmlspecialchars($profile) . '</span>
                <span>Data : ' . $limitbytes . ' GB</span>
            </div>
            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 13a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                    <path fill-rule="evenodd" d="M2.05 6.93a10 10 0 0 1 11.9 0l-.8 1.2a8.5 8.5 0 0 0-10.3 0l-.8-1.2z"/>
                    <path fill-rule="evenodd" d="M4.45 9.4a6 6 0 0 1 7.1 0l-.8 1.2a4.5 4.5 0 0 0-5.5 0l-.8-1.2z"/>
                </svg>
            </div>
        </div>';

    if ($quantity == 1) {
        $text = urlencode("Voucher $dn$sn\nKode: $vc\nPaket: $profile\nData: $limitbytes GB\nHarga: Rp" . number_format($amount, 0, ',', '.'));
        echo '<div style="margin-top:10px;"><a href="https://wa.me/?text=' . $text . '" target="_blank">Bagikan Lewat WhatsApp</a></div>';
    }

    echo '</div>';
}

// Update saldo
foreach ($userList as &$user) {
    if ($user['sellername'] === $sell) {
        $user['sellerbalance'] = $initial_balance - $total_cost;
        break;
    }
}
file_put_contents($dataFile, json_encode($userList, JSON_PRETTY_PRINT));

// Simpan log history
$logFile = "../json/voucherlog.json";
$logData = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];

$logEntry = [
    "seller"     => $sell,
    "profile"    => $profile,
    "amount"     => $amount,
    "qty"        => $quantity,
    "cost"       => $total_cost,
    "codes"      => $generatedCodes,
    "date"       => date("Y-m-d H:i:s")
    
];

$logData[] = $logEntry;
file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));

// Informasi popup
echo "<script>
    alert('✅ Voucher berhasil dibuat!\\nPenjual: $sell\\nSaldo Sebelum: Rp" . number_format($initial_balance, 0, ',', '.') . "\\nTotal Biaya: Rp" . number_format($total_cost, 0, ',', '.') . "\\nSaldo Setelah: Rp" . number_format($initial_balance - $total_cost, 0, ',', '.') . "');
</script>";
?>

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script> 
</body>
</html>
