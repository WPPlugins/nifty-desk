<?php
global $current_user;
//get_currentuserinfo();
$current_user = wp_get_current_user();
?><div class="wrap">
   
    
    <div id="icon-options-general" class="icon32 icon32-posts-post"><br></div><h2><?php _e("Nifty Desk Feedback","nifty_desk") ?></h2>
    <h3><?php _e("We'd love to hear your comments and/or suggestions","nifty_desk"); ?></h3>
    <form name="nifty_desk_feedback" action="" method="POST">
     <table width='100%'>
        <tr>
            <td width="250px" >
                <label><?php _e("Your Name","nifty_desk"); ?></label>
            </td>
            <td>
                <input type="text" class='nifty-desk-input' name="nifty_desk_feedback_name" value="<?php echo $current_user->user_firstname; ?>"/>
           </td>
        </tr>
        <tr>
            <td width="250px" >
                <label><?php _e("Your Email","nifty_desk"); ?></label>
            </td>
            <td>
                <input type="text" class='nifty-desk-input' name="nifty_desk_feedback_email" value="<?php echo $current_user->user_email; ?>"/>
           </td>
        </tr>
        <tr>
            <td width="250px" >
                <label><?php _e("Your Website","nifty_desk"); ?></label>
            </td>
            <td>
                <input type="text" class='nifty-desk-input' name="nifty_desk_feedback_website" value="<?php echo get_site_url(); ?>"/>
           </td>
        </tr>
        <tr>
            <td width="250px" valign='top' >
                <label><?php _e("Feedback","nifty_desk"); ?></label>
            </td>
            <td>
                <textarea name="nifty_desk_feedback_feedback" cols='60' rows='10'></textarea>
           </td>
        </tr>
        <tr>
            <td width="250px" valign='top' >
                
            </td>
            <td>
                <?php wp_nonce_field( 'nifty_desk_save_admin_settings_basic','nifty_desk_security' ); ?>
                <input type='submit' name='nifty_desk_send_feedback' class='button-primary' value='<?php _e("Send Feedback","nifty_desk") ?>' />
           </td>
        </tr>
     </table>
    
    </form>
    
<?php include 'footer.php'; ?>