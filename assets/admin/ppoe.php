<?php
include("../class/mt_resources.php");

// Fungsi untuk ambil user aktif
function getActiveUsers($API) {
    $active_pppoe = json_decode(json_encode($API->comm('/ppp/active/print')), true);
    $users = [];
    foreach ($active_pppoe as $active) {
        $users[$active['name']] = [
            'address' => $active['address'],
            'uptime' => $active['uptime']
        ];
    }
    return $users;
}

// Ambil semua secret user
$mt_pppoe = json_decode(json_encode($API->comm('/ppp/secret/print')), true);

// Ambil semua user aktif
$active_users = getActiveUsers($API);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPOE user</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
	<link rel="stylesheet" href="../css/style.css">
    <style>
    .actionBtn {
        background-color: #f0ad4e;
        color: #000;
        padding: 1px 10px;
        border-radius: 3px;
        cursor: pointer;
    }
    .actionBtn:hover {
        text-decoration: none;
        color: #4e4e4e;
    }
    </style>
</head>
<body class="bg-dark">
<?php include("../page/navigation.php"); ?>

     
    <h4 class="text-light mb-3">PPPoE Users Monitoring</h4>
<div class="table-responsive">
    <table id="userTable" class="table table-dark table-hover table-sm" style="font-size:14px">
        <thead>
            <tr>
                <th>No</th>
                <th>Username</th>
                <th>Service</th>
                <th>Profile</th>
                <th>IP Address</th>
                <th>Uptime</th>
                <th>Status</th>
				<th>Last-logged-out</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($mt_pppoe as $user) {
                $isActive = array_key_exists($user['name'], $active_users);
                $status = $isActive ? '<span class="text-success">Active</span>' : '<span class="text-danger">Non Active</span>';
                $address = $isActive ? $active_users[$user['name']]['address'] : '-';
                $uptime = $isActive ? $active_users[$user['name']]['uptime'] : '-';

                $action_button = ($user['disabled'] == 'false') 
                    ? '<a href="action.php?disable='.$user['.id'].'" class="btn btn-danger btn-sm">Disable</a>'
                    : '<a href="action.php?enable='.$user['.id'].'" class="btn btn-success btn-sm">Enable</a>';
                
                echo "<tr>";
                echo "<td>".$no++."</td>";
                echo "<td>".$user['name']."</td>";
                echo "<td>".$user['service']."</td>";
                echo "<td>".$user['profile']."</td>";
                echo "<td>".$address."</td>";
                echo "<td>".$uptime."</td>";
                echo "<td>".$status."</td>";
				echo "<td>".$user['last-logged-out']."</td>";
                echo "<td>".$action_button."</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Auto Refresh hanya bagian tabel -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
setInterval(function(){
    $("#userTable tbody").load(location.href + " #userTable tbody>*", "");
}, 10000); // 10 detik refresh
</script>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script> 
</body> 
</body>
</html>
