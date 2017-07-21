<?php



add_action("nifty_desk_view_control","nifty_desk_hook_control_view_control",10);

function nifty_desk_hook_control_view_control() {

	$current_views = get_option("nifty_desk_views");

	echo '<ul class="nifty_desk_db_controls">';

	foreach ($current_views as $key => $view) {

		echo '<li><a href="javascript:void(0)" class="nifty_desk_view_control" id="nifty_desk_view_control_'.$key.'" view="'.$key.'"><span class="nifty_desk_view_control_ticket_name" id="nifty_desk_view_control_ticket_name_'.$key.'">'.$view['title'].'</span><span class="nifty_desk_view_control_ticket_count" id="nifty_desk_view_count_'.$key.'"">'.nifty_desk_ticket_count_by_view($key).'</span></a></li>';

	}

	echo '</ul>';

}



function nifty_desk_ticket_count_by_view($key) {



	$check = json_decode(nifty_desk_get_tickets_by_view($key,0,1,0,false,false,false,false,true));

	return $check->cnt;

}





function nifty_desk_get_tickets_by_view($view, $offset = 0, $limit = 20, $return_all_counts = 0, $sortbytype = false, $sortbyvalue = false, $sortby = false, $search_string = false, $just_count = false) {

	$return_all_counts = intval($return_all_counts);



	$orderby = 'date';

	$order = 'DESC';


	if (!$search_string) {

		$current_views = get_option("nifty_desk_views");



		if (isset($current_views[$view])) { } else {  echo "error2"; wp_die(); }



		$orderby = $current_views[$view]['data']['orderby'];

		if (!isset($current_views[$view]['data']['channel'])) {

			$channel = false;

		} else {

			$channel = $current_views[$view]['data']['channel'];

		}

		

		$order = $current_views[$view]['data']['order'];

		$priority = $current_views[$view]['data']['priority'];

		if (isset($current_views[$view]['data']['department'])) { $department = $current_views[$view]['data']['department']; } else { $department = false; }

		if (!isset($current_views[$view]['data']['status'])) {

			/* if a user created a view and selected no statuses, select all by default now  */

			$status = array(

				0 => true,

				1 => true,

				2 => true,

				3 => true,

				9 => true

				);

		} else {

			$status = $current_views[$view]['data']['status'];

		}

		$agents = $current_views[$view]['data']['agents'];

	}

	

	



	$department_string = "";



	$and_relation = array("relation" => "AND");











	$meta_query = array();



	if (!$search_string) { 

		if (count($status) > 1) {

			$status_meta_query = array("relation" => "OR");



			/* multiple status values requested */

			$cnter = 0;

			foreach ($status as $key => $status_val) {



				if ((string)$key == "-1") {

					/* Unassigned statuses */

					$status_meta_query_sub = array(

						'key'     => 'ticket_status',

						'compare' => 'NOT EXISTS',

					);



				} else {

					$status_meta_query_sub = array(

						'key'     => 'ticket_status',

						'value'   => (string)$key,

						'compare' => '=',

					);

				}

				array_push($status_meta_query,$status_meta_query_sub);

			}

			

			

		} else {

			/* single status value requested */

			if ((string)key($status) == "-1") {

				/* Unassigned statuses */

				$status_meta_query = array(

					'key'     => 'ticket_status',

					'compare' => 'NOT EXISTS',

				);



			} else {

				$status_meta_query = array(

					'key'     => 'ticket_status',

					'value'   => (string)key($status),

					'compare' => '=',

				);

			}

			

		}

		

		if ($department) {



			$cnter = 0;

			foreach ($department as $key => $department_val) {

				if ($cnter == 0) { 

					$department_string = $key;

				} else {

					$department_string = $department_string.",".$key;

				}

				$cnter++;

			}

		}

		







		if ($priority > 0) {



			if (count($priority) > 1) {

				$priority_meta_query = array("relation" => "OR");



				/* multiple priority values requested */

				$cnter = 0;

				foreach ($priority as $key => $priority_val) {

					if ((string)$key == "0") {

						/* all priorities, so just ignore this meta query build subset */

						/* set $priority to zero so that we do not include this meta query sub build */

						$priority = 0;

						break;





					} else if ((string)$key == "-1") {

						/* Unassigned tickets */

						$priority_meta_query_sub = array(

							'key'     => 'ticket_priority',

							'compare' => 'NOT EXISTS',

						);



					} else {

						$priority_meta_query_sub = array(

							'key'     => 'ticket_priority',

							'value'   => (string)$key,

							'compare' => '=',

						);

					}

					array_push($priority_meta_query,$priority_meta_query_sub);

				}

				

				

			} else {

				/* single priority value requested */

				if ((string)key($priority) == "0") {

					/* all agents, so just ignore this meta query build subset */

				} else if ((string)key($priority) == "-1") {

					/* Unassigned tickets */

					$priority_meta_query = array(

						'key'     => 'ticket_priority',

						'compare' => 'NOT EXISTS',

					);



				} else {



					$priority_meta_query = array(

								'key'     => 'ticket_priority',

								'value'   => (string)key($priority),

								'compare' => '=',

							);

				}

				$include_priority = true;

			}

		}



		if ($agents > 0) {



			if (count($agents) > 1) {



				$agent_meta_query = array("relation" => "OR");



				/* multiple agent values requested */

				$cnter = 0;

				foreach ($agents as $key => $agents_val) {

					if ((string)$key == "0") {

						/* all agents, so just ignore this meta query build subset */

						/* set $agents to zero so that we do not include this meta query sub build */

						$agents = 0;

						break;





					} else if ((string)$key == "-1") {

						/* Unassigned tickets */

						$agent_meta_query_sub = array(

							'key'     => 'ticket_assigned_to',

							'compare' => 'NOT EXISTS',

						);



					} else if ((string)$key == "current_agent") {

						/* Tickets for this current user only */

						$agent_meta_query_sub = array(

							'key'     => 'ticket_assigned_to',

							'value'	  => get_current_user_id(),

							'compare' => '=',



						);





					} else {

						$agent_meta_query_sub = array(

							'key'     => 'ticket_assigned_to',

							'value'   => (string)$key,

							'compare' => '=',

						);

					}

					array_push($agent_meta_query,$agent_meta_query_sub);

				}

				

				

			} else {



				/* single priority value requested */

				if ((string)key($agents) == "0") {

					/* all agents, so just ignore this meta query build subset */

				} else if ((string)key($agents) == "-1") {

					/* Unassigned tickets */

					$agent_meta_query = array(

						'key'     => 'ticket_assigned_to',

						'compare' => 'NOT EXISTS',

					);



				} else if ((string)key($agents) == "current_agent") {



					/* Tickets for this current user only */

					$agent_meta_query = array(

						'key'     => 'ticket_assigned_to',

						'value'	  => get_current_user_id(),

						'compare' => '=',



					);





				} 

			 	else {

					$agent_meta_query = array(

								'key'     => 'ticket_assigned_to',

								'value'   => (string)key($agents),

								'compare' => '=',

							);

					$include_agent = true;

				}

			}

		}

	



		if ($priority > 0 || $agents > 0) {





			$meta_query = array("relation" => "AND");

			$meta_query['status_query_clause'] = $status_meta_query;

			//array_push($meta_query,$status_meta_query);

			

			if ($priority) {

				$meta_query['priority_query_clause'] = $priority_meta_query;

				//array_push($meta_query,$priority_meta_query);

			}

			if ($agents) {

				$meta_query['agent_query_clause'] = $agent_meta_query;

				//array_push($meta_query,$agent_meta_query);

			}



		} else {

			/* one line query, do not use AND or OR */

			$meta_query = array("relation" => "AND");

			





			$meta_query['status_query_clause'] = $status_meta_query;





		}

	}	







	global $wpdb;

	//$meta_sql = get_meta_sql( $meta_query, 'post', $wpdb->posts, 'ID' );





	//echo $required_action;



	$ret = "";





	/* limit is set to $limit+1 here because we are actively seeking if there are more posts than what has been asked for, if true, then we can let the JS know that it can keep the "next" button active. */



	$posts_per_page_default = get_option("posts_per_page");



	update_option("posts_per_page",$limit+1);



	if ($department) {



		$terms = explode(",",$department_string);

		$terms = array_values($terms);



		$tax_query = array(

			array(

				'taxonomy' => 'nifty_desk_departments',

				'field'    => 'term_id',

				'terms'    => $terms

			)

		);

		$args = array(

			'post_type' => 'nifty_desk_tickets',

			'posts_per_page ' => $limit+1,

			'offset' => $offset,

			'meta_query' => array($meta_query),

			'orderby' => $orderby,

			'order' => $order,

			'tax_query' => $tax_query

		);

	} else {

		$args = array(

			'post_type' => 'nifty_desk_tickets',

			'posts_per_page ' => $limit+1,

			'offset' => $offset,

			'meta_query' => array($meta_query),

			'orderby' => $orderby,

			'order' => $order

		);

	}

	

	if ($search_string) {

		$args['s'] = $search_string;

	}

	



	



	/* do sort by over-rides (when users click on the table headings */

	if ($sortbytype == 'meta')  {

		if ($sortbyvalue=='ticket_last_updated') {

			/* this is a date.. */





			$last_updated_meta_query_sub = array(

				'key' => 'ticket_last_updated',

				'compare' => "EXISTS"



			);

			$meta_query['ticket_last_updated_clause'] = $last_updated_meta_query_sub;

			$args['orderby'] = 'ticket_last_updated_clause';

			$args['order'] = $sortby;

			$args['meta_query'] = $meta_query;

			

			

		}

		if ($sortbyvalue=='ticket_sentiment') {

			/* this is a date.. */





			$last_updated_meta_query_sub = array(

				'key' => 'ticket_sentiment',

				'compare' => "EXISTS"



			);

			$meta_query['ticket_sentiment_clause'] = $last_updated_meta_query_sub;

			$args['orderby'] = 'ticket_sentiment_clause';

			$args['order'] = $sortby;

			$args['meta_query'] = $meta_query;

			

			

		}



		if ($sortbyvalue=='ticket_priority') {

			/* this is a date.. */

			$priority_meta_query_sub = array(

				'key' => 'ticket_priority',

				'compare' => "EXISTS"

			);

			$meta_query['ticket_priority_clause'] = $priority_meta_query_sub;

			$args['orderby'] = 'ticket_priority_clause';

			$args['order'] = $sortby;

			$args['meta_query'] = $meta_query;

			

		}

		if ($sortbyvalue=='ticket_status') {

			/* this is a date.. */

			$status_meta_query_sub = array(

				'key' => 'ticket_status',

				'compare' => "EXISTS"

			);

			$meta_query['ticket_status_clause'] = $status_meta_query_sub;

			$args['orderby'] = 'ticket_status_clause';

			$args['order'] = $sortby;

			$args['meta_query'] = $meta_query;

			

		}

		if ($sortbyvalue=='ticket_assigned_to') {

			/* this is a date.. */

			$status_meta_query_sub = array(

				'key' => 'ticket_assigned_to',

				'compare' => "EXISTS"

			);

			$meta_query['ticket_assigned_to_clause'] = $status_meta_query_sub;

			$args['orderby'] = 'ticket_assigned_to_clause';

			$args['order'] = $sortby;

			$args['meta_query'] = $meta_query;

			

		}

		if ($sortbyvalue=='ticket_channel_id') {

			/* this is a date.. */

			$status_meta_query_sub = array(

				'key' => 'ticket_channel_id',

				'compare' => "EXISTS"

			);

			$meta_query['ticket_channel_id_clause'] = $status_meta_query_sub;

			$args['orderby'] = 'ticket_channel_id_clause';

			$args['order'] = $sortby;

			$args['meta_query'] = $meta_query;

			

		}	

	} else if ($sortbytype == 'default') {



		if ($sortbyvalue=='ticket_id') {

			$args['orderby'] = 'ID';

			$args['order'] = $sortby;

		}

		if ($sortbyvalue=='ticket_title') {

			$args['orderby'] = 'title';

			$args['order'] = $sortby;

		}

		if ($sortbyvalue=='ticket_author') {

			$args['orderby'] = 'author';

			$args['order'] = $sortby;

		}

		if ($sortbyvalue=='ticket_created') {

			$args['orderby'] = 'date';

			$args['order'] = $sortby;

		}



	}	





	$my_query = new WP_Query( $args );




	if ($just_count) {

		$js = json_encode(array(

			'cnt' => $my_query->found_posts

			)

		);

		return $js;

	}



	$ticket_counter = 0;

	$is_more = false;

	$is_less = false;



	if ($offset > 0) { $is_less = true; } /* if we've offset anything, logically there would be previous items so set is_less to true */


	if ( $my_query->have_posts() ) {



		while ( $my_query->have_posts() ) {

			$my_query->the_post();

			$ticket_id = get_the_ID();




			$post_meta = get_post_meta($ticket_id);



			$post_status = nifty_desk_return_ticket_status_html_block( $post_meta['ticket_status'][0] );


			if (isset($post_meta['ticket_channel_id'])) {



				$ticket_channel = $post_meta['ticket_channel_id'][0];

				if (function_exists('nifty_desk_get_ticket_channel_name')) {



					$ticket_channel_id = $ticket_channel;

					$channel_name_full = nifty_desk_get_ticket_channel_name($ticket_channel);

					$channel_name = $channel_name_full;



				} else {

					$ticket_channel_id = 0;

					$channel_name_full = __('Support Desk','nifty_desk');

					$channel_name = __('s.desk','nifty_desk');

				}

			} else {

				$ticket_channel_id = 0;

				$channel_name_full = __('Support Desk','nifty_desk');

				$channel_name = __('s.desk','nifty_desk');

			}

			if (isset($post_meta['ticket_status'][0])) { $is_public = $post_meta['ticket_public'][0]; } else { $is_public = false; }

			if (isset($post_meta['ticket_assigned_to'][0])) { $assigned_to = $post_meta['ticket_assigned_to'][0]; } else { $assigned_to = false; }



			$user_data = get_user_by('id', $assigned_to);

			if (!$user_data) { $user_data = (object)[]; $user_data->display_name = "-"; }





			$response_count = nifty_desk_cnt_responses( $ticket_id );


			$priority = nifty_desk_return_ticket_priority_returns( $ticket_id );



			$last_updated = false;

			if (isset($post_meta['ticket_last_updated'][0])) {

				$last_updated = $post_meta['ticket_last_updated'][0];

			} else {

				/* backwards compatibility - get last response date and then save it to the 'ticket_last_updated' meta */

				$last_updated = nifty_desk_get_last_updated_time( $ticket_id );

				if ($last_updated) {

					/* we found a response date via backwards compa, let's save it so we dont need to do that again. */

					update_post_meta( $ticket_id, 'ticket_last_updated', strtotime($last_updated));

				}

			}

			if ($last_updated) {

				

				$last_updated_actual = date("Y-m-d H:i:s",intval($last_updated));

				$last_updated = nifty_desk_time_elapsed_string($last_updated);

			} else {

				$last_updated_actual = '-';

				$last_updated = "-";

			}



			$author_id = get_the_author_meta('ID');

			$user_info = get_userdata($author_id);





			$author_name = $user_info->display_name;

			$author_email = $user_info->user_email;





			

            $ticket_counter++;

            if ($ticket_counter <= $limit) {





				$ret .= "<tr id='nifty_desk_modern_ticket_row_".$ticket_id."' class='nifty_desk_modern_ticket_row'>";

				$ret .= "<td class='ticket_checkbox'><input type='checkbox' class='nifty_desk_checkbox' value='".$ticket_id."' /></td>";

				$ret .= "<td class='nifty_desk_db_single_ticket ticket_status' ticket_id='".$ticket_id."'>" . $post_status . " ".apply_filters("nifty_desk_return_seen_tag_in_list","",$ticket_id)."</td>";

				$ret .= "<td class='nifty_desk_db_single_ticket ticket_id' ticket_id='".$ticket_id."'>#" . $ticket_id . "</td>";

				$ret .= apply_filters("nifty_desk_ticket_view_list_column_body","",$ticket_id,$post_meta);

				$ret .= "<td class='nifty_desk_db_single_ticket ticket_title' ticket_id='".$ticket_id."' ><span title='".get_the_title()."'>" . get_the_title() . "</span></td>";

				$ret .= "<td class='nifty_desk_db_single_ticket ticket_author' ticket_id='".$ticket_id."'><span title='".$author_email."'>" . $author_name . "</span></td>";

				$ret .= "<td class='nifty_desk_db_single_ticket ticket_date' ticket_id='".$ticket_id."'><span title='".date("Y-m-d H:i:s",get_the_date('U'))."'>".nifty_desk_time_elapsed_string(get_the_time('U')) . "</span></td>";

				$ret .= "<td class='nifty_desk_db_single_ticket ticket_updated' ticket_id='".$ticket_id."'><span title='".$last_updated_actual."'>" . $last_updated . "</span></td>";

				$ret .= "<td class='nifty_desk_db_single_ticket ticket_priority' ticket_id='".$ticket_id."'>" . $priority . "</td>";

				$ret .= "<td class='nifty_desk_db_single_ticket ticket_responses' ticket_id='".$ticket_id."'>" . $response_count . "</td>";

				$ret .= "<td class='nifty_desk_db_single_ticket ticket_owner' ticket_id='".$ticket_id."'>" . $user_data->display_name . "</td>";

				$ret .= "<td class='nifty_desk_db_single_ticket ticket_channel' ticket_id='".$ticket_id."'>" . nifty_desk_return_ticket_channel_html_block($channel_name,$ticket_channel_id,$channel_name_full) . "</td>";

				$ret .= "</tr>";

			} else {

				$is_more = true;

			}




		}

	} else {

		$ret .= "<tr><td colspan='11' style='padding: 10px 0;'>".__('No tickets found', 'nifty_desk')."</td></tr>";

	}

	if ($return_all_counts) {

		$js = json_encode(array(

			'ticket_cnt' => $ticket_counter,

			'ticket_html' => $ret,

			'is_more' => $is_more,

			'is_less' => $is_less,

			'orderby' => $args['orderby'],

			'order' => $args['order'],

			'priority' => $priority,

			'ticket_status' => $status,

			'cnt' => $my_query->found_posts,

			'limit' => $limit,

			'offset' => $offset,

			'counts' => nifty_desk_return_ticket_count_array()

			)

		);

	} else {

		$js = json_encode(array(

			'ticket_cnt' => $ticket_counter,

			'ticket_html' => $ret,

			'is_more' => $is_more,

			'is_less' => $is_less,

			'orderby' => $args['orderby'],

			'order' => $args['order'],

			'priority' => $priority,

			'ticket_status' => $status,

			'cnt' => $my_query->found_posts,

			'limit' => $limit,

			'offset' => $offset

			)

		);



	}

	//var_dump($js);

	update_option("posts_per_page",$posts_per_page_default);



	return $js;

}



function nifty_desk_return_ticket_count_array() {

	$current_views = get_option("nifty_desk_views");





	$view_array = array();

	foreach ($current_views as $key => $view) {

		$view_array[$key] = intval(nifty_desk_ticket_count_by_view($key));



	}

	return $view_array;



	

}



function nifty_desk_views_html_output() {



	$current_views = get_option("nifty_desk_views");



	echo "<p><span class='update-nag'>".sprintf(__("Upgrade to the <a href='%s' target='_BLANK'>Pro version</a> of Nifty Desk and create an unlimited amount of customizable views.","nifty_desk"),'http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=views')."</span></p>";





	echo "<table class='wp-list-table widefat fixed striped pages'>";

	echo "<thead>";

	echo "<tr>";

	echo "<th>Name</th>";

	echo "<th class='nifty_desk_table_action'>Action</th>";

	echo "</tr>";

	echo "</thead>";

	echo "<tbody>";

	foreach ($current_views as $key => $view) {

		echo "<tr id='view_tr_'".$key.">";

		echo "<td>".$view['title']."</td>";

		echo "<td>".apply_filters("nifty_desk_filter_view_action_control","",$key)."</td>";

		echo "</tr>";





	}

	echo "</tbody>";

	echo "</table>";





}



function nifty_desk_set_default_views() {

	$current_views = array(

		1 => array(

			"readonly" => true,

			"active" => 1,

			"title" => __("Your unsolved tickets","nifty_desk"),

			"data" => array(

				"status" => array(

					"-1" => true,

					"0" => true,

					"3" => true,

					"9" => true

				),

				"priority" => false,

				"agents" => array(

					"current_agent" => true

				),

				"orderby" => "date",

				"order" => "asc",

				"department" => false

			)

		),

		2 => array(

			"readonly" => true,

			"active" => 1,

			"title" => __("All unsolved tickets","nifty_desk"),

			"data" => array(

				"status" => array(

					"0" => true,

					"3" => true,

					"9" => true

				),

				"priority" => false,

				"agents" => false,

				"orderby" => "date",

				"order" => "asc",

				"department" => false

			)

		),

		3 => array(

			"readonly" => true,

			"active" => 1,

			"title" => __("New tickets","nifty_desk"),

			"data" => array(

				"status" => array(

					"9" => true

				),

				"priority" => false,

				"agents" => false,

				"orderby" => "date",

				"order" => "asc",

				"department" => false

			)

		),

		4 => array(

			"readonly" => true,

			"active" => 1,

			"title" => __("Open tickets","nifty_desk"),

			"data" => array(

				"status" => array(

					"0" => true

				),

				"priority" => false,

				"agents" => false,

				"orderby" => "date",

				"order" => "asc",

				"department" => false

			)

		),

		5 => array(

			"readonly" => true,

			"active" => 1,

			"title" => __("Pending tickets","nifty_desk"),

			"data" => array(

				"status" => array(

					"3" => true

				),

				"priority" => false,

				"agents" => false,

				"orderby" => "date",

				"order" => "asc",

				"department" => false

			)

		),

		6 => array(

			"readonly" => true,

			"active" => 0,

			"title" => __("Closed Tickets","nifty_desk"),

			"data" => array(

				"status" => array(

					"1" => true,

					"2" => true

				),

				"priority" => false,

				"agents" => false,

				"orderby" => "date",

				"order" => "asc",

				"department" => false

			)

		)





	);



	update_option("nifty_desk_views",$current_views);

}



function nifty_desk_output_ticket_actions($ticket_id) {





		if( current_user_can('manage_options' ) ){

			$delete_button = '<a href="javascript:void(0);" id="nifty_desk_delete_ticket" ticket_id="'.$ticket_id.'">'.__('Delete', 'nifty_desk').'</a>';

		} else {

			$delete_button = "";

		}





		$ret .= '<div class="nifty_desk_dropdown_button">';

		$ret .= '	<span><a href="javascript:void(0);" class="button button-secondary nifty_desk_action_button">'.__("Actions","nifty_desk").' <span class="nifty_desk_more_button">&nbsp;<span></a></span>';

  		$ret .= '		<div class="nifty_desk_dropdown-content">';

    	$ret .= 			$delete_button.'';

  		$ret .= '			<a href="javascript:void(0);" id="nifty_desk_merge_ticket" ticket_id="'.$ticket_id.'">'.__('Merge into another ticket', 'nifty_desk').'</a>';

  		$ret .= '		</div>';

		$ret .= '</div>';





		return $ret;



}



function nifty_desk_check_for_followup($ticket_id) {

	$check = get_post_meta( $ticket_id, '_ticket_follow_up', true );

	if ($check) {



		$merged_link = sprintf( __( 'This ticket was a follow up from <a href="javascript:void(0);" tid="%1$s" class="nifty_desk_followup_from">#%1$s</a>.</a>', 'nifty_desk' ),

            $check            

        );

		return "<span class='nifty_desk_error' id='nifty_desk_followup_from_".$check."'>".$merged_link."</span>";

	}

}





function nifty_desk_check_for_merge($ticket_id) {

	$check = get_post_meta( $ticket_id, '_nifty_desk_merged_with', true );

	if ($check) {



		$merged_link = sprintf( __( 'This ticket was merged into <a href="javascript:void(0);" tid="%1$s" class="nifty_desk_merged_with">#%1$s</a>.</a>', 'nifty_desk' ),

            $check            

        );

		return "<span class='nifty_desk_error' id='nifty_desk_merged_with_".$check."'>".$merged_link."</span>";

	}

}





function nifty_desk_check_for_merge_from($ticket_id) {

	$check = get_post_meta( $ticket_id, '_nifty_desk_merged_from', true );

	if ($check) {



		$merged_link = sprintf( __( 'This ticket was merged from <a href="javascript:void(0);" tid="%1$s" class="nifty_desk_merged_with">#%1$s</a>.</a>', 'nifty_desk' ),

            $check            

        );

		return "<span class='nifty_desk_error' id='nifty_desk_merged_with_".$check."'>".$merged_link."</span>";

	}

}



function nifty_desk_get_last_updated_time($ticket_id) {

	

	$data = nifty_desk_get_last_response( $ticket_id );

    if (isset($data->post_author)) {

        $author = $data->post_author;

        if ($author) {

            $author_data = get_userdata($author);

            return $data->post_date;

        } else {

            return false;

        }

    } else {

        return false;

    }

}





add_filter("nifty_desk_response_after_author_name","nifty_desk_filter_control_response_after_author_name",10,2);

function nifty_desk_filter_control_response_after_author_name($content,$ticket_id) {

	$ticket_raw = get_post_meta($ticket_id, 'ticket_full_raw', true );

	$ticket_html = get_post_meta($ticket_id, 'ticket_html', true );



	if ($ticket_raw) {

		$content .= " | <a href='".admin_url("?nifty_desk_raw=".$ticket_id)."' target='_BLANK' class='nifty_desk_view_front_end'>view email source</a>";

	}

	if ($ticket_html) {

		$content .= " | <a href='".admin_url("?nifty_desk_html=".$ticket_id)."' target='_BLANK' class='nifty_desk_view_front_end'>view HTML</a>";

	}



	return $content;



}



function nifty_desk_mark_ticket_as_read($tid) {

	update_post_meta ($tid, 'nifty_desk_seen', current_time('timestamp'));

}