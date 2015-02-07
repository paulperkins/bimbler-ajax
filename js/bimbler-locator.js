
jQuery(document).ready(function ($) {
	
	var user_id = 0;
	var event_id = 0;
	var rwgps_id = 0;
	var nonce = '';
	
	// Counter to select colour for each marker. 
	var marker_count = 0;
	
	// Marker colour values. Reserve red for the ride leader and this user.
	var marker_colours = ['blue', 
	                      //'red', 
	                      'purple', 
	                      'yellow', 
	                      'green', 
	                      //'gray', 
	                      'orange', 
	                      //'white', 
	                      //'black', 
	                      'blue',
	                      'pink',
	                      'lightblue',
	                      //'brown'
	                      ];
	
	var person_markers = [];
	var me_marker;
	
	var my_position;
	
	var brisbane = new google.maps.LatLng(-27.471010, 153.023453);
	var initialLocation = brisbane;
	
	// Run once - create map and store global vars.
	if (document.getElementById('bimbler_locator_map_canvas')) {
		
		var markers = [];
	
	    // Get the event ID and user ID from the DIV metadata.
		event_id = document.getElementById('bimbler_locator_map_canvas').getAttribute('data-event-id');
		user_id = document.getElementById('bimbler_locator_map_canvas').getAttribute('data-user-id');

		rwgps_id = document.getElementById('bimbler_locator_map_canvas').getAttribute('data-rwgps-id');

		nonce = document.getElementById('bimbler_locator_map_canvas').getAttribute('data-nonce');
	
		console.log ('Event: ' + event_id + ', user ID ' + user_id);

		var options = {
	            zoom: 17,
	            center: initialLocation,
	            mapTypeId: google.maps.MapTypeId.ROADMAP,
	            //mapTypeControl: true
	        };
		
	    // Create the map.
	    var map = new google.maps.Map(document.getElementById('bimbler_locator_map_canvas'), options);
		
         
/*	    // Add the start point to the map.
         var marker = new google.maps.Marker({
             position: initialLocation,
             draggable: true,
             animation: google.maps.Animation.DROP,
             map: map,
             title: 'The start point',
	         icon: new google.maps.MarkerImage("http://google.com/mapfiles/kml/paddle/go.png")
         });
         
         var contentString = '<div id="content">'+
         '<div id="siteNotice">'+
         '</div>'+
         '<h1 id="firstHeading" class="firstHeading">' + 'Start Point' + '</h1>'+
         '<div id="bodyContent">'+
         //'<p>X: ' + position.coords.longitude + ', Y: ' +  position.coords.latitude + '</p>' +
         '</div>'+
         '</div>';

	     var infowindow = new google.maps.InfoWindow({
	         content: contentString
	     });

	     google.maps.event.addListener(marker, 'click', function() {
	    	 infowindow.open(map,marker);
	     }); */
     
	    
	    // Add the cycle layer to the map.
		var bikeLayer = new google.maps.BicyclingLayer();
	
		bikeLayer.setMap(map);
		  
		// And load the route map if it exists.
		if (rwgps_id) {
	 		var ctaLayer = new google.maps.KmlLayer({
	    		  	url: 'http://ridewithgps.com/routes/' + rwgps_id + '.kml'
	    		    //url: 'http://ridewithgps.com/routes/6463068.kml'
	    		  });
	    		  ctaLayer.setMap(map);
		}

		/*
		 *  Iterate over data.
			+---------------------+--------------+------+-----+-------------------+----------------+
			| Field               | Type         | Null | Key | Default           | Extra          |
			+---------------------+--------------+------+-----+-------------------+----------------+
			| id                  | mediumint(9) | NO   | PRI | NULL              | auto_increment |
			| time                | timestamp    | NO   |     | CURRENT_TIMESTAMP |                |
			| event               | bigint(20)   | NO   |     | NULL              |                |
			| user_id             | varchar(60)  | NO   |     | NULL              |                |
			| rsvp                | char(1)      | NO   |     | NULL              |                |
			| comment             | varchar(128) | YES  |     | NULL              |                |
			| attended            | char(1)      | YES  |     | NULL              |                |
			| no_show             | char(1)      | YES  |     | NULL              |                |
			| email_notifications | char(1)      | NO   |     | Y                 |                |
			| guests              | int(11)      | NO   |     | 0                 |                |
			+---------------------+--------------+------+-----+-------------------+----------------+
		 */
    	// Executed on a timer - refresh the markers for each user.  
		function update_markers (data) {
			
			// Iterate each object returned.
			$.each (data,function (index, row) {
				

				// Start off with the default location.
				new_pos = initialLocation;

				if (row.lat && row.lon) {
					new_pos = new google.maps.LatLng(row.lat, row.lon);	
				} /*else {
					console.log ('  Using default position.');
				}*/
				
				// Does this marker ID exist? If not, create. 
				// Make sure not to double-up on the current user - this will already have a marker. 
				if (!(row.user_id in person_markers) && (row.user_id != user_id)) {
					var new_pos;

					console.log ('  Creating new marker for user ID ' + row.user_id);
					console.log ('    Row: ID ' + row.id + ', Event ID ' + row.event + ', User ' + row.user_id + ', Time ' + row.time + ', current user ' + user_id);
					
					// Rotate through the marker colours.
					marker_colour = marker_colours[(marker_count++) % marker_colours.length];
					
/*					if (!row.lat || !row.lon) {
						console.log ('Using default position.');
						new_pos = initialLocation;
					} else {
						new_pos = new google.maps.LatLng(row.lat, row.lon);	
					} */
			         
			         var new_marker = new google.maps.Marker({
			             position: new_pos,
			             map: map,
			             draggable: true,
			             animation: google.maps.Animation.DROP,
			             icon: new google.maps.MarkerImage("http://maps.google.com/mapfiles/ms/icons/" + marker_colour + ".png")
			             //title: 'Person - X: ' + position.coords.longitude + ', Y: ' +  position.coords.latitude
			             //title: 'Click Me ' + i
			         });

			         // Add the marker.
			         markers.push (new_marker);
			         
			         // Add the marker to the associative array.
			         person_markers[row.user_id] = new_marker;
			         
			         var contentString = '<div id="content">'+
			         '<div id="siteNotice">'+
			         '</div>'+
			         //'<h1 id="firstHeading" class="firstHeading">' + row.user_name + '</h1>'+
			         '<div id="bodyContent">'+
			         '<h4>' + row.user_name + '</h4>' +
			         '</div>'+
			         '</div>';

				     var infowindow = new google.maps.InfoWindow({
				         content: contentString
				     });

				     google.maps.event.addListener(new_marker, 'click', function() {
				    	 infowindow.open(map,new_marker);
				     });
				     
				     // Set the icon for the marker. This runs as an Ajax call.
				     //get_avatar (new_marker, row.user_id);
			         
					// initialLocation
				} else {
					
					// Update existing marker location.

					var pos = person_markers[row.user_id].getPosition();
					
					// TODO: Get the current position - don't want to update each marker unecessarily.
					// For now, always update each marker.
					if (pos != new_pos) {
						
						console.log ('  Updating marker for user ID ' + row.user_id);

						console.log ('  Position: ' + new_pos);

						person_markers[row.user_id].setPosition (new_pos);
					}
				}
				
				
			});
			
			console.log ('  Current person_markers array: ' + person_markers);
			
			console.log ('=========================================================');
		}
		

		function show_my_position () {
			
			// If this device supports geolocation, show the current posision.
			if (navigator.geolocation) {
			     navigator.geolocation.getCurrentPosition(function (position) {
			    	 //console.log ('Current position available.');
			    	 
			    	 // No marker exists - create a new one.
			    	 if (!me_marker) {
			    		 
			    		 console.log ("Creating new marker for current user's location.");
			    		 
				         my_position = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
		
				         me_marker = new google.maps.Marker({
				             position: my_position,
				             animation: google.maps.Animation.DROP,
				             map: map,
				             title: 'You!'
				         });
				         
				         var contentString = '<div id="content">'+
				         '<div id="siteNotice">'+
				         '</div>'+
				         //'<h1 id="firstHeading" class="firstHeading">' + row.user_name + '</h1>'+
				         '<div id="bodyContent">'+
				         '<h4>You!</h4>' +
				         '</div>'+
				         '</div>';

					     var infowindow = new google.maps.InfoWindow({
					         content: contentString
					     });

					     google.maps.event.addListener(me_marker, 'click', function() {
					    	 infowindow.open(map,me_marker);
					     });
			    	 } 
			    	 else { // Otherwise update the position of the existing marker.

			    		 //console.log ("Updating current user's location");
			    		 
			    		 me_marker.setPosition (my_position);
			    	 }

			     });
			}

			
		}
		
		var run_fetch_ajax = true;
		
		function request(){
			
	        if(run_fetch_ajax == true){
	        
	        	//$.post(
				console.log ('Firing Locator Ajax');
				
				run_fetch_ajax = false;
				
				jQuery.ajax({
					type: "POST",
				     url: LocatorAjax.ajaxurl,
				     data: ({
				    	 action : 'locatorajax-submit',
				    	 nonce: nonce,
				    	 event : event_id 
				     	}
				    ),
				     success: function(response) {
	 	       			console.log ('Success: ' + response);
	 	       			
	 	       			update_markers (response);
	 	       			
	 	       			run_fetch_ajax = true;
				     },
				     error: function(response) {
	  	       			console.log ('Error: ' + response);
	  	       			run_fetch_ajax = true;
	 			     }
				});

	        }
		};

		var run_update_ajax = true;
		
		function update(){

			// First, update the current position and display the marker. Sets 'my_position'.
			show_my_position ();
			
	        if(run_update_ajax == true){
	        
	        	//$.post(
				console.log ('Firing Updater Ajax');
				
				run_update_ajax = false;
				
				// Check the setting of the 'Trace Me' flag.
				if (1) {
					jQuery.ajax({
						type: "POST",
					     url: LocatorAjax.ajaxurl,
					     data: ({
					    	 action : 'locationupdateajax-submit',
					    	 event : event_id,
					    	 user_id: user_id,
					    	 nonce: nonce,
					    	 position: my_position
					     	}
					    ),
					     success: function(response) {
		 	       			console.log ('Success: ' + response);
		 	       			
		 	       			run_updater_ajax = true;
					     },
					     error: function(response) {
		  	       			console.log ('Error: ' + response);
		  	       			
		  	       			run_updater_ajax = true;
		 			     }
					});
	        	}
	        }
		};

		
		function get_avatar(marker, user_id){

			console.log ('Firing Avatar Ajax');
			
			jQuery.ajax({
				type: "POST",
			     url: AvatarAjax.ajaxurl,
			     data: ({
			    	 action : 'avatarajax-submit',
			    	 user_id: user_id,
			    	 nonce: nonce,
			     	}
			    ),
			     success: function(response) {
 	       			console.log ('Success: ' + response);
 	       			
 	       			//marker.setIcon (response);
 	       			
 	       			var icon = ({//new google.maps.Icon ({
 	       				url: response,
 	       				size: new google.maps.Size (20, 20)
 	       			});
 	       			
 	       			marker.setIcon (icon);
			     },
			     error: function(response) {
  	       			console.log ('Error: ' + response);
 			     }
			});
		};

		
		
		// Show the current user's location.
		show_my_position ();
		
		// Get the initial set of positions.
		request ();
	
		// Repeatedly fetch data on a timer.
		setInterval (
				function (){
					request ();
				}, 10*1000); 

		// Repeatedly update the current user's location on a timer.
		setInterval (
				function (){
					update ();
				}, 20*1000); 

	}
	
});
