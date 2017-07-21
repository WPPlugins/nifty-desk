<?php
/*Handles Reporting Ajax Calls*/
add_action("wp_ajax_nifty_desk_rep_update_stats", "nifty_desk_rep_ajax_callback");
function nifty_desk_rep_ajax_callback(){

	if( isset( $_POST['action'] ) ){
		if ($_POST['action'] == 'nifty_desk_rep_update_stats') {
		 	if(isset($_POST['payload']['nifty_desk_rep_period'])){
		 		$return_data = nifty_desk_get_tickets_for_period(intval(sanitize_text_field($_POST['payload']['nifty_desk_rep_period'])), $_POST['payload'] );
		 		echo json_encode($return_data);
		 		wp_die();
		 	}
		}
	}
}


function nifty_desk_get_tickets_for_period($period_value, $payload){
	$return_data = array();
	$period_in_days = 30;
	if(isset($period_value)){
		$nifty_desk_args = false;
		switch($period_value){
			case 0:
				$nifty_desk_args = array(
				    'posts_per_page'  => -1,
				    'date_query' => array(
				    	'after' => array(
				    		'year'     => date('Y'),
				    		'month'    => date('m'),
				    		'day' 	   => date('d') - 1
				    	)
				    ),
				    'post_type'       => 'nifty_desk_tickets'
				);
				$period_in_days = 1;
				break;
			case 1:
				$nifty_desk_args = array(
				    'posts_per_page'  => -1,
				    'date_query' => array(
				    	'after' => array(
				    		'year'     => date('Y'),
				    		'month'    => date('m'),
				    		'day' 	   => date('d') - 7
				    	)
				    ),
				    'post_type'       => 'nifty_desk_tickets'
				);
				$period_in_days = 7;
				break;
			case 2:
				$nifty_desk_args = array(
				    'posts_per_page'  => -1,
				    'date_query' => array(
				    	'after' => array(
				    		'year'     => date('Y'),
				    		'month'    => date('m') - 1,
				    		'day' 	   => date('d')
				    	)
				    ),
				    'post_type'       => 'nifty_desk_tickets'
				);
				$period_in_days = 30;
				break;
			case 3:
				$nifty_desk_args = array(
				    'posts_per_page'  => -1,
				    'date_query' => array(
				    	'after' => array(
				    		'year'     => date('Y'),
				    		'month'    => date('m') - 2,
				    		'day' 	   => date('d')
				    	)
				    ),
				    'post_type'       => 'nifty_desk_tickets'
				);
				$period_in_days = 60;
				break;
			default:
				$nifty_desk_args = apply_filters("nifty_desk_get_ticket_query_args_hook", $period_value, $payload); //Pass it on to the next function if does not match original case
				$period_in_days = intval($nifty_desk_args['temp_data']['days']);
				unset($nifty_desk_args['temp_data']); //remove temp data
				break;
		}


		$return_count_new = 0;
		$return_count_solved = 0;
		$return_count_responded = 0;
		
		$total_time =0;

		if($nifty_desk_args !== false){
			$all_nifty_desk_tickets = get_posts($nifty_desk_args);
			$statuses = array(
    			'0' => __('Open', 'nifty_desk'),
    			'3' => __('Pending', 'nifty_desk'),
    			'1' => __('Solved', 'nifty_desk'),
    			'2' => __('Closed', 'nifty_desk'),
    			'9' => __('New', 'nifty_desk')
			);

			foreach ($all_nifty_desk_tickets as $key => $value) {

				$ticket_meta_data = get_post_meta($value->ID);
				$ticket_status = $ticket_meta_data['ticket_status'][0];
				if(isset($statuses[$ticket_status])){
					//This is a valid index
					if(intval($ticket_status) === 1 || intval($ticket_status) === 2){
						//solved ticket - or closed that is
						$return_count_solved ++;
					} 
				}
				//Just a ticket
				$return_count_new ++;

				//Now the response time stuff
				$req_date = strtotime($value->post_date);
				$current_date = strtotime(date("Y-m-d H:i:s"));
				$diff_interval = false;
				$ticket_responses = nifty_desk_get_post_meta_all($value->ID);
				if(is_array($ticket_responses) && count($ticket_responses) > 0){
					//Got responses	
					$first_response = $ticket_responses[0];
					if(is_object($first_response) && $first_response !== false){
						$first_response_time = strtotime(nifty_desk_get_response_data($first_response->post_id)->post_date);
						$diff_interval = $first_response_time - $req_date;
						$total_time += intval($diff_interval);
						$return_count_responded++;
					} else {
						/* If uncommentationated, will take any tickets that havent been responded to and work out the date backwards from current time */
						/* $diff_interval = $current_date - $req_date; */
					}
				} else {
					/* $diff_interval = $current_date - $req_date; */
				}
				
			}
		}
		//$total_diff = intval($total_time / $return_count_new);
		//$total_diff = intval($total_time / $return_count_responded);

 		if ($return_count_responded < 1 || !$return_count_responded) {
            $total_diff = 0;
        } else {
            $total_diff = intval($total_time / $return_count_responded);
        }
		$avg_hour = intval($total_diff / 60 / 60);

		$total_diff = $total_diff - ($avg_hour * 60 * 60);
		$avg_minutes = intval($total_diff / 60);

		$total_diff = $total_diff - ($avg_minutes * 60);
		$avg_seconds = intval($total_diff);

		$return_array['data']['success_action'] = 'update_nifty_desk_rep_heading';
		$return_array['data']['nifty_desk_new_count'] = $return_count_new;
		$return_array['data']['nifty_desk_solved_count'] = $return_count_solved;
		$return_array['data']['nifty_desk_first_response'] = $avg_hour . ":" . ($avg_minutes > 9 ? $avg_minutes :  "0" . $avg_minutes) . ":" . ($avg_seconds > 9 ? $avg_seconds :  "0" . $avg_seconds);
		$return_array['data']['selected_period_days'] = $period_in_days;

		$return_array = apply_filters("nifty_desk_get_tickets_for_period_hook", $return_array, $all_nifty_desk_tickets);
	}else{
		$return_array['error'] = "No value passed through";
	}
	return $return_array;
}