<?php

if (!defined('ABSPATH')) exit;

function bis_admin_page(){

    global $wpdb;

    // usamos post_date por ahora
    $date_column = 'post_date';

    // rango de fechas real
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

    if(!$min_date || !$max_date){
        $min_year = date('Y');
        $max_year = date('Y');
    } else {
        $min_year = date('Y', strtotime($min_date));
        $max_year = date('Y', strtotime($max_date));
    }

    // 🔥 obtener conteo por año y mes
    $results = $wpdb->get_results("
    SELECT 
        YEAR($date_column) as year,
        MONTH($date_column) as month,
        COUNT(*) as total
    FROM {$wpdb->posts}
    WHERE post_type='post' 
    AND post_status='publish'
    GROUP BY YEAR($date_column), MONTH($date_column)
    ");

    // organizar datos
    $data = [];

    foreach($results as $row){
        $data[$row->year][$row->month] = $row->total;
    }

?>

<div class="wrap">

<h1>Broken Image Scanner</h1>

<!-- SELECTORES -->

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

<div id="bis_progress">0%</div>
<br>
<div id="bis_stats"></div>

<hr>

<!-- 🔥 TABLAS POR AÑO -->

<h2>Posts by Month</h2>

<?php

for($year=$max_year; $year >= $min_year; $year--){

    echo "<h3>$year</h3>";

    echo "<table class='widefat striped' style='max-width:400px'>";
    echo "<thead>
            <tr>
                <th>Month</th>
                <th>Posts</th>
            </tr>
          </thead>
          <tbody>";

    for($m=1;$m<=12;$m++){

        $count = isset($data[$year][$m]) ? $data[$year][$m] : 0;

        $month_name = date("F", mktime(0,0,0,$m,1));

        // gris si no hay contenido
        $style = $count == 0 ? "style='color:#999'" : "";

        echo "<tr $style>";
        echo "<td>$month_name</td>";
        echo "<td>$count</td>";
        echo "</tr>";
    }

    echo "</tbody></table><br>";
}

?>

</div>

<?php

}