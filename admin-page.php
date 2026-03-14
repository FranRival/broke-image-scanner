<?php

if (!defined('ABSPATH')) exit;

function bis_admin_page(){

?>

<div class="wrap">

<h1>Broken Image Scanner</h1>

<label>Year</label>
<select id="bis_year">
<?php
$current = date("Y");
for($y=$current;$y>=2018;$y--){
echo "<option value='$y'>$y</option>";
}
?>
</select>

<label>Month</label>
<select id="bis_month">
<option value="">All</option>
<?php
for($m=1;$m<=12;$m++){
echo "<option value='$m'>$m</option>";
}
?>
</select>

<button id="bis_start_scan" class="button button-primary">
Start Scan
</button>

<br><br>

<div id="bis_progress">
0%
</div>

<br>

<div id="bis_stats"></div>

</div>

<?php
}