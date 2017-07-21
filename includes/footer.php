<br /><br />
<hr />
<div class="footer" style="padding:15px 7px;">
    <div id=foot-contents>
        <div class="support">
            <em><?php _e("If you find any errors or if you have any suggestions","nifty_desk");?>, <a href="http://niftydesk.org/support-desk/?utm_source=plugin&utm_medium=link&utm_campaign=getintouch" target="_BLANK"><?php _e("please get in touch with us","nifty_desk"); ?></a>.</em>
            
            <?php if (function_exists("nifty_desk_pro_activate")) { global $nifty_desk_pro_version; global $nifty_desk_pro_version_string; ?>
            
            <br />Nifty Desk Premium Version: <a target='_BLANK' href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=footer_pro"><?php echo $nifty_desk_pro_version.$nifty_desk_pro_version_string; ?></a> |
            <a target="_blank" href="http://niftydesk.org/support-desk/">Support</a>
            <?php } else { global $nifty_desk_version; global $nifty_desk_version_string; ?>
            <br /><?php _e("Nifty Desk Version","nifty_desk"); ?>: <a target='_BLANK' href="http://niftydesk.org/?utm_source=plugin&utm_medium=link&utm_campaign=version_free"><?php echo $nifty_desk_version.$nifty_desk_version_string; ?></a> |
            <a target="_blank" href="http://niftydesk.org/support-desk/?utm_source=plugin&utm_medium=link&utm_campaign=support_footer"><?php _e("Support","nifty_desk"); ?></a> | 
            <a target="_blank" href="http://niftydesk.org/support-desk/?utm_source=plugin&utm_medium=link&utm_campaign=documentation_footer"><?php _e("Documentation","nifty_desk"); ?></a> | 
            <a target="_blank" id="uppgrade" href="http://niftydesk.org/pro-version/?utm_source=plugin&utm_medium=link&utm_campaign=footer" title="Premium Upgrade"><?php _e("Go Premium","nifty_desk"); ?></a>
            <?php } ?>
            
        </div>
    </div>
</div>
 