<?php

function nifty_desk_autoassign_html_output() {
	do_action("nifty_desk_autoassign_output_html");
}

add_action("nifty_desk_autoassign_output_html","nifty_desk_basic_autoassign_html_output",10);
function nifty_desk_basic_autoassign_html_output() {
	$current_channels = get_option("nifty_desk_schedules");

	echo "<table class='wp-list-table widefat fixed striped pages'>";
	echo "<thead>";
	echo "<tr>";
	echo "<th>".__("Auto Assign Schedules","nifty_desk")."</th>";
	echo "<th class='nifty_desk_table_action'>Action</th>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";
	echo "<tr><td colspan='2'>".__("No schedules. Set up schedules with the Pro version.","nifty_desk")."</td></tr>";
	echo "</tbody>";
	echo "</table>";
}