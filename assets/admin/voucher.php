<?php
session_start();
include("../class/mt_resources.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>

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
<?php include("usercreate.php"); ?>

    <table id="vcTable" class="table table-dark table-hover table-sm" style="font-size:12px">
        <tr>
            <th scope="col">Id</th>
           <?php if ($_SESSION['username'] === 'admin') { ?> <th scope="col">Action</th>
		   <?php } ?>
            <th scope="col">Status</th>
            <th scope="col">Username</th>
            <th scope="col">Profile</th>
			<th scope="col">Seller</th>
            
        </tr>
        
       <?php 
    $id = 0;
    foreach(array_slice($mt_hotspotUser, 1) as $hsUser){
        // misalnya filter berdasarkan komentar baris ke-2
        $commentLines = explode("|", $hsUser['comment']);
        $userseller = isset($commentLines[1]) ? trim($commentLines[1]) : '';
        $amountseller = isset($commentLines[2]) ? trim($commentLines[2]) : '';
		
      if($_SESSION['role'] == 'admin' || $_SESSION['sell'] == $userseller){
        $id++;
        echo '<tr>';
        echo '<td>'.$id.'</td>';
        
        if ($_SESSION['role'] == 'admin') {
                echo '<td><a class="actionBtn" href="deleteuser.php?id='.$id.'">Delete</a></td>';
            }
       
        echo '<td>'.($hsUser['disabled'] == 'true' ? 'Disabled' : 'Active').'</td>';
        echo '<td>'.$hsUser['name'].'</td>';
        echo '<td>'.$hsUser['profile'].'</td>';
        echo '<td>'.$amountseller.'</td>';
        echo '</tr>';
    }
}
    ?>

    </table>



<script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>    
<script> setInterval(function() { var time = new Date().toLocaleString(); $("#timeDiv").html(time); }, 1000);</script>

<script>
function searchVoucher() {
  var input, filter, table, tr, td, i, txtValue;
  input = document.getElementById("vcInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("vcTable");
  tr = table.getElementsByTagName("tr");
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[3];
    if (td) {
      txtValue = td.textContent || td.innerText;
      if (txtValue.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    }       
  }
}



var $table = document.getElementById("vcTable"),
$n = 20,
$rowCount = $table.rows.length,
$firstRow = $table.rows[0].firstElementChild.tagName,
$hasHead = ($firstRow === "TH"),
$tr = [],
$i,$ii,$j = ($hasHead)?1:0,
$th = ($hasHead?$table.rows[(0)].outerHTML:"");
var $pageCount = Math.ceil($rowCount / $n);
if ($pageCount > 1) {
	for ($i = $j,$ii = 0; $i < $rowCount; $i++, $ii++)  
		$tr[$ii] = $table.rows[$i].outerHTML;
$table.insertAdjacentHTML("beforebegin","<div class='my-2 mx-2' id='buttons'></div");
	sort(1);
}

function sort($p) {
	var $rows = $th,$s = (($n * $p)-$n);
	for ($i = $s; $i < ($s+$n) && $i < $tr.length; $i++)
		$rows += $tr[$i];
	
	$table.innerHTML = $rows;
	document.getElementById("buttons").innerHTML = pageButtons($pageCount,$p);
	document.getElementById("id"+$p).setAttribute("class","btn btn-info btn-sm mx-1");
}


function pageButtons($pCount,$cur) {
	var	$prevDis = ($cur == 1)?"disabled":"",
		$nextDis = ($cur == $pCount)?"disabled":"",
		$buttons = "<input class='btn btn-sm btn-light mr-1' type='button' value='<< Prev' onclick='sort("+($cur - 1)+")' "+$prevDis+">";
	for ($i=1; $i<=$pCount;$i++)
		$buttons += "<input class='btn btn-sm btn-secondary ml-1' type='button' id='id"+$i+"'value='"+$i+"' onclick='sort("+$i+")'>";
	$buttons += "<input class='btn btn-sm btn-light ml-1' type='button' value='Next >>' onclick='sort("+($cur + 1)+")' "+$nextDis+">";
	return $buttons;
}

</script>


</body>
</html>