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
 * @package Bimbler_Login_Redirect
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
