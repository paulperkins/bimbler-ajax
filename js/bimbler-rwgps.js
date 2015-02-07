
jQuery(document).ready(function ($) {
	
	if (document.getElementById('bimbler_rwgps_cuesheet')) {
		
		var rwgps_id = document.getElementById('bimbler_rwgps_cuesheet').getAttribute('data-rwgps-id');
	        
		console.log ('Firing Cuesheet Ajax');
		
		run_ajax = false;
		
		jQuery.ajax({
			type: "POST",
		     url: CuesheetAjax.ajaxurl,
		     data: ({
		    	 action : 'cuesheetajax-submit',
		    	 rwgps_id : rwgps_id 
		     	}
		    ),
		     success: function(response) {
       			console.log ('Success: ' + response);
       			
       			document.getElementById('bimbler_rwgps_cuesheet').innerHTML = response;
    	     },
		     error: function(response) {
       			console.log ('Error: ' + response);
    	     }
		});
	
	}
});
