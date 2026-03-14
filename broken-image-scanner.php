<?php
/*
Plugin Name: Broken Image Scanner
Description: Scans posts for broken images.
Version: 1.0
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'admin-page.php';
require_once plugin_dir_path(__FILE__) . 'scanner.php';

add_action('admin_menu', 'bis_add_menu');

function bis_add_menu() {

    add_menu_page(
        'Broken Image Scanner',
        'Broken Image Scanner',
        'manage_options',
        'broken-image-scanner',
        'bis_admin_page',
        'dashicons-format-image',
        80
    );
}