<?php
include("../class/mt_resources.php");

$API = new RouterosAPI();

$API->connect($mtip, $mtuser, $mtpass);

$usersell = $_POST['usersell'];
$passsell = $_POST['passsell'];
$wasell = $_POST['wasell'];
$dnssell = $vendo['dns'];



if(isset($_POST['dashboardBtn'])) {
           
           header('Location: ../admin/admin.php');
		   
        }




$createuser = $API->comm('/system/script/add', array(
        "name" => "$usersell-|-$passsell-|-$wasell",
		"comment" => "$dnssell",
        "source" => "0",
    ));

           header('Location: ../admin/admin.php');


?>