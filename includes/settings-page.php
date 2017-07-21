<?php
$nifty_desk_settings = get_option("nifty_desk_settings");

$st_notification = get_option("nifty_desk_notifications");
$nifty_desk_ajax_nonce = wp_create_nonce("nifty_desk");




if(function_exists('nifty_desk_pro_activate')){
    global $nifty_desk_pro_version;
    if($nifty_desk_pro_version < '1.0'){
        $need_update = true;
    } else {
        $need_update = false;
    }
} else {
    $need_update = false;
}

if (function_exists("nifty_desk_api_check")) {
    nifty_desk_api_check();
}

?>

<script language="javascript">
    var nifty_desk_nonce = '<?php echo $nifty_desk_ajax_nonce; ?>';
</script>
<style>
    label { font-weight: bolder; }
</style>

<div class="wrap">
    <div id="icon-options-general" class="icon32 icon32-posts-post"><br></div><h2><?php _e("Nifty Desk Settings","nifty_desk") ?></h2>
    <?php if($need_update){ ?>
        <div id="" class="error">
            <p><?php _e('You are using an outdated version of Nifty Desk Pro. Please update the plugin to take advantage of new features.', 'nifty_desk'); ?></p>
        </div>
    <?php } ?>

    <form action='' name='nifty_desk_settings' method='POST' id='nifty_desk_settings'>



    <div id="nifty_desk_tabs">
      <ul>
          <li><a href="#tabs-1"><?php _e("Main Settings","nifty_desk") ?></a></li>
          <li><a href="#tabs-2"><?php _e("Email","nifty_desk") ?></a></li>
          <li><a href="#tabs-3"><?php _e("Agents","nifty_desk") ?></a></li>
          <li><a href="#tabs-6"><?php _e("Views","nifty_desk") ?></a></li>
          <li><a href="#tabs-channels"><?php _e("Channels","nifty_desk") ?></a></li>
          <li><a href="#tabs-schedules"><?php _e("Schedules","nifty_desk") ?></a></li>

          <?php do_action("nifty_desk_settings_tabs"); ?>

          <?php if (!function_exists("nifty_desk_pro_activate")) { ?>
            <li><a href="#tabs-4"><?php _e("Upgrade","nifty_desk") ?></a></li>
          <?php } ?>
          
          <?php
          	if(defined('nifty_desk_CSS_CUSTOMER_SATISFACTION_SURVEY_ACTIVE')&&function_exists('nifty_desk_pro_activate'))
			{
				$out='<li>
					<a href="#tabs-5"> 
						'.__("Customer Satisfaction Survey","nifty_desk").'
					</a>
				</li>';
				
				echo $out;
				
			}
          ?>
          
          
          
          
      </ul>
      <div id="tabs-1">


        <h3><?php _e("Notification Settings","nifty_desk"); ?></h3>
        <table width='100%' class="wp-list-table widefat striped ">
            <tr>
                <td width="350">
                    <label><?php _e("Notifications","nifty_desk"); ?> <div class='nifty_desk_question_icon' title="<?php _e('You will recieve email notifications') ?>">?</div></label>
                </td>
                <td>
                    <input type="checkbox" class='nifty-desk-input' name="nifty_desk_settings_notify_new_tickets" value="1" <?php if (isset($nifty_desk_settings['nifty_desk_settings_notify_new_tickets']) && $nifty_desk_settings['nifty_desk_settings_notify_new_tickets'] == "1") echo 'checked="checked"'; ?> /><?php _e("Send a notification when a new support ticket is received","nifty_desk"); ?><br />
                    <input type="checkbox" class='nifty-desk-input' name="nifty_desk_settings_notify_new_responses" value="1" <?php if (isset($nifty_desk_settings['nifty_desk_settings_notify_new_responses']) && $nifty_desk_settings['nifty_desk_settings_notify_new_responses'] == "1") echo 'checked="checked"'; ?> /><?php _e("Send a notification when a new response is received","nifty_desk"); ?><br />
                    <input type="checkbox" class='nifty-desk-input' name="nifty_desk_settings_notify_status_change" value="1" <?php if (isset($nifty_desk_settings['nifty_desk_settings_notify_status_change']) && $nifty_desk_settings['nifty_desk_settings_notify_status_change'] == "1") echo 'checked="checked"'; ?> /><?php _e("Send a notification to the user whenever the status of a support ticket changes","nifty_desk"); ?><br />
                    <?php if (function_exists("nifty_desk_pro_activate")) { ?>
                        <input type="checkbox" class='nifty-desk-input' name="nifty_desk_settings_notify_agent_change" value="1" <?php if (isset($nifty_desk_settings['nifty_desk_settings_notify_agent_change']) && $nifty_desk_settings['nifty_desk_settings_notify_agent_change'] == "1") echo 'checked="checked"'; ?> /><?php _e("Send a notification to the agent when a ticket is assigned to them","nifty_desk"); ?><br />
                    <?php } else {
                        $pro_link = '<a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=notify_agent_change">'.__('Premium Version', 'nifty_desk').'</a>';
                    ?>
                        <input type="checkbox" class='nifty-desk-input' value="1" disabled readonly="readonly" /><?php _e("Send a notification to the agent when a ticket is assigned to them. Upgrade to the $pro_link to take advantage of this.","nifty_desk"); ?><br />
                    <?php } ?>
                    <?php if (function_exists("nifty_desk_pro_activate")) { ?>
                        <input type="checkbox" class='nifty-desk-input' name="nifty_desk_settings_notify_all_agents" value="1" <?php if (isset($nifty_desk_settings['nifty_desk_settings_notify_all_agents']) && $nifty_desk_settings['nifty_desk_settings_notify_all_agents'] == "1") echo 'checked="checked"'; ?> /><?php _e("Send a notification to all agents when a new ticket is received.","nifty_desk"); ?><br />
                    <?php } else {
                        $pro_link = '<a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=notify_all_agents">'.__('Premium Version', 'nifty_desk').'</a>';
                    ?>
                        <input type="checkbox" class='nifty-desk-input' value="1" disabled readonly="readonly" /><?php _e("Send a notification to all agents when a new ticket is received. Upgrade to the $pro_link to take advantage of this.","nifty_desk"); ?><br />
                  <?php } ?>

               </td>
            </tr>
            <tr>
                <td width="250">
                    <label><?php _e("Thank you text","nifty_desk"); ?></label>
                    <p class="description"><?php _e("This is sent when someone posts a new support ticket","nifty_desk"); ?></p>
                </td>
               <td>
                  <textarea cols="80" rows="6" name="nifty_desk_settings_thank_you_text"><?php if (isset($nifty_desk_settings['nifty_desk_settings_thank_you_text'])) { echo $nifty_desk_settings['nifty_desk_settings_thank_you_text']; } ?></textarea>
               </td>
            </tr>
            <tr>
                <td width="250">
                    <label><?php _e("Default Ticket Status","nifty_desk"); ?></label>
                </td>
               <td>
                   <?php if(function_exists('nifty_desk_pro_activate')){ ?>
                    <select name="nifty_desk_settings_default_status" id="nifty_desk_settings_default_status">
                        <option value="0" <?php if(isset($nifty_desk_settings['nifty_desk_settings_default_status']) && $nifty_desk_settings['nifty_desk_settings_default_status'] == '0'){ echo 'selected'; }?>><?php _e("New","nifty_desk"); ?></option>
                        <option value="1" <?php if(isset($nifty_desk_settings['nifty_desk_settings_default_status']) && $nifty_desk_settings['nifty_desk_settings_default_status'] == '1'){ echo 'selected'; }?>><?php _e("Open","nifty_desk"); ?></option>
                    </select>
                   <?php } else { ?>
                        <select name="nifty_desk_settings_default_status" id="nifty_desk_settings_default_status" disabled>
                            <option value="0" ><?php _e("New","nifty_desk"); ?></option>
                        </select>
                        <?php
                            $pro_link = '<a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=default_status" target="_BLANK">'.__('Premium Version', 'nifty_desk').'</a>';
                            _e("Only available in the $pro_link", "nifty_desk");
                   }
                   ?>
               </td>
            </tr>
            <tr>
                <td width="250">
                    <label><?php _e("Priorities","nifty_desk"); ?></label>
                </td>
               <td>
                  <?php _e("Default ticket priority:","nifty_desk"); ?>
                  <select name="nifty_desk_settings_default_priority" id="nifty_desk_settings_default_priority">
                      <option value="0" <?php if (isset($nifty_desk_settings['nifty_desk_settings_default_priority']) && $nifty_desk_settings['nifty_desk_settings_default_priority'] == 0) { echo "selected='selected'"; } ?>><?php _e("Low","nifty_desk"); ?></option>
                      <option value="1" <?php if (isset($nifty_desk_settings['nifty_desk_settings_default_priority']) && $nifty_desk_settings['nifty_desk_settings_default_priority'] == 1) { echo "selected='selected'"; } ?>><?php _e("High","nifty_desk"); ?></option>
                      <option value="2" <?php if (isset($nifty_desk_settings['nifty_desk_settings_default_priority']) && $nifty_desk_settings['nifty_desk_settings_default_priority'] == 2) { echo "selected='selected'"; } ?>><?php _e("Urgent","nifty_desk"); ?></option>
                      <option value="3" <?php if (isset($nifty_desk_settings['nifty_desk_settings_default_priority']) && $nifty_desk_settings['nifty_desk_settings_default_priority'] == 3) { echo "selected='selected'"; } ?>><?php _e("Critical","nifty_desk"); ?></option>
                  </select>
               </td>
            </tr>
            <tr>
                <td width="250">

                </td>
               <td>
                  <input type="checkbox" class='nifty-desk-input' name="nifty_desk_settings_allow_priority" value="1" <?php if (isset($nifty_desk_settings['nifty_desk_settings_allow_priority']) && $nifty_desk_settings['nifty_desk_settings_allow_priority'] == "1") echo 'checked="checked"'; ?> /><?php _e("Allow users to select a priority when submitting a ticket","nifty_desk"); ?><br />
               </td>
            </tr>
            <tr>
                <td width="250">
                        <label><?php _e("Departments ","nifty_desk"); ?></label>
                </td>
                <td>
                    <?php if(function_exists('nifty_desk_pro_activate')){ ?>
                        <input type="checkbox" class='nifty-desk-input' name="nifty_desk_settings_allow_department" id="nifty_desk_settings_allow_department" value="1" <?php if(isset($nifty_desk_settings['nifty_desk_settings_allow_department']) && $nifty_desk_settings['nifty_desk_settings_allow_department'] == 1) { echo 'checked'; } ?>/><?php _e("Allow users to select a department when submitting a ticket","nifty_desk"); ?><br />
                    <?php
                    } else {
                        $pro_link = '<a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=st_select_departments" target="_BLANK">'.__('Premium Version', 'nifty_desk').'</a>';
                    ?>
                        <input type="checkbox" value="1" disabled="disabled" /><?php _e("Allow users to select a department when submitting a ticket. $pro_link Only","nifty_desk"); ?><br />
                    <?php
                    }
                    ?>

                </td>
            </tr>
            <tr id="nifty_desk_departments_row">
                <td width="250">
                </td>
                <td>
                    <?php
                    if(function_exists('nifty_desk_pro_activate')){
                        if(function_exists('nifty_desk_get_all_departments')){
                            echo nifty_desk_get_all_departments();
                            _e(" Select a default ","nifty_desk");
                            ?>
                              <a href="edit-tags.php?taxonomy=nifty_desk_departments&post_type=nifty_desk_tickets"><?php _e("department", "nifty_desk"); ?></a>
                            <?php
                            _e(" your support tickets will be added to.","nifty_desk");
                        }
                    } else {
                        $pro_link = '<a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=default_departments" target="_BLANK">'.__('Premium Version', 'nifty_desk').'</a>';
                        echo '<select disabled><option>'.__('None', 'nifty_desk').'</option></select>';
                        _e(" Select a default department your support tickets will be added to. Only available in the $pro_link","nifty_desk");
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td width="250">
                     <label><?php _e("Require Login?","nifty_desk"); ?></label>
                </td>
                <td>
                    <?php
                    if(function_exists('nifty_desk_pro_activate')){
                    ?>
                        <input type="checkbox" class='nifty-desk-input' name="nifty_desk_settings_require_login" value="1" <?php if (isset($nifty_desk_settings['nifty_desk_settings_require_login']) && $nifty_desk_settings['nifty_desk_settings_require_login'] == "1") { echo 'checked="checked"'; } ?> /><?php _e("Require users to login when submitting a support ticket?","nifty_desk"); ?><br />
                    <?php } else {
                        $pro_link = '<a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=allow_guest_tickets" target="_BLANK">'.__('Premium Version', 'nifty_desk').'</a>';
                    ?>
                        <input type="checkbox" class='nifty-desk-input' disabled="disabled" checked><?php _e("Require users to login when submitting a support ticket? Only available in the $pro_link","nifty_desk"); ?><br />
                    <?php } ?>

                </td>
            </tr>
            <?php
            if(function_exists('nifty_desk_pro_activate')){
            ?>
            <tr>
                <td width="250">
                     <label><?php _e("Send log in details via email","nifty_desk"); ?></label>
                </td>
                <td>
                    <input type="checkbox" class='nifty-desk-input' name="nifty_desk_settings_email_account_info" value="1" <?php if (isset($nifty_desk_settings['nifty_desk_settings_email_account_info']) && $nifty_desk_settings['nifty_desk_settings_email_account_info'] == "1") { echo 'checked="checked"'; } ?> /><?php _e("When creating a ticket, if the user doesnt exist an account will be created for them. This option will enable or disable the email that gets sent to them upon account creation.","nifty_desk"); ?><br />

                </td>
            </tr>
            <?php } ?>
            <tr>
                <td width="250">
                     <label><?php _e("Enable CAPTCHA?","nifty_desk"); ?></label>
                </td>
                <td>
                    <?php
                    if(function_exists('nifty_desk_pro_activate')){
                        /* Allow them to enable the captcha */
                        if(class_exists('ReallySimpleCaptcha'))
                        {
                            ?><input type="checkbox" class='nifty-desk-input' name="nifty_desk_settings_enable_captcha" value="1" <?php if (isset($nifty_desk_settings['nifty_desk_settings_enable_captcha']) && $nifty_desk_settings['nifty_desk_settings_enable_captcha'] == "1") { echo 'checked="checked"'; } ?> /><?php _e("Enable CAPTCHA verification for users who are not logged in when submitting a ticket?","nifty_desk"); ?><br /><?php
                        }
                        else
                        {
                            $captcha_link = '<a href="https://wordpress.org/plugins/really-simple-captcha/" target="_BLANK">'.__('Really Simple CAPTCHA', 'nifty_desk').'</a>';
                            ?><input type="checkbox" class='nifty-desk-input' disabled value="1" /><span style="color: red;"><?php _e("$captcha_link is required to be installed and activated on your website to enable CAPTCHA verification","nifty_desk"); ?></span><br /><?php
                        }
                    }
                    else
                    {
                        /* Disabled the checkbox */
                        $pro_link = '<a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=enable_captcha" target="_BLANK">'.__('Premium Version', 'nifty_desk').'</a>';
                        ?><input type="checkbox" class='nifty-desk-input' disabled="disabled"><?php _e("Enable CAPTCHA verification for users who are not logged in when submitting a ticket? Only available in the $pro_link","nifty_desk"); ?><br /><?php
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td style="width:250px;">
                    <label for="cb_settings_enable_file_uploads"><?php _e("Enable file uploads","nifty_desk"); ?></label>
                </td>
                <td>
                    <?php

                        $prem_active=false; /*no check status and override the var value if necessarry*/

                        if(function_exists('nifty_desk_pro_activate'))
                        {
                            $prem_active=true;
                        }



                        $checkbox_normal='<input type="checkbox" name="cb_settings_enable_file_uploads" id="cb_settings_enable_file_uploads" value="1" disabled="disabled"/>
                        '.__('Give a user the ability to upload a file when creating a support ticket or adding a response on the admin ticket editor page. Only available in the','nifty_desk').' <a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=enable_file_uploads" target="_blank">'.__('Premium Version','nifty_desk').'</a>';

                        if(isset($nifty_desk_settings['enable_file_uploads'])&&$nifty_desk_settings['enable_file_uploads']===1)
                        {
                            $checkbox_premium='<input type="checkbox" name="cb_settings_enable_file_uploads" id="cb_settings_enable_file_uploads" value="1" checked="checked"/>
                            '.__('Give a user the ability to upload a file when creating a support ticket or adding a response on the admin ticket editor page.','nifty_desk');
                        }
                        else
                        {
                            $checkbox_premium='<input type="checkbox" name="cb_settings_enable_file_uploads" id="cb_settings_enable_file_uploads" value="1"/>
                            '.__('Give a user the ability to upload a file when creating a support ticket or adding a response on the admin ticket editor page.','nifty_desk');

                        }



                        ($prem_active===true) ? $html_to_enter = $checkbox_premium : $html_to_enter=$checkbox_normal;

                        echo $html_to_enter;

                    ?>





                </td>
            </tr>

            <tr>
                <td>
                    <label for="automatic_ticket_closure">
                        <?php _e("Close tickets automatically","nifty_desk"); ?>:
                    </label>
                </td>
                <td>

                    <?php
                        $prem_active_check=false;

                        if(function_exists('nifty_desk_pro_activate'))
                        {
                            $prem_active_check=true;
                        }


                        $normal_auto_close='<input type="checkbox" name="automatic_ticket_closure" id="automatic_ticket_closure" value="1" disabled="disabled"/>'
                                . __('Enable automatic closing of tickets after a set amount of days. Only available in the','nifty_desk').' <a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=auto_close_tickets" target="_blank">'.__('Premium Version','nifty_desk').'</a>';


                        if(isset($nifty_desk_settings['automatic_ticket_closure'])&&$nifty_desk_settings['automatic_ticket_closure']===1)
                        {
                            $pro_auto_close='<input type="checkbox" name="automatic_ticket_closure" id="automatic_ticket_closure" value="1" checked="checked"/>';
                            $style_settings_display='display:inline-block;';
                            $style_display_instructions='display:none;';

                        }
                        else
                        {
                            $pro_auto_close='<input type="checkbox" name="automatic_ticket_closure" id="automatic_ticket_closure" value="1"/>';
                            $style_settings_display='display:none;';
                            $style_display_instructions='display:inline-block;';

                        }


                        $pro_auto_close.='<span id="display_hide_auto_ticket_closure_settings" style="'.$style_settings_display.'">'.__("Automatically close tickets after","nifty_desk").'';


                        if(isset($nifty_desk_settings['interval_in_days_autoclose_tickets']))
                        {
                        	$stored_interval_auto_close=(integer)$nifty_desk_settings['interval_in_days_autoclose_tickets'];
                        	$pro_auto_close.='<select name="sb_amount_of_days_till_auto_close" id="sb_amount_of_days_till_auto_close">';

                        	$pro_auto_close.='<option value=""> - '.__("Please select","nifty_desk").' - </option>';


                        	for($x=1;$x<=120;$x++)
                        	{
                        		if($x===$stored_interval_auto_close)
                        		{
                        			$pro_auto_close.='<option value="'.$x.'" selected="selected">'.$x.'</option>';
                        		}
                        		else
                        		{
                        			$pro_auto_close.='<option value="'.$x.'">'.$x.'</option>';
                        		}
                        	}

                        	$pro_auto_close.='</select>';
                        }
                        else
                        {
                        	$pro_auto_close.='<select name="sb_amount_of_days_till_auto_close" id="sb_amount_of_days_till_auto_close">';

                        	$pro_auto_close.='<option value="" selected="selected"> - '.__("Please select","nifty_desk").' - </option>';


                        	for($x=1;$x<=120;$x++)
                        	{
                        		$pro_auto_close.='<option value="'.$x.'">'.$x.'</option>';
                        	}

                        	$pro_auto_close.='</select>';

                        }







                        $pro_auto_close.=__('day(s)','nifty_desk').'</span>

            			<span id="display_no_setting_for_autoclose" style="'.$style_display_instructions.'">
            				'.__("Please tick the checkbox for settings.","nifty_desk").'
            			</span>
            			';



                        ($prem_active===true)? $html=$pro_auto_close : $html=$normal_auto_close;

                        echo $html;









                    ?>


                </td>
            </tr>
            <?php
                $premium_user=false;
                $include_exclude_bootstrap_checkbox_normal='';
                $include_exclude_bootstrap_checkbox_premium='';
                if(function_exists('nifty_desk_pro_activate'))
                {
                        $premium_user=true;
                }
                
                if($premium_user===false)
                {
                        $include_exclude_bootstrap_checkbox_normal='';
                } else {
            ?>
            <tr>
            	<td>
            		<label for="cb_boostrap_disable">
            			<?php _e("Exclude Bootstrap","nifty_desk"); ?>
            		</label>

            	</td>
            	<td>

                    <?php

                    if(isset($nifty_desk_settings['disable_bootstrap'])&&$nifty_desk_settings['disable_bootstrap']===true)
                    {
                            $include_exclude_bootstrap_checkbox_premium='<input type="checkbox" name="cb_boostrap_disable" id="cb_boostrap_disable" value="" checked="checked"/> '.__("Exclude Bootstrap library (select this if you are experiencing issues with your layout and theme in the front end)","nifty_desk");
                    }
                    else
                    {
                            $include_exclude_bootstrap_checkbox_premium='<input type="checkbox" name="cb_boostrap_disable" id="cb_boostrap_disable" value=""/> '.__("Exclude Bootstrap library (select this if you are experiencing issues with your layout and theme in the front end)","nifty_desk");
                    }
                    ($premium_user===false)? $html=$include_exclude_bootstrap_checkbox_normal : $html=$include_exclude_bootstrap_checkbox_premium;

                    echo $html;
?>
            	</td>
            </tr>
            <?php } ?>
            <?php
            
           

                $premium_user=false;

                $exclude_font_awesome_normal_checkbox="";
                $exclude_font_awesome_premium_checkbox="";

                if(function_exists('nifty_desk_pro_activate'))
                {
                        $premium_user=true;
                }




                if($premium_user===false)
                {
                        $exclude_font_awesome_normal_checkbox='';
                }
                else
                { ?>
            <tr>
            	<td>
            		<label for="cb_font_awesome_disable">
            			<?php _e("Exclude Font Awesome","nifty_desk"); ?>
            		</label>
            	</td>
            	<td>
            		<?php
            			if(isset($nifty_desk_settings['disable_font_awesome'])&&$nifty_desk_settings['disable_font_awesome']===true)
            			{
            				$exclude_font_awesome_premium_checkbox='<input type="checkbox" name="cb_font_awesome_disable" id="cb_font_awesome_disable" value="" checked="checked"/>
            				'.__('Exclude Font Awesome','nifty_desk');
            			}
            			else
            			{
            				$exclude_font_awesome_premium_checkbox='<input type="checkbox" name="cb_font_awesome_disable" id="cb_font_awesome_disable" value=""/>
            				'.__('Exclude Font Awesome','nifty_desk');
            			}
            		



            		($premium_user===true)?$html=$exclude_font_awesome_premium_checkbox:$html=$exclude_font_awesome_normal_checkbox;


            		echo $html;







            		?>

            	</td>
            </tr>
                <?php } ?>
            <tr>
                <td width="250">
                    <label><?php _e("General Settings","nifty_desk"); ?></label>
                </td>
               <td>
                  <input type="checkbox" class='nifty-desk-input' name="nifty_desk_settings_allow_html" value="1" <?php if (isset($nifty_desk_settings['nifty_desk_settings_allow_html']) && $nifty_desk_settings['nifty_desk_settings_allow_html'] == "1") { echo 'checked="checked"'; } ?> /><?php _e("Allow users to post HTML in support tickets and responses?","nifty_desk"); ?><br />
               </td>
            </tr>

            <tr>
                <td width="250">
  
                </td>
               <td>
                  <input type="checkbox" class='nifty-desk-input' name="nifty_desk_settings_dashboard_folded" value="1" <?php if (isset($nifty_desk_settings['nifty_desk_settings_dashboard_folded']) && $nifty_desk_settings['nifty_desk_settings_dashboard_folded'] == "1") { echo 'checked="checked"'; } ?> /><?php _e("Collapse WordPress Admin menu when using the Dashboard","nifty_desk"); ?><br />
               </td>
            </tr>

           <!-- <tr>
                <td width="250">
                    <label><?php _e("Display Tickets In Legacy View","nifty_desk"); ?></label>
                </td>
               <td>
                  <input type="checkbox" class='nifty-desk-input' name="nifty_desk_display_legacy_tickets" value="1" <?php if (isset($nifty_desk_settings['nifty_desk_display_legacy_tickets']) && $nifty_desk_settings['nifty_desk_display_legacy_tickets'] == "1") { echo 'checked="checked"'; } ?> /><?php _e("Change back from the modern support ticket view to the legacy view (Removes Ajax ticket loading)","nifty_desk"); ?><br />
               </td>
            </tr> -->
          </table>
        <p>&nbsp;</p>
        <p><?php echo __("Need more options?","nifty_desk"). " <a href='./admin.php?page=nifty-desk-menu-feedback-page'>".__("Let us know!","nifty_desk")."</a> ".__("and we'll add it in!","nifty_desk"); ?></p>



      </div>
      <div id="tabs-2">
        <h3><?php _e("Email Settings",'nifty_desk'); ?></h3>
        <?php
            if (function_exists("nifty_desk_pro_activate")) {
                
                
                    /*pro function - backwards compatibility*/
                    /* The person could have updated basic but not pro */
                    if(function_exists('nifty_desk_pro_set_from_email'))
                    {
                        nifty_desk_pro_set_from_email();
                   
                    }

					
					
					
					
					
                    
                nifty_desk_pro_settings('email_settings');
				
				
				
				
            } else {
					
					
            	
				
				
				
        ?>
          <p><?php echo __("Upgrade to the","nifty_desk")." <a href='http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=email' title='Premium Version' target='_BLANK'>".__("Premium version","nifty_desk")."</a> ". __("of Nifty Desk and automatically convert received emails to support tickets and responses","nifty_desk"); ?></p>
          
          <?php } ?>
          
          
          <?php
          	
          	
          	if(function_exists('nifty_desk_select_mailing_system_to_use'))
			{
				nifty_desk_select_mailing_system_to_use();
			}
          
          
          
          ?>
          
          
          
          
          
          
          
          
      </div>
      <div id="tabs-3">
          <h3><?php _e("Agents",'nifty_desk'); ?></h3>
          <?php if (function_exists("nifty_desk_pro_activate")) { ?>
                <?php nifty_desk_pro_settings('agents'); ?>
          <?php } else { ?>
          <p><?php _e('Nifty Desk Basic allows for 1 Agent, which is set to be the super-admin of the site.'); ?> </p>
          <p><?php  echo "<p><span class='update-nag'>".sprintf(__("Add as many agents as you need with the <a href='%s' target='_BLANK'>Pro version</a> of Nifty Desk. Pay once off and receive updates forever.","nifty_desk"),'http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=agents')."</span></p>"; ?></p> 
          <?php } ?>
      </div>
      <div id="tabs-channels">
          <h3><?php _e("Channels",'nifty_desk'); ?> <?php echo apply_filters("nifty_desk_filter_view_new_channel_button_control",""); ?></h3>
          <?php nifty_desk_channel_html_output(); ?>
      </div>
      <div id="tabs-schedules">
          <h3><?php _e("Schedules",'nifty_desk'); ?> <?php echo apply_filters("nifty_desk_filter_view_new_autoassign_button_control",""); ?></h3>
          <?php nifty_desk_autoassign_html_output(); ?>
      </div>
      <div id="tabs-6">
          <h3><?php _e("Views",'nifty_desk'); ?> <?php echo apply_filters("nifty_desk_filter_view_new_view_button_control",""); ?></h3>
          <?php nifty_desk_views_html_output(); ?>
      </div>
      
      <?php do_action("nifty_desk_settings_content"); ?>

      <?php if (!function_exists("nifty_desk_pro_activate")) { ?>
      <div id="tabs-4">
          <div style="display:block; clear:both; width:100%; overflow:auto;">


            <h1 style="font-weight:200;">12 Amazing Reasons to Upgrade to our Pro Version</h1>
            <p style="font-size:16px; line-height:28px;">We've spent over a year upgrading our plugin to ensure that it is the most user-friendly and comprehensive support desk plugin in the WordPress directory. Enjoy the peace of mind knowing that you are getting a truly premium product for all your support requirements. Did we also mention that we have fantastic support?</p>
            <div id="nifty_premium">
                <div class="nifty_premium_row">
                    <div class="nifty_icon"></div>
                    <div class="nifty_details">
                        <h2>Unlimited Agents</h2>
                        <p>Add as many agents as you need!</p>
                    </div>
                </div>
                <div class="nifty_premium_row">
                    <div class="nifty_icon"></div>
                    <div class="nifty_details">
                        <h2>Fetch emails and convert them into tickets</h2>
                        <p>Add as many email channels as you need. Collect emails from multiple email addresses and convert them into tickets</p>
                    </div>
                </div>
                <div class="nifty_premium_row">
                    <div class="nifty_icon"></div>
                    <div class="nifty_details">
                        <h2>Allow customers to reply via email</h2>
                        <p>Email replies will be added as responses for that ticket</p>
                    </div>
                </div>
                <div class="nifty_premium_row">
                    <div class="nifty_icon"></div>
                    <div class="nifty_details">
                        <h2>Front end support desk</h2>
                        <p>Add a fully customizable (via templates) Support Desk to your front end</p>
                    </div>
                </div>                                
                <div class="nifty_premium_row">
                    <div class="nifty_icon"></div>
                    <div class="nifty_details">
                        <h2>Unlimited Views</h2>
                        <p>Create and manage as many views as you need</p>
                    </div>
                </div>                                
                <div class="nifty_premium_row">
                    <div class="nifty_icon"></div>
                    <div class="nifty_details">
                        <h2>Ticket Scheduling</h2>
                        <p>Assign tickets to certain agents at certain times</p>
                    </div>
                </div>  
                <div class="nifty_premium_row">
                    <div class="nifty_icon"></div>
                    <div class="nifty_details">
                        <h2>File sharing</h2>
                        <p>Share files with your customers and let them share files with you</p>
                    </div>
                </div>                  
                <div class="nifty_premium_row">
                    <div class="nifty_icon"></div>
                    <div class="nifty_details">
                        <h2>Advanced Reporting</h2>
                        <p>Pull detailed reports and understand how you can improve customer satisfaction</p>
                    </div>
                </div>
                <div class="nifty_premium_row">
                    <div class="nifty_icon"></div>
                    <div class="nifty_details">
                        <h2>Extended REST API</h2>
                        <p>More end points for the Nifty Desk REST API</p>
                    </div>
                </div>                                   
                <div class="nifty_premium_row">
                    <div class="nifty_icon"></div>
                    <div class="nifty_details">
                        <h2>Amazing Support</h2>
                        <p>We pride ourselves on providing quick and amazing support</p>
                    </div>
                </div>
                <div class="nifty_premium_row">
                    <div class="nifty_icon"></div>
                    <div class="nifty_details">
                        <h2>Easy Upgrade</h2>
                        <p>You'll receive a download link immediately. Simply upload and activate the Pro plugin to your WordPress admin area and you're done!</p>
                    </div>
                </div>                                  
                <div class="nifty_premium_row">
                    <div class="nifty_icon"></div>
                    <div class="nifty_details">
                        <h2>Free updates and support forever</h2>
                        <p>Once you're a pro user, you'll receive free updates and support forever! You'll also receive amazing specials on any future plugins we release.</p>
                    </div>
                </div>              
                
                <br /><p>Get all of this and more for only $29.99 once off</p>                                
                <br /><a href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=upgradenow" target="_BLANK" title="Upgrade now for only $29.99 once off" class="button-primary" style="font-size:20px; display:block; width:220px; text-align:center; height:42px; line-height:41px;">Upgrade Now</a>
                <br /><br />
                Have a sales question? Contact Nick on <a href="mailto:nick@niftydesk.org">nick@niftydesk.org</a> or use our <a href="http://niftydesk.org/contact-us/" target="_BLANK">contact form</a>. <br /><br />
                Need help? <a href="http://niftydesk.org/support-desk/" target="_BLANK">Ask a question on our support desk</a>.       

          </div>
        </div>

      <?php } ?>
      
      
      
      
      </div>
      
      
      
      
      
      
      
     
      
      
    </div>  
    <?php wp_nonce_field( 'nifty_desk_save_admin_settings_basic','nifty_desk_security' ); ?>
        <p class='submit' style="margin-left:15px;"><input type='submit' name='nifty_desk_save_settings' class='button-primary' value='<?php _e("Save Settings","nifty_desk") ?>' /></p>

    </form>

    <p style="margin-left:15px;"><?php echo __("Need help?","nifty_desk"). " <a href='http://niftydesk.org/support-desk/?utm_source=plugin&utm_medium=link&utm_campaign=documentation' target='_BLANK'>".__("Read the documentation","nifty_desk")."</a>"; ?></p>

<?php include 'footer.php'; ?>