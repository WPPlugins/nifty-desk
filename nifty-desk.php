<?php
/*
  Plugin Name: Nifty Desk - Ultimate Support Desk Plugin
  Plugin URI: http://niftydesk.org/
  Description: Create a support centre within your WordPress admin. No need for third party systems!
  Version: 1.02
  Author: CODECABIN_
  Author URI: http://niftydesk.org/
 */

/* 
 * 1.02 - 2017-02-09 - Medium Priority
 * You can now search for tickets based on the author email address
 * Bug Fix: Correct timestamp shown in search results for last responder
 * Bug Fix: Author name displays correctly in search results
 * Bug Fix: Slashes are now removed from ticket replies
 * Bug Fix: Two PHP errors after submitting a ticket while logged in
 * Bug Fix: Headers already sent once a ticket is submitted and needs to redirect to the thank you page
 * Enhancement: You can now close the 'Merge Tickets' popup by pressing the escape key
 * Bug Fix: Changing originator's email address will change their display name in the ticket too
 * Enhancement: Styling fixes made to the 'Merge Tickets' popup
 * 
 * 1.01 - 2016-11-22
 * Fixed bugs when creating a ticket from the API (no last_update timestamp and support tickets were being incorrectly assigned)
 * Fixed a bug when creating a ticket from the front end (user's side)
 * Fixed a bug that caused html entities to show up incorrectly in tickets
 * Added a check in place to abort the previous XHR requests when clicking on views sequentially
 * 
 * 1.00 - 2016-11-17 - Launch
 */ 

global $nifty_desk_version;
global $nifty_desk_pro_version;
define("NIFTY_DESK_PLUGIN_NAME", "Nifty Desk");
global $nifty_desk_version;
global $nifty_desk_version_string;
$nifty_desk_version = "1.02";
$nifty_desk_version_string = "basic";



include_once "modules/views.php";
include_once "modules/channels.php";
include_once "modules/api/nifty-desk-api.php";
include_once "modules/reporting.php";
include_once "modules/autoassign.php";
include_once "modules/email.php";
include_once "modules/widgets.php";
include_once "modules/archive.php";
include_once "templates/templates.php";

define("NIFTY_DESK_SITE_URL", get_bloginfo('url'));
define("NIFTY_DESK_PLUGIN_URL", plugins_url() . '/nifty-desk');
define("NIFTY_DESK_PLUGIN_DIR", plugin_dir_path(__FILE__));


add_action('init', 'nifty_desk_init');
add_action('admin_menu', 'nifty_desk_admin_menu' ,1);

if (function_exists("nifty_desk_pro_wp_head")) {
    add_action('admin_head', 'nifty_desk_pro_wp_head');
} else {
    add_action('admin_head', 'nifty_desk_wp_head');
}

if (function_exists("nifty_desk_pro_user_head")) {
    add_action('init', 'nifty_desk_pro_user_head');
} else {
    add_action('init', 'nifty_desk_user_head');
}
if (function_exists("nifty_desk_pro_admin_head")) {
    add_action("admin_head", "nifty_desk_pro_admin_head");
}


add_shortcode("nifty_desk_submit_ticket", "nifty_desk_shortcode_submit_ticket_page");

register_activation_hook(__FILE__, 'nifty_desk_activate');
register_deactivation_hook(__FILE__, 'nifty_desk_deactivate');

/**
 * Primary Initialization function
 * - Handles Page Actions
 * - Set's up administrator capabilities
 * - Checks that all default settings are set
 * - Checks version of plugin
 *
 * @return void
*/
function nifty_desk_init() {
    if (isset($_POST['action']) && $_POST['action'] == 'nifty_desk_submit_find_us') {
        nifty_desk_feedback_head();
        wp_redirect("./edit.php?post_type=nifty_desk_tickets&page=nifty-desk-settings", 302);
        exit();
    }
    if (isset($_POST['action']) && $_POST['action'] == 'nifty_desk_skip_find_us') {
        wp_redirect("./edit.php?post_type=nifty_desk_tickets&page=nifty-desk-settings", 302);
        exit();
    }

    if (isset($_GET['post_type']) && $_GET['post_type'] == "nifty_desk_tickets") {
        
        if (get_option('nifty_desk_first_time') == false) {
            update_option('nifty_desk_first_time', true);
            wp_redirect('edit.php?post_type=nifty_desk_tickets&page=nifty-desk-settings&action=welcome_page', 302);
            exit();
        }
    }


    $plugin_dir = basename(dirname(__FILE__)) . "/languages/";
    load_plugin_textdomain('nifty_desk', false, $plugin_dir);

    /* allow admins to create and edit tickets */
    $admins = get_role('administrator');
    $admins->add_cap('edit_nifty_desk_ticket');
    $admins->add_cap('edit_nifty_desk_tickets');
    $admins->add_cap('edit_other_nifty_desk_tickets');
    $admins->add_cap('publish_nifty_desk_tickets');
    $admins->add_cap('read_nifty_desk_ticket');
    $admins->add_cap('read_private_nifty_desk_tickets');
    $admins->add_cap('delete_nifty_desk_tickets');

    $nifty_desk_submit_ticket_page_option = get_option("nifty_desk_submit_ticket_page");
    $nifty_desk_submit_ticket_post = get_post(intval($nifty_desk_submit_ticket_page_option));
    if (!$nifty_desk_submit_ticket_page_option || $nifty_desk_submit_ticket_post === null) {

        $content = __("This page is generated by your support desk theme", "nifty_desk");
        $page_id = nifty_desk_create_page('submit-ticket', __("Submit a ticket", "nifty_desk"), $content);
        update_option("nifty_desk_submit_ticket_page", "$page_id");
    } else if($nifty_desk_submit_ticket_post !== null){
       nifty_desk_check_if_pages_trashed();
    }

    /* check if options are correct */
    $nifty_desk_settings = get_option("nifty_desk_settings");

    if (!isset($nifty_desk_settings['nifty_desk_settings_default_priority'])) { $nifty_desk_settings['nifty_desk_settings_default_priority'] = 1; }
    if (!isset($nifty_desk_settings['nifty_desk_settings_allow_priority'])) { $nifty_desk_settings['nifty_desk_settings_allow_priority'] = 0; }
    if (!isset($nifty_desk_settings['nifty_desk_settings_notify_new_tickets'])) { $nifty_desk_settings['nifty_desk_settings_notify_new_tickets'] = 0; }
    if (!isset($nifty_desk_settings['nifty_desk_settings_notify_new_responses'])) { $nifty_desk_settings['nifty_desk_settings_notify_new_responses'] = 0; }
    if (!isset($nifty_desk_settings['nifty_desk_settings_allow_html'])) { $nifty_desk_settings['nifty_desk_settings_allow_html'] = 0; }
    if (!isset($nifty_desk_settings['nifty_desk_settings_dashboard_folded'])) { $nifty_desk_settings['nifty_desk_settings_dashboard_folded'] = 0; }
    
    if (!isset($nifty_desk_settings['nifty_desk_settings_thank_you_text'])) {
        $nifty_desk_settings['nifty_desk_settings_thank_you_text'] = __("<p>Thank you for submitting your support ticket. One of our agents will respond as soon as possible.</p>".
                            "{ticket_content}".
                            "{login_details}".
                            "<p>To view this ticket, please follow this link: {ticket_link}</p>", "nifty_desk");
    }
    if (!isset($nifty_desk_settings['nifty_desk_settings_notify_agent_change'])) { $nifty_desk_settings['nifty_desk_settings_notify_agent_change'] = 0; }
    if (!isset($nifty_desk_settings['nifty_desk_settings_notify_status_change'])) { $nifty_desk_settings['nifty_desk_settings_notify_status_change'] = 0; }


    
    /* version control */
    global $nifty_desk_version;
    if (floatval($nifty_desk_version) > floatval(get_option("nifty_desk_current_version"))) {
        /* new version update functionality here */

        if (!get_option("nifty_desk_views")) {
            nifty_desk_set_default_views();
        }

        if (floatval(get_option("nifty_desk_current_version")) < 4) {
            /* set the thank you text to the new default if updating from an older version */
            $nifty_desk_settings['nifty_desk_settings_thank_you_text'] = __("<p>Thank you for submitting your support ticket. One of our agents will respond as soon as possible.</p>".
                            "{ticket_content}".
                            "{login_details}".
                            "<p>To view this ticket, please follow this link: {ticket_link}</p>", "nifty_desk");
        }

        do_action("nifty_desk_update_hook");

        update_option("nifty_desk_current_version", $nifty_desk_version);
    }
	

    update_option("nifty_desk_settings", $nifty_desk_settings);
	
	nifty_desk_warn_update_pro();
	
	
	
}

add_action('init', 'nifty_desk_create_ticket_post_type', 0);
add_action('init', 'nifty_desk_create_response_post_type', 0);
add_action('init', 'nifty_desk_create_internal_notes', 0);

add_action('wp_ajax_nifty_desk_save_response', 'nifty_desk_action_callback');
add_action('wp_ajax_nifty_desk_save_note', 'nifty_desk_action_callback');




add_filter( 'add_menu_classes', 'nifty_desk_show_pending_number');
/**
 * Displays 'Pending' (Tickets without responses) for use in admin menu
 *
 * @param  array $menu
 * @return string (HTML)
*/
function nifty_desk_show_pending_number( $menu ) {

    $settings = get_option('nifty_desk_settings');

    if( isset( $settings['nifty_desk_display_legacy_tickets'] ) && $settings['nifty_desk_display_legacy_tickets'] == 1 ){
        $menu_str = "edit.php?post_type=nifty_desk_tickets";
    } else {
        $menu_str = "support-tickets";
    }
    $pending_count = nifty_desk_return_pending_ticket_qty();

    // loop through $menu items, find match, add indicator
    foreach( $menu as $menu_key => $menu_data ) {
        if( $menu_str != $menu_data[2] )
            continue;
        $menu[$menu_key][0] .= " <span class='update-plugins count-$pending_count'><span class='plugin-count'>" . number_format_i18n($pending_count) . '</span></span>';
    }
    return $menu;
}

/**
 * Registers 'Ticket' post type
 *
 * @return void
*/
function nifty_desk_create_ticket_post_type() {

    $labels = array(
        'name' => __('Tickets', 'nifty_desk'),
        'singular_name' => __('Ticket', 'nifty_desk'),
        'add_new' => __('New Ticket', 'nifty_desk'),
        'add_new_item' => __('Add New Ticket', 'nifty_desk'),
        'edit_item' => __('Edit Ticket', 'nifty_desk'),
        'new_item' => __('New Ticket', 'nifty_desk'),
        'all_items' => __('All Tickets', 'nifty_desk'),
        'view_item' => __('View Ticket', 'nifty_desk'),
        'search_items' => __('Search Tickets', 'nifty_desk'),
        'not_found' => __('No tickets found', 'nifty_desk'),
        'not_found_in_trash' => __('No tickets found in the Trash', 'nifty_desk'),
        'menu_name' => __('Help Desk', 'nifty_desk')
    );
    $args = array(
        'labels' => $labels,
        'description' => __('Support tickets', 'nifty_desk'),
        'public' => true,
        'menu_position' => 50,
        'hierarchical' => false,
        'rewrite' => array('slug' => 'support-tickets'),
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'query_var' => true,
        'supports' => array('title', 'editor', 'custom-fields', 'revisions', 'page-attributes', 'author'),
        'has_archive' => true,
        'capabilities' => array(
            'edit_post' => 'edit_nifty_desk_ticket',
            'edit_posts' => 'edit_nifty_desk_tickets',
            'edit_others_posts' => 'edit_other_nifty_desk_tickets',
            'publish_posts' => 'publish_nifty_desk_tickets',
            'read_post' => 'read_nifty_desk_ticket',
            'read_private_posts' => 'read_private_nifty_desk_tickets',
            'delete_post' => 'delete_nifty_desk_ticket'
        ),
        'map_meta_cap' => true
    );
    if (post_type_exists('nifty_desk_tickets')) {

    } else {
        register_post_type('nifty_desk_tickets', $args);
    }
    flush_rewrite_rules();
}

/**
 * Registers 'Response' post type
 *
 * @return void
*/
function nifty_desk_create_response_post_type() {

    $labels = array(
        'name' => __('Responses', 'nifty_desk'),
        'singular_name' => __('Response', 'nifty_desk'),
        'add_new' => __('New Response', 'nifty_desk'),
        'add_new_item' => __('Add New Response', 'nifty_desk'),
        'edit_item' => __('Edit Response', 'nifty_desk'),
        'new_item' => __('New Response', 'nifty_desk'),
        'all_items' => __('All Responses', 'nifty_desk'),
        'view_item' => __('View Response', 'nifty_desk'),
        'search_items' => __('Search Responses', 'nifty_desk'),
        'not_found' => __('No responses found', 'nifty_desk'),
        'not_found_in_trash' => __('No responses found in the Trash', 'nifty_desk'),
        'menu_name' => __('Ticket Responses', 'nifty_desk')
    );
    $args = array(
        'labels' => $labels,
        'description' => __('Responses to support tickets', 'nifty_desk'),
        'public' => true,
        'menu_position' => 51,
        'hierarchical' => true,
        'rewrite' => array('slug' => 'ticket-response'),
        'show_in_nav_menus' => false,
        'show_in_menu' => false,
        'publicly_queryable' => true,
        'supports' => array('title', 'editor', 'custom-fields', 'revisions', 'page-attributes', 'author'),
        'has_archive' => true,
        'capabilities' => array(
            'edit_post' => 'edit_nifty_desk_ticket',
            'edit_posts' => 'edit_nifty_desk_tickets',
            'edit_others_posts' => 'edit_other_nifty_desk_tickets',
            'publish_posts' => 'publish_nifty_desk_tickets',
            'read_post' => 'read_nifty_desk_ticket',
            'read_private_posts' => 'read_private_nifty_desk_tickets',
            'delete_post' => 'delete_nifty_desk_ticket'
        ),
        'map_meta_cap' => true
    );
    if (post_type_exists('nifty_desk_responses')) {

    } else {
        register_post_type('nifty_desk_responses', $args);
    }
}

/**
 * Registers 'Internal Note' post type
 *
 * @return void
*/
function nifty_desk_create_internal_notes() {

    $labels = array(
        'name' => __('Notes', 'nifty_desk'),
        'singular_name' => __('Note', 'nifty_desk'),
        'add_new' => __('New Note', 'nifty_desk'),
        'add_new_item' => __('Add New Note', 'nifty_desk'),
        'edit_item' => __('Edit Note', 'nifty_desk'),
        'new_item' => __('New Note', 'nifty_desk'),
        'all_items' => __('All Notes', 'nifty_desk'),
        'view_item' => __('View Note', 'nifty_desk'),
        'search_items' => __('Search Notes', 'nifty_desk'),
        'not_found' => __('No notes found', 'nifty_desk'),
        'not_found_in_trash' => __('No notes found in the Trash', 'nifty_desk'),
        'menu_name' => __('Internal Notes', 'nifty_desk')
    );
    $args = array(
        'labels' => $labels,
        'description' => __('Internal Notes for support tickets', 'nifty_desk'),
        'public' => true,
        'menu_position' => 52,
        'hierarchical' => true,
        'rewrite' => array('slug' => 'ticket-note'),
        'show_in_nav_menus' => false,
        'show_in_menu' => false,
        'publicly_queryable' => false,
        'supports' => array('title', 'editor'),
        'has_archive' => true
    );
    if (post_type_exists('nifty_desk_notes')) {

    } else {
        register_post_type('nifty_desk_notes', $args);
    }
}

function nifty_desk_normalize($text) {
    return $text;
}


/**
 * Nifty Desk Activation Function
 * - Set's up default settings, if not set
 *
 * @return void
*/
function nifty_desk_activate() {

    if (!get_option("nifty_desk_views")) {
        nifty_desk_set_default_views();
    }

    //nifty_desk_handle_db();
    if (!get_option("nifty_desk_email_to_ticket")) {
        add_option("nifty_desk_email_to_ticket", "0");
    }
    if (!get_option("nifty_desk_host")) {
        add_option("nifty_desk_host", "");
    }
    if (!get_option("nifty_desk_port")) {
        add_option("nifty_desk_port", "");
    }
    if (!get_option("nifty_desk_username")) {
        add_option("nifty_desk_username", "");
    }
    if (!get_option("nifty_desk_password")) {
        add_option("nifty_desk_password", "");
    }
    if (!get_option("nifty_desk_encryption")) {
        add_option("nifty_desk_encryption", "");
    }

    $nifty_desk_settings = get_option("nifty_desk_settings");
    if (!isset($nifty_desk_settings['nifty_desk_settings_thank_you_text'])) {
        $nifty_desk_settings['nifty_desk_settings_thank_you_text'] = __('Thank you for submitting your support ticket. One of our agents will respond as soon as possible.', 'nifty_desk');
    }

    if (!isset($nifty_desk_settings['nifty_desk_settings_selected_niftytheme'])) {
        $nifty_desk_settings['nifty_desk_settings_selected_theme'] = 'classic';
    }
    if (!isset($nifty_desk_settings['nifty_desk_settings_notify_new_responses'])) {
        $nifty_desk_settings['nifty_desk_settings_notify_new_responses'] = '1';
    }

    update_option("nifty_desk_settings", $nifty_desk_settings);

    if (!get_option("nifty_desk_current_version")) {
        global $nifty_desk_version;
        add_option("nifty_desk_current_version", $nifty_desk_version);
    }



   do_action("nifty_desk_activate_hook");


    flush_rewrite_rules();
}


/**
 * Nifty Desk De-Activation Hook
 *
 * @return void
*/
function nifty_desk_deactivate() {
    do_action("nifty_desk_deactivate_hook");
}

/**
 * Add Nifty Desk Admin Menu
 *
 * @return void
*/
function nifty_desk_admin_menu() {
    $settings = get_option('nifty_desk_settings');

    if( isset( $settings['nifty_desk_display_legacy_tickets'] ) && $settings['nifty_desk_display_legacy_tickets'] == 1 ){
        add_submenu_page('edit.php?post_type=nifty_desk_tickets', __('Responses', 'nifty_desk'), __('Responses', 'nifty_desk'), 'manage_options', 'edit.php?post_type=nifty_desk_responses');
        add_submenu_page('edit.php?post_type=nifty_desk_tickets', __('Settings', 'nifty_desk'), __('Settings', 'nifty_desk'), 'manage_options', 'nifty-desk-settings', 'nifty_desk_settings_page');
        add_submenu_page('edit.php?post_type=nifty_desk_tickets', __('Feedback', 'nifty_desk'), __('Feedback', 'nifty_desk'), 'manage_options', 'nifty-desk-menu-feedback-page', 'nifty_desk_admin_feedback_layout');
        add_submenu_page('edit.php?post_type=nifty_desk_tickets', __('Log', 'nifty_desk'), __('System Log', 'nifty_desk'), 'manage_options', 'nifty-desk-menu-error-log', 'nifty_desk_admin_error_log_layout');
    } else {
        remove_menu_page('edit.php?post_type=nifty_desk_tickets');
        add_menu_page( __('Nifty Desk', 'nifty_desk'), __('Nifty Desk', 'nifty_desk'), 'delete_posts', 'support-tickets', 'nifty_desk_modern_dashboard', 'dashicons-businessman', 51);
        //add_submenu_page('support-tickets', __('Responses', 'nifty_desk'), __('Responses', 'nifty_desk'), 'manage_options', 'edit.php?post_type=nifty_desk_responses');

        do_action("nifty_desk_admin_menu_above");

        add_submenu_page('support-tickets', __('Settings', 'nifty_desk'), __('Settings', 'nifty_desk'), 'manage_options', 'nifty-desk-settings', 'nifty_desk_settings_page');
        add_submenu_page('support-tickets', __('Feedback', 'nifty_desk'), __('Feedback', 'nifty_desk'), 'manage_options', 'nifty-desk-menu-feedback-page', 'nifty_desk_admin_feedback_layout');
        add_submenu_page('support-tickets', __('Log', 'nifty_desk'), __('Error Log', 'nifty_desk'), 'manage_options', 'nifty-desk-menu-error-log', 'nifty_desk_admin_error_log_layout');
    }    
}

/**
 * Display Nifty Desk Settings Area
 *  - Display Welcome page on first run
 *
 * @return void
*/
function nifty_desk_settings_page() {
    if (isset($_GET['page']) && $_GET['page'] == "nifty-desk-settings" && isset($_GET['action']) && $_GET['action'] == "welcome_page") {
        include('includes/welcome-page.php');
    } else if (isset($_GET['page']) && $_GET['page'] == "nifty-desk-settings" && !isset($_GET['action'])) {
        include('includes/settings-page.php');
    }
    do_action("nifty_desk_settings_page_header_control");
}

/**
 * Display Nifty Desk Error Log Area
 *
 * @return void
*/
function nifty_desk_admin_error_log_layout() {
    include('includes/error-log-page.php');
}

/**
 * Display Nifty Desk Feedback Area
 *
 * @return void
*/
function nifty_desk_admin_feedback_layout() {
    include('includes/feedback-page.php');
}

add_action('admin_print_scripts', 'nifty_desk_admin_scripts_basic');
/**
 * Loads Nifty Desk Admin Scripts
 *
 * @return void
*/
function nifty_desk_admin_scripts_basic() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-tabs');

    if (isset($_GET['page']) && $_GET['page'] == "nifty-desk-settings") {
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_register_style('nifty_desk_jquery_ui_theme_css', plugins_url('/css/jquery-ui-theme/jquery-ui.css', __FILE__));
        wp_enqueue_style('nifty_desk_jquery_ui_theme_css');
        wp_register_script('nifty-desk-tabs', plugins_url('js/nifty_desk_tabs.js', __FILE__), array('jquery-ui-core'), '', true);
        wp_enqueue_script('nifty-desk-tabs');
        wp_register_style('nifty_desk_admin_styles', plugins_url('/css/nifty-desk-admin.css', __FILE__));
        wp_enqueue_style('nifty_desk_admin_styles', get_stylesheet_uri());

    }

    if (isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] == 'edit') {
        wp_register_script('nifty-desk', plugins_url('js/nifty_desk.js', __FILE__), array('jquery'), '', true);
        wp_enqueue_script('nifty-desk');
        wp_register_style('nifty_desk_user_styles', plugins_url('/css/nifty-desk.css', __FILE__));
        wp_enqueue_style('nifty_desk_user_styles', get_stylesheet_uri());
    }
    
    /**
     * Loading required scripts and styles for the modern dashboard
     * @author  Nifty Desk, Jarryd Long
     * @since  4.0.0
     */


    if( isset( $_GET['page'] ) && $_GET['page'] == 'support-tickets' ){

        wp_register_script('nifty-desk-dashboard', plugins_url('js/dashboard.js', __FILE__), array('jquery'), '', true);

        $nifty_desk_dashboard_nonce = wp_create_nonce("nifty_desk_dashboard");

        wp_localize_script( 'nifty-desk-dashboard', 'nifty_desk_dashboard_security', $nifty_desk_dashboard_nonce );
        wp_localize_script( 'nifty-desk-dashboard', 'nifty_desk_db_plugins_url', plugins_url() );

        wp_enqueue_script('nifty-desk-dashboard');
        
        wp_register_style('nifty-desk-dashboard-styles', plugins_url('/css/dashboard.css', __FILE__));
        wp_enqueue_style('nifty-desk-dashboard-styles');

        $nifty_desk_dashboard_js_strings = array(
          'merge_title' => __('Merge with another ticket', 'nifty_desk'),
          'merge_content1' => __('Please insert the ticket number that you would like to merge this ticket into.', 'nifty_desk'),
          'merge_label1' => __('Ticket ID:', 'nifty_desk'),
          'merge_button1' => __('Preview', 'nifty_desk'),
          'merge_error1' => __('Please ensure you insert the ticket ID only (numeric input only)', 'nifty_desk'),
          'merge_error2' => __('The merge ID cannot be the same as the parent ID.', 'nifty_desk'),
          'merge_ok' => __('Merge', 'nifty_desk'),
          'merge_cancel' => __('Cancel', 'nifty_desk'),
          'tickets_name_singular' => __('Ticket', 'nifty_desk'),
          'tickets_name_plural' => __('Tickets', 'nifty_desk')
        );
        wp_localize_script('nifty-desk-dashboard', 'nifty_desk_dashboard_strings', $nifty_desk_dashboard_js_strings);        

    }

}

/**
 * Loads Nifty Desk Front End Styles and Scripts
 *
 * @return void
*/
function nifty_desk_user_styles() {
    if (is_single() && get_post_type() == 'nifty_desk_tickets') {
        wp_register_style('nifty_desk_user_styles', plugins_url('/css/nifty-desk.css', __FILE__));
        wp_enqueue_style('nifty_desk_user_styles', get_stylesheet_uri());

        wp_register_script('nifty-desk-form-validation', plugins_url('js/jquery.form-validator.min.js', __FILE__), array('jquery'), '', true);
        wp_enqueue_script('nifty-desk-form-validation');
        wp_register_script('nifty-desk-js', plugins_url('js/nifty_desk_frontend.js', __FILE__), array('jquery'), '', true);
        wp_enqueue_script('nifty-desk-js');
    }
}

add_action('wp_enqueue_scripts', 'nifty_desk_user_styles');
/**
 * Nifty Desk Head Handling/Processing (Handles admin actions)
 *
 * @return void
*/
function nifty_desk_wp_head() {

    


        @session_start();
        // post data handling

        global $nifty_desk_success;
        global $nifty_desk_error;

        /* move to activation hook */
        if (!get_option("nifty_desk_default_assigned_to")) {
            $super_admins = get_super_admins();
            $user = get_user_by('slug', $super_admins[0]);
            if(is_object($user))
            {
                add_option('nifty_desk_default_assigned_to', $user->ID);
            }
        }





        if (isset($_POST['nifty_desk_save_settings'])) {

            if ( ! empty( $_POST ) && check_admin_referer( 'nifty_desk_save_admin_settings_basic', 'nifty_desk_security' ) && current_user_can( 'manage_options' ) ) {
        	
        		$data=get_option('nifty_desk_settings');
        		
        		
        		
                $nifty_desk_settings = array();

                if (isset($_POST['nifty_desk_settings_notify_new_tickets'])) { $nifty_desk_settings['nifty_desk_settings_notify_new_tickets'] = sanitize_text_field( $_POST['nifty_desk_settings_notify_new_tickets'] ); } else { $nifty_desk_settings['nifty_desk_settings_notify_new_tickets'] = 0; }
                if (isset($_POST['nifty_desk_settings_notify_new_responses'])) { $nifty_desk_settings['nifty_desk_settings_notify_new_responses'] = sanitize_text_field($_POST['nifty_desk_settings_notify_new_responses']); } else { $nifty_desk_settings['nifty_desk_settings_notify_new_responses'] = 0; }
                if (isset($_POST['nifty_desk_settings_allow_html'])) { $nifty_desk_settings['nifty_desk_settings_allow_html'] = sanitize_text_field($_POST['nifty_desk_settings_allow_html']); } else { $nifty_desk_settings['nifty_desk_settings_allow_html'] = 0; }
                if (isset($_POST['nifty_desk_settings_dashboard_folded'])) { $nifty_desk_settings['nifty_desk_settings_dashboard_folded'] = sanitize_text_field($_POST['nifty_desk_settings_dashboard_folded']); } else { $nifty_desk_settings['nifty_desk_settings_dashboard_folded'] = 0; }
                if (isset($_POST['nifty_desk_settings_thank_you_text'])) { $nifty_desk_settings['nifty_desk_settings_thank_you_text'] = sanitize_text_field($_POST['nifty_desk_settings_thank_you_text']); } else { $nifty_desk_settings['nifty_desk_settings_thank_you_text'] = __('Thank you for submitting your support ticket. One of our agents will respond as soon as possible.', 'nifty_desk'); }
                if (isset($_POST['nifty_desk_settings_allow_priority'])) { $nifty_desk_settings['nifty_desk_settings_allow_priority'] = sanitize_text_field($_POST['nifty_desk_settings_allow_priority']); } else { $nifty_desk_settings['nifty_desk_settings_allow_priority'] = 0; }
                
                if (isset($_POST['nifty_desk_settings_default_priority'])) { $nifty_desk_settings['nifty_desk_settings_default_priority'] = sanitize_text_field( $_POST['nifty_desk_settings_default_priority'] ); } else { $nifty_desk_settings['nifty_desk_settings_default_priority'] = 0; }
        		
                if (isset($_POST['nifty_desk_settings_notify_status_change'])) { $nifty_desk_settings['nifty_desk_settings_notify_status_change'] = sanitize_text_field($_POST['nifty_desk_settings_notify_status_change']); } else { $nifty_desk_settings['nifty_desk_settings_notify_status_change'] = 0; }

                if (isset($_POST['cb_settings_enable_file_uploads'])) { $nifty_desk_settings['enable_file_uploads'] = 1; } else { $nifty_desk_settings['enable_file_uploads'] = 0; }
                if (isset($_POST['nifty_desk_display_legacy_tickets'])) { $nifty_desk_settings['nifty_desk_display_legacy_tickets'] = 1; } else { $nifty_desk_settings['nifty_desk_display_legacy_tickets'] = 0; }

                if (isset($_POST['rb_nifty_desk_mailing_system_selection'])) { $nifty_desk_settings['rb_nifty_desk_mailing_system_selection'] = sanitize_text_field( $_POST['rb_nifty_desk_mailing_system_selection'] ); }
                if (isset($_POST['nifty_desk_smtp_host_setting_php_mailer'])) { $nifty_desk_settings['nifty_desk_smtp_host_setting_php_mailer'] = sanitize_text_field( $_POST['nifty_desk_smtp_host_setting_php_mailer'] ); }
                if (isset($_POST['nifty_desk_smtp_username_setting_php_mailer'])) { $nifty_desk_settings['nifty_desk_smtp_username_setting_php_mailer'] = sanitize_text_field( $_POST['nifty_desk_smtp_username_setting_php_mailer'] ); }
                if (isset($_POST['nifty_desk_smtp_password_setting_php_mailer'])) { $nifty_desk_settings['nifty_desk_smtp_password_setting_php_mailer'] = sanitize_text_field( $_POST['nifty_desk_smtp_password_setting_php_mailer'] ); }
                if (isset($_POST['nifty_desk_smtp_port_setting_php_mailer'])) { $nifty_desk_settings['nifty_desk_smtp_port_setting_php_mailer'] = sanitize_text_field( $_POST['nifty_desk_smtp_port_setting_php_mailer'] ); }
                if (isset($_POST['nifty_desk_smtp_encryption_setting_php_mailer'])) { $nifty_desk_settings['nifty_desk_smtp_encryption_setting_php_mailer'] = sanitize_text_field( $_POST['nifty_desk_smtp_encryption_setting_php_mailer'] ); }


                $nifty_desk_settings = apply_filters("nifty_desk_save_settings_hook", $nifty_desk_settings);

                update_option('nifty_desk_settings', $nifty_desk_settings);
                echo "<div class='updated'>";
                _e("Your settings have been saved.", "nifty_desk");
                echo "</div>";

            }

    }


    if (isset($_POST['nifty_desk_send_feedback'])) {

         if ( ! empty( $_POST ) && check_admin_referer( 'nifty_desk_save_admin_settings_basic', 'nifty_desk_security' ) && current_user_can( 'manage_options' ) ) {
    	
    		if(function_exists('send_automated_emails')) {
    			$mail_result = send_automated_emails("support@niftydesk.org", "Support Tickets Plugin feedback", "Name: " . esc_attr($_POST['nifty_desk_feedback_name']) . " <br/><br/> " . "Email: " . esc_attr($_POST['nifty_desk_feedback_email']) . " <br/><br/> " . "Website: " . esc_attr($_POST['nifty_desk_feedback_website']) . " <br/><br/> " . "Feedback:" . esc_attr($_POST['nifty_desk_feedback_feedback']));
    		}
    		else {
    			$mail_result = false;		
    		}
    		
    		
            if ($mail_result===true) {
                echo "<div id=\"message\" class=\"updated\"><p>" . __("Thank you for your feedback. We will be in touch soon", "nifty_desk") . ".</p></div>";
            } else {

                if (function_exists('curl_version')) {
                    $request_url = "http://www.niftydesk.org/apif-support-tickets/rec_feedback.php";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $request_url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
                    curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $output = curl_exec($ch);
                    curl_close($ch);
                    echo "<div id=\"message\" class=\"updated\"><p>" . __("Thank you for your feedback. We will be in touch soon", "nifty_desk") . "</p></div>";
                } else {
                    echo "<div id=\"message\" class=\"error\">";
                    echo "<p>" . __("There was a problem sending your feedback. Please log your feedback on ", "nifty_desk") . "<a href='http://niftydesk.org/support-desk' target='_BLANK'>http://niftydesk.org/support-desk</a></p>";
                    echo "</div>";
                }
            }

        }
    }
}

/**
 * Returns Error Log
 *
 * @return string
*/
function nifty_desk_return_error_log() {
    $upload_dir = wp_upload_dir();
    $fh = @fopen($upload_dir['basedir'].'/nifty_desk' . "/nifty_desk_log.txt", "r");
    $ret = "";
    if ($fh) {
        for ($i = 0; $i < 10; $i++) {
            $visits = fread($fh, 4096);
            $ret .= $visits;
        }
    } else {
        $ret .= __("No errors to report on", "nifty_desk");
    }
    return $ret;
}

/**
 * Outputs (echo) current objects error
 *
 * @param  object $data
 * @return void
*/
function nifty_desk_return_error($data) {
    echo "<div id=\"message\" class=\"error\"><p><strong>" . $data->get_error_message() . "</strong><blockquote>" . $data->get_error_data() . "</blockquote></p></div>";
    nifty_desk_write_to_error_log($data);
}

/**
 * Writes to the Mail Log
 *
 * @param  string $data
 * @return void
*/
function nifty_desk_write_to_mail_log($data) {
    $upload_dir = wp_upload_dir();
    if (nifty_desk_error_directory()) {
        if (is_multisite()) {
            $content = "\r\n" . date("Y-m-d H:i:s", current_time('timestamp')) . ": " . $data;
            $fp = fopen($upload_dir['basedir'] . '/nifty_desk' . "/nifty_desk_mail_log.txt", "a+");
            if ($fp) { 
                @fwrite($fp, $content);
            }
        } else {
            $content = "\r\n" . date("Y-m-d H:i:s", current_time('timestamp')) . ": " . $data;
            $fp = fopen($upload_dir['basedir'] . '/nifty_desk' . "/nifty_desk_mail_log.txt", "a+");
            if ($fp) {
                @fwrite($fp, $content);
            }
        }
    }

}

/**
 * Writes to the Error Log
 *
 * @param  object $data
 * @return void
*/
function nifty_desk_write_to_error_log($data) {
    $upload_dir = wp_upload_dir();
    if (nifty_desk_error_directory()) {
        if (is_multisite()) {
            $content = "\r\n" . date("Y-m-d H:i:s", current_time('timestamp')) . ": " . $data->get_error_message() . " -> " . $data->get_error_data();
            $fp = fopen($upload_dir['basedir'] . '/nifty_desk' . "/nifty_desk_log.txt", "a+");
            fwrite($fp, $content);
        } else {
            $content = "\r\n" . date("Y-m-d H:i:s", current_time('timestamp')) . ": " . $data->get_error_message() . " -> " . $data->get_error_data();
            $fp = fopen($upload_dir['basedir'] . '/nifty_desk' . "/nifty_desk_log.txt", "a+");
            fwrite($fp, $content);
        }
    }

    error_log(date("Y-m-d H:i:s", current_time('timestamp')) . ": " . nifty_desk_PLUGIN_NAME . ": " . $data->get_error_message() . "->" . $data->get_error_data());
}

/**
 * Checks if log path/file  exists, if not create it
 *
 * @return boolean (force true)
*/
function nifty_desk_error_directory() {
    $upload_dir = wp_upload_dir();

    if (is_multisite()) {
        if (!file_exists($upload_dir['basedir'] . '/nifty_desk')) {
            wp_mkdir_p($upload_dir['basedir'] . '/nifty_desk');
            $content = "Log created";
            $fp = @fopen($upload_dir['basedir'] . '/nifty_desk' . "/nifty_desk_log.txt", "w+");
            if ($fp) {
                @fwrite($fp, $content);
            }
        }
    } else {
        if (!file_exists($upload_dir['basedir'].'/nifty_desk')) {
            wp_mkdir_p($upload_dir['basedir'].'nifty_desk');
            $content = "Log created";
            $fp = @fopen($upload_dir['basedir'] . '/nifty_desk' . "/nifty_desk_log.txt", "w+");
            if ($fp) {
                @fwrite($fp, $content);
            }
        }
    }
    return true;
}

/**
 * Returns response data for a specific ticket ID
 *
 * @param int $post_id
 * @return object (false if not found)
*/
function nifty_desk_get_response_data($post_id) {
    $data = get_post($post_id);
    if (isset($data) && $data) {
        return $data;
    } else {
        return false;
    }
}

/**
 * Ajax Action Hander
 *
 * @return void
*/
function nifty_desk_action_callback() {

    /* encoding error fixed 3 march 2015 - albert */
    /* url_decode() shouldn't be used */
    
    
    
    global $wpdb;
    $check = check_ajax_referer('nifty_desk', 'security');

    if ($check == 1) {

        if ($_POST['action'] == "nifty_desk_save_response") {
            if (!isset($_POST['parent'])) {
                return false;
            }


            $parent_id = sanitize_text_field( $_POST['parent'] );
            $content_current = sanitize_text_field( $_POST['content'] );
            $title = sanitize_text_field( $_POST['title'] );
            $author = sanitize_text_field( $_POST['author'] );


           
            /*base 64 file upload*/


            if(isset($_POST['base_64_data'])&&isset($_POST['file_name'])&&isset($_POST['file_mime_type'])) {
                
                if(trim($content_current)==='') {
                    $content_current=' <span style="font-style:italic;"> - '.__(' File uploaded ','nifty_desk').' - </span>';
                }
                
                
                $posted_full_base_64 = $_POST['base_64_data'];
                $posted_mime_type = sanitize_text_field( $_POST['file_mime_type'] );
                $posted_file_name = sanitize_text_field( $_POST['file_name'] );

                $upload_dir = wp_upload_dir();
                $upload_path = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;
                $base64_array=explode(';base64,',$posted_full_base_64);
                $file_data_as_string=$base64_array[1];

                if($posted_mime_type==='application/pdf'||$posted_mime_type==='application/zip'||$posted_mime_type==='application/x-zip-compressed'||$posted_mime_type==='image/tiff'||$posted_mime_type==='image/png'||$posted_mime_type==='image/x-png'||$posted_mime_type==='image/jpeg'||$posted_mime_type==='image/gif')
                {
                    $decoded_base64_string = base64_decode($file_data_as_string);
                    $hashed_filename = md5( $posted_file_name . microtime() ) . '_' . $posted_file_name;
                    $image_upload = file_put_contents( $upload_path . $hashed_filename, $decoded_base64_string );

                    /* create my own fake php $_FILES array*/
                    $overload_php_files_array = array();
                    $overload_php_files_array['error'] = '';
                    $overload_php_files_array['tmp_name'] = $upload_path . $hashed_filename;
                    $overload_php_files_array['name'] = $hashed_filename;
                    $overload_php_files_array['type'] = $posted_mime_type;
                    $overload_php_files_array['size'] = filesize( $upload_path . $hashed_filename );

                    /*pass the fake $_FILES array to the wp_handle_sideload function as the first parameter - this is the format this wp function expects*/
                    $file_save_result = wp_handle_sideload($overload_php_files_array, array( 'test_form' => false));


                    $file_name_path=$file_save_result['file'];
                    $file_name_url=$file_save_result['url'];

                    $wp_filetype = wp_check_filetype(basename($file_name_url), null );
                    $post_mime_type=$wp_filetype['type'];
                    $post_title=preg_replace('/\.[^.]+$/', '', basename($file_name_url));



                    $attachment = array(
                        'post_mime_type' => $post_mime_type,
                        'post_title' => $post_title,
                        'post_content' => '',
                        'post_status' => 'inherit',
                        'guid'=>$file_name_url,
                        'post_parent'=>$parent_id
                    );
                    wp_insert_attachment( $attachment, $file_name_path );
                }
            }

            /* check if we allow for HTML or not */
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

            update_post_meta($post_id, '_response_parent_id', $parent_id);
            /* update parent's 'last updated' time */
            update_post_meta( $parent_id, 'ticket_last_updated', current_time('timestamp') );


            $ticket_array = array(
                'ticket_id' => $post_id,
                'parent_id' => $parent_id,
                'content' => $content,
                'userid' => $author
            );
            do_action("nifty_desk_after_create_response",$ticket_array);            

            
            nifty_desk_notification_control('response', $parent_id, get_current_user_id(),false,false,$content,false,$post_id);
            
        } else if ($_POST['action'] == "nifty_desk_save_note") {
            if (!isset($_POST['parent'])) {
                return false;
            }

            
             /* encoding error fixed 3 march 2015 - albert */
             /* url_decode() shouldn't be used */
            
            
            $parent_id = sanitize_text_field( $_POST['parent'] );
            $content_current = sanitize_text_field( $_POST['content'] );
            $title = sanitize_text_field( $_POST['title'] );
            $author = sanitize_text_field( $_POST['author'] );


            /* check if we allow for HTML or not */
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
        }
    }

    die(); // this is required to return a proper result
}

/**
 * Filters ticket content
 * if HTML is allowed, dont strip content
 *
 * @param string $content
 * @return string
*/
function nifty_desk_check_for_html($content) {

    if (current_user_can('edit_nifty_desk_ticket', array(null))) {
        /* they're a support agent, they can do what they want */
        return nl2br(($content));
    } else {
        $nifty_desk_settings = get_option("nifty_desk_settings");
        if (!isset($nifty_desk_settings['nifty_desk_settings_allow_html']) || intval($nifty_desk_settings['nifty_desk_settings_allow_html']) == 0) {
            return nl2br(strip_tags($content));
        } else {
            return nl2br($content);
        }
    }
}


/**
 * Adds quick links to the left panel of the dashboard
 */
add_action("nifty_desk_modern_tickets_left_column_after_wrapper","nifty_desk__hook1_modern_tickets_left_column_after",9);
function nifty_desk__hook1_modern_tickets_left_column_after() {
    echo "<hr />";
    echo "<div class='nifty_desk_db_left_column_inner'>";
    echo "<strong class='nd_quicklinks'>".__("Quick links","nifty_desk")."</strong>";
    echo "<ul>";
    $submit_ticket_page = get_option("nifty_desk_submit_ticket_page");
    if ($submit_ticket_page) {
        $stlink = get_permalink($submit_ticket_page);
        echo "<li class='nd_ql_li'><a class='nd_ql_li_a' href='".$stlink."' target='_BLANK'>".__("Submit a ticket","nifty_desk")."</a> " . __("(for users)","nifty_desk") . "</li>";
    }
    
    echo "</ul>";
    echo "";
    echo "</div>";
}


/**
 * Handles Notifications to Users 
 *
 * @param string  $type (Response, Ticket, etc)
 * @param int     $post_id       Post ID
 * @param int     $userid        User ID
 * @param string  $email         Users Email
 * @param string  $password      Password
 * @param string  $response_text Response
 * @param int     $channel       Channel ID
 * @param int     $response_id   Response ID
 * @param array   $attachments   Attached files
 *
 * @return boolean
*/
function nifty_desk_notification_control($type, $post_id, $userid, $email = false, $password = false,$response_text='',$channel = false,$response_id = false,$attachments = false) {
    

    
    
	global $wpdb;
    $nifty_desk_settings = get_option("nifty_desk_settings");
    


    $post_data=get_post( $post_id, ARRAY_A);
    $ticket_content=$post_data['post_content'];
    $ticket_content=str_replace('<br/>',"\r",$ticket_content);
    $ticket_content=str_replace('<br>',"\r",$ticket_content);
    $ticket_content=strip_tags($ticket_content);
    $ticket_content=html_entity_decode($ticket_content,ENT_QUOTES);
    
    $response_text=str_replace('<br/>',"\r",$response_text);
    $response_text=str_replace('<br>',"\r",$response_text);
    $response_text=strip_tags($response_text);
    $response_text=html_entity_decode($response_text,ENT_QUOTES);

	

	
	
    //echo "notification control".$type.$post_id;
    if ($type == 'response') {
    	
		
		/*add the latest response to the notification e-mail*/
		
		
		
		
        /* response */

        if ($nifty_desk_settings['nifty_desk_settings_notify_new_responses'] == "1") {
            /* get user who the post is assigned to */
            $assigned_ID = get_post_meta($post_id,"ticket_assigned_to",true);
            $user_details = get_user_by('id', intval($assigned_ID));

            /* first figure out who sent the response */

            $post_data = get_post($post_id);

            $post_user = $post_data->post_author;

            /* get a list of everyone involved in this ticket */
            $meta_data = nifty_desk_get_post_meta_all($post_id);
            $notification_array = array();
            $notification_array[$post_user] = get_userdata($post_user)->user_email;
            

            /* get the email address of who this ticket is assigned to and add it to the notification array. */
            if ($user_details) {
                $assigned_to_email = $user_details->user_email;
                $notification_array[$assigned_ID] = $assigned_to_email;
            }

            foreach ($meta_data as $response) {
                $response_data = get_post($response->post_id);
                $response_user = $response_data->post_author;
                if (isset($notification_array[$response_user])) {

                } else {
                    $notification_array[$response_user] = get_userdata($response_user)->user_email;
                }
            }


            $email_of_person_sending_the_response = get_userdata($userid)->user_email;

             
            
            
            
            $notification_array = array_unique($notification_array);


            $notification_array = apply_filters("nifty_filter_notification_email_array",$notification_array,$channel);

            $ticket_reference = get_post_meta($post_id,"ticket_reference",true);
            foreach ($notification_array as $email_item) {
                if($email_item!==$email_of_person_sending_the_response) {


					if(isset($response_text) && $response_text!=="") {
						$message_text= __("There is a new response to the support ticket titled", "nifty_desk") ." <br/><br/> ". $post_data->post_title . " <br/><br/> ".__("Ticket content","nifty_desk").": <br/><br/> ".$ticket_content." <br/><br/> ".__("Response text","nifty_desk").": <br/><br/> ".$response_text." <br/><br/> ". __("Follow this link to view the reply", "nifty_desk") . " " . get_permalink($post_id);
					}
					else {
						$message_text= __("There is a new response to the support ticket titled", "nifty_desk") ." <br/><br/> ". $post_data->post_title . " <br/><br/> ".__("Ticket content","nifty_desk").": <br/><br/> ".$ticket_content." <br/><br/> ". __("Follow this link to view the reply", "nifty_desk") . " " . get_permalink($post_id);
					}


                    /* check if we originally got this via an email, if yes, then we need to respond directly to this mail */
                    $messageid = get_post_meta($post_id, '_ticket_reference_receipt_id', true);


                    if ($email && $password) {
                        $array = array(
                            'post_id' => $post_id,
                            'response_id' => $response_id,
                            'username' => $email,
                            'password' => $password,
                            'ticket_reference' => $ticket_reference
                            
                        );
                    } else {
                        $array = array(
                            'post_id' => $post_id,
                            'response_id' => $response_id,
                            'ticket_reference' => $ticket_reference
                        );
                    }
                    $content = apply_filters("nifty_desk_email_body_build","",$array);
                    if ($messageid) {
                        if (function_exists("nifty_desk_pro_send_response_notification")) {
                            return nifty_desk_pro_send_response_notification($email_item,$response_id,$post_data,$messageid,$content,$ticket_reference,$channel,$attachments);
                        } else {
                            if(function_exists('send_automated_emails')) {
                                return send_automated_emails($email_item,  __("New response", "nifty_desk") . " (" . $post_data->post_title . ")", $content,false,$messageid,$channel,$response_id,$attachments);   
                            }
                        }
                    } else {
                        if(function_exists('send_automated_emails')) {
                            return send_automated_emails($email_item,  __("New response", "nifty_desk") . " (" . $post_data->post_title . ")", $content,false,$messageid,$channel,$response_id,$attachments);   
                            
                        }
                    }
					
					
                }
                
                
            }
            return true;
            
        } else {
            return true;
        }
    } else if ($type == 'ticket') {


        /* new ticket */
        extract($_POST);

        /* send an email to the owner of the ticket */
        if(is_object(get_userdata($userid))) {
            $user_email = get_userdata($userid)->user_email;
        } else {
            $user_email = null;
        }

        $post = get_post($post_id);

        if ($user_email == null && isset($nifty_desk_user_email_address)) {

            $user_email = $nifty_desk_user_email_address;

        }


        if (isset($user_email)) {

            $custom_fields = get_post_custom($post_id);
            if (!isset($custom_fields['ticket_reference'])) {
                $ticket_reference = md5($post_id . $userid);
                add_post_meta($post_id, 'ticket_reference', $ticket_reference, true);
            } else {
                $ticket_reference = $custom_fields['ticket_reference'][0];
            }

            if (isset($nifty_desk_settings['nifty_desk_username']) && function_exists('nifty_desk_pro_init')) {
                $admin_address = $nifty_desk_settings['nifty_desk_username'];
            } else {
                $admin_address = get_option('admin_email');
            }

            
			$headers_array['from']['name']=get_bloginfo('name');
			$headers_array['from']['address']=$admin_address;
			$headers_array['reply_to']['name']=get_bloginfo('name');
			$headers_array['reply_to']['address']=$admin_address;

            


            /**
             * Build the contents of the email in HTML using the templates
             */
            if ($email && $password) {
                $array = array(
                    'post_id' => $post_id,
                    'username' => $email,
                    'password' => $password,
                    'ticket_reference' => $ticket_reference
                    
                );
            } else {
                $array = array(
                    'post_id' => $post_id,
                    'ticket_reference' => $ticket_reference
                );
            }
            $content = apply_filters("nifty_desk_email_body_build","",$array);


            if(function_exists('send_automated_emails')) {
                /* notify the ticket owner of us receiving his ticket */

                $confirm_send = apply_filters("nifty_filter_confirm_send_to_email",$user_email,$channel,true);

                if ($confirm_send) {
                    /* did this come from an email? if yes, get the email ID and respond to it directly. */
                    $messageid = get_post_meta($post_id, '_ticket_reference_receipt_id', true);

                    /**
                     * Send the auto reply
                     */
                    $checker = send_automated_emails($user_email,$post->post_title, $content,$headers_array,$messageid,$channel,$post_id); 
                }
                
            }



        }


        /**
         * Check if we need to send to all agents
         */
        if (isset($nifty_desk_settings['nifty_desk_settings_notify_all_agents']) && $nifty_desk_settings['nifty_desk_settings_notify_all_agents'] == "1") {
            /* Notify all agents function must go here */
            if (function_exists('nifty_desk_pro_activate')) {
                nifty_desk_notify_all_agents($post_id,$channel,$attachments);
            }
        } else {
            /* send an email to the auto assigned support member */
            if (isset($nifty_desk_settings['nifty_desk_settings_notify_new_tickets']) && $nifty_desk_settings['nifty_desk_settings_notify_new_tickets'] == "1") {
                $meta_data = get_post_custom_values('ticket_assigned_to', $post_id);
                $user_details = get_user_by('id', $meta_data[0]);
                
                if (isset($user_details->user_email)) {
					if(function_exists('send_automated_emails')) {
						send_automated_emails($user_details->user_email, __("New support ticket:" ,"nifty_desk") . " " . $post->post_title . " <br/><br/> ".__("Ticket content","nifty_desk").": <br/><br/> ".$ticket_content." <br/><br/> ", __("A new support ticket has been received. To access this ticket, please follow this link:", "nifty_desk") . " " . get_permalink($post_id),null,false,$channel,$post_id,$attachments);
						
					}
                	
                }
            }
        }

    } else if ($type == 'agent_change') {

        if(isset($nifty_desk_settings['nifty_desk_settings_notify_agent_change']) && $nifty_desk_settings['nifty_desk_settings_notify_agent_change'] == "1"){
            $post_data = get_post($post_id);
            $user_details = get_user_by('id', $userid);
            $user_email = $user_details->user_email;
			
			if(function_exists('send_automated_emails'))
			{
				send_automated_emails($user_email, __("New Ticket Assigned", "nifty_desk") . " (" . $post_data->post_title . ")", __("A new ticket has been assigned to you. ", "nifty_desk") . " \"" . $post_data->post_title . "\" <br/><br/> ".__("Ticket content","nifty_desk").": <br/><br/> ".$ticket_content." <br/><br/> ". __("Follow this link to view the ticket", "nifty_desk") . " " . get_page_link($post_id),null,false,$channel,$post_id);	
			}
			            
       }

    } else if ($type == 'status_change') {

        if(isset($nifty_desk_settings['nifty_desk_settings_notify_status_change']) && $nifty_desk_settings['nifty_desk_settings_notify_status_change'] == "1")
        {

            $post_data = get_post($post_id);
            $post_status = get_post_meta($post_id, 'ticket_status', true);

            if($post_status == 0){
                /* Open */
                $stat = __('Open', 'nifty_desk');
            } else if ($post_status == 1) {
                /* Solved */
                $stat = __('Solved', 'nifty_desk');
            } else if ($post_status == 9) {
                /* New */
                $stat = __('New', 'nifty_desk');
            } else if ($post_status == 2) {
                /* Closed */
                $stat = __('Closed', 'nifty_desk');
            } else if ($post_status == 3) {
                /* Pending */
                $stat = __('Pending', 'nifty_desk');
            } else {
                /* Unknown */
                $stat = __('Unknown', 'nifty_desk');
            }

            $user_details = get_user_by('id', $userid);
            $user_email = $user_details->user_email;

            $channel = get_post_meta($post_id,"ticket_channel_id",true);

			if(function_exists('send_automated_emails'))
			{
                $body =     __("Your Support Ticket ", "nifty_desk") . " *" .
                            $post_data->post_title . "* ".__("has been marked as ","nifty_desk").$stat." <br/><br/> " .
                            __("Ticket content","nifty_desk").": <br/><br/> ".$ticket_content." <br/><br/> ".
                            __("Follow this link to view the ticket", "nifty_desk") . " " .
                            get_page_link($post_id);

				send_automated_emails($user_email, __("Support Ticket Status Changed", "nifty_desk") . " (" . $post_data->post_title . ")", $body,"",false,false,$channel,$post_id);
			}
			
        }

    }
    elseif($type==='customer_satisfaction_survey_send_out')
    {

        $post_status = get_post_meta($post_id, 'ticket_status', true);
        $user_details = get_user_by('id', $userid);
        $user_email = $user_details->user_email;




        if($post_status == 1 ||$post_status==2)
        {

            if((isset($nifty_desk_settings['enable_sending_of_customer_satisfaction_surveys'])&&$nifty_desk_settings['enable_sending_of_customer_satisfaction_surveys']==='true')&&(function_exists('nifty_desk_pro_activate')&&defined('nifty_desk_CSS_CUSTOMER_SATISFACTION_SURVEY_ACTIVE')))
            {
                $query_customer_satisfaction_survey='SELECT * FROM '.nifty_desk_CSS_CUSTOMER_SATISFACTION_SURVEYS.';';
                $available_surveys=$wpdb->get_results($query_customer_satisfaction_survey);
                if(is_array($available_surveys)&&!empty($available_surveys))
                {
                    $survey_listing='';
                    $survey_listing.=__('Your ticket has been marked as closed. Please answer the following survey(s):','nifty_desk').'<br/>';
                    $survey_listing.='<ul>';
                    foreach($available_surveys as $survey)
                    {
                        $survey_id=$survey->id;
                        $survey_name=$survey->survey_name;
                        $ticket_id=$post_id;
                        $uid=$userid;
                        $permalink=get_permalink((integer)get_option('nifty_desk_survey_page'));
                        global $wp_rewrite;
                        if ($wp_rewrite->permalink_structure == '')
                            $survey_listing.='<li><a href="'.$permalink.'&survey_id='.$survey_id.'&uid='.$uid.'&ticket_id='.$ticket_id.'">'.$survey_name.'</a></li>';
                        else
                            $survey_listing.='<li><a href="'.$permalink.'?survey_id='.$survey_id.'&uid='.$uid.'&ticket_id='.$ticket_id.'">'.$survey_name.'</a></li>';


                    }

                    $survey_listing.='</ul>';
                }


                if(isset($survey_listing))
                {

                    if(function_exists('send_automated_emails'))
                    {
                        send_automated_emails($user_email, __('Customer satisfaction survey','nifty_desk'), $survey_listing,$post_id);
                    }
                }
            }
        }
    }
    else
    {
        return true;
    }
}

/**
 * Returns the 'Response Form' for a ticket
 *
 * @param int  $post_id  Ticket ID
 * @return string (HTML)
*/
function nifty_desk_append_response_form($post_id){
    $custom_data = get_post_custom($post_id);
    $post_data = get_post($post_id);

     $nifty_desk_content = "";

    if ($custom_data['ticket_status'][0] == "1") {
        $nifty_desk_content = "";
    } else {
        if (is_user_logged_in()) {
            $nifty_desk_content = "";

            $macros = "";
            if (function_exists("nifty_desk_pro_metabox_addin_macros") && current_user_can('edit_nifty_desk_ticket', $post_id)) {
                $macros = nifty_desk_pro_metabox_addin_macros(1);
            } 

            /*           
            if( function_exists( 'nifty_desk_pro_display_uploaded_files' ) ){
                $nifty_desk_content .= '
                    <tr>
                       <td>
                            '.__('Uploaded Files: ', 'nifty_desk').nifty_desk_pro_display_uploaded_files($ticket_id).' 
                       </td>
                    </tr>';
            }*/

            $nifty_desk_content .= "<div class='nifty_desk_response_form_container'>";
            $nifty_desk_content .=   "<form name='nifty_desk_add_response' method='POST' action='' enctype='multipart/form-data'>";
            $nifty_desk_content .=       "<input type='hidden' value='" . $post_id . "' name='nifty_desk_response_id' id='nifty_desk_response_id' />";
            $nifty_desk_content .=       "<input type='hidden' value='Reply to " . get_the_title() . "' name='nifty_desk_response_title' id='nifty_desk_response_title' />";

            $nifty_desk_content .=       "<div class='nifty_desk_row'>";
            $nifty_desk_content .=           "<div class='nifty_desk_col_12'>";
            $nifty_desk_content .=              "<textarea name='nifty_desk_response_text' id='nifty_desk_response_text'></textarea>";
            $nifty_desk_content .=           "</div>";
            $nifty_desk_content .=       "</div>";

            $nifty_desk_content .= $macros;

            $nifty_desk_content .=       "<div class='nifty_desk_row'>";
            $nifty_desk_content .=           "<div class='nifty_desk_col_12'>";
            $nifty_desk_content .=              "<input type='submit' value='" . __("Submit Response", "nifty_desk") . "' class='nifty_desk_response_form_button' />";
            $nifty_desk_content .=           "</div>";
            $nifty_desk_content .=       "</div>";
            
            $nifty_desk_content .=   "</form>";
            $nifty_desk_content .= "</div>";
                    
        } else {
            $nifty_desk_content = "<p><strong><a href=\"" . wp_login_url(get_permalink()) . "\">" . __("Log in", "nifty_desk") . "</a> " . __("or", "nifty_desk") . " <a href=\"" . wp_registration_url() . "\">" . __("register", "nifty_desk") . "</a> " . __("to submit a response.", "nifty_desk") . "</strong></p>";
        }
    }

    return $nifty_desk_content;
}

/**
 * Returns the ticket primary content
 *
 * @param int  $ticket_id  Ticket ID
 * @return string (HTML)
*/
function nifty_desk_append_content_of_ticket($ticket_id){
    $post_data = get_post($ticket_id);
    $author_data = get_userdata($post_data->post_author);

    $nifty_desk_content = "";

    $nifty_desk_content .= "<div class='nifty_desk_response_container'>";
    $nifty_desk_content .=  "<div class='nifty_desk_author_container'>";
    $nifty_desk_content .=      "<div class='nifty_desk_author_avatar'>" . get_avatar($author_data->user_email, '50') . "</div>";
    $nifty_desk_content .=       "<div class='nifty_desk_author_meta_container'>";
    $nifty_desk_content .=          "<span class=\"nifty_desk_response_user\">" . $author_data->display_name . "</span> | <span title=\"" . $post_data->post_date . "\" class=\"nifty_desk_response_time\">" . nifty_desk_time_elapsed_string(strtotime($post_data->post_date)) . "</span>";
    $nifty_desk_content .=        "</div>";
    $nifty_desk_content .=      "</div>";

    $nifty_desk_content .=      "<div class=\"nifty_desk_response_content\"><p>" . ($post_data->post_content) . "</p>";

    /*$nifty_desk_content .= "<div class='nifty_desk_post_box'>";
    if (nifty_desk_is_admin()) {
        $nifty_desk_content .= "<span class='nifty_desk_admin_edit_response_span'><a href='" . get_edit_post_link($post_id) . "' class='nifty_desk_button'>" . __("edit", "nifty_desk") . "</a></span> &nbsp;";
        $nifty_desk_content .= "<span class='nifty_desk_admin_delete_response_span'><a href='" . get_delete_post_link($post_id) . "' class='nifty_desk_button'>" . __("delete", "nifty_desk") . "</a></span>";
    }
    $nifty_desk_content .= '</div>';*/

    $nifty_desk_content .=      "</div>";
    $nifty_desk_content .= "</div>";


    return $nifty_desk_content;
}


/**
 * Returns the ticket responses content
 *
 * @param int  $ticket_id  Ticket ID
 * @return string (HTML)
*/
function nifty_desk_append_responses_to_ticket($ticket_id) {
    $meta_data = nifty_desk_get_post_meta_all($ticket_id);
    
    $nifty_desk_content = "";
    foreach ($meta_data as $response) {
        $nifty_desk_content .= nifty_desk_draw_response_box($response->post_id, $ticket_id);
    }

    return $nifty_desk_content;
}
add_filter('the_content', 'nifty_desk_content_control');


add_filter('next_post_link', 'nifty_desk_next_previous_fix');
add_filter('previous_post_link', 'nifty_desk_next_previous_fix');
/**
 * Checks if current post is 'nifty_desk_tickets' 
 *
 * @param $url (Unused)
 * @return string ("" empty if page matches)
*/
function nifty_desk_next_previous_fix($url) {
    if (get_post_type() == "nifty_desk_tickets") {
        return "";
    }
}

/** 
 * DEPRECATED - In Progress
 * Handles content output of a ticket. This has been replaced with the new template view system
 *
 * @param string $content
 * @return string
*/
function nifty_desk_content_control($content) {
    global $post;
    $nifty_desk_content = "";



    if (!isset($post)) {
        return $content;
    }

    if (get_post_type($post) == "nifty_desk_tickets") {
        /* is single page? */

        if (!is_single() && !is_admin() && !is_archive()) {
            return $content;
        } else {
            $is_public = get_post_meta($post->ID, 'ticket_public', true);

            $ticket_status = get_post_meta($post->ID, 'ticket_status', true);

            /*
             * 0 - Open
             * 1 - Solved
             * 3 - Pending
             * 9 - New
             */
            if ($ticket_status == '0' || $ticket_status == '3') {
                /* Open Ticket */
                $current_user = wp_get_current_user();
                $post_details = get_post($post->ID);

                $author_id = $post_details->post_author;
                $author_details = get_user_by('id', $author_id);
                /* come here */

                if(function_exists('nifty_desk_display_linked_files_metabox'))
                {
                	$nifty_desk_attached_files = nifty_desk_display_linked_files_metabox();
                }
                else
                {
                	$nifty_desk_attached_files = '';
                }




                if($current_user->ID == 0){
                    if($is_public){
                        $show_ticket = true;
                    } else {
                        $show_ticket = false;
                    }
                    $messgae = "";
                } else if ($current_user->ID == $author_details->ID || current_user_can('edit_nifty_desk_tickets', array(null))){
                    $show_ticket = true;
                } else {
                    if($is_public){
                        $show_ticket = true;
                    } else {
                        $show_ticket = false;
                    }
                }
				
                if ($show_ticket) {
                    $content = $content;
					if(!is_search()){
                    	$content = $content .$nifty_desk_attached_files. nifty_desk_append_responses_to_ticket(get_the_ID());
					} 
                } else {
                    $nifty_desk_content .= "<span class='nifty_desk_pending_approval_span'>" . __("This support ticket has been marked as private.", "nifty_desk") . "</span>";
                    $nifty_desk_content .= wp_login_form();
                    $content = $nifty_desk_content;
                }
            } else if ($ticket_status == '1') {
                /* Solved Ticket */
                $current_user = wp_get_current_user();
                $post_details = get_post($post->ID);

                $author_id = $post_details->post_author;
                $author_details = get_user_by('id', $author_id);

                if($current_user->ID == 0){
                    if($is_public){
                        $show_ticket = true;
                    } else {
                        $show_ticket = false;
                    }
                    $messgae = "";
                } else if ($current_user->ID == $author_details->ID || current_user_can('edit_nifty_desk_tickets', array(null))){
                    $show_ticket = true;
                } else {
                    if($is_public){
                        $show_ticket = true;
                    } else {
                        $show_ticket = false;
                    }
                }

                if ($show_ticket) {
                    $nifty_desk_content .= "<span class='nifty_desk_pending_approval_span'>" . __("This support ticket has been marked as solved.", "nifty_desk") . "</span>";
                    $content = $nifty_desk_content . $content;
					if(!is_search()){
                    	$content = $content . nifty_desk_append_responses_to_ticket(get_the_ID());
					}
                } else {
                    $nifty_desk_content .= "<span class='nifty_desk_pending_approval_span'>" . __("This support ticket has been marked as solved.", "nifty_desk") . "</span>";
                    $content = $nifty_desk_content;
                }
            } else if ($ticket_status == '9') {
                /* Pending Ticket */
                $current_user = wp_get_current_user();
                $post_details = get_post($post->ID);

                $author_id = $post_details->post_author;
                $author_details = get_user_by('id', $author_id);

                if($current_user->ID == 0){
                    if($is_public){
                        $show_ticket = true;
                    } else {
                        $show_ticket = false;
                    }
                    $messgae = "";
                } else if ($current_user->ID == $author_details->ID || current_user_can('edit_nifty_desk_tickets', array(null))){
                    $show_ticket = true;
                } else {
                    if($is_public){
                        $show_ticket = true;
                    } else {
                        $show_ticket = false;
                    }
                }




                if(isset($_SESSION['file_upload_failed']))
                {
                    echo $_SESSION['file_upload_failed'].'<br/>';
                    unset($_SESSION['file_upload_failed']);
                }


                if ($show_ticket) {
                    $nifty_desk_content .= "<span class='nifty_desk_pending_approval_span'>" . __("This support ticket is pending approval.", "nifty_desk") . "</span>";
                    $content = $nifty_desk_content . $content;
                    $content = $content;
                    if(!is_search()){
                        $content = $content . nifty_desk_append_responses_to_ticket(get_the_ID());
                    }
                } else {
                    $nifty_desk_content .= "<span class='nifty_desk_pending_approval_span'>" . __("This support ticket is pending approval.", "nifty_desk") . "</span>";
                    $content = $nifty_desk_content;
                }
            }
        }
    }

    return $content;
}

//
//    if (get_post_type( $post ) == "nifty_desk_tickets") {
//        /* is single page? /*
//         *
//         */
//        if (!is_single() && !is_admin()) {
//            return $content;
//        } else {
//            $post_id = get_the_ID();
//            $custom = get_post_custom($post_id);
//            if ($custom['ticket_status'][0] == "9") {
//
//                /* check if there is a user logged in */
//                $current_user = wp_get_current_user();
//                if (!$current_user->ID) {
//                    /* show 404 template as the user is not logged in and it is pending */
//                    return __("This support ticket is marked as private or is pending approval.","nifty_desk");
//                }
//                else {
//                /* check if it's the owner of the ticket */
//                    $show_content = false;
//                    if ((get_the_author_meta('ID') == $current_user->ID)) {
//                        /* this is the user that posted the ticket */
//                        $show_content = true;
//                    } else {
//                        /* let's check if the current user has capabilitie to see tickets */
//                        if (current_user_can('edit_nifty_desk_ticket')) {
//                            $show_content = true;
//                        } else {
//                            $show_content = false;
//                        }
//
//                    }
//
//                    if ($show_content) {
//                        $nifty_desk_content .= "<span class='nifty_desk_pending_approval_span'>".__("This support ticket is pending approval.","nifty_desk")."</span>";
//                        $content = $content.$nifty_desk_content;
//                    }
//                }
//            } else if ($custom['ticket_status'][0] == "0") {
//                /* open ticket */
//
//                /* can others see the ticket or not? - pro version only */
//
//                if (function_exists("nifty_desk_check_if_public")) {
//                    $show_content = false;
//                } else {
//
//
//                    $show_content = false;
//                    if ((get_the_author_meta('ID') == $current_user->ID)) {
//                        /* this is the user that posted the ticket */
//                        $show_content = true;
//                    } else {
//                        /* let's check if the current user has capabilitie to see tickets */
//                        if (current_user_can('edit_nifty_desk_ticket')) {
//                            $show_content = true;
//                        } else {
//                            $show_content = false;
//                        }
//
//                    }
//                }
//                if ($show_content) {
//                    $nifty_desk_content = "";
//                    $pre_content = "";
//                    $after_content = nifty_desk_show_author_box(get_the_author_meta('ID'),get_the_date(),get_the_time());
//
//                    $content = $pre_content.$content.$nifty_desk_content.$after_content;
//                }
//
//                $content = $content.nifty_desk_append_responses_to_ticket(get_the_ID());
//
//            } else if ($custom['ticket_status'][0] == "1") {
//                /* solved ticket */
//
//                /* can others see the ticket or not? - pro version only */
//                $current_user = wp_get_current_user();
//                if (!$current_user->ID) {
//                    return __("You cannot view this support ticket.","nifty_desk");
//                }
//                else {
//                /* check if it's the owner of the ticket */
//                    $show_content = false;
//                    if ((get_the_author_meta('ID') == $current_user->ID)) {
//                        /* this is the user that posted the ticket */
//                        $show_content = true;
//                    } else {
//                        /* let's check if the current user has capabilitie to see tickets */
//                        if (current_user_can('edit_nifty_desk_ticket')) {
//                            $show_content = true;
//                        } else {
//                            $show_content = false;
//                        }
//
//                    }
//
//                    if ($show_content) {
//                        $nifty_desk_content .= "<span class='nifty_desk_pending_approval_span'>".__("This support ticket is marked as solved.","nifty_desk")."</span>";
//                        $content = $content.$nifty_desk_content;
//                        $content = $content.nifty_desk_append_responses_to_ticket(get_the_ID());
//                    }
//                }
//
//
//
//
//            }
//            $content = $content;
//        }
//    }
//    return $content;

/**
 * Returns the 'Response Form' for a ticket
 *
 * @param int  $post_id  Ticket ID
 * @param int  $ticket_id  Ticket ID
 * @return string (HTML)
*/
function nifty_desk_draw_response_box($post_id, $ticket_id) {
    $response_data = nifty_desk_get_response_data($post_id);

    if ($response_data->post_status != "publish") {
        return "";
    }

    $author_data = get_userdata($response_data->post_author);
    $nifty_desk_content = "<div class='nifty_desk_response_container'>";

    $nifty_desk_content .= "<div class='nifty_desk_author_container'>";
    $nifty_desk_content .=  "<div class='nifty_desk_author_avatar'>" . get_avatar($author_data->user_email, '50') . "</div>";
    $nifty_desk_content .=      "<div class='nifty_desk_author_meta_container'>";
    $nifty_desk_content .=          "<span class=\"nifty_desk_response_user\">" . $author_data->display_name . "</span> | <span title=\"" . $response_data->post_date . "\" class=\"nifty_desk_response_time\">" . nifty_desk_time_elapsed_string(strtotime($response_data->post_date)) . "</span>";
    $nifty_desk_content .=      "</div>";
    $nifty_desk_content .=   "</div>";

    $nifty_desk_content .= "<div class=\"nifty_desk_response_content\"><p>" . ($response_data->post_content) . "</p>";
    
    /*$nifty_desk_content .= "<div class='nifty_desk_post_box'>";
    if (nifty_desk_is_admin()) {
        $nifty_desk_content .= "<span class='nifty_desk_admin_edit_response_span'><a href='" . get_edit_post_link($post_id) . "' class='nifty_desk_button'>" . __("edit", "nifty_desk") . "</a></span> &nbsp;";
        $nifty_desk_content .= "<span class='nifty_desk_admin_delete_response_span'><a href='" . get_delete_post_link($post_id) . "' class='nifty_desk_button'>" . __("delete", "nifty_desk") . "</a></span>";
    }
    $nifty_desk_content .= '</div>';*/

    $ticket_attachments = maybe_unserialize(get_post_custom_values('ticket_attachments', $post_id));
    $upload_dir = wp_upload_dir();
    $udir = $upload_dir['baseurl'].'/nifty-desk-uploads/'.$ticket_id."/";
    if ($ticket_attachments) {
        $nifty_desk_content .= "<ul>";
        foreach ($ticket_attachments as $key => $att) {
            $att = maybe_unserialize($att);
            foreach ($att as $att_for_realz) {
                $checkpath = $upload_dir['basedir'].'/nifty-desk-uploads/'.$ticket_id."/";
                $check_exists = @file_exists($checkpath.$att_for_realz);
                if (!$check_exists) { $check_exists_string = "<em style='font-family: monospace; font-size:0.8em; color:#000;'>(".__("File no longer exists","nifty_desk").")</em>"; } else { $check_exists_string = ""; }
                $nifty_desk_content .= "<li class='nifty_desk_attachment'><a class='' target='_BLANK' href='".$udir.$att_for_realz."'>".$att_for_realz."</a> " . $check_exists_string. "</li>";
            }
        }
        $nifty_desk_content .= "</ul>";
    }   

    $nifty_desk_content .=  "</div>";
    $nifty_desk_content .= "</div>";

    return $nifty_desk_content;
}

/**
 * Checks if user is an administrator
 * @return boolean
*/
function nifty_desk_is_admin() {
    /* build this up according to user roles in the near future */
    if (current_user_can('manage_options')) {
        return true;
    } else {
        return false;
    }
}

/**
 * Handles frontend user 'actions'
 * @return void
*/
function nifty_desk_user_head() {


    if (isset($_POST['nifty_desk_submit_ticket']) && $_POST['nifty_desk_ticket_title'] != "") {

        /* add a option to save as draft or live (settings) */

        $content = nifty_desk_check_for_html($_POST['nifty_desk_ticket_text']);

        if(isset($_POST['nifty_desk_submit_department']))
        {
            $tax_input = array(
                'nifty_desk_deparments' => wp_strip_all_tags($_POST['nifty_desk_submit_department'])
            );
        }

        $nifty_desk_settings = get_option("nifty_desk_settings");
        $title = sanitize_text_field( $_POST['nifty_desk_ticket_title'] );
        $data = array(
            'post_content' => $content,
            'post_status' => 'publish',
            'post_title' => $title,
            'post_type' => 'nifty_desk_tickets',
            'post_author' => get_current_user_id(),
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $post_id = wp_insert_post($data);

        $custom_fields = get_post_custom($post_id);
        if (!isset($custom_fields['ticket_status'])) { add_post_meta($post_id, 'ticket_status', '9', true); } else { add_post_meta($post_id, 'ticket_status', '', true); }
        if (!isset($custom_fields['ticket_public'])) { add_post_meta($post_id, 'ticket_public', '0', true); } else { add_post_meta($post_id, 'ticket_public', '', true); }

        if (isset($_POST['nifty_desk_submit_priority'])) {
            add_post_meta($post_id, 'ticket_priority', sanitize_text_field( $_POST['nifty_desk_submit_priority'] ), true);
        } else {
            /* get default */
            $nifty_desk_default_priority = $nifty_desk_settings['nifty_desk_settings_default_priority'];
            if (!$nifty_desk_default_priority) {
                $nifty_desk_default_priority = 0;
            }
            add_post_meta($post_id, 'ticket_priority', $nifty_desk_default_priority, true);
        }

        $ticket_array = array(
            'ticket_id' => $post_id,
            'title' => $title,
            'content' => $content
        );
        do_action("nifty_desk_after_create_ticket",$ticket_array);

        $assigned_to = apply_filters("nifty_desk_assigned_to_new_ticket",false);
        if ($assigned_to) { update_post_meta( $post_id, 'ticket_assigned_to', $assigned_to); }
        update_post_meta( $post_id, 'ticket_last_updated', current_time('timestamp'));
        add_post_meta($post_id, 'ticket_reference', md5($post_id . get_current_user_id()), true);
        nifty_desk_notification_control('ticket', $post_id, get_current_user_id());


        wp_redirect(get_permalink($post_id));
    }

    if (isset($_POST['nifty_desk_response_id']) && $_POST['nifty_desk_response_id'] != "") {

        $parent_id = intval( sanitize_text_field( $_POST['nifty_desk_response_id'] ) );

        $content = nifty_desk_check_for_html( wp_kses( $_POST['nifty_desk_response_text']) );

        $data = array(
            'post_content' => $content,
            'post_status' => 'publish',
            'post_title' => sanitize_text_field( $_POST['nifty_desk_response_title'] ),
            'post_type' => 'nifty_desk_responses',
            'post_author' => get_current_user_id(),
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $post_id = wp_insert_post($data);

        /* check if current user is an agent, if not, change status to "open" */
        if (get_the_author_meta('nifty_desk_agent', get_current_user_id()) == "1") {
            /* is agent */
            /* change status to pending */
            update_post_meta($parent_id, 'ticket_status', '3');            

        } else {
            /* change status to open */
            update_post_meta($parent_id, 'ticket_status', '0');            
        }



        update_post_meta($post_id, '_response_parent_id', $parent_id);
        /* update parent's 'last updated' time */
        update_post_meta( $parent_id, 'ticket_last_updated', current_time('timestamp'));
        nifty_desk_notification_control('response', $parent_id, get_current_user_id(),false,false,$content,false,$post_id);
    }
}

/**
 * Returns column headings for ticket list
 * @return array
*/
function nifty_desk_tickets_cpt_columns($columns) {

    if (defined('nifty_desk_CSS_CUSTOMER_SATISFACTION_SURVEY_ACTIVE')&&function_exists('nifty_desk_pro_activate')) {
        /* we're using customer satisfaction surveys */
        $new_columns = array(
            'ticket_priority_column' => __('Priority', 'nifty_desk'),
            'ticket_responses_column' => __('Responses', 'nifty_desk'),
            'ticket_last_responded_column' => __('Last Response By', 'nifty_desk'),
            'ticket_status' => __('Status', 'nifty_desk'),
            'satisfaction_rating' => __('Rating', 'nifty_desk')
        );

    } else {
        $new_columns = array(
            'ticket_priority_column' => __('Priority', 'nifty_desk'),
            'ticket_responses_column' => __('Responses', 'nifty_desk'),
            'ticket_last_responded_column' => __('Last Response By', 'nifty_desk'),
            'ticket_status' => __('Status', 'nifty_desk')
        );
    }
    return array_merge($columns, $new_columns);
}

add_filter('manage_nifty_desk_tickets_posts_columns', 'nifty_desk_tickets_cpt_columns');

add_action('manage_nifty_desk_tickets_posts_custom_column', 'nifty_desk_manage_ticket_status_column', 10, 2);

/**
 * Outputs column HTML based on columns
 *
 * @param string $column_name Column Name
 * @param int    $post_id ticket id
 * @return void
*/
function nifty_desk_manage_ticket_status_column($column_name, $post_id) {
    global $wpdb;
    switch ($column_name) {
        case 'ticket_responses_column':
            echo "<a href='" . get_edit_post_link($post_id) . "'>" . nifty_desk_cnt_responses($post_id) . "</a>";
            break;
        case 'ticket_priority_column':
            echo "<a href='" . get_edit_post_link($post_id) . "'>" . nifty_desk_return_ticket_priority($post_id) . "</a>";
            break;
        case 'ticket_last_responded_column':
            $data = nifty_desk_get_last_response($post_id);
            if (isset($data->post_author)) {
                $author = $data->post_author;


                if ($author) {
                    $author_data = get_userdata($author);
                    echo $author_data->display_name;

                    echo "<br /><small>" . nifty_desk_time_elapsed_string(strtotime($data->post_date)) . "</small>";
                } else {
                    echo "-";
                }
            } else {
                echo "-";
            }
            break;
        case 'ticket_status':
            echo nifty_desk_return_ticket_status($post_id);
            break;
        case 'satisfaction_rating':
			
			if(defined('nifty_desk_CSS_CUSTOMER_SATISFACTION_SURVEY_ACTIVE'))
			{
				$rating_data = nifty_desk_model::retrieve_average_rating_by_ticket_id_model($post_id);
				$rating=$rating_data[0]->rating;
				if($rating===null)
				{
					$rating=0;
				}	
				
				
				$stars = nifty_desk_view::return_survey_stars($rating);
            	$view_button='<input style="margin-top:10px;" type="button" class="btn btn-default btn-xs" name="nifty_desk_css_get_ticket_survey_results_'.$post_id.'" id="nifty_desk_css_get_ticket_survey_results_'.$post_id.'" value="View results"/>';
            	echo $stars.'<br/>'.$view_button;	
			}
			else
			{
				echo '';		
			}
			
			
            break;
        default:
            break;
    } // end switch
}


/**
 * Returns response count for a ticket
 * @param int $id Ticket ID
 * @return int
*/
function nifty_desk_cnt_responses($id) {
    $meta_data = nifty_desk_get_post_meta_all($id);
    $cnt = count($meta_data);
    return $cnt;
}

/**
 * Returns last response for a ticket
 * @param int $id Ticket ID
 * @return object (False on fail)
*/
function nifty_desk_get_last_response($id) {
    $meta_data = nifty_desk_get_post_meta_last($id);

    if (isset($meta_data[0])) {
        $post_id = $meta_data[0]->post_id;
        if ($meta_data) {
            $response_data = nifty_desk_get_response_data($post_id);
            return $response_data;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * Checks if 'Collapse Admin Menu' is enabled in the settings area
 * If, adds 'Folded' to the input variable
 * @param string $classes classes of the wrapper
 * @return string
*/
function nifty_desk_fold_menu_body_classes($classes) {
    $nifty_desk_settings = get_option("nifty_desk_settings");
    if (isset($nifty_desk_settings['nifty_desk_settings_dashboard_folded']) && $nifty_desk_settings['nifty_desk_settings_dashboard_folded'] == 1) {
        return "$classes folded";
    } else {
         return "$classes";
    }
}


/**
 * Returns a formatted time passed based on input
 * @param time $ptime
 * @return string
*/
function nifty_desk_time_elapsed_string($ptime) {
    $ptime = intval($ptime);
    $etime = current_time('timestamp') - $ptime;

    if ($etime < 1) {
        return 'Now';
    }

    $a = array(
        60 * 60 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($a as $secs => $str) {
        $d = $etime / $secs;
        if ($d >= 1) {
            $r = round($d);
            if ($str == 'hour' && $r > 23) { return date("Y-m-d",$ptime); }
            return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
        }
    }
    return date("Y-m-d",$ptime);
}

/**
 * Returns a ticket status
 * @param int $post_id ticket id
 * @return string
*/
function nifty_desk_return_ticket_status($post_id) {
    $value = get_post_custom_values('ticket_status', $post_id);
    if ($value[0] == "0") { _e("Open", "nifty_desk"); } 
    else if ($value[0] == "1") { _e("Solved", "nifty_desk"); }
    else if ($value[0] == "2") { _e("Closed", "nifty_desk"); }
    else if ($value[0] == "9") { _e("New", "nifty_desk"); }
    else if ($value[0] == "3") { _e("Pending", "nifty_desk"); }
    else { _e("Unknown", "nifty_desk"); }
}
/**
 * Returns a ticket status codes
 * @param int $post_id ticket id
 * @return string (integers)
*/
function nifty_desk_return_ticket_status_returns($post_id) {
    $value = get_post_custom_values('ticket_status', $post_id);
    if ($value[0] == "0") {
       return __("Open", "nifty_desk");
    } else if ($value[0] == "1") {
       return __("Solved", "nifty_desk");
    } else if ($value[0] == "2") {
       return __("Closed", "nifty_desk");
    } else if ($value[0] == "3") {
       return __("Pending", "nifty_desk");
    } else if ($value[0] == "9") {
        return __("New", "nifty_desk");
    } else {
        return __("Unknown", "nifty_desk");
    }
}

/**
 * Returns a ticket status names
 * @param int $status status code
 * @return string
*/
function nifty_desk_return_ticket_status_name($status) {
    if ($status == "0") {
       return __("Open", "nifty_desk");
    } else if ($status == "1") {
       return __("Solved", "nifty_desk");
    } else if ($status == "2") {
       return __("Closed", "nifty_desk");
    } else if ($status == "3") {
       return __("Pending", "nifty_desk");
    } else if ($status == "9") {
        return __("New", "nifty_desk");
    } else {
        return __("Unknown", "nifty_desk");
    }
}

/**
 * Returns a ticket status wrapped in HTML span
 * @param int $status status code
 * @return string (html)
*/
function nifty_desk_return_ticket_status_html_block($status) {
    $status_name = nifty_desk_return_ticket_status_name($status);
    $first_letter = strtolower(substr($status_name,0,1));
    return "<span class='nifty_desk_status_block nifty_desk_status_block_".$status."' title='".$status_name."'>".$first_letter."</span>";
}

/**
 * Returns a channel name wrapped in HTML span
 * @param string $channel channel name
 * @param int $channel_id (unused)
 * @param string $full_name channel full name
 * @return string (html)
*/
function nifty_desk_return_ticket_channel_html_block($channel,$channel_id,$full_name) {
    return "<span class='nifty_desk_channel_block' title='".$full_name."'>".$channel."</span>";
}

/**
 * Returns a ticket  status from a meta_id
 * @param string $id reponse id
 * @return string
*/
function nifty_desk_return_ticket_status_from_meta_id($id) {
    if ($id == "0") {
        return __("Open", "nifty_desk");
    } else if ($id == "1") {
        return __("Solved", "nifty_desk");
    } else if ($id == "2") {
        return __("Closed", "nifty_desk");
    } else if ($id == "3") {
        return __("Pending", "nifty_desk");
    } else if ($id == "9") {
        return __("New", "nifty_desk");
    } else {
        return __("Unknown", "nifty_desk");
    }
}


/**
 * Returns a ticket priority
 * @param string $post_id ticket id
 * @return string
*/
function nifty_desk_return_ticket_priority($post_id) {
    $value = get_post_custom_values('ticket_priority', $post_id);
    if ($value[0] == "1") {
        echo __("Low", "nifty_desk");
    } else if ($value[0] == "2") {
        echo __("High", "nifty_desk");
    } else if ($value[0] == "3") {
        echo "<span style='color:orange;'>" . __("Urgent", "nifty_desk") . "</span>";
    } else if ($value[0] == "4") {
        echo "<span style='color:red;'>" . __("Critical", "nifty_desk") . "</span>";
    } else {
        echo __("Low", "nifty_desk");
    }
}


/**
 * Returns a ticket priority wrapped in span
 * @param string $post_id ticket id
 * @return string
*/
function nifty_desk_return_ticket_priority_returns($post_id) {
    $value = get_post_custom_values('ticket_priority', $post_id);
    if ($value[0] == "1") {
        return "<span class='low priority_box'>".__("Low", "nifty_desk"). "</span>";
    } else if ($value[0] == "2") {
        return "<span class='high priority_box'>".__("High", "nifty_desk"). "</span>";
    } else if ($value[0] == "3") {
        return "<span class='urgent priority_box'>" . __("Urgent", "nifty_desk") . "</span>";
    } else if ($value[0] == "4") {
        return "<span class='critical priority_box'>" . __("Critical", "nifty_desk") . "</span>";
    } else {
        return "<span class='low priority_box'>".__("Low", "nifty_desk"). "</span>";
    }
}

/**
 * Returns a ticket priority from a meta
 * @param string $id response id
 * @return string
*/
function nifty_desk_return_ticket_priority_from_meta_id($id) {
    if ($id == "1") {
        return __("Low", "nifty_desk");
    } else if ($id == "2") {
        return __("High", "nifty_desk");
    } else if ($id == "3") {
        return __("Urgent", "nifty_desk");
    } else if ($id == "4") {
        return __("Critical", "nifty_desk");
    } else {
        return __("Low", "nifty_desk");
    }
}

/**
 * Creates a page
 *
 * @param string $slug page slug
 * @param string $title page title
 * @param string $content page content
 * @return int (page id)
*/
function nifty_desk_create_page($slug, $title, $content) {
    // Initialize the post ID to -1. This indicates no action has been taken.
    $post_id = -1;

    // Setup the author, slug, and title for the post
    $author_id = 1;

    $post_type = "page";

    // If the page doesn't already exist, then create it
    $nifty_desk_check_page = get_page_by_title($title, '', $post_type);
    if ($nifty_desk_check_page == null) {

        // Set the page ID so that we know the page was created successfully
        $post_id = wp_insert_post(
                array(
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'post_author' => $author_id,
                    'post_name' => $slug,
                    'post_title' => $title,
                    'post_status' => 'publish',
                    'post_type' => $post_type,
                    'post_content' => $content
                )
        );
        return $post_id;

        // Otherwise, we'll stop and set a flag
    } else {

        // Arbitrarily use -2 to indicate that the page with the title already exists

        return $nifty_desk_check_page->ID;


        //$post_id = -2;
    } // end if
}

/**
 * Handles submit a ticket shortcode
 *
 * @param array     $atr    shortcode attributes
 * @param string    $text   text
 * @return string           The shortcode content
*/
function nifty_desk_shortcode_submit_ticket_page($atr, $text = null) {
    $nifty_desk_settings = get_option("nifty_desk_settings");
    if (function_exists('nifty_desk_pro_activate')) {	
        if (isset($nifty_desk_settings['nifty_desk_settings_require_login']) && $nifty_desk_settings['nifty_desk_settings_require_login'] == 1) {
            if (is_user_logged_in()) {
                return nifty_desk_submission_form();
            } else {
                $content = "
                    <a href=\"" . wp_login_url(get_permalink()) . "\">" . __("Log in", "nifty_desk") . "</a> " . __("or", "nifty_desk") . " <a href=\"" . wp_registration_url() . "\">" . __("register", "nifty_desk") . "</a> " . __("to submit a support ticket.", "nifty_desk") . "
                    <br /><br />";
                return $content;
            }
        } else {
            return nifty_desk_submission_form();
        }
    } else {
        if (is_user_logged_in()) {
            return nifty_desk_submission_form();
        } else {
            $content = "
                <a href=\"" . wp_login_url(get_permalink()) . "\">" . __("Log in", "nifty_desk") . "</a> " . __("or", "nifty_desk") . " <a href=\"" . wp_registration_url() . "\">" . __("register", "nifty_desk") . "</a> " . __("to submit a support ticket.", "nifty_desk") . "
                <br /><br />";
            return $content;
        }
    }
}

add_filter('views_edit-nifty_desk_tickets', 'meta_views_nifty_desk_tickets', 10, 1);
/**
 * Adds 'metakey' key to the array of views
 *
 * @param array $views View Array
 * @return array
*/
function meta_views_nifty_desk_tickets($views) {
    
	if(defined('nifty_desk_CSS_CUSTOMER_SATISFACTION_SURVEY_ACTIVE'))
	{
		echo nifty_desk_view::enter_survey_preview_modal();
    	echo nifty_desk_view::ajax_loader_display();	
	}
	
    	
    //$views['separator'] = '&nbsp;';
    $views['metakey'] = '<a href="edit.php?meta_data=ticket_status&ticket_status=9&post_type=nifty_desk_tickets">' . __('New', 'nifty_desk') . '</a> (' . nifty_desk_return_pending_ticket_qty() . ")";
    $views['metakey'] .= '| <a href="edit.php?meta_data=ticket_status&ticket_status=0&post_type=nifty_desk_tickets">' . __('Open Tickets', 'nifty_desk') . '</a> (' . nifty_desk_return_open_ticket_qty() . ")";
    $views['metakey'] .= '| <a href="edit.php?meta_data=ticket_status&ticket_status=0&post_type=nifty_desk_tickets">' . __('Pending Tickets', 'nifty_desk') . '</a> (' . nifty_desk_return_ticket_qty_by_status(3) . ")";
    $views['metakey'] .= '| <a href="edit.php?meta_data=ticket_status&ticket_status=1&post_type=nifty_desk_tickets">' . __('Solved Tickets', 'nifty_desk') . '</a> (' . nifty_desk_return_solved_ticket_qty() . ")";
    $views['metakey'] .= '| <a href="edit.php?meta_data=ticket_status&ticket_status=2&post_type=nifty_desk_tickets">' . __('Closed Tickets', 'nifty_desk') . '</a> (' . nifty_desk_return_closed_ticket_qty() . ")";
    return $views;
}

add_action('load-edit.php', 'load_nifty_desk_custom_filter');
/**
 * Custom Filter
 * @return void
*/
function load_nifty_desk_custom_filter() {
    global $typenow;

    if ('nifty_desk_tickets' != $typenow)
        return;
    add_filter('posts_where', 'posts_where_nifty_desk_status');
}
/**
 * 'WHERE' filter for ticket retrieval query
 * @param string $where where term
 * @return string
*/
function posts_where_nifty_desk_status($where) {
    global $wpdb;
    if (isset($_GET['meta_data']) && !empty($_GET['meta_data'])) {
        $meta = esc_sql($_GET['meta_data']);
        $meta_val = esc_sql($_GET['ticket_status']);
        $where .= " AND ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key='$meta' AND meta_value='$meta_val' )";
    }
    return $where;
}

/**
 * Returns Ticket Count by Status
 * @param string $status Status
 * @return int
*/
function nifty_desk_return_ticket_qty_by_status($status) {
   global $wpdb;
    $row = $wpdb->get_row(
            $wpdb->prepare(
                    "SELECT count(meta_id) as total FROM ".$wpdb->prefix."postmeta pm INNER JOIN ".$wpdb->prefix."posts as p
                    ON pm.post_id =p.ID  
                    WHERE pm.meta_key = %s AND pm.meta_value = %d
                    AND p.post_status='publish' AND p.post_type='nifty_desk_tickets'
                    "  , 'ticket_status', $status
            )
    );
    $total = $row->total;
    return $total;
}

/**
 * Returns Open ticket count 
 * @return int
*/
function nifty_desk_return_open_ticket_qty() {
   global $wpdb;
    $row = $wpdb->get_row(
            $wpdb->prepare(
                    "SELECT count(meta_id) as total FROM ".$wpdb->prefix."postmeta pm INNER JOIN ".$wpdb->prefix."posts as p
                    ON pm.post_id =p.ID  
                    WHERE pm.meta_key = %s AND pm.meta_value = %d
                    AND p.post_status='publish' AND p.post_type='nifty_desk_tickets'
                    "  , 'ticket_status', 0
            )
    );
    $total = $row->total;
    return $total;
}

/**
 * Returns Pending ticket count for use in admin menu (HTML Wrapped)
 * @return string (html)
*/
function nifty_desk_return_pending_ticket_qty_menu(){
    $qty = nifty_desk_return_pending_ticket_qty();
    return " <span class=\"update-plugins count-".$qty."\"><span class=\"plugin-count\">$qty</span></span>";
}

/**
 * Returns Pending ticket count 
 * @return int
*/
function nifty_desk_return_pending_ticket_qty() {
    global $wpdb;
    $row = $wpdb->get_row(
            $wpdb->prepare(
                    "SELECT count(meta_id) as total FROM ".$wpdb->prefix."postmeta pm INNER JOIN ".$wpdb->prefix."posts as p
                    ON pm.post_id =p.ID  
                    WHERE pm.meta_key = %s AND pm.meta_value = %d
                    AND p.post_status='publish' AND p.post_type='nifty_desk_tickets'
                    "  , 'ticket_status', 9
            )
    );
    $total = $row->total;
    return $total;
}

/**
 * Returns Closed ticket count 
 * @return int
*/
function nifty_desk_return_closed_ticket_qty() {
    global $wpdb;
    $row = $wpdb->get_row(
            $wpdb->prepare(
                    "SELECT count(meta_id) as total FROM ".$wpdb->prefix."postmeta pm INNER JOIN ".$wpdb->prefix."posts as p
                    ON pm.post_id =p.ID  
                    WHERE pm.meta_key = %s AND pm.meta_value = %d
                    AND p.post_status='publish' AND p.post_type='nifty_desk_tickets'
                    "  , 'ticket_status', 2
            )
    );
    $total = $row->total;
    return $total;
}

/**
 * Returns Solved ticket count 
 * @return int
*/
function nifty_desk_return_solved_ticket_qty() {
    global $wpdb;
    $row = $wpdb->get_row(
            $wpdb->prepare(
                    "SELECT count(meta_id) as total FROM ".$wpdb->prefix."postmeta pm INNER JOIN ".$wpdb->prefix."posts as p
                    ON pm.post_id =p.ID  
                    WHERE pm.meta_key = %s AND pm.meta_value = %d
                    AND p.post_status='publish' AND p.post_type='nifty_desk_tickets'
                    "  , 'ticket_status', 1
            )
    );
    $total = $row->total;
    return $total;
}

if (!function_exists("nifty_desk_pro_activate")) {
    add_filter('pre_get_posts', 'nifty_desk_loop_control');
}

/**
 * Filters the primary query loop for ticket queries
 * @param  string $query Query
 * @return string (Filtered Query)
*/
function nifty_desk_loop_control($query) {

    $current_user = wp_get_current_user();
    if ((get_the_author_meta('ID') == $current_user->ID) || current_user_can('edit_nifty_desk_ticket', array(null)) || current_user_can('read', array(null))) {
        if (!is_single() && !is_admin() && !is_page()) {
            if ($query->is_search) {
                if (isset($query->query['post_type']) && $query->query['post_type'] == "nifty_desk_tickets") {
                    if (current_user_can('edit_nifty_desk_tickets', array(null))) {
                        /* Agent is searching */
                        $query->set('meta_query', array(
                            'relation' => 'AND',
                            array(
                                'relation' => 'OR',
                                array(
                                    'key' => 'ticket_status',
                                    'value' => 0
                                ),
                                array(
                                    'key' => 'ticket_status',
                                    'value' => 1
                                ),
                                array(
                                    'key' => 'ticket_status',
                                    'value' => 9
                                ),
                                array(
                                    'key' => 'ticket_status',
                                    'value' => 3
                                )
                            ),
                            array(
                                'relation' => 'OR',
                                array(
                                    'key' => 'ticket_public',
                                    'value' => 1
                                ),
                                array(
                                    'key' => 'ticket_public',
                                    'value' => 0
                                )
                            )
                        ));
                    } else {
                        $query->set('meta_query', array(
                            'relation' => 'AND',
                            array(
                                'relation' => 'OR',
                                array(
                                    'key' => 'ticket_status',
                                    'value' => 0
                                ),
                                array(
                                    'key' => 'ticket_status',
                                    'value' => 1
                                ),
                                array(
                                    'key' => 'ticket_status',
                                    'value' => 3
                                )
                            ),
                            array(
                                'key' => 'ticket_public',
                                'value' => 1
                            )
                        ));
                    }
                } else {
                    /* Exclude support tickets from normal search */

                    $query->set('meta_query', array(
                        'relation' => 'AND',
                        array(
                            'key' => 'ticket_status',
                            'compare' => 'NOT EXISTS'
                        ),
                        array(
                            'key' => '_response_parent_id',
                            'compare' => 'NOT EXISTS'
                        )
                    ));
                }
                return $query;
            }
        }
    }
}

/**
 * Handles sending Feedback through from the feedback form
 * @return void
*/
function nifty_desk_feedback_head() {

    if (function_exists('curl_version')) {

        $request_url = "http://www.ccplugins.com/apif-support-tickets/rec.php";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
    }
    return;
}

add_action('restrict_manage_posts', 'nifty_desk_add_priority_filter');
add_action('restrict_manage_posts', 'nifty_desk_add_agent_filter');
add_action('restrict_manage_posts', 'nifty_desk_add_status_filter');
/**
 * Outputs (echo) Priority selection field
 * @return void
*/
function nifty_desk_add_priority_filter() {
    global $typenow;
    if ($typenow != "nifty_desk_tickets") {
        return;
    }
    ?>
    <select name="nifty_desk_priority_mv">
        <option value=""><?php _e('All Priorities', 'nifty_desk_tickets'); ?></option>
        <option value="1" <?php
        if (isset($_GET['nifty_desk_priority_mv']) && $_GET['nifty_desk_priority_mv'] == "1") {
            echo "selected='selected'";
        }
        ?>><?php echo esc_attr(nifty_desk_return_ticket_priority_from_meta_id(1)); ?></option>
        <option value="2" <?php
    if (isset($_GET['nifty_desk_priority_mv']) && $_GET['nifty_desk_priority_mv'] == "2") {
        echo "selected='selected'";
    }
        ?>><?php echo esc_attr(nifty_desk_return_ticket_priority_from_meta_id(2)); ?></option>
        <option value="3" <?php
                if (isset($_GET['nifty_desk_priority_mv']) && $_GET['nifty_desk_priority_mv'] == "3") {
                    echo "selected='selected'";
                }
                ?>><?php echo esc_attr(nifty_desk_return_ticket_priority_from_meta_id(3)); ?></option>
        <option value="4" <?php
    if (isset($_GET['nifty_desk_priority_mv']) && $_GET['nifty_desk_priority_mv'] == "4") {
        echo "selected='selected'";
    }
    ?>><?php echo esc_attr(nifty_desk_return_ticket_priority_from_meta_id(4)); ?></option>
    </select><input type="hidden" size="16" value="ticket_priority" name="nifty_desk_priority_mk" />
    <?php
}

/**
 * Outputs (echo) Status selection field
 * @return void
*/
function nifty_desk_add_status_filter() {
    global $typenow;
    if ($typenow != "nifty_desk_tickets") {
        return;
    }
    ?>
    <select name="nifty_desk_status_mv">
        <option value=""><?php _e('All Statuses', 'nifty_desk_tickets'); ?></option>
        <option value="99" <?php
    if (isset($_GET['nifty_desk_status_mv']) && $_GET['nifty_desk_status_mv'] == "99") {
        echo "selected='selected'";
    }
    ?>><?php echo esc_attr(nifty_desk_return_ticket_status_from_meta_id(0)); ?></option>
        <option value="1" <?php
    if (isset($_GET['nifty_desk_status_mv']) && $_GET['nifty_desk_status_mv'] == "1") {
        echo "selected='selected'";
    }
    ?>><?php echo esc_attr(nifty_desk_return_ticket_status_from_meta_id(1)); ?></option>
        <option value="2" <?php
    if (isset($_GET['nifty_desk_status_mv']) && $_GET['nifty_desk_status_mv'] == "2") {
        echo "selected='selected'";
    }
    ?>><?php echo esc_attr(nifty_desk_return_ticket_status_from_meta_id(2)); ?></option>
        <option value="9" <?php
    if (isset($_GET['nifty_desk_status_mv']) && $_GET['nifty_desk_status_mv'] == "9") {
        echo "selected='selected'";
    }
    ?>><?php echo esc_attr(nifty_desk_return_ticket_status_from_meta_id(9)); ?></option>
    </select><input type="hidden" size="16" value="ticket_status" name="nifty_desk_status_mk" />
        <?php
}

/**
 * Outputs (echo) Agent selection field
 * @return void
*/
function nifty_desk_add_agent_filter() {

        global $typenow;
        if ($typenow != "nifty_desk_tickets") {
            return;
        }
        ?>
    <select name="nifty_desk_agent_mv">
        <option value=""><?php _e('All Agents', 'nifty_desk_tickets'); ?></option>
        <?php
        /* add superadmin */
        $super_admins = get_super_admins();
        if($super_admins && isset($super_admins[0])){
            $suser = get_user_by('slug', $super_admins[0]);
            echo '<option value="'.$suser->ID.'"';
                if (isset($_GET['nifty_desk_agent_mv']) && $_GET['nifty_desk_agent_mv'] == $suser->ID) {
                    echo "selected='selected'";
                }
            echo '>'.$suser->display_name.'</option>';
        } 
        ?>

    <?php
    $users = get_users(array(
        'meta_key' => 'nifty_desk_agent',
        'meta_value' => '1',
        'meta_compare' => '-',
    ));
    foreach ($users as $user) {
        ?>
            <option value="<?php echo $user->ID; ?>" <?php
        if (isset($_GET['nifty_desk_agent_mv']) && $_GET['nifty_desk_agent_mv'] == $user->ID) {
            echo "selected='selected'";
        }
        ?>><?php echo $user->display_name; ?></option>
        <?php
    }
    ?>
    </select><input type="hidden" size="16" value="ticket_assigned_to" name="nifty_desk_agent_mk" />
    <?php
}

add_filter('pre_get_posts', 'nifty_desk_admin_loop_control');
/**
 * Filters the primary admin query loop
 * @param string $query Query
 * @return string (Filtered Query)
*/
function nifty_desk_admin_loop_control($query) {


    if (is_admin()) {
        if (isset($query->query['post_type']) && $query->query['post_type'] == "nifty_desk_tickets") {

            $agent = false;
            $status = false;
            $priority = false;

            if (isset($_GET['nifty_desk_agent_mk']) and isset($_GET['nifty_desk_agent_mv']) and ( $_GET['nifty_desk_agent_mv'] != '')) {
                $agent = true;
                $agent_array = array(
                    'key' => 'ticket_assigned_to',
                    'value' => $_GET['nifty_desk_agent_mv']
                );
            } else {
                $agent_array = array('' => '');
            }
            if (isset($_GET['nifty_desk_priority_mk']) and isset($_GET['nifty_desk_priority_mv']) and ( $_GET['nifty_desk_priority_mv']) != '') {
                $priority = true;
                $priority_array = array(
                    'key' => 'ticket_priority',
                    'value' => $_GET['nifty_desk_priority_mv']
                );
            } else {
                $priority_array = array('' => '');
            }
            if (isset($_GET['nifty_desk_status_mk']) and isset($_GET['nifty_desk_status_mv']) and ( $_GET['nifty_desk_status_mv']) != '') {
                $status = true;
                if ($_GET['nifty_desk_status_mv'] == "99") {
                    $status_code = 0;
                } else {
                    $status_code = $_GET['nifty_desk_status_mv'];
                }
                $status_array = array(
                    'key' => 'ticket_status',
                    'value' => "$status_code"
                );
            } else {
                $status_array = array('' => '');
            }


            if ($agent || $priority || $status) {
                $query->set('meta_query', array(
                    'relation' => 'AND',
                    $agent_array,
                    $status_array,
                    $priority_array
                        )
                );
            }
        }
    }
}

/**
 * Returns the users gravatar URL
 * 
 * @param string $email Email
 * @param int $s size in pixels
 * @param string $d Default icon to return
 * @param string $r Icon Rating 
 * @param boolean $img Wrap in an image tag
 * @param array $atts Additional Attributes
 * @return string 
*/
function nifty_desk_get_gravatar($email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array()) {
    $url = 'http://www.gravatar.com/avatar/';
    $url .= md5(strtolower(trim($email)));
    $url .= "?s=$s&d=$d&r=$r";
    if ($img) {
        $url = '<img src="' . $url . '"';
        foreach ($atts as $key => $val)
            $url .= ' ' . $key . '="' . $val . '"';
        $url .= ' />';
    }
    return $url;
}

/**
 * Returns the author box
 * 
 * @param int $id User ID
 * @param string $date Date
 * @param string $time Time
 * @return string (html)
*/
function nifty_desk_show_author_box($id, $date, $time) {
    $user_data = get_user_by('id', $id);
    $out='';
	
	
    $out.="
        <div class='nifty_desk_author_box'>
            <img src='" . nifty_desk_get_gravatar($user_data->user_email, '50') . "' class='alignleft nifty_desk_author_image' />
            " . __("Submitted by ", "nifty_desk") . " <span class='nifty_desk_author_box_name'>" . $user_data->display_name . "</span><br />
            " . __("on ", "nifty_desk") . " <span class='nifty_desk_author_box_date'>" . $date . " " . $time . "</span>

        </div>";
	
	
	/*
	$out.="<div class='nifty_desk_author_box'>
            <span class='nifty_desk_author_box_text'>
            	<span class='alignleft nifty_desk_author_image'>" . get_avatar($user_data->user_email, '50') . "</span>
            	" . __("Submitted by ", "nifty_desk") . " <span class='nifty_desk_author_box_name'>" . $user_data->display_name . "</span><br />
            	" . __("on ", "nifty_desk") . " <span class='nifty_desk_author_box_date'>" . $date . " at " . $time . "</span>
        	</span>
        	</div>";
	
	
	 */
	 
	return $out;
		
}

/**
 * Returns the Submission Form
 * 
 * @return string (html)
*/
function nifty_desk_submission_form() {
    $nifty_desk_settings = get_option('nifty_desk_settings');	
	
    if (isset($nifty_desk_settings['nifty_desk_settings_allow_priority']) && $nifty_desk_settings['nifty_desk_settings_allow_priority'] == "1") {
				
	
			if(isset($nifty_desk_settings['nifty_desk_settings_default_priority']))
			{
				if($nifty_desk_settings['nifty_desk_settings_default_priority']=='0')
				{
					$nifty_desk_priority_text = "
		            <div class='nifty_desk_row'>
                        <div class='nifty_desk_col_5'>
		                  <label for=\"nifty_desk_submit_priority\" title=". __("Priority", "nifty_desk") ."><strong>" . __("Priority", "nifty_desk") . "</strong></label>
		                </div>
                        <div class='nifty_desk_col_5 nifty_desk_text_align_right'>
		                  <select name=\"nifty_desk_submit_priority\" id=\"nifty_desk_submit_priority\">
                                    <option value='1' selected='selected'>" . __("Low", "nifty_desk") . "</option>
                                    <option value='2'>" . __("High", "nifty_desk") . "</option>
                                    <option value='3'>" . __("Urgent", "nifty_desk") . "</option>
                                    <option value='4'>" . __("Critical", "nifty_desk") . "</option>
		                    </select>
		               </div>
                    </div>";		
				}
				else if($nifty_desk_settings['nifty_desk_settings_default_priority']=='1')
				{
					$nifty_desk_priority_text = "
		            <div class='nifty_desk_row'>
                        <div class='nifty_desk_col_5'>
		                  <strong>" . __("Priority", "nifty_desk") . "</strong>
		                </div>
                        <div class='nifty_desk_col_5 nifty_desk_text_align_right'>
		                    <select name=\"nifty_desk_submit_priority\" id=\"nifty_desk_submit_priority\">
		                       <option value='1'>" . __("Low", "nifty_desk") . "</option>
		                       <option value='2' selected='selected'>" . __("High", "nifty_desk") . "</option>
		                       <option value='3'>" . __("Urgent", "nifty_desk") . "</option>
		                       <option value='4'>" . __("Critical", "nifty_desk") . "</option>
		                    </select>
		               </div>
		            </div>";		
				}
				else if($nifty_desk_settings['nifty_desk_settings_default_priority']=='2')
				{
					$nifty_desk_priority_text = "
		            <div class='nifty_desk_row'>
		                <div class='nifty_desk_col_5'>
		                  <strong>" . __("Priority", "nifty_desk") . "</strong>
		                </div>
                        <div class='nifty_desk_col_5 nifty_desk_text_align_right'>
		                    <select name=\"nifty_desk_submit_priority\" id=\"nifty_desk_submit_priority\">
		                       <option value='1'>" . __("Low", "nifty_desk") . "</option>
		                       <option value='2'>" . __("High", "nifty_desk") . "</option>
		                       <option value='3' selected='selected'>" . __("Urgent", "nifty_desk") . "</option>
		                       <option value='4'>" . __("Critical", "nifty_desk") . "</option>
		                    </select>
		               </div>
		            </div>";		
				}
				else if($nifty_desk_settings['nifty_desk_settings_default_priority']=='3')
				{
					$nifty_desk_priority_text = "
		            <div class='nifty_desk_row'>
                        <div class='nifty_desk_col_5'>
		                  <strong>" . __("Priority", "nifty_desk") . "</strong>
		                </div>
		                <div class='nifty_desk_col_5 nifty_desk_text_align_right'>
		                    <select name=\"nifty_desk_submit_priority\" id=\"nifty_desk_submit_priority\">
		                  	   <option value='1'>" . __("Low", "nifty_desk") . "</option>
		                  	   <option value='2'>" . __("High", "nifty_desk") . "</option>
		                  	   <option value='3'>" . __("Urgent", "nifty_desk") . "</option>
		                  	   <option value='4' selected='selected'>" . __("Critical", "nifty_desk") . "</option>
		                    </select>
		               </div>
		            </div>";		
				}
				else
				{
					$nifty_desk_priority_text = "
		            <div class='nifty_desk_row'>
		                <div class='nifty_desk_col_5'>
		                  <strong>" . __("Priority", "nifty_desk") . "</strong>
		                </div>
                        <div class='nifty_desk_col_5 nifty_desk_text_align_right'>
		                      <select name=\"nifty_desk_submit_priority\" id=\"nifty_desk_submit_priority\">
		                          <option value='1'>" . __("Low", "nifty_desk") . "</option>
		                          <option value='2'>" . __("High", "nifty_desk") . "</option>
		                          <option value='3'>" . __("Urgent", "nifty_desk") . "</option>
		                          <option value='4'>" . __("Critical", "nifty_desk") . "</option>
		                      </select>
		               </div>
		            </div>";		
				}
			}
			else
			{
				 $nifty_desk_priority_text = "
                    <div class='nifty_desk_row'>
                        <div class='nifty_desk_col_5'>
		                  <strong>" . __("Priority", "nifty_desk") . "</strong>
		                </div>
		                <div class='nifty_desk_col_5 nifty_desk_text_align_right'>
		                    <select name=\"nifty_desk_submit_priority\" id=\"nifty_desk_submit_priority\">
		                      <option value='1'>" . __("Low", "nifty_desk") . "</option>
		                      <option value='2'>" . __("High", "nifty_desk") . "</option>
		                      <option value='3'>" . __("Urgent", "nifty_desk") . "</option>
		                      <option value='4'>" . __("Critical", "nifty_desk") . "</option>
		                    </select>
		               </div>
		            </div>";					
			}
			
			
			
			
    } else {
        $nifty_desk_priority_text = "";
    }

    $validation_text = __('You have not completed all required fields', 'nifty_desk');
    if (function_exists('nifty_desk_pro_activate')) {
        if (function_exists('nifty_desk_pro_departments')) {
            $nifty_desk_departments_row = nifty_desk_pro_departments();
        } else {
            $nifty_desk_departments_row = "";
        }
        if (!is_user_logged_in()) {
            if (function_exists('nifty_desk_pro_email_field')) {
                $nifty_desk_email_row = nifty_desk_pro_email_field();
            } else {
                $nifty_desk_email_row = "";
            }
        } else {
            $nifty_desk_email_row = "";
        }
        
        if(isset($nifty_desk_settings['nifty_desk_settings_enable_captcha'])&&$nifty_desk_settings['nifty_desk_settings_enable_captcha']==1)
        {
            if(function_exists('nifty_desk_pro_captcha'))
            {
                $captcha = nifty_desk_pro_captcha();    
            }
            else
            {
                $captcha='';
            }
        }
        else
        {
            $captcha='';
        }
        
        
    } else {
        $nifty_desk_departments_row = "";
        $nifty_desk_email_row = "";
        $captcha = "";
    }

    $content = "
        
            <form name=\"nifty_desk_add_ticket\" method=\"POST\" action=\"\" id=\"nifty_desk_add_ticket\" enctype=\"multipart/form-data\">
                
                $nifty_desk_email_row
                
                <div class='nifty_desk_row'>
                    <div class='nifty_desk_col_5'>
                      <label for=\"nifty_desk_ticket_title\" title=". __("Subject", "nifty_desk")."><strong>" . __("Subject", "nifty_desk") . "</strong></label>
                    </div>
                    <div class='nifty_desk_col_5 nifty_desk_text_align_right'>                    
                      <input type=\"text\" value=\"\" name=\"nifty_desk_ticket_title\" id=\"nifty_desk_ticket_title\" data-validation=\"required\" data-validation-error-msg=\"$validation_text\"/><br />
                    </div>
                </div>
                <div class='nifty_desk_row'>
                    <div class='nifty_desk_col_5'>
                      <label for=\"nifty_desk_ticket_text\" title=".__("Description", "nifty_desk")."><strong>" . __("Description", "nifty_desk") . "</strong></label>
                    </div>
                    <div class='nifty_desk_col_5 nifty_desk_text_align_right'>
                      <textarea style=\"width:100%; height:120px;\" name=\"nifty_desk_ticket_text\" id=\"nifty_desk_ticket_text\" data-validation=\"required\" data-validation-error-msg=\"$validation_text\"></textarea><br />
                    </div>
                </div>

                $nifty_desk_priority_text
                $nifty_desk_departments_row
                $captcha";

                if(isset($nifty_desk_settings['enable_file_uploads'])&&$nifty_desk_settings['enable_file_uploads']===1 && function_exists('nifty_desk_pro_activate'))
                {
                    $content.='
                    <div class="nifty_desk_row">
                        <div class="nifty_desk_col_5">
                            <label for="fl_upload_ticket_file_public_section" title="'.__('Upload a file:', 'nifty_desk').'"><strong>'.__('Upload a file:', 'nifty_desk').'</strong></label>
                        </div>
                    </div>
                    <div class="nifty_desk_row">
                        <div class="nifty_desk_col_5">
                            '.__('Allowed formats: JPEG, PNG, GIF, TIFF, PDF, ZIP:', 'nifty_desk').'
                        </div>
                        <div class="nifty_desk_col_5">
                            <input type="file" name="fl_upload_ticket_file_public_section" id="fl_upload_ticket_file_public_section"/>
                        </div>
                    </div>';
                }



                $content.="
                <div class='nifty_desk_row'>
                    <div class='nifty_desk_col_5'>
                    </div>
                    <div class='nifty_desk_col_5 nifty_desk_text_align_right'>
                        <input type=\"submit\" name=\"nifty_desk_submit_ticket\" title=\"" . __("Submit", "nifty_desk") . "\" class=\"nifty_desk_button_send_reponse nifty_desk_button\" value=\"" . __("Submit", "nifty_desk") . "\" />
                   </div>
                </div>
            </form>";
    return $content;
}

/**
 * Outputs Mailing System Options
 * 
 * @return void
*/
function nifty_desk_select_mailing_system_to_use()
{
		$out='';
	
		$data = get_option('nifty_desk_settings');
		$rb_nifty_desk_mailing_system_selection='';
		$nifty_desk_smtp_host_setting_php_mailer='';
		$nifty_desk_smtp_username_setting_php_mailer='';
		$nifty_desk_smtp_password_setting_php_mailer='';
		$nifty_desk_smtp_port_setting_php_mailer='';
		$nifty_desk_smtp_encryption_setting_php_mailer='';
		
		if(isset($data['rb_nifty_desk_mailing_system_selection'])) { $rb_nifty_desk_mailing_system_selection=$data['rb_nifty_desk_mailing_system_selection'];	 }
		if(isset($data['nifty_desk_smtp_host_setting_php_mailer'])) { $nifty_desk_smtp_host_setting_php_mailer=$data['nifty_desk_smtp_host_setting_php_mailer']; }
		if(isset($data['nifty_desk_smtp_username_setting_php_mailer'])) { $nifty_desk_smtp_username_setting_php_mailer=$data['nifty_desk_smtp_username_setting_php_mailer']; }
		if(isset($data['nifty_desk_smtp_password_setting_php_mailer'])) { $nifty_desk_smtp_password_setting_php_mailer=$data['nifty_desk_smtp_password_setting_php_mailer']; }
		if(isset($data['nifty_desk_smtp_port_setting_php_mailer'])) { $nifty_desk_smtp_port_setting_php_mailer=$data['nifty_desk_smtp_port_setting_php_mailer']; }
		if(isset($data['nifty_desk_smtp_encryption_setting_php_mailer'])) { $nifty_desk_smtp_encryption_setting_php_mailer=$data['nifty_desk_smtp_encryption_setting_php_mailer']; }
		
		
	
		$out.='<div class="nifty_desk_email_settings_seperator">';
			 $out.='<p> '.__("Please select the mailing type to use when sending any notification e-mails","nifty_desk").'</p>';
            $em_method1 = ($rb_nifty_desk_mailing_system_selection == 'wp_mail' || !$rb_nifty_desk_mailing_system_selection || $rb_nifty_desk_mailing_system_selection == "") ? 'checked="checked"' : '';
            $em_method2 = $rb_nifty_desk_mailing_system_selection == 'smtp' ? 'checked="checked"' : '';

            $out.='<input type="radio" name="rb_nifty_desk_mailing_system_selection" id="rb_nifty_desk_mailing_system_selection_wp_mail" value="wp_mail" '.$em_method1.'/> <label> '.__('Default WordPress Mail','nifty_desk').'</label> ';
            $out.='<br/>';
            $out.='<input type="radio" name="rb_nifty_desk_mailing_system_selection" id="rb_nifty_desk_mailing_system_selection_smtp" value="smtp" '.$em_method2.' /> <label>  '.__('Custom SMTP settings','nifty_desk').' </label> ';
            
			
			$out.='<div style="display:none;" class="nifty_desk_email_settings_seperator" id="nifty_desk_hidden_php_mailer_smtp_settings">
				<label for="nifty_desk_smtp_host_setting_php_mailer"> '.__('Enter your host URL (for gmail use imap.gmail.com)','nifty_desk').': </label> <br/>
				<input type="text" name="nifty_desk_smtp_host_setting_php_mailer" id="nifty_desk_smtp_host_setting_php_mailer" class="nifty_desk-input nifty_desk_smtp_settings" value="'.$nifty_desk_smtp_host_setting_php_mailer.'"/> <br/><br/>
				<label for="nifty_desk_smtp_username_setting_php_mailer"> '.__('The username for your Email Account','nifty_desk').': </label> <br/> <input type="text" name="nifty_desk_smtp_username_setting_php_mailer" id="nifty_desk_smtp_username_setting_php_mailer" class="nifty_desk-input nifty_desk_smtp_settings" value="'.$nifty_desk_smtp_username_setting_php_mailer.'" /> <br/><br/>
				<label for="nifty_desk_smtp_password_setting_php_mailer"> '.__('The password of your Email Account','nifty_desk').': </label> <br/> <input type="text" name="nifty_desk_smtp_password_setting_php_mailer" id="nifty_desk_smtp_password_setting_php_mailer" class="nifty_desk-input nifty_desk_smtp_settings" value="'.$nifty_desk_smtp_password_setting_php_mailer.'" /> <br/><br/>
				<label for="nifty_desk_smtp_port_setting_php_mailer"> '.__('And finally the port number','nifty_desk').': </label> <br/> <input type="text" name="nifty_desk_smtp_port_setting_php_mailer" id="nifty_desk_smtp_port_setting_php_mailer" class="nifty_desk-input nifty_desk_smtp_settings" value="'.$nifty_desk_smtp_port_setting_php_mailer.'" /> <br/><br/>
				<label>'.__('Encryption','nifty_desk').': </label>
				<br/>';
			
			
				
				if($nifty_desk_smtp_encryption_setting_php_mailer==='No Encryption')
				{
					$out.='<input type="radio" name="nifty_desk_smtp_encryption_setting_php_mailer" id="nifty_desk_smtp_encryption_setting_php_mailer_no_encryption" value="No Encryption" checked="checked"/> '.__('No Encryption','nifty_desk').' <br/>
					<input type="radio" name="nifty_desk_smtp_encryption_setting_php_mailer" id="nifty_desk_smtp_encryption_setting_php_mailer_ssl" value="SSL"/> '.__('SSL - (use SSL for gmail)','nifty_desk').' <br/>
					<input type="radio" name="nifty_desk_smtp_encryption_setting_php_mailer" id="nifty_desk_smtp_encryption_setting_php_mailer_tls" value="TLS"/> '.__('TLS - This is not the same as STARTTLS. For most servers SSL is the recommended option.','nifty_desk').' <br/>';
				
				}
				elseif($nifty_desk_smtp_encryption_setting_php_mailer==='SSL')
				{
					$out.='<input type="radio" name="nifty_desk_smtp_encryption_setting_php_mailer" id="nifty_desk_smtp_encryption_setting_php_mailer_no_encryption" value="No Encryption"/> '.__('No Encryption','nifty_desk').' <br/>
					<input type="radio" name="nifty_desk_smtp_encryption_setting_php_mailer" id="nifty_desk_smtp_encryption_setting_php_mailer_ssl" value="SSL" checked="checked"/> '.__('SSL - (use SSL for gmail)','nifty_desk').' <br/>
					<input type="radio" name="nifty_desk_smtp_encryption_setting_php_mailer" id="nifty_desk_smtp_encryption_setting_php_mailer_tls" value="TLS"/> '.__('TLS - This is not the same as STARTTLS. For most servers SSL is the recommended option.','nifty_desk').' <br/>';
				
				}
	            elseif($nifty_desk_smtp_encryption_setting_php_mailer==='TLS')
				{
					$out.='<input type="radio" name="nifty_desk_smtp_encryption_setting_php_mailer" id="nifty_desk_smtp_encryption_setting_php_mailer_no_encryption" value="No Encryption"/> '.__('No Encryption','nifty_desk').' <br/>
					<input type="radio" name="nifty_desk_smtp_encryption_setting_php_mailer" id="nifty_desk_smtp_encryption_setting_php_mailer_ssl" value="SSL"/> '.__('SSL - (use SSL for gmail)','nifty_desk').' <br/>
					<input type="radio" name="nifty_desk_smtp_encryption_setting_php_mailer" id="nifty_desk_smtp_encryption_setting_php_mailer_tls" value="TLS" checked="checked"/> '.__('TLS - This is not the same as STARTTLS. For most servers SSL is the recommended option.','nifty_desk').' <br/>';
				
				}
				else 
				{
		           $out.='<input type="radio" name="nifty_desk_smtp_encryption_setting_php_mailer" id="nifty_desk_smtp_encryption_setting_php_mailer_no_encryption" value="No Encryption"/> '.__('No Encryption','nifty_desk').' <br/>
				  <input type="radio" name="nifty_desk_smtp_encryption_setting_php_mailer" id="nifty_desk_smtp_encryption_setting_php_mailer_ssl" value="SSL"/> '.__('SSL - (use SSL for gmail)','nifty_desk').' <br/>
				  <input type="radio" name="nifty_desk_smtp_encryption_setting_php_mailer" id="nifty_desk_smtp_encryption_setting_php_mailer_tls" value="TLS"/> '.__('TLS - This is not the same as STARTTLS. For most servers SSL is the recommended option.','nifty_desk').' <br/>';
			
				}
	
				
				$out.='<div style="font-weight:bold;" class="nifty_desk_email_settings_seperator" >'.__('In the event that SMTP sending fails, the wordpress mailer will be used as the fallback.','nifty_desk').'</div>';
			
			
			
			
			
			$out.='</div>';
			
			
		$out.='</div>';
		
		
		echo $out;
		
		
		
}

/**
 * Set's mailing timeouts
 * 
 * @param PHPMailer Object $phpmailer
 * @return PHPMailer
*/
function nifty_desk_smtp_timeout($phpmailer){
  $phpmailer->Timeout = 20;
  $phpmailer->Timelimit = 20;
  return $phpmailer;
}

/* reduce the timeout.. some hosts set this to 300.. wtf? */
add_filter('wp_mail_smtp_custom_options', 'nifty_desk_smtp_timeout');
/**
 * Sends an automated email
 * 
 * @param string $email Email
 * @param string $subject Subject
 * @param string $message Message
 * @param string $headers Headers
 * @param string $in_reply_to Set Reply To Field
 * @param string $channel Channel to send from
 * @param int $post_id Ticket ID
 * @param array $attachments Attached files
 * @return boolean ? 
*/
function send_automated_emails($email,$subject,$message,$headers=null,$in_reply_to = false,$channel = false,$post_id = false,$attachments = false) {

    


	$nifty_desk_smtp_host_setting_php_mailer='';
	$nifty_desk_smtp_username_setting_php_mailer='';
	$nifty_desk_smtp_password_setting_php_mailer='';
	$nifty_desk_smtp_port_setting_php_mailer='';
	$nifty_desk_smtp_encryption_setting_php_mailer='';
	$replyto_address='';
	$from_address='';
	$from_name='';
	$replyto_name='';
	$wp_mail_headers='';
	
	$result=false;
	
	$data = get_option('nifty_desk_settings');

    $message = $message;





	
	if($headers === null) {
		/*none passed to function - check if any were set for pro*/
		if(function_exists("nifty_desk_pro_activate")) {
			if((isset($data['nifty_desk_automated_emails_from'])&& filter_var($data['nifty_desk_automated_emails_from'], FILTER_VALIDATE_EMAIL) !== false)&&(isset($data['nifty_desk_automated_emails_from_name'])&&ctype_alnum($data['nifty_desk_automated_emails_from_name'])===true)) {
				/*valid from and reply to headers*/
				$replyto_address=$data['nifty_desk_automated_emails_from'];
            	$from_address=$data['nifty_desk_automated_emails_from'];
            	$from_name=$data['nifty_desk_automated_emails_from_name'];
            	$replyto_name=$data['nifty_desk_automated_emails_from_name'];
				
				$wp_mail_headers['from_name']=$from_name;
				$wp_mail_headers['from_email']=$from_address;
				$wp_mail_headers['replyto_name']=$replyto_name;
                $wp_mail_headers['replyto_address']=$replyto_address;

                if ($in_reply_to) { $wp_mail_headers['In-Reply-To']=$in_reply_to; }
                if ($in_reply_to) { $wp_mail_headers['References']=$in_reply_to; }
				
			}
		}
	}
	elseif(is_array($headers)) {

    	 $replyto_address=$headers['reply_to']['address'];
    	 $from_address=$headers['from']['address'];
    	 $from_name=$headers['from']['name'];
    	 $replyto_name=$headers['reply_to']['name'];
    	 
    	 $wp_mail_headers['from_name']=$from_name;
    	 $wp_mail_headers['from_email']=$from_address;
    	 $wp_mail_headers['replyto_name']=$replyto_name;
    	 $wp_mail_headers['replyto_address']=$replyto_address;
        if ($in_reply_to) { $wp_mail_headers['In-Reply-To']=$in_reply_to; }
        if ($in_reply_to) { $wp_mail_headers['References']=$in_reply_to; }
	}
    $wp_mail_headers = apply_filters("nifty_desk_email_header_filter",$wp_mail_headers,$channel);

	
	if(isset($data['nifty_desk_smtp_host_setting_php_mailer'])) { $nifty_desk_smtp_host_setting_php_mailer=$data['nifty_desk_smtp_host_setting_php_mailer']; }
	if(isset($data['nifty_desk_smtp_username_setting_php_mailer'])) { $nifty_desk_smtp_username_setting_php_mailer=$data['nifty_desk_smtp_username_setting_php_mailer']; }
	if(isset($data['nifty_desk_smtp_password_setting_php_mailer'])) {	$nifty_desk_smtp_password_setting_php_mailer=$data['nifty_desk_smtp_password_setting_php_mailer']; }
	if(isset($data['nifty_desk_smtp_port_setting_php_mailer'])) { $nifty_desk_smtp_port_setting_php_mailer=$data['nifty_desk_smtp_port_setting_php_mailer']; }
	if(isset($data['nifty_desk_smtp_encryption_setting_php_mailer'])) { $nifty_desk_smtp_encryption_setting_php_mailer=$data['nifty_desk_smtp_encryption_setting_php_mailer']; }
	
	
	


	if(isset($data['rb_nifty_desk_mailing_system_selection'])&&$data['rb_nifty_desk_mailing_system_selection']==='smtp') {
            
            include_once "includes/PHPMailer-master/PHPMailerAutoload.php";

            $php_mailer_object = new PHPMailer;
			$php_mailer_object->isSMTP();
			$php_mailer_object->Host = $nifty_desk_smtp_host_setting_php_mailer;		
			$php_mailer_object->Port = $nifty_desk_smtp_port_setting_php_mailer;
			$php_mailer_object->SMTPAuth = true;
			$php_mailer_object->SMTPSecure = $nifty_desk_smtp_encryption_setting_php_mailer;
			$php_mailer_object->Username = $nifty_desk_smtp_username_setting_php_mailer;
			$php_mailer_object->Password = $nifty_desk_smtp_password_setting_php_mailer;
			$php_mailer_object->CharSet = "UTF-8"; 
			if($from_address!==''&&$from_name!=='') {
				$php_mailer_object->setFrom($from_address,$from_name);	
			}
			
			$php_mailer_object->addAddress($email,'User');
			
			if($replyto_address!==''&&$replyto_name!=='') { $php_mailer_object->addReplyTo($replyto_address,$replyto_name);	}
			
			
			$php_mailer_object->Subject = $subject;

            if ($in_reply_to) { $php_mailer_object->addCustomHeader('In-Reply-To', $in_reply_to); }
            if ($in_reply_to) { $php_mailer_object->addCustomHeader('References', $in_reply_to); }

			
			$php_mailer_object->msgHTML($message."<br/><br/> <b>Sent using SMTP settings</b>");
		
			if(!$php_mailer_object->send()) { $result=use_wp_mail_as_default($email, $subject, $message, $wp_mail_headers,$attachments, $post_id); } 
			else {
	   			$result=true;
			}
			
	}
	else {
		$result=use_wp_mail_as_default($email, $subject, $message, $wp_mail_headers,$attachments, $post_id);
	}
    if (!$result && $post_id) {
        $issue_array = array(
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'in_reply_to' => $in_reply_to,
            'post_id' => $post_id,
            'channel' => $channel,
            'headers' => $headers
        );
        /* we couldnt send the email, flag this */
        update_post_meta($post_id, 'nifty_desk_notification_issue', $issue_array,true);
    } else {
        /* was a success, clear the issue */
        delete_post_meta($post_id, 'nifty_desk_notification_issue');
    }

	
	return $result;
	
	
}


add_filter("nifty_desk_wrap_body_in_html","nifty_desk_filter_control_wrap_body_in_html",10,1);
/**
 * Wrap contents in standard HTML body
 * 
 * @param string $content Content
 * @return string
*/
function nifty_desk_filter_control_wrap_body_in_html($content) {

    $html_top = ''.
'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <meta http-equiv=3D"Content-Type" content=3D"text/html; charset=3Dutf-8" />
  <style type=3D"text/css">
    table td {
      border-collapse: collapse;
    }
    body[dir=3Drtl] .directional_text_wrapper { direction: rtl; unicode-bidi: embed; }

  </style>
</head>
<body  style=3D"width: 100%!important; margin: 0; padding: 0;">
  <div style=3D"padding: 10px ; line-height: 18px; font-family: \'Lucida Grande\',Verdana,Arial,sans-serif; font-size: 12px; color:#444444;">';


$html_bottom = ''.
'</div>
</html>';

return $html_top.$content.$html_bottom;

}

/**
 * Return Mail Typ
 * 
 * @return string 
*/
function nifty_desk_set_html_mail_content_type() {
    return 'text/html';
}

/**
 * Sends an email using WordPress Mail
 * 
 * @param string $email Email
 * @param string $subject Subject
 * @param string $message Message
 * @param string $headers Headers
 * @param array $attachments Attached files
 * @return boolean ? 
*/
function use_wp_mail_as_default($email,$subject,$message,$wp_mail_headers,$attachments = false, $post_id = false) {
	add_filter( 'wp_mail_content_type', 'nifty_desk_set_html_mail_content_type' );
    $message = apply_filters("nifty_desk_wrap_body_in_html",$message);

    $result=false;
	
	if(is_array($wp_mail_headers))
	{
		$headers_mail = array();
        $headers_mail[] = 'From: '.$wp_mail_headers['from_name'].' < '.$wp_mail_headers['from_email'].' >' ;
        $headers_mail[] = 'Reply-To: '.$wp_mail_headers['replyto_name'].' < '.$wp_mail_headers['replyto_address'].' >' ;
        if (isset($wp_mail_headers['In-Reply-To'])) {
            $headers_mail[] = 'In-Reply-To: '.$wp_mail_headers['In-Reply-To'];
            $headers_mail[] = 'References: '.$wp_mail_headers['References'];
        }
        //$headers_mail.= 'Content-type: text/html; charset=utf-8' . "\r\n";
        //$headers_mail.= 'MIME-Version: 1.0' . "\r\n";        

        $headers_mail = apply_filters( 'nifty_desk_email_header_filter_string', $headers_mail, $post_id );

        $result=wp_mail($email,$subject,$message.nifty_desk_signature(),$headers_mail,$attachments);
	}
	else
	{
        $headers_mail= 'MIME-Version: 1.0' . "\r\n";
        //$headers_mail.= 'Content-type: text/html; charset=utf-8' . "\r\n";
        if (isset($wp_mail_headers['In-Reply-To'])) {
            $headers_mail.= 'In-Reply-To: '.$wp_mail_headers['In-Reply-To']."\r\n";
            $headers_mail.= 'References: '.$wp_mail_headers['References']."\r\n";
        }
		$result = wp_mail($email,$subject,$message.nifty_desk_signature(),$headers_mail,$attachments);
	}
    remove_filter( 'wp_mail_content_type', 'nifty_desk_set_html_mail_content_type' );
	return $result;
	
	
}

/**
 * Outputs the Nifty Desk signature in the email
 * @return 
 */
function nifty_desk_signature() {
    return "<br /><br />Powered by <a href='http://niftydesk.org' target='_BLANK' title='Nifty Desk' style='border:none;'><img src='".plugins_url('/images/logo.png', __FILE__)."' border='0' width='100' style='border:none;' alt='Nifty Desk' title='Nifty Desk'></a>";
}

/**
 * Outputs Update Warning
 * 
 * @return void
*/
function nifty_desk_warn_update_pro() {
    return;
}

if (isset($_GET['page']) && $_GET['page'] == "support-tickets") {
    add_filter('admin_body_class', 'nifty_desk_fold_menu_body_classes');
}

/**
 * Includes required files for the modern dashboard
 * 
 * @return void
*/
function nifty_desk_modern_dashboard(){
    
    //delete_option("nifty_desk_UPGRADE_V_4");
    //exit();
    $checker = get_option("nifty_desk_UPGRADE_V_4");
    if (is_array($checker)) {
        /* continuation */
        $limit = $checker['limit'];
        $offset = $checker['offset'];
        nifty_desk_upgrade_db_v4($limit,$offset);
    } else if ($checker == true) {
        /* all good.. */
        include 'modules/dashboard.php';
    } else {
        /* first run */
        $limit = 50;
        $offset = 0;
        nifty_desk_upgrade_db_v4($limit,$offset);
    }

    

}

/**
 * (DEPRECATED) Update Tickets 
 * 
 * @return void
*/
function nifty_desk_upgrade_db_v4($limit,$offset) {
    $posts_per_page_default = get_option("posts_per_page");
    update_option("posts_per_page",$limit);

    $count_posts = wp_count_posts('nifty_desk_tickets');
    $count_posts = $count_posts->publish;
    $checker = get_option("nifty_desk_UPGRADE_V_4");
    
    if (isset($checker['total_updated'])) { $total_updated = $checker['total_updated']; }
    else { $total_updated = 0; }


    $args = array(
        'post_type' => 'nifty_desk_tickets',
        'posts_per_page ' => $limit,
        'offset' => $offset
    );
    $ticket_counter = 0;
    $my_query = new WP_Query( $args );
    if ( $my_query->have_posts() ) {
        while ( $my_query->have_posts() ) {
            $ticket_counter++;
            
            $my_query->the_post();
            $ticket_id = get_the_ID();
            $post_meta = get_post_meta($ticket_id);

            $ticket_datetime = get_the_time('U');
            
            if (!isset($post_meta['ticket_channel_id'])) { 
                update_post_meta($ticket_id,'ticket_channel_id','');
            }

            if (!isset($post_meta['ticket_last_updated'])) {
                $data = nifty_desk_get_last_response( $ticket_id );
                if (isset($data->post_author)) {
                    $author = $data->post_author;
                    if ($author) {
                        update_post_meta($ticket_id,'ticket_last_updated',strtotime($data->post_date));
                        
                    } else {
                        update_post_meta($ticket_id,'ticket_last_updated',$ticket_datetime);
                        
                    }
                } else {
                    update_post_meta($ticket_id,'ticket_last_updated',$ticket_datetime);
                }
 
            } else {
                /* there is a date, but lets convert it to an int */
                $date = $post_meta['ticket_last_updated'][0];
                if ($date) {
                    if (strpos($date, ':') !== false) {
                        $new_date = strtotime($date);
                        update_post_meta($ticket_id,'ticket_last_updated',$new_date);
                    }
                    else {
                        /* already an int... */
                    }
                }
            }

            if (!isset($post_meta['ticket_assigned_to'])) {
                update_post_meta($ticket_id,'ticket_assigned_to','');
            }
            if (!isset($post_meta['ticket_priority'])) {
                update_post_meta($ticket_id,'ticket_priority','');
            }
            if (!isset($post_meta['ticket_public'])) {
                update_post_meta($ticket_id,'ticket_public','');
            }
            if (!isset($post_meta['ticket_status'])) {
                update_post_meta($ticket_id,'ticket_status','');
            }
            $total_updated++;

        }
    }
    if ($total_updated >= $count_posts) {
        /* we are done */
        /* refresh the page */
        update_option("nifty_desk_UPGRADE_V_4",true);
        ?>
        <script>
         var ti = setTimeout(function() { 
            location.reload();
        },2000);
        </script>
        <?php
        return;
    } else {
        /* we must continue.. */
        ?>
        <h1><?php _e( 'Database Update In Progress' ,"nifty_desk"); ?></h1>
        <p><?php _e( 'We need to upgrade the Nifty Desk database tables. Please be patient and do not navigate away from this page. The process will refresh the page through each update iteration. Once done, your ticket dashboard should be displayed.' ,"nifty_desk"); ?></p>
        <p><?php _e( 'The database update process may take a little while, so please be patient.' ,"nifty_desk"); ?></p>

        <p class="step"><strong><?php _e("Updated $total_updated of $count_posts...","nifty_desk"); ?></strong></p>
        <script>
         var ti = setTimeout(function() { 
            location.reload();
        },2000);
        </script>

        <?php
    }
    update_option("nifty_desk_UPGRADE_V_4",array("limit"=>$limit,"offset"=>$total_updated,'total_updated' => $total_updated));
    update_option("posts_per_page",$posts_per_page_default);
    
    return;
}

include 'modules/dashboard-ajax.php';

/*
add_action('nifty_desk_modern_tickets_left_column_after', 'nifty_desk_basic_pro_filtering_functionality', 10 );

function nifty_desk_basic_pro_filtering_functionality(){

    $ret = "";

    $ret .= "<div class='nifty_desk_db_column'>";

    $ret .= "<h4>". __('Filter By Ticket Privacy', 'nifty_desk')."</h4>";

    $ret .= "<p>".sprintf(__('Filtering tickets according to their privacy is only available in the %s', 'nifty_desk'), '<a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=st_ticket_filter_privacy" target="_BLANK">'.__('Pro Version', 'nifty_desk').'</a>')."</p>";

    $ret .= "</div>";

    $ret .= "<div class='nifty_desk_db_column'>";

    $ret .= "<h4>". __('Filter By Assigned Agent', 'nifty_desk')."</h4>";

    $ret .= "<p>".sprintf(__('Filtering tickets according to the agent that they are assigned to is only available in the %s', 'nifty_desk'), '<a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=st_ticket_filter_agents" target="_BLANK">'.__('Pro Version', 'nifty_desk').'</a>')."</p>";

    $ret .= "</div>";

    $ret .= "<div class='nifty_desk_db_column'>";

    $ret .= "<h4>". __('Filter By Department', 'nifty_desk')."</h4>";

    $ret .= "<p>".sprintf(__('Filtering tickets according to the department they are assigned to is only available in the %s', 'nifty_desk'), '<a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=st_ticket_filter_departments" target="_BLANK">'.__('Pro Version', 'nifty_desk').'</a>')."</p>";

    $ret .= "</div>";


    echo $ret;

}
*/

add_action('nifty_desk_text_response_after', 'nifty_desk_internal_notes_submit', 10, 3);
/**
 * Filters the primary controls for submitting an internal note 
 * 
 * @param string $string string to append to
 * @param string $data Ticket ID
 * @return string (html)
*/
function nifty_desk_internal_notes_submit( $string, $data ){

    $ret = "";

    $ret .= "<button type='button' class='button submit_ticket_internal_note' tid='".$data."' id='submit_ticket_internal_note_".$data."' style='display: none;'>".__('Save Note', 'nifty_desk')."</button>";

    $ret .= "<select class='submit_ticket_status_on_response' id='submit_ticket_status_on_response_".$data."'>";
    $ret .= "<option value='0'>".__("Submit as Open","nifty_desk")."</option>";
    $ret .= "<option value='3' selected>".__("Submit as Pending","nifty_desk")."</option>";
    $ret .= "<option value='1'>".__("Submit as Solved","nifty_desk")."</option>";
    $ret .= "</select>";


    return $string . $ret;

}

add_filter('nifty_desk_current_agent_meta', 'nifty_desk_response_type_switch', 10, 3);
/**
 * Filter the primary controls for switching between an internal note and a public response
 * 
 * @param string $string string to append to
 * @param string $data Ticket ID
 * @return string (html)
*/
function nifty_desk_response_type_switch( $string, $data ){

    $ret = "";

    $ret .= " <span class='nifty_desk_response_type_container'><span  tid='".$data."' id='nifty_desk_standard_response_".$data."' class='nifty_desk_standard_response nifty_desk_button_active'>".__('Standard Response', 'nifty_desk')."</span>  <span tid='".$data."' id='nifty_desk_private_note_".$data."' class='nifty_desk_private_note'>".__('Private Note', 'nifty_desk')."</span></span>";

    return $string.$ret;

}

/**
 * Filter Internal shortcodes from content
 * 
 * @param string $content Content to filter
 * @param string $post_id Ticket ID
 * @return string
*/
function nifty_desk_internal_tags($content,$post_id) {
    $ticket_link = get_permalink($post_id);

    $content_post = get_post($post_id);
    $content_post = $content_post->post_content;
    //$content_post = apply_filters('the_content', $content_post);
    //$content_post = str_replace(']]>', ']]&gt;', $content_post);

    $title_title = get_the_title( $post_id );


    $content = str_replace("{ticket_link}",$ticket_link,$content);
    $content = str_replace("{ticket_title}",$title_title,$content);
    $content = str_replace("{ticket_content}",$content_post,$content);
    return $content;


}

/**
 * Returns login email content
 * 
 * @param string $content Content to filter
 * @param string $post_id Ticket ID
 * @param string $username Users username
 * @param string $password Users Password (Generated)
 * @return string
*/
function nifty_desk_build_login_details_content($content,$post_id,$username,$password) {

    $login_details_content =    "<p>Please use the following credentials to access and respond to your ticket:</p>"+
                                "<p>Username: {ticket_username}<br />"+
                                "Password: {ticket_password}</p>"+
                                "<p>To login and view your ticket, please follow this link: {ticket_login_link}</p>";
                                

    $content = str_replace("{ticket_username}",$username,$content);
    $content = str_replace("{ticket_password}",$password,$content);
    $content = str_replace("{ticket_login_link}",wp_login_url(get_permalink($post_id)),$content);

    return $content;
}



add_action("nifty_desk_modern_tickets_left_column_before","nifty_desk_modern_tickets_left_column_before_insert_refresh",10);
/**
 * Outputs (Echo) Refresh button to the dashboar
 * 
 * @return void
*/
function nifty_desk_modern_tickets_left_column_before_insert_refresh() {
    echo "<img src='".plugins_url('/images/reload.png', __FILE__)."' class='nifty_desk_refresh' alt='".__("Refresh","nifty_desk")."' title='".__("Refresh","nifty_desk")."' />";
}

/**
 * Returns allowed tags for KSES fitlers
 * 
 * @return array
*/
function nifty_desk_get_allowed_tags(){
   $tags = wp_kses_allowed_html("post");
   
   $tags['iframe'] = array(
           'src'             => true,
           'width'           => true,
           'height'          => true,
           'align'           => true,
           'class'           => true,
           'style'           => true,
           'name'            => true,
           'id'              => true,
           'frameborder'     => true,
           'seamless'        => true,
           'srcdoc'          => true,
           'sandbox'         => true,
           'allowfullscreen' => true
       );
   unset($tags['font']);
   $tags['input'] = array(
           'type'            => true,
           'value'           => true,
           'placeholder'     => true,
           'class'           => true,
           'style'           => true,
           'name'            => true,
           'id'              => true,
           'checked'         => true,
           'readonly'        => true,
           'disabled'        => true,
           'enabled'         => true
       );
   $tags['select'] = array(
           'value'           => true,
           'class'           => true,
           'style'           => true,
           'name'            => true,
           'id'              => true
       );
   $tags['option'] = array(
           'value'           => true,
           'class'           => true,
           'style'           => true,
           'name'            => true,
           'id'              => true,
           'selected'        => true
       );
   return $tags;
}

/**
 * Returns readable timestamp
 *
 * @param string $timestamp Time 
 * @return string 
*/
function nifty_desk_parse_date($timestamp) {

    $timestamp = $timestamp;
    $datetime1=date_create(date('Y-m-d H:i:s',current_time('timestamp')));
    $datetime2=date_create(date('Y-m-d H:i:s',$timestamp));
    $diff=date_diff($datetime1, $datetime2);
    $timemsg='';
    if($diff->y > 0){
        $timemsg = $diff->y .' year'. ($diff->y > 1?"s":'');

    }
    else if($diff->m > 0){
     $timemsg = $diff->m . ' month'. ($diff->m > 1?"s":'');
    }
    else if($diff->d > 0){
     $timemsg = $diff->d .' day'. ($diff->d > 1?"s":'');
    }
    else if($diff->h > 0){
     $timemsg = $diff->h .' hour'.($diff->h > 1 ? "s":'');
    }
    else if($diff->i > 0){
     $timemsg = $diff->i .' minute'. ($diff->i > 1?"s":'');
    }
    else if($diff->s > 0){
     $timemsg = $diff->s .' second'. ($diff->s > 1?"s":'');
    } else {
        return "Just now";
    }

    $timemsg = $timemsg.' ago';
    return $timemsg;
}

/**
 * Delete a Ticket
 *
 * @param int $ticket_id Ticket ID
 * @return boolean
*/
function nifty_desk_delete_ticket($ticket_id) {
    global $wpdb;

    $checker = $wpdb->get_results( 
        $wpdb->prepare("SELECT `post_id` FROM $wpdb->postmeta
             WHERE meta_key = %s
             AND meta_value = %d
            ",
            '_response_parent_id', $ticket_id 
            )
    );
    
    if ($checker) {
        foreach($checker as $check) {
            $response_id = intval($check->post_id);
            if ($response_id) {

            /* delete all the RESPONSE attachments */
            $checker = $wpdb->get_results( 
                $wpdb->prepare("SELECT `meta_value` FROM $wpdb->postmeta
                     WHERE meta_key = %s
                     AND post_id = %d
                    ",
                    'ticket_attachments', $response_id 
                    )
            );
            if (isset($checker[0]->meta_value)) {
                $upload_dir = wp_upload_dir();
                $udir = $upload_dir['basedir'].'/nifty-desk-uploads/'.$ticket_id."/";

                $checker = maybe_unserialize($checker[0]->meta_value);
                foreach($checker as $check) {
                    if (@file_exists($udir.$check)) {
                        unlink($udir.$check);
                        rmdir($udir);
                    }
                }
            }  


                wp_delete_post($response_id,true);
            }
        }
    }
    $checker = $wpdb->get_results( 
        $wpdb->prepare("DELETE FROM $wpdb->postmeta
             WHERE meta_key = %s
             AND meta_value = %d
            ",
            '_response_parent_id', $ticket_id 
            )
    );


    /* delete all the TICKET attachments */
    $checker = $wpdb->get_results( 
        $wpdb->prepare("SELECT `meta_value` FROM $wpdb->postmeta
             WHERE meta_key = %s
             AND post_id = %d
            ",
            'ticket_attachments', $ticket_id 
            )
    );
    if (isset($checker[0]->meta_value)) {
        $upload_dir = wp_upload_dir();
        $udir = $upload_dir['basedir'].'/nifty-desk-uploads/'.$ticket_id."/";

        $checker = maybe_unserialize($checker[0]->meta_value);
        foreach($checker as $check) {
            if (@file_exists($udir.$check)) {
                unlink($udir.$check);
                rmdir($udir);
            }
        }
    }  



    $failed = wp_delete_post($ticket_id,true);
    return $failed;
}

/**
 * Returns a ticket author
 *
 * @param int $ticket_id Ticket ID
 * @return string
*/
function nifty_desk_get_ticket_author($ticket_id){
    $post = get_post($ticket_id);
    return  get_userdata($post->post_author)->ID;
}

add_filter("nifty_desk_get_merged_id","nifty_desk_filter_control_get_merged_id",10,1);
/**
 * Recursively look for merged ID's and return the final ticket ID
 * @return  int (false on fail)
*/
function nifty_desk_filter_control_get_merged_id($ticket_id) {
    $final = true;
    while ($final) {
        $check = get_post_meta( $ticket_id, '_nifty_desk_merged_with', true );
        if ($check) {
            /* there is a merged ID, use it */
            $ticket_id = $check;
        } else {
            $final = false;
        }
    }
    return $ticket_id;

}


/**
 * Return Nifty Error 
 *
 * @param string $type 
 * @param int $post_id Ticket/Response ID
 * @return boolean
*/
function nifty_desk_build_notification_error_html($type,$post_id) {
   if ($type == 'ticket') {
        $message = "<span class='nifty_desk_error' id='nifty_desk_error_".$post_id."'>".__("There was a problem sending the ticket notification. Please check your email settings and host to make sure everything is correct and your mail ports are not blocked","nifty_desk")." <a href='javascript:void(0);' class='nifty_desk_resend_notification_button' nid='".$post_id."'>Resend</a></span>";
   } else if ($type == 'response') {
        $message = "<span class='nifty_desk_error' id='nifty_desk_error_".$post_id."'>".__("This response failed to be sent to the intended user(s). Please check your email settings and host to make sure everything is correct and your mail ports are not blocked.","nifty_desk")." <a href='javascript:void(0);' class='nifty_desk_resend_notification_button' nid='".$post_id."'>Resend</a></span>";
   }
   return $message;

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



function nifty_desk_get_post_meta_last_non_agent($post_id){
    global $wpdb;
    $data   =   array();
    $sql = "SELECT `meta_key`, `meta_value`, `post_id` FROM $wpdb->postmeta WHERE `meta_key` = '_response_parent_id' AND `meta_value` = '$post_id' ORDER BY `meta_id` DESC LIMIT 3";

    $results = $wpdb->get_results($sql);
    $i = 0;

    foreach($results as $k => $v){
        if (isset($v->post_id)) {
            $tmp_post_id = $v->post_id;
            $post_data = get_post($tmp_post_id);
            $post_author = $post_data->post_author;
            if (get_the_author_meta('nifty_desk_agent', $post_author) != "1") {
                /* this came from a non-agent */
                $data[0] = $v;
                break;        
            }
        }

        
        $i++;
    };
    return $data;
}




/**
 * Checks if any of the system pages have been trashed (not deleted forever)
 * If so, show a notice
 *
 * @return void
*/
function nifty_desk_check_if_pages_trashed(){
    $nifty_desk_submit_ticket_page_option = get_option("nifty_desk_submit_ticket_page");
    $nifty_desk_submit_ticket_page = get_post(intval($nifty_desk_submit_ticket_page_option));
    if($nifty_desk_submit_ticket_page !== null && isset($nifty_desk_submit_ticket_page->post_status)){
        if($nifty_desk_submit_ticket_page->post_status  === "trash"){
            add_action( 'admin_notices', 'nifty_desk_page_trash_notice_submit_ticket');
        }
    }

    do_action("nifty_desk_check_if_pages_trashed_hook");
}

/**
 * Displays a notice to the user that the 'Submit a Ticket' page was trashed
 *
 * @return void
*/
function nifty_desk_page_trash_notice_submit_ticket() {
    $nifty_desk_submit_ticket_page_option = get_option("nifty_desk_submit_ticket_page");
    $nifty_desk_submit_ticket_page = get_post(intval($nifty_desk_submit_ticket_page_option));
    if(isset($nifty_desk_submit_ticket_page) && isset($nifty_desk_submit_ticket_page->ID) && isset($nifty_desk_submit_ticket_page->post_title)){
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php _e( 'Nifty Desk Notice', 'sample-text-domain' ); ?>: 
               <?php _e( 'The following page has been trashed', 'sample-text-domain' ); ?>: <strong>'<?php echo $nifty_desk_submit_ticket_page->post_title ?>' </strong>
            </p>

            <p> 
               <?php _e( 'Please restore it from your trashed posts.', 'sample-text-domain' ); ?> <a href="edit.php?post_status=trash&post_type=page" class="button button-default"><?php _e("View", "nifty_desk") ?></a>
            </p>
        
        </div>
        <?php
    }
}
