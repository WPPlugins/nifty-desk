<?php get_header(); ?>

<div class="nifty_desk_container">

	<div class="nifty_desk_row">
		<div class="nifty_desk_col_8">
			<h2><?php _e("Subject:", "nifty_desk"); ?>
				<?php echo do_shortcode("[nifty_desk_ticket_title]"); ?>
			</h2>
		</div>

	</div>

	<div class="nifty_desk_row">
		<div class="nifty_desk_col_8">
			<h4>Add a Reply:</h4>
			<?php echo do_shortcode("[nifty_desk_ticket_response_form]"); ?>
			<?php echo do_shortcode("[nifty_desk_ticket_notices]"); ?>
		</div>

		<div class="nifty_desk_col_3 nifty_desk_pull_right nifty_desk_text_align_right"> 
			<h4><?php _e("Ticket Author", "nifty_desk"); ?>:</h4>
			<?php echo do_shortcode("[nifty_desk_ticket_author_details]"); ?>
		</div>

	</div>
	
	<div class="nifty_desk_row">
		<div class="nifty_desk_col_12">
			<?php echo do_shortcode("[nifty_desk_ticket_responses]"); ?>
		</div>
		
	</div>
</div>


<?php get_footer(); ?>

