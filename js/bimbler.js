    
/*
 * Bimbler JavaScript - helper JS / JQuery for RSVP functionality.
 */

jQuery(document).ready(function ($) {
	
	  	//$('#linky').masonry({ singleMode: true });
	
	// Populate the DIV which is part of the 'Our Next Ride' front-page post.
	if (document.getElementById('bimbler-next-ride-map')) {
	
		var brisbane = new google.maps.LatLng(-27.471010, 153.023453);
	
	    // Get the RWGPS ID.
		var rwgps_id = document.getElementById('bimbler-next-ride-map').getAttribute('data-rwgps-id');
	
		console.log ('RWGPS: ' + rwgps_id);

	    var options = {
	            zoom: 13,
	            center: brisbane,
	            disableDefaultUI: true,
	            draggable: false,
	            scrollwheel: false, 
	            disableDoubleClickZoom: true
	        };
		
	    // init map
	    var ride_map = new google.maps.Map(document.getElementById('bimbler-next-ride-map'), options);

	    if (rwgps_id) {
		    var ctaLayer = new google.maps.KmlLayer({
			    url: 'http://ridewithgps.com/routes/' + rwgps_id + '.kml',
	    		    suppressInfoWindows: true,
	    		  });
		    
	    	ctaLayer.setMap(ride_map);
	    }
	}	
	
	// Detect if we're running on a mobile device, and adjust the map DIV if so to make it visible.
	if (navigator.userAgent.indexOf('iPhone') != -1 || navigator.userAgent.indexOf('Android') != -1 ) {

		var mapdiv = document.getElementById('bimbler-next-ride-map');

		mapdiv.style.width = '100%';
		mapdiv.style.height = '100%';
	}
	
	

	// Block UI Helper
	function blockUI(el)
	{
		el.block({
			message: '',
			css: {
				border: 'none',
				padding: '0px',
				backgroundColor: 'none'
			},
			overlayCSS: {
				backgroundColor: '#fff',
				opacity: .3,
				cursor: 'wait'
			}
		});
	}

	function unblockUI(el)
	{
		el.unblock();
	}

	
		
		$('.rsvp-checkin-container').click (function () {
		
			var rsvp_id = $(this).attr('id');

			//alert ('Clicked ' + person_clicked);
					
			//var debug_out = $("#bimbler-rsvp-debug");
			
			//debug_out.html ('<p>You clicked ' + rsvp_id + '</p>');
			
			//var indicators = ['<div class="rsvp-checkin-indicator-none"><i class="fa-question-circle"></i></div>',
			//                  '<div class="rsvp-checkin-indicator-yes"><i class="fa-check-circle"></i></div>',
			//                  '<div class="rsvp-checkin-indicator-no"><i class="fa-times-circle"></i></div>'];
			
			var wait = '<div class="rsvp-checkin-indicator-wait"><i class="fa fa-spinner fa-spin"></i></div>';
			
			//var pick = Math.floor(Math.random()*(2-0+1)+0);
			var indicator = $("#rsvp-checkin-indicator-" + rsvp_id);
			
			// Set the indicator to an animation.
			indicator.html (wait);

            $.post(
            		RSVPAjax.ajaxurl,
            		{
            			action: 	'checkinajax-submit', 
            			container: 	rsvp_id
            		},
            		function (response) {
            			console.log (response);
            			indicator.html(response);
            		}
            );
			//alert ('Called Ajax?');
		});
		
		$('#rsvp-submit-yes').click (function () {
			
			var rsvp_id = $(this).attr('id');
			
			var rsvp_list = document.getElementById('bimbler-event-rsvps');
			
			var form_contents = $("#rsvp-ajax-form").serialize();

			console.log ('Sending:' + form_contents);
			
			// Set the indicator to an animation.
			//indicator.html (wait); 
			
			//blockUI(rsvp_list);

            $.post(
            		RSVPAjax.ajaxurl,
            		{
            			action: 'rsvpajax-submit',
            			rsvp:   'Y',
            			input:  form_contents 
            		}
            )
            .done (function (response) {
    			//console.log (response);
    			
    			rsvp_list.innerHTML = response;
    			
    			console.log ('AJAX completed.');
            	
            })
            .fail (function (response) {
            	// Log the error message.
    			document.getElementById('bimbler-ajax-error-message').innerHTML = '<pre>' + response.responseText + '</pre>'; 
    			console.log ('AJAX completed with errors.');
            })
		});
		
		$('#rsvp-submit-no').click (function () {
			
			var rsvp_id = $(this).attr('id');
			
			var rsvp_list = document.getElementById('bimbler-event-rsvps');
			
			var form_contents = $("#rsvp-ajax-form").serialize();

			console.log ('Sending:' + form_contents);
			
			// Set the indicator to an animation.
			//indicator.html (wait); 
			
			//blockUI(rsvp_list);

            $.post(
            		RSVPAjax.ajaxurl,
            		{
            			action: 'rsvpajax-submit',
            			rsvp:   'N',
            			input:  form_contents 
            		}
            )
            .done (function (response) {
    			//console.log (response);
    			
    			rsvp_list.innerHTML = response;

    			console.log ('AJAX completed.');
            })
            .fail (function (response) {
            	// Log the error message.
    			document.getElementById('bimbler-ajax-error-message').innerHTML = '<pre>' + response.responseText + '</pre>'; 
    			console.log ('AJAX completed with errors.');
            })

			//unblockUI(rsvp_list);

		});
		
	
	
/*	    // This function will be executed when the user scrolls the page.
		$(window).scroll(function(e) {
		    // Get the position of the location where the scroller starts.
		    var scroller_anchor = $(".bimbler_scroll_anchor").offset().top;
		     
		    // Check if the user has scrolled and the current position is after the scroller start location and if its not already fixed at the top
		    if ($(this).scrollTop() >= scroller_anchor && $('.scroller').css('position') != 'fixed')
		    {    // Change the CSS of the scroller to hilight it and fix it at the top of the screen.
		        $('.alx-tabs-container').css({
		            //'background': '#CCC',
		            //'border': '1px solid #000',
		            'position': 'fixed',
		            'top': '0px'
		        });
		        // Changing the height of the scroller anchor to that of scroller so that there is no change in the overall height of the page.
		        $('.bimbler_scroll_anchor').css('height', '50px');
		    }
		    else if ($(this).scrollTop() < scroller_anchor && $('.alx-tabs-container').css('position') != 'relative')
		    {    // If the user has scrolled back to the location above the scroller anchor place it back into the content.
		         
		        // Change the height of the scroller anchor to 0 and now we will be adding the scroller back to the content.
		        $('.bimbler_scroll_anchor').css('height', '0px');
		         
		        // Change the CSS and put it back to its original position.
		        $('.alx-tabs-container').css({
		            //'background': '#FFF',
		            //'border': '1px solid #CCC',
		            'position': 'relative'
		        });
		    }
		}); */
});
    