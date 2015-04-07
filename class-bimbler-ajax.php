<?php
/**
 * Bimbler RSVP
 *
 * @package   Bimbler_Ajax
 * @author    Paul Perkins <paul@paulperkins.net>
 * @license   GPL-2.0+
 * @link      http://www.paulperkins.net
 * @copyright 2014 Paul Perkins
 */

/**
 * Include dependencies necessary... (none at present)
 *
 */

/**
 * Bimbler Ajax
 *
 * @package Bimbler_Ajax
 * @author  Paul Perkins <paul@paulperkins.net>
 */
class Bimbler_Ajax {

        /*--------------------------------------------*
         * Constructor
         *--------------------------------------------*/

        /**
         * Instance of this class.
         *
         * @since    1.0.0
         *
         * @var      object
         */
        protected static $instance = null;
        
        protected static $rwgps_api_key = '8zmegw';
        protected static $rwgps_api_version = 2;

        /**
         * Return an instance of this class.
         *
         * @since     1.0.0
         *
         * @return    object    A single instance of this class.
         */
        public static function get_instance() {

                // If the single instance hasn't been set, set it now.
                if ( null == self::$instance ) {
                        self::$instance = new self;
                } // end if

                return self::$instance;

        } // end get_instance

        /**
         * Initializes the plugin by setting localization, admin styles, and content filters.
         */
        private function __construct() {

        	// Set up the AJAX enqueing.
        	add_action ('wp_enqueue_scripts', array ($this, 'enqueue_bimbler_scripts'));

        	// Add the Ajax submit handler.
        	add_action( 'wp_ajax_checkinajax-submit', array ($this, 'rsvp_ajax_submit'));
        	add_action( 'wp_ajax_rsvpajax-submit', array ($this, 'rsvp_ajax_submit'));
        	add_action( 'wp_ajax_user-rsvpajax-submit', array ($this, 'user_rsvp_ajax_submit'));
        	add_action( 'wp_ajax_commentajax-submit', array ($this, 'comment_ajax_submit'));
        	add_action( 'wp_ajax_elevationajax-submit', array ($this, 'elevation_ajax_submit'));
        	add_action( 'wp_ajax_locatorajax-submit', array ($this, 'locator_ajax_submit'));
        	add_action( 'wp_ajax_locationupdateajax-submit', array ($this, 'location_update_ajax_submit'));
        	 
        	 
		} // End constructor.
		
		
		function enqueue_bimbler_scripts () {
			// embed the javascript file that makes the AJAX request
			wp_register_script ('bimbler-ajax-script', plugin_dir_url( __FILE__ ) . 'js/bimbler.js', array( 'jquery' ) );
		
			wp_enqueue_script( 'bimbler-ajax-script');
				
			// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
			wp_localize_script( 'bimbler-ajax-script', 'RSVPAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );


			wp_register_script ('bimbler-google-maps', 'https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=weather');
		
			wp_enqueue_script( 'bimbler-google-maps');

		}
		
		
		function get_current_attendance_status ($rsvp_id) {
			global $wpdb;
			
			$rsvp_db_table = 'bimbler_rsvp';
			
			$table_name = $wpdb->prefix . $rsvp_db_table;
			
			$sql =  'SELECT * FROM '. $table_name;
			$sql .= ' WHERE id = '. $rsvp_id;
			
			$att = $wpdb->get_row ($sql);
				
			if (!isset ($att)) {
				error_log ('Bimbler Ajax: SQL Error fetching atendance status for RSVP ID ' . $rsvp_id . ' Error: ' . $wpdb->print_error());
				return null;	
			}

			// Assume not set - NULL.
			$return = 'X';
			
			if (isset ($att->attended)) {
				$return = $att->attended;
			}
			
			return $return;
		}

		function set_current_attendance_status ($rsvp_id, $status, $null_status) {
			global $wpdb;
				
			$rsvp_db_table = 'bimbler_rsvp';
				
			$table_name = $wpdb->prefix . $rsvp_db_table;

			if (true == $null_status) {
				error_log ('Null flag set.');

				$sql  = 'UPDATE '. $table_name;
				$sql .= ' SET attended = null ';
				$sql .= ' WHERE id = %s';
				
				$upd = $wpdb->query( $wpdb->prepare ($sql, $rsvp_id));
			}
			else {
				error_log ('Null flag not set.');
				
				$sql  = 'UPDATE '. $table_name;
				$sql .= ' SET attended = %s';
				$sql .= ' WHERE id = %s';
				
				$upd = $wpdb->query( $wpdb->prepare ($sql, $status, $rsvp_id));
			}
			
			if (!isset ($upd)) {
					error_log ('Bimbler Ajax: SQL Error updating atendance status for RSVP ID ' . $rsvp_id . '. Error: '. $wpdb->print_error());
					return false;
			}
							
			return true;			
		}

		function fetch_rwgps_elevation ($rwgps_id) {
			
			if (!isset ($rwgps_id)) {
				return null;
			}
			
			$get  = 'http://ridewithgps.com/routes/' . $rwgps_id . '.json?';
			$get .= 'apikey=' . $this->rwgps_api_key;
			$get .= 'version=' . $this->rwgps_api_version;
			
			$elevation_json = file_get_contents($get);
			
			if (!isset ($elevation_json)) {
				error_log ('Call to RWGPS API failed.');
				
				return null;
			}

			$elevation = json_decode ($elevation_json);
			
			//error_log (print_r($elevation->route->track_points, true));
			
			return $elevation->route->track_points;
		}
		
		/*
		 * Handler for the elevation fetch Ajax call.
		*/
		function elevation_ajax_submit () {
		
			$rwgps_id = $_POST['rwgps_id'];
			$nonce = $_POST['nonce'];
		
			error_log ('Elevation AJAX: RWGPS ID: ' . $rwgps_id);
			error_log ('Elevation AJAX: Nonce:    ' . $nonce);
		
			$response = '';
		
			if (!is_user_logged_in()) {
				error_log ('elevation_ajax_submit: User not logged in.');
					
				header( "Content-Type: application/json" );
		
				$send['status'] = 'error';
				$send['text'] = 'Not logged in!';
		
				$response = json_encode ($send);
					
				error_log ('Elevation AJAX - Sending:' . print_r ($send, true));
		
				// response output
				echo $response;
		
				exit;
			}
				
			// TODO: Sanity-check value of RWGPS ID - must be an int.
			if (0 == strlen ($rwgps_id)) {
				error_log ('elevation_ajax_submit: RWGPS ID not set.');
					
				header( "Content-Type: application/json" );
					
				$send['status'] = 'no_comment';
				$send['text'] = 'Please enter a comment!';
					
				$response = json_encode ($send);
					
				error_log ('Elevation AJAX - Sending:' . print_r ($send, true));
					
				echo $response;
					
				exit;
			}
		
/*			if (!wp_verify_nonce($_POST['nonce'], 'bimbler_elevation')) {
				error_log ('elevation_ajax_submit: Cannot validate nonce.');
					
				header( "Content-Type: application/json" );
		
				$send['status'] = 'invalid_nonce';
				$send['text'] = 'Cannot validate nonce.';
		
				$response = json_encode ($send);
					
				error_log ('Elevation AJAX - Sending:' . print_r ($send, true));
		
				// response output
				echo $response;
		
				exit;
			} */

			$elevation_data = $this->fetch_rwgps_elevation ($rwgps_id); 
			
			//error_log (print_r($elevation_data, true));
		
			if (!isset ($elevation_data)) {
				header( "Content-Type: application/json" );
		
				$send['status'] = 'error';
				$send['text'] = 'Cannot fetch elevation data.';
		
				$response = json_encode ($send);
					
				error_log ('Elevation AJAX - Sending:' . print_r ($send, true));
		
				// response output
				echo $response;
		
				exit;
			}
		
			// All good - convert data into an array of (distance, elevation) pairs.
			$first = true;
			$return = array();
			
			foreach ($elevation_data as $pt) {
				
				if ($first) {
					$pair = array (0, $pt->e);
				} else {
					// Distance is measures in metres.
					$pair = array (round($pt->d / 1000,2), $pt->e);
						
				}
				
				$return[] = $pair;
				
				$first = false;
			}
			
			//error_log (print_r($return, true));
			
			$send['status'] = 'success';
			$send['text'] = 'Elevation data retrieved.';
			$send['data'] = $return;
				
			header( "Content-Type: application/json" );
		
			// generate the response
			$response = json_encode ($send);
		
			//error_log ('Elevation AJAX - Sending:' . print_r ($response, true));
			error_log ('Elevation AJAX - Sending: ' . $send['status'] . ' -> \'' . $send['text'] . '\'.');
					
			// response output
			echo $response;
		
			exit;
		}

		function locator_ajax_submit () {
		
			// get the submitted parameters
			$event_id = $_POST['event'];
				
			//error_log ('Locator AJAX: Event ID: ' . $event_id);
				
			if (!wp_verify_nonce($_POST['nonce'], 'bimbler_locator')) {
				error_log ('locator_ajax_submit: Cannot validate nonce.');
		
				$send = 'Error: cannot validate nonce.';
			} else if  (!is_numeric ($event_id)) {
				error_log ('locator_ajax_submit: Cannot validate event_id.');
				
				$send = 'Error: cannot validate event_id.';
				
			} else {
				// Fetch RSVP table contents for this event.
				$rsvps = Bimbler_RSVP::get_instance()->get_event_rsvp_object ($event_id);
				
				$return_rsvps = array();
				
				// Get the position data for each user.
				if ($rsvps) {
					foreach ($rsvps as $rsvp) {
						
						$meta = get_user_meta ($rsvp->user_id, 'bimblers_loc_json', true);
						
						$meta_object = json_decode ($meta);
	
						if (!empty ($meta_object)) {
							$rsvp->pos_lat = $meta_object->lat;
							$rsvp->pos_lng = $meta_object->lng;
							$rsvp->pos_spd = $meta_object->spd;
							$rsvp->pos_hdg = $meta_object->hdg;
							$rsvp->pos_time = $meta_object->time;
						
							// Only return records for those tracking on this event.
							if ($meta_object->event_id == $event_id) {
								$return_rsvps[] = $rsvp;
							}
						}
					}
				}
				
				$send = $return_rsvps;
			}
		
			header( "Content-Type: application/json" );
		
			// generate the response
			$response = json_encode ($send);
				
			//error_log ('Locator AJAX - Sending:' . print_r ($response, true));
		
			// response output
			echo $response;
		
			exit;
		}
		

		/*
		 * Handler for the location update Ajax call.
		*/
		function location_update_ajax_submit () {
		
			$nonce = $_POST['nonce'];
			$pos_lat = $_POST['pos_lat'];
			$pos_lng = $_POST['pos_lng'];
			$pos_spd = $_POST['pos_spd'];
			$pos_hdg = $_POST['pos_hdg'];
			$event_id = $_POST['event'];
			//$pos_time = $_POST['pos_time'];
				
			//error_log ('Location Update AJAX: Lat: ' . $pos_lat);
			//error_log ('Location Update AJAX: Lng: ' . $pos_lng);
			//error_log ('Location Update AJAX: Spd: ' . $pos_spd);
			//error_log ('Location Update AJAX: Nonce: ' . $nonce);
		
			$response = '';
		
			if (!is_user_logged_in()) {
				error_log ('location_update_ajax_submit: User not logged in.');
					
				header( "Content-Type: application/json" );
		
				$send['status'] = 'error';
				$send['text'] = 'Not logged in!';
		
				$response = json_encode ($send);
					
				error_log ('Location Update AJAX - Sending:' . print_r ($send, true));
		
				// response output
				echo $response;
		
				exit;
			}
		
			if (!wp_verify_nonce($_POST['nonce'], 'bimbler_locator')) {
				error_log ('location_update_ajax_submit: Cannot validate nonce.');
					
				header( "Content-Type: application/json" );
		
				$send['status'] = 'invalid_nonce';
				$send['text'] = 'Cannot validate nonce.';
		
				$response = json_encode ($send);
					
				error_log ('Location Update AJAX - Sending:' . print_r ($send, true));
		
				// response output
				echo $response;
		
				exit;
			}
			
			// Validate the incoming data.
			if (!is_numeric ($event_id)) {
				error_log ('location_update_ajax_submit: Event ID is not numeric!');
					
				header( "Content-Type: application/json" );
					
				$send['status'] = 'error';
				$send['text'] = 'Event ID data not valid!';
					
				$response = json_encode ($send);
					
				error_log ('Location Update AJAX - Sending:' . print_r ($send, true));
					
				// response output
				echo $response;
					
				exit;
			}
			
			// Validate the incoming data.
			if (!is_numeric ($pos_lat) || !is_numeric ($pos_lng)) {
				error_log ('location_update_ajax_submit: Lat / lng not numeric!');
					
				header( "Content-Type: application/json" );
					
				$send['status'] = 'error';
				$send['text'] = 'Lat / lng data not valid!';
					
				$response = json_encode ($send);
					
				error_log ('Location Update AJAX - Sending:' . print_r ($send, true));
					
				// response output
				echo $response;
					
				exit;
			}
			
			// Validate the incoming data.
			if (!empty ($pos_spd) && !is_numeric ($pos_spd)) {
				error_log ('location_update_ajax_submit: Spd not numeric!');
					
				header( "Content-Type: application/json" );
					
				$send['status'] = 'error';
				$send['text'] = 'Spd data not valid!';
					
				$response = json_encode ($send);
					
				error_log ('Location Update AJAX - Sending:' . print_r ($send, true));
					
				// response output
				echo $response;
					
				exit;
			}
			
			// Validate the incoming data.
			if (!empty ($pos_hdg) && !is_numeric ($pos_hdg)) {
				error_log ('location_update_ajax_submit: Hdg not numeric!');
					
				header( "Content-Type: application/json" );
					
				$send['status'] = 'error';
				$send['text'] = 'Hdg data not valid!';
					
				$response = json_encode ($send);
					
				error_log ('Location Update AJAX - Sending:' . print_r ($send, true));
					
				// response output
				echo $response;
					
				exit;
			}
				
			// Validate the incoming data.
/*			if (!empty ($pos_time) && !is_numeric ($pos_time)) {
				error_log ('location_update_ajax_submit: Time not numeric!');
					
				header( "Content-Type: application/json" );
					
				$send['status'] = 'error';
				$send['text'] = 'Time data not valid!';
					
				$response = json_encode ($send);
					
				error_log ('Location Update AJAX - Sending:' . print_r ($send, true));
					
				// response output
				echo $response;
					
				exit;
			} */
				
			global $current_user;
			get_currentuserinfo();
			
			$this_user_id = $current_user->ID;
				
			// Do the biz.
			
			$loc_object = new stdClass();
			
			//$loc_object->time = $pos_time;
			$loc_object->time = time(); //date(DATE_W3C);
			$loc_object->lat = $pos_lat;
			$loc_object->lng = $pos_lng;
			$loc_object->spd = $pos_spd;
			$loc_object->hdg = $pos_hdg;
			$loc_object->event_id = $event_id;
				
			$loc_json = json_encode ($loc_object);
			
			//error_log ('Location JSON: ' . print_r($loc_json, true));
			
			update_user_meta ($current_user->ID, 'bimblers_loc_json', $loc_json);
			
			//$meta = get_user_meta ($current_user->ID, 'bimblers_loc_json', true);
			//error_log ('Location Meta: ' . print_r($meta, true));
				
			// All good.
			$send['status'] = 'success';
			$send['text'] = 'Location updated.';
			$send['data'] = $loc_json;
				
			header( "Content-Type: application/json" );
		
			// generate the response
			$response = json_encode ($send);
		
			//error_log ('Update Location AJAX - Sending:' . print_r ($response, true));
		
			// response output
			echo $response;
		
			exit;
		}
		
		
		
		/*
		 * Handler for the comment upload Ajax call.
		 */
		function comment_ajax_submit () {
		
			$post_id = $_POST['event_id'];
			$parent_comment_id = $_POST['parent_comment_id'];
			$nonce = $_POST['nonce'];
			$comment = $_POST['comment'];
		
			error_log ('Comment AJAX: Event Post ID: ' . $post_id);
			error_log ('Comment AJAX: Parent comment: ' . $parent_comment_id);
			error_log ('Comment AJAX: Comment: ' . $comment);
			error_log ('Commentt AJAX: Nonce: ' . $nonce);
		
			$response = '';
				
			if (!is_user_logged_in()) {
				error_log ('comment_ajax_submit: User not logged in.');
					
				header( "Content-Type: application/json" );
		
				$send['status'] = 'error';
				$send['text'] = 'Not logged in!';
		
				$response = json_encode ($send);
					
				error_log ('Comment AJAX - Sending:' . print_r ($send, true));
		
				// response output
				echo $response;
		
				exit;
			}
				
			global $current_user;
			get_currentuserinfo();
		
			$this_user_id = $current_user->ID;
			
			if (!isset ($post_id)) {
				error_log ('comment_ajax_submit: Event ID not set.');
					
				header( "Content-Type: application/json" );
				
				$send['status'] = 'error';
				$send['text'] = 'Unknown error - form not set up correctly (event_id not set)!';
				
				$response = json_encode ($send);
					
				error_log ('Comment AJAX - Sending:' . print_r ($send, true));
				
				// response output
				echo $response;
				
				exit;
			}
			
			if (0 == strlen ($comment)) {
				error_log ('comment_ajax_submit: Comment not set.');
					
				header( "Content-Type: application/json" );
			
				$send['status'] = 'no_comment';
				$send['text'] = 'Please enter a comment!';
			
				$response = json_encode ($send);
					
				error_log ('Comment AJAX - Sending:' . print_r ($send, true));
			
				// response output
				echo $response;
			
				exit;
			}
			
				
			if (!wp_verify_nonce($_POST['nonce'], 'bimbler_comment')) {
				error_log ('rsvp_ajax_submit: Cannot validate nonce.');
					
				header( "Content-Type: application/json" );
		
				$send['status'] = 'invalid_nonce';
				$send['text'] = 'Cannot validate nonce.';
		
				$response = json_encode ($send);
					
				error_log ('Comment AJAX - Sending:' . print_r ($send, true));
		
				// response output
				echo $response;
		
				exit;
			}
			
			$commentdata = array(
					'comment_post_ID' 		=> $post_id,
					'comment_author'		=> $current_user->user_login,
					'comment_author_email' 	=> $current_user->user_email,
					'comment_author_url' 	=> $current_user->user_url,
					'comment_content' 		=> $comment,
					// 'comment_parent'		=> $parent_Comment_id, // We're not supporting replies right now.
					'user_id' 				=> $this_user_id
			);
			
			// Insert new comment and get the comment ID
			$new_comment_id = wp_new_comment($commentdata);

			if (!isset ($new_comment_id)) {
				header( "Content-Type: application/json" );
				
				$send['status'] = 'error';
				$send['text'] = 'Cannot create comment.';
				
				$response = json_encode ($send);
					
				error_log ('Comment AJAX - Sending:' . print_r ($send, true));
				
				// response output
				echo $response;
				
				exit;
				
			}

			// All good.
			$send['status'] = 'success';
			$send['text'] = 'New comment created.';
			$send['id'] = $new_comment_id;
			
			header( "Content-Type: application/json" );
		
			// generate the response
			$response = json_encode ($send);
		
			error_log ('Comment AJAX - Sending:' . print_r ($response, true));
		
			// response output
			echo $response;
		
			exit;
		}
		

		/*
		 * Handler for RSVP update Ajax.
		 */
		function user_rsvp_ajax_submit () {
				
			$event_id = $_POST['event_id'];
			$user_id = $_POST['user_id'];
			$nonce = $_POST['nonce'];
			$rsvp = $_POST['rsvp'];
		
			error_log ('RSVP AJAX: Event Post ID: ' . $event_id);
			error_log ('RSVP AJAX: User ID: ' . $user_id);
			error_log ('RSVP AJAX: RSVP: ' . $rsvp);
			error_log ('RSVP AJAX: Nonce: ' . $nonce);
		
			$response = '';
			
			if (!is_user_logged_in()) {
				error_log ('rsvp_ajax_submit: User not logged in.');
					
				header( "Content-Type: application/json" );
				
				$send['status'] = 'error';
				$send['text'] = 'Not logged in!';
				
				$response = json_encode ($send);
					
				error_log ('RSVP AJAX - Sending:' . print_r ($send, true));
				
				// response output
				echo $response;
				
				exit;
			}

			
			global $current_user;
			get_currentuserinfo();

			$this_user_id = $current_user->ID;
			
			if ((!current_user_can('manage_options' )) && ($this_user_id != $user_id)) {
				error_log ('rsvp_ajax_submit: Non-admin user trying to update other user\'s RSVP.');
					
				header( "Content-Type: application/json" );
				
				$send['status'] = 'error';
				$send['text'] = 'Nice try!';
				
				$response = json_encode ($send);
					
				error_log ('RSVP AJAX - Sending:' . print_r ($send, true));
				
				// response output
				echo $response;
				
				exit;
			}
			
			if (!wp_verify_nonce($_POST['nonce'], 'bimbler_rsvp')) {
				error_log ('rsvp_ajax_submit: Cannot validate nonce.');
					
				header( "Content-Type: application/json" );
		
				$send['status'] = 'invalid_nonce';
				$send['text'] = 'Cannot validate nonce.';
		
				$response = json_encode ($send);
					
				error_log ('RSVP AJAX - Sending:' . print_r ($send, true));
		
				// response output
				echo $response;
		
				exit;
			}
			
		
			$send['status'] = 'success';
			//$send['text'] = 'Cannot validate nonce.';
			
			
			if (null == Bimbler_RSVP::get_instance()->get_current_rsvp($event_id, $user_id)) {
				error_log ('RSVP Ajax - Create new RSVP.');
				
				$send['text'] = 'New RSVP created.';
				
				// New RSVP.
				Bimbler_RSVP::get_instance()->insert_rsvp ($event_id, $user_id, $rsvp, $comment, $guests);
			}
			else {
				error_log ('RSVP Ajax - Update existing RSVP.');
				
				$send['text'] = 'RSVP updated.';
				
				// Updated RSVP.
				Bimbler_RSVP::get_instance()->update_rsvp  ($event_id, $user_id, $rsvp, $comment, $guests);
			}
		
			$send['yes_rsvp_count'] = Bimbler_RSVP::get_instance()->count_rsvps ($event_id);
			$send['no_rsvp_count'] = Bimbler_RSVP::get_instance()->count_no_rsvps ($event_id);
				
			if (!isset ($send['yes_rsvp_count'])) {
				$send['yes_rsvp_count'] = 0;
			}
			
			if (!isset ($send['no_rsvp_count'])) {
				$send['no_rsvp_count'] = 0;
			}
				
			header( "Content-Type: application/json" );
				
			// generate the response
			$response = json_encode ($send);
		
			error_log ('RSVP AJAX - Sending:' . print_r ($response, true));
				
			// response output
			echo $response;
				
			exit;
		}
		
		/* 
		 * Handler for 'attended' indicator update.
		 */
		function rsvp_ajax_submit () {
			//error_log ('Ajax submit fired.');
			
			$rsvp_id = $_POST['container'];
			
			$event_id = 0;
			
			if (isset ($_POST['event_id'])) {
				$event_id = $_POST['event_id'];
			}
				
			$status = $this->get_current_attendance_status ($rsvp_id);
			
			if (!isset ($status)) {
				header( "Content-Type: application/json" );
				
				$send['status'] = 'error';
				$send['text'] = 'SQL Error';
				
				// generate the response
				$response = json_encode( $send );
	
				error_log ('Bimbler AJAX: Error - sending: \'' . $response . '\'.');

				echo $response;
				
				exit;
			}
			
			// No attendance - set to 'Here'.
			if ('X' == $status) {
				//error_log ('Was null, now Y');
				$next = 'Y';
				$null_status = false;
				$send['indicator'] = '<div class="rsvp-checkin-indicator-yes"><i class="fa-check-circle"></i></div>';
			}
			elseif ('Y' == $status) { // If here, set to not.
				//error_log ('Was Y, now N');
				$next = 'N';
				$null_status = false;
				$send['indicator'] = '<div class="rsvp-checkin-indicator-no"><i class="fa-times-circle"></i></div>';
			}
			else { // If not here, set to 'unknown'.
				//error_log ('Was N, now null');
				$next = null;
				$null_status = true;
				$send['indicator'] = '<div class="rsvp-checkin-indicator-none"><i class="fa-question-circle"></i></div>';
			}
			
			if (!$this->set_current_attendance_status ($rsvp_id, $next, $null_status)) {
				
				$send['status'] = 'error';
				$send['text'] = 'SQL Error';
				
				$response = json_encode( $send );
				
				error_log ('Bimbler AJAX: Error - sending: \'' . $response . '\'.');
				
				echo $response;
				
				exit;
			}
			
			//error_log ('RSVP ID: ' . $container);

			header( "Content-Type: application/json" );
			
			$send['status'] = 'success';
			
			// Send the number of attendees.
			if (isset ($event_id)) {

				$send['attendee_count'] = Bimbler_RSVP::get_instance()->count_attendees ($event_id);
				$send['rsvp_count'] = Bimbler_RSVP::get_instance()->count_rsvps ($event_id);
			}
			
			// generate the response
			$response = json_encode ($send);

			error_log ('Sending:' . $response);
			
			// response output
			echo $response;
			
			exit;
		}
		
		
} // End class
