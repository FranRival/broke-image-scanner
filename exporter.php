<?php

if (!defined('ABSPATH')) exit;

function bis_export_reports($broken,$timeout,$total){

$broken_file = BIS_PATH.'broken-images.xlsx';
$timeout_file = BIS_PATH.'timeout-images.xlsx';

$broken_percent = count($broken)/$total*100;
$timeout_percent = count($timeout)/$total*100;

/* simplificado: en producción usar PhpSpreadsheet */

}