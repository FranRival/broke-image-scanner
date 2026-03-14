<?php

if (!defined('ABSPATH')) {
    exit;
}

function bis_admin_page() {

    echo '<div class="wrap">';
    echo '<h1>Broken Image Scanner</h1>';

    echo '<form method="post">';
    submit_button('Scan Images');
    echo '</form>';

    if (isset($_POST['submit'])) {

        $results = bis_scan_images();

        echo '<table class="widefat striped">';
        echo '<thead>
        <tr>
        <th>Post</th>
        <th>Image URL</th>
        <th>Status</th>
        <th>HTTP Code</th>
        </tr>
        </thead>';

        foreach ($results as $row) {

            echo '<tr>';

            echo '<td>'.$row['post'].'</td>';
            echo '<td>'.$row['url'].'</td>';
            echo '<td>'.$row['status'].'</td>';
            echo '<td>'.$row['code'].'</td>';

            echo '</tr>';
        }

        echo '</table>';
    }

    echo '</div>';
}