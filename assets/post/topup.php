<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['sellerid'];
    $topup = (int) $_POST['topup'];

    $sellerDataPath = "../json/sellerdata.json";
    $logPath = "../json/topup_log.json";

    $sellers = json_decode(file_get_contents($sellerDataPath), true);
    $log = file_exists($logPath) ? json_decode(file_get_contents($logPath), true) : [];

    foreach ($sellers as &$seller) {
        if ($seller['id'] == $id) {
            $before = (int)$seller['sellerbalance'];
            $seller['sellerbalance'] = $before + $topup;
            $after = $seller['sellerbalance'];
            
            // Tambahkan ke log
            $log[] = [
                'datetime' => date('Y-m-d H:i:s'),
                'sellerid' => $id,
                'sellername' => $seller['sellername'],
                'topup' => $topup,
                'before' => $before,
                'after' => $after,
				"msg" => "Topup Rp ". number_format($topup). "  Berhasil. Saldo Rp ". number_format($after),
				'read' => false
            ];
            break;
        }
    }

    file_put_contents($sellerDataPath, json_encode($sellers, JSON_PRETTY_PRINT));
    file_put_contents($logPath, json_encode($log, JSON_PRETTY_PRINT));

    $_SESSION['success'] = "Top up berhasil!";
	
	  
    header("Location: ../admin/seller.php");
    exit;
}
?>
