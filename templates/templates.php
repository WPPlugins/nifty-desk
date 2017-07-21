<?php

add_action("nifty_desk_settings_tabs", "nifty_desk_template_settings_tab", 1);
/**
 * Outputs Settings Tab Heading
 * 
 * @return void
*/
function nifty_desk_template_settings_tab(){
	?>
		 <li><a href="#tabs-templates"><?php _e("Themes","nifty_desk") ?></a></li>
	<?php
}

add_action("nifty_desk_settings_content", "nifty_desk_template_settings_content", 1);
/**
 * Outputs Settings Content
 *
 * @return void
*/
function nifty_desk_template_settings_content(){
	?>
		<div id="tabs-templates">
			<h3><?php _e("Themes", "nifty_desk") ?></h3>
			<i><small><?php _e("Select a theme for your help desk", "nifty_desk"); ?></small></i>
			
			<table class="wp-list-table widefat fixed striped pages">
				<thead>
					<tr>
						<th><?php _e("Option", "nifty_desk") ?></th>
						<th><?php _e("Value", "nifty_desk") ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<?php _e("Current Theme", "nifty_desk") ?>:
						</td>
						<td>
							<select name="nifty_desk_settings_selected_theme">
								<?php 
								$all_themes = nifty_desk_list_themes(true);
								$selected_theme = nifty_desk_get_selected_theme();
								foreach ($all_themes as $key => $value) {
									echo "<option value='" . $key . "' " . ($selected_theme == $key ? "SELECTED" : "") . ">";
									echo ucfirst($key);
									echo "</option>";
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<?php _e("Theme Directories", "nifty_desk") ?>: <small>(<?php _e("Directories where themes can be found", "nifty_desk"); ?>)</small>
						</td>
						<td>
							<?php 
							$all_directories = nifty_desk_get_theme_directories();
							
							foreach ($all_directories as $key => $value) {
								echo "<code>";
								echo str_replace("\\", "/", $value);
								echo "</code>";

								if(!@file_exists(nifty_desk_path_shortcode_filter($value))){
									//Directory does not exist
									echo "<code>" . __("Does Not Exist", "nifty_desk") . "</code>";
								}

								echo "<br>";
							}
							?>
						</td>
					</tr>

					<?php do_action("nifty_desk_template_settings_hook"); ?>

					<tr>
						<td>
							<?php _e("Directory Shortcodes", "nifty_desk") ?>:
						</td>
						<td>
							<?php 
							$root_paths = array("{install_path}", "{upload_path}");

							$root_paths = apply_filters("nifty_desk_settings_theme_root_hook", $root_paths);
							foreach ($root_paths as $value) {
								echo "<code>";
								echo $value;
								echo "</code>";
								echo " = ";
								echo "<code>" . nifty_desk_path_shortcode_filter($value, true) . "</code>";
								echo "<br>";
							}
							?>
						</td>
					</tr>

					<tr>
						<td>

						</td>
						<td>
							<small>
								<?php _e("Note", "nifty_desk") ?>: <?php _e("Use {upload_path} when adding/editing theme files, to prevent losing theme files upon update", "nifty_desk") ?>
							</small>
						</td>
						
					</tr>


				</tbody>
			</table>
		</div>
	<?php
}

add_filter("nifty_desk_save_settings_hook", "nifty_desk_theme_settings_head", 1);
/**
 * Handles Saving of Theme Settings
 *
 * @return void
*/
function nifty_desk_theme_settings_head($nifty_desk_settings){
	if (isset($_POST['nifty_desk_settings_selected_theme'])) { 
		$nifty_desk_settings['nifty_desk_settings_selected_theme'] = esc_attr($_POST['nifty_desk_settings_selected_theme']);
	} 

	return $nifty_desk_settings;
}

add_filter( 'template_include', 'nifty_desk_view_template', 99 ); //Support Ticket Filter
/**
 * Returns new tempalte if this post type matches one of the system types
 *
 * @param  string $template Current page template
 * @return string  PHP page tmeplate
*/
function nifty_desk_view_template( $template ) {
	$nifty_desk_submit_ticket_post_id = get_option("nifty_desk_submit_ticket_page");

	if ("nifty_desk_tickets" == get_post_type()) {
		$template_to_include = nifty_desk_ticket_view_include_template();

		if($template_to_include !== false){
			return $template_to_include;
		} else {
			return $template;
		}
	} else if(get_the_ID() === intval($nifty_desk_submit_ticket_post_id)){
		$template_to_include = nifty_desk_submit_ticket_include_template();

		if($template_to_include !== false){
			return $template_to_include;
		} else {
			return $template;
		}
	}

	return apply_filters("nifty_desk_view_template_hook", $template);
}

/**
 * Gets selected ticket view template structure (ticket_view.php)
 *
 * @return string  HTML page tmeplate
*/
function nifty_desk_ticket_view_include_template(){
	$selected_theme = nifty_desk_get_selected_theme();
	if($selected_theme !== false){
		$all_themes = nifty_desk_list_themes(true);

		if(isset($all_themes[$selected_theme])){
			if(@file_exists($all_themes[$selected_theme] . "ticket_view" . ".php")){
				nifty_desk_template_include_styles($all_themes[$selected_theme], $selected_theme);
				nifty_desk_ticket_view_globals();
				nifty_desk_ticket_view_shortcodes();
				return load_template($all_themes[$selected_theme] . "ticket_view" . ".php");
			} else {
				return false;
			}
		} else {
			return false;
		}	
	} else {
		return false; 
	}

}

/**
 * Gets selected ticket submit template structure (submit_ticket.php)
 *
 * @return string  HTML page tmeplate
*/
function nifty_desk_submit_ticket_include_template(){
	$selected_theme = nifty_desk_get_selected_theme();
	if($selected_theme !== false){
		$all_themes = nifty_desk_list_themes(true);
		if(isset($all_themes[$selected_theme])){
			if(@file_exists($all_themes[$selected_theme] . "submit_ticket" . ".php")){
				nifty_desk_template_include_styles($all_themes[$selected_theme], $selected_theme);
				return load_template($all_themes[$selected_theme] . "submit_ticket" . ".php");
			} else {
				return false;
			}
		} else {
			return false;
		}	
	} else {
		return false; 
	}

}

/**
 * Returns the selected theme name as selected by user
 *
 * @return string
*/
function nifty_desk_get_selected_theme(){
	$nifty_desk_settings = get_option('nifty_desk_settings');
	if(isset($nifty_desk_settings["nifty_desk_settings_selected_theme"])){
		return $nifty_desk_settings["nifty_desk_settings_selected_theme"];
	} else {
		return false;
	}
}

/**
 * Enqueue selected template styles
 *
 * @param string $path Direcotry to theme
 * @return void
*/
function nifty_desk_template_include_styles($path, $selected_theme){
	wp_enqueue_style('nifty_desk_template_styles', NIFTY_DESK_PLUGIN_DIR . '/css/templates_base.css');

	if(@file_exists($path . $selected_theme . ".css")){
		$all_themes = nifty_desk_list_themes(false);
		if(isset($all_themes[$selected_theme])){
			wp_enqueue_style('nifty_desk_theme_styles', str_replace("\\", "/", $all_themes[$selected_theme]) . $selected_theme . ".css");
		}
	}
}

/**
 * Create the ticket Globals
 *
 * @return void
*/
function nifty_desk_ticket_view_globals(){

	$GLOBALS['nd_ticket_view_id'] = get_the_ID();
	$GLOBALS['nd_ticket_view_safe'] = $GLOBALS['nd_ticket_view_id'] !== false ? true : false; 
	$GLOBALS['nd_ticket_view_current_user'] = wp_get_current_user();


	if($GLOBALS['nd_ticket_view_safe'] === true){	//Ticket is 'safe'
		$GLOBALS['nd_ticket_view_status'] = intval(get_post_meta($GLOBALS['nd_ticket_view_id'], 'ticket_status', true));
		$GLOBALS['nd_ticket_view_public'] = get_post_meta($GLOBALS['nd_ticket_view_id'], 'ticket_public', true);


        $GLOBALS['nd_ticket_view_author'] = nifty_desk_get_ticket_author(get_the_ID());

		$nd_show_ticket = false;
		if($GLOBALS['nd_ticket_view_current_user']->ID == 0){
            if($GLOBALS['nd_ticket_view_public']){
                $nd_show_ticket = true;
            } else {
                $nd_show_ticket = false;
            }
        } else if ($GLOBALS['nd_ticket_view_current_user']->ID == $GLOBALS['nd_ticket_view_author'] || current_user_can('edit_nifty_desk_tickets', array(null))){
            $nd_show_ticket = true;
        } else {
            if($GLOBALS['nd_ticket_view_public']){
                $nd_show_ticket = true;
            } else {
                $nd_show_ticket = false;
            }
        }

		$GLOBALS['nd_ticket_view_show'] = $nd_show_ticket; 	//False by default to prevent viewing of ticket
	}
}

/**
 * Adds shortcode handlers to the template page
 *
 * @return void
*/
function nifty_desk_ticket_view_shortcodes(){
	add_shortcode('nifty_desk_ticket_title', 'nifty_desk_ticket_view_title');
	add_shortcode('nifty_desk_ticket_responses', 'nifty_desk_ticket_view_show_responses');
	add_shortcode('nifty_desk_ticket_response_form', 'nifty_desk_ticket_view_response_form');
	add_shortcode('nifty_desk_ticket_author_details', 'nifty_desk_ticket_author_details');
	add_shortcode('nifty_desk_ticket_notices', 'nifty_desk_ticket_notices');
}

/**
 * Returns ticket responses
 *
 * @return string
*/
function nifty_desk_ticket_view_title(){
	$output = "";
	$is_safe = isset($GLOBALS['nd_ticket_view_safe']) ? $GLOBALS['nd_ticket_view_safe'] : false;
	if($is_safe){
		$the_post = get_post(get_the_ID());
		$output .= $the_post->post_title;
	}
	return $output;
}

/**
 * Returns ticket responses
 *
 * @return string (html)
*/
function nifty_desk_ticket_view_show_responses(){
	$output = "";
	$is_safe = isset($GLOBALS['nd_ticket_view_safe']) ? $GLOBALS['nd_ticket_view_safe'] : false;
	if($is_safe){
		$can_show = isset($GLOBALS['nd_ticket_view_show']) ? $GLOBALS['nd_ticket_view_show'] : false;
		$ticket_status = isset($GLOBALS['nd_ticket_view_status']) ? $GLOBALS['nd_ticket_view_status'] : false;

		/*
         * 0 - Open
         * 1 - Solved
         * 3 - Pending
         * 9 - New
         */
		if ($ticket_status === 0 || $ticket_status === 3) {	
            if ($can_show) {
				if(!is_search()){
                	$output = nifty_desk_append_responses_to_ticket(get_the_ID()) . nifty_desk_append_content_of_ticket(get_the_ID());
				} 
            } else {
                $output = wp_login_form();
            }
        } else if ($ticket_status === 1 || $ticket_status === 9) {
            if ($can_show) {
				if(!is_search()){
                	$output = nifty_desk_append_responses_to_ticket(get_the_ID()) . nifty_desk_append_content_of_ticket(get_the_ID());
				}
            }
        } 
	}
	return $output;
}


/**
 * Returns ticket response form
 *
 * @return string
*/
function nifty_desk_ticket_view_response_form(){
	return nifty_desk_append_response_form(get_the_ID());
}

/**
 * Returns ticket author details
 *
 * @return string
*/
function nifty_desk_ticket_author_details(){
	$output = "";
	$is_safe = isset($GLOBALS['nd_ticket_view_safe']) ? $GLOBALS['nd_ticket_view_safe'] : false;
	if($is_safe){
		$author_id = $GLOBALS['nd_ticket_view_author'];
		$author_data = get_user_by('id', $author_id);

		$output .= "<span class='nifty_desk_author_info_name'>" . $author_data->display_name . "</span><br>";
		$output .= "<span class='nifty_desk_author_info_email'>" . $author_data->user_email . "</span>";
	}
	return $output;

}

/**
 * Returns ticket notcies
 *
 * @return string (html)
*/
function nifty_desk_ticket_notices(){
	$output = "";
	$is_safe = isset($GLOBALS['nd_ticket_view_safe']) ? $GLOBALS['nd_ticket_view_safe'] : false;

	if($is_safe){
		$can_show = isset($GLOBALS['nd_ticket_view_show']) ? $GLOBALS['nd_ticket_view_show'] : false;
		$ticket_status = isset($GLOBALS['nd_ticket_view_status']) ? $GLOBALS['nd_ticket_view_status'] : false;

		/*
	     * 0 - Open
	     * 1 - Solved
	     * 3 - Pending
	     * 9 - New
	     */
	    if ($ticket_status === 0 || $ticket_status === 3) {
	        if (!$can_show) {
				$output = "<span class='nifty_desk_notice_span'>" . __("This support ticket has been marked as private.", "nifty_desk") . "</span>";
	        }
	    } else if ($ticket_status === 1) {
	        $output = "<span class='nifty_desk_notice_span'>" . __("This support ticket has been marked as solved.", "nifty_desk") . "</span>";
	    } else if ($ticket_status === 9) {

	    	$output = "";
	        if(isset($_SESSION['file_upload_failed'])){
	           $output .= "<span class='nifty_desk_notice_span'>" . $_SESSION['file_upload_failed'] . "</span>";
	           unset($_SESSION['file_upload_failed']);
	        }

	        $output .= "<span class='nifty_desk_notice_span'>" . __("This support ticket has been received.", "nifty_desk") . "</span>";
	        
	    }
	} else {
		$output = "<span class='nifty_desk_notice_span'>" . __("This support ticket has been marked as private.", "nifty_desk") . "</span>";
	}

	return $output;
}

/**
 * Returns theme directories
 *
 * @return array
*/
function nifty_desk_get_theme_directories(){
	$search_directories = array( "system_basic" =>  "{install_path}" . "/templates/basic_templates/",
								 "system_basic_uploads" => "{upload_path}" . "/nifty_desk/templates/"

	);
	$search_directories = apply_filters("nifty_desk_themes_search_dir_filter", $search_directories); //Allows for directories to be added dynamically

	return $search_directories;
}

/**
 * Returns list of all themes found in directories
 *
 * @param  boolean $return_dir Return Directory Path (Alternatively returns URL)
 * @return array
*/
function nifty_desk_list_themes($return_dir = true){
	$search_directories = nifty_desk_get_theme_directories();

	$found_themes = array();

	foreach ($search_directories as $key => $value) {
		$orig_value = $value;
		$value = nifty_desk_path_shortcode_filter($value, true);

		if(@file_exists($value)){
			$parent_directory = @dir($value);
			if(@is_dir($value)){
				$open_directory = @opendir($value);
				while( ($sub_directory = @readdir($open_directory)) !== false){			
					if($sub_directory !== "." && $sub_directory !== ".."){
						if(!isset($found_themes[$sub_directory])){
							$found_themes[$sub_directory] = $value . $sub_directory . "/" ;
							if($return_dir === false){
								$found_themes[$sub_directory] = nifty_desk_path_shortcode_filter($orig_value, false) . $sub_directory . "/" ;
							}

						}
					}
				}
			}
		}

	}

	return $found_themes;
}

/** 
 * Processes internal path shorcodes, for example: {install_path}
 *
 * @param  string  $path_to_filter Path with shortcodes present
 * @param  boolean $return_dir 	   Return Directory Path (Alternatively returns URL)
 * @return string
*/
function nifty_desk_path_shortcode_filter($path_to_filter, $return_dir = true){
	//Search and destroy
	$search_replace = array(
		"{install_path}" => $return_dir ? NIFTY_DESK_PLUGIN_DIR : NIFTY_DESK_PLUGIN_URL,
		"{upload_path}" => $return_dir ? wp_upload_dir()['basedir'] : wp_upload_dir()['baseurl']
	); 

	$search_replace = apply_filters("nifty_desk_path_shortcode_hook", $search_replace, $return_dir);

	foreach ($search_replace as $key => $value) {
		$path_to_filter = str_replace($key, $value, $path_to_filter);
	}
	return $path_to_filter;
}

add_action("edit_form_top", "nifty_desk_template_page_edit_checker");
/** 
 * Checks if user is attempting to edit a generated page. 
 * If so, shows a notice of to the user stating that editing this page will not alter the appearance of the page
 * 
 * @return void
*/
function nifty_desk_template_page_edit_checker(){
	$generated_pages = array();
	$generated_pages = apply_filters("nifty_desk_template_page_edit_checker_array_hook", $generated_pages);

	foreach ($generated_pages as $key => $value) {
		if(intval($value) === get_the_ID()){
		?>
			<div class="notice notice-warning is-dismissible">
	            <p><strong><?php _e( 'Nifty Desk Notice', 'sample-text-domain' ); ?>: </strong>
	               <?php _e( 'This page is generated by your support desk theme', 'sample-text-domain' ); ?>
	            </p>

	            <p> 
	               <?php _e( 'Your support desk theme can be changed from within the Nifty Desk setting area.', 'sample-text-domain' ); ?> <a href="admin.php?page=nifty-desk-settings" class="button button-default"><?php _e("Themes", "nifty_desk") ?></a>
	            </p>
	        
	        </div>
        <?php
		}
	}

}

add_action("nifty_desk_template_page_edit_checker_array_hook", "nifty_desk_template_page_add_to_checker_basic", 10, 1);
/** 
 * Checks if user is attempting to edit a generated page. 
 * If so, shows a notice of to the user stating that editing this page will not alter the appearance of the page
 * 
 * @return void
*/
function nifty_desk_template_page_add_to_checker_basic($pages){
	if(is_array($pages)){
		if(get_option("nifty_desk_submit_ticket_page") !== false){
			$pages[count($pages)] = get_option("nifty_desk_submit_ticket_page");
		}

		if(get_option("nifty_desk_support_center_page") !== false){
			$pages[count($pages)] = get_option("nifty_desk_support_center_page");
		}
	}

	return $pages;
}
