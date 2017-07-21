<?php
/* Handles all routes related to the Nifty Desk API */

/*
 * Add the following routes:
 * - '/nifty_desk/v1/create_ticket' 
 * - '/nifty_desk/v1/view_ticket' 
 * - '/nifty_desk/v1/delete_ticket' 
 */
add_action('rest_api_init', function(){
	register_rest_route('nifty_desk/v1','/create_ticket', array(
						'methods' => 'GET, POST',
						'callback' => 'nifty_desk_api_create_ticket'
	));

	register_rest_route('nifty_desk/v1','/view_ticket', array(
						'methods' => 'GET, POST',
						'callback' => 'nifty_desk_api_view_ticket'
	));

	register_rest_route('nifty_desk/v1','/delete_ticket', array(
						'methods' => 'GET, POST',
						'callback' => 'nifty_desk_api_delete_ticket'
	));

	do_action("nifty_desk_api_route_hook");
});