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

    // 🔥 obtener TAGS
    $tags = get_terms([
    'taxonomy' => 'post_tag',
    'hide_empty' => false
]);

// 🔥 obtener conteo real de posts por tag
    $tag_counts = [];

    $results = $wpdb->get_results("
        SELECT tt.term_id, COUNT(p.ID) as total
        FROM {$wpdb->term_taxonomy} tt
        LEFT JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
        LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
        WHERE tt.taxonomy = 'post_tag'
        AND p.post_type = 'post'
        AND p.post_status = 'publish'
        GROUP BY tt.term_id
    ");

    foreach($results as $row){
        $tag_counts[$row->term_id] = intval($row->total);
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

<!-- 🔥 NUEVO SELECTOR TAG -->

<label>Tag</label>
<select id="bis_tag">
<option value="">All</option>
<?php
if(!empty($tags) && !is_wp_error($tags)){
    foreach($tags as $tag){
        $count = isset($tag_counts[$tag->term_id]) 
            ? $tag_counts[$tag->term_id] 
            : 0;

        echo "<option value='{$tag->slug}'>
            {$tag->name} ({$count})
        </option>";
    }
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