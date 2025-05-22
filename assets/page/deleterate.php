<?php
require('../class/routeros_api.class.php');
$user = new User("../json/rate.json");

$id = intval($_GET['id']);
echo $id;
$user->deleteUser($id);
header('Location:createvoucher.php');