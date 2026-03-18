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

$file = WP_CONTENT_DIR . '/uploads/bis-temp.json';

if(file_exists($file)){
    unlink($file);
}

$year = isset($_POST['year']) ? intval($_POST['year']) : 0;
$month = isset($_POST['month']) ? $_POST['month'] : '';

$date_query = [
    [
        'year'=>$year
    ]
];

if(!empty($month)){
    $date_query[0]['month'] = intval($month);
}

$args=[
'post_type'=>'post',
'posts_per_page'=>-1,
'fields'=>'ids',
'date_query'=>$date_query
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
$year = isset($_POST['year']) ? intval($_POST['year']) : 0;
$month = isset($_POST['month']) ? $_POST['month'] : '';

$date_query = [
    [
        'year'=>$year
    ]
];

if(!empty($month)){
    $date_query[0]['month'] = intval($month);
}

$args=[
'post_type'=>'post',
'posts_per_page'=>5,
'offset'=>$offset,
'date_query'=>$date_query
];

$query=new WP_Query($args);

$images=[];
$seen_urls=[];



$file = WP_CONTENT_DIR . '/uploads/bis-temp.json';

// leer existente
$existing = [];

if(file_exists($file)){
    $existing = json_decode(file_get_contents($file), true);
    if(!is_array($existing)) $existing = [];
}

// merge
$merged = array_merge($existing, $images);

// guardar
file_put_contents($file, json_encode($merged));

// 🔥 PROTECCIÓN AQUÍ
if(empty($query->posts)){
    wp_send_json([
        'images'=>[],
        'processed'=>$offset,
        'batch_count'=>0
    ]);
}

foreach($query->posts as $post){

$urls = bis_extract_images($post->post_content);

if(empty($urls) || !is_array($urls)) continue;

foreach($urls as $url){

// validar URL
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

    // fallback a GET
    $response = @wp_remote_get($url,[
        'timeout' => 3,
        'redirection' => 2
    ]);
}

// =========================
// EVALUAR RESPUESTA
// =========================
if(is_wp_error($response)){

$status="timeout";
$code="Timeout";

}else{

$code = wp_remote_retrieve_response_code($response);

if(!$code){
$code = "Unknown";
$status="timeout";
}
elseif($code>=400){
$status="broken";
}
else{
$status="ok";
}

}


// =========================
// DOMAIN
// =========================
$parsed = parse_url($url);
$domain = isset($parsed['host']) ? $parsed['host'] : 'unknown';
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

wp_send_json([
'images'=>$images,
'processed'=>$offset + $query->post_count,
'batch_count'=>$query->post_count
]);

}

add_action('wp_ajax_bis_generate_excel','bis_generate_excel');

function bis_generate_excel(){

$file = WP_CONTENT_DIR . '/uploads/bis-temp.json';

if(!file_exists($file)){
    wp_send_json(['status'=>'error']);
}

$data = json_decode(file_get_contents($file), true);

if(!$data){
    wp_send_json(['status'=>'error']);
}

if(!$data){
    wp_send_json(['status'=>'error']);
}

$total = count($data);

require_once BIS_PATH.'exporter.php';

bis_generate_reports($data, $total);

wp_send_json([
    'status'=>'ok',
    'files'=>[
        'broken'=>BIS_URL.'broken-images-report.csv',
        'timeout'=>BIS_URL.'timeout-images-report.csv'
    ]
]);

}