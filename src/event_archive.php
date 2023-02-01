<?php

sadf
$check_page_exist = get_page_by_path('events', 'OBJECT', 'page');
// Check if the page already exists
if(empty($check_page_exist)) {
    $page_id = wp_insert_post(
        array(
        'comment_status' => 'close',
        'ping_status'    => 'close',
        'post_title'     => ucwords('Termine'),
        'post_name'      => strtolower(trim('events')),
        'post_status'    => 'publish',
        'post_content'   => '[events_calendar]',
        'post_type'      => 'page',
        )
    );
}
