<?php

if (!defined('ABSPATH')) {
    exit;
}

function bis_scan_images() {

    $results = [];

    $posts = get_posts([
        'post_type' => 'post',
        'numberposts' => -1
    ]);

    foreach ($posts as $post) {

        preg_match_all('/<img[^>]+src="([^">]+)"/i', $post->post_content, $matches);

        if (!empty($matches[1])) {

            foreach ($matches[1] as $url) {

                $response = wp_remote_head($url, [
                    'timeout' => 10
                ]);

                if (is_wp_error($response)) {

                    $status = 'Broken';
                    $code = 'Error';

                } else {

                    $code = wp_remote_retrieve_response_code($response);

                    if ($code >= 400) {
                        $status = 'Broken';
                    } else {
                        $status = 'OK';
                    }
                }

                $results[] = [
                    'post' => $post->post_title,
                    'url' => $url,
                    'status' => $status,
                    'code' => $code
                ];
            }
        }
    }

    return $results;
}