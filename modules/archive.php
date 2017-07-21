<?php


/*
add_action( 'admin_head', 'nifty_desk_archive' );

function nifty_desk_archive() {
	global $wpdb;
	$results = $wpdb->get_results("select * from `".$wpdb->prefix."posts` where ((`post_date` < NOW() - INTERVAL 1 MONTH) AND (`post_type` = 'nifty_desk_tickets' OR `post_type` = 'nifty_desk_responses'))");
	var_dump($results);
	
}

*/