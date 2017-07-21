<?php
/* Handles all nifty desk related API init*/

if(class_exists("WP_REST_Request")){
	//The request class was found, move one
	include_once "nifty-desk-api-routes.php";
	include_once "nifty-desk-api-functions.php";
	
}else{
	//No Rest Request class
}


/*
 * Checks if a secret key has been created. 
 * If not create one for use in the API
*/
add_action("nifty_desk_activate_hook", "nifty_desk_api_s_key_check", 10);
add_action("nifty_desk_update_hook", "nifty_desk_api_s_key_check", 10);
function nifty_desk_api_s_key_check(){
	if (!get_option("nifty_desk_api_secret_token")) {
		$user_token = nifty_desk_api_s_key_create();
        add_option("nifty_desk_api_secret_token", $user_token);
    }
}

/*
 * Generates a new Secret Token
*/
function nifty_desk_api_s_key_create(){
	$the_code = rand(0, 1000) . rand(0, 1000) . rand(0, 1000) . rand(0, 1000) . rand(0, 1000);
	$the_time = time();
	$token = md5($the_code . $the_time);
	return $token;
}

/*
 * Creates new settings tab
*/
add_action("nifty_desk_settings_tabs", "nifty_desk_api_settings_tab", 10);
function nifty_desk_api_settings_tab(){
	?>
		 <li><a href="#tabs-api"><?php _e("REST API","nifty_desk") ?></a></li>
	<?php
}

/*
 * Creates new settings content
*/
add_action("nifty_desk_settings_content", "nifty_desk_api_settings_content", 10);
function nifty_desk_api_settings_content(){
	nifty_desk_api_settings_head();
	?>
		<div id="tabs-api">
	<?php

	if(!class_exists("WP_REST_Request")){
		?>
		 	<div class="update-nag">
		 		<?php _e("To make use of the REST API, please ensure you are using a version of WordPress with the REST API included.", "nifty_desk");?>
		 		<br><br>
		 		<?php _e("Alternatively, please install the official Rest API plugin from WordPress.", "nifty_desk");?>
		 	</div>
		<?php
	}

	$secret_token = get_option("nifty_desk_api_secret_token"); //Checks for token
	?>
			<h3><?php _e("REST API", "nifty_desk") ?></h3>
			<table class="wp-list-table widefat fixed striped pages">
				<thead>
					<tr>
						<th><?php _e("Option", "nifty_desk") ?></th>
						<th><?php _e("Value", "nifty_desk") ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<?php _e("Secret Token", "nifty_desk") ?>
						</td>
						<td>
							<input style="max-width:60%; width:100%" type="text" value="<?php echo ($secret_token === false ? __('No secret token found', 'nifty_desk') : $secret_token) ?>" readonly>
							<a class="button-secondary" href="?page=nifty-desk-settings&nifty_desk_action=new_secret_key"><?php _e("Generate New", "nifty_desk") ?></a>
						</td>
					</tr>
					<tr>
						<td>
							<?php _e("Supported API Calls", "nifty_desk") ?>:
						</td>
						<td>
							<code>/wp-json/nifty_desk/v1/create_ticket</code> <code>GET, POST</code> 
							<code><a href="#" class="rest_test_button" niftyRest="/wp-json/nifty_desk/v1/create_ticket" niftyTerms="email,subject,message,name" niftyVals="test@user.com,Rest Test,This is a test for REST with Nifty Desk,Nifty Desk"><?php _e("Try", "nifty_desk") ?></a></code>
						</td>
					</tr>
					<tr>
						<td>
						</td>
						<td>
							<code>/wp-json/nifty_desk/v1/view_ticket</code> <code>GET, POST</code>
							<code><a href="#" class="rest_test_button" niftyRest="/wp-json/nifty_desk/v1/view_ticket" niftyTerms="ticket_id" niftyVals="1"><?php _e("Try", "nifty_desk") ?></a></code>
						</td>
					</tr>
					<tr>
						<td>
						</td>
						<td>
							<code>/wp-json/nifty_desk/v1/delete_ticket</code> <code>GET, POST</code>
							<code><a href="#" class="rest_test_button" niftyRest="/wp-json/nifty_desk/v1/delete_ticket" niftyTerms="ticket_id" niftyVals="1"><?php _e("Try", "nifty_desk") ?></a></code>
						</td>
					</tr>

					<?php do_action("nifty_desk_api_reference_hook"); ?>

					<tr>
						<td>
							<?php _e("API Response Codes", "nifty_desk") ?>:
						</td>
						<td>
							<code>200</code> <code>Success</code>
						</td>
					</tr>
					<tr>
						<td>
						</td>
						<td>
							<code>400</code> <code>Bad Request</code>
						</td>
					</tr>
					<tr>
						<td>
						</td>
						<td>
							<code>401</code> <code>Unauthorized</code>
						</td>
					</tr>
					<tr>
						<td>
						</td>
						<td>
							<code>403</code> <code>Forbidden</code>
						</td>
					</tr>
					<tr>
						<td>
						</td>
						<td>
							<code>404</code> <code>Content Not Found</code>
						</td>
					</tr>

					<?php do_action("nifty_desk_api_response_ref_hook"); ?>
				</tbody>
			</table>
			<br>

			<?php do_action("nifty_desk_api_below_table_hook"); ?>

		</div>
		
	<?php
}

/*
 * Settings head
*/
function nifty_desk_api_settings_head(){
	if(isset($_GET)){
		if(isset($_GET["nifty_desk_action"])){
			if($_GET["nifty_desk_action"] === "new_secret_key"){
				$user_token = nifty_desk_api_s_key_create();
       			update_option("nifty_desk_api_secret_token", $user_token);
			}
		}
	}
}

add_action("nifty_desk_api_below_table_hook", "nifty_desk_api_test_component", 10);
function nifty_desk_api_test_component(){
	$site_url = home_url();
	$secret_token = get_option("nifty_desk_api_secret_token");

	?>
		<script>
			jQuery(function(){
				jQuery(document).ready(function(){
					jQuery("body").on("click", ".rest_test_button", function (){
						var route = jQuery(this).attr('niftyRest');
						var terms = jQuery(this).attr('niftyTerms').split(",");
						var vals = jQuery(this).attr('niftyVals').split(",");
						nifty_deskrest_console_setup(route,terms,vals);
						nifty_deskrest_console_show();
					});

					jQuery("body").on("click", "#nifty_deskrest_console_button", function (){
						nifty_deskrest_ajax();
					});

					function nifty_deskrest_console_setup(route,terms,values){
						var url = "<?php echo $site_url; ?>";

						url += route + "?token=" + "<?php echo $secret_token; ?>";

						for(var i = 0; i < terms.length; i++){
							url += "&" + terms[i] + "=" + values[ (i < values.length ? i : values.length-1) ]
						}

						jQuery("#nifty_deskrest_console_input").val(encodeURI(url));
					}

					function nifty_deskrest_console_show(){
						jQuery(".nifty_deskrest_consol").fadeIn();
					}

					function nifty_deskrest_ajax(){
						var url = jQuery("#nifty_deskrest_console_input").val();

						jQuery.get(url, function(response){
							console.log(response);

							var returned_data = niftyDeskParseResponse(response);

							jQuery("#nifty_deskrest_console_response").text("Success:\n--------\n" + returned_data);
						}).fail(function(e){
							//console.log("somin wrong ");
							var errors = "";

							errors = niftyDeskParseResponse(e.responseText);

							jQuery("#nifty_deskrest_console_response").text("Error:\n--------\n" + errors);
						});
					}

					function niftyDeskParseResponse(content){
						try{
							if(typeof content !== "object"){
						    	content = JSON.parse(content);
						    }
						}catch(e){
						    content = e.toString();
						}
						if (typeof e === "undefined") {
							var new_content ="";
							jQuery.each(content, function(i, val) {
								if(typeof val === "object"){
									new_content += niftyDeskParseResponse(val);
								}else{
							  		new_content += "\n"+ i + ": "+ val;										
								}
							});
							content = new_content;
						}
						return content;
					}
				});
			});
			
		</script>
		<table class="wp-list-table widefat fixed striped pages nifty_deskrest_consol" style="display:none">
			<thead>
				<tr>
					<th><?php _e("Rest Console ", "nifty_desk") ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<input type="text" value="<?php echo $site_url ?>" style="max-width: 600px; width:80%" id="nifty_deskrest_console_input">
						<a href="javascript:void(0)"  class="button" style="max-width:120px" id="nifty_deskrest_console_button"><?php _e("Try it!", "nifty_desk"); ?></a>
					</td>
				</tr>
				<tr>
					<td>
						<textarea style="max-width: 600px; width:80%; min-height:250px" id="nifty_deskrest_console_response">

						</textarea>
					</td>
				</tr>
			</tbody>
		</table>
	<?php
}
