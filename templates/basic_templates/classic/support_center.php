<?php get_header(); ?>
<!-- Nifty Desk Pro Only -->
<div class="nifty_desk_container">

	<div class="nifty_desk_row">
		<div class="nifty_desk_col_12">
			<h2><?php _e("Help Desk", "nifty_desk"); ?> <?php echo do_shortcode("[nifty_desk_support_centre_submit_link]"); ?></h2><br>
		</div>
	</div>

	<div class="nifty_desk_row">
		<div class="nifty_desk_col_12">
			<?php echo do_shortcode("[nifty_desk_support_centre_search_box]"); ?><br>
		</div>
	</div>

	<div class="nifty_desk_row">

		<div class="nifty_desk_col_8">
			<h2><?php _e("Documentation", "nifty_desk"); ?></h2>
			<div class="nifty_desk_max_height_300">
				<?php echo do_shortcode("[nifty_desk_support_centre_documentation catid='0']"); ?>
			</div>
			<hr>

			<h2><?php _e("News", "nifty_desk"); ?></h2>
			<div class="nifty_desk_max_height_300">
				<?php echo do_shortcode("[nifty_desk_support_centre_news]"); ?>
			</div>
			<hr>

			<h2><?php _e("Updates", "nifty_desk"); ?></h2>
			<div class="nifty_desk_max_height_300">
				<?php echo do_shortcode("[nifty_desk_support_centre_product_updates]"); ?>
			</div>
			<hr>
		</div>

		<div class="nifty_desk_col_3 nifty_desk_text_align_right nifty_desk_pull_right">
			<h3><?php _e("Support Tickets", "nifty_desk"); ?></h3>
			<hr> 

			<h4><?php _e("Latest", "nifty_desk"); ?></h4>
			<div class="nifty_desk_max_height_300">
				<?php echo do_shortcode("[nifty_desk_support_centre_all_tickets]"); ?>
			</div>
			<br>

			<h4><?php _e("Open", "nifty_desk"); ?></h4>
			<div class="nifty_desk_max_height_300">
				<?php echo do_shortcode("[nifty_desk_support_centre_open_tickets]"); ?>
			</div>
			<br>

			<h4><?php _e("Closed", "nifty_desk"); ?></h4>
			<div class="nifty_desk_max_height_300">
				<?php echo do_shortcode("[nifty_desk_support_centre_closed_tickets]"); ?>
			</div>
			<br>

			<h4><?php _e("Pending", "nifty_desk"); ?></h4>
			<div class="nifty_desk_max_height_300">
				<?php echo do_shortcode("[nifty_desk_support_centre_pending_tickets]"); ?>
			</div>
		</div>
	</div>
	
</div>


<?php get_footer(); ?>

