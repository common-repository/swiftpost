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
 * Inject SwiftPost into page footer
 *
 *
 */
function swiftpost_native_inject() {
	
	global $wpdb, $swiftpost_slotfills;
	$swiftpost_config = get_option('swiftpost_config');
	
	if (isset($_GET['swift_preview']) && isset( $swiftpost_slotfills) ) {
		$postid = $_GET['swift_preview'];
		$postids = array(0 => $postid );
		$previews = "";
		//create each slot
		foreach ($swiftpost_slotfills as $slotid) {
			$previews .= "\nfillPowerPost(" . $postid . ", " . $slotid  . ");\n";
		}
		
		$args['nopaging'] = true;
		$args['post_type'] = 'post';
		$args['post_status'] = array('publish','private');;
		$args['post__in'] = $postids;
		wp_reset_query();
		$query = new WP_Query($args);
		echo "<div id=\"swiftpost-powerpost-block\" style=\"display: none;\">";
		while (  $query->have_posts() ) {
			$query->the_post();
			echo "<div id=\"swiftpost-" . get_the_ID() . "\" style=\"display: none;\">";
			echo "<div id=\"swiftpost-preview-header\" style=\"padding: 8px 20px;margin: 10px 20px;background: rgba(240,240,240, 1);border-radius: 5px;border: 1px solid #ccc;\"><img style=\"float: left;\" src=\"". plugins_url('/images/small-logo.png', __FILE__) ."\"><div style=\"float: right;padding: 6px 0;color: #ccc;\"> Preview</div><div style=\"clear: both\"><!--clear--></div></div>\n";
			 
			 if ($swiftpost_config['swiftpost-template'] == 'templatecode') {
			 	 include(plugin_dir_path( __FILE__ ) . "template/swiftpost-custom.php");
			 } else if ($swiftpost_config['swiftpost-template'] == 'templatename'){
				 if(!get_post_format()) {
		               get_template_part($swiftpost_config['swiftpost-template-name'], 'standard');
		          } else {
		               get_template_part($swiftpost_config['swiftpost-template-name'], get_post_format());
		          }
			 } else {
			 	 include(plugin_dir_path( __FILE__ ) . "template/".$swiftpost_config['swiftpost-template']);
			 }
			echo "\n</div>\n";
		}	
		echo "\n</div>\n";
		 
		echo "<script type='text/javascript'>jQuery(document).ready(function() {" . $previews . "});\n\nfunction fillPowerPost(postid,slotid) {jQuery(\"#div-swiftpost-\" + slotid).replaceWith(jQuery(\"#swiftpost-powerpost-block #swiftpost-\"+postid).html());}</script>";	
		
			
		
	} else if (isset($swiftpost_slotfills)) {
	
		$swiftposts = $wpdb->get_results("SELECT postid, slotid FROM {$wpdb->prefix}swiftpost_nativepost where status = \"live\" || status = \"abtest\"");
		$postids = array();
		$dfp1 = "";
		$dfp2 = "";
		$divs = "";
		
		foreach ( $swiftposts as $swiftpost ) {
			$postids[] = $swiftpost->postid;
		}
		/* Get shortcode slot fills & create each slot */
		foreach ($swiftpost_slotfills as $slotid) {
			$dfp1 .= "googletag.defineSlot(\"/72045342/".$slotid."\", [1, 1], \"div-swift-" . $slotid . "\").addService(googletag.pubads());";
			$dfp2 .= "googletag.cmd.push(function() { googletag.display(\"div-swift-" . $slotid . "\"); });";
			$divs .= "\n<div sytle=\"display: none;\" id=\"div-swift-". $slotid ."\"></div>\n";
		}
	
		if ( !empty($postids) ) {
			$swiftclass = (current_user_can('administrator') ?  "swiftpost swift-post-fill" : "swiftpost");
			$args['nopaging'] = true;
			$args['post_type'] = 'post';
			$args['post_status'] = array('publish','private');
			$args['post__in'] = $postids;
			wp_reset_query();
			$query = new WP_Query($args);
			if ( $query->have_posts() ) {
				echo "<div id=\"swiftpost-powerpost-block\" style=\"display: none;\">";
				while (  $query->have_posts() ) {
					$query->the_post();
					echo "<div id=\"swiftpost-" .get_the_ID(). "\" class=\"" . $swiftclass . "\">";
					 if ($swiftpost_config['swiftpost-template'] == 'templatecode') {
					 	 include(plugin_dir_path( __FILE__ ) . "template/swiftpost-custom.php");
					 } else if ($swiftpost_config['swiftpost-template'] == 'templatename') {
						 if(!get_post_format()) {
				               get_template_part($swiftpost_config['swiftpost-template-name'], 'standard');
				          } else {
				               get_template_part($swiftpost_config['swiftpost-template-name'], get_post_format());
				          }
					 } else {
					 	 include(plugin_dir_path( __FILE__ ) . "template/".$swiftpost_config['swiftpost-template']);
					 }
					echo "\n</div>\n";
				}
			} else {
				echo "<!--siwftimpressions posts not found-->";
			}
			echo "\n</div>\n";
			wp_reset_query();
			echo $divs;
			
			echo "<script type='text/javascript'>var googletag = googletag || {};googletag.cmd = googletag.cmd || [];(function() {var gads = document.createElement('script');gads.async = true;gads.type = 'text/javascript';var useSSL = 'https:' == document.location.protocol;gads.src = (useSSL ? 'https:' : 'http:') +'//www.googletagservices.com/tag/js/gpt.js';var node = document.getElementsByTagName('script')[0];node.parentNode.insertBefore(gads, node);})(); \n\ngoogletag.cmd.push(function() {" . $dfp1 ."googletag.enableServices();});\n\njQuery(document).ready(function() {".$dfp2."});\n\nfunction fillPowerPost(postid, slotid, clickurl) {\n\njQuery.extend({getQueryParameters : function(str) {return (str || document.location.search).replace(/(^\?)/,'').split(\"&\").map(function(n){return n = n.split(\"=\"),this[n[0]] = n[1],this}.bind({}))[0];}}); \n\nvar GET = jQuery.getQueryParameters();\nif ( typeof GET.swiftdebug != \"undefined\" ) {alert(\"Filled Post p-\" + postid );}\n\njQuery(\"#swiftpost-powerpost-block #swiftpost-\"+postid+\" a\").attr(\"href\",function(i,v) { return clickurl + v;});\n\njQuery(\"#div-swiftpost-\" + slotid).replaceWith(jQuery(\"#swiftpost-powerpost-block #swiftpost-\"+postid)[0].outerHTML);\n\n}</script>";	
		} else {
			echo "<!--siwftimpressions no posts-->";
		}
		unset($swiftpost_config['slotfills']);
	}	
}



/*
 * Handle auto insert
 *
 */
function swiftpost_autoinsert( $query ) {
	
    if ( $query->is_home() && $query->is_main_query() && !$query->is_paged() && !$query->is_single()) {
    	$license = get_option('swiftpost_license');
		global $swiftpost_slotfills;
		echo "<div id=\"div-swiftpost-" . $license['parent_code']  . "\" style=\"display:none;\"><!--Native Swift Post--></div>";
		$swiftpost_slotfills[] = $license['parent_code'];
    }

} 

/*
 * Handle all shortcodes
 *
 */

function swiftpost_shortcode($atts, $content = null) {
    global $swiftpost_slotfills;

	if (empty($swiftpost_slotfills)) $swiftpost_slotfills = array();

	$output = "<!--Swift Post-->\n";
	if(!empty($atts['slotid'])) {
		$output .= "<div id=\"div-swiftpost-{$atts['slotid']}\" sytle=\"display:none;\"></div>";
		$swiftpost_slotfills[] = $atts['slotid'];
	}
	return $output;
}
function swiftpost_shortcode_ai($atts, $content = null) {
	 if ($query->is_front_page()) {
	 	$output = " ";
	 } else {
		global $swiftpost_slotfills;
	
		if (empty($swiftpost_slotfills)) $swiftpost_slotfills = array();
	
		$output = "<!--Swift Post-->\n";
		if(!empty($atts['slotid'])) {
			$output .= "<div id=\"div-swiftpost-{$atts['slotid']}\" sytle=\"display:none;\"></div>";
			$swiftpost_slotfills[] = $atts['slotid'];
		}
	 }
	 return $output;
	 
}

function swiftpost_scripts() {
	wp_enqueue_script( "SwiftPostBrowser", plugins_url( '/js/swiftpost.js' , __FILE__),array('jquery'), 1.01, true);
	wp_enqueue_style( 'swiftpost-stylesheet', plugins_url( '/css/swiftpost.css', __FILE__ ) );
}
