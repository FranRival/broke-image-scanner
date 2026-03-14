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
$total=0;

foreach($query->posts as $post){

$urls=bis_extract_images($post->post_content);

foreach($urls as $url){

$total++;

$response=wp_remote_head($url,['timeout'=>10]);

if(is_wp_error($response)){

$status="timeout";

}elseif(wp_remote_retrieve_response_code($response)>=400){

$status="broken";

}else{

$status="ok";

}

$images[]=[
'post'=>$post->post_title,
'url'=>$url,
'status'=>$status
];

}

}

wp_send_json([
'images'=>$images,
'count'=>$query->post_count
]);

}