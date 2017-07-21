<?php

function nifty_desk_channel_html_output() {


	do_action("nifty_desk_channels_output_html");




}

add_action("nifty_desk_channels_output_html","nifty_desk_basic_channel_html_output",10);
function nifty_desk_basic_channel_html_output() {
	$current_channels = get_option("nifty_desk_channels");

	echo "<p><span class='update-nag'>".sprintf(__("Upgrade to the <a href='%s' target='_BLANK'>Pro version</a> of Nifty Desk and convert emails to support tickets from multiple email accounts.","nifty_desk"),'http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=channels')."</span></p>";



	echo "<table class='wp-list-table widefat fixed striped pages'>";
	echo "<thead>";
	echo "<tr>";
	echo "<th>".__("Channel","nifty_desk")."</th>";
	echo "<th class='nifty_desk_table_action'>Action</th>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";
	if ($current_channels) {
		foreach ($current_channels as $key => $channel) {
			echo "<tr id='view_tr_'".$key.">";
			echo "<td>".$channel['title']."</td>";
			echo "<td>".apply_filters("nifty_desk_filter_channel_action_control","",$key)."</td>";
			echo "</tr>";


		}
	} else {
		echo "<tr><td colspan='2'>".__("No channels","nifty_desk")."</td></tr>";
	}
	echo "</tbody>";
	echo "</table>";
}