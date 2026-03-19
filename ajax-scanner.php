<?php
if (!defined('ABSPATH')) exit;

ini_set('memory_limit','512M');
set_time_limit(0);

add_action('wp_ajax_bis_get_total_posts', 'bis_get_total_posts');
add_action('wp_ajax_bis_get_months_with_posts', 'bis_get_months_with_posts');
add_action('wp_ajax_bis_scan_batch', 'bis_scan_batch');
add_action('wp_ajax_bis_generate_excel', 'bis_generate_excel');


// =========================
// TOTAL POSTS
// =========================
function bis_get_total_posts(){

    $upload_dir = wp_upload_dir();
    $file = $upload_dir['basedir'] . '/bis-temp.json';

    if(file_exists($file)) unlink($file);

    $year = isset($_POST['year']) && $_POST['year'] != '' 
        ? intval($_POST['year']) 
        : date('Y');

    $month = isset($_POST['month']) && $_POST['month'] !== '' 
        ? intval($_POST['month']) 
        : null;

    $args = [
        'post_type'=>'post',
        'posts_per_page'=>-1,
        'fields'=>'ids',
        'date_query'=>[
            [
                'year'=>$year
            ]
        ]
    ];

    if(!empty($month)){
        $args['date_query'][0]['month'] = $month;
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
// SCAN BATCH
// =========================
function bis_scan_batch(){

    $offset = intval($_POST['offset']);

    $year = isset($_POST['year']) && $_POST['year'] != '' 
        ? intval($_POST['year']) 
        : date('Y');

    $month = isset($_POST['month']) && $_POST['month'] !== '' 
        ? intval($_POST['month']) 
        : null;

    $args=[
        'post_type'=>'post',
        'posts_per_page'=>5,
        'offset'=>$offset,
        'date_query'=>[
            [
                'year'=>$year
            ]
        ]
    ];

    if(!empty($month)){
        $args['date_query'][0]['month'] = $month;
    }

    $query = new WP_Query($args);
    $images=[];

    if(!empty($query->posts)){

        foreach($query->posts as $post){

            preg_match_all('/<img[^>]+src="([^">]+)"/i',$post->post_content,$matches);

            foreach(array_unique($matches[1] ?? []) as $url){

                if(!filter_var($url,FILTER_VALIDATE_URL)) continue;

                $response = wp_remote_head($url,['timeout'=>5]);

                if(is_wp_error($response)){
                    $response = wp_remote_get($url,['timeout'=>5]);
                }

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
}


// =========================
// GENERAR EXCEL
// =========================
function bis_generate_excel(){

    $upload_dir = wp_upload_dir();
    $file = $upload_dir['basedir'].'/bis-temp.json';

    if(!file_exists($file)){
        wp_send_json(['status'=>'error','msg'=>'No JSON']);
        return;
    }

    $data = json_decode(file_get_contents($file),true);

    if(!is_array($data) || empty($data)){
        wp_send_json(['status'=>'error','msg'=>'Empty data']);
        return;
    }

    $path = $upload_dir['basedir'].'/bis-reports/';

    if(!file_exists($path)){
        wp_mkdir_p($path);
    }

    require_once BIS_PATH.'exporter.php';

    bis_generate_reports($data,count($data),$path);

    wp_send_json([
        'status'=>'ok',
        'files'=>[
            'broken'=>$upload_dir['baseurl'].'/bis-reports/broken-images-report.csv',
            'timeout'=>$upload_dir['baseurl'].'/bis-reports/timeout-images-report.csv'
        ]
    ]);
}