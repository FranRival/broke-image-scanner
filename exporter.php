<?php
if (!defined('ABSPATH')) exit;

function bis_generate_reports($images, $total, $path) {
    $broken = []; $timeout = []; $domains = [];

    foreach($images as $img) {
        $dom = $img['domain'] ?: 'unknown';
        $domains[$dom] = ($domains[$dom] ?? 0) + 1;
        $pct = ($total > 0) ? round((1 / $total) * 100, 4) . "%" : "0%";
        $img['percentage'] = $pct;

        if($img['error_type'] === "broken") $broken[] = $img;
        if($img['error_type'] === "timeout") $timeout[] = $img;
    }

    bis_export_csv($path . 'broken-images-report.csv', $broken);
    bis_export_csv($path . 'timeout-images-report.csv', $timeout);
    bis_export_domains($path . 'domains-report.csv', $domains);
}

function bis_export_csv($full_path, $rows) {
    $file = fopen($full_path, 'w');
    if(!$file) return;
    fputcsv($file, ['Post ID','Post Title','Post URL','Image URL','HTTP Status','Error Type','Domain','Percentage']);
    foreach($rows as $row) {
        fputcsv($file, [$row['post_id'], $row['post_title'], $row['post_url'], $row['image_url'], $row['http_status'], $row['error_type'], $row['domain'], $row['percentage']]);
    }
    fclose($file);
}

function bis_export_domains($full_path, $domains) {
    $file = fopen($full_path, 'w');
    if(!$file) return;
    fputcsv($file, ['Domain','Occurrences']);
    foreach($domains as $dom => $count) fputcsv($file, [$dom, $count]);
    fclose($file);
}