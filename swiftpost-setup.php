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

/*
 * Activate Swift Posts
 *
 */
function swiftpost_activate($network_wide) {
	if(is_multisite() && $network_wide) {
		global $wpdb;
		$current_blog = $wpdb->blogid;
		$activated = array();
		$blog_ids = $wpdb->get_col("SELECT `blog_id` FROM $wpdb->blogs;");
		foreach($blog_ids as $blog_id) {
			switch_to_blog($blog_id);
			swiftpost_db_config_setup();
			$activated[] = $blog_id;
			
		}
		switch_to_blog($current_blog);
		return;
	}
	swiftpost_db_config_setup();
}

/*
 * Setup Swift Posts on Activate
 *
 */

function swiftpost_db_config_setup() {
	global $wpdb, $userdata;
	if(version_compare(PHP_VERSION, '5.3.0', '<') == -1) { 
		deactivate_plugins(plugin_basename('swiftpost/swiftpost.php'));
		wp_die('Swift Post requires PHP 5.3 or higher. Your server reports version '.PHP_VERSION.'. Contact your hosting provider about upgrading your server!<br /><a href="'. get_option('siteurl').'/wp-admin/plugins.php">Return to dashboard</a>.'); 
		return; 
	} else {
		if(!current_user_can('activate_plugins')) {
			deactivate_plugins(plugin_basename('swiftpost/swiftpost.php'));
			wp_die('You do not have appropriate access to activate this plugin! Contact your administrator!<br /><a href="'. get_option('siteurl').'/wp-admin/plugins.php">Back to dashboard</a>.'); 
			return; 
		} else {
			// Set the capabilities for the administrator
			$role = get_role('administrator');		
			$role->add_cap("swiftpost_pp_manage");
			
			/* Setup Options */
			swiftpost_check_config();

	
			/* Install new database */
			swiftpost_database_install();
			
			/* Set Task Schedule */
			wp_schedule_event(time(), 'twicedaily', 'swiftpost_tasks_daily');

		}
	}
}




/*
 * Deactivate Swift Posts
 *
 */
function swiftpost_deactivate($network_wide) {
    swiftpost_network_propagate('swiftpost_deactivate_setup', $network_wide);
}


function swiftpost_deactivate_setup() {
	global $wpdb;

	// Clean up capabilities from ALL users
    $role = get_role('administrator');		
	$role->remove_cap("swiftpost_pp_manage");
	
	/* Clear Tasks */
	wp_clear_scheduled_hook('swiftpost_tasks_daily');
	
	/* Pause Posts */
	$wpdb->update($wpdb->prefix."swiftpost_nativepost", array('status' => 'paused'), array('status' => 'live'), array( '%s'), array( '%s') );
	
	/*Pause Orders*/
	
	$license = get_option('swiftpost_license');
	if (isset($license['license_key']) && strlen($license['license_key']) == 16) {
		$request = array("action" => "pauseall");
		$post = array("timeout" => 200, "body" => array("request" => $request, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
		// Update Ad Server
		$url ="http://api.swiftimpressions.com/pauseallposts";
		$reply = wp_safe_remote_post($url, $post);
		$response  = wp_remote_retrieve_body($reply);
	}
	


}

/*
 * Deactivate Swift Posts
 *
 */
function swiftpost_network_propagate($pfunction, $network_wide) {
    global $wpdb;

    if(is_multisite() && $network_wide) {
        $current_blog = $wpdb->blogid;
        // Get all blog ids
        $blogids = $wpdb->get_col("SELECT `blog_id` FROM $wpdb->blogs;");
        foreach ($blogids as $blog_id) {
            switch_to_blog($blog_id);
            call_user_func($pfunction, $network_wide);
        }
        switch_to_blog($current_blog);
        return;
    }
    call_user_func($pfunction, $network_wide);
}


/*
 * Swift Posts Network Uninstall
 *
 */
function swiftpost_uninstall($network_wide) {
    swiftpost_network_propagate('swiftpost_uninstall_setup', $network_wide);
}

/*
 * Swift Impressions Uninstall
 *
 */
function swiftpost_uninstall_setup() {
	global $wpdb, $wp_roles;

	// Clean up roles and scheduled tasks
	swiftpost_deactivate_setup();

	// Drop MySQL Tables
	$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}swiftpost_nativepost`");
	// Delete Options	
	delete_option('swiftpost_config');
	delete_option('swiftpost_db_version');
	delete_option('swiftpost_version');
	if ( !function_exists('swiftad_info') ) {
		delete_option('swiftpost_license');
	} 

}



/*
 * Swift Impressions check config has been created or exists
 *
 */
function swiftpost_check_config() {
	$config = get_option('swiftpost_config');
	$license = get_option('swiftpost_license');
	if($config === false || !is_array($config) || !isset($config['injectoption']) ) {
		$config = array('swiftpost-template' => 'swiftpost-full.php','swiftpost-template-name' => "swiftpost-teaser.php", 'debug' => 'off', 'swiftpost-custom-template' => '','injectoption' => 'autoinsert', 'preview_path' => '/');
		update_option('swiftpost_config', $config);
	}
	if(!isset($config['preview_path'])) {
		$config['preview_path'] = "/";
		update_option('swiftpost_config', $config);
	}
	
	if($license === false || !is_array($license) || !isset($license['server_key']) ) update_option('swiftpost_license', array('status' => 'unregistered', 'server_key' => null, 'license_key' => null, "reg_user" => "", "reg_date" => "", "level" => "free", "parent_code" => 'no-code'));
	
}



/*
 * Swift Impressions install DB table
 *
 */
function swiftpost_database_install() {
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	// Initial data
	$charset_collate = $engine = '';

	if(!empty($wpdb->charset)) {
		$charset_collate .= " DEFAULT CHARACTER SET {$wpdb->charset}";
	} 
	if($wpdb->has_cap('collation') AND !empty($wpdb->collate)) {
		$charset_collate .= " COLLATE {$wpdb->collate}";
	}

	$found_engine = $wpdb->get_var("SELECT ENGINE FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = '".DB_NAME."' AND `TABLE_NAME` = '{$wpdb->prefix}posts';");
	if(strtolower($found_engine) == 'innodb') {
		$engine = ' ENGINE=InnoDB';
	}

	$found_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}swiftpost%';");


		dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}swiftpost_nativepost (
				  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				  postid bigint(20) unsigned NOT NULL,
				  status varchar(15)  NOT NULL DEFAULT 'live',
				  run int(11) NOT NULL DEFAULT '1',
				  impressions int(11) NOT NULL DEFAULT '0',
				  order_id int(15) NOT NULL DEFAULT '0',
				  lineitem_id int(15) NOT NULL DEFAULT '0',
				  slotid varchar(64)  NOT NULL,
				  startdate date NOT NULL DEFAULT '0000-00-00',
				  starttime varchar(12) NOT NULL DEFAULT '12:00am',
				  enddate date NOT NULL DEFAULT '0000-00-00',
				  endtime varchar(12) NOT NULL DEFAULT '11:45pm',
				  fc varchar(6)  NOT NULL,
				  fc_impressions varchar(8)  NOT NULL,
				  fc_howmany varchar(8)  NOT NULL,
				  fc_type varchar(16)  NOT NULL,
				  fc_lifetime varchar(32)  NOT NULL,
				  gd varchar(6)  NOT NULL,
				  geo text  NOT NULL,
				  post_status varchar(20)  NOT NULL DEFAULT 'off',
				  created datetime NOT NULL,
				  timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				  PRIMARY KEY  (id),
				  UNIQUE KEY postid (postid),
				  KEY startdate (startdate),
				  KEY enddate (enddate),
				  KEY status  (status)
				) ".$charset_collate.$engine.";");

		dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}swiftpost_abtest (
				  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				  name varchar(64)  NOT NULL DEFAULT 'A/B Test',
				  hypothesis text,
				  postid_a bigint(20) unsigned NOT NULL,
				  postid_b bigint(20) unsigned NOT NULL,
				  run_a int(4) NOT NULL DEFAULT '1',
				  run_b int(4) NOT NULL DEFAULT '1',
				  startdate date NOT NULL DEFAULT '0000-00-00',
				  starttime varchar(12) NOT NULL DEFAULT '12:00am',
				  enddate date NOT NULL DEFAULT '0000-00-00',
				  endtime varchar(12) NOT NULL DEFAULT '11:45pm',
				  status varchar(15)  NOT NULL DEFAULT 'running',
				  created datetime NOT NULL,
				  timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				  PRIMARY KEY  (id),
				  KEY startdate (startdate),
				  KEY enddate (enddate),
				  KEY status  (status)
				) ".$charset_collate.$engine.";");

}
