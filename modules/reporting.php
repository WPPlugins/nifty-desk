<?php
/* Handles reporting functionality */
include("reporting-ajax.php");

/*
 * Register the dashboard widget
*/
add_action('wp_dashboard_setup', 'nifty_desk_register_reporting_dash_components');
function nifty_desk_register_reporting_dash_components(){
	wp_add_dashboard_widget("nifty_desk_reporting_dashboard_widget", __("Nifty Desk - Reporting", "nifty_desk"), "nifty_desk_reporting_widget");
}

/*
 * Creates the reporting window (Minimal)
 * Used in widget view
*/
function nifty_desk_reporting_widget(){
	nifty_desk_reporting_enqueue_styles();
	nifty_desk_reporting_enqueue_scripts();

	do_action("nifty_desk_reporting_widget_head_hook"); //For adding widget exclusive code of styles

	nifty_desk_reporting_action_bar();
	nifty_desk_reporting_separate();

	nifty_desk_reporting_primary_stats();
	nifty_desk_reporting_separate();

	nifty_desk_draw_primary_stats_chart();
	nifty_desk_all_stats_button();
}

/*
 * Register the reporting page
*/
add_action('nifty_desk_admin_menu_above', 'nifty_desk_admin_menu_reporting', 1);
function nifty_desk_admin_menu_reporting(){
	add_submenu_page('support-tickets', __('Reporting', 'nifty_desk'), __('Reporting', 'nifty_desk'), 'manage_options', 'nifty-desk-menu-reporting', 'nifty_desk_reporting_page');
}
/*
 * Creates the reporting window
 * Used in page view
*/
function nifty_desk_reporting_page(){
	?>
		<h1><?php _e("Nifty Desk - Reporting", "nifty_desk"); ?></h1>
	<?php
	nifty_desk_reporting_enqueue_styles();
	nifty_desk_reporting_enqueue_scripts();

	nifty_desk_open_container();
	nifty_desk_reporting_action_bar();
	nifty_desk_reporting_separate();
	nifty_desk_reporting_primary_stats();
	nifty_desk_reporting_separate();

	?>
	<div class="nifty_desk_reporting_row">
		<div class="nifty_desk_reporting_col_30">
	<?php
	nifty_desk_draw_primary_stats_chart();
	?>
		</div>
	<?php

	?>
		<div class="nifty_desk_reporting_col_70">
	<?php
		do_action("nifty_desk_reporting_page_grid_area_hook");
	?>
		</div>
	</div>
	<?php	
	nifty_desk_close_container();
	?>
	<div class="nifty_desk_container_clear">
		<div class="nifty_desk_reporting_row" style="text-align:left">
	<?php
	do_action("nifty_desk_reporting_page_after_hook");
	?>
		</div>
	</div>
	<?php
	
}

/*
 * Loads stylesheet
*/
function nifty_desk_reporting_enqueue_styles(){
	wp_register_style('nifty_desk_reporting_css', plugins_url('/css/', dirname(__FILE__)).'reporting.css');
	wp_enqueue_style("nifty_desk_reporting_css");	
}

/*
 * Loads scripts
*/
function nifty_desk_reporting_enqueue_scripts(){
	wp_register_script('nifty-desk-reporting', plugins_url('/js/', dirname(__FILE__)).'reporting.js', array('jquery'), '', true);

    $nifty_desk_dashboard_nonce = wp_create_nonce("nifty_desk_reporting");

    wp_localize_script('nifty-desk-reporting', 'nifty_desk_rep_security', $nifty_desk_dashboard_nonce);
    wp_localize_script('nifty-desk-reporting', 'nifty_desk_rep_ajax_icon_url', plugins_url('/images/', dirname(__FILE__)).'ajax-loader.gif');

    wp_localize_script('nifty-desk-reporting', 'nifty_desk_rep_total', __("Total Tickets", "nifty_desk"));
    wp_localize_script('nifty-desk-reporting', 'nifty_desk_rep_solved', __("Solved Tickets", "nifty_desk"));

    wp_enqueue_script('nifty-desk-reporting');

    do_action("nifty_desk_reporting_js_hook");
}

/*
 * Creates the actions
*/
function nifty_desk_reporting_action_bar(){
	?>
		<div class="nifty_desk_reporting_actions">
			<?php do_action("nifty_desk_reporting_action_bar_before_hook"); ?>
			<select class="nifty_desk_reporting_action_dropdown" id="nifty_desk_rep_action_period">
				<option value="0" autoFire><?php _e("Last 24 hours", "nifty_desk"); ?></option>
				<option value="1" autoFire><?php _e("Last 7 days", "nifty_desk"); ?></option>
				<option value="2" autoFire selected><?php _e("Last 30 days", "nifty_desk"); ?></option>
				<option value="3" autoFire><?php _e("Last 60 days", "nifty_desk"); ?></option>
				<?php do_action("nifty_desk_reporting_period_action_hook"); ?>
			</select>
			<?php do_action("nifty_desk_reporting_action_bar_after_hook"); ?>
		</div>
	<?php
}

/*
 * Creates the primary stats (counts etc)
*/
function nifty_desk_reporting_primary_stats(){
	?>
		<div class="nifty_desk_reporting_row">
			<div class="nifty_desk_reporting_col_30 nifty_deskat_heading">
				<span id="ticket_count_current_new"><?php nifty_desk_show_ajax_loader("50"); ?></span>
				<span class="nifty_deskat_heading_sub"><?php _e("Total Tickets", "nifty_desk") ?></span>
			</div>
			<div class="nifty_desk_reporting_col_30 nifty_deskat_heading">
				<span id="ticket_count_current_closed"><?php nifty_desk_show_ajax_loader("50"); ?></span>
				<span class="nifty_deskat_heading_sub"><?php _e("Solved Tickets", "nifty_desk") ?></span>
			</div>
			<div class="nifty_desk_reporting_col_30 nifty_deskat_heading">
				<span id="ticket_average_res_time"><?php nifty_desk_show_ajax_loader("50"); ?></span>
				<span class="nifty_deskat_heading_sub"><?php _e("First Reply Time", "nifty_desk") ?></span>
			</div>
			<?php do_action("nifty_desk_reporting_primary_stats_hook"); ?>
		</div>
	<?php
}


function nifty_desk_draw_primary_stats_chart(){
	?>
		<div class="nifty_desk_rep_chart" id="nifty_desk_rep_chart_primary"></div>
	<?php
}

/*
 * Creates an HR tag
*/
function nifty_desk_reporting_separate(){
	?><hr><?php
}

function nifty_desk_show_ajax_loader($max_width_perc){
	?>
		<img style="max-width: <?php echo $max_width_perc;?>px;" src="<?php echo  plugins_url('/images/', dirname(__FILE__)).'ajax-loader.gif' ?>"/>
	<?php	
}

function nifty_desk_open_container(){
	?>
		<div class="nifty_desk_container nifty_desk_border nifty_desk_rounded">
	<?php
}

function nifty_desk_close_container(){
	?>
		</div>
	<?php
}

add_action("nifty_desk_reporting_page_grid_area_hook", "nifty_desk_reporting_page_grid_basic_upsell", 10);
function nifty_desk_reporting_page_grid_basic_upsell(){
	?>
		<div class="nifty_desk_border nifty_desk_rounded_container_grey">
			<div style="font-size:16px;margin-top: 87px;"> 
				<span style="display:block"><?php _e("Get Comparison Charts", "nifty_desk"); ?></span><br>
				<span style="display:block; font-size:20px"><strong><?php _e("Upgrade to the premium version", "nifty_desk"); ?></strong></span><br>
				<a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=reporting" target="_BLANK" class="button button-primary">
					<?php _e("Upgrade now" ,"nifty_desk"); ?>
				</a>
			</div>
		</div>
	<?php
}

function nifty_desk_all_stats_button(){
	?>
		<a href="<?php echo admin_url('admin.php') ?>?page=nifty-desk-menu-reporting"  style="width: 100%;text-align: center;" class="button"><?php _e("View All Statistics"); ?></a>
	<?php
}