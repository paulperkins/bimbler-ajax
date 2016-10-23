    
/*
 * Bimbler JavaScript - helper JS / JQuery for RSVP functionality.
 */

jQuery(document).ready(function ($) {
	
	  	//$('#linky').masonry({ singleMode: true });
	
	// Populate the DIV which is part of the 'Our Next Ride' front-page post.
	if (document.getElementById('bimbler-next-ride-map')) {
	
		var brisbane = new google.maps.LatLng(-27.471010, 153.023453);

		var map_div = document.getElementById('bimbler-next-ride-map');
			
	    // Get the RWGPS ID.
		//var rwgps_id = document.getElementById('bimbler-next-ride-map').getAttribute('data-rwgps-id');
		var rwgps_id = map_div.getAttribute('data-rwgps-id');
	
		//console.log ('RWGPS: ' + rwgps_id);

	    var options = {
	            zoom: 17,
	            center: brisbane,
	            //disableDefaultUI: true,
	            //draggable: false,
	            scrollwheel: false, 
	            //disableDoubleClickZoom: true
	        };
		
	    // init map
	    //var ride_map = new google.maps.Map(document.getElementById('bimbler-next-ride-map'), options);
	    var ride_map = new google.maps.Map(map_div, options);
	    
	    if (rwgps_id) {
		    var ctaLayer = new google.maps.KmlLayer({
			    url: 'http://ridewithgps.com/routes/' + rwgps_id + '.kml',
	    		    suppressInfoWindows: true,
	    		  });
		    
	    	ctaLayer.setMap(ride_map);
	    }

		var venue_address_enc = map_div.getAttribute('data-venue-address');

		if (venue_address_enc) {

			var venue_address = decodeURIComponent(venue_address_enc);

			var geocoder= new google.maps.Geocoder();
			
			geocoder.geocode( 
				{ 'address': venue_address }, 
				function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {

						var marker = new google.maps.Marker(
							{
								map: ride_map,
								animation: google.maps.Animation.DROP,
								position: results[0].geometry.location
							}
						);

						var venue_name_enc = map_div.getAttribute('data-venue-name');

						if (venue_name_enc) {

							var venue_name = decodeURIComponent(venue_name_enc);

							var contentString = '<div id="content">'+
								'<p>' + venue_name + '</p>';

							var infowindow = new google.maps.InfoWindow({
								content: contentString
								});

							infowindow.open(ride_map, marker);

							marker.addListener('click', function() {
								infowindow.open(ride_map, marker);
							});								
						}

						ride_map.setCenter(marker.getPosition());
					}
				}
			);
			
		}



/*

    window.renderVenueMap = function (address, map_div, event_id) {

		if ('undefined' === typeof google) {
			return;
		}

			var venue_address = decodeURIComponent(gmap.getAttribute('data-venue-address'));


    	var address = address || null;
    	
    	var geocoder= new google.maps.Geocoder();
    	
    	geocoder.geocode( 
    		{ 'address': address }, 
    		function(results, status) {
    			if (status == google.maps.GeocoderStatus.OK) {
    				setMap(results[0].geometry.location, map_div, event_id);
    			}
    		}
    	);
    }

*/

	}	
	
	// Detect if we're running on a mobile device, and adjust the map DIV if so to make it visible.
	if (navigator.userAgent.indexOf('iPhone') != -1 || navigator.userAgent.indexOf('Android') != -1 ) {

		var mapdiv = document.getElementById('bimbler-next-ride-map');
		
		if (mapdiv) {
			mapdiv.style.width = '100%';
			mapdiv.style.height = '100%';
		}
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

		var wait = '<div class="rsvp-checkin-indicator-wait"><i class="fa fa-spinner fa-spin"></i></div>';
		
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
        			//console.log (response);
        			//indicator.html(response);
        			
        			if ('success' == response.status) {
        				indicator.html(response.indicator);
        			}

        		}
        );
	});

});
    
