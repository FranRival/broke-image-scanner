<?php

if (!defined('ABSPATH')) exit;

// 🔥 evitar problemas de memoria/tiempo
ini_set('memory_limit','512M');
set_time_limit(60);

add_action('wp_ajax_bis_scan_batch','bis_scan_batch');
add_action('wp_ajax_bis_get_total_posts','bis_get_total_posts');
add_action('wp_ajax_bis_get_months_with_posts','bis_get_months_with_posts');


// =========================
// TOTAL POSTS
// =========================
function bis_get_total_posts(){

$year=intval($_POST['year']);
$month=$_POST['month'];

$args=[
'post_type'=>'post',
'posts_per_page'=>-1,
'fields'=>'ids',
'date_query'=>[
[
'year'=>$year,
'month'=>$month
]
]
];

$query=new WP_Query($args);

wp_send_json([
'total_posts'=>count($query->posts)
]);

}


// =========================
// MESES CON POSTS
// =========================
function bis_get_months_with_posts(){

$year = intval($_POST['year']);
$date_type = $_POST['date_type'] ?? 'post_date';

global $wpdb;

$results = $wpdb->get_results("
SELECT MONTH($date_type) as month, COUNT(*) as total
FROM {$wpdb->posts}
WHERE post_type='post'
AND post_status='publish'
AND YEAR($date_type) = $year
GROUP BY MONTH($date_type)
");

$months = [];

foreach($results as $row){
$months[intval($row->month)] = intval($row->total);
}

wp_send_json($months);

}


// =========================
// SCAN BATCH
// =========================
function bis_scan_batch(){

$offset=intval($_POST['offset']);
$year=intval($_POST['year']);
$month=$_POST['month'];

$args=[
'post_type'=>'post',
'posts_per_page'=>5, // 🔥 reducido para estabilidad
'offset'=>$offset,
'date_query'=>[
[
'year'=>$year,
'month'=>$month
]
]
];

$query=new WP_Query($args);

$images=[];
$seen_urls=[]; // deduplicación

foreach($query->posts as $post){

$urls = bis_extract_images($post->post_content);

// seguridad: evitar contenido vacío
if(empty($urls) || !is_array($urls)) continue;

foreach($urls as $url){

// 🔥 validar URL
if(empty($url) || !filter_var($url, FILTER_VALIDATE_URL)){
continue;
}

// deduplicación
if(isset($seen_urls[$url])){
continue;
}

$seen_urls[$url]=true;


// =========================
// REQUEST SEGURO
// =========================
$response = @wp_remote_head($url,[
    'timeout' => 3,
    'redirection' => 2
]);

if(is_wp_error($response)){

$status="timeout";
$code="Timeout";

}else{

$code = wp_remote_retrieve_response_code($response);

// fallback por si viene vacío
if(!$code){
$code = "Unknown";
$status="timeout";
}
else if($code>=400){
$status="broken";
}else{
$status="ok";
}

}


// =========================
// DOMAIN SEGURO
// =========================
$domain = parse_url($url, PHP_URL_HOST);

if(!$domain){
$domain = 'unknown';
}


// =========================
// RESULT
// =========================
$images[]=[

'post_id'=>$post->ID,
'post_title'=>$post->post_title,
'post_url'=>get_permalink($post->ID),
'image_url'=>$url,
'http_status'=>$code,
'error_type'=>$status,
'domain'=>$domain

];

}

}

// =========================
// RESPONSE
// =========================
wp_send_json([
'images'=>$images,
'processed'=>$offset + $query->post_count,
'batch_count'=>$query->post_count
]);

}