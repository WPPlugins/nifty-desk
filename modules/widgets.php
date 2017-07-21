<?php
/*Handles Time Tracking Widget Creation*/

add_filter( 'nifty_desk_author_meta_top', 'nifty_desk_originator_control', 1, 2 );
function nifty_desk_originator_control( $string, $ticket_id){
	$current_originator = get_post_field ('post_author', $ticket_id);
	$user_info = get_userdata($current_originator);
	$user_email = $user_info->user_email;
	$user_name = $user_info->display_name;

	$ret .= "<p class='originator_section originator_section_".$ticket_id."'>
				<label>".__("Originator", "nifty_desk").":</label><br>	
				<span class='ticket_originator ticket_originator_".$ticket_id."' tid='".$ticket_id."' title='".$user_email."' alt='".$user_email."'>
				".$user_name."
				</span>
				<small><em><a href='javascript:void(0);' class='nifty_blue originator_change originator_change_".$ticket_id."' tid='".$ticket_id."'>".__("change","nifty_desk")."</a></small></em>
			</p>
		";
	$ret .= "<hr>";

	return $string.$ret;

}

add_action('admin_print_scripts', 'nifty_desk_admin_scripts_originator');
function nifty_desk_admin_scripts_originator(){
	wp_register_script('nifty-desk-originator-js', plugins_url('/js/', dirname(__FILE__)).'widgets.js', array('jquery'), '', true);
    wp_enqueue_script('nifty-desk-originator-js');
}

add_action("wp_ajax_nifty_desk_change_originator", "nifty_desk_originator_ajax");
function nifty_desk_originator_ajax(){
	if( isset( $_POST['action'] ) ){
		if ($_POST['action'] == 'nifty_desk_change_originator') {
		 	if(isset($_POST['ticket_id'])) {
		 		$ticket_id = intval($_POST['ticket_id']);
		 		$originator_email = sanitize_email($_POST['originator_email']);
		 		$originator_email = str_replace("+","",$originator_email);

				/* check if we have this user is the db, if not, create an account for them */
                if( email_exists( $originator_email )) {

                      $post_user_data = get_user_by('email',$originator_email);
                      $post_user = $post_user_data->ID;
                      $username = $post_user_data->data->display_name;
                      
                } else {
                    /* create the user */
                    if (!$from_name) {
                        $username = $originator_email;
                    } else {
                        $username = $from_name.rand(0,9).rand(0,9).rand(0,9);
                    }
                    $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
                    $post_user = wp_create_user( $originator_email, $random_password, $originator_email );
					wp_update_user( array( 'ID' => $post_user, 'display_name' => $from_name ) );

                    $new_user = true;

                    $new_user_username = $originator_email;
                    $new_user_password = $random_password;
					
                    
                }



				$my_post = array(
				'ID'           => $ticket_id,
				'post_author'   => $post_user

				);

				// Update the post into the database
			  	wp_update_post( $my_post );


	 			//add_post_meta(intval($ticket_id), 'ticket_time', $seconds_to_add);
	 			echo json_encode(array('user_email' => $originator_email, 'user_name' => $username));
	 			wp_die();
		 		
		 		
		 	}
		}
	}
}