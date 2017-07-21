<?php

add_action("wp_ajax_nifty_desk_db_request_tickets_from_control", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_db_request_tickets_from_control_by_view", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_fetch_channels", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_db_request_ticket_from_content_list", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_db_update_ticket_status", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_db_update_ticket_priority", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_submit_response", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_db_request_tickets_from_control_priority", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_delete_ticket", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_modern_submit_internal_note", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_db_bulk_delete_tickets", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_delete_channel", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_db_search_ticets", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_resend_notification", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_delete_schedule", "nifty_desk_db_ajax_callback");

add_action("wp_ajax_nifty_desk_merge_get_ticket_details", "nifty_desk_db_ajax_callback");
add_action("wp_ajax_nifty_desk_merge_tickets", "nifty_desk_db_ajax_callback");


function nifty_desk_db_ajax_callback(){

	if( isset( $_POST['action'] ) ){

		if ($_POST['action'] == 'nifty_desk_resend_notification') {

			$nid = intval( sanitize_text_field( $_POST['post_id'] ) );
			$not_array = get_post_meta($nid, 'nifty_desk_notification_issue', true);
			if ($not_array) {
				$email = $not_array['email'];
				$subject = $not_array['subject'];
				$message = $not_array['message'];
				$in_reply_to = $not_array['in_reply_to'];
				$post_id = $not_array['post_id'];
				$channel = $not_array['channel'];
				$headers = $not_array['headers'];

				$checker = send_automated_emails($email,$subject,$message,$headers,$in_reply_to,$channel,$post_id);
				if ($checker) {
					/* success.. */
					echo "1";
					wp_die();
				} else {
					echo json_encode(array('errormsg' => 'Failed'));
					wp_die();
				}

			}


			die();
		}

		if ($_POST['action'] == 'nifty_desk_fetch_channels') {
			do_action("nifty_desk_fetch_channels");
			echo "2";
			die();
		}
		if ($_POST['action'] == 'nifty_desk_merge_tickets') {
			$ticket_id = intval( sanitize_text_field( $_POST['ticket_id'] ) );
			$merge_into = intval( sanitize_text_field( $_POST['merge_into'] ) );
			
			$post_data = get_post( $ticket_id, ARRAY_A);
			$merge_post_data = get_post( $merge_into, ARRAY_A);
		    if ( ($post_data && get_post_type($ticket_id) == 'nifty_desk_tickets') && ($merge_post_data && get_post_type($merge_into) == 'nifty_desk_tickets') ) {
		    	
		    	/* add the ticket as a response to the ticket we are merging with */
		    	$ticket_content = $post_data['post_content'];
		    	$ticket_author = $post_data['post_author'];
		    	/* check if we allow for HTML or not */
	            $content = nifty_desk_check_for_html($ticket_content);

	            

	            $title = $post_data['post_title'];
	            $data = array(
	                'post_content' => $content,
	                'post_status' => 'publish',
	                'post_title' => $title,
	                'post_type' => 'nifty_desk_responses',
	                'post_author' => $ticket_author,
	                'comment_status' => 'closed',
	                'ping_status' => 'closed'
	            );
	            $post_id = wp_insert_post($data);
	            update_post_meta($post_id, '_response_parent_id', $merge_into);
	            update_post_meta($post_id, '_nifty_desk_merged_from', $ticket_id);

                /* update parent's 'last updated' time */
				update_post_meta( $merge_into, 'ticket_last_updated', current_time('timestamp') );



	            /* let the system know that this ticket has a merged ID, so that we can direct any future responses to the merged ID */
				update_post_meta($ticket_id, '_nifty_desk_merged_with', $merge_into);	            

				/* close the ticket */
				update_post_meta($ticket_id, 'ticket_status', '2');
				echo json_encode(array("message"=>"success"));
				die();

		    } else {
				echo json_encode(array("message"=>"error"));
		    	die();
		    }


		}
		if ($_POST['action'] == 'nifty_desk_merge_get_ticket_details') {

			$ticket_id = intval( sanitize_text_field( $_POST['ticket_id'] ) );
		    $post_data = get_post( $ticket_id, ARRAY_A);
		    if ($post_data && get_post_type($ticket_id) == 'nifty_desk_tickets') {
			    $ticket_content = substr($post_data['post_content'],0,255);
			    $ticket_title = $post_data['post_title'];
			    $check = array(
			    	'ticket_content' => $ticket_content,
			    	'ticket_title' => $ticket_title
		    	);
			} else {
				$check = array(
			    	'ticket_content' => "",
			    	'ticket_title' => __("Ticket not found","nifty_desk")
		    	);
			}
	    	echo json_encode($check);

			die();
		}

		if ($_POST['action'] == 'nifty_desk_delete_channel') {
			$cid = intval(sanitize_text_field($_POST['cid']));

			$nifty_desk_channels = get_option("nifty_desk_channels");

			if (count($nifty_desk_channels) == 1) {
				delete_option("nifty_desk_channels");
			} else {
				unset($nifty_desk_channels[$cid]);
				update_option("nifty_desk_channels",$nifty_desk_channels);
			}
			echo "1";
			die();
		}

		if ($_POST['action'] == 'nifty_desk_delete_schedule') {
			$sid = intval(sanitize_text_field($_POST['sid']));
			$nifty_desk_schedules = get_option("nifty_desk_schedules");
			if (count($nifty_desk_schedules) == 1) {
				delete_option("nifty_desk_schedules");
			} else {
				unset($nifty_desk_schedules[$sid]);
				update_option("nifty_desk_schedules",$nifty_desk_schedules);
			}
			echo "1";
			die();
		}

		if( $_POST['action'] == 'nifty_desk_db_request_tickets_from_control_by_view'){

			

			if (isset($_POST['offset'])) { $offset = intval(sanitize_text_field($_POST['offset'])); } else { $offset = 0; }
			if (isset($_POST['limit'])) { $limit = intval(sanitize_text_field($_POST['limit'])); } else { $limit = 20; }
			if (isset($_POST['view'])) { $view = intval(sanitize_text_field($_POST['view'])); } else { echo "error"; wp_die(); }
			if (isset($_POST['return_counts'])) { $return_counts = intval(sanitize_text_field($_POST['return_counts'])); } else { $return_counts = 0; }

			if (isset($_POST['sortbytype'])) { $sortbytype = sanitize_text_field($_POST['sortbytype']); } else { $sortbytype = false; }
			if (isset($_POST['sortbyvalue'])) { $sortbyvalue = sanitize_text_field($_POST['sortbyvalue']); } else { $sortbyvalue = false; }
			if (isset($_POST['sortby'])) { $sortby = sanitize_text_field($_POST['sortby']); } else { $sortby = false; }


			$tickets = nifty_desk_get_tickets_by_view($view,$offset,$limit,$return_counts,$sortbytype,$sortbyvalue,$sortby);


			echo $tickets;

			

			wp_reset_postdata();

			
	

			wp_die();

		}

		if( $_POST['action'] == 'nifty_desk_db_request_tickets_from_control' ){

			$status = explode(",",sanitize_text_field($_POST['ticket_status']));

			$include_priority = false;

			$orderby = 'title';
			$order = 'DESC';
			

			if (isset($_POST['offset'])) { $offset = intval( sanitize_text_field( $_POST['offset'] ) ); } else { $offset = 0; }
			if (isset($_POST['limit'])) { $limit = intval( sanitize_text_field( $_POST['limit'] ) ); } else { $limit = 20; }


			if (isset($_POST['priority'])) { $priority = intval(sanitize_text_field($_POST['priority'])); } else { $priority = false; }

			$and_relation = array("relation" => "AND");
			$or_relation = array("relation" => "OR");


			$meta_query = array();
			
			if (count($status) > 1) {
				$status_meta_query = array();
				array_push($status_meta_query,$or_relation);

				/* multiple status values requested */
				$cnter = 0;
				foreach ($status as $status_val) {
					$status_meta_query_sub = array(
						'key'     => 'ticket_status',
						'value'   => $status_val,
						'compare' => '=',
					);
					array_push($status_meta_query,$status_meta_query_sub);
				}
				
				
			} else {
				/* single status value requested */
				$status_meta_query = array(
					'key'     => 'ticket_status',
					'value'   => $status[0],
					'compare' => '=',
				);
				
			}
			


			if ($priority > 0) {
				$priority_meta_query = array(
							'key'     => 'ticket_priority',
							'value'   => $priority,
							'compare' => '=',
						);
				$include_priority = true;
				
			}


			if ($priority > 0) {
				array_push($meta_query,$and_relation);
				array_push($meta_query,$status_meta_query);
				array_push($meta_query,$priority_meta_query);

			} else {
				/* one line query, do not use AND or OR */
				array_push($meta_query,$status_meta_query);
			}


			//echo $required_action;

			$ret = "";


			/* limit is set to $limit+1 here because we are actively seeking if there are more posts than what has been asked for, if true, then we can let the JS know that it can keep the "next" button active. */

			$posts_per_page_default = get_option("posts_per_page");

			update_option("posts_per_page",$limit+1);



			$args = array(
				'post_type' => 'nifty_desk_tickets',
				'posts_per_page ' => $limit+1,
				'offset' => 0,
				'orderby' => $orderby,
				'order' => $order,
				'meta_query' => array($meta_query)
			);


			$my_query = new WP_Query( $args );

			$ticket_counter = 0;
			$is_more = false;
			$is_less = false;

			if ($offset > 0) { $is_less = true; } /* if we've offset anything, logically there would be previous items so set is_less to true */

			if ( $my_query->have_posts() ) {
		
				while ( $my_query->have_posts() ) {
					$my_query->the_post();

					$post_status = nifty_desk_return_ticket_status_returns( get_the_ID() );
					$is_public = get_post_meta( get_the_ID(), 'ticket_public', true );
					$response_count = nifty_desk_cnt_responses( get_the_ID() );
					$priority = nifty_desk_return_ticket_priority_returns( get_the_ID() );

					$last_responder = "";
					$data = nifty_desk_get_last_response( get_the_ID() );
		            if (isset($data->post_author)) {
		                $author = $data->post_author;
		                if ($author) {
		                    $author_data = get_userdata($author);
		                    $last_responder .= $author_data->display_name;

		                    $last_responder .= "<br /><small>" . nifty_desk_time_elapsed_string(strtotime($data->post_date)) . "</small>";
		                } else {
		                    $last_responder .= "-";
		                }
		            } else {
		                $last_responder .= "-";
		            }
		            $ticket_counter++;
		            if ($ticket_counter <= $limit) {
						$ret .= "<tr id='nifty_desk_modern_ticket_row_".get_the_ID()."'>";
						$ret .= "<td><input type='checkbox' class='nifty_desk_checkbox' value='".get_the_ID()."' /></td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_title' ticket_id='".get_the_ID()."' >" . get_the_title() . "</td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_author' ticket_id='".get_the_ID()."'>" . get_the_author() . "</td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_date' ticket_id='".get_the_ID()."'>" . get_the_date() . "</td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_priority' ticket_id='".get_the_ID()."'>" . $priority . "</td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_responses' ticket_id='".get_the_ID()."'>" . $response_count . "</td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_responser' ticket_id='".get_the_ID()."'>" . $last_responder . "</td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_status' ticket_id='".get_the_ID()."'>" . $post_status . "</td>";
						$ret .= "</tr>";
					} else {
						$is_more = true;
					}


				}
			} else {
				$ret .= "<tr><td colspan='8' style='padding: 10px 0;'>".__('No tickets found', 'nifty_desk')."</td></tr>";
			}

			echo json_encode(array(
				'ticket_cnt' => $ticket_counter,
				'ticket_html' => $ret,
				'is_more' => $is_more,
				'is_less' => $is_less,
				'orderby' => $orderby,
				'order' => $order,
				'priority' => $priority,
				'ticket_status' => $status
				)
			);
			

			wp_reset_postdata();

			update_option("posts_per_page",$posts_per_page_default);
	

			wp_die();

		}

		if( $_POST['action'] == 'nifty_desk_db_request_tickets_from_control_priority' ){

			$required_action = sanitize_text_field($_POST['required_action']);
			if (isset($_POST['offset'])) { $offset = intval(sanitize_text_field($_POST['offset'])); } else { $offset = 0; }
			if (isset($_POST['limit'])) { $limit = intval(sanitize_text_field($_POST['limit'])); } else { $limit = 20; }

			//echo $required_action;

			$ret = "";

			$args = array(
				'post_type' => 'nifty_desk_tickets',
				'posts_per_page ' => $limit+1,
				'offset' => $offset,
				'meta_query' => array(
					array(
						'key'     => 'ticket_priority',
						'value'   => $required_action,
						'compare' => '=',
					),
				),
			);

			$my_query = new WP_Query( $args );

			$ticket_counter = 0;
			$is_more = false;
			$is_less = false;

			if ($offset > 0) { $is_less = true; } /* if we've offset anything, logically there would be previous items so set is_less to true */


			if ( $my_query->have_posts() ) {
		
				while ( $my_query->have_posts() ) {
					$my_query->the_post();

					$post_status = nifty_desk_return_ticket_status_returns( get_the_ID() );

					$response_count = nifty_desk_cnt_responses( get_the_ID() );

					$priority = nifty_desk_return_ticket_priority_returns( get_the_ID() );

					$last_responder = "";
					$data = nifty_desk_get_last_response( get_the_ID() );
		            if (isset($data->post_author)) {
		                $author = $data->post_author;
		                if ($author) {
		                    $author_data = get_userdata($author);
		                    $last_responder .= $author_data->display_name;

		                    $last_responder .= "<br /><small>" . nifty_desk_time_elapsed_string(strtotime($data->post_date)) . "</small>";
		                } else {
		                    $last_responder .= "-";
		                }
		            } else {
		                $last_responder .= "-";
		            }

		            $ticket_counter++;
		            if ($ticket_counter <= $limit) {
						$ret .= "<tr id='nifty_desk_modern_ticket_row_".get_the_ID()."'>";
						$ret .= "<td><input type='checkbox' class='nifty_desk_checkbox' value='".get_the_ID()."' /></td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_title' ticket_id='".get_the_ID()."' >" . get_the_title() . "</td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_author' ticket_id='".get_the_ID()."'>" . get_the_author() . "</td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_date' ticket_id='".get_the_ID()."'>" . get_the_date() . "</td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_priority' ticket_id='".get_the_ID()."'>" . $priority . "</td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_responses' ticket_id='".get_the_ID()."'>" . $response_count . "</td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_responser' ticket_id='".get_the_ID()."'>" . $last_responder . "</td>";
						$ret .= "<td class='nifty_desk_db_single_ticket ticket_status' ticket_id='".get_the_ID()."'>" . $post_status . "</td>";
						$ret .= "</tr>";

					} else {
						$is_more = true;
					}


				}
			} else {
				$ret .= "<tr><td colspan='8' style='padding: 10px 0;'>".__('No tickets found', 'nifty_desk')."</td></tr>";
			}

			echo json_encode(array(
				'ticket_cnt' => $ticket_counter,
				'ticket_html' => $ret,
				'is_more' => $is_more,
				'is_less' => $is_less
				)
			);
			

			wp_reset_postdata();

			update_option("posts_per_page",$posts_per_page_default);
	

			wp_die();
		}

		if( $_POST['action'] == 'nifty_desk_db_request_ticket_from_content_list' ){
			$debug_start = (float) array_sum(explode(' ',microtime()));

			$ticket_id = sanitize_text_field( $_POST['ticket_id'] );


			$post = get_post( $ticket_id );
	        $end = (float) array_sum(explode(' ',microtime()));
//	        $ret = "processing time: ". sprintf("%.4f", ($end-$debug_start))." seconds\n";


			$ticket_subject = $post->post_title;
			$ticket_content = stripslashes( $post->post_content );			
			
			$ticket_actual_date = $post->post_date;
			$ticket_request_date = nifty_desk_parse_date(strtotime($post->post_date));

			$ticket_requester_id = $post->post_author;

			$ticket_author_data = get_userdata($post->post_author);
			$ticket_author_name = $ticket_author_data->display_name;
			$ticket_author_email = $ticket_author_data->user_email;
			$ticket_author_image = get_avatar( $ticket_author_data->ID, '40' );

			$meta_data = nifty_desk_get_post_meta_all($post->ID);
			
			$ticket_status = get_post_meta($post->ID, 'ticket_status', true);

			$note_data = nifty_desk_get_note_meta_all($post->ID);

			$response_contents = "";
			$note_contents = "";


	        $end = (float) array_sum(explode(' ',microtime()));
//	        $ret .= "processing time: ". sprintf("%.4f", ($end-$debug_start))." seconds\n";


			if( $note_data ){

				krsort( $note_data );

				foreach( $note_data as $meta ){

					$note_data = nifty_desk_get_response_data($meta->post_id);

					$author_data = get_userdata($note_data->post_author);

				    if (isset($author_data->roles[0])) {
		        		$role = $author_data->roles[0];
		    		} else {
		        		if (isset($author_data->roles[1])) { 
		            		$role = $author_data->roles[1]; 
		        		} else { 
		            		$role = ""; 
		        		}
		    		}

		    		$response_image = get_avatar($author_data->user_email, '40');

		    		$response_display_name = $author_data->display_name;
		    		$response_post_date = $note_data->post_date;
		    		$response_post_time = nifty_desk_time_elapsed_string(strtotime($note_data->post_date));
		    		$response_title = $note_data->post_title;
		    		$response_content = $note_data->post_content;

		    		$note_contents .= "<div class='ticket_author_meta_note'>";

					$note_contents .= "	<div class='ticket_author_image ticket_responder_gravatar'>$response_image</div>";

					$note_contents .= "	<div class='ticket_author_details'>";

					$label = "<div class='ticket_response_label'>".__('Internal Note', 'nifty_desk')."</div>";

					$note_contents .= "		<div class='ticket_author'>$response_display_name | <span>$response_post_date</span> $label</div>";

					$note_contents .= " </div>";				

					$note_contents .= "		<div class='ticket_contents ticket_contents_response'>".$response_content."</div>";

					$note_contents .= "	</div>";

				}

			}

			if( $meta_data ){

				//krsort( $meta_data );

				foreach( $meta_data as $meta ){

					$response_data = nifty_desk_get_response_data($meta->post_id);

					$author_data = get_userdata($response_data->post_author);

				    if (isset($author_data->roles[0])) {
		        		$role = $author_data->roles[0];
		    		} else {
		        		if (isset($author_data->roles[1])) { 
		            		$role = $author_data->roles[1]; 
		        		} else { 
		            		$role = ""; 
		        		}
		    		}

		    		$nifty_desk_seen = get_post_meta($meta->post_id, 'nifty_desk_seen', true);

		    		if ($nifty_desk_seen) {
		    			$nifty_desk_seen = "<span class='nifty_desk_seen' title='".date("Y-m-d H:i:s",$nifty_desk_seen)."'>".__("Seen","nifty_desk")." </span>";
		    		} else {
		    			$nifty_desk_seen = '';
		    		}

		    		$response_image = get_avatar($author_data->user_email, '40');

		    		$response_display_name = $author_data->display_name;
		    		$response_post_date = $response_data->post_date;
		    		//$response_post_time = nifty_desk_time_elapsed_string(strtotime($response_data->post_date));
		    		$response_post_time = nifty_desk_parse_date(strtotime($response_data->post_date));
		    		$response_title = $response_data->post_title;
		    		$response_content = nifty_desk_check_for_html($response_data->post_content);

		    		$response_contents .= "<div class='ticket_author_meta_response'>";

					$response_contents .= "	<div class='ticket_author_image ticket_responder_gravatar'>$response_image</div>";

					$response_contents .= "	<div class='ticket_author_details'>";

					if( $ticket_requester_id == $author_data->ID ) {
						$label = "<div class='ticket_author_label'>".__('Ticket Author', 'nifty_desk')."</div>";
					} else {
						$label = "";
					}

					$response_contents .= "		<div class='ticket_author'><span class='author_name'>$response_display_name</span> | <span>$response_post_time</span> $label $nifty_desk_seen ".apply_filters("nifty_desk_response_after_author_name","",$meta->post_id)."</div>";

					$response_contents .= " </div>";				

					$response_contents .= "		<div class='ticket_contents ticket_contents_response'>";

					$response_contents .= nifty_desk_check_for_merge_from($meta->post_id);


					/* check if we had issues notifying this user of their ticket */
					$notification_issue = get_post_meta($meta->post_id,'nifty_desk_notification_issue',true);
					if ($notification_issue) {

						$response_contents .= nifty_desk_build_notification_error_html('response',$meta->post_id);
					}

					$response_contents .= $response_content;


					$ticket_attachments = maybe_unserialize(get_post_custom_values('ticket_attachments', $meta->post_id));

					$upload_dir = wp_upload_dir();
					$udir = $upload_dir['baseurl'].'/nifty-desk-uploads/'.$ticket_id."/";
					if ($ticket_attachments) {
						$response_contents .= "<ul>";
						foreach ($ticket_attachments as $key => $att) {
							$att = maybe_unserialize($att);
							foreach ($att as $att_for_realz) {
							 	$checkpath = $upload_dir['basedir'].'/nifty-desk-uploads/'.$ticket_id."/";
							 	$check_exists = @file_exists($checkpath.$att_for_realz);
							 	if (!$check_exists) { $check_exists_string = "<em style='font-family: monospace; font-size:0.8em; color:#000;'>(".__("File no longer exists","nifty_desk").")</em>"; } else { $check_exists_string = ""; }
							 	$response_contents .= "<li class='nifty_desk_attachment'><a class='' target='_BLANK' href='".$udir.$att_for_realz."'>".$att_for_realz."</a> " . $check_exists_string. "</li>";
							}
						}
						$response_contents .= "</ul>";
					}
					$response_contents .= "</div>";

					$response_contents .= "	</div>";

				}

			}
	        $end = (float) array_sum(explode(' ',microtime()));
//	        $ret .= "processing time: ". sprintf("%.4f", ($end-$debug_start))." seconds\n";


			$agent_id = get_current_user_id();

			$current_agent_data = get_userdata($agent_id);

			$current_agent_name = $current_agent_data->display_name;
			$current_agent_image = get_avatar($current_agent_data->user_email, '40');

			$text_box_response = "";

			$text_box_response .= "<div class='ticket_author_meta_response'>";

			$text_box_response .= "	<div class='ticket_author_image ticket_responder_gravatar'>$current_agent_image</div>";

			$text_box_response .= "	<div class='ticket_author_details'>";

			$text_box_response .= "		<div class='ticket_author'>$current_agent_name";

			$text_box_response = 			apply_filters('nifty_desk_current_agent_meta', $text_box_response, $ticket_id );

			$text_box_response .= "		</div>";

			$text_box_response .= " </div>";

			$text_box_response .= "<div class='ticket_response_fields'>";

			$text_box_response .= "<textarea id='nifty_desk_db_response_textarea_".$ticket_id."' class='nifty_desk_response_textarea' rows='5'></textarea>";			

			$text_box_response .= "<input type='hidden' id='nifty_desk_response_title_".$ticket_id."' value='".__('Reply to ', 'nifty_desk').$ticket_subject."' />";
			$text_box_response .= "<input type='hidden' id='nifty_desk_agent_id_".$ticket_id."' value='$agent_id' />";
			$text_box_response .= "<input type='hidden' id='nifty_desk_parent_id_".$ticket_id."' value='$ticket_id' />";			



			if ($ticket_status != '2' && $ticket_status != '1') { 
				/* only show if the ticket is not closed or solved */
				$text_box_response .= "<div class='nifty_desk_db_before_button' id='nifty_desk_db_before_button_".$ticket_id."'>";
				$text_box_response = apply_filters('nifty_desk_text_response_before', $text_box_response, $ticket_id );
				$text_box_response .= "</div>";
			}

			$text_box_response .= nifty_desk_check_for_followup($ticket_id);

			$text_box_response .= nifty_desk_check_for_merge($ticket_id);


			/* check if we had issues notifying this user of their ticket */
			$notification_issue = get_post_meta($ticket_id,'nifty_desk_notification_issue',true);
			if ($notification_issue) {
				$text_box_response .= nifty_desk_build_notification_error_html('ticket',$ticket_id);
			}

			if ($ticket_status != '2' && $ticket_status != '1') { 
				/* only show if the ticket is not closed or solved */
				$text_box_response .= "<button type='button' class='button submit_ticket_response' tid='".$ticket_id."' id='submit_ticket_response_".$ticket_id."'>".__('Submit Response', 'nifty_desk')."</button>";			
			}
			$text_box_response = apply_filters('nifty_desk_text_response_after', $text_box_response, $ticket_id );

	        $end = (float) array_sum(explode(' ',microtime()));
//	        $ret .= "processing time: ". sprintf("%.4f", ($end-$debug_start))." seconds\n";



			$text_box_response .= "</div>";			

			$ret = "";

			$ret .= "<div class='ticket_container'>";

			$ret .= apply_filters("nifty_desk_filter_warning_control","",$ticket_id);
			
			$ret .= nifty_desk_output_ticket_actions($ticket_id);

			$ret .= "	<div class='ticket_author_meta' id='ticket_author_meta_".$ticket_id."'>";

			$ret .= "		<div class='ticket_author_image ticket_responder_gravatar'>$ticket_author_image</div>";

			$ret .= "			<div class='ticket_author_details'>";


			$ret .= "			<div class='ticket_subject'>$ticket_subject </div>";

			$ret .= "			<div class='ticket_author'><span class='author_name'><span class='author_display_name_".$ticket_id."'>$ticket_author_name</span> (<a class='nifty_author_email_".$ticket_id."' href='mailto:".$ticket_author_email."'>".$ticket_author_email."</a>)</span> | <span title='".$ticket_actual_date."'>".$ticket_request_date."</span> | <div class='ticket_id_label'>".__('Ticket #', 'nifty_desk')."$ticket_id</div> <a href='".admin_url('admin.php?page=support-tickets&tid='.$ticket_id)."' target='_BLANK' class='nifty_desk_view_front_end' title='".$ticket_subject."'>".__("Back end link","nifty_desk")."</a> | <a href='".get_permalink($ticket_id)."' target='_BLANK' class='nifty_desk_view_front_end' title='".$ticket_subject."'>".__("Front end link","nifty_desk")."</a></div>";

			$ret .= "		</div>";

			$ret .= "	</div>";			

			$ret .= 	$text_box_response;

			$ret .= "	<div id='ticket_response_content_holder_".$ticket_id."'></div>";

			$ret .= 	$note_contents;

			$ret .= 	$response_contents;

			$ret .= "	<div class='ticket_author_meta_response'>";

			$ret .= "		<div class='ticket_author_image ticket_responder_gravatar'>$ticket_author_image</div>";

			$ret .= "		<div class='ticket_author_details'>";

			$ret .= "		<div class='ticket_author'><span class='author_name'>$ticket_author_name</span> | <span>$ticket_request_date</span>".apply_filters("nifty_desk_response_after_author_name","",$ticket_id)."</div>";

			$ret .= " 	</div>";

			$ret .= "	<div class='ticket_contents'>";
			


			

			$nifty_desk_tags = nifty_desk_get_allowed_tags();
			//$ret .= wp_kses(utf8_decode($ticket_content),$nifty_desk_tags); 
			$ret .= wp_kses(nifty_desk_normalize(html_entity_decode($ticket_content)),$nifty_desk_tags); 


			$ticket_attachments = maybe_unserialize(get_post_custom_values('ticket_attachments', $ticket_id));

			$upload_dir = wp_upload_dir();
			$udir = $upload_dir['baseurl'].'/nifty-desk-uploads/'.$ticket_id."/";
			if ($ticket_attachments) {
				$ret .= "<ul>";
				foreach ($ticket_attachments as $key => $att) {
					$att = maybe_unserialize($att);
					foreach ($att as $att_for_realz) {
					 	$checkpath = $upload_dir['basedir'].'/nifty-desk-uploads/'.$ticket_id."/";
					 	$check_exists = @file_exists($checkpath.$att_for_realz);
					 	if (!$check_exists) { $check_exists_string = "<em style='font-family: monospace; font-size:0.8em; color:#000;'>(".__("File no longer exists","nifty_desk").")</em>"; } else { $check_exists_string = ""; }
					 	$response_contents .= "<li class='nifty_desk_attachment'><a class='' target='_BLANK' href='".$udir.$att_for_realz."'>".$att_for_realz."</a> " . $check_exists_string. "</li>";
					}
				}
				$ret .= "</ul>";
			}
			$ret .= "	</div>";

			$ret .= "</div>";

			$ticket_meta = "";

			$stored_ticket_status = get_post_custom_values('ticket_status', $ticket_id);

		    $stored_ticket_priority = get_post_custom_values('ticket_priority', $ticket_id);

	        $end = (float) array_sum(explode(' ',microtime()));
//	        $ret .= "processing time: ". sprintf("%.4f", ($end-$debug_start))." seconds\n";


		    $ticket_statuses = array(
		    	'9' => __('New', 'nifty_desk'),
		    	'0' => __('Open', 'nifty_desk'),
		    	'3' => __('Pending', 'nifty_desk'),
		    	'2' => __('Closed', 'nifty_desk'),
		    	'1' => __('Solved', 'nifty_desk')
	    	);

	    	$ticket_priorities = array(
	    		'1' => __('Low', 'nifty_desk'),
	    		'2' => __('High', 'nifty_desk'),
	    		'3' => __('Urgent', 'nifty_desk'),
	    		'4' => __('Critical', 'nifty_desk')
    		);

		    $ticket_author_meta = apply_filters('nifty_desk_author_meta_top', $ticket_author_meta, $ticket_id );

		    $ticket_author_meta .= "<p>";

		    $ticket_author_meta .= "<label>".__('Ticket Status', 'nifty_desk')."</label>";

		    $ticket_author_meta .= "<select id='nifty_desk_ticket_status_".$ticket_id."' tid='".$ticket_id."' class='nifty_desk_ticket_status nifty_desk_ticket_meta_input'>";
		    
		    foreach( $ticket_statuses as $key => $val ){

		    	if( $stored_ticket_status[0] == $key ) { $sel = 'selected'; } else { $sel = ''; }

		    	$ticket_author_meta .= "<option value='$key' $sel >$val</option>";

		    }
		    
		    $ticket_author_meta .= "</select>";

		    $ticket_author_meta .= "</p>";

		    $ticket_author_meta .= "<p>";

		    $ticket_author_meta .= "<label>".__('Ticket Priority', 'nifty_desk')."</label>";

		    $ticket_author_meta .= "<select id='nifty_desk_ticket_priority_".$ticket_id."' tid='".$ticket_id."' class='nifty_desk_ticket_priority nifty_desk_ticket_meta_input'>";
		    
		    foreach( $ticket_priorities as $key => $val ){

		    	if( $stored_ticket_priority[0] == $key ) { $sel = 'selected'; } else { $sel = ''; }

		    	$ticket_author_meta .= "<option value='$key' $sel >$val</option>";

		    }
		    
		    $ticket_author_meta .= "</select>";

		    $ticket_author_meta .= "</p>";

	        $end = (float) array_sum(explode(' ',microtime()));
//	        $ret .= "processing time: ". sprintf("%.4f", ($end-$debug_start))." seconds\n";

		    $ticket_author_meta = apply_filters('nifty_desk_author_meta', $ticket_author_meta, $ticket_id );

	        $end = (float) array_sum(explode(' ',microtime()));
//	        $ret .= "processing time: ". sprintf("%.4f", ($end-$debug_start))." seconds\n";


			echo json_encode( array( 'ticket' => $ret, 'meta' => $ticket_author_meta, 'ticket_title' => $ticket_subject ) );

			wp_die();

		}

		if( $_POST['action'] == 'nifty_desk_db_update_ticket_status' ){
			@ob_start();			
			$post_id = intval(sanitize_text_field( $_POST['ticket_id'] ));
			echo update_post_meta($post_id, 'ticket_status', sanitize_text_field( $_POST['ticket_status'] ) );
			$post_details = get_post($post_id);
			$author_id = $post_details->post_author;
			@ob_flush();
			@flush();
			@ob_end_flush();

			$nifty_desk_settings = get_option("nifty_desk_settings");
            if(isset($nifty_desk_settings['nifty_desk_settings_notify_status_change'])&&$nifty_desk_settings['nifty_desk_settings_notify_status_change'] == 1){
            	nifty_desk_notification_control('status_change', $post_id, $author_id);
            }

			wp_die();

		}

		if( $_POST['action'] == 'nifty_desk_db_update_ticket_priority' ){

			echo update_post_meta( intval( sanitize_text_field( $_POST['ticket_id'] ) ), 'ticket_priority', sanitize_text_field( $_POST['ticket_priority'] ) );

			wp_die();

		}		

		if( $_POST['action'] == 'nifty_desk_submit_response' ){


			$parent_id = intval( sanitize_text_field( $_POST['parent'] ) );

            if (isset($_POST['content'])) { 
            	$content_current = wp_kses( $_POST['content'] );
            } else {
            	$content_current = false; 
            }

            $title = sanitize_text_field($_POST['title']);
            $author = sanitize_text_field($_POST['author']);
            if (!isset($_POST['status'])) {
            	$status = '0';
            } else { 
            	$status = sanitize_text_field($_POST['status']);
            }


            if ($content_current) {

            	/* only add if there is content */
	            $content = nifty_desk_check_for_html($content_current);

	            $data = array(
	                'post_content' => $content,
	                'post_status' => 'publish',
	                'post_title' => $title,
	                'post_type' => 'nifty_desk_responses',
	                'post_author' => $author,
	                'comment_status' => 'closed',
	                'ping_status' => 'closed'
	            );

	            $post_id = wp_insert_post($data);

	            $ticket_channel = get_post_meta($parent_id, 'ticket_channel_id', true );

	            update_post_meta($post_id, '_response_parent_id', $parent_id);
                /* update parent's 'last updated' time */
				update_post_meta( $parent_id, 'ticket_last_updated', current_time('timestamp'));
	            update_post_meta($parent_id, 'ticket_status', $status);
	            if (!empty($_FILES)) {
	            	$file_filter = apply_filters("nifty_desk_filters_pro_files",$_POST,$_FILES);
	            } else {
	            	$file_filter = false;
	            }

	            if (is_array($file_filter)) {

					$upload_dir = wp_upload_dir();
					$udir = $upload_dir['baseurl'].'/nifty-desk-uploads/'.$parent_id."/";
					$upath = $upload_dir['basedir'].'/nifty-desk-uploads/'.$parent_id."/";

	            	$post_attachments = array();
	            	$mail_attachments = array();


	            	foreach ($file_filter as $file) {
	            		$post_attachments[] = $file['filename'];
	            		$mail_attachments[] = $upath.$file['filename'];
	            	}
	            	if (count($post_attachments) > 0) {
                        add_post_meta( $post_id, 'ticket_attachments', $post_attachments, true );
                    }



	            }

	            do_action("nifty_desk_submit_response_before_notify_hook", $parent_id, get_current_user_id());


	            $checker = nifty_desk_notification_control('response', $parent_id, get_current_user_id(),false,false,$content,$ticket_channel,$post_id,$mail_attachments);
	            $post = get_post( $post_id );


				$ticket_request_date = nifty_desk_parse_date(strtotime($post->post_date));

				$ticket_author_data = get_userdata($author);
				$ticket_author_name = $ticket_author_data->display_name;
	            $response_contents = "";
				$response_contents .= "<div class='ticket_author_meta_response'>";
				$response_contents .= "	<div class='ticket_author_image ticket_responder_gravatar'>".get_avatar(get_current_user_id(), '40')."</div>";
				$response_contents .= "	<div class='ticket_author_details'>";
				$response_contents .= "		<div class='ticket_author'><span class='author_name'>$ticket_author_name</span> | <span>$ticket_request_date</span></div>";
				$response_contents .= " </div>";				
				$response_contents .= "		<div class='ticket_contents ticket_contents_response'>";
				$response_contents .= nl2br( stripslashes( $content_current ) );

				$ticket_attachments = maybe_unserialize(get_post_custom_values('ticket_attachments', $post_id));

					$upload_dir = wp_upload_dir();
					$udir = $upload_dir['baseurl'].'/nifty-desk-uploads/'.$parent_id."/";
					if ($ticket_attachments) {
						$response_contents .= "<ul>";
						foreach ($ticket_attachments as $key => $att) {
							$att = maybe_unserialize($att);
							foreach ($att as $att_for_realz) {
							 	$checkpath = $upload_dir['basedir'].'/nifty-desk-uploads/'.$parent_id."/";
							 	$check_exists = @file_exists($checkpath.$att_for_realz);
							 	if (!$check_exists) { $check_exists_string = "<em style='font-family: monospace; font-size:0.8em; color:#000;'>(".__("File no longer exists","nifty_desk").")</em>"; } else { $check_exists_string = ""; }
							 	$response_contents .= "<li class='nifty_desk_attachment'><a class='' target='_BLANK' href='".$udir.$att_for_realz."'>".$att_for_realz."</a> " . $check_exists_string. "</li>";
							}
						}
						$response_contents .= "</ul>";
					}
				$response_contents .= "</div>";
				$response_contents .= "	</div>";

				if (!$checker) {
	            	/* email settings not working or what?! */
	            	echo json_encode( array( 'content' => $response_contents, 'errormsg' => __("There was a problem trying to send the email notification for this response. Please check your WordPress email settings and/or host to ensure that your settings are correct and no email ports are blocked.","nifty_desk") ) );
	            	wp_die();
	            } else {
					echo json_encode( array( 'content' => $response_contents, 'status_string' => $status, 'message' => __('Your ticket has been successfully submitted', 'nifty_desk' ) ) );
	            	wp_die();	
	            }
	        } else {
	        	/* just update the status */
	        	update_post_meta($parent_id, 'ticket_status', $status);
	        	echo json_encode( array( 'content' => '', 'status_string' => $status, 'message' => __('Your ticket has been successfully submitted', 'nifty_desk' ) ) );
	            wp_die();
	        }



            

            


            

		}

		if( $_POST['action'] == 'nifty_desk_modern_submit_internal_note' ){

            $parent_id = intval( sanitize_text_field( $_POST['parent'] ) );
            $content_current = wp_kses( $_POST['content'] );
            $title = sanitize_text_field( $_POST['title'] );
            $author = sanitize_text_field( $_POST['author'] );

            $content = nifty_desk_check_for_html($content_current);

            $data = array(
                'post_content' => $content,
                'post_status' => 'publish',
                'post_title' => $title,
                'post_type' => 'nifty_desk_notes',
                'post_author' => $author,
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            );

            $post_id = wp_insert_post($data);

            update_post_meta($post_id, '_note_parent_id', $parent_id);

            $post = get_post( $post_id );

            $ticket_request_date = nifty_desk_parse_date(strtotime($post->post_date));

            $ticket_author_data = get_userdata($author);
            $ticket_author_name = $ticket_author_data->display_name;

            $note_contents = "";

            $note_contents .= "<div class='ticket_author_meta_note'>";

            $note_contents .= " <div class='ticket_author_image ticket_responder_gravatar'>".get_avatar(get_current_user_id(), '40')."</div>";

            $note_contents .= " <div class='ticket_author_details'>";

            $note_contents .= "     <div class='ticket_author'><span class='author_name'>$ticket_author_name</span> | <span>$ticket_request_date</span></div>";

            $note_contents .= " </div>";                

            $note_contents .= "     <div class='ticket_contents ticket_contents_response'>".$content_current."</div>";

            $note_contents .= " </div>";

            echo json_encode( array( 'content' => $note_contents, 'message' => __('Your note has been successfully saved', 'nifty_desk' ) ) );

            wp_die();

        }

		if( $_POST['action'] == 'nifty_desk_delete_ticket' ){

			$ticket_id = intval( sanitize_text_field( $_POST['ticket_id'] ) );
			if ($ticket_id) {
				

				$failed = nifty_desk_delete_ticket($ticket_id);
				if( $failed == FALSE ){
					echo 0;				
				} else {
					echo 1;
				}
			}
			wp_die();

		}

		if( $_POST['action'] == 'nifty_desk_db_bulk_delete_tickets' ){

			if( isset( $_POST['ticket_ids'] ) ){

				$failed_count = 0;

				$ticket_ids = json_decode(stripslashes($_POST['ticket_ids']));

				foreach( $ticket_ids as $ticket ){

					$ticket = intval( $ticket );

					$failed = nifty_desk_delete_ticket($ticket);
					//$failed = wp_delete_post( $ticket, true);

					if( $failed == FALSE ){
						$failed_count++;			
					} 

				}

				if( $failed_count > 0 ){
					echo 0;
				} else {
					echo __('The selected tickets have been successfully deleted', 'nifty_desk');
				}

			}

			wp_die();

		}

		if( $_POST['action'] == 'nifty_desk_db_search_ticets' ) {

			$query = sanitize_text_field($_POST['search']);
			if (isset($_POST['offset'])) { $offset = intval( sanitize_text_field( $_POST['offset'] ) ); } else { $offset = 0; }
			if (isset($_POST['limit'])) { $limit = intval( sanitize_text_field( $_POST['limit'] ) ); } else { $limit = 2; }

			if (isset($_POST['sortbytype'])) { $sortbytype = sanitize_text_field( $_POST['sortbytype'] ); } else { $sortbytype = false; }
			if (isset($_POST['sortbyvalue'])) { $sortbyvalue = sanitize_text_field( $_POST['sortbyvalue'] ); } else { $sortbyvalue = false; }
			if (isset($_POST['sortby'])) { $sortby = sanitize_text_field( $_POST['sortby'] ); } else { $sortby = false; }


			/* SEARCH FOR A TICKET NUMBER FIRST */
			global $wpdb;

			$search_query = "SELECT * FROM $wpdb->posts WHERE ID = '$query' AND post_type = 'nifty_desk_tickets' AND post_status = 'publish' ";

			if (strpos($query, "@") !== false) {				
		      	$split = explode("@", $query);		      	
		        if (strpos($split['1'], ".") !== false) { 		        	
		        	$user_data = get_user_by( "email", sanitize_email( $query ) );		        			        	
	         		$search_query = "SELECT * FROM $wpdb->posts WHERE post_author = '".$user_data->ID."' AND post_type = 'nifty_desk_tickets' AND post_status = 'publish' ";	         		
	         	}
		   	}
		   	   
			$search_id = $wpdb->get_results( $search_query );						
			$ret = "";

			if( $search_id ){

				/**
				 * Do something with the response
				 */
				$query_count = count($search_id);

				foreach( $search_id as $search ){

					$ticket_id = $search->ID;


					$post_meta = get_post_meta($ticket_id);

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

					
					$nifty_desk_status = get_post_custom_values('ticket_status', intval( $search->ID ), true);
					if ($nifty_desk_status) {
						$post_status = nifty_desk_return_ticket_status_html_block( $nifty_desk_status[0] );
					} else {
						$post_status = '';
					}
					//$post_status = nifty_desk_return_ticket_status_returns( $search->ID );

					$response_count = nifty_desk_cnt_responses( $search->ID );

					$priority = nifty_desk_return_ticket_priority_returns( $search->ID );

					$last_responder = "";
					$data = nifty_desk_get_last_response( intval( $search->ID ) );

		            if (isset($search->post_author)) {
		                $author = $search->post_author;
		                if ($author) {
		                    $author_data = get_userdata( intval( $author ) );

		                    $author_name = $author_data->display_name;
		                    $author_email = $author_data->user_email;

		                    $last_responder .= $author_data->display_name;

		                    $last_responder .= "<br /><small>" . nifty_desk_time_elapsed_string(strtotime($search->post_date)) . "</small>";
		                } else {
		                    $last_responder .= "-";
		                }
		            } else {	
		            	// $user_data = get_user_by( 'id', $search->post_at)	            	
		                $last_responder .= "-";
		            }
					$ret .= "<tr id='nifty_desk_modern_ticket_row_".$search->ID."' class='nifty_desk_modern_ticket_row'>";
					$ret .= "<td><input type='checkbox' class='nifty_desk_checkbox' value='".$search->ID."' /></td>";
					$ret .= "<td class='nifty_desk_db_single_ticket ticket_status' ticket_id='".$ticket_id."'>" . $post_status . " ".apply_filters("nifty_desk_return_seen_tag_in_list","",$ticket_id)."</td>";
					$ret .= "<td class='nifty_desk_db_single_ticket ticket_id' ticket_id='".$ticket_id."'>#" . $ticket_id . "</td>";
					if( function_exists( 'nifty_desk_filter_control_sa_ticket_view_list_column_body' ) ){						
						$ret .= nifty_desk_filter_control_sa_ticket_view_list_column_body( "", $ticket_id, $post_meta);
					} else {
						$ret .= "<td class='nifty_desk_db_single_ticket'>&nbsp;</td>";
					}					
					$ret .= "<td class='nifty_desk_db_single_ticket ticket_title' ticket_id='".$search->ID."' >" . $search->post_title . "</td>";
					$ret .= "<td class='nifty_desk_db_single_ticket ticket_author' ticket_id='".$search->ID."' title='".$author_email."'>" . $author_name . "</td>";
					$ret .= "<td class='nifty_desk_db_single_ticket ticket_date' ticket_id='".$search->ID."'>" . date('M d, Y', strtotime($search->post_date) ) . "</td>";
					$ret .= "<td class='nifty_desk_db_single_ticket ticket_priority' ticket_id='".$search->ID."'>" . $priority . "</td>";
					$ret .= "<td class='nifty_desk_db_single_ticket ticket_responses' ticket_id='".$search->ID."'>" . $response_count . "</td>";
					$ret .= "<td class='nifty_desk_db_single_ticket ticket_responser' ticket_id='".$search->ID."'>" . $last_responder . "</td>";
					$ret .= "<td class='nifty_desk_db_single_ticket ticket_owner' ticket_id='".$ticket_id."'>" . $author_name . "</td>";
					$ret .= "<td class='nifty_desk_db_single_ticket ticket_channel' ticket_id='".$ticket_id."'>" . nifty_desk_return_ticket_channel_html_block($channel_name,$ticket_channel_id,$channel_name_full) . "</td>";
					$ret .= "</tr>";	

					$js = json_encode(array(
						'ticket_cnt' => 0,
						'ticket_html' => $ret,
						'is_more' => false,
						'is_less' => false,
						'orderby' => $sortbyvalue,
						'order' => $sortby,
						'cnt' => 1,
						'limit' => $limit,
						'offset' => $offset
						)
					);

					echo $js;

					wp_die();



				}
			}
			/** THE SEARCH STRING WASNT A TICKET NUMBER SO LETS RUN THROUGH THE NORMAL PROCESS */

			$tickets = nifty_desk_get_tickets_by_view($view,$offset,$limit,0,$sortbytype,$sortbyvalue,$sortby,$query);


			echo $tickets;

			die();

			
		}

	}

}

