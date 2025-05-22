<?php
require('../class/routeros_api.class.php');
$id = intval($_GET['id']);
$API = new RouterosAPI();
$mtip = LocalStorage::getInstance()->getValue("mtip");
$mtuser = LocalStorage::getInstance()->getValue("mtuser");
$mtpass = LocalStorage::getInstance()->getValue("mtpass");
if($API->connect($mtip, $mtuser, $mtpass)){
    $API->comm("/ip/hotspot/active/remove", array(".id" => "$id",));
	header('Location: activeuser.php');

}