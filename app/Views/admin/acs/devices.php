<h3>ACS Devices</h3>

<table border="1">
<tr>
<th>Serial</th>
<th>Model</th>
</tr>

<?php foreach($devices as $d): ?>

<tr>
<td><?= $d['_id'] ?? '' ?></td>
<td><?= $d['DeviceID']['ProductClass'] ?? '' ?></td>
</tr>

<?php endforeach; ?>

</table>
