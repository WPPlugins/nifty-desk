<?php

add_filter("nifty_desk_email_body_build","nifty_desk_filter_control_email_body_build",10,2);
function nifty_desk_filter_control_email_body_build($content,$data) {
	$dir = dirname(dirname(__FILE__));
	$template_mail_template = file_get_contents($dir."/templates/mail_template.html");
	//var_dump($data);

	if (isset($data['username']) && isset($data['password'])) {
		//var_dump("doing login filter");
		$content = apply_filters("nifty_desk_email_login_filter",$template_mail_template,$data);
	} else {
		//var_dump("doing login filter2");
		$content = str_replace("{ticket_login}","",$template_mail_template);
	}
	

//var_dump($content);

	$content = apply_filters("nifty_desk_email_content_filter",$content,$data);
//var_dump($content);

	return $content;

}

add_filter("nifty_desk_email_login_filter","nifty_desk_filter_control_email_login_filter",10,2);
function nifty_desk_filter_control_email_login_filter($content,$data) {
	$dir = dirname(dirname(__FILE__));
	$template_login_template = file_get_contents($dir."/templates/mail_login_template.html");
	
	$nifty_desk_settings = get_option("nifty_desk_settings");
	if (isset($nifty_desk_settings['nifty_desk_settings_email_account_info']) && intval($nifty_desk_settings['nifty_desk_settings_email_account_info']) == 1) {
		$content = str_replace("{ticket_login}",$template_login_template,$content);
		$content = apply_filters("nifty_desk_ticket_internal_tags",$content,$data);
	} else {
		$content = str_replace("{ticket_login}","",$content);
	}
	
	return $content;
} 

add_filter("nifty_desk_email_content_filter","nifty_desk_filter_control_email_content_filter",10,2);
function nifty_desk_filter_control_email_content_filter($content,$data) {
	$dir = dirname(dirname(__FILE__));
	$template_content_template = file_get_contents($dir."/templates/mail_ticket_template.html");

	$ticket_content_holder = "";

	/* build a list of replies */

	$main_id = intval($data['post_id']);
	$meta_data = nifty_desk_get_post_meta_all($main_id);
	if( $meta_data ){
		
		foreach( $meta_data as $meta ){
			//var_dump("getting content for ".$meta->post_id);
			$new_ticket_content = $template_content_template;
			$new_data = array(
				'post_id' => intval($meta->post_id)
			);
			$ticket_content_holder .= apply_filters("nifty_desk_ticket_internal_tags",$new_ticket_content,$new_data);
		}
	}
	
	$new_ticket_content = $template_content_template;
	$new_data = array(
		'post_id' => intval($main_id)
	);
	$ticket_content_holder .= apply_filters("nifty_desk_ticket_internal_tags",$new_ticket_content,$new_data);


	$content = str_replace("{ticket_content}",$ticket_content_holder,$content);
	$content = apply_filters("nifty_desk_ticket_internal_tags",$content,$data);
	return $content;
} 

add_filter("nifty_desk_ticket_internal_tags","nifty_desk_filter_control_ticket_internal_tags",10,2);
function nifty_desk_filter_control_ticket_internal_tags($content,$data) {
    $ticket_link = get_permalink($data['post_id']);

    $content_post = get_post($data['post_id']);
    $ticket_ticket = $content_post->post_content;
    $ticket_date = $content_post->post_date;
    $ticket_author_id = $content_post->post_author;

    $author_details = get_user_by('id', $ticket_author_id);
    $author_name = $author_details->display_name;
    $author_email = $author_details->user_email;

	$s = 40;
	$d = 'mm';
	$r = 'g';
    $url = 'https://www.gravatar.com/avatar/';
    $url .= md5( strtolower( trim( $author_email ) ) );
    $url .= "?s=$s&d=$d&r=$r";
    $url = '<img src="' . $url . '"';
    $url .= ' />';
    $author_image = $url;

    //$content_post = apply_filters('the_content', $content_post);
    //$content_post = str_replace(']]>', ']]&gt;', $content_post);

    $title_title = get_the_title( $data['post_id'] );

    $content = str_replace("{ticket_permalink}",$ticket_link,$content);
    $content = str_replace("{ticket_id}",$data['post_id'],$content);
    $content = str_replace("{ticket_title}",$title_title,$content);
    $content = str_replace("{ticket_ticket}",$ticket_ticket,$content);
    $content = str_replace("{ticket_date}",$ticket_date,$content);
    $content = str_replace("{author_name}",$author_name,$content);
    $content = str_replace("{author_image}",$author_image,$content);
    if (isset($data['username'])) {
    	$content = str_replace("{username}",$data['username'],$content);
    } else {
    	$content = str_replace("{username}","",$content);
    }
    if (isset($data['password'])) {
    	$content = str_replace("{password}",$data['password'],$content);
    } else {
    	$content = str_replace("{password}","",$content);
    }

    $content = str_replace("{reply_above_line}",apply_filters("nifty_desk_email_body_user_ticket_reply_above_line",""),$content);
    if ( isset( $data['ticket_reference'] ) ) {
    	if( isset( $data['response_id'] ) ){
    		$content = str_replace("{ticket_reference}",apply_filters("nifty_desk_email_body_ticket_reference","",$data['ticket_reference'],$data['response_id']),$content); 
    	} else {
    		$content = str_replace("{ticket_reference}",apply_filters("nifty_desk_email_body_ticket_reference","",$data['ticket_reference'],""),$content); 
    	}    	
    }

    return $content;

}

