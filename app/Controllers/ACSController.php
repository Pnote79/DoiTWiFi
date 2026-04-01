<?php
require_once __DIR__.'/../Services/ACSService.php';

class ACSController{

public function devices(){

$acs=new ACSService();
$devices=$acs->getDevices();

include __DIR__.'/../Views/admin/acs/devices.php';

}

}
