<?php




?>

<div class="wrap">
	<div class="nifty_desk_db_container">
			<?php do_action( 'nifty_desk_modern_tickets_tab_pane_before' ); ?>
			<!-- <div class="nifty_desk_db_full_column nifty_desk_db_column">
				Tabs
			</div> -->
			<?php do_action( 'nifty_desk_modern_tickets_tab_pane_after' ); ?>

    <div id="nifty_desk_overlay_container" style='display:none;'>
      <div id="nifty_desk_overlay_bg"></div>
	<div id="nifty_desk_the_thing_that_shouldnt_be">
	  <div id="nifty_desk_prompt_title"></div>
		<div id="nifty_desk_prompt_content">

	      	
	  </div>
	  <div id='nifty_desk_prompt_actions'>
	  	<div id='nifty_desk_prompt_actions_container'>
	  		<button class='nifty_desk_prompt_button button nifty_desk_prompt_close'></button> 
	  		<button class='nifty_desk_prompt_button button button-primary nifty_desk_prompt_accept'></button>

  		</div>
	  </div>
	</div>
    </div>

		<div id='nifty_desk_tabs'>

		    <ul id='nifty_desk_tabs_ul'>
		        <li><a href='#tab1' class='nifty_desk_tabselector'><?php _e("Dashboard","nifty_desk"); ?></a></li>
		    </ul>
		    <div id='tab1'>
	  
				<div class="nifty_desk_db_inner_container">

					<div class="nifty_desk_db_left_column">
						<div class="nifty_desk_db_left_column_inner">
							<?php do_action( 'nifty_desk_modern_tickets_left_column_before' ); ?>
							<div class='nifty_desk_db_column nifty_desk_db_ticket_meta_info' style='display: none;'></div>
							<div class='nifty_desk_db_column nifty_desk_db_controls_search'>
								<h4><?php _e('Search for a Ticket', 'nifty_desk'); ?></h4>
								<input type='text' class='nifty_desk_ticket_meta_input' id='nifty_desk_modern_search' placeholder="<?php _e("Press 'Enter' to search", "nifty_desk"); ?>" />
							</div>

							<div class='nifty_desk_db_column nifty_desk_db_controls_primary'>
								<?php do_action("nifty_desk_view_control"); ?>
							</div>


							
							<?php do_action( 'nifty_desk_modern_tickets_left_column_after' ); ?>
		
						</div>	
						<?php do_action( 'nifty_desk_modern_tickets_left_column_after_wrapper' ); ?>
					</div>
					<div class="nifty_desk_db_center_column">
						<div class="nifty_desk_db_center_column_inner">


									<div class='nifty_desk_dashboard_view_control'></div>
									<div class='nifty_desk_db_ticket_above_container'></div>
									<div class='nifty_desk_modern_ticket_actions'>
										<div class='nifty_desk_ticket_action_inner' style="display:none;">
											<select class="nifty_desk_dropdown" id="nifty_desk_modern_bulk_select_primary">
												<option value="nifty_desk_db_bulk_delete_tickets"><?php _e('Delete', 'nifty_desk'); ?></option>
												<?php do_action("nifty_desk_dashboard_actions_primary"); ?>
											</select>

											<?php do_action("nifty_desk_dashboard_actions_after_primary"); ?>

											<button class='button' id='nifty_desk_modern_bulk_action'><?php _e('Apply', 'nifty_desk'); ?></button>
										</div>
									</div>
									<?php do_action( 'nifty_desk_modern_tickets_right_column_before' ); ?>

									<table class="nifty_desk_db_ticket_container">
										<thead>
											<tr>
												<td class='ticket_checkbox'><input type="checkbox" id='nifty_desk_db_check_all' /></td>
												<td class='ticket_status_clause nifty_desk_sortby' sortbytype='meta' sortbyvalue='ticket_status' sortby='ASC'>&nbsp;</td>
												<td class='ID nifty_desk_sortby' sortbytype='default' sortbyvalue='ticket_id' sortby='ASC'><?php _e("ID","nifty_desk_"); ?></td>
												<?php echo apply_filters("nifty_desk_ticket_view_list_column_header",""); ?>
												<td class='title nifty_desk_sortby' sortbytype='default' sortbyvalue='ticket_title' sortby='ASC'><?php _e('Subject', 'nifty_desk'); ?></td>
												<td class='author nifty_desk_sortby' sortbytype='default' sortbyvalue='ticket_author' sortby='ASC'><?php _e('Requester', 'nifty_desk'); ?></td>
												<td class='date nifty_desk_sortby' sortbytype='default' sortbyvalue='ticket_created' sortby='ASC'><?php _e('Created', 'nifty_desk'); ?></td>
												<td class='ticket_last_updated_clause nifty_desk_sortby ticket_responser' sortbytype='meta' sortbyvalue='ticket_last_updated' sortby='ASC'><?php _e('Updated', 'nifty_desk'); ?></td>
												<td class='ticket_priority_clause nifty_desk_sortby' sortbytype='meta' sortbyvalue='ticket_priority' sortby='ASC'><?php _e('Priority', 'nifty_desk'); ?></td>
												<td class='ticket_responses'><?php _e('Responses', 'nifty_desk'); ?></td>
												<td class='ticket_assigned_to_clause nifty_desk_sortby' sortbytype='meta' sortbyvalue='ticket_assigned_to' sortby='ASC'><?php _e('Owner', 'nifty_desk'); ?></td>
												<td class='ticket_channel_id_clause nifty_desk_sortby'  sortbytype='meta' sortbyvalue='ticket_channel_id' sortby='ASC'><?php _e('Channel', 'nifty_desk'); ?></td>
											</tr>
										</thead>
										<tbody></tbody>					
									</table>
									<div class='nifty_desk_modern_ticket_pagination'>
										<span style='display:none;' offset='0' limit='20' id='nifty_desk_modern_pagination_controls'>&nbsp;</span>
										<button class='button' id='nifty_desk_modern_pagination_previous'><?php _e('Previous', 'nifty_desk'); ?></button>
										<button class='button' id='nifty_desk_modern_pagination_next'><?php _e('Next', 'nifty_desk'); ?></button>

										<a href="http://niftydesk.org/support-desk/?utm_source=plugin&utm_medium=link&utm_campaign=st_documentation_dashboard"><div class='nifty_desk_question_icon_dashboard' title="<?php _e('Need help? View our documentation') ?>">?</div></a>
									</div>
								
							
							<div class="nifty_desk_db_single_ticket_handle"></div>
						</div>
					</div>
					<!-- Add when on a single ticket page -->
					<!-- <div class="nifty_desk_db_center_column nifty_desk_db_column nifty_desk_action_panel">
						<button class='button button-primary'><?php // _e( 'Submit', 'nifty_desk' ); ?></button>
					</div> -->
				</div>
	  		</div>
    	</div>
	</div>
</div>