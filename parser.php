<?php

if (!defined('ABSPATH')) exit;

function bis_extract_images($content){

$urls=[];

preg_match_all('/<img[^>]+src="([^">]+)"/i',$content,$matches);

if(!empty($matches[1])){

foreach($matches[1] as $url){

$urls[]=$url;

}

}

return array_slice($urls, 0, 10);

}