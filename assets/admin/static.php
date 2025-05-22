<?php
include("../class/mt_resources.php");

// Ambil semua data dari Tool > Netwatch
$netwatch = json_decode(json_encode($API->comm('/tool/netwatch/print')), true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Netwatch Monitor</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-dark">
 <?php include("../page/navigation.php"); ?>

    <h4 class="text-light mb-3">Monitoring Netwatch</h4>
    <table class="table table-dark table-hover table-sm" style="font-size:14px">
        <thead>
            <tr>
                <th>No</th>
                <th>IP </th>
                <th>Nama</th>
                <th>AP</th>
                <th>Status (Real)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($netwatch as $item) {
                $comment = isset($item['comment']) ? $item['comment'] : '';
                $parts = explode('/', $comment); // Pisahkan berdasarkan spasi
                
                $nama = isset($parts[0]) ? $parts[0] : '-';
                $ap = isset($parts[1]) ? $parts[1] : '-';

                // Status dari Mikrotik (up / down)
                $status_real = ($item['status'] == 'up') 
                    ? '<span class="text-success">ON</span>'
                    : '<span class="text-danger">DOWN</span>';

                echo "<tr>";
                echo "<td>".$no++."</td>";
                echo "<td>".$item['host']."</td>";
                echo "<td>".$nama."</td>";
                echo "<td>".$ap."</td>";
                echo "<td>".$status_real."</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script> 
</body> 
</body>
</html>
