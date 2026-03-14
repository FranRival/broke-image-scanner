<?php

if (!defined('ABSPATH')) exit;

function bis_generate_reports($images,$total){

$broken=[];
$timeout=[];

foreach($images as $img){

if($img['error_type']=="broken"){

$img['percentage']=round((1/$total)*100,4)."%";
$broken[]=$img;

}

if($img['error_type']=="timeout"){

$img['percentage']=round((1/$total)*100,4)."%";
$timeout[]=$img;

}

}

bis_export_csv('broken-images-report.csv',$broken);
bis_export_csv('timeout-images-report.csv',$timeout);

}

function bis_export_csv($filename,$rows){

$file=fopen(BIS_PATH.$filename,'w');

fputcsv($file,[

'Post ID',
'Post Title',
'Post URL',
'Image URL',
'HTTP Status',
'Error Type',
'Domain',
'Percentage'

]);

foreach($rows as $row){

fputcsv($file,[

$row['post_id'],
$row['post_title'],
$row['post_url'],
$row['image_url'],
$row['http_status'],
$row['error_type'],
$row['domain'],
$row['percentage']

]);

}

fclose($file);

}