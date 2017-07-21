jQuery(document).ready(function(){
	jQuery("body").on("click",".ticket_originator", function() { 
		jQuery(this).removeClass("ticket_originator");
		var tid = jQuery(this).attr('tid');
		var user_email = jQuery(this).attr('title');
		jQuery(".originator_change_"+tid).hide();
	 	jQuery(this).html('<input id="ticket_new_email_'+tid+'" type="text" value="'+user_email+'"><button class="ticket_originator_change_btn ticket_originator_change_btn_'+tid+'" tid="'+tid+'">save</button>');
	});
	jQuery("body").on("click",".originator_change", function() { 
		var tid = jQuery(this).attr('tid');
		var elem = jQuery(".ticket_originator_"+tid);
		
		jQuery(elem).removeClass("ticket_originator");
		var user_email = jQuery(elem).attr('title');
		jQuery(".originator_change_"+tid).hide();
	 	jQuery(elem).html('<input id="ticket_new_email_'+tid+'" type="text" value="'+user_email+'"><button class="ticket_originator_change_btn ticket_originator_change_btn_'+tid+'" tid="'+tid+'">save</button>');
	});

	jQuery("body").on("click",".ticket_originator_change_btn", function() {
		jQuery(this).attr('disabled',true);
		var tid = jQuery(this).attr('tid');
		var new_email = jQuery("#ticket_new_email_"+tid).val();

		var data = {
	        'nifty_desk_db_security': nifty_desk_dashboard_security,
			'action': 'nifty_desk_change_originator',
			'ticket_id': tid,
			'originator_email': new_email
	    };
	    

		jQuery.post(ajaxurl, data, function(response){
			jQuery(".ticket_originator_change_btn_"+tid).attr('disabled',false);

			try{
		    response = JSON.parse(response);
			}catch(e){
			    console.log(e); //error in the above string(in this case,yes)!
			}

			if (typeof e === "undefined") {
				console.log(response);
			}
			
			if (typeof response.user_email !== "undefined") {
				jQuery(".ticket_originator_change_btn_"+tid).remove();
				jQuery("#ticket_new_email_"+tid).remove();
				jQuery(".ticket_originator_"+tid).addClass('ticket_originator');
				jQuery(".originator_change_"+tid).show();
				jQuery(".ticket_originator_"+tid).attr('title',response.user_email);
				jQuery(".ticket_originator_"+tid).attr('alt',response.user_email);
				jQuery(".nifty_author_email_"+tid).attr('href','mailto:'+response.user_email);
				jQuery(".nifty_author_email_"+tid).html(response.user_email);
				jQuery(".ticket_originator_"+tid).html(response.user_email);
				jQuery(".author_display_name_"+tid).html(response.user_name);


			}

		});

	});

	
	
	
});	
