<?php
if (!defined('ABSPATH')) exit;

ini_set('memory_limit','512M');
set_time_limit(0);

add_action('wp_ajax_bis_get_total_posts', 'bis_get_total_posts');
add_action('wp_ajax_bis_get_months_with_posts', 'bis_get_months_with_posts');
add_action('wp_ajax_bis_scan_batch', 'bis_scan_batch');

// 🔥 action renombrado
add_action('wp_ajax_bis_finalize', 'bis_generate_excel');


// =========================
// TOTAL POSTS
// =========================
function bis_get_total_posts(){

    $upload_dir = wp_upload_dir();
    $file = $upload_dir['basedir'] . '/bis-temp.json';
    if(file_exists($file)) unlink($file);

    $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    $month = isset($_POST['month']) && $_POST['month'] !== '' ? intval($_POST['month']) : null;
    $tag = isset($_POST['tag']) ? sanitize_text_field($_POST['tag']) : '';

    $args = [
        'post_type'=>'post',
        'posts_per_page'=>-1,
        'fields'=>'ids',
        'post_status'=>'publish'
    ];

    if(empty($tag)){
        $args['date_query'] = [
            [
                'year'=>$year
            ]
        ];

        if(!empty($month)){
            $args['date_query'][0]['month'] = $month;
        }
    }

    if(!empty($tag)){
        $args['tax_query'] = [
            [
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => $tag,
            ]
        ];
    }

    $query = new WP_Query($args);

    wp_send_json([
        'total_posts'=>count($query->posts)
    ]);
}


// =========================
// MESES CON POSTS
// =========================
function bis_get_months_with_posts(){

    global $wpdb;
    $year = intval($_POST['year']);

    $results = $wpdb->get_results($wpdb->prepare("
        SELECT MONTH(post_date) as month, COUNT(*) as total
        FROM {$wpdb->posts}
        WHERE post_type='post'
        AND post_status='publish'
        AND YEAR(post_date) = %d
        GROUP BY MONTH(post_date)
    ", $year));

    $months=[];
    foreach($results as $row){
        $months[intval($row->month)] = intval($row->total);
    }

    wp_send_json($months);
}


// =========================
// SCAN BATCH (SOLO UNA FUNCIÓN)
function bis_scan_batch(){

    try {

        $offset = intval($_POST['offset']);
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
        $month = isset($_POST['month']) && $_POST['month'] !== '' ? intval($_POST['month']) : null;
        $tag = isset($_POST['tag']) ? sanitize_text_field($_POST['tag']) : '';

        $args=[
            'post_type'=>'post',
            'posts_per_page'=>2,
            'offset'=>$offset,
            'post_status'=>'publish',
            'orderby'=>'ID',
            'order'=>'ASC'
        ];

        if(empty($tag)){
            $args['date_query'] = [
                ['year'=>$year]
            ];

            if(!empty($month)){
                $args['date_query'][0]['month'] = $month;
            }
        }

        if(!empty($tag)){
            $args['tax_query'] = [
                [
                    'taxonomy' => 'post_tag',
                    'field'    => 'slug',
                    'terms'    => $tag,
                ]
            ];
        }

        $query = new WP_Query($args);
        $images=[];

        if(!empty($query->posts)){

            foreach($query->posts as $post){

                preg_match_all('/<img[^>]+src="([^">]+)"/i',$post->post_content,$matches);

                $limit = 3;
                $count = 0;

                foreach(array_unique($matches[1] ?? []) as $url){

                    if($count >= $limit) break;
                    $count++;

                    if(!filter_var($url,FILTER_VALIDATE_URL)) continue;

                    $response = wp_remote_get($url,[
                        'timeout'=>3,
                        'redirection'=>2,
                        'sslverify'=>false
                    ]);

                    if(is_wp_error($response)){
                        $status="timeout"; 
                        $code="Timeout";
                    }else{
                        $code = wp_remote_retrieve_response_code($response);
                        $status = ($code>=400||!$code)?"broken":"ok";
                    }

                    $images[]=[
                        'post_id'=>$post->ID,
                        'post_title'=>$post->post_title,
                        'post_url'=>get_permalink($post->ID),
                        'image_url'=>$url,
                        'http_status'=>$code,
                        'error_type'=>$status,
                        'domain'=>parse_url($url,PHP_URL_HOST) ?: 'unknown'
                    ];
                }
            }
        }

        $upload_dir = wp_upload_dir();
        $file = $upload_dir['basedir'].'/bis-temp.json';

        $existing = file_exists($file) ? json_decode(file_get_contents($file),true) : [];

        file_put_contents($file,json_encode(array_merge((array)$existing,$images)));

        wp_send_json([
            'images'=>$images,
            'batch_count'=>$query->post_count
        ]);

    } catch (Exception $e){

        wp_send_json([
            'images'=>[],
            'batch_count'=>0,
            'error'=>$e->getMessage()
        ]);
    }
}


// =========================
// GENERAR EXCEL (CORREGIDO)


function bis_generate_excel(){

    if(!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'],'bis_nonce')){
        wp_send_json(['status'=>'error','msg'=>'Invalid nonce']);
    }

    $upload_dir = wp_upload_dir();
    $file = $upload_dir['basedir'].'/bis-temp.json';

    if(!file_exists($file)){
        wp_send_json(['status'=>'error','msg'=>'No JSON']);
    }

    $data = json_decode(file_get_contents($file),true);

    if(!is_array($data) || empty($data)){
        wp_send_json(['status'=>'error','msg'=>'Empty data']);
    }

    $path = $upload_dir['basedir'].'/bis-reports/';

    if(!file_exists($path)){
        wp_mkdir_p($path);
    }

    require_once BIS_PATH.'exporter.php';

    bis_generate_reports($data, count($data), $path);

    // =========================
    // 🔥 CONTEXTO DEL SCAN
    // =========================

    $domain = parse_url(home_url(), PHP_URL_HOST);

    $year  = isset($_POST['year']) ? intval($_POST['year']) : '';
    $month = isset($_POST['month']) ? intval($_POST['month']) : '';
    $tag   = isset($_POST['tag']) ? sanitize_text_field($_POST['tag']) : '';

    // 🔥 FORMATO DE FECHA INTELIGENTE
    $date_part = '';

    if(!empty($tag)){
        // opcional: puedes dejar vacío o usar 'full'
        $date_part = 'tag';
    }
    elseif(!empty($year) && !empty($month)){
        $date_part = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
    }
    elseif(!empty($year)){
        $date_part = $year;
    } else {
        $date_part = date('Y-m'); // fallback
    }

    // 🔥 TAG EN NOMBRE
    $tag_part = !empty($tag) ? '_tag-'.$tag : '';

    // 🔥 NOMBRES
    $broken_name  = "bis_broken_images_{$domain}{$tag_part}_{$date_part}.csv";
    $timeout_name = "bis_timeout_images_{$domain}{$tag_part}_{$date_part}.csv";

    // archivos originales
    $original_broken  = $path.'broken-images-report.csv';
    $original_timeout = $path.'timeout-images-report.csv';

    $final_broken  = $path.$broken_name;
    $final_timeout = $path.$timeout_name;

    if(file_exists($original_broken)){
        rename($original_broken, $final_broken);
    }

    if(file_exists($original_timeout)){
        rename($original_timeout, $final_timeout);
    }

    wp_send_json([
        'status'=>'ok',
        'files'=>[
            'broken'  => $upload_dir['baseurl'].'/bis-reports/'.$broken_name,
            'timeout' => $upload_dir['baseurl'].'/bis-reports/'.$timeout_name
        ]
    ]);
}