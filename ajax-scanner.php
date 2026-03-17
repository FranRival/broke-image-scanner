<?php

if (!defined('ABSPATH')) exit;

add_action('wp_ajax_bis_scan_batch','bis_scan_batch');
add_action('wp_ajax_bis_get_total_posts','bis_get_total_posts');

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



add_action('wp_ajax_bis_get_months_with_posts','bis_get_months_with_posts');

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

function bis_scan_batch(){

$offset=intval($_POST['offset']);
$year=intval($_POST['year']);
$month=$_POST['month'];

$args=[
'post_type'=>'post',
'posts_per_page'=>20,
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

$urls=bis_extract_images($post->post_content);

foreach($urls as $url){

if(isset($seen_urls[$url])){
continue;
}

$seen_urls[$url]=true;

$response=wp_remote_head($url,['timeout'=>10]);

if(is_wp_error($response)){

$status="timeout";
$code="Timeout";

}else{

$code=wp_remote_retrieve_response_code($response);

if($code>=400){

$status="broken";

}else{

$status="ok";

}

}

$images[]=[

'post_id'=>$post->ID,
'post_title'=>$post->post_title,
'post_url'=>get_permalink($post->ID),
'image_url'=>$url,
'http_status'=>$code,
'error_type'=>$status,
'domain'=>parse_url($url,PHP_URL_HOST)

];

}

}

wp_send_json([
'images'=>$images,
'processed'=>$offset + $query->post_count,
'batch_count'=>$query->post_count
]);

}