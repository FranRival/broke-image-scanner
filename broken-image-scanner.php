<?php
/*
Plugin Name: Broken Image Scanner
Description: Scan posts by month/year to detect broken or timeout images and export Excel reports.
Version: 2.0
*/

if (!defined('ABSPATH')) exit;

define('BIS_PATH', plugin_dir_path(__FILE__));
define('BIS_URL', plugin_dir_url(__FILE__));

require_once BIS_PATH.'admin-page.php';
require_once BIS_PATH.'ajax-scanner.php';
require_once BIS_PATH.'parser.php';
require_once BIS_PATH.'exporter.php';

add_action('admin_menu','bis_menu');

function bis_menu(){

add_menu_page(
'Broken Image Scanner',
'Broken Image Scanner',
'manage_options',
'broken-image-scanner',
'bis_admin_page',
'dashicons-search',
80
);

}

add_action('admin_enqueue_scripts','bis_scripts');

function bis_scripts(){

wp_enqueue_script(
'bis-scanner',
BIS_URL.'assets/scanner.js',
['jquery'],
null,
true
);

wp_localize_script('bis-scanner','bis_ajax',[
'ajax_url'=>admin_url('admin-ajax.php')
]);

}