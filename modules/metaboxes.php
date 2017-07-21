<?php

function nifty_desk_add_reply_meta_box() {

	$screens = array( 'nifty_desk_responses' );

	foreach ( $screens as $screen ) {

		add_meta_box(
			'nifty_desk_sectionid',
			__( 'Response Information', 'nifty_desk' ),
			'nifty_desk_reply_meta_box_callback',
			$screen,
                        'side',
                        'high'
		);
	}
}
add_action( 'add_meta_boxes', 'nifty_desk_add_reply_meta_box' );

/**
 * Prints the box content.
 *
 * @param WP_Post $post The object for the current post/page.
 */
function nifty_desk_reply_meta_box_callback( $post ) {

	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'nifty_desk_add_reply_meta_box', 'nifty_desk_meta_box_nonce' );

	/*
	 * Use get_post_meta() to retrieve an existing value
	 * from the database and use the value for the form.
	 */
	$value = get_post_meta( $post->ID, '_response_parent_id', true );

	echo '<label for="nifty_desk_new_field">';
	_e( 'Parent ID', 'nifty_desk' );
	echo '</label> ';
	echo '<input type="text" id="nifty_desk_new_field" name="nifty_desk_new_field" value="' . esc_attr( $value ) . '" size="25" />';
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function nifty_desk_reply_save_meta_box_data( $post_id ) {
	/*
	 * We need to verify this came from our screen and with proper authorization,
	 * because the save_post action can be triggered at other times.
	 */

    
    

	// Check if our nonce is set.
	if ( ! isset( $_POST['nifty_desk_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['nifty_desk_meta_box_nonce'], 'nifty_desk_add_reply_meta_box' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	/* OK, it's safe for us to save the data now. */

	// Make sure that it is set.
	if ( ! isset( $_POST['nifty_desk_new_field'] ) ) {
		return;
	}

	// Sanitize user input.
	$my_data = sanitize_text_field( $_POST['nifty_desk_new_field'] );

	// Update the meta field in the database.
	update_post_meta( $post_id, '_response_parent_id', $my_data );

        /* add custom fields if neccessary */
        $custom_fields = get_post_custom($post_id);
        if (!isset($custom_fields['ticket_status'])) {
            add_post_meta( $post_id, 'ticket_status', '0', true );
        }
        if (!isset($custom_fields['ticket_assigned_to'])) {
            add_post_meta( $post_id, 'ticket_assigned_to', '0', true );  /* 0 is default administrator */
        }



}
add_action( 'save_post', 'nifty_desk_reply_save_meta_box_data' );







function nifty_desk_ticket_meta_box() {

	$screens = array( 'nifty_desk_tickets' );

	foreach ( $screens as $screen ) {

		add_meta_box(
			'nifty_desk_tickets_sectionid',
			__( 'Responses', 'nifty_desk' ),
			'nifty_desk_view_responses_meta_box_callback',
			$screen,
                        'normal',
                        'high'
		);
	}
}
add_action( 'add_meta_boxes', 'nifty_desk_ticket_meta_box' );




/**
 * Prints the box content.
 *
 * @param WP_Post $post The object for the current post/page.
 */
function nifty_desk_view_responses_meta_box_callback( $post ) {

	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'nifty_desk_view_responses_meta_box_callback', 'nifty_desk_ticket_meta_box_nonce' );



        $custom_fields = get_post_custom($post->ID);
        if (!isset($custom_fields['ticket_status'])) {
            add_post_meta( $post->ID, 'ticket_status', '0', true );
        }
        if (!isset($custom_fields['ticket_public'])) {
            add_post_meta( $post->ID, 'ticket_public', '0', true );
        }
        if (!isset($custom_fields['ticket_assigned_to'])) {
            if (!get_option("nifty_desk_default_assigned_to")) {
                $super_admins = get_super_admins();
                $user = get_user_by( 'slug', $super_admins[0] );
                if(is_object($user))
                {
                    add_option('nifty_desk_default_assigned_to',$user->ID);
                }

            }
            $default_user = get_option("nifty_desk_default_assigned_to");
            add_post_meta( $post->ID, 'ticket_assigned_to', $default_user, true );
        }






	$meta_data = nifty_desk_get_post_meta_all($post->ID);

        $nifty_desk_ajax_nonce = wp_create_nonce("nifty_desk");
        $value = get_post_custom_values( 'ticket_status', $post->ID );


        if ($value[0] == "9") {
            echo __("This support ticket is pending approval. Please change the status to 'Open' before responding to this ticket.","nifty_desk");
        } else {

            ?>
            <script language="javascript">
                var nifty_desk_nonce = '<?php echo $nifty_desk_ajax_nonce; ?>';
            </script>
            <h2 style="display:block; font-weight:bold; border-bottom:2px solid #2ea2cc; color:#2ea2cc; padding:5px; padding-left:0px;"><?php _e('Add a Response','nifty_desk'); ?></h2>
            <div class="nifty_desk_response_div">
                <form name="nifty_desk_add_response" method="POST" action="" enctype="multipart/form-data">

                    <input type="hidden" value="<?php echo $post->ID; ?>" name="nifty_desk_response_id" id="nifty_desk_response_id" />
                    <input type="hidden" value="<?php echo get_current_user_id(); ?>" name="nifty_desk_response_author" id="nifty_desk_response_author" />
                    <table width='100%'>
                    <tr>
                       <td width="10%" valign="top">
                           <?php _e("Title","nifty_desk"); ?>
                       </td>
                       <td>
                          <input style="width:50%; min-width:200px; margin-bottom:5px; font-weight:bold;" type="text" value="Reply to <?php echo $post->post_title; ?>" name="nifty_desk_response_title" id="nifty_desk_response_title" />

                       </td>
                    </tr>
                    <tr>
                        <td valign="top">
                            <?php _e("Response","nifty_desk"); ?>
                        </td>
                        <td><textarea style="width:100%; height:120px;" name="nifty_desk_response_text" id="nifty_desk_response_text"></textarea></td>
                    </tr>






                    <?php if (function_exists("nifty_desk_pro_metabox_addin_macros")) { echo nifty_desk_pro_metabox_addin_macros(); } ?>

                    <?php
                        $nifty_desk_settings = get_option("nifty_desk_settings");
                        if(isset($nifty_desk_settings['enable_file_uploads'])&&$nifty_desk_settings['enable_file_uploads']===1&&function_exists('nifty_desk_pro_activate'))
                        {
                            $out='
                            <tr>
                                <td colspan="2">
                                    <div style="display:none;" id="response_file_upload_field_container">
                                        <br/><br/>
                                        '.__("Allowed file formats: JPEG, PNG, GIF, TIFF, PDF, ZIP","nifty_desk").' : <br/>
                                        <br/>
                                        <input type="file" name="fl_upload_ticket_file_admin_section" id="fl_upload_ticket_file_admin_section"/>
                                    </div>
                                </td>
                            </tr>';

                            echo $out;
                        }
                    ?>


                    <tr>
                        <td>
                        </td>
                       <td align="right">
                            <a href="javascript:void(0);" title="<?php _e("Send","nifty_desk"); ?>" class="button-primary nifty_desk_send_response_btn" /><?php _e("Send Response","nifty_desk"); ?></a>
                       </td>
                    </tr>
                    </table>
                </form>


            </div>




            <?php
            echo '<hr />';
            foreach ($meta_data as $response) {
                echo nifty_desk_draw_response_box($response->post_id);

            }
        }
}

function nifty_desk_notes_meta_box() {

	$screens = array( 'nifty_desk_tickets' );

	foreach ( $screens as $screen ) {

		add_meta_box(
			'nifty_desk_notes',
			__( 'Notes', 'nifty_desk' ),
			'nifty_desk_view_internal_notes_callback',
			$screen,
                        'normal',
                        'high'
		);
	}
}
add_action( 'add_meta_boxes', 'nifty_desk_notes_meta_box' );

function nifty_desk_view_internal_notes_callback($post){

    $nifty_desk_ajax_nonce = wp_create_nonce("nifty_desk");
?>

<script language="javascript">
    var nifty_desk_nonce = '<?php echo $nifty_desk_ajax_nonce; ?>';
</script>
<h2 style="display:block; font-weight:bold; border-bottom:2px solid #2ea2cc; color:#2ea2cc; padding:5px; padding-left:0px;"><?php _e('Add a Note','nifty_desk'); ?></h2>
            <div class="nifty_desk_note_div">
                <form name="nifty_desk_add_note" method="POST" action="" enctype="multipart/form-data">

                    <input type="hidden" value="<?php echo $post->ID; ?>" name="nifty_desk_note_id" id="nifty_desk_note_id" />
                    <input type="hidden" value="<?php echo get_current_user_id(); ?>" name="nifty_desk_note_author" id="nifty_desk_note_author" />
                    <table width='100%'>
                    <tr>
                       <td width="10%" valign="top">
                           <?php _e("Title","nifty_desk"); ?>
                       </td>
                       <td>
                          <input style="width:50%; min-width:200px; margin-bottom:5px; font-weight:bold;" type="text" value="<?php _e('Note for ', 'nifty_desk'); echo $post->post_title; ?>" name="nifty_desk_note_title" id="nifty_desk_note_title" />
                       </td>
                    </tr>
                    <tr>
                        <td valign="top">
                            <?php _e("Note","nifty_desk"); ?>
                        </td>
                        <td><textarea style="width:100%; height:120px;" name="nifty_desk_note_text" id="nifty_desk_note_text"></textarea></td>
                    </tr>
                    <tr>
                        <td>
                        </td>
                       <td align="right">
                            <a href="javascript:void(0);" title="<?php _e("Save","nifty_desk"); ?>" class="button-primary nifty_desk_save_note_btn" /><?php _e("Save Note","nifty_desk"); ?></a>
                       </td>
                    </tr>
                    </table>
                </form>

            </div>


<?php

$meta_data = nifty_desk_get_note_meta_all($post->ID);

    echo '<hr />';
            foreach ($meta_data as $response) {
                echo nifty_desk_draw_response_box($response->post_id);

            }
}

function nifty_desk_get_post_meta_all($post_id){
    global $wpdb;
    $data   =   array();
    $sql = "SELECT `meta_key`, `meta_value`, `post_id` FROM $wpdb->postmeta WHERE `meta_key` = '_response_parent_id' AND `meta_value` = '$post_id' ORDER BY `meta_id` DESC";
    $wpdb->query($sql);
    foreach($wpdb->last_result as $k => $v){
        $data[$k] = $v;
    };
    return $data;
}
function nifty_desk_get_note_meta_all($post_id){
    global $wpdb;
    $data   =   array();
    $sql = "SELECT `meta_key`, `meta_value`, `post_id` FROM $wpdb->postmeta WHERE `meta_key` = '_note_parent_id' AND `meta_value` = '$post_id' ORDER BY `meta_id` DESC";
    $wpdb->query($sql);
    foreach($wpdb->last_result as $k => $v){
        $data[$k] = $v;
    };
    return $data;
}
function nifty_desk_get_post_meta_last($post_id){
    global $wpdb;
    $data   =   array();
    $sql = "SELECT `meta_key`, `meta_value`, `post_id` FROM $wpdb->postmeta WHERE `meta_key` = '_response_parent_id' AND `meta_value` = '$post_id' ORDER BY `meta_id` DESC LIMIT 1";

    $wpdb->query($sql);
    foreach($wpdb->last_result as $k => $v){
        $data[$k] = $v;
    };
    return $data;
}




/* open or closed meta box */

function nifty_desk_add_topic_status_meta_box() {

	$screens = array( 'nifty_desk_tickets' );

	foreach ( $screens as $screen ) {

		add_meta_box(
			'nifty_desk_sectionid',
			__( 'Ticket Status', 'nifty_desk' ),
			'nifty_desk_ticket_status_meta_box_callback',
			$screen,
                        'side',
                        'high'
		);
	}
}
if (function_exists("nifty_desk_pro_add_topic_status_meta_box")) {
    add_action( 'add_meta_boxes', 'nifty_desk_pro_add_topic_status_meta_box' );
} else {
    add_action( 'add_meta_boxes', 'nifty_desk_add_topic_status_meta_box' );
}

/**
 * Prints the box content.
 *
 * @param WP_Post $post The object for the current post/page.
 */
function nifty_desk_ticket_status_meta_box_callback( $post ) {

	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'nifty_desk_add_topic_status_meta_box', 'nifty_desk_meta_box_ticket_status_nonce' );

	/*
	 * Use get_post_meta() to retrieve an existing value
	 * from the database and use the value for the form.
	 */
        $value = get_post_custom_values( 'ticket_status', $post->ID );
        $priority = get_post_custom_values( 'ticket_priority', $post->ID );
        $author_id=$post->post_author;


        $user_email=get_the_author_meta( 'user_email', $author_id );



        $nifty_desk_get_all_users = get_users();

        ?>
        <table>
            <tr>
                <td>
                    <label for="nifty_desk_new_field" style="color:red;"><?php _e( 'Ticket Status', 'nifty_desk' ); ?></label>
                </td>
                <td>
                    <select name="nifty_desk_change_ticket_status">
                        <option value="0"<?php if ($value[0] == "0") { echo 'selected="selected"'; } ?>><?php _e("Open","nifty_desk"); ?></option>
                        <option value="1"<?php if ($value[0] == "1") { echo 'selected="selected"'; } ?>><?php _e("Solved","nifty_desk"); ?></option>
                        <option value="9"<?php if ($value[0] == "9") { echo 'selected="selected"'; } ?>><?php _e("Pending Approval","nifty_desk"); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="nifty_desk_new_field"><?php _e( 'Priority', 'nifty_desk' ); ?></label>
                </td>
                <td>
                    <select name="nifty_desk_change_ticket_priority">
                        <option value="1" <?php if ($priority[0] == "1") { echo 'selected="selected"'; } ?>><?php _e("Low","nifty_desk"); ?></option>
                        <option value="2" <?php if ($priority[0] == "2") { echo 'selected="selected"'; } ?>><?php _e("High","nifty_desk"); ?></option>
                        <option value="3" <?php if ($priority[0] == "3") { echo 'selected="selected"'; } ?>><?php _e("Urgent","nifty_desk"); ?></option>
                        <option value="4" <?php if ($priority[0] == "4") { echo 'selected="selected"'; } ?>><?php _e("Critical","nifty_desk"); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
            	<td>
            		<?php _e( 'Author e-mail:', 'nifty_desk' ); ?>

            	</td>
            	<td>
                    <input type="text" readonly value="<?php echo $user_email ?>" />
            	</td>
            </tr>
        </table>

<?php
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function nifty_desk_topic_status_save_meta_box_data( $post_id ) {
	/*
	 * We need to verify this came from our screen and with proper authorization,
	 * because the save_post action can be triggered at other times.
	 */


	// Check if our nonce is set.
	if ( ! isset( $_POST['nifty_desk_meta_box_ticket_status_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['nifty_desk_meta_box_ticket_status_nonce'], 'nifty_desk_add_topic_status_meta_box' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	/* OK, it's safe for us to save the data now. */

	// Make sure that it is set.
	if ( ! isset( $_POST['nifty_desk_change_ticket_status'] ) || ! isset( $_POST['nifty_desk_change_ticket_priority'] ) ) {
		return;
	}

	// Sanitize user input.
	$my_data = sanitize_text_field( $_POST['nifty_desk_change_ticket_status'] );
	$priority = sanitize_text_field( $_POST['nifty_desk_change_ticket_priority'] );


	// Update the meta field in the database.

	update_post_meta( $post_id, 'ticket_priority', $priority );

        if(update_post_meta( $post_id, 'ticket_status', $my_data )){
            $nifty_desk_settings = get_option("nifty_desk_settings");

            if(isset($nifty_desk_settings['nifty_desk_settings_notify_status_change'])&&$nifty_desk_settings['nifty_desk_settings_notify_status_change'] == 1){
                $post_details = get_post($post_id);

                $author_id = $post_details->post_author;

                nifty_desk_notification_control('status_change', $post_id, $author_id);
            }
        }

}
if (function_exists("nifty_desk_pro_topic_status_save_meta_box_data")) {
    add_action( 'save_post', 'nifty_desk_pro_topic_status_save_meta_box_data' );
} else {
    add_action( 'save_post', 'nifty_desk_topic_status_save_meta_box_data' );
}