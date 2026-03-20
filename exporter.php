<?php
if (!defined('ABSPATH')) exit;


// =========================
// GENERAR REPORTES
// =========================
function bis_generate_reports($images, $total, $path){

    // 🔥 limpiar archivos previos
    @unlink($path . 'broken-images-report.csv');
    @unlink($path . 'timeout-images-report.csv');

    $broken = [];
    $timeout = [];

    foreach($images as $img){

        $pct = ($total > 0) ? round((1 / $total) * 100, 4) . "%" : "0%";
        $img['percentage'] = $pct;

        if(isset($img['error_type']) && $img['error_type'] === "broken"){
            $broken[] = $img;
        }

        if(isset($img['error_type']) && $img['error_type'] === "timeout"){
            $timeout[] = $img;
        }
    }

    bis_export_csv($path . 'broken-images-report.csv', $broken);
    bis_export_csv($path . 'timeout-images-report.csv', $timeout);
}


// =========================
// EXPORT CSV
// =========================
function bis_export_csv($file, $rows){

    $fp = fopen($file, 'w+'); // 🔥 FIX

    if(!$fp){
        error_log("ERROR WRITING FILE: " . $file);
        return;
    }

    fputcsv($fp, [
        'Post ID',
        'Post Title',
        'Post URL',
        'Image URL',
        'HTTP Status',
        'Error Type',
        'Domain',
        'Percentage'
    ]);

    foreach($rows as $r){

        fputcsv($fp, [
            $r['post_id'] ?? '',
            $r['post_title'] ?? '',
            $r['post_url'] ?? '',
            $r['image_url'] ?? '',
            $r['http_status'] ?? '',
            $r['error_type'] ?? '',
            $r['domain'] ?? '',
            $r['percentage'] ?? ''
        ]);
    }

    fclose($fp);
    chmod($file, 0644);
}