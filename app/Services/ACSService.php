<?php

class ACSService{

private $url="http://localhost:7557";

public function getDevices(){

$json=@file_get_contents($this->url."/devices");

if(!$json) return [];

return json_decode($json,true);

}

}
