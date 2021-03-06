<?php
/* Handles all functions related to the Nifty Desk API */

/*
 * Creates a new ticket within the Nifty Desk
 * Required GET/POST variables:
 * - Token 
 * - Email
 * - Subject
 * - Message
 * Optional GET Variables:
 * - Name
*/
function nifty_desk_api_create_ticket(WP_REST_Request $request){
	$return_array = array();
	if(isset($request)){
		if(isset($request['token'])){
			$check_token = get_option('nifty_desk_api_secret_token');
			if($check_token !== false && $request['token'] === $check_token){
				if(isset($request['email'])){
					if(isset($request['subject'])){
						if(isset($request['message'])){
							//All the required data is here, lets grab everything now
							$email = htmlentities($request['email']);
							$subject = htmlentities($request['subject']);
							$message = htmlentities($request['message']);
							//Now the optionals
							$name = htmlentities($request['name']);

							/*Now let's process the code*/
							if(email_exists($email)) {
                                //echo "USER EXISTS";
                                $wp_nd_user_data = get_user_by('email',$email);
                                $wp_nd_user = $wp_nd_user_data->ID;
                            } else {
                                /* create the user */
                                //echo "CREATING USER";
                                if (!$name) {
                                    $username = $email;
                                } else {
                                    $username = $name.rand(0,9).rand(0,9).rand(0,9);
                                }
                                $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
                                $post_user = wp_create_user( $username, $random_password, $email );

                                $wp_nd_user_data = get_user_by('email',$email);
                                $wp_nd_user = $wp_nd_user_data->ID;
                                
                                if(function_exists('send_automated_emails')) {
                                    //send_automated_emails($email,__('Your support desk log in details','nifty_desk'), __("Login URL:","nifty_desk"). " ". wp_login_url(). " <br/><br/> ".__("Username:","nifty_desk"). " ".$username. " <br/><br/> ".__("Password:","nifty_desk")." ".$random_password,$channel['id'],$post_id);    
                                }            
                            }

							$message = nifty_desk_check_for_html($message);

					        $nifty_desk_settings = get_option("nifty_desk_settings");

					        $data = array(
					            'post_content' => $message,
					            'post_status' => 'publish',
					            'post_title' => esc_attr($subject),
					            'post_type' => 'nifty_desk_tickets',
					            'post_author' => $wp_nd_user,
					            'comment_status' => 'closed',
					            'ping_status' => 'closed'
					        );
					        $post_id = wp_insert_post($data);

					        $custom_fields = get_post_custom($post_id);
					        if (!isset($custom_fields['ticket_status'])) {
					            add_post_meta($post_id, 'ticket_status', '9', true);
					        } else {
					        	add_post_meta($post_id, 'ticket_status', '', true);
					        }
					        if (!isset($custom_fields['ticket_public'])) {
					            add_post_meta($post_id, 'ticket_public', '0', true);
					        } else {
					        	add_post_meta($post_id, 'ticket_public', '', true);
					        }
 

					        if (isset($_POST['nifty_desk_submit_priority'])) {
					            add_post_meta($post_id, 'ticket_priority', sanitize_text_field( $_POST['nifty_desk_submit_priority'] ), true);
					        } else {
					            /* get default */
					            $nd_default_priority = $nifty_desk_settings['nifty_desk_settings_default_priority'];
					            if (!$nd_default_priority) {
					                $nd_default_priority = 0;
					            }
					            add_post_meta($post_id, 'ticket_priority', $nd_default_priority, true);
					        } 

                            $assigned_to = apply_filters("nifty_desk_assigned_to_new_ticket",false);
                            if ($assigned_to) {
                                update_post_meta( $post_id, 'ticket_assigned_to', $assigned_to);
                            }
					        
		                    update_post_meta( $post_id, 'ticket_last_updated', current_time('timestamp'));

				        	add_post_meta($post_id, 'ticket_origin', 'api', true);

		                    $darray = array(
		                    	"parent_id" => $parent_id,
		                    	"post_id" => $post_id,
		                    	"current_user" => get_current_user_id(),
		                    	"request" => $request
	                    	);
		                    do_action("nifty_desk_hook_create_ticket_api",$darray);

					        add_post_meta($post_id, 'ticket_reference', md5($post_id . get_current_user_id()), true);
					        nifty_desk_notification_control('ticket', $post_id, get_current_user_id());

					        $return_array['response'] = "Ticket created successfully";
							$return_array['code'] = "200";
							$return_array['code_def'] = "Success";
							$return_array['data'] = array("name" => $username,
														 	"email" => $email,
															"subject" => $subject,
															"message" => $message);

							$return_array = apply_filters("nifty_desk_api_create_ticket_hook", $return_array, $request);

						} else {
							$return_array['response'] = "No 'message' provided";
							$return_array['code'] = "400";
							$return_array['code_def'] = "Bad Request";
						}
					} else {
						$return_array['response'] = "No 'subject' provided";
						$return_array['code'] = "400";
						$return_array['code_def'] = "Bad Request";
					}
				} else {
					$return_array['response'] = "No 'email' provided";
					$return_array['code'] = "400";
					$return_array['code_def'] = "Bad Request";
				}
			} else {
				$return_array['response'] = "Secret token is invalid";
				$return_array['code'] = "401";
				$return_array['code_def'] = "Unauthorized";
			}
		}else{
			$return_array['response'] = "No secret 'token' found";
			$return_array['code'] = "401";
			$return_array['code_def'] = "Unauthorized";
			$return_array['requirements'] = array("token" => "YOUR_SECRET_TOKEN",
											  "email" => "Recepient Email",
											  "subject" => "Message Subject",
											  "message" => "Initial Message");
		}
	}else{
		$return_array['response'] = "No request data found";
		$return_array['code'] = "400";
		$return_array['code_def'] = "Bad Request";
		$return_array['requirements'] = array("token" => "YOUR_SECRET_TOKEN",
											  "email" => "Recepient Email",
											  "subject" => "Message Subject",
											  "message" => "Initial Message");
	}

	return $return_array;
}

/*
 * View a ticket within the Nifty Desk Support Desk
 * Required GET/POST variables:
 * - Token 
 * - Ticket ID
*/
function nifty_desk_api_view_ticket(WP_REST_Request $request){
	$return_array = array();
	if(isset($request)){
		if(isset($request['token'])){
			$check_token = get_option('nifty_desk_api_secret_token');
			if($check_token !== false && $request['token'] === $check_token){
				if(isset($request['ticket_id'])){
					$ticket_id = htmlentities($request['ticket_id']);
					$post = get_post($ticket_id);
					if($post !== false){
						//Primary data
						$nd_tags = nifty_desk_get_allowed_tags();
						$ticket_subject = $post->post_title;
						$ticket_content = wp_kses(utf8_decode($post->post_content),$nd_tags);			
						$ticket_request_date = nifty_desk_parse_date(strtotime($post->post_date));
						$ticket_requester_id = $post->post_author;

						$ticket_meta_data = get_post_meta($ticket_id);
						$ticket_status = $ticket_meta_data['ticket_status'][0];
				    	if (isset($ticket_meta_data['ticket_priority'][0])) { $ticket_priority =  $ticket_meta_data['ticket_priority'][0]; } else { $ticket_priority = NULL; }

						//Author data
						$ticket_author_data = get_userdata($post->post_author);
						$ticket_author_name = $ticket_author_data->display_name;
						$ticket_author_email = $ticket_author_data->user_email;
						$ticket_author_image = get_avatar( $ticket_author_data->ID, '40' );

						//Additional Data - TO be processed
						$meta_data = nifty_desk_get_post_meta_all($post->ID);
						$note_data = nifty_desk_get_note_meta_all($post->ID);


						//Work our way through notes
						$note_array = array();
						if(isset($note_data) && is_array($note_data)){
							krsort($note_data);
							foreach ($note_data as $key => $note) {
								//Create a new array for output
								$note_array[$key] = array();

								//Get this notes data
								$note_data = nifty_desk_get_response_data($note->post_id);
								$author_data = get_userdata($note_data->post_author);
								//Check user role
								if (isset($author_data->roles[0])) {
					        		$role = $author_data->roles[0];
					    		} else {
					        		if (isset($author_data->roles[1])) { 
					            		$role = $author_data->roles[1]; 
					        		} else { 
					            		$role = ""; 
					        		}
					    		}

					    		$note_array[$key]["user_name"] = $author_data->display_name;
					    		$note_array[$key]["message"] = wp_kses(utf8_decode($note_data->post_content), $nd_tags);
					    		
					    		$note_array[$key]["post_date"] = $note_data->post_date;
					    		$note_array[$key]["post_time"] = nifty_desk_time_elapsed_string(strtotime($note_data->post_date));

					    		$note_array[$key]["user_avatar"] = get_avatar($author_data->user_email, '40');
					    		$note_array[$key]["user_role"] = $role;
							}
						
						}

						//Work through the meta?
						$responses_array = array();
						if(isset($meta_data) && is_array($meta_data)){
							foreach ($meta_data as $key => $response) {
								//Get base data
								$response_data = nifty_desk_get_response_data($response->post_id);
								if($response_data !== false){
									//Creat a new array for output
									$responses_array[$key] = array();
									$author_data = get_userdata($response_data->post_author);
									
									//Check user role
									if (isset($author_data->roles[0])) {
						        		$role = $author_data->roles[0];
						    		} else {
						        		if (isset($author_data->roles[1])) { 
						            		$role = $author_data->roles[1]; 
						        		} else { 
						            		$role = ""; 
						        		}
						    		}

						    		$responses_array[$key]["user_name"] = $author_data->display_name;
						    		$responses_array[$key]["message"] = wp_kses(utf8_decode($response_data->post_content),$nd_tags);

						    		$responses_array[$key]["post_date"] = $response_data->post_date;
						    		$responses_array[$key]["post_time"] = nifty_desk_time_elapsed_string(strtotime($response_data->post_date));

						    		$responses_array[$key]["user_avatar"] = get_avatar($author_data->user_email, '40');
						    		$responses_array[$key]["user_role"] = $role;

						    		$responses_array[$key]["is_author"] = $ticket_requester_id == $author_data->ID ? true : false;
								}
							}
						}

						//Now on to attachements
						$attachement_array = array();

						$ticket_attachments = maybe_unserialize($ticket_meta_data['ticket_attachments'][0]);
						if(isset($ticket_attachments) && is_array($ticket_attachments)){
							$upload_dir = wp_upload_dir();
							$udir = $upload_dir['baseurl'].'/nifty-desk-uploads/'.$ticket_id."/";

							foreach ($ticket_attachments as $key => $att) {
								$att = maybe_unserialize($att);
								if (is_array($att)) { 
									foreach ($att as $att_for_realz) {
										$attachement_array[$key] = array();
										$attachement_array[$key]['filename'] = $udir.$att_for_realz;
										$attachement_array[$key]['url'] = $att_for_realz;
									}
								}
							}
						}

						//Now let's prep our output array

						$return_array['response'] = "Ticket data retrieved";
						$return_array['code'] = "200";
						$return_array['code_def'] = "Success";

						//Create data array 
						$return_array['data'] = array();
						$return_array['data']['ticket_id'] = $ticket_id;
						$return_array['data']['subject'] = $ticket_subject;
						$return_array['data']['message'] = $ticket_content;
						$return_array['data']['status'] = $ticket_status;


						//Create author array
						$return_array['data']['author'] = array();
						$return_array['data']['author']['user_name'] = $ticket_author_name;
						$return_array['data']['author']['email'] = $ticket_author_email;
						$return_array['data']['author']['user_avatar'] = $ticket_author_image;

						//Now do notes, responses, and attachements
						$return_array['data']['notes'] = $note_array;
						$return_array['data']['responses'] = $responses_array;
						$return_array['data']['attachements'] = $attachement_array;

						$return_array = apply_filters("nifty_desk_api_view_ticket_hook", $return_array, $request);

					} else {
						$return_array['response'] = "No ticket with id '".$request['ticket_id']."' found";
						$return_array['code'] = "404";
						$return_array['code_def'] = "Content Not Found";
					}	
				} else {
					$return_array['response'] = "No 'ticket_id' provided";
					$return_array['code'] = "400";
					$return_array['code_def'] = "Bad Request";
				}
			} else {
				$return_array['response'] = "Secret token is invalid";
				$return_array['code'] = "401";
				$return_array['code_def'] = "Unauthorized";
			}
		}else{
			$return_array['response'] = "No secret 'token' found";
			$return_array['code'] = "401";
			$return_array['code_def'] = "Unauthorized";
			$return_array['requirements'] = array("token" => "YOUR_SECRET_TOKEN",
											  "ticket_id" => "Ticket ID");
		}
	}else{
		$return_array['response'] = "No request data found";
		$return_array['code'] = "400";
		$return_array['code_def'] = "Bad Request";
		$return_array['requirements'] = array("token" => "YOUR_SECRET_TOKEN",
											  "ticket_id" => "Ticket ID");
	}

	return $return_array;
}

/*
 * Delete a ticket within the Nifty Desk Support Desk
 * Required GET/POST variables:
 * - Token 
 * - Ticket ID
*/
function nifty_desk_api_delete_ticket(WP_REST_Request $request){
	$return_array = array();
	if(isset($request)){
		if(isset($request['token'])){
			$check_token = get_option('nifty_desk_api_secret_token');
			if($check_token !== false && $request['token'] === $check_token){
				if(isset($request['ticket_id'])){
					$ticket_id = htmlentities($request['ticket_id']);
					$post = get_post($ticket_id);
					if($post !== false){
						$success = nifty_desk_delete_ticket(intval($ticket_id));
						if( $success == FALSE ){
							$return_array['response'] = "No ticket with id '".$request['ticket_id']."' could not be deleted";
							$return_array['code'] = "404";
							$return_array['code_def'] = "Content Not Found";
						} else {
							$return_array['response'] = "Ticket has been deleted";
							$return_array['code'] = "200";
							$return_array['code_def'] = "Success";
							$return_array = apply_filters("nifty_desk_api_delete_ticket_hook", $return_array, $request);
						}
					} else {
						$return_array['response'] = "No ticket with id '".$request['ticket_id']."' found";
						$return_array['code'] = "404";
						$return_array['code_def'] = "Content Not Found";
					}	
				} else {
					$return_array['response'] = "No 'ticket_id' provided";
					$return_array['code'] = "400";
					$return_array['code_def'] = "Bad Request";
				}
			} else {
				$return_array['response'] = "Secret token is invalid";
				$return_array['code'] = "401";
				$return_array['code_def'] = "Unauthorized";
			}
		}else{
			$return_array['response'] = "No secret 'token' found";
			$return_array['code'] = "401";
			$return_array['code_def'] = "Unauthorized";
			$return_array['requirements'] = array("token" => "YOUR_SECRET_TOKEN",
											  "ticket_id" => "Ticket ID");
		}
	}else{
		$return_array['response'] = "No request data found";
		$return_array['code'] = "400";
		$return_array['code_def'] = "Bad Request";
		$return_array['requirements'] = array("token" => "YOUR_SECRET_TOKEN",
											  "ticket_id" => "Ticket ID");
	}

	return $return_array;
}

//Action for the peeps neh
do_action("nifty_desk_api_function_hook");
