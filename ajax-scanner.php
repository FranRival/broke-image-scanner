<?php

if (!defined('ABSPATH')) exit;

add_action('wp_ajax_bis_scan_batch','bis_scan_batch');

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
$total_images=0;

foreach($query->posts as $post){

$urls=bis_extract_images($post->post_content);

foreach($urls as $url){

$total_images++;

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
'count'=>$query->post_count,
'total_images'=>$total_images
]);

}