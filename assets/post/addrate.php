<?php
	require('../class/routeros_api.class.php');

	$name = $_POST['name'];
	$quantity = $_POST['quantity'];
	$amount = $_POST['amount'];
	$limitbytes = $_POST['limitbytes'];
	$length = $_POST['length'];
	$profile = $_POST['profile'];
	$vendo = $_POST['vendo'];

	$new_user = [
		"name" => $name,
		"quantity" => $quantity,
		"amount" => $amount,
		"limitbytes" => $limitbytes,
		"length" => $length,
		"profile" => $profile,
		"vendo" => $vendo
	];
	$user = new User("../json/rate.json");
    $user->insertNewUser($new_user);
    header('Location:../page/dashboard.php');
