/* 
scripts for the lazyest-widgets plugin for wordpress
copyright (c) 2011, Marcel Brinkkemper http://brimosoft.nl/lazyest/gallery/widgets/
*/

function lazyestSlideSwitch() {
	jQuery('.lazyest_random_slideshow_item').each( function(){		
		var the_id = jQuery(this).attr( 'id' );	
		var active = jQuery( '#'+the_id+' div.lg_thumb.active' );
	  var next = active.next().length ? active.next() : jQuery('#'+the_id+' div.lg_thumb:first');
		var data = {
			action: 'lg_random_slideshow',
			_wpnonce : lazyest_widgets._nonce		
		}
		jQuery.post( lazyest_widgets.ajaxurl, data, function(response) {
			next.html(response);
			active.addClass('last-active');
			next.css({opacity: 0.0})
				.addClass('active')
				.animate({opacity: 1.0}, 500, function() {
				active.removeClass('active last-active');
				active.animate({opacity: 0.0},500);
	   	});
		});
	});
}

function lazyestRecentSwitch() {
	jQuery('.lazyest_recent_slideshow_item').each( function(){		
		var the_id = jQuery(this).attr( 'id' );	
		var active = jQuery( '#'+the_id+' div.lg_thumb.active' );
	  var next = active.next().length ? active.next() : jQuery('#'+the_id+' div.lg_thumb:first');
	  var thisInstance = the_id.charAt(25);
	  var thisRecent = parseInt(jQuery('span#recent_'+thisInstance).text());	  
	  var thisLatest = parseInt(jQuery('span#latest_'+thisInstance).text());
		var data = {
			action: 'lg_recent_slideshow',
			recent: thisRecent, 
			latest: thisLatest,
			_wpnonce : lazyest_widgets._nonce		
		}
		jQuery.post( lazyest_widgets.ajaxurl, data, function(response) {
			thisRecent++;
			if ( thisRecent == thisLatest ) {
				thisRecent = 0;				
			}				
			jQuery('#recent_'+thisInstance).html(thisRecent);
			next.html(response);
			active.addClass('last-active');
			next.css({opacity: 0.0})
				.addClass('active')
				.animate({opacity: 1.0}, 500, function() {
				active.removeClass('active last-active');
				active.animate({opacity: 0.0},500);
	   	});
		});
	});
}

jQuery(document).ready( function() {
	
	if( jQuery( '.lazyest_recent').length ) {
		jQuery( '.lazyest_recent').each( function() {
			var the_id = jQuery(this).attr( 'id' );
			var data = {
				action : 'lg_recent_image',
				recent : the_id.substring(7),
				_wpnonce : lazyest_widgets._nonce
			}
			jQuery.post( lazyest_widgets.ajaxurl, data, function(response) {
				if ( '0' != response ) {
					jQuery('#'+the_id).html(response);
					jQuery('#'+the_id).show();
					if(typeof lg_js_loadFirst == 'function') {    
	  				lg_js_loadFirst();
	  			}
					if(typeof lg_js_loadNext == 'function') {    
	      			lg_js_loadNext();      	
					}		  
				}
			});	
		});
	}
	
	if( jQuery( '.lazyest_random').length ) {
		jQuery( '.lazyest_random').each( function() {
			var the_id = jQuery(this).attr( 'id' );
			var data = {
				action : 'lg_random_image',
				random : the_id.substring(7),
				_wpnonce : lazyest_widgets._nonce
			}
			jQuery.post( lazyest_widgets.ajaxurl, data, function(response) {
				if ( '0' != response ) {
					jQuery('#'+the_id).html(response);
					jQuery('#'+the_id).show();
					if(typeof lg_js_loadFirst == 'function') {    
	  				lg_js_loadFirst();
	  			}
					if(typeof lg_js_loadNext == 'function') {    
	      			lg_js_loadNext();      	
					}		  
				}
			});	
		});
	}
	
	lazyestSlideSwitch();
	lazyestRecentSwitch();
}); 

if ( jQuery('.lazyest_random_slideshow_item').length ) { 
    jQuery( function(){
	    setInterval( 'lazyestSlideSwitch()', lazyest_widgets.slideshow_duration );
    });
}

if ( jQuery('.lazyest_recent_slideshow_item').length ) { 
    jQuery( function(){
	    setInterval( 'lazyestRecentSwitch()', lazyest_widgets.slideshow_duration );
    });
}