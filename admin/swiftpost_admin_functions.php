<?php
/* 
*  License: GPLv2
*
*  COPYRIGHT AND TRADEMARK NOTICE
*  Copyright (C) 2015 Swift Impressions. All Rights Reserved.
*  Swift Impressions is a subsidiary of Blog Nirvana.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Blog Nirvana and its subsidiaries from any
*  liability that might arise from it's use. 
*  
*  This program is free software; you can redistribute it and/or
*  modify it under the terms of the GNU General Public License
*  as published by the Free Software Foundation; either version 2
*  of the License or (at your option) any later version.
*  
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*  
*  You should have received a copy of the GNU General Public License
*  along with this program; if not, write to the Free Software
*  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

function swiftpost_license_activate() {
	$swiftpost_config	= get_option('swiftpost_config');
	global $wpfn_notifications;
	$license = get_option('swiftpost_license');
	if ( ! isset($license['license_key']) || !isset($license['server_key']) || (isset($license['license_key']) && strlen($license['license_key']) < 16 ) || ( isset($license['server_key']) && strlen($license['server_key']) < 128 ) ) {
		$user_id  = get_current_user_id();
		$current_user = wp_get_current_user();
		$site = get_bloginfo("url");
		$handle = preg_replace("(^https?://)", "", $site );
		$handle = preg_replace('/[\s\W]+/', '', $handle);
		$data = array (
						'blog' => get_bloginfo("name"),
						'url' => $site,
						'email' => get_bloginfo("admin_email"),
						'handle' => $handle,
						'server' => $_SERVER['SERVER_NAME'],
						'user' => $current_user->user_login ,
						'date' => date("Y-m-d H:i:s"),
						'accepted_terms' => $_POST['swift-accept-terms']
						);
		$post = array (
			'body' => array('data' => $data),
			'timeout' => 20
		);
		$url ="http://api.swiftimpressions.com/plugin_activate";
		$result = wp_safe_remote_post($url, $post);
		$result1 = wp_remote_retrieve_body($result);
		if ($reg = json_decode($result1)) {
			if (isset($reg->error)) {
				$wpfn_notifications->add("License Reg Error 3", __($reg->error),array('status' => 'error','icon' => 'thumbs-down'));
			} else {
				$license['license_key'] 	= $reg->license->license_key;
				$license['server_key'] 		= $reg->license->server_key;
				$license['reg_user'] 		= $current_user->user_login;
				$license['reg_date'] 		= date("Y-m-d H:i:s");
				$license['parent_code'] 	= $reg->license->ParentAdCode;
				$license['status'] 			= "registered";
				$license['level'] 			= $reg->license->level;
				
				$wpfn_notifications->add("Plugin Registered", __( 'The plugin was successfully registered to this installation.'),array('status' => 'success','icon' => 'thumbs-up'));
				update_option('swiftpost_license', $license);
			}
		} else {
			$wpfn_notifications->add("License activate reg error", __('There was a problem registering your plugin. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		}
		
		set_transient("swiftpost_save_errors_{$user_id}", $wpfn_notifications, 45);
	}
}

 
function swift_license_check() {
	global $wpfn_notifications;
	$license = get_option('swiftpost_license');
	$swiftpost_config	= get_option('swiftpost_config');
	
	
	if(isset($license['license_key']) && isset($license['server_key'])) {
		$post = array("timeout" => 200, "body" => array( "license_key" => $license['license_key'], "server_key" => $license['server_key']));
		$url ="http://api.swiftimpressions.com/license_check";
		$reply = wp_safe_remote_post($url, $post);
		$response  = wp_remote_retrieve_body($reply);
		
		if($reg = json_decode($response)) {
			if (isset($reg->error)) {
				$license['server_key']  = "";
				$license['license_key'] = "";
				update_option('swiftpost_license', $license);
				$wpfn_notifications->add("Swift Post License Unregistered", __( 'There was a problem verifing the Swift Post plugin license registration and the plugin has been unregistered. Please register your plugin again on the license info tab using the key from your swiftimpressions.com account. If you are having problems setting up your account or registering the plugin please contact support by phone at 208.991.4865 or by email at njones@swiftimpressions.com' ),array('status' => 'error','icon' => 'thumbs-down'));
				if ($swiftpost_config['debug'] == 'on') $wpfn_notifications->add("Swift Post Debug", "<pre>License Check Result:\n" . var_export($reg, true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
				$user_id  = get_current_user_id();
				return false;
			} else {
				$license['parent_code'] 	= $reg->ParentAdCode;
				$license['level'] 			= $reg->level;
				update_option('swiftpost_license', $license);
				if ($swiftpost_config['debug'] == 'on') $wpfn_notifications->add("Swift Post Debug", "<pre>License Check Result:\n" . var_export($reg, true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
				set_transient("swiftimpressions_license_check", true, 84600);
			}
		}
	}
	
	return true;
	
}
 
function swiftStartSession() {
   
}

function swiftEndSession() {
   delete_transient("swiftimpressions_license_check");
}
 
/*
 * display notices
 * 
 *  
 */

function swiftpost_admin_notices($message) {
	
	global $wpfn_notifications;
	do_action('wpfn_notifications');
	$user_id  = get_current_user_id();
	
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}  else if ( isset( $_POST['save'] ) || isset( $_POST['publish'] ) ) {
		return;
	} else if ( $errors = get_transient("swiftpost_save_errors_{$user_id}") ) { 
		$wpfn_notifications = $errors;
	    do_action('wpfn_notifications');
	    delete_transient("swiftpost_save_errors_{$user_id}");
	}	
}

/*
 * load dashboard/admin scripts
 * 
 *  
 */

function swiftpost_admin_scripts() {
	wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-ui' , "//ajax.googleapis.com/ajax/libs/jqueryui/1.10.0/themes/base/jquery-ui.css?ver=4.4.2"); 
	wp_enqueue_style( 'swiftpost-admin-stylesheet', plugins_url( '/css/swiftpost_admin.css', __FILE__ ) );
	wp_enqueue_script( 'swiftpost-admin-script', plugins_url( '/js/swiftpost_admin.js', __FILE__ ),null,1,true);
	wp_enqueue_script( 'jquery-timepicker', plugins_url( '/js/jquery.timepicker.min.js', __FILE__ ),null,1,true);
	wp_enqueue_style( 'jquery-timepicker', plugins_url( '/css/jquery.timepicker.css', __FILE__ ) );
	wp_enqueue_script( 'jquery-ui-autocomplete' );
	wp_localize_script( 'swiftpost-admin-script', 'swiftposdt_locals', array('license_url' => plugins_url( '/plugin-activate-terms.html', __FILE__ )));
}


/**
 * Add Swift Post edit box to Post Edit.
 */
function swiftpost_add_meta_box() {

	$screens = array( 'post' );

	foreach ( $screens as $screen ) {

		add_meta_box(
			'swiftpost_pp_edit',
			__( 'Swift Post Setup', 'swiftpost_textdomain' ),
			'swiftpost_meta_box_callback',
			$screen, 'side', 'core'
		);
	}
}
add_action( 'add_meta_boxes', 'swiftpost_add_meta_box' );

/**
 * Prints the box content.
 * 
 * @param WP_Post $post The object for the current post/page.
 */
function swiftpost_meta_box_callback( $post ) {
	global $wpdb;
	$user_id = get_current_user_id();
	$query = "SELECT * FROM ".$wpdb->prefix."swiftpost_nativepost WHERE `postid` = " . $post->ID;
	$disabled = "";
	$startclass = "datepicker";
	$license = get_option('swiftpost_license');
	$not_reg = "";
	$disabled = "";
	$date_disabled 	= "";
	if ($license['status'] != "registered" ) {
		$not_reg = '<p>The Swift Post plugin has not yet been registered. Please enter the unique license code your received after registering on SwiftImpressions.com. If you are having problems setting up your account or registering the plugin please contact support by phone at 208.991.4865 or by email at njones@swiftimpressions.com</p>';
		$disabled = " disabled=\"disabled\"  readonly=\"readonly\"  ";
		
	}
	
	if ( ($default = $wpdb->get_results($query)) && $default[0]->post_status == "checked") {
		$startdate = $default[0]->startdate;
		$starttime = $default[0]->starttime;
		if (!($startdate > date("Y-m-d"))) {
			$date_disabled 	= ' readonly="readonly" ';
			$startclass = "";
		}
		$enddate 		= $default[0]->enddate;
		$endtime 		= $default[0]->endtime;
		$post_status 	= "checked";
		$impressions 	= $default[0]->impressions;
		$fc 			= $default[0]->fc;
		$fc_impressions = $default[0]->fc_impressions ;
		$fc_howmany 	= $default[0]->fc_howmany ;
		$fc_type	 	= $default[0]->fc_type;
		$fc_lifetime 	= $default[0]->fc_lifetime;
		$gd				= $default[0]->gd;
		$geo		 	= ( empty($default[0]->geo) ? "" : unserialize($default[0]->geo));
		$geo_json 		= (empty($geo) ? "" : json_encode($geo));
		$status			= $default[0]->status;
	} else {
		$post_status 	= "unchecked";
		$startdate 		= "";
		$starttime 		= "";
		$enddate	 	= "";
		$endtime	 	= "";
		$post_status 	= "";
		$impressions 	= "";
		$fc 			= "off";
		$fc_impressions = "";
		$fc_howmany 	= "";
		$fc_type	 	= "";
		$fc_lifetime 	= "";
		$gd				= "off";
		$geo		 	= "";
		$geo_json 		= "";
		$status			= "";
	}
	

	// Add a nonce field so we can check for it later.
	wp_nonce_field( 'swiftpost_save_meta_box_data', 'swiftpost_meta_box_nonce' );
	
	
    echo $not_reg;
   ?>
    <p>
     <input type="checkbox" id="swiftpost_pp_on" name="swiftpost_pp_on"  <?php echo $post_status; ?> size="25"  <?php echo $disabled; ?> />
	 <label for="swiftpost_new_field">Enable as Swift Post</label>
	</p>
	<?php if ($post_status == "checked") :?>
		<p>
	     <input type="checkbox" id="swiftpost_pp_pasued" name="swiftpost_pp_paused" <?php echo $disabled; ?> <?php echo ($status != "live" ? "checked" : "") ; ?> size="25" />
		 <label for="swiftpost_new_field">Pause</label>
		</p>
	<?php endif; ?>
	<div class="swiftpost-meta-section" id="swiftpost-meta-general">
	<p><label>Start Date: </label> <input type="text" id="pp-start-date" class="<?php echo $startclass; ?>" name="swiftpost_pp_start_date" value="<?php echo $startdate; ?>"  <?php echo $disabled; ?> <?php echo $date_disabled; ?> /></p>
	<p><label>Start time: </label> <input type="text" id="pp-start-time" class="timepicker" name="swiftpost_pp_start_time" value="<?php echo $starttime; ?>"  <?php echo $disabled; ?> <?php echo $date_disabled; ?> /></p>
	<p><label>End Date: </label> <input type="text" id="pp-end-date" class="datepicker" name="swiftpost_pp_end_date" value="<?php echo $enddate; ?>" <?php echo $disabled; ?> /></p>
	<p><label>End Time: </label> <input type="text" id="pp-end-time" class="timepicker" name="swiftpost_pp_end_time" value="<?php echo $endtime; ?>"  <?php echo $disabled; ?> /></p>
	<p><label>Total Impressions: </label> <input type="text" id="pp-quanity" class="" name="swiftpost_pp_quanity" value="<?php echo $impressions; ?>" <?php echo $disabled; ?> /></p>
	<?php if (isset($default[0]->post_status)): ?>
		<p><input type="checkbox" name="swiftpost-newline" id="swiftpost-newline" style="width: 10px;"  <?php echo $disabled; ?> /> Start New Run?</p>
	<?php endif; ?>
	
	</div>
  


	<div class="swiftpost-meta-section" id="swiftpost-meta-pp-fc-section">
		<h4>Frequency</h4> 
		
		<p>
			<input type="checkbox" value="on" id="UserFrequency-input" name="user-frequency-input" <?php echo ($fc=="on" ? "checked" : ""); ?>  <?php echo $disabled; ?> />
			<label  id="chkPerUserFrequency-label">Set per user frequency cap</label>
		</p>
		<p>Impressions per user:</p>
		<p>
			<input type="text" class="swiftpost-fc-shorttext" tabindex="0" maxlength="5" name="swiftpost_fc_impress"  <?php echo ($fc=="on" ? " value='$fc_impressions' " : ""); ?> length="3" <?php echo $disabled; ?> /> per 
			<input type="text" class="swiftpost-fc-shorttext" tabindex="0" maxlength="5" name="swiftpost_fc_howmany" <?php echo ($fc=="on" ? " value='$fc_howmany' " : ""); ?> length="3" <?php echo $disabled; ?> /> 
			<select name="swiftpost_fc_type" class="">
				<option value="MINUTE" <?php echo ($fc=="on" && $fc_type=="MINUTE" ? " selected " : ""); ?> <?php echo $disabled; ?> >minutes</option>
				<option value="HOUR"<?php echo ($fc=="on" && $fc_type=="HOUR" ? " selected " : ""); ?> <?php echo $disabled; ?> >hours</option>
				<option value="DAY"<?php echo ($fc=="on" && $fc_type=="DAY" ? " selected " : ""); ?> <?php echo $disabled; ?> >days</option>
				<option value="WEEK"<?php echo ($fc=="on" && $fc_type=="WEK" ? " selected " : ""); ?> <?php echo $disabled; ?> >weeks</option>
				<option value="MONTH"<?php echo ($fc=="on" && $fc_type=="MONTH" ? " selected " : ""); ?> <?php echo $disabled; ?> >months</option>
			</select>
   		</p>
		<p>and/or</p>
		
			<input type="text" class="swiftpost-fc-shorttext" tabindex="0" maxlength="5" name="swiftpost_fc_impress_lifetime"  length="3"<?php echo ($fc=="on" ? " value='$fc_lifetime' " : ""); ?>  <?php echo $disabled; ?> /> max impressions per visitor
		
	</div>

	<div class="swiftpost-meta-section" id="swiftpost-meta-pp-gt-section">
	    <h4>Geo Targeting</h4>
		<p>
			<input type="checkbox" value="on" id="geo-data-input" name="geo-data-input" <?php echo ($gd=="on" ? "checked" : ""); ?>  <?php echo $disabled; ?> />
			<label  id="chkGeoData-label">Limit to geographical areas</label>
		</p>
		<div id="pp-targeting-searchbox">
		     Target Type:
		     <select name="swiftpost_gt_type" id="swiftpost_gt_type" <?php echo $disabled; ?> >
			        <option>--Choose Type--</option>
			     	<option value="City">City</option>
			     	<option value="Country">Country</option>
			     	<option value="Postal_Code">Postal Code</option>
			     	<option value="State">State</option>
		     </select>
            <br />		
			<input id="swiftpost_gt_search" type="text"  <?php echo $disabled; ?> /><a id="swiftpost_gt_button" class="button btn" <?php echo $disabled; ?> >Search</a>
		</div>
		<div id="swiftpost_gt_results"></div>
		<p>Targets:</p>
		<div id="swiftpost_gt_selections"></div>
	</div>
	
		<input type="hidden" name="swiftpost-geotargets" id="swiftpost-geotargets" value='<?php echo $geo_json;?>' />
	<div style="clear:both;"><!--Clear--></div>
	<?php
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function swiftpost_save_meta_box_data($post_id, $abtest = false) {
	$user_id = get_current_user_id();
    $swiftpost_config	= get_option('swiftpost_config');
	$license = get_option('swiftpost_license');
	$return = "";
	// add the notifications class
	global $wpfn_notifications;
	// get db class
	global $wpdb;
	
	//Check License
	if ($license['status'] != "registered" && isset( $_POST['swiftpost_pp_on']) ) {
		if(	$license['status'] == "unregistered" ) {
			$wpfn_notifications->add("Swift Post License Unregistered", __( 'Error: The  Swift Post plugin has not yet been registered. Please enter the unique license code your received after registering on SwiftImpressions.com. If you are having problems setting up your account or registering the plugin please contact support by phone at 208.991.4865 or by email at njones@swiftimpressions.com' ),array('status' => 'error','icon' => 'thumbs-down'));
		} else {
			$wpfn_notifications->add("Swift Post License Unregistered", __( 'Error: The  Swift Post plugin has not yet been registered. Please enter the unique license code your received after registering on SwiftImpressions.com. If you are having problems setting up your account or registering the plugin please contact support by phone at 208.991.4865 or by email at njones@swiftimpressions.com' ),array('status' => 'error','icon' => 'thumbs-down'));
		}
		set_transient("swiftpost_save_errors_{$user_id}", $wpfn_notifications, 45);
		return;
	}
	
	$query = "SELECT * FROM ".$wpdb->prefix."swiftpost_nativepost WHERE `postid` = " . $post_id;
	if (!$data = $wpdb->get_row($query,ARRAY_A,0)) {
		$data = array();
		$data['created'] = date("Y-m-d");
	}
	/*
	 * We need to verify this came from our screen and with proper authorization,
	 * because the save_post action can be triggered at other times.
	 */

	// Check if our nonce is set.
	if ( ! isset( $_POST['swiftpost_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['swiftpost_meta_box_nonce'], 'swiftpost_save_meta_box_data' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if(  wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	

	// Check the user's permissions.
	if ((isset( $_POST['post_type'] ) && 'post' == $_POST['post_type'] ) || $abtest === true ) {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

	} else {
			return;
	}

	// OK, it's safe for us to save the data now. 
	// Is a Swift Post?.
	
	$post_status = "off";
	$status = 'live';
	if (isset( $_POST['swiftpost_pp_on'])) $post_status = "checked";
	if (isset( $_POST['swiftpost_pp_paused'])) $status = 'paused';

	
	if ( $post_status == "off" ) {
		if(isset($data["postid"]) ) {
			$data["post_status"] = "off";
			if(!($wpdb->replace( "{$wpdb->prefix}swiftpost_nativepost", $data))) {
				$wpfn_notifications->add("Swift Post DB Update Error", __( 'There was an error disabling your Swift Post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
				// add error reporting
				return;
			} 
			
			/* Archive Order, poweroff */
			$request = array("postid" => $post_id);
			$post = array("timeout" => 200, "body" => array("request" => $request, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
			// Update Ad Server
			$url ="http://api.swiftimpressions.com/poweroff";
			$reply = wp_safe_remote_post($url, $post);
			$response  = wp_remote_retrieve_body($reply);
			
			if($res = json_decode($response)) {
				if(isset($res->error)) {
					$wpfn_notifications->add("Swift Disable Error1", __('There was an error disabling your Swift Post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
					//error reporting  $res->error
				} else {
					$wpfn_notifications->add("Swift Post Disabled", __('Swift Posting has been disabled for this post'),array('status' => 'success','icon' => 'thumbs-up'));
				}
			} else {
				$wpfn_notifications->add("Swift Disable Error2", __('There was an error disabling your Swift Post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
				//error reporting  $res->error
			}
			if ($swiftpost_config['debug'] == 'on') $wpfn_notifications->add("Swift Post Debug", "<pre>PostOff Query =>\n\n" . var_export($post, true) . "\n\nPostoff Response =>\n\n" . var_export($res, true) . "\n\nreply->\n\n" . var_export($reply,true)."</pre>",array('status' => 'debug','icon' => 'hammer')); 
		} else {
			if ($swiftpost_config['debug'] == 'on') $wpfn_notifications->add("Swift Post Debug", "<pre>PostOff Not Set, nothing to turn off</pre>",array('status' => 'debug','icon' => 'hammer')); 
		}
	} else if ($status == 'paused') {
		if(isset($data["postid"]) ) {
			$data["status"] = "paused";
			if(!($wpdb->replace( "{$wpdb->prefix}swiftpost_nativepost", $data))) {
				$wpfn_notifications->add("Swift Post DB Update Error", __( 'There was an error pausing your Swift Post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
				// report error  $wpdb->print_error()
				return;
			} 
			
			/* Pause Order, poweroff */
			$request = array("postid" => $post_id);
			$post = array("timeout" => 200, "body" => array("request" => $request, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
			// Update Ad Server
			$url ="http://api.swiftimpressions.com/poweroff";
			$reply = wp_safe_remote_post($url, $post);
			$response  = wp_remote_retrieve_body($reply);
			
			if($res = json_decode($response)) {
				if(isset($res->error)) {
					$wpfn_notifications->add("Swift Disable Error1", __('There was an error pausing your Swift Post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
					// report error $res->error
				} else {
					$wpfn_notifications->add("Swift Post Paused", __('Swift Posting has been paused for this post'),array('status' => 'success','icon' => 'thumbs-up'));
				}
			} else {
				$wpfn_notifications->add("Swift Disable Error2", __('There was an error pausing your Swift Post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com' . $res->error),array('status' => 'error','icon' => 'thumbs-down'));
				// report error
			}
			if ($swiftpost_config['debug'] == 'on') $wpfn_notifications->add("Swift Post Debug", "<pre>PostOff Query =>\n\n" . var_export($post, true) . "\n\nPostoff Response =>\n\n" . var_export($res, true) . "\n\nreply->\n\n" . var_export($reply,true)."</pre>",array('status' => 'debug','icon' => 'hammer')); 
		} else {
			if ($swiftpost_config['debug'] == 'on') $wpfn_notifications->add("Swift Post Debug", "<pre>Swift Post Not Set, nothing to pause</pre>",array('status' => 'debug','icon' => 'hammer'));
		}
	} else {
		// Sanitize user input.
		$startdate 		= sanitize_text_field( $_POST['swiftpost_pp_start_date'] );
		$enddate 		= sanitize_text_field( $_POST['swiftpost_pp_end_date'] );
		$starttime 		= sanitize_text_field( $_POST['swiftpost_pp_start_time'] );
		$endtime 		= sanitize_text_field( $_POST['swiftpost_pp_end_time'] );
		$impressions 	= sanitize_text_field( $_POST['swiftpost_pp_quanity'] );
		if (isset($_POST['user-frequency-input']) && $_POST['user-frequency-input']=="on") {
			$fc 			= sanitize_text_field( $_POST['user-frequency-input'] );
			$fc_impress 	= sanitize_text_field( $_POST['swiftpost_fc_impress'] );
			$fc_howmany 	= sanitize_text_field( $_POST['swiftpost_fc_howmany'] );
			$fc_type	 	= sanitize_text_field( $_POST['swiftpost_fc_type'] );
			$fc_lifetime 	= sanitize_text_field( $_POST['swiftpost_fc_impress_lifetime'] );
		} else {
			$fc = "off";
			$fc_impress 	= "";
			$fc_howmany 	= "";
			$fc_type	 	= "";
			$fc_lifetime 	= "";
		}
		if (isset( $_POST['swiftpost-newline'])) {
			$data['run'] = "newrun";
		} else if (!isset($data['run']) ||  $data['run'] <  1) {
			$data['run'] = 1;
		}
		
		
		//Check values then break and return errors if there are any.
		$errors = 0;
		if (!is_numeric($impressions) || $impressions > 1000000) {
    		$errors++;
    		$wpfn_notifications->add("SPFormError1", __('Invalid Swift Post value - Total Impressions  must be numeric and less than 1,000,000'),array('status' => 'error','icon' => 'thumbs-down'));
    	}
	    if ($fc=="on") {
	    	if (isset($fc_impress) && (!is_numeric($fc_impress) || $fc_impress > 1000)) {
	    		$errors++;
	    		$wpfn_notifications->add("SPFormError2", __('Invalid Swift Post value - 1rst box in Impressions per user: must be numeric and less than 1000'),array('status' => 'error','icon' => 'thumbs-down'));
	    	}
		    if (isset($fc_impress) && (!is_numeric($fc_howmany) || $fc_howmany > 1000)) {
	    		$errors++;
	    		$wpfn_notifications->add("SPFormError3", __('Invalid Swift Post value - 2nd box in Impressions per user: must be numeric and less than 1000' ),array('status' => 'error','icon' => 'thumbs-down'));
	    	}
	    }
	    $dateTimestamp1 = new DateTime($startdate . " " . date("H:i:s",strtotime($starttime)), new DateTimeZone('America/New_York'));
		$dateTimestamp2 = new DateTime($enddate . " "  .date("H:i:s" , strtotime($endtime)), new DateTimeZone('America/New_York'));
		$dateTimestamp3 = new DateTime("NOW", new DateTimeZone('America/New_York'));
	    if ($dateTimestamp1 >= $dateTimestamp2  || $dateTimestamp2 <= $dateTimestamp3 ) {
	    	$errors++;
	    	$wpfn_notifications->add("SPFormError4", __('Invalid Swift Post form value: Dates must be in the format yyyy-mm-dd and the end date must be greater than the start date which must be today or later. '),array('status' => 'error','icon' => 'thumbs-down'));
	    }
	    if ($errors > 0) {
	    	set_transient("swiftpost_save_errors_{$user_id}", $wpfn_notifications, 45);
	    	return;
	    }
	    
	    
		$geo		 			= stripcslashes($_POST['swiftpost-geotargets']) ;
		$geo_php				= json_decode($geo);	
		$data["postid"]			= $post_id;
		$data["impressions"]	= $impressions;
		$data["startdate"] 		= $startdate;
		$data["enddate"] 		= $enddate;
		$data["starttime"] 		= $starttime;
		$data["endtime"] 		= $endtime;
		$data["fc"]				= $fc;
		$data["fc_impressions"] = $fc_impress;
		$data["fc_howmany"] 	= $fc_howmany;
		$data["fc_type"] 		= $fc_type;
		$data["fc_lifetime"] 	= $fc_lifetime;
		$data["geo"] 			= $geo_php;
		$data["post_status"] 	= $post_status;


		$post = array("timeout" => 200, "body" => array("request" => $data, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
		
		// Update Ad Server

		$url ="http://api.swiftimpressions.com/poweron";
		$reply = wp_safe_remote_post($url, $post);
		$response = wp_remote_retrieve_body($reply);

		if ($res = json_decode($response)) {
			if(isset($res->overbook) && $res->overbook == true) {
			    $wpfn_notifications->add("Swift Post Update Error", $res->error,array('status' => 'error','icon' => 'thumbs-down'));
			} else if (isset($res->error)) {
				$wpfn_notifications->add("Swift Post Update Error", __('There was an error creating/updating your Swift Post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
				// report error $res->error
			} else {
				// Update Swift Post Table
				if ( isset($data['geo']) && !empty($data['geo']) ) { 
					$data['geo'] = serialize($data['geo']);
				} else {
					$data['geo'] = "";	
				}
				$data["order_id"] = $res->orderid;
				$data["lineitem_id"] = $res->lineitemid;
				$data["run"] = $res->run;
				$data["status"] = "live";
				if ($abtest) $data["status"] = "abtest";
				if(!($wpdb->replace( "{$wpdb->prefix}swiftpost_nativepost", $data))) {
					$wpfn_notifications->add("Swift Post DB Update Error", __( 'There was an error creating/updating your Swift Post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
					if ($abest) $return = array("error" => "DB Error");
					// report error $wpdb->print_error()
				} else {
					$wpfn_notifications->add("Swift Post Created/Updated", __( 'Swift Post Created/Updated'),array('status' => 'success','icon' => 'thumbs-up'));
					//$wpfn_notifications->add("Swift Post Impression Count", $res->count_message, array('status' => 'success','icon' => 'thumbs-up'));
					if ($abtest) $return = $data;
				}	
			}
		} else {
			$wpfn_notifications->add("Swift Post Error", __( 'There was an error creating/updating your Swift Post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
			if ($abest) $return = array("error" => "Post Update Error");
			// report error unable to decode response $res
		}
		if ($swiftpost_config['debug'] == 'on') $wpfn_notifications->add("Swift Post Debug", "<pre>PostOn Query =>\n\n" . var_export($post, true) . "\n\nPoston Response =>\n\n" . var_export($res, true) . "\n\nreply->\n\n" . var_export($reply,true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
	}	
	set_transient("swiftpost_save_errors_{$user_id}", $wpfn_notifications, 45);
	
	return $return;
	
}

/*
 * Apply License Key to Blog Installation
 *
 */
function swiftpost_license_apply() {
	$swiftpost_config	= get_option('swiftpost_config');
	global $wpfn_notifications;
	$license = get_option('swiftpost_license');
	if ( isset($_POST['license_key'])) {
		$key = sanitize_text_field($_POST['license_key']);
		$current_user = wp_get_current_user();
		$data = array (
			'body' => array (
						'key' => $key,
						'server' => $_SERVER['SERVER_NAME'],
						'user' => $current_user->user_login ,
						'date' => date("Y-m-d H:i:s")
						),
			'timeout' => 20
		);
	
		$url ="http://api.swiftimpressions.com/register";
		$result = wp_safe_remote_post($url, $data);
		$result1 = wp_remote_retrieve_body($result);
	
		if ($reg = json_decode($result1)) {
			if (isset($reg->error)) {
				$wpfn_notifications->add("License Reg Error 3", __($reg->error),array('status' => 'error','icon' => 'thumbs-down'));
			} else {
				$license['license_key'] 	= $reg->license->license_key;
				$license['server_key'] 		= $reg->license->server_key;
				$license['reg_user'] 		= $reg->license->reg_user;
				$license['reg_date'] 		= $reg->license->reg_date;
				$license['parent_code'] 	= $reg->license->ParentAdCode;
				$license['status'] 			= "registered";
				$license['level'] 			= $reg->license->level;
				
				$wpfn_notifications->add("Plugin Registered", __( 'The plugin was successfully registered to this installation.'),array('status' => 'success','icon' => 'thumbs-up'));
				
				update_option('swiftpost_license', $license);
			}
		} else {
			$wpfn_notifications->add("License Reg Error 1", __('There was a problem registering your plugin. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		}

	} else {
		$wpfn_notifications->add("License Reg Error 2", __('There was a problem registering your plugin. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
	}

	if ($swiftpost_config['debug'] == 'on') $wpfn_notifications->add("Swift Post Debug", "<pre>License Register Result:\nrequest:\n" . var_export($reg, true)."\nresult:\n" . var_export($result, true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
	
}

/**
 * License Release
 *
 *
 */
function swiftpost_license_release() {
	$swiftpost_config	= get_option('swiftpost_config');
	$license = get_option('swiftpost_license');
	
	global $wpfn_notifications;
	
	if (isset($_POST['license_key']) && $_POST['license_key'] != $license['license_key'] ) {
		$wpfn_notifications->add("License Release Error2", __('There was a problem releasing your license key. The license key in the form did not match your currently registered license, please copy your license key and paste it into the form. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		
	} else if ( isset($_POST['license_key']) && isset( $license['server_key'])) {
		$key = sanitize_text_field($_POST['license_key']);
		$data = array (
			'body' => array (
						'license_key' => $key,
						'server_key' => $license['server_key']
						)
		);
		
		$url ="http://api.swiftimpressions.com/release";
		$result = wp_remote_retrieve_body(wp_safe_remote_post($url, $data));
		if (isset($result['error'])) {
			//$license['error'] =  $result['error'];
			$wpfn_notifications->add("License Release Error1", __('There was a problem releasing your license key. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		} else {
			$license['license_key'] 	= "";
			$license['server_key'] 		= "";
			$license['reg_user'] 		= "";
			$license['reg_date'] 		= "";
			$license['status'] 			= "unregistered";
			//$license['error'] = "Your license key has been released";
			$wpfn_notifications->add("License Released", __('Your license key has been released.'),array('status' => 'success','icon' => 'thumbs-up'));
		}
	} else {
		$wpfn_notifications->add("License Released", __('There was a problem releasing your license key. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
	}
	update_option('swiftpost_license', $license);
	
		 
	if ($swiftpost_config['debug'] == 'on') $wpfn_notifications->add("Swift Post Debug", "<pre>License Release Result:\n" . var_export($result, true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
}

/**
 * Apply Post Status cahnges from post manage page
 *
 *
 */
function swiftpost_postmanage($action, $postid, $run) {
	$swiftpost_config = get_option('swiftpost_config');
	// add the notifications class
	global $wpfn_notifications;
	// get db class
	global $wpdb;
	$query = "SELECT * FROM ".$wpdb->prefix."swiftpost_nativepost WHERE `postid` = " . $postid;
	
	if ($data = $wpdb->get_row($query,ARRAY_A,0)) {
		$license = get_option('swiftpost_license');
		/* Pause Order, poweroff */
		$request = array("postid" => $postid, "action" => $action, "run" => $run, "data" => $data);
		$post = array("timeout" => 200, "body" => array("request" => $request, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
		// Update Ad Server
		$url ="http://api.swiftimpressions.com/poweronmanage";
		$reply = wp_safe_remote_post($url, $post);
		$response  = wp_remote_retrieve_body($reply);
		
		if($res = json_decode($response)) {
			if(isset($res->error)) {
				$wpfn_notifications->add("Swift Pause Error 1", __('Error turning off Swift Post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com' . $res->error),array('status' => 'error','icon' => 'thumbs-down'));
				// report error $res->error
			} else if(isset($res->success)) {
				if ($action == 'pause') {
					$data['status'] = "paused";
				} else if ($action == 'resume') {
					$data['status'] = "live";
				} else if ($action == 'new run') {
					$data['status'] = "live";
					$data['run'] = $res->run;
					$data['lineitem_id'] = $res->lineitemid;
					$data['startdate'] = $res->startdate;
					$data['enddate'] = $res->enddate;
				}
				
				if(!($wpdb->replace( "{$wpdb->prefix}swiftpost_nativepost", $data))) {
					$wpfn_notifications->add("Swift Post DB Update Error", __( 'There was a problem updating your post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com' . $wpdb->print_error()),array('status' => 'error','icon' => 'thumbs-down'));
					// report error $wpdb->print_error()
				} else {
					$wpfn_notifications->add("Swift Post Updated", __( 'Swift Post Updated, status set to '.$action."d"),array('status' => 'success','icon' => 'thumbs-up'));
				}
				
			}
		} else {
			$wpfn_notifications->add("Swift Pause Error 2", __('There was a problem pausing your post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
			// report error  $res->error
		}
		if ($swiftpost_config['debug'] == 'on') $wpfn_notifications->add("Swift Post Debug", "<pre>Request Query =>\n " . var_export($request, true) . "\nPostoff Response =>\n\n" . var_export($res, true) . "</pre>",array('status' => 'debug','icon' => 'hammer'));
	
	}

}


/**
 * If post is trashed, cancel order
 *
 *
 */

function swift_post_transition( $new_status, $old_status, $post) {
    $post_id = $post->ID;
    
    // Check the user's permissions.
	if ( isset( $post->post_type ) && 'post' ==  $post->post_type  ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	} else {
			return;
	}
	// add the notifications class
	global $wpfn_notifications;
	$user_id  = get_current_user_id();
	// get db class
	global $wpdb;
	$query = "SELECT * FROM ".$wpdb->prefix."swiftpost_nativepost WHERE `postid` = " . $post_id;
	
	if ($data = $wpdb->get_row($query,ARRAY_A,0)) {
		if ($data['status'] == "live" && $old_status == "publish" && $new_status == "trash") {
			swiftpost_postmanage("pause", $post_id, $data['run']);
			set_transient("swiftpost_save_errors_{$user_id}", $wpfn_notifications, 45);
		} else if ($data['status'] == "paused" && $new_status == "publish" && $old_status == "trash") {
			swiftpost_postmanage('resume', $post_id, $data['run']);
			set_transient("swiftpost_save_errors_{$user_id}", $wpfn_notifications, 45);
		}
		
	}
  
}

/**
 * A/B add test form
 *
 *
 */


function swiftpost_abtest_form() {
	  global $wpdb, $swiftpost_config, $wpfn_notifications;
	  $license = get_option('swiftpost_license');

     ?>	
     
    <form name="abtest-new" id="abtest-new" method="post" action="<?php echo admin_url("admin.php?page=swiftpost-abtest"); ?>">   
	<input type="hidden" name="swiftpost_pp_on" value="on" />
	<input type="hidden" name="swiftpost-newline" value="on" />
	<input type="hidden" id="abtest_p1" name="abtest_p1" value="0" />
	<input type="hidden" id="abtest_p2" name="abtest_p2" value="0" />
	<?php wp_nonce_field( 'swiftpost_save_meta_box_data', 'swiftpost_meta_box_nonce' ); ?> 
	
    <div id="swiftpost-admin-block-fw" class="swift-admin-box-fw ab-page postbox">
    	<h2>Start New Test</h2>
    	<div>
	     	<label>Name this test: </label> <input name="swiftpost_abtest_name" value="" id="swiftpost_abtest_name" type="text">
	     </div>
	     <div>
	     	<label><!--Hypothesis--> </label> <input name="swiftpost_abtest_hypothesis" value="" id="swiftpost_abtest_hypothesis" type="hidden">
	     </div>
	    <div>
	     	<label>Select SwiftPost for A: </label> <input name="swiftpost_atest" value="" id="swiftpost_atest" type="text">
	     </div>
	     <div>
	     	<label>Select SwiftPost for B: </label> <input name="swiftpost_btest" value="" id="swiftpost_btest" type="text" disabled="disabled">
	     </div>
	     <div id="test-block"></div>
    
		<div class="swiftpost-meta-section" id="swiftpost-meta-general">
		<p><label>Start Date: </label> <input type="text" id="pp-start-date" class="datepicker" name="swiftpost_pp_start_date" value=""  /></p>
		<p><label>Start time: </label> <input type="text" id="pp-start-time" class="timepicker" name="swiftpost_pp_start_time" value="" /></p>
		<p><label>End Date: </label> <input type="text" id="pp-end-date" class="datepicker" name="swiftpost_pp_end_date" value=""  /></p>
		<p><label>End Time: </label> <input type="text" id="pp-end-time" class="timepicker" name="swiftpost_pp_end_time" value=""   /></p>
		<p><label>Total Impressions: </label> <input type="text" id="pp-quanity" class="" name="swiftpost_pp_quanity" value=""  /></p>
		<?php if (isset($default[0]->post_status)): ?>
			<p><input type="checkbox" name="swiftpost-newline" id="swiftpost-newline" style="width: 10px;"  /> Start New Run?</p>
		<?php endif; ?>
		
		</div>
	  
	
	
		<div class="swiftpost-meta-section" id="swiftpost-meta-pp-fc-section">
			<h4>Frequency</h4> 
			
			<p>
				<input type="checkbox" value="on" id="UserFrequency-input" name="user-frequency-input" />
				<label  id="chkPerUserFrequency-label">Set per user frequency cap</label>
			</p>
			<p>Impressions per user:</p>
			<p>
				<input type="text" class="swiftpost-fc-shorttext" tabindex="0" maxlength="5" name="swiftpost_fc_impress"   length="3"  /> per 
				<input type="text" class="swiftpost-fc-shorttext" tabindex="0" maxlength="5" name="swiftpost_fc_howmany"  length="3"  /> 
				<select name="swiftpost_fc_type" class="">
					<option value="MINUTE"  >minutes</option>
					<option value="HOUR" >hours</option>
					<option value="DAY" >days</option>
					<option value="WEEK" >weeks</option>
					<option value="MONTH" >months</option>
				</select>
	   		</p>
			<p>and/or</p>
			
				<input type="text" class="swiftpost-fc-shorttext" tabindex="0" maxlength="5" name="swiftpost_fc_impress_lifetime"  length="3" /> max impressions per visitor
			
		</div>
	
		<div class="swiftpost-meta-section" id="swiftpost-meta-pp-gt-section">
		    <h4>Geo Targeting</h4>
			<p>
				<input type="checkbox" value="on" id="geo-data-input" name="geo-data-input" />
				<label  id="chkGeoData-label">Limit to geographical areas</label>
			</p>
			<div id="pp-targeting-searchbox">
			     Target Type:
			     <select name="swiftpost_gt_type" id="swiftpost_gt_type"  >
			     	<option>--Choose Type--</option>
			     	<option value="City">City</option>
			     	<option value="Country">Country</option>
			     	<option value="Postal_Code">Postal Code</option>
			     	<option value="State">State</option>
			     </select>
	            <br />		
				<input id="swiftpost_gt_search" type="text"   /><a id="swiftpost_gt_button" class="button btn" >Search</a>
			</div>
			<div id="swiftpost_gt_results"></div>
			<p>Targets:</p>
			<div id="swiftpost_gt_selections"></div>
		</div>
		<div style="clear:both;"><!--Clear--></div>
			<input type="hidden" name="swiftpost-geotargets" id="swiftpost-geotargets" value='' />
			<input name="submit" type="submit" class="swift-btn-rainbow"  value="Start Test">
		
	</div>
	
	</form>
	<?php
	
	
	
}

/**
 * A/B add test
 *
 *
 */


function swiftpost_abtest_add() {
	$swiftpost_config	= get_option('swiftpost_config');
	$license = get_option('swiftpost_license');
	$return = "";
	// add the notifications class
	global $wpfn_notifications;
	// get db class
	global $wpdb;
	
	$test_a = swiftpost_save_meta_box_data($_POST['abtest_p1'], true);
	$test_b = swiftpost_save_meta_box_data($_POST['abtest_p2'], true);
	
	$data = array();
	
	$data['name'] 		= sanitize_text_field($_POST['swiftpost_abtest_name']);
	$data['hypothesis'] = sanitize_text_field($_POST['swiftpost_abtest_hypothesis']);
	$data['postid_a'] 	= $_POST['abtest_p1'];
	$data['postid_b'] 	= $_POST['abtest_p2'];
	$data['run_a'] 		= $test_a['run'];
	$data['run_b'] 		= $test_b['run'];
	$data['startdate'] 	= $test_a['startdate'];
	$data['starttime'] 	= $test_a['starttime'];
	$data['enddate'] 	= $test_a['enddate'];
	$data['endtime'] 	= $test_a['endtime'];
	$data['status']  	= 'running';
	$data['created'] 	= date('Y-m-d G:i:s');
	
	if ($swiftpost_config['debug'] == 'on') $wpfn_notifications->add("Swift Post Debug", "<pre>AB Test Query =>\n\n" . var_export($data, true) ."</pre>",array('status' => 'debug','icon' => 'hammer'));

	if(!($wpdb->replace( "{$wpdb->prefix}swiftpost_abtest", $data))) {
		$wpfn_notifications->add("Swift Post AB Test DB Update Error", __( 'There was an error creating/updating your Swift Post AB Test. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		return array("error" => "DB Error");
		
	} else {
		$wpfn_notifications->add("Swift Post AB Test Created", __( 'Swift Post AB Test Created'),array('status' => 'success','icon' => 'thumbs-up'));
		return array("sccess" => TRUE);
	}	
}

/**
 * Apply Post Status cahnges from post manage page
 *
 *
 */
function swiftpost_abtest_manage($action, $testid) {
	$swiftpost_config = get_option('swiftpost_config');
	// add the notifications class
	global $wpfn_notifications;
	// get db class
	global $wpdb;
	$license = get_option('swiftpost_license');
	
	$test = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."swiftpost_abtest WHERE `id` = " . $testid, ARRAY_A,0);
    
    
    $data_a = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."swiftpost_nativepost WHERE `postid` = " . $test['postid_a'], ARRAY_A,0);
    $data_b = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."swiftpost_nativepost WHERE `postid` = " . $test['postid_b'], ARRAY_A,0);
    
	$request_a = array("postid" => $test['postid_a'], "action" => $action, "run" => $test['run_a'], "data" => $data_a, "swiftad" => true);
	$post_a = array("timeout" => 200, "body" => array("request" => $request, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
	
	$request_b = array("postid" => $test['postid_b'], "action" => $action, "run" => $test['run_b'], "data" => $data_b, "swiftad" => true);
	$post_b = array("timeout" => 200, "body" => array("request" => $request, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
	
	// Update Ad Server
	$url ="http://api.swiftimpressions.com/poweronmanage";
	$reply_a = wp_safe_remote_post($url, $post_a);
	$response_a  = wp_remote_retrieve_body($reply_a);
	
	$reply_b = wp_safe_remote_post($url, $post_b);
	$response_b  = wp_remote_retrieve_body($reply_b);
	
	if( ($res_a = json_decode($response_a)) && ($res_b = json_decode($response_b))) {
		if(isset($res_a->error) || isset($res_b->error)) {
			$wpfn_notifications->add("Swift Post A/B Test Error 1", __('Error Changing Swift Post A/B test. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com' . $res->error),array('status' => 'error','icon' => 'thumbs-down'));
			return;
			// report error $res->error
		} else if(isset($res_a->success) && isset($res_b->success)) {
			if ($action == 'pause') {
				$test['status'] = "paused";
				$data_a['status'] = "paused";
				$data_b['status'] = "paused";
				
			} else if ($action == 'resume') {
				$test['status'] = "running";
				$data_a['status'] = "abtest";
				$data_b['status'] = "abtest";
			} 
		
			if(!($wpdb->replace( "{$wpdb->prefix}swiftpost_abtest", $test)) ||  !($wpdb->replace( "{$wpdb->prefix}swiftpost_nativepost", $data_a )) || !($wpdb->replace( "{$wpdb->prefix}swiftpost_nativepost", $data_b)) ) {
				$wpfn_notifications->add("Swift Post A/B Test DB Update Error", __( 'There was a problem updating the a/b test. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com' . $wpdb->print_error()),array('status' => 'error','icon' => 'thumbs-down'));
				
			} else {
				$wpfn_notifications->add("Swift Post A/B Test Updated", __( 'Swift Ad A/B Test Updated, status set to '.$action."d"),array('status' => 'success','icon' => 'thumbs-up'));
			}
		}
	} else {
		$wpfn_notifications->add("Swift Post A/B Test Error 2", __('There was a problem changing your A/B Test. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		// report error  $res->error
		
	}
	
	
	if ($swiftad_config['debug'] == 'on') $wpfn_notifications->add("Swift Post A/b Test Debug", "<pre>Swift Post A/b Test Manage Request Query =>\n " . var_export($request_a, true) . "\n\n" . var_export($request_b, true) . "\nResponse =>\n\n". var_export($res, true) . "\n\n" . var_export($res, true) ."</pre>",array('status' => 'debug','icon' => 'hammer'));

}




