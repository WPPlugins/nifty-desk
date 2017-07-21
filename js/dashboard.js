var tid;
var nifty_desk_file = new Array();
var dashboard_obj = new Object();
var num_tabs;
var ticket_id;
var view_type;
var nifty_desk_limit = 20;
var nifty_desk_offset = 0;
var nifty_desk_rotate_degree = 0, rotate_timer, nifty_desk_is_refreshing;

var current_nd_ajax;

jQuery(document).ready(function(){

	/* set timer ... */


	var nifty_desk_main_timer = setInterval(function() {
		jQuery(".nifty_desk_refresh").click();
	},600000);

    jQuery("#nifty_desk_tabs").tabs({});
            


	/* check if we are looking at a ticket */
	var items = location.search.substr(1).split("&");
	tid = false;
    for (var index = 0; index < items.length; index++) {
        tmp = items[index].split("=");
        if (tmp[0] == "tid") {
	        if (typeof tmp[1] !== "undefined") {
	        	tid = tmp[1];
	        } else {
	        	tid = false;
	        }
	    } else {
	    	tid = false;
	    }
    }


    if (tid) {
    	view_type = 1;
    	nifty_desk_view_ticket(parseInt(tid));

    	nifty_desk_fetch_tickets_by_view(1,nifty_desk_limit,0,0,false,false,false);
    }
    else { 
    	view_type = 1;
		nifty_desk_fetch_tickets_by_view(view_type,nifty_desk_limit,0,0,false,false,false);
	}	

});

function nifty_desk_add_tab(tid,callback) {

	if (jQuery("#nifty_desk_tab_"+tid).length > 0) {
		/* do nothing */
	} else {


		nifty_desk_offset = jQuery("#nifty_desk_modern_pagination_controls").attr('offset');
		nifty_desk_limit = jQuery("#nifty_desk_modern_pagination_controls").attr('limit');

	    jQuery("#nifty_desk_tabs_ul").append(
	        "<li id='nifty_desk_tab_"+tid+"'><a ticketid='"+tid+"' class='nifty_desk_tabselector' id='tickettaba_"+tid+"' fromlimit='"+nifty_desk_limit+"' fromoffset='"+nifty_desk_offset+"' fromview='"+view_type+"' href='#tab" + tid + "'>#" + tid + "</a> <a href='javascript:void(0);' class='tabclose' tabid='"+tid+"'>x</a></li>"
	    );
		jQuery("#nifty_desk_tabs").append(
	        "<div id='tab" + tid + "'></div>"
	    );
	    jQuery("#nifty_desk_tabs").tabs("refresh");
	}
    return callback("tab" + tid);

}
function nifty_desk_remove_tab(tid,refresh_view) {

	jQuery.event.trigger({type: "nifty_desk_db_close_ticket", current_ticket_id: tid}); 

    jQuery("#nifty_desk_tab_"+tid).remove();
    jQuery("#tab"+tid).remove();
    jQuery("#nifty_desk_tabs").tabs("refresh");
	nifty_desk_fetch_tickets_by_view(view_type,nifty_desk_limit,nifty_desk_offset,0,false,false,false);


}
function removeURLParameter(url, parameter) {
    //prefer to use l.search if you have a location/link object
    var urlparts= url.split('?');   
    if (urlparts.length>=2) {

        var prefix= encodeURIComponent(parameter)+'=';
        var pars= urlparts[1].split(/[&;]/g);

        //reverse iteration as may be destructive
        for (var i= pars.length; i-- > 0;) {    
            //idiom for string.startsWith
            if (pars[i].lastIndexOf(prefix, 0) !== -1) {  
                pars.splice(i, 1);
            }
        }

        url= urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : "");
        return url;
    } else {
        return url;
    }
}


function st_add_to_object(obj_name,key,val,callback) {


	if (val === false) { callback(obj_name); return; }


	if (typeof obj_name[key] === "undefined") {
		obj_name[key] = new Object();
	}

	if (typeof obj_name[key][val] === "undefined") {
		obj_name[key][val] = true;
	} else {
	}
	
	callback(obj_name);
}





function nifty_desk_fetch_tickets(status,limit,offset,priority) {


	st_add_to_object(dashboard_obj,"status",status, function(dashboard_obj2) {

		st_add_to_object(dashboard_obj2,"priority",priority, function(dashboard_obj) {



			url = document.URL ;
			newurl = removeURLParameter(url,"tid");
			
			window.history.pushState(
		        {
		            "html":"",
		            "pageTitle":""
		        },
		        "",
		        newurl
		   );


			jQuery(".nifty_desk_modern_ticket_actions").css('display', 'block');
			jQuery(".nifty_desk_db_single_ticket_handle").html("");	
			//jQuery(".nifty_desk_db_ticket_container").css("display", "table");
			//jQuery(".nifty_desk_db_ticket_container tbody").html("<tr><td colspan='8'><img src='"+nifty_desk_db_plugins_url+"/nifty-desk/images/ajax-loader.gif' style='display: block; margin: 0 auto;' /></td></tr>");
			
			var main_action = 'nifty_desk_db_request_tickets_from_control';
			/*if (query_type === 'priority') {
				main_action = 'nifty_desk_db_request_tickets_from_control_priority';
			}*/


			var data = {
				'nifty_desk_db_security': nifty_desk_dashboard_security,
				'action': main_action,
				'offset' : offset,
				'limit' : limit,
				'ticket_status': status,
				'priority': priority
			}


			current_nd_ajax = jQuery.post( ajaxurl, data, function(response){
				response = JSON.parse(response);

				if (typeof response.is_more !== "undefined" && response.is_more !== true) {
					jQuery("#nifty_desk_modern_pagination_next").attr('disabled', true);
				} else if (typeof response.is_more !== "undefined" && response.is_more === true) {
					jQuery("#nifty_desk_modern_pagination_next").removeAttr('disabled');
				} else {
					jQuery("#nifty_desk_modern_pagination_next").removeAttr('disabled');
				}

				if (typeof response.is_less !== "undefined" && response.is_less !== true) {
					jQuery("#nifty_desk_modern_pagination_previous").attr('disabled', true);
				} else if (typeof response.is_less !== "undefined" && response.is_less === true) {
					jQuery("#nifty_desk_modern_pagination_previous").removeAttr('disabled');
				} else {
					jQuery("#nifty_desk_modern_pagination_previous").removeAttr('disabled');
				}



				jQuery(".nifty_desk_db_ticket_container tbody").html(response.ticket_html);
				jQuery("#nifty_desk_modern_pagination_controls").attr('ticket_status',status);
			});



		});
	});
	
}

jQuery("body").on("click",".tabclose", function() {
	var ticket_id = jQuery(this).attr('tabid');

	offset = jQuery("#tickettaba_"+ticket_id).attr('fromoffset');
	limit = jQuery("#tickettaba_"+ticket_id).attr('fromlimit');
	view_type = jQuery("#tickettaba_"+ticket_id).attr('fromview');

	if(typeof nifty_desk_file !== "undefined" && nifty_desk_file[ticket_id] !== "undefined"){
		delete nifty_desk_file[ticket_id];
	}

	nifty_desk_remove_tab(ticket_id,true);
});

jQuery("body").on("click",".nifty_desk_tabselector", function() {
	if (typeof jQuery(this).attr('ticketid') !== 'undefined' && jQuery(this).attr('ticketid').length > 0) {
		ticket_id = jQuery(this).attr('ticketid');	
	    newurl = removeURLParameter(document.URL,"tid");
		window.history.pushState({"html":"","pageTitle":""},"",newurl);
		newurl = addUrlParam(document.URL,"tid",ticket_id);
		window.history.pushState({"html":"","pageTitle":""},"",newurl);


	} else {
		newurl = removeURLParameter(document.URL,"tid");
		window.history.pushState({"html":"","pageTitle":""},"",newurl);
	}
	
	

});

jQuery("body").on("click", "#nifty_desk_modern_pagination_next", function(){
	offset = jQuery("#nifty_desk_modern_pagination_controls").attr('offset');
	limit = jQuery("#nifty_desk_modern_pagination_controls").attr('limit');
	offset = parseInt(offset)+parseInt(limit);
	jQuery("#nifty_desk_modern_pagination_controls").attr('offset',offset);


	query = jQuery("#nifty_desk_modern_pagination_controls").attr('query');
	if (query === "search") {
		s = jQuery("#nifty_desk_modern_pagination_controls").attr('s');
		nifty_desk_fetch_tickets_by_search(s,limit,offset,false,false,false);
	} else {

		nifty_desk_fetch_tickets_by_view(view_type,limit,offset,0,false,false,false);

	}




});
jQuery("body").on("click", "#nifty_desk_modern_pagination_previous", function(){
	offset = jQuery("#nifty_desk_modern_pagination_controls").attr('offset');

	limit = jQuery("#nifty_desk_modern_pagination_controls").attr('limit');

	offset = parseInt(offset) - parseInt(limit);
	if (offset < 0) { offset = 0; }
	jQuery("#nifty_desk_modern_pagination_controls").attr('offset',offset);
	//nifty_desk_fetch_tickets(ticket_status,query_type,limit,offset,priority);
	nifty_desk_fetch_tickets_by_view(view_type,limit,offset,0,false,false,false);

});



jQuery("body").on("click", ".nifty_desk_view_control", function(){
	
	var ids = jQuery('.nifty_desk_view_control').map(function(index) {
	    jQuery("#"+this.id).removeClass("nifty_desk_view_active");

	});
	jQuery(this).addClass("nifty_desk_view_active");
	    

	/* set current view type */
	view_type = jQuery(this).attr('view');

	var offset = 0;
	var limit = nifty_desk_limit;
	jQuery("#nifty_desk_modern_pagination_controls").attr('offset',offset);
	jQuery("#nifty_desk_modern_pagination_controls").attr('limit',limit);

	jQuery("#nifty_desk_modern_pagination_controls").attr('view',view_type);
	jQuery("#nifty_desk_modern_pagination_controls").attr('query',"view");

	nifty_desk_fetch_tickets_by_view(view_type,limit,offset,0,false,false,false);

	

});

jQuery("body").on("click", ".nifty_desk_db_control", function(){
	
	var query_type = jQuery(this).attr('query');

	var ticket_status = jQuery(this).attr('ticket_status');
	var offset = 0;
	var limit = nifty_desk_limit;
	jQuery("#nifty_desk_modern_pagination_controls").attr('ticket_status',ticket_status);
	jQuery("#nifty_desk_modern_pagination_controls").attr('query',"default");

	nifty_desk_fetch_tickets(ticket_status,query_type,limit,offset,false);

	

});

jQuery("body").on("click", ".nifty_desk_db_priority_control", function(){

	var priority = jQuery(this).attr('priority');
	var offset = 0;
	var limit = nifty_desk_limit;
	jQuery("#nifty_desk_modern_pagination_controls").attr('query',"priority");
	nifty_desk_fetch_tickets(ticket_status,limit,offset,priority);



});

jQuery("body").on("click", ".nifty_desk_private_note", function(){

	var ticket_id = jQuery(this).attr('tid');

	jQuery(this).css('text-decoration', 'underline');
	jQuery("#nifty_desk_standard_response_"+ticket_id).css('text-decoration', 'none');
	jQuery("#nifty_desk_db_response_textarea_"+ticket_id).css("background-color", "#FFF6D9");
	jQuery("#nifty_desk_db_response_textarea_"+ticket_id).focus();
	jQuery("#submit_ticket_response_"+ticket_id).hide();
	jQuery("#submit_ticket_internal_note_"+ticket_id).show();		

});

jQuery("body").on("click", ".nifty_desk_standard_response", function(){


	var ticket_id = jQuery(this).attr('tid');

	jQuery(this).css('text-decoration', 'underline');
	jQuery("#nifty_desk_private_note_"+ticket_id).css('text-decoration', 'none');
	jQuery("#nifty_desk_db_response_textarea_"+ticket_id).css("background-color", "#FFF");
	jQuery("#nifty_desk_db_response_textarea_"+ticket_id).focus();
	jQuery("#submit_ticket_response_"+ticket_id).show();
	jQuery("#submit_ticket_internal_note_"+ticket_id).hide();		

});	

jQuery('#nifty_desk_db_check_all').click(function(event) {   
    if(this.checked) {
        jQuery('.nifty_desk_checkbox').each(function() {
            this.checked = true;                        
        });
    } else {
        jQuery('.nifty_desk_checkbox').each(function() {
            this.checked = false;                        
        });
    }
});

function nifty_desk_rotate(element) {
    jQuery(element).css({ WebkitTransform: 'rotate(' + nifty_desk_rotate_degree + 'deg)'});  
    jQuery(element).css({ '-moz-transform': 'rotate(' + nifty_desk_rotate_degree + 'deg)'});                      
    rotate_timer = setTimeout(function() {
        ++nifty_desk_rotate_degree; nifty_desk_rotate(element);
    },5);
}

jQuery(function() {
    
    

}); 
jQuery("body").on("click", ".nifty_desk_refresh", function(){
	if (nifty_desk_is_refreshing) {
		return;
	}

	nifty_desk_is_refreshing = true;
	nifty_desk_rotate(jQuery(this));

	var wheeler = jQuery(this);
	var data = {
		'nifty_desk_db_security': nifty_desk_dashboard_security,
		'action': 'nifty_desk_fetch_channels'
	}

	var ids = jQuery('.nifty_desk_view_control_ticket_count').map(function(index) {
	    jQuery(this.id).html("...");
	});

	jQuery('.nifty_desk_view_control_ticket_count').each(function(i, obj) {
	    //test
	    jQuery("#"+obj.id).html("...");
	});
	    


	jQuery.post( ajaxurl, data, function(response){

		if( response ){



			/* refresh everything but stay on the current view */
			offset = jQuery("#nifty_desk_modern_pagination_controls").attr('offset');
			limit = jQuery("#nifty_desk_modern_pagination_controls").attr('limit');
			query = jQuery("#nifty_desk_modern_pagination_controls").attr('query');
			if (query === "search") {
				s = jQuery("#nifty_desk_modern_pagination_controls").attr('s');
				nifty_desk_fetch_tickets_by_search(s,limit,offset,false,false,false);
			} else {
				nifty_desk_fetch_tickets_by_view(view_type,limit,offset,1,false,false,false);

			}

		}
		nifty_desk_is_refreshing = false
		clearTimeout(rotate_timer);


	});
});

jQuery("body").on("click", ".nifty_desk_checkbox, #nifty_desk_db_check_all", function(){
	var must_show = typeof jQuery("#nifty_desk_db_check_all").attr("checked") == "undefined" ? false : true;
	if(!must_show){
		//Bulk selector not used. Lets cycle through all checkboxes
		jQuery(".nifty_desk_checkbox").each(function(index, element){
			if(!must_show){
				must_show = typeof jQuery(this).attr("checked") == "undefined" ? false : true;
			}
		});
	}

	if(must_show){
		jQuery(".nifty_desk_ticket_action_inner").fadeIn();
	}else{
		jQuery(".nifty_desk_ticket_action_inner").fadeOut();
	}
});

jQuery("body").on("change", "#nifty_desk_modern_bulk_select_primary", function(){
	var nifty_desk_sec = jQuery("#nifty_desk_modern_bulk_select_primary").find(":selected").attr("nifty-desk-sec-ref");
	jQuery(this).attr("nifty-desk-sec-action", nifty_desk_sec);
});

jQuery("body").on("click", "#nifty_desk_modern_bulk_action", function(){

	var ticket_ids = jQuery(".nifty_desk_checkbox:checked").map(function(){

	  	return jQuery(this).val();

	}).get();

	var bulk_action = jQuery("#nifty_desk_modern_bulk_select_primary").val();
	var secondary_id = jQuery("#nifty_desk_modern_bulk_select_primary").attr("nifty-desk-sec-action");

	var secondary_action = jQuery("#nifty_desk_modern_bulk_select_secondary_" + secondary_id).length > 0 ? jQuery("#nifty_desk_modern_bulk_select_secondary_" + secondary_id).val() : false;

	var proceed = confirm('Are you sure you want to proceed?');

	if(proceed){

		var data = {
			'nifty_desk_db_security': nifty_desk_dashboard_security,
			'action': bulk_action,
			'sec_action' : secondary_action,
			'ticket_ids': JSON.stringify( ticket_ids )
		}


		jQuery.post( ajaxurl, data, function(response){

			if( response ){

				jQuery(".nifty_desk_modern_ticket_actions").append("<div class='updated nifty_desk_fade_away'><p style='text-align: left;'>"+response+"</p></div>");

				if(bulk_action == "nifty_desk_db_bulk_delete_tickets"){
					jQuery.each( ticket_ids, function( index, value ){
						jQuery("#nifty_desk_modern_ticket_row_"+value).fadeOut();
					});
				}

				var fadeaway = setTimeout(function() {
					jQuery(".nifty_desk_fade_away").fadeOut('slow');
				},1000);
				jQuery("#nifty_desk_db_check_all").attr('checked',false);

				jQuery(".nifty_desk_ticket_action_inner").fadeOut();
				jQuery(".nifty_desk_refresh").click();
			}

		});

	}

});

jQuery("body").on("click", ".nifty_desk_resend_notification_button", function() {
	var nid = jQuery(this).attr('nid');
	var thiselem = jQuery(this);
	var origtext = jQuery("#nifty_desk_error_"+nid).html();
	jQuery(this).hide();
	jQuery("#nifty_desk_error_"+nid).html('Sending...');

	var data = {
		'nifty_desk_db_security': nifty_desk_dashboard_security,
		'action': 'nifty_desk_resend_notification',
		'post_id': nid
	}

	jQuery.post( ajaxurl, data, function(response){
		try{
		    response = JSON.parse( response );
		}catch(e){
		    alert(e); //error in the above string(in this case,yes)!
		}

		if (typeof e === "undefined") {
			if (typeof response.errormsg !== "undefined") {
				jQuery("#nifty_desk_error_"+nid).html(origtext);
				jQuery(thiselem).show();

				

			} else {
				/* success */
				jQuery(thiselem).hide();
				jQuery("#nifty_desk_error_"+nid).html('The notification was sent successfully.');

			}
		} else {
			jQuery("#nifty_desk_error_"+nid).html(origtext);
			jQuery(thiselem).show();
			/* failed.. */
		}

	});



});
jQuery("body").on("click", ".nifty_desk_db_single_ticket", function(){


	//jQuery(".nifty_desk_db_ticket_container tbody").html("<tr><td colspan='8'><img src='"+nifty_desk_db_plugins_url+"/nifty-desk/images/ajax-loader.gif' style='display: block; margin: 0 auto;' /></td></tr>");
	ticket_id = jQuery(this).attr('ticket_id');
	nifty_desk_view_ticket(ticket_id);
	
	jQuery.event.trigger({type: "nifty_desk_db_open_single_ticket", current_ticket_id: ticket_id}); 


});
/**
* Add a URL parameter (or changing it if it already exists)
* @param {search} string  this is typically document.location.search
* @param {key}    string  the key to set
* @param {val}    string  value 
*/
var addUrlParam = function(search, key, val){
  var newParam = key + '=' + val,
      params = '?' + newParam;

  // If the "search" string exists, then build params from it
  if (search) {
    // Try to replace an existance instance
    params = search.replace(new RegExp('([?&])' + key + '[^&]*'), '$1' + newParam);

    // If nothing was replaced, then add the new param to the end
    if (params === search) {
      params += '&' + newParam;
    }
  }


  return params;
};
function insertParam(key, value) {
    key = encodeURI(key); value = encodeURI(value);

    var kvp = document.location.search.substr(1).split('&');

    var i=kvp.length; var x; while(i--) 
    {
        x = kvp[i].split('=');

        if (x[0]==key)
        {
            x[1] = value;
            kvp[i] = x.join('=');
            break;
        }
    }

    if(i<0) {kvp[kvp.length] = [key,value].join('=');}

    //this will reload the page, it's likely better to store this until finished
    document.URL = kvp.join('&');
 
}


/* thank you KooiInc
http://stackoverflow.com/questions/1199352/smart-way-to-shorten-long-strings-with-javascript
*/
String.prototype.trunc = String.prototype.trunc ||
      function(n){
          return (this.length > n) ? this.substr(0,n-1)+'&hellip;' : this;
      };


function nifty_desk_view_ticket(ticket_id) {

	var items = location.search.substr(1).split("&");
	tid = false;
    for (var index = 0; index < items.length; index++) {
        tmp = items[index].split("=");
        if (tmp[0] == "tid") {
	        if (typeof tmp[1] !== "undefined") {
	        	tid = tmp[1];
	        } else {
	        	tid = false;
	        }
	    } else {
	    	tid = false;
	    }
    }

    newurl = removeURLParameter(document.URL,"tid");
	window.history.pushState({"html":"","pageTitle":""},"",newurl);
	newurl = addUrlParam(document.URL,"tid",ticket_id);
	window.history.pushState({"html":"","pageTitle":""},"",newurl);
	
	
	var data = {
		'nifty_desk_db_security': nifty_desk_dashboard_security,
		'action': 'nifty_desk_db_request_ticket_from_content_list',
		'ticket_id': ticket_id
	}
	check = nifty_desk_add_tab(ticket_id, function(tabdiv) {

		jQuery('a[href=#tab'+ticket_id+']').click();
		jQuery(".nifty_desk_loader_placeholder").html("<img src='"+nifty_desk_db_plugins_url+"/nifty-desk/images/ajax-loader.gif' class='nifty_desk_loader' />");
			
		jQuery.post( ajaxurl, data, function(response){

			/*jQuery(".nifty_desk_db_ticket_container").css("display", "none");*/

			response = JSON.parse( response );

			
			jQuery("#"+tabdiv).html('<div class="nifty_desk_db_center_column"><div class="nifty_desk_db_center_column_inner">'+response.ticket+'</div></div>');
			jQuery("#"+tabdiv).prepend("<div class='nifty_desk_db_left_column'><div class='nifty_desk_db_left_column_inner'><div class='nifty_desk_db_ticket_meta_info' tid='"+ticket_id+"'>"+response.meta+"</div></div></div>");
			
			jQuery('a[href=#tab'+ticket_id+']').click();
			
			jQuery("#tickettaba_"+ticket_id).html("#"+ticket_id+" "+response.ticket_title.trunc(27));

			jQuery(".nifty_desk_db_ticket_meta_info").css('display', 'block');

			jQuery("textarea.nifty_desk_response_textarea").focus();

			jQuery('.nifty_desk_ticket_status').change(function( e ){

		    	var elem = jQuery(this);
				var ticket_id = jQuery(this).attr('tid');
				jQuery("#nifty_desk_ticket_status_"+ticket_id).attr('disabled', 'disabled');

				var new_ticket_status = jQuery(this).val();

				var data = {
					'nifty_desk_db_security': nifty_desk_dashboard_security,
					'action': 'nifty_desk_db_update_ticket_status',
					'ticket_id': ticket_id,
					'ticket_status': new_ticket_status
				}

				jQuery.post( ajaxurl, data, function(response){

					if( response ){

						setTimeout(function(){

							jQuery("#nifty_desk_ticket_status_"+ticket_id).removeAttr('disabled');

						}, 300);					

					}				

				});
				
		    });

		    jQuery('.nifty_desk_ticket_priority').change(function( e ){

				var new_ticket_priority = jQuery(this).val();
				var ticket_id = jQuery(this).attr('tid');
		    	jQuery("nifty_desk_ticket_priority_"+ticket_id).attr('disabled', 'disabled');

		        var data = {
					'nifty_desk_db_security': nifty_desk_dashboard_security,
					'action': 'nifty_desk_db_update_ticket_priority',
					'ticket_id': ticket_id,
					'ticket_priority': new_ticket_priority
				}

				jQuery.post( ajaxurl, data, function(response){

					if( response ){

						setTimeout(function(){

							jQuery("#nifty_desk_ticket_priority_"+ticket_id).removeAttr('disabled');

						}, 300);					

					}

				});
		    });

		    if( typeof nifty_desk_single_response_success == 'function' ){

		    	nifty_desk_single_response_success( ticket_id );

		    }

		});
	
	});
}

jQuery("body").on("click", ".submit_ticket_response", function(){

	jQuery('.updated').remove();


	var current_ticketid = jQuery(this).attr("tid");
	if (typeof current_ticketid == "undefined" || parseInt(current_ticketid) < 1) {
		alert("No ticket ID found. Cannot send message");
		return;
	}

	var agent_id = jQuery("#nifty_desk_agent_id_"+current_ticketid).val();
	if (typeof agent_id == "undefined" || parseInt(agent_id) < 1) {
		alert("No agent ID found. Cannot send message");
		return;
	}

	jQuery("#submit_ticket_response_"+current_ticketid).attr('disabled', 'disabled');

	formData = new FormData();
	
	formData.append('nifty_desk_db_security', nifty_desk_dashboard_security);
	formData.append('action', 'nifty_desk_submit_response');
	formData.append('status', jQuery("#submit_ticket_status_on_response_"+current_ticketid).val());
	formData.append('parent', current_ticketid);
	formData.append('content', jQuery("#nifty_desk_db_response_textarea_"+current_ticketid).val());
	formData.append('title', jQuery("#nifty_desk_response_title_"+current_ticketid).val());
	formData.append('author', agent_id);

	if (typeof nifty_desk_file !== "undefined" &&  typeof nifty_desk_file[current_ticketid] !== "undefined") {
		jQuery.each( nifty_desk_file[current_ticketid], function( index, value ){
			formData.append('file[]', nifty_desk_file[current_ticketid][index]);
		});
		//formData.append('file', nifty_desk_file);
		formData.append('ticket_id', current_ticketid);
	}
	formData.append('timestamp', Date.now());

	jQuery.ajax({
		url : ajaxurl,
		type : 'POST',
		data : formData,
		cache: false,
		processData: false, 
		contentType: false, 
		success : function(response) {

			if(parseInt(response) !== 0){

				try{
				    response = JSON.parse( response );
				}catch(e){

				}

				if (typeof e === "undefined") {
					if (typeof response.errormsg !== "undefined") {
						alert(response.errormsg);

						var nifty_tmp_response = "";

						nifty_tmp_response += "<div class='ticket_author_meta_response'>";
						nifty_tmp_response += "	 <div class='ticket_author_image ticket_responder_gravatar'>" + jQuery(".ticket_author_image").html() + "</div>";
						nifty_tmp_response += "	 <div class='ticket_author_details'>";
						nifty_tmp_response += "		<div class='ticket_author'><span class='author_name'>" + jQuery(".ticket_author").text() + "</span> | <span>Now</span></div>";
						nifty_tmp_response += "  </div>";				
						nifty_tmp_response += "	 <div class='ticket_contents ticket_contents_response'>";
						nifty_tmp_response += jQuery("#nifty_desk_db_response_textarea_"+current_ticketid).val();
						nifty_tmp_response += "	  </div></div>";


				    	jQuery("#ticket_response_content_holder_"+current_ticketid).prepend( response.content );
				    	jQuery("#nifty_desk_db_response_textarea_"+current_ticketid).val('');

						jQuery("#submit_ticket_response_"+current_ticketid).removeAttr('disabled');

					} else {
				    	if (response.content !== '') {
				    		jQuery("#nifty_desk_db_response_textarea_"+current_ticketid).val('');
					    	jQuery("#ticket_response_content_holder_"+current_ticketid).prepend( response.content );
					    	jQuery("#submit_ticket_response_"+current_ticketid).removeAttr('disabled');
					    	jQuery("#ticket_author_meta_"+current_ticketid).append("<div class='updated nifty_desk_fade_away' id='nifty_desk_fade_away_"+current_ticketid+"'><p>"+response.message+"</p></div>");
					    	jQuery("#nifty_desk_ticket_status_"+current_ticketid).val(response.status_string);

					    } else {
					    	jQuery("#submit_ticket_response_"+current_ticketid).removeAttr('disabled');
					    	jQuery("#ticket_author_meta_"+current_ticketid).append("<div class='updated nifty_desk_fade_away' id='nifty_desk_fade_away_"+current_ticketid+"'><p>"+response.message+"</p></div>");
					    	jQuery("#nifty_desk_ticket_status_"+current_ticketid).val(response.status_string);
					    }
					    jQuery(document).trigger({type: "nifty_response_sent",current_ticket_id: current_ticketid});
					}
				} else {
				    	jQuery("#submit_ticket_response_"+current_ticketid).removeAttr('disabled');
				}
    

			}

		},
		error : function (){
			jQuery("#submit_ticket_response_"+current_ticketid).removeAttr('disabled');
   		 	jQuery("#ticket_author_meta_"+current_ticketid).append("<div class='updated'><p>"+"There was an error. Please try again."+"</p></div>");
		}
	});
   	setTimeout(function() {jQuery("#nifty_desk_fade_away_"+current_ticketid).fadeOut('slow'); },1000);                  
   	jQuery.event.trigger({type: "nifty_desk_db_response_sent", current_ticket_id: current_ticketid}); 



});


jQuery("body").on("click",".nifty_desk_merged_with", function() {
	var ticket_id = jQuery(this).attr('tid');
	nifty_desk_view_ticket(ticket_id);
});
jQuery("body").on("click",".nifty_desk_recent_ticket", function() {
	var ticket_id = jQuery(this).attr('tid');
	nifty_desk_view_ticket(ticket_id);
});


jQuery("body").on("click",".nifty_desk_prompt_close,#nifty_desk_overlay_bg", function() {
	jQuery("#nifty_desk_overlay_container").fadeOut('fast');
	jQuery("#nifty_desk_the_thing_that_shouldnt_be").removeClass("nifty_desk_shakey_thing");
});
jQuery("body").on("click","#nifty_desk_merge_ticket", function() {
	var ticket_id = jQuery(this).attr('ticket_id');
	jQuery("#nifty_desk_overlay_container").fadeIn('fast');
	jQuery("#nifty_desk_the_thing_that_shouldnt_be").addClass("nifty_desk_shakey_thing");
	jQuery("#nifty_desk_prompt_title").html(nifty_desk_dashboard_strings.merge_title);
	jQuery("#nifty_desk_prompt_content").html("<p>"+nifty_desk_dashboard_strings.merge_content1+"</p><p><label>"+nifty_desk_dashboard_strings.merge_label1+"</label> <input type='text' autofocus ticket_id='"+ticket_id+"' name='nifty_desk_ticket_merge_id' value='' placeholder='12345' id='nifty_desk_ticket_merge_id' /> <a href='javascript:void(0);' class='button button-secondary'>"+nifty_desk_dashboard_strings.merge_button1+"</a></p>");
	jQuery("#nifty_desk_prompt_content").prepend("<input type='hidden' id='nifty_desk_ticket_id_merge_id' value='"+ticket_id+"' />");
	jQuery("#nifty_desk_prompt_content").append("<div id='nifty_desk_ticket_merge_preview' style='display:none !important; border:1px solid #ccc; padding:5px; width:100%; height:150px; overflow-y: auto;'></div>");

	jQuery(".nifty_desk_prompt_close").html(nifty_desk_dashboard_strings.merge_cancel);
	jQuery(".nifty_desk_prompt_accept").html(nifty_desk_dashboard_strings.merge_ok);
	
	jQuery(".nifty_desk_prompt_accept").attr('id','nifty_desk_merge_ticket_confirm');


});

jQuery("body").on("click","#nifty_desk_merge_ticket_confirm", function() {
	var ticket_id = jQuery("#nifty_desk_ticket_merge_id").attr('ticket_id');
	var to_merge_with = jQuery("#nifty_desk_ticket_merge_id").val();

	var can_continue = confirm("Are you sure you want to merge these ticket?");

	if( can_continue ){
	
		if (ticket_id.length > 0 && to_merge_with.length > 0){ 
			var data = {
		        nifty_desk_db_security: nifty_desk_dashboard_security,
				action: 'nifty_desk_merge_tickets',
				merge_into: to_merge_with,
				ticket_id:ticket_id
				
		    };
		    jQuery.post( ajaxurl, data, function(response){
				response = JSON.parse( response );
				if(response.message !== "error"){
					jQuery("#nifty_desk_overlay_container").fadeOut('fast');
					jQuery("#nifty_desk_the_thing_that_shouldnt_be").removeClass("nifty_desk_shakey_thing");
					nifty_desk_remove_tab(ticket_id,false);
					nifty_desk_view_ticket(to_merge_with);
				} else {

				}
			});
		}
	}
});

jQuery("body").on("click",".nifty_desk_sortby",function() {
	var sortbytype = jQuery(this).attr('sortbytype');
	var sortbyvalue = jQuery(this).attr('sortbyvalue');
	var sortby = jQuery(this).attr('sortby');

	query = jQuery("#nifty_desk_modern_pagination_controls").attr('query');
	if (query === "search") {
		s = jQuery("#nifty_desk_modern_pagination_controls").attr('s');
		nifty_desk_fetch_tickets_by_search(s,nifty_desk_limit,nifty_desk_offset,sortbytype,sortbyvalue,sortby);
	} else {

		nifty_desk_fetch_tickets_by_view(view_type,nifty_desk_limit,nifty_desk_offset,0,sortbytype,sortbyvalue,sortby);

	}


})

jQuery("body").on("focusout","#nifty_desk_ticket_merge_id", function() {
	var ticket_id = jQuery(this).attr('ticket_id');
	var to_merge_with = jQuery(this).val();

	if (ticket_id == to_merge_with) { 
		jQuery("#nifty_desk_prompt_content").prepend("<span class='nifty_desk_error'>"+nifty_desk_dashboard_strings.merge_error2+"</span>");
		var fadeaway = setTimeout(function() {
			jQuery(".nifty_desk_error").fadeOut('slow');
		},2000);
	} else {
		if (to_merge_with.length > 0) {
			if (jQuery.isNumeric( to_merge_with )) {
				var data = {
			        nifty_desk_db_security: nifty_desk_dashboard_security,
					action: 'nifty_desk_merge_get_ticket_details',
					ticket_id: to_merge_with
					
			    };
			    jQuery.post( ajaxurl, data, function(response){
		    		response = JSON.parse( response );
		    		jQuery("#nifty_desk_ticket_merge_preview").show();
		    		jQuery("#nifty_desk_ticket_merge_preview").html("<pre>#"+to_merge_with+"</pre> <strong>"+response.ticket_title+"</strong><br /><p>"+response.ticket_content+"</p>");
		    	});
			} else {
				jQuery("#nifty_desk_prompt_content").prepend("<span class='nifty_desk_error'>"+nifty_desk_dashboard_strings.merge_error1+"</span>");
				var fadeaway = setTimeout(function() {
					jQuery(".nifty_desk_error").fadeOut('slow');
				},2000);

			}
		} else {

		}
	}

});

jQuery("body").on("click", ".submit_ticket_internal_note", function(){

	var ticket_id = jQuery(this).attr("tid");

	jQuery('.updated').remove();

	if( jQuery("#nifty_desk_db_response_textarea_"+ticket_id).val() !== '' ){

		jQuery("#submit_ticket_internal_note").attr('disabled', 'disabled');
	    var data = {
	        nifty_desk_db_security: nifty_desk_dashboard_security,
			action: 'nifty_desk_modern_submit_internal_note',
			parent: ticket_id,
	        content: jQuery("#nifty_desk_db_response_textarea_"+ticket_id).val(),
	        title: jQuery("#nifty_desk_response_title_"+ticket_id).val(),
	        author: jQuery("#nifty_desk_agent_id_"+ticket_id).val()
	    };
	    
	    jQuery.post( ajaxurl, data, function(response){

	    	response = JSON.parse( response );

	    	jQuery("#nifty_desk_db_response_textarea_"+ticket_id).val('');

	    	jQuery("#ticket_response_content_holder_"+ticket_id).prepend( response.content );

	    	jQuery("#submit_ticket_internal_note_"+ticket_id).removeAttr('disabled');

	    	jQuery("#ticket_author_meta_"+ticket_id).append("<div class='updated'><p>"+response.message+"</p></div>");

	    });

	} 

});

jQuery("body").on("click", ".nifty_desk_private_note", function(){
	var ticket_id = jQuery(this).attr('tid');
	jQuery(this).addClass('nifty_desk_button_active');
	jQuery("#nifty_desk_standard_response_"+ticket_id).removeClass('nifty_desk_button_active');

});
jQuery("body").on("click", ".nifty_desk_standard_response", function(){
	var ticket_id = jQuery(this).attr('tid');
	jQuery(this).addClass('nifty_desk_button_active');
	jQuery("#nifty_desk_private_note_"+ticket_id).removeClass('nifty_desk_button_active');

});


jQuery("body").on("click", "#nifty_desk_delete_ticket", function(){

	var can_continue = confirm("Are you sure you want to delete this ticket?");

	if( can_continue ){

		var ticket_id = jQuery(this).attr('ticket_id');

		var data = {
	        'nifty_desk_db_security': nifty_desk_dashboard_security,
			'action': 'nifty_desk_delete_ticket',
			'ticket_id': ticket_id
	    };

	    jQuery.post( ajaxurl, data, function(response){

	    	if( response ){

				nifty_desk_fetch_tickets(0,nifty_desk_limit,0,false);	
				nifty_desk_remove_tab(ticket_id,true);
	    		
	    	}

	    });

	}

});

jQuery(document).keypress(function(e) {

    if(e.which == 13) {

        if( jQuery("#nifty_desk_modern_search").is(":focus") ){

			jQuery(".nifty_desk_db_ticket_container").css("display", "table");
			jQuery(".nifty_desk_loader_placeholder").html("<img src='"+nifty_desk_db_plugins_url+"/nifty-desk/images/ajax-loader.gif' class='nifty_desk_loader' />");




			var s = jQuery("#nifty_desk_modern_search").val();

        	nifty_desk_fetch_tickets_by_search(s,nifty_desk_limit,0,false,false,false);

        }
    }

});


function nifty_desk_fetch_tickets_by_search(s,limit,offset,sortbytype,sortbyvalue,sortby) {
	var ids = jQuery('.nifty_desk_view_control').map(function(index) {
	    jQuery("#"+this.id).removeClass("nifty_desk_view_active");

	});


	var data = {
		'nifty_desk_db_security': nifty_desk_dashboard_security,
		'action': 'nifty_desk_db_search_ticets',
		'limit' : limit,
		'offset' : offset,
		'search': s,
		'sortbytype' : sortbytype,
		'sortbyvalue' : sortbyvalue,
		'sortby' : sortby
	}

	jQuery("#nifty_desk_modern_pagination_controls").attr('query',"search");
	jQuery("#nifty_desk_modern_pagination_controls").attr('s',s);

	jQuery(".nifty_desk_db_ticket_above_container").html("<h1>Search<span class='nifty_desk_loader_placeholder'></span></h1><p>&nbsp;</p>");
	jQuery(".nifty_desk_loader_placeholder").html("<img src='"+nifty_desk_db_plugins_url+"/nifty-desk/images/ajax-loader.gif' class='nifty_desk_loader' />");

	jQuery.post( ajaxurl, data, function(response){
		response = JSON.parse(response);

		jQuery(".nifty_desk_db_ticket_container tbody").html(response.ticket_html);

		jQuery("#nifty_desk_modern_search").val("");

		if (typeof response.is_more !== "undefined" && response.is_more !== true) {
			jQuery("#nifty_desk_modern_pagination_next").attr('disabled', true);
		} else if (typeof response.is_more !== "undefined" && response.is_more === true) {
			jQuery("#nifty_desk_modern_pagination_next").removeAttr('disabled');
		} else {
			jQuery("#nifty_desk_modern_pagination_next").removeAttr('disabled');
		}

		if (typeof response.orderby !== "undefined" && typeof response.order !== "undefined") {
			nifty_desk_set_order_by(response.orderby,response.order,sortbyvalue);
		}
		jQuery(".nifty_desk_db_ticket_above_container").html("<h1>Search<span class='nifty_desk_loader_placeholder'></span></h1><p>"+response.cnt+" "+ticket_string_name+"</p>");

		if (typeof response.is_less !== "undefined" && response.is_less !== true) {
			jQuery("#nifty_desk_modern_pagination_previous").attr('disabled', true);
		} else if (typeof response.is_less !== "undefined" && response.is_less === true) {
			jQuery("#nifty_desk_modern_pagination_previous").removeAttr('disabled');
		} else {
			jQuery("#nifty_desk_modern_pagination_previous").removeAttr('disabled');
		}
		
	});
}


function nifty_desk_set_order_by(orderby,order,sortbyvalue) {
	
	jQuery('.nifty_desk_sortby').each(function() {
		jQuery(this).removeClass("nifty_desk_sorted");
		jQuery(this).removeClass("nifty_desk_sorted_ASC");
		jQuery(this).removeClass("nifty_desk_sorted_DESC");
	});




	jQuery("."+orderby).addClass("nifty_desk_sorted");
	jQuery("."+orderby).addClass("nifty_desk_sorted_"+order.toUpperCase());


	if (order == 'ASC' || order == 'asc') {
		
		jQuery("."+orderby).attr("sortby","DESC");
	}
	if (order == 'DESC' || order == 'desc') {
		jQuery("."+orderby).attr("sortby","ASC");
	}

}

function nifty_desk_fetch_tickets_by_view(view,limit,offset,return_counts,sortbytype,sortbyvalue,sortby) {
	jQuery(".nifty_desk_loader_placeholder").html("<img src='"+nifty_desk_db_plugins_url+"/nifty-desk/images/ajax-loader.gif' class='nifty_desk_loader' />");
	//jQuery(".nifty_desk_db_ticket_container tbody").html("<tr><td colspan='11'><img src='"+nifty_desk_db_plugins_url+"/nifty-desk/images/ajax-loader.gif' style='display: block; margin: 0 auto;' class='nifty_desk_loader' /></td></tr>");
	
	// abort any current ajax requests so they dont overlap
	if (typeof current_nd_ajax !== "undefined" && typeof current_nd_ajax.abort !== "undefined") { current_nd_ajax.abort(); }

	url = document.URL ;
	newurl = removeURLParameter(url,"tid");
	window.history.pushState({"html":"","pageTitle":""},"",newurl);


	jQuery(".nifty_desk_modern_ticket_actions").css('display', 'block');
	jQuery(".nifty_desk_db_single_ticket_handle").html("");	
	jQuery(".nifty_desk_db_ticket_container").css("display", "table");
	jQuery("#nifty_desk_modern_pagination_controls").attr('query',"view");
	//jQuery(".nifty_desk_db_ticket_container tbody").html("<tr><td colspan='8'><img src='"+nifty_desk_db_plugins_url+"/nifty-desk/images/ajax-loader.gif' style='display: block; margin: 0 auto;' /></td></tr>");
	
	var main_action = 'nifty_desk_db_request_tickets_from_control_by_view';

	var data = {
		'nifty_desk_db_security': nifty_desk_dashboard_security,
		'action': main_action,
		'offset' : offset,
		'return_counts': return_counts,
		'limit' : limit,
		'view': view,
		'sortbytype' : sortbytype,
		'sortbyvalue' : sortbyvalue,
		'sortby' : sortby
	}

	current_nd_ajax = jQuery.post( ajaxurl, data, function(response){
		response = JSON.parse(response);		
		if (typeof response.counts !== "undefined") {
			/* update ticket counts.. */
			jQuery.each( response.counts, function( index, value ){
				// console.log(index);
				// console.log(value);
				jQuery("#nifty_desk_view_count_"+index).html(value);
			});
		}


		if (typeof response.orderby !== "undefined" && typeof response.order !== "undefined") {
			nifty_desk_set_order_by(response.orderby,response.order,sortbyvalue);
		}

		var view_name = jQuery("#nifty_desk_view_control_ticket_name_"+view).html();
		if (response.cnt === '1') {
			ticket_string_name = nifty_desk_dashboard_strings.tickets_name_singular;
		} else {
			ticket_string_name = nifty_desk_dashboard_strings.tickets_name_plural;
		}
		jQuery(".nifty_desk_db_ticket_above_container").html("<h1>"+view_name+"<span class='nifty_desk_loader_placeholder'></span></h1><p>"+response.cnt+" "+ticket_string_name+"</p>");


		if (typeof response.is_more !== "undefined" && response.is_more !== true) { jQuery("#nifty_desk_modern_pagination_next").attr('disabled', true); }
		else if (typeof response.is_more !== "undefined" && response.is_more === true) { jQuery("#nifty_desk_modern_pagination_next").removeAttr('disabled'); } 
		else { jQuery("#nifty_desk_modern_pagination_next").removeAttr('disabled'); }

		if (typeof response.is_less !== "undefined" && response.is_less !== true) { jQuery("#nifty_desk_modern_pagination_previous").attr('disabled', true); }
		else if (typeof response.is_less !== "undefined" && response.is_less === true) { jQuery("#nifty_desk_modern_pagination_previous").removeAttr('disabled'); }
		else { jQuery("#nifty_desk_modern_pagination_previous").removeAttr('disabled'); }

		jQuery(".nifty_desk_db_ticket_container tbody").html(response.ticket_html);
		jQuery("#nifty_desk_view_count_"+view).html(response.cnt);


		
	});

	
}

jQuery(document).keyup(function(e) { 

	if (e.keyCode == 27){
		jQuery(".nifty_desk_prompt_close").click();
	}

});