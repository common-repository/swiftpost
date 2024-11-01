<?php
/*
Plugin Name: SwiftPost
Plugin URI: https://swiftimpressions.com
Author: Swiftimpressions
Author URI: https://swiftimpressions.com
Description: SwiftPost, native advertising for Wordpress
Version: 0.5.5
License: GPLv2
*/

/* 
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

 
/*
 * Load Setup
 */
include_once(plugin_dir_path( __FILE__ ) . 'swiftpost-setup.php');

/*
 * Load config 
 */
load_plugin_textdomain('swiftpost', false, basename(dirname(__FILE__)) . '/language');
$swiftpost_config	= get_option('swiftpost_config');
$swiftpost_license	= get_option('swiftpost_license');
$swiftpost_version	= get_option("swiftpost_version");

/*
 * Core Hooks/Actions
 *
 */
register_activation_hook(__FILE__, 'swiftpost_activate');
register_deactivation_hook(__FILE__, 'swiftpost_deactivate');
register_uninstall_hook(__FILE__, 'swiftpost_uninstall');
add_action('swiftpost_tasks_daily', 'swiftpost_exec_daily');

/*
 * Daily cleanup tasks
 * 
 *  
 */ 
function swiftpost_exec_daily() {
	global $wpdb;
	$auto_expire = $wpdb->query("UPDATE " . $wpdb->prefix . "swiftpost_nativepost SET `status` = \"expired\" WHERE `enddate` < CURDATE()");
	$auto_expire = $wpdb->query("UPDATE " . $wpdb->prefix . "swiftpost_abtest SET `status` = \"completed\" WHERE `enddate` < CURDATE()");

}
/* Get Post List for new test */
function siwftpost_testpost_list() {
	  global $wpdb;
	  $postids = $wpdb->get_col("SELECT postid FROM {$wpdb->prefix}swiftpost_nativepost ORDER BY startdate DESC"); 
	  $post_ids_query = implode (", ", $postids);
	  $posts = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->prefix}posts WHERE ID IN ($post_ids_query)", OBJECT);
	  $swift_title_array = array();
	  $title_list ="";
	  foreach ($posts as $post) {
	  	$id = $post->ID;
	  	$name = $post->post_title;
	  	$title_list .=  "{\"label\": \"$name -> $id\", \"value\": \"$id::: $name\"},";
	  	$swift_title_array[$id] = $name;
	  }
	  ?>
	<script type="text/javascript">
	    jQuery(function() {
         	var swiftposts_titles = <?php echo json_encode($swift_title_array); ?>;
         	var post_ids = [<?php echo $post_ids_query;?>];
         	 jQuery(document).ready(function() {
	         	jQuery("#swiftpost_atest").autocomplete({
	         		source: [<?php echo $title_list; ?>],
	         		select: function( event, ui ) {
	         			jQuery("#swiftpost_btest").val('');
						jQuery("#swiftpost_btest").prop( "disabled", true );
					},
					change: function(event,ui) { 
						if (ui.item==null) {
							jQuery("#swiftpost_atest").val('');
							jQuery("#swiftpost_atest").focus();
							jQuery("#swiftpost_btest").val('');
							jQuery("#swiftpost_btest").prop( "disabled", true );
						} else { 
							var p1 = ui.item.value.split(":::");
							jQuery("#abtest_p1").val(p1[0]);
							jQuery("#abtest_p2").val("");
							jQuery("#swiftpost_btest").val('');
							jQuery("#swiftpost_btest").prop( "disabled", false );
							var blist = [<?php echo $title_list; ?>];
							var blist_clean = blist.filter(function (el) {return el.value !== ui.item.value;});
						    jQuery("#swiftpost_btest").autocomplete({
				         		source: blist_clean,
				         		select:  function( event, ui ) {},
				         		change: function(event,ui) { if (ui.item==null) {jQuery("#swiftpost_btest").val('');jQuery("#swiftpost_btest").focus();}
				         			else { var p2 = ui.item.value.split(":::");jQuery("#abtest_p2").val(p2[0]);}
				         		}
				         	});
						
						
						}
					}
	         	});
	         	
		    });
		    
		 });
	</script>
	<?php
}

// Register Custom Status
function custom_post_status() {

	$args = array(
		'label'                     => _x( 'Swift Post', 'Status General Name', 'text_domain' ),
		'label_count'               => _n_noop( 'Swift Post (%s)',  'Swift Posts (%s)', 'text_domain' ), 
		'public'                    => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'exclude_from_search'       => true,
	);
	register_post_status( 'swiftpost', $args );

}
add_action( 'init', 'custom_post_status', 0 );

if(!is_admin()) {
	include_once(plugin_dir_path( __FILE__ ) . 'swiftpost-functions.php');
	/* Swift Post Inject */
	add_action('wp_footer', 'swiftpost_native_inject');
	add_action( 'wp_enqueue_scripts', 'swiftpost_scripts');
	if (isset($swiftpost_config['autoinsert']) && $swiftpost_config['autoinsert'] == "yes") add_action( 'loop_start' , 'swiftimpressoins_autoinsert' );
	/*
	 *  Slot Fill
	 */
	if ($swiftpost_config["injectoption"] == "autoinsert") {
		/* Try to insert div in correct location */
		add_action( 'loop_start' , 'swiftpost_autoinsert' );
		add_shortcode('swiftpost', 'swiftpost_shortcode_ai');
	} else {
		/* Short Code for all slots */
		add_shortcode('swiftpost', 'swiftpost_shortcode');
	}
	
	
	
}


/*
 * Dashboard
 */
if(is_admin()) {
	global $wpfn_notifications;
	include_once( plugin_dir_path( __FILE__ ) . 'admin/swiftpost_admin_functions.php');
	include_once(plugin_dir_path( __FILE__ ) . 'admin/wpfn-notifications.php');
	swiftpost_check_config();
	add_action('save_post', 'swiftpost_save_meta_box_data');
	add_action('transition_post_status','swift_post_transition',10,3);
	add_action('admin_menu', 'swiftpost_dashboard');
	add_action('admin_enqueue_scripts', 'swiftpost_admin_scripts' );
	add_action('wp_logout', 'swiftEndSession');
	add_action('wp_login', 'swiftStartSession');
	add_action('admin_notices', 'swiftpost_admin_notices');
	if (isset($_GET['action']) && $_GET['action'] == 'new_test') {
	  /* Get Post List */
	  add_action('admin_head', 'siwftpost_testpost_list');
	}
 
	/*--- Internal redirects ------------------------------------*/
	if(isset($_POST['swiftpost_register_license']))  add_action('init', 'swiftpost_license_apply');
	if(isset($_POST['swiftpost_release_license']))  add_action('init', 'swiftpost_license_release');

}

/*
 * Swift Impressions Dashboard Menus & pages
 *
 */
function swiftpost_dashboard() {
	add_menu_page('Swift Post', 'Swift Post', 'swiftpost_pp_manage', 'swiftpost', "","", '25.7');
	add_submenu_page('swiftpost', 'Swift Post > '.__('Dashboard', 'swiftpost'), __('Dashboard', 'swiftpost'), 'swiftpost_pp_manage', 'swiftpost', 'swiftpost_info');
	add_submenu_page('swiftpost', 'Swift Post > '.__('Reports', 'swiftpost'), __('Reports', 'swiftpost'), 'swiftpost_pp_manage', 'swiftpost-nativepost', 'swiftpost_nativepost');	
	add_submenu_page('swiftpost', 'Swift Post > '.__('Split Testing', 'swiftpost'),  __('Split Testing', 'swiftpost'), 'swiftpost_pp_manage', 'swiftpost-abtest', 'swiftpost_abtest');
	add_submenu_page('swiftpost', 'Swift Post > '.__('Plugin Setup', 'swiftpost'), __('Plugin Setup', 'swiftpost'), 'swiftpost_pp_manage', 'swiftpost-settings', 'swiftpost_settings');

}



/*
 * Swift Impressions Info Page
 *
 */
function swiftpost_info() {
	
	global $wpdb, $current_user, $userdata, $swiftpost_config, $swiftpost_debug, $wpfn_notifications;
	$swiftpost_config	= get_option('swiftpost_config');
	$swiftpost_license	= get_option('swiftpost_license');
	
	if ((!isset($swiftpost_license['server_key']) || (isset($swiftpost_license['server_key']) && $swiftpost_license['server_key'] == "")) && isset($_POST['swift-accept-terms']) ) swiftpost_license_activate();

	?>
	<div class="swift-admin-wrap">
	  <div class="swift-admin-logo-bar" >
	  	 <div class="swift-admin-logo" ></div>
		<div class="swift-admin-title-buttons" >
		 <a class="swift-btn-rainbow" href="<?php echo site_url( "/wp-admin/admin.php?page=swiftpost-settings") ?>" >Settings</a>
		 <a class="swift-btn-rainbow" href="https://swiftimpressions.com/useful-resources/">Help</a>
		</div>
	  </div>
		
		<?php 
		 
		$status = $file = $view = $ad_edit_id = '';
		$license = get_option('swiftpost_license');
		
		if (!isset($license['level'])) {
			$license['level'] = "free";
			update_option('swiftpost_license', $license);
		}
		$plan_count = array('free' => 10000, '1333' => 100000, '1334' => 300000, '1335' => "unlimited");
	    $plan_level = array('free' => "Free Plan", '1333' => "Independent Operators", '1334' => "High Volume Niche", '1335' => "Industry Leader/Promoter");
		
		
		
		?>
		<div class="swift-admin-box">
			<div class="swift-admin-box-title-bar rs-status-red-wrap">
				<div class="swift-admin-box-title">General Information</div>
				
				
				<div class="clear"></div>
			</div>
			
			<div class="swift-admin-box-inner ">
				<h2 class="swift-box-title">Upgrading License</h2>
				<ol>
					<li>Go to <a href="http://SwiftImpressions.com" target="_blank">Swiftimpressions.com</a> and register for an account.</li>
					<li>Once registered and logged in, go to the Account page where you can find your license key and the updgade links.</li>
					<li>Choose to upgrade and then select one of the levels to purchase that subscription.</li>
					<li>Return to this setup page, release your current license if you've activated and enter your new license key in the activation box.</li>
					

				</li></ol>
				<h2 class="swift-box-title">Setup</h2>
				<ul>
				<li>Setup steps can be found on the on the Plugin Setup page of this plugin, (<a href="<?php echo site_url( "/wp-admin/admin.php?page=swiftpost-settings"); ?>">click here</a>)
				</ul>
				<h2 class="swift-box-title">Customer Support Information</h2>
				<p>Please call us at (208) 473-7119 any time, day or night. We are more likely to answer between the hours of 9am and 5pm MST… Just an FYI.</p>

				<p>You can also email us at njones@swiftimpression.com if you are a millennial or don't like talking on the phone. We usually get back to you in under 30 minutes.</p>
				
				<p>Here are some helpful walkthroughs:</p>
				<ul>
					<li><a href="https://swiftimpressions.com/walkthrough/"><span style="font-weight: 400;">Swift Post Installation Guide</span></a></li>
					<li><a href="https://swiftimpressions.com/swift-post-widget-guide/"><span style="font-weight: 400;">Swift Post Widget Explainer</span></a></li>
				</ul>				
			</div>
		</div>
		
		
		<div class="swift-admin-box">
			<div class="swift-admin-box-title-bar rs-status-red-wrap">
				<div class="swift-admin-box-title">Plugin Activation</div>
				
				<?php if (isset($license['server_key']) && $license['server_key'] != ""): ?>
					<div class="swift-admin-green btn">Plugin Activated</div>
				<?php else: ?>
					<div class="swift-admin-red btn">Not Activated</div>
				<?php endif; ?>
				<div class="clear"></div>
			</div>
			
			<div class="swift-admin-box-inner ">
				<?php if (isset($license['server_key']) && $license['server_key'] != ""): ?>
				
				<table>
					<tr><td valign="top"><label>License key:</label></td><td> <?php echo $license['license_key']; ?> </td></tr>
					<tr><td valign="top"><label>Subscription: </label> </td><td><?php echo $plan_level[$license['level']] ; ?> </td></tr>
					<tr><td valign="top"><label>Monthly impressions:</label> </td><td><?php echo $plan_count[$license['level']] . " impressions can be booked to start each month."; ?> </td></tr>
					<tr><td valign="top"><label>Registered by:</label> </td><td><?php echo $license['reg_user']; ?> </td></tr>
					<tr><td valign="top"><label>To release your license: </label> </td><td>Copy and paste the license key above into the box below and click submit. Once released ads will quit serving and you can register the plugin on another website.</td></tr>
				</table>
					<form name="unregister_license" id="post" method="post">
				    <input name="license_key" type=text />
				    <input type=submit class="swift-btn-rainbow"  name="swiftpost_release_license" value="Release" />
				    </form>
					</p>
				<?php else: ?>
				 	<p>
				    <h4>Press activate below to get 10K free impressions per month:</h4>
				    <a href="#" onclick="javascript: swiftpostactivatefree(); return false;" class="swift-btn-rainbow">Activate</a>
				    </p>
				     <p></p><br />
				    <h4>Or, enter a valid premium License Key from <a href="//swiftimpressions.com" >SwiftImpressions.com</a> to register the plugin</h4>
				    <form name="register_license" id="post" method="post">
				    
				    <input name="license_key" type=text />
				    <input type=submit class="swift-btn-rainbow" name="swiftpost_register_license" value="Register" />
				    
				    </form>
				   
				    <i>*When registering your plugin, we will store your username, site url and the site email associated with your license on our licensing server in order to confirm your identity.</i>
				<?php endif; ?>
			</div>
		</div>
		
		<?php
			/* Check Bookings */
			$request = array("impressions" => 0, "startdate" => date("Y-m-d H:i:s"));
			$post = array("timeout" => 200, "body" => array("request" => $request, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
			// Update Ad Server
			$url ="http://api.swiftimpressions.com/licensecount";
			$reply = wp_safe_remote_post($url, $post);
			$response  = wp_remote_retrieve_body($reply);
			
			if($res = json_decode($response)) {
			
				$current_count = $res->monthlycount->count;
				if ($res->licenselevel == 1335) {
					$available = "unlimited";
					$mark = "<div class=\"swift-admin-green btn\">&#10003;</div>";
				} else {
					$available = $plan_count[$res->licenselevel]-$current_count;
					if ($available > 0) {
						$mark = "<div class=\"swift-admin-green btn\">&#10003;</div>";
					} else {
						$mark = "<div class=\"swift-admin-red btn\">&#x274C;</div>";
					}
				}
		?>
		
		<div class="swift-admin-box">
			<div class="swift-admin-box-title-bar rs-status-red-wrap">
				<div class="swift-admin-box-title">Current Bookings</div>
				<?php echo $mark ?>
				<div class="clear"></div>
			</div>
			
			<div class="swift-admin-box-inner ">
				
				<p>
				<table>
					<tr><td valign="top"><label>For this month:</label> </td><td><?php echo $res->monthlycount->month . ", " . $res->monthlycount->year; ?> </td></tr>
					<tr><td valign="top"><label>Subscription: </label> </td><td><?php echo $plan_level[$license['level']] ; ?> </td></tr>
					<tr><td valign="top"><label>Booked this month:</label> </td><td><?php echo $current_count;  ?> Impressions</td></tr>
					<tr><td valign="top"><label>Available to Book:</label> </td><td><?php echo $available; ?> Impressions</td></tr>
				</table>
				</p>
			</div>
		</div>	
		<?php } ?>

		<br class="clear" />

	</div>
<?php
}


/*
 * Swift Impressions Metrics/Reports Page
 */
function swiftpost_nativepost() {
	global $wpdb, $swiftpost_config, $wpfn_notifications;
	$license = get_option('swiftpost_license');
	$active_tab = (isset($_GET['tab'])) ? esc_attr($_GET['tab']) : 'active';

	if(isset($_POST['postid']) && isset($_POST['action']) ) {
		
		// Check if our nonce is set.
		if ( ! isset( $_POST['swiftpost_post_page_nonce'] ) || ! wp_verify_nonce( $_POST['swiftpost_post_page_nonce'], 'swiftpost_post_page' ) ) {
			
		} else {
			$result = swiftpost_postmanage( $_POST['action'], $_POST['postid'],  $_POST['run']); 			
			
		}
	}
	?>
	<div class="swift-admin-wrap">
	  <div class="swift-admin-logo-bar" >
	  	 <div class="swift-admin-logo" ></div>
		<div class="swift-admin-title-buttons" >
		 <a class="swift-btn-rainbow" href="<?php echo site_url("/wp-admin/admin.php?page=swiftpost-settings"); ?>">Settings</a>
		 <a class="swift-btn-rainbow" target="_blank" href="https://swiftimpressions.com/useful-resources/">Help</a>
		</div>
	  </div>
		
	  <h2 class="nav-tab-wrapper">  
          <a href="?page=swiftpost-nativepost&tab=active" class="nav-tab <?php echo $active_tab == 'active' ? 'nav-tab-active' : ''; ?>">Active Posts</a>  
          <a href="?page=swiftpost-nativepost&tab=paused" class="nav-tab <?php echo $active_tab == 'paused' ? 'nav-tab-active' : ''; ?>">Paused Posts</a>  
          <a href="?page=swiftpost-nativepost&tab=expired" class="nav-tab <?php echo $active_tab == 'expired' ? 'nav-tab-active' : ''; ?>">Expired Posts</a>
      </h2>
	
	<div class="swift-admin-box-tabs">
		
	    <div class="swift-admin-box-inner ">
	
	
	<?php
	if($active_tab == 'active') {
		$filter = "WHERE status = \"live\"";
		$action = "pause";
		$icon = "dashicons-controls-pause";
	} else if ($active_tab == 'paused' ) {
		$filter = "WHERE status = \"paused\"";
		$action = "resume";
		$icon = "dashicons-controls-play";
	} else if ($active_tab == 'expired' ) {
		$filter = "WHERE status = \"expired\"";
		$action = "new run";
		$icon = "dashicons-format-gallery";
	}
	
	
	$offset = 5;
	$paged = isset($_GET['paged']) ? $_GET['paged'] : "0";
	
	$postids = $wpdb->get_col("SELECT postid FROM {$wpdb->prefix}swiftpost_nativepost " .$filter ." ORDER BY startdate DESC LIMIT $offset OFFSET $paged");
	
	if ( $postids ) {
		
		$data = array(
			"postids" => $postids
			);
		$post = array("timeout" => 200, "body" => array("request" => $data, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
	
		// Get Post Stats
		$url ="http://api.swiftimpressions.com/poststats";
		$reply 	   = wp_safe_remote_post($url, $post);
		$response  = json_decode(wp_remote_retrieve_body($reply));
		
		if ($swiftpost_config['debug'] == 'on') $wpfn_notifications->add("Swift Post Debug", "<pre>Stats Query =>\n\n\ " . var_export($post, true) .  "\n\nreply->\n\n" . var_export($reply,true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
		do_action('wpfn_notifications');

		$args['post_type'] = 'post';
		$args['post__in'] = $postids;
		$args['posts_per_page'] = 5;
		$args['orderby'] = "post__in";
		$args['ignore_sticky_posts'] = true;
		$get_sposts  = new WP_Query($args);
		
		if ( $get_sposts->have_posts() ) {
			
			echo "<form name=\"post-manage\" id=\"post-manage\" method=\"post\" action=\"admin.php?page=swiftpost-nativepost\">\n<input type=\"hidden\" name=\"postid\" id=\"post-postid\" value=\"\" />\n<input type=\"hidden\" name=\"action\" id=\"post-action\" value=\"$action\" />\n<input type=\"hidden\" name=\"run\" id=\"post-run\" value=\"\" />";
			wp_nonce_field( 'swiftpost_post_page', 'swiftpost_post_page_nonce' );
			
			echo "<div id= \"swift-admin-post-wrqp\" class=\"swift-rainbow-box\">";
			
			while ( $get_sposts->have_posts() ) {
				$get_sposts->the_post();
				$id = get_the_ID();
				
				echo "<div class=\"swift-post-report-titlebar\"><div class=\"swift-post-report-title\">" . the_title( '<span>', '</span>' , FALSE) . " </div>";
				echo "<div class=\"swift-post-report-buttons\">";
				echo "<a href=\"" .site_url( "/wp-admin/post.php?post=" . $id . "&amp;action=edit" ) . "\" title=\"Edit this item\"class=\"swift-btn-rainbow swift-tooltip swift-icon-button\" ><span class=\"dashicons dashicons-edit\"></span></a>";
				echo "<a href=\"" .site_url( $swiftpost_config['preview_path'] . '?swift_preview='. $id ) . "\" target=\"_blank\" class=\"swift-btn-rainbow swift-tooltip swift-icon-button\" title=\"Preview Post\"><span class=\"dashicons dashicons-welcome-view-site\"></span></a>\n";
				echo "<button class=\"swift-tooltip swift-icon-button swift-btn-rainbow post-manage\" title=\"$action post\" type=\"button\" value=\"$id\" run=\"$run\" id=\"submit-$id\"><span class=\"dashicons $icon\"></span></button>";
				echo "</div></div>\n";
				echo "<div class=\"swift-admin-table-wrap\">\n";
				echo "<table class=\"table swift-stat-table\" cellspacing=\"0\"><tr><th>Run #</th><th>Dates</th><th>Impressions</th><th>Clicks</th><th>CTR (%)</th></tr>";
				foreach ($response->$id as $run => $stats) {
					if (!isset($stats->stats) ) $stats->stats = new stdClass();
					if (!isset($stats->stats->impressionsDelivered)) $stats->stats->impressionsDelivered = 0;
					if (!isset($stats->stats->clicksDelivered)) $stats->stats->clicksDelivered = 0;
					echo "<tr><td>Run ". $run." </td>\n";
					echo "<td>" . $stats->start->date->month . " / " . $stats->start->date->day . " / " . $stats->start->date->year . " - "; 
					echo $stats->end->date->month . " / " . $stats->end->date->day . " / " . $stats->end->date->year . "</td>\n";
					echo "<td>" . $stats->stats->impressionsDelivered . "</td>\n";
					echo "<td>" . $stats->stats->clicksDelivered . "</td>\n";
					echo "<td>". ($stats->stats->impressionsDelivered > 0 ? round(($stats->stats->clicksDelivered/$stats->stats->impressionsDelivered)*100, 2) : "0"). "</td></tr>\n";
				}
				echo "</table></div>\n";

			}
			echo "</form>";

		} else {
			echo "<!--no posts-->";
		}
		
		
		$page_url = "?page=swiftpost-nativepost&tab=$active_tab&paged=";
		
		$prev = ($paged == 0 ? "" : "<a href=\"$page_url".($paged-$offset)."\">".__( '&laquo; Back', 'swiftpost' )."</a>");
		$next = (count($postids) == $offset ? "<a href=\"$page_url".($paged+$offset)."\">".__( 'Next &raquo;', 'swiftpost' )."</a>" : "");
		
		?>
		<div class="page-nav nav-previous alignleft"><?php echo $prev; ?></div>
		<div class="page-nav nav-next alignright"><?php echo $next; ?></div>
		<?php
		echo "</div>\n";

	} else {
		echo "<h4><i>There are no Swift Posts currently $active_tab</i></h4>";
	}
	echo "</div></div>\n";

}

/*
 * Swift Impressions A/B Testing
 */
function swiftpost_abtest() {

	global $wpdb, $swiftpost_config, $wpfn_notifications;
	$license = get_option('swiftpost_license');
	$active_tab = (isset($_GET['tab'])) ? esc_attr($_GET['tab']) : 'running';
    $url = admin_url("admin.php?page=swiftpost-abtest");

	?>
		<div class="swift-admin-wrap">
		  <div class="swift-admin-logo-bar" >
		  	 <div class="swift-admin-logo" ></div>
			<div class="swift-admin-title-buttons" >
			 <a type="button" class="swift-btn-rainbow" href="<?php echo $url;?>&action=new_test">New Test</a>
			 <a class="swift-btn-rainbow" href="<?php echo site_url( "/wp-admin/admin.php?page=swiftpost-settings"); ?>">Settings</a>
			 <a class="swift-btn-rainbow" href="https://swiftimpressions.com/useful-resources/" target="_blank" >Help</a>
			</div>
		  </div>
	<?php



	if (isset($_GET['action']) && $_GET['action'] == "new_test") {
		swiftpost_abtest_form();
		return;
	} else if (isset($_POST['submit']) && $_POST['submit'] == "Start Test") {
		$result = swiftpost_abtest_add();
		
	} else if(isset($_POST['abtestid']) && isset($_POST['action']) ) {
		
		// Check if our nonce is set.
		if ( ! isset( $_POST['swiftpost_post_page_nonce'] ) || ! wp_verify_nonce( $_POST['swiftpost_post_page_nonce'], 'swiftpost_post_page' ) ) {
			
		} else {
			$result = swiftpost_abtest_manage( $_POST['action'], $_POST['abtestid']); 			
		}
	}
	
	do_action('wpsa_notifications');

		
		?>
		
		
		  <h2 class="nav-tab-wrapper">  
	          <a href="<?php echo $url;?>&tab=running" class="nav-tab <?php echo $active_tab == 'running' ? 'nav-tab-active' : ''; ?>">Active </a>  
	          <a href="<?php echo $url;?>&tab=paused" class="nav-tab <?php echo $active_tab == 'paused' ? 'nav-tab-active' : ''; ?>">Paused </a>  
	          <a href="<?php echo $url;?>&tab=completed" class="nav-tab <?php echo $active_tab == 'completed' ? 'nav-tab-active' : ''; ?>">Completed </a>
	      </h2>
	
		<div class="swift-admin-box-tabs">
			<div class="swift-admin-box-title-bar rs-status-red-wrap">
				<div class="swift-admin-box-title"></div>				
				<div class="clear"></div>
			</div>
		    <div class="swift-admin-box-inner ">
		<?php
		if($active_tab == 'running') {
			$filter = "WHERE status = \"running\"";
			$action = "pause";
			$icon = "dashicons-controls-pause";
		} else if ($active_tab == 'paused' ) {
			$filter = "WHERE status = \"paused\"";
			$action = "resume";
			$icon = "dashicons-controls-play";
		} else if ($active_tab == 'completed' ) {
			$filter = "WHERE status = \"completed\"";
			$action = "new test";
		}
		
		$offset = 5;
		$paged = isset($_GET['paged']) ? $_GET['paged'] : "0";
		
		$tests = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}swiftpost_abtest " .$filter ." ORDER BY startdate DESC", ARRAY_A);
		
		$postids = array();
		foreach ($tests as $row) {
			$postids[] = $row['postid_a'];
			$postids[] = $row['postid_b'];
		}
	
		
		if (!empty($postids) ) {
			
			$data = array(
				"postids" => $postids
				);
			$post = array("timeout" => 200, "body" => array("request" => $data, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
		
			// Get Post Stats
			$statsurl ="http://api.swiftimpressions.com/poststats";
			$reply 	   = wp_safe_remote_post($statsurl, $post);
			$response  = json_decode(wp_remote_retrieve_body($reply));
			
			if ($swiftpost_config['debug'] == 'on') {
				$wpfn_notifications->add("Swift Post Debug", "<pre>A/B TEST Query =>\n\n\ " . var_export($tests, true) .  "\n\nreply->\n\n" . var_export($reply,true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
				do_action('wpfn_notifications');
			}
			
			echo "<div id=\"swiftpost-admin-block\" >";
		    
		    if (!isset($response->error)) {
				
		
				wp_nonce_field( 'swiftpost_post_page', 'swiftpost_post_page_nonce' );
				
				foreach ( $tests as $row) {
					
					if (!($stats_a = $response->$row['postid_a']->$row['run_a']->stats)) {
					    $stats_a = new stdClass();
						$stats_a->impressionsDelivered = 0;
						$stats_a->clicksDelivered = 0;
					}
					if (!($stats_b = $response->$row['postid_b']->$row['run_b']->stats)) {
					    $stats_b = new stdClass();
						$stats_b->impressionsDelivered = 0;
						$stats_b->clicksDelivered = 0;
					}
					
					$ctr_a = ($stats_a->impressionsDelivered == 0) ? 0 : round(($stats_a->clicksDelivered/$stats_a->impressionsDelivered), 2);
					$ctr_b = ($stats_b->impressionsDelivered == 0) ? 0 : round(($stats_b->clicksDelivered/$stats_b->impressionsDelivered), 2);
					$a_result = "";
					$b_result = "";
					$id = $row['id'];
					if ($ctr_a > $ctr_b) {
						$a_result = "<span class=\"dashicons dashicons-yes\" style=\"color: green;\"></span> ". round((($ctr_a - $ctr_b)/ $ctr_b) * 100, 2) . "% increase";
					} else if ($ctr_b > $ctr_a) {
						$b_result = "<span class=\"dashicons dashicons-yes\" style=\"color: green;\"></span> ". round((($ctr_b - $ctr_a)/ $ctr_a) * 100, 2) . "% increase";
					}
					?>
					
					
					<div class="swiftpost-ab-test-item">
						<div class="swiftpost-abtest-info">
							<h3><?php echo $row['name']; ?></h3>
							<div><h3>Runs: <i><?php echo $row['startdate'] . " " . $row['starttime']; ?> </i> through <i><?php echo $row['enddate'] . " " . $row['endtime']; ?> </i></h3></div>
	
						</div>
						<div class="swiftpost-abtest-buttons">
							<form name="abtest-postform-<?php echo $row['id']; ?>" id="abtest-postform-<?php echo $row['id']; ?>" method="post" >
							<input type="hidden" name="abtestid"  value="<?php echo $row['id']; ?>" />
							<input type="hidden" name="action" value="<?php echo $action; ?>" />
					 		<button type="submit" value="<?php echo $id; ?>" id="submit-<?php echo $id; ?>"><span class="dashicons <?php echo $icon; ?>"></span></button>
					 		</form>
						</div>
					
						<table class="table swift-stat-table" cellspacing="0">
						  <tbody>
						  	<tr><th>Test - Title</th><th>Impressions</th><th>Clicks</th><th>CTR</th><th></th></tr>
							<tr><td>A Test - <?php echo get_the_title($row['postid_a']); ?></td><td><?php echo $stats_a->impressionsDelivered ?><td><?php echo $stats_a->clicksDelivered; ?></td><td><?php echo $ctr_a; ?></td><td><?php echo $a_result; ?></td></tr>
							<tr><td>B Test - <?php echo get_the_title($row['postid_b']); ?> </td><td><?php echo $stats_b->impressionsDelivered ?></td><td><?php echo $stats_b->clicksDelivered; ?></td><td><?php echo $ctr_b; ?></td><td><?php echo $b_result; ?></td></tr>
						  </tbody>
						</table>
					
					</div>
					
					<?php
					
				}					
				echo "</div>\n";
			}
			
			$page_url = "?page=swiftpost_abtestt&tab=$active_tab&paged=";
			
			$prev = ($paged == 0 ? "" : "<a href=\"$page_url".($paged-$offset)."\">".__( '&laquo; Back', 'swiftpost' )."</a>");
			$next = (count($postids) == $offset ? "<a href=\"$page_url".($paged+$offset)."\">".__( 'Next &raquo;', 'swiftpost' )."</a>" : "");
			echo "<div class=\"page-nav nav-previous alignleft\">$prev</div>";
			echo "<div class=\"page-nav nav-next alignright\">$next</div>";
	
		} else {
				echo "<!--no tests-->";
				echo "<h4><i>There are no Swift Posts A/B Tests currently $active_tab</i></h4>";
		}
		echo "</div>\n";
	
	echo "</div>\n";
} 
function swiftpost_settings() {
	
	global $wpdb, $wp_roles;
	$swiftpost_config = get_option('swiftpost_config');
	$swiftpost_license = get_option('swiftpost_license');
	$X = false;
	if( ($current_theme = get_option('current_theme')) && ($current_theme == "X &ndash; Child Theme" || $current_theme == "X") ) $X = true;
    
    if (isset($_POST['swiftpost_settings_apply'])) {
    	global $wpfn_notifications;
    	if ( isset( $_POST['swiftpost_settings_page_nonce'] ) && wp_verify_nonce( $_POST['swiftpost_settings_page_nonce'], 'swiftpost_settings_page' )) {
   			$swiftpost_config['swiftpost-template'] = sanitize_text_field($_POST['swiftpost-template']);
   			if ($swiftpost_config['swiftpost-template'] == 'templatecode' && $_POST['swiftpost-custom-template-code'] == "" ) {
   				$wpfn_notifications->add("Settings Form Error", __('Template code field must be filled in if you choose custom temnplate' ),array('status' => 'error','icon' => 'thumbs-down'));
   			} else if ($swiftpost_config['swiftpost-template'] == 'templatecode') {
   				file_put_contents(plugin_dir_path( __FILE__ ) . "template/swiftpost-custom.php", stripslashes($_POST['swiftpost-custom-template-code']) );
   				$swiftpost_config['swiftpost-template'] = "templatecode";
   			} else if ($swiftpost_config['swiftpost-template'] == 'templatename' && $_POST['swiftpost-template-name'] == "" ) {
   				$wpfn_notifications->add("Settings Form Error", __('Template name field must be filled in if you choose temnplate name' ),array('status' => 'error','icon' => 'thumbs-down'));
   			} else if ($swiftpost_config['swiftpost-template'] == 'templatename' ) {
   				$swiftpost_config['swiftpost-template-name'] = $_POST['swiftpost-template-name'];
   			}
    		if (isset($_POST['swiftpost-debug'])) {
    			$swiftpost_config['debug'] = "on";
    		} else {
    			$swiftpost_config['debug'] = "off";
    		}
    		$swiftpost_config['injectoption'] = $_POST['injectoption'];
    		$swiftpost_config['preview_path'] = $_POST['swiftpost-preview-path'];
			update_option('swiftpost_config', $swiftpost_config);
			$wpfn_notifications->add("Settings Applied", __('Swift Impressions Settings Updated' ),array('status' => 'success','icon' => 'thumbs-up'));
		} else {
			$wpfn_notifications->add("Settings Form Error", __('Invalid Swift Impressions Form' ),array('status' => 'error','icon' => 'thumbs-down'));
			
		}
    	do_action('wpfn_notifications');
    }
	
	$template_code = "";
	if ($swiftpost_config['swiftpost-template'] == 'templatecode' ) {
		$template_code = file_get_contents(plugin_dir_path( __FILE__ ) . "template/swiftpost-custom.php");
	}
	
	?>

	<div class="swift-admin-wrap">
	  <div class="swift-admin-logo-bar" >
	  	 <div class="swift-admin-logo" ></div>
		<div class="swift-admin-title-buttons" >
		 <a class="swift-btn-rainbow" href="<?php echo site_url( "/wp-admin/admin.php?page=swiftpost-settings"); ?>">Settings</a>
		 <a class="swift-btn-rainbow" href="https://swiftimpressions.com/useful-resources/" target="_blank" >Help</a>
		</div>
	  </div>

		<?php if(isset($status) && $status > 0) swiftpost_status($status, array('error' => $error)); ?>
	

	  	<form name="settings" id="post" method="post" action="admin.php?page=swiftpost-settings">
	  		<?php wp_nonce_field( 'swiftpost_settings_page', 'swiftpost_settings_page_nonce' ); ?>
			
			
			
		<div class="swift-admin-box-fw">
			<div class="swift-admin-box-title-bar rs-status-red-wrap">
				<div class="swift-admin-box-title">Native Ad Location</div>				
				<div class="clear"></div>
			</div>
			
			<div class="swift-admin-box-inner ">
				<p>
				The default location for each Swift Post is right at the top of your feed. 99% of the time the auto-insert option will work perfectly. If you’re part of the coveted 1%, however, don’t worry! We have you covered and we will help you set it up manually. Just give us a call at 208-473-7119.
				</p>
			</div>
		</div>
			
		<div class="swift-admin-box-fw">
			<div class="swift-admin-box-title-bar rs-status-red-wrap">
				<div class="swift-admin-box-title">Insertion Options</div>				
				<div class="clear"></div>
			</div>
			
			<div class="swift-admin-box-inner ">
				<h2 class="swift-box-title">Please Choose One of the Following Options:</h2>
				<p>
					<label><input type="radio" name="injectoption" <?php echo $swiftpost_config['injectoption'] == "autoinsert" ? "checked" : "" ; ?> value="autoinsert" /> Auto-insert Shortcode at Loop Start</label><br />
					<i>The plugin will attempt to auto-insert the necessary shortcode at the beginning of the loop on the front page and/or the posts page only, as defined in the Wordpress reading settings.</i>
				</p>
				<p>
					<label><input type="radio" name="injectoption" <?php echo $swiftpost_config['injectoption'] == "placephp" ? "checked" : "" ; ?> value="placephp" /> Manually Insert Shortcode </label></br>
					Place this php code in the desired location on your WordPress template: <p><code>&lt;?php echo do_shortcode('[swiftpost slotid="<?php echo  $swiftpost_license["parent_code"]; ?>"]'); ?&gt; </code></p> Or, simply use the shortcode:<p> <code> [swiftpost slotid="<?php echo  $swiftpost_license["parent_code"]; ?>"] </code></p>somewhere somewhere in the page content on any page or location you would like the post to run. <br/> 

				</p>
				
				<p>
				<label>Path to Posts page:
					<input name="swiftpost-preview-path" value="<?php echo $swiftpost_config['preview_path']; ?>" type=text size=120></label><br />
					<i>If the path to your posts page is not the front page you can enter that here, used for previewing swifdt posts before they are active.</i>
				</p>
				
				<input type="submit" name="swiftpost_settings_apply" class="swift-btn-rainbow" value="<?php _e('Update Settings', 'swiftpost'); ?>" />
			</div>
		</div>
			
		<div class="swift-admin-box-fw">
			<div class="swift-admin-box-title-bar rs-status-red-wrap">
				<div class="swift-admin-box-title">Formating</div>				
				<div class="clear"></div>
			</div>
			
			<div class="swift-admin-box-inner ">
				<h2 class="swift-box-title">Using Swift Post on Your Site</h2>
				
				
				<?php if ($X) {?>
						<p>The X theme is currently active, please choose the correct stack:</p>
						<p>
						<label><input type=radio name="swiftpost-template" value="swiftpost-integrity.php" onchange="jQuery('#swiftpost-custom-template-code').hide();"  <?php echo $swiftpost_config['swiftpost-template'] == "swiftpost-integrity.php" ? "checked" : "" ; ?>>X Theme Integrity </label><br />
						<label><input type=radio name="swiftpost-template" value="swiftpost-renew.php" onchange="jQuery('#swiftpost-custom-template-code').hide();"<?php echo $swiftpost_config['swiftpost-template'] == "swiftpost-renew.php" ? "checked" : "" ; ?>> X Theme Renew </label> <br />
						<label><input type=radio name="swiftpost-template" value="swiftpost-icon.php" onchange="jQuery('#swiftpost-custom-template-code').hide();"<?php echo $swiftpost_config['swiftpost-template'] == "swiftpost-icon.php" ? "checked" : "" ; ?>>X Theme Icon </label><br />
						<label><input type=radio name="swiftpost-template" value="swiftpost-ethos.php" onchange="jQuery('#swiftpost-custom-template-code').hide();"<?php echo $swiftpost_config['swiftpost-template'] == "swiftpost-ethos.php" ? "checked" : "" ; ?>>X Theme Ethos </label>
						</p>
					<?php } else { ?>
						<p>Please pick one of the following options: 1) a Standard Swift Post Theme, or 2) a Custom Content Option.</p>
						<p>

						<label><input type=radio name="swiftpost-template" value="swiftpost-full.php" onchange="jQuery('#swiftpost-custom-template-code').hide();"  <?php echo $swiftpost_config['swiftpost-template'] == "swiftpost-full.php" ? "checked" : "" ; ?>>Image on top, full content below </label><br />
						<label><input type=radio name="swiftpost-template" value="swiftpost-teaser.php" onchange="jQuery('#swiftpost-custom-template-code').hide();"<?php echo $swiftpost_config['swiftpost-template'] == "swiftpost-teaser.php" ? "checked" : "" ; ?>> Image on top, content excerpt below</label> <br />
						<label><input type=radio name="swiftpost-template" value="swiftpost-teaser-sideimg.php" onchange="jQuery('#swiftpost-custom-template-code').hide();"<?php echo $swiftpost_config['swiftpost-template'] == "swiftpost-teaser-sideimg.php" ? "checked" : "" ; ?>> Image on left, content expert on the side</label>
						</p>
					<?php } ?>
				<p> 
					<label><input type=radio name="swiftpost-template" value="templatename" onchange="jQuery('#swiftpost-custom-template-code').hide();" <?php echo $swiftpost_config['swiftpost-template'] == "templatename" ? "checked" : "" ; ?>>
					 Post Template Name:</label>
					<input name="swiftpost-template-name" value="<?php echo $swiftpost_config['swiftpost-template-name']; ?>" type=text size=120><br />
					<i>For help finding the post template name please see the step-by-step found <a href="https://swiftimpressions.com/walkthrough/" target="_blank">here</a>.</i>

				</p>
				
				<p>
					<label><input name="swiftpost-template" value="templatecode" type="radio" onchange="jQuery('#swiftpost-custom-template-code').show();" <?php echo ($swiftpost_config['swiftpost-template'] == "templatecode" ? "checked" : ""); ?> >
					Use Custom Setup Code (Feel free to call us and we will help if your theme requires this)
</label>  <br />
					<div style="display: <?php echo ($swiftpost_config['swiftpost-template'] == "templatecode" ? "block" : "none"); ?>;" id="swiftpost-custom-template-code">
						<textarea name="swiftpost-custom-template-code" style="margin: 0px; width: 663px; height: 264px;"><?php echo $template_code; ?></textarea>
					</div>
				</p>
				<input type="submit" name="swiftpost_settings_apply" class="swift-btn-rainbow" value="<?php _e('Update Settings', 'swiftpost'); ?>" />

			</div>
		</div>
		
		<div class="swift-admin-box-fw">
			<div class="swift-admin-box-title-bar rs-status-red-wrap">
				<div class="swift-admin-box-title">Debug Settings</div>				
				<div class="clear"></div>
			</div>
			
			<div class="swift-admin-box-inner ">
				<h2 class="swift-box-title">Enable Debugging</h2>
				<p><label><input name="swiftpost-debug" <?php echo ($swiftpost_config['debug'] == "on" ? "checked" : ""); ?> type="checkbox" size=120> Turn on debugbing </label></p>
				<input type="submit" name="swiftpost_settings_apply" class="swift-btn-rainbow" value="<?php _e('Update Settings', 'swiftpost'); ?>" />
			</div>
		</div>
			
		</form>
	</div>
<?php 
}
