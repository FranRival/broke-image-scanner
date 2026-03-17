<?php

if (!defined('ABSPATH')) exit;

function bis_admin_page(){


    global $wpdb;

    // usamos post_date por ahora (luego se puede hacer dinámico)
    $date_column = 'post_date';

    $min_date = $wpdb->get_var("
    SELECT MIN($date_column) 
    FROM {$wpdb->posts} 
    WHERE post_type='post' AND post_status='publish'
    ");

    $max_date = $wpdb->get_var("
    SELECT MAX($date_column) 
    FROM {$wpdb->posts} 
    WHERE post_type='post' AND post_status='publish'
    ");

    // fallback por seguridad
    if(!$min_date || !$max_date){
        $min_year = date('Y');
        $max_year = date('Y');
    } else {
        $min_year = date('Y', strtotime($min_date));
        $max_year = date('Y', strtotime($max_date));
    }

?>

<div class="wrap">

<h1>Broken Image Scanner</h1>

<label>Year</label>
<select id="bis_year">

<?php

for($y=$max_year;$y>=$min_year;$y--){
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