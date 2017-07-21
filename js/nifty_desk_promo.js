jQuery(document).ready(function(){

	jQuery("body").on("click","#nifty_desk_close_review", function() {
        
        jQuery("#nifty_desk_review_div").fadeOut("fast");

        var data = { 

        	action: 'close_st_review',
        	security: nifty_desk_security 

        };

        jQuery.post( ajaxurl, data, function( response ) {

        	//console.log(response);
           
        });


    });


});