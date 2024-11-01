/** Swift Imopressions Power Post Admin **/

jQuery(document).ready(function() {

    jQuery('.datepicker').datepicker({
        dateFormat : 'yy-mm-dd'
    });
    
    jQuery('.timepicker:not([disabled], [readonly])').timepicker({ useSelect: true , step: 15});
    
    initiateGeoTarget();
    
    jQuery('#swiftpost_gt_button').click(function() {
    	jQuery("#swiftpost_gt_results").html('<img src="/wp-includes/images/spinner.gif" />');
		jQuery.ajax({
			url: "http://api.swiftimpressions.com/geoquery_get",
			jsonp: "processGeoResults",
			dataType: "jsonp",
			data: {
		        type: jQuery("#swiftpost_gt_type").val(),
		        name: jQuery("#swiftpost_gt_search").val()
		    }
		});    	
    });
    
     jQuery('#swift-admin-post-wrqp button.post-manage').click(function(e) {
    	   	jQuery("#post-manage #post-postid").val(jQuery(this).attr("value"));
    	   	jQuery("#post-manage #post-run").val(jQuery(this).attr("run"));
    	   	jQuery("#post-manage").submit();
    });
     jQuery('#abtest-postform button').click(function(e) {
    	   	jQuery("#abtest-postform #abtestid").val(jQuery(this).attr("value"));
    	   	jQuery("#abtest-postform").submit();
    });
    jQuery('#swiftpost-newline').change(function() {
        if (jQuery(this).is(':checked')) {
           jQuery("#swiftpost_pp_pasued").prop( "checked", false );
           jQuery("#pp-start-time").attr("readonly", false);
           jQuery("#pp-start-date").attr("readonly", false);
           jQuery("#pp-start-time").timepicker({ useSelect: true , step: 15});
           jQuery("#pp-start-date").datepicker({dateFormat : 'yy-mm-dd'});
        } else {
        	
        }
    });
    
    
    
});

function processGeoResults(results) {
	jQuery("#swiftpost_gt_results").html("<!--Results-->");
	if (typeof results.error !== 'undefined') {
		jQuery("#swiftpost_gt_results").html(results.error);
	} else {
		jQuery.each(results, function( index, geoObject ) {
			var data_o = {};
		   data_o[index] = geoObject;
		   jQuery("#swiftpost_gt_results").append(geoObject.name +  " - " + geoObject.Parent +  " - " +geoObject.countrycode + " - " + geoObject.type + " <a data_t='" + geoObject.type + "' data_c='" + geoObject.countrycode + "' data_n='" + geoObject.name + "' data_i='" + index + "'data_o='" + JSON.stringify(data_o) + "' class='geo-results-target' style='cursor: pointer;' onclick='addGeoTarget(this);return false;'>add</a><br />");
		});
	}

}

function addGeoTarget(link) {
	
	jQuery("#swiftpost_gt_selections").append("<a data_i='" + jQuery(link).attr('data_i') + "'  onclick='removeGeoTarget(this);return false;' class='swiftpost-geo-target-link'>" + jQuery(link).attr('data_n') + " - " + jQuery(link).attr('data_c')  + " - " + jQuery(link).attr('data_t') + " <span class='xremove'>x</span></a>");
	
	if (jQuery("#swiftpost-geotargets").length) {
		var old_val = jQuery.parseJSON(jQuery("#swiftpost-geotargets").val());
		var new_val = jQuery.parseJSON(jQuery(link).attr('data_o'));
		old_val = jQuery.extend(old_val, new_val);
		jQuery("#swiftpost-geotargets").val(JSON.stringify(old_val));
		
	} else {
		var form = jQuery(link).closest('form');
	    input = jQuery("<input>").attr("type", "hidden")
                             .attr("name", "swiftpost-geotargets")
                             .attr("id", "swiftpost-geotargets")
                             .val(jQuery(link).attr('data_o'));
    	jQuery(form).append(jQuery(input));
	}
}

function initiateGeoTarget() {
	
	var dirty = jQuery("#swiftpost-geotargets").val();
	
	if (dirty !== null && dirty !== undefined && dirty.length) {
		dirty.replace('\\','');
		var targets = jQuery.parseJSON(dirty);
	
		jQuery.each(targets, function( index, value ) {
			jQuery("#swiftpost_gt_selections").append("<a data_i='" + index + "'  onclick='removeGeoTarget(this);return false;' class='swiftpost-geo-target-link'>" + value.name + " - " + value.Parent  + " - " + value.type + " - " +   value.countrycode + " <span class='xremove'>x</span></a>");
		});
	}
	
}



function removeGeoTarget(link) {
	var old_val = jQuery.parseJSON(jQuery("#swiftpost-geotargets").val());
	var index = jQuery(link).attr('data_i');
	delete old_val[index];
	if(jQuery.isEmptyObject(old_val)) {
		jQuery("#swiftpost-geotargets").val("");
	} else {
		jQuery("#swiftpost-geotargets").val(JSON.stringify(old_val));
	}
	jQuery(link).remove();
}


function swiftpostactivatefree() {
	//display licnense with click through

	if (!jQuery("#swiftpost-popup").length) {
      jQuery("body").append("<div id=\"swiftpost-form-overlay\" class=\"swiftpost-form-overlay js-form-close\"></div>");
      jQuery("body").append("<div id=\"swiftpost-popup\" class=\"swiftpost-form-box\"><div class=\"swiftpost-form-body\"></div><div class=\"swiftpost-form-footer\"><form id=\"activate-agree-terms\" method=post name=\"activate-agree-terms\" ><input type=\"checkbox\" name=\"swift-accept-terms\"  value=\"acepted terms checked\"> I have read, understand, and agree to the terms<br><button type=\"submit\" name=\"agree\" class=\"swift-btn-rainbow\" style=\"color: #fff;\" value=\"I agree\">I agree</button></form> </div></div>\n");
	}
	jQuery("#swiftpost-popup > .swiftpost-form-body").load(swiftposdt_locals.license_url);
	
    jQuery(".swiftpost-form-overlay").fadeTo(500, 0.4);
	jQuery("#swiftpost-popup").fadeIn(500);
	jQuery(".js-form-close, .swiftpost-form-overlay").click(function() {
	    jQuery("#swiftpost-popup, .swiftpost-form-overlay").fadeOut(500, function() {
	        jQuery("#swiftpost-form-overlay").remove();
	        jQuery("#swiftpost-popup").remove();
	    });
	});
	jQuery(window).resize(function() {
		if (jQuery("#swiftpost-popup").length) {
			var topmargin = (jQuery(window).height() - jQuery("#swiftpost-popup").outerHeight()) / 2;
		    jQuery("#swiftpost-popup").css({
		        top: topmargin,
		        left: (jQuery(window).width() - jQuery("#swiftpost-popup").outerWidth()) / 2
		    });
		}
	});
	jQuery(window).resize(); 
}




