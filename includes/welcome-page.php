
<center>
    <h1 style="font-weight: 300; font-size: 50px; line-height: 50px;">
        <?php _e("Welcome to ",'nifty_desk'); ?> 
        <strong style='color: #ec6851;'>Nifty Desk</strong> 
        <small>Beta</small>
    </h1>
    <div class="about-text" style="margin: 0;"><?php _e("Create your own support desk in minutes","nifty_desk"); ?></div>
    <img src="<?php echo NIFTY_DESK_PLUGIN_DIR; ?>/images/nifty-desk-welcome.png" width="20%"/>

    <h2 style="font-size: 25px;"><?php _e("How did you find us?","nifty_desk"); ?></h2>
    <form method="post" name="nifty_desk_find_us_form" action="" style="font-size: 16px;">
        <div  style="text-align: left; width:275px;">
            <input type="radio" name="nifty_desk_find_us" id="wordpress" value='repository'>
            <label for="wordpress">
                <?php _e('WordPress.org plugin repository ', 'nifty_desk'); ?>
            </label>
            <br/>
            <input type='text' placeholder="<?php _e('Search Term', 'nifty_desk'); ?>" name='nifty_desk_search_term' style='margin-top:5px; margin-left: 23px; width: 100%  '>
            <br/>
            <input type="radio" name="nifty_desk_find_us" id="search_engine" value='search_engine'>
            <label for="search_engine">
                <?php _e('Google or other search Engine', 'nifty_desk'); ?>
            </label>
            <br/>
            <input type="radio" name="nifty_desk_find_us" id="friend" value='friend'>
            
            <label for='friend'>
                <?php _e('Friend recommendation', 'nifty_desk'); ?>
            </label>
            <br/>   
            <input type="radio" name="nifty_desk_find_us" id='other' value='other'>
            
            <label for='other'>
                <?php _e('Other', 'nifty_desk'); ?>
            </label>
            <br/>
            
            <textarea placeholder="<?php _e('Please Explain', 'nifty_desk'); ?>" style='margin-top:5px; margin-left: 23px; width: 100%' name='nifty_desk_findus_other_url'></textarea>
        </div>
        <div>
            
        </div>
        <div>
            
        </div>
        <div style='margin-top: 20px;'>
            <button name='action' value='nifty_desk_submit_find_us' class="button-primary" style="font-size: 30px; line-height: 60px; height: 60px; margin-bottom: 10px;"><?php _e('Submit', 'nifty_desk'); ?></button>
            <br/>
            <button name='action' value="nifty_desk_skip_find_us" class="button"><?php _e('Skip', 'nifty_desk'); ?></button>
        </div>
    </form> 
</center>

