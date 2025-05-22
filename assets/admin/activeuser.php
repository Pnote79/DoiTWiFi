<?php
include("../class/mt_resources.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active user</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
	<link rel="stylesheet" href="../css/style.css">
 <style>
.actionBtn{
    background-color: #f0ad4e;
    color: #000;
    padding: 1px 10px 1px 10px;
    border-radius: 3px;
    cursor: pointer;
}

.actionBtn:hover{
    text-decoration: none;
    color: #4e4e4e;
}
</style>  
</head>
<body class="bg-dark">
<?php include("../page/navigation.php"); ?>
<table class="table table-dark table-hover table-sm" style="font-size:12px">
    <tr>
        <th>Id</th>
        <th>Action</th>
        <th>User</th>
		<th>Profile</th>
        <th>Ip address</th>
        <th>Mac address</th>
        <th>Server At</th>
        <th>Upload</th>
        <th>Download</th>
        <th>Aktip</th>
		<th>Login</th>
		<th>Hostname</th>
    </tr>
    
    <?php
    $active = -1; 
        foreach($mt_hotspotUserActive as $hsUserActive){
        $active++;
           echo '<tr>';
           echo '<td>'.$active.'</td>';
           echo '<td><a class="actionBtn" href="kickuser.php?id='.$active.'"> Kick </a></td>';
           echo '<td>'.$hsUserActive['user'].'</td>';
		   foreach($mt_hotspotUser as $hsUser){
            if($hsUser['name'] == $hsUserActive['user']){
                echo '<td>'.$hsUser['profile'].'</td>';
            }
           }
           echo '<td>'.$hsUserActive['address'].'</td>';
           echo '<td>'.$hsUserActive['mac-address'].'</td>';
           echo '<td>'.$hsUserActive['server'].'</td>';
           echo '<td>'.round(($hsUserActive['bytes-in']/1024)/1024, 0).'MB</td>';
           echo '<td>'.round(($hsUserActive['bytes-out']/1024)/1024, 0).'MB</td>';
		   echo '<td>'.$hsUserActive['comment'].'</td>';
		   foreach ($mt_log as $ite) {
                 $name = isset($ite['name']) ? $ite['name'] : '';
                 $parts = explode('-|-', $name);
                 $vc= isset($parts[2]) ? $parts[2] : '-';
           if($hsUserActive['user'] == $vc){ 
            $source = !empty($ite['source']) ? $ite['source'] : 'unlimited';
           echo '<td>' . $source . '</td>';
			    }
			}
           foreach($mt_ipLease as $data){
            if($data['active-mac-address'] == $hsUserActive['mac-address']){
                echo '<td>'.$data['host-name'].'</td>';
            }
           }
          
        }
        echo '</tr>';
    
    ?>
    
</table>


<script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>    
<script> setInterval(function() { var time = new Date().toLocaleString(); $("#timeDiv").html(time); }, 1000);</script>
<script>
function askFirst() {
 
  if (confirm("Are you sure to reboot?") == true) {
    window.location.href = "action.php?reboot=1";
  } else {
    location.reload();
  }

}

function logOut() {
 
 if (confirm("Are you want to logout?") == true) {
   window.location.href = "action.php?logout=1";
 } else {
   location.reload();
 }

}


</script>  
</body>
</html>