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


define( 'BIMBLER_AJAX_CLASS', 'Bimbler_Ajax' );

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

        	// Add the Ajax submit handlers.
        	add_action( 'wp_ajax_rsvpajax-submit', array ($this, 'rsvp_ajax_submit'));
        	add_action( 'wp_ajax_checkinajax-submit', array ($this, 'checkin_ajax_submit'));
        	add_action( 'wp_ajax_locatorajax-submit', array ($this, 'locator_ajax_submit'));
        	add_action( 'wp_ajax_cuesheetajax-submit', array ($this, 'cuesheet_ajax_submit'));
        	add_action( 'wp_ajax_avatarajax-submit', array ($this, 'avatar_ajax_submit'));
        	 
		} // End constructor.
		
		
		function enqueue_bimbler_scripts () {
			// embed the javascript file that makes the AJAX request
			wp_register_script ('bimbler-rsvp-script', plugin_dir_url( __FILE__ ) . 'js/bimbler.js', array( 'jquery' ) );
			wp_register_script ('bimbler-locator-script', plugin_dir_url( __FILE__ ) . 'js/bimbler-locator.js', array( 'jquery' ) );
			wp_register_script ('bimbler-rwgps-script', plugin_dir_url( __FILE__ ) . 'js/bimbler-rwgps.js', array( 'jquery' ) );
			wp_register_script ('bimbler-blockui-script', plugin_dir_url( __FILE__ ) . 'js/jquery.blockUI.js', array( 'jquery' ) );
				
			wp_enqueue_script( 'bimbler-rsvp-script');
			wp_enqueue_script( 'bimbler-locator-script');
			wp_enqueue_script( 'bimbler-rwgps-script');
			wp_enqueue_script( 'bimbler-blockui-script');
				
			// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
			wp_localize_script( 'bimbler-rsvp-script', 'RSVPAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
			wp_localize_script( 'bimbler-locator-script', 'LocatorAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
			wp_localize_script( 'bimbler-rwgps-script', 'CuesheetAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
			wp_localize_script( 'bimbler-locator-script', 'AvatarAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		}
		
		

		/**
		 * Adds the RSVP list to the event.
		 *
		 * @param
		 */
		
		function render_rsvp_table ($postid) {
		
			global $wpdb;
			global $rsvp_db_table;
		
			$has_event_passed = false;
		
			$html = '<div id="rsvp-list" class="widget">';
			$html .= '		    <h3 id="reply-title" class="comment-reply-title">Who\'s Coming</h3>';
	
			$rsvps_y = Bimbler_RSVP::get_instance()->get_event_rsvp_object ($postid, 'Y');
			$rsvps_n = Bimbler_RSVP::get_instance()->get_event_rsvp_object ($postid, 'N');
			$count_rsvps = Bimbler_RSVP::get_instance()->count_rsvps ($postid);
			$count_atts = Bimbler_RSVP::get_instance()->count_attendees ($postid);
	
			if (null === $count_atts) {
				$count_atts = 0;
			}
	
			$has_event_passed = Bimbler_RSVP::get_instance()->has_event_passed ($postid);
	
			$html .= '<div class="bimbler-count-tags" style="overflow-y: hidden;">';
			$html .= '  <div style="float: left;">RSVPs:&nbsp; </div>';
			$html .= '  <div id="yes-count" style="float: left;">' . $count_rsvps .'</div>';
			$html .= '</div>';
	
			if ($this->can_modify_attendance ()) {
				$html .= '<div class="bimbler-count-tags" style="overflow-y: hidden;">';
	
				if ($has_event_passed) {
					$html .= '  <div style="float: left;">Attended:&nbsp; </div>';
				} else {
					$html .= '  <div style="float: left;">Confirmed:&nbsp; </div>';
				}
	
				$html .= '  <div id="attendee-count" style="float: left;">' . $count_atts .'</div>';
				$html .= '</div>';
			}
	
			$html .= '<div id="AvatarListSide" class="AvatarListSide-wrap">';
			//$html .= '	<form method="post" id="commentform" class="commentform" enctype="multipart/form-data">';
	
			if ((0 == count ($rsvps_y)) && (0 == count ($rsvps_n)))
			{
				$html .= '<p>No RSVPs yet.</p>';
			}
			else if (!is_user_logged_in())
			{
				$html .= "<p>You must be logged in to see RSVPs.</p>";
			}
			else
			{
				// Show Yes RSVPs.
				$rsvps = $rsvps_y;
	
				if ($count_rsvps > 0)
				{
					$html .= '		    <ul>';
	
					foreach ( $rsvps as $rsvp) {
	
						$user_info   = get_userdata ($rsvp->user_id);
	
						$avatar = '';
							
						if (isset ($user_info->user_login)) {
							$avatar .= get_avatar ($rsvp->user_id, null, null, $user_info->user_login);
						}
							
						$comment = stripslashes ($rsvp->comment); // De-escape the DB data.
						$attend = $rsvp->attended;
	
						$html .= '<li class="AvatarListSide">';
							
						// Output an innocuous DIV if the user cannot amend attendance, or if the Ajax module is not loaded.
						if (!$this->can_modify_attendance ()) {
							$html .= '<div class="rsvp-checkin-container-noajax">';
						}
						else {
							// Store the RSVP ID.
							$html .= '<div class="rsvp-checkin-container" id="'. $rsvp->id .'">';
						}
							
						// Only allow changes if this is the currently logged-in user or admin.
						$html .= $avatar; // IMG.
	
						// Only show if the event has ended or we're admin.
						if (current_user_can( 'manage_options') || $has_event_passed)
						{
							$html .= '<div class="rsvp-checkin-indicator" id="rsvp-checkin-indicator-'. $rsvp->id .'">'; // Content will be replaced by Ajax.
	
							if (!isset ($attend)) {
								$html .= '<div class="rsvp-checkin-indicator-none"><i class="fa-question-circle"></i></div>';
							} else if ('Y' == $attend) {
								$html .= '<div class="rsvp-checkin-indicator-yes"><i class="fa-check-circle"></i></div>';
							}
							else {
								$html .= '<div class="rsvp-checkin-indicator-no"><i class="fa-times-circle"></i></div>';
							}
	
							$html .= '</div>';
						}
	
						$html .= '</div> <!-- rsvp-checkin-container -->';
	
						if (isset ($user_info->user_nicename)) {
							$html .= '<p><a href="/profile/' . urlencode ($user_info->user_nicename) .'/">' . $user_info->nickname;
	
							if ($rsvp->guests > 0) {
								$html .= ' + ' . $rsvp->guests;
							}
	
							$html .= '</a></p>';
						}
							
						$html .= '</li>';
					}
	
					$html .= '		    </ul>';
	
				}
				// Show No RSVPs.
				$rsvps = $rsvps_n;
	
				$count = count($rsvps_n);
					
				if ($count > 0)
				{
					if (1 == $count) {
						$html .= '<p>'. count($rsvps) .' not attending:</p>';
					} else {
						$html .= '<p>'. count($rsvps) .' not attending:</p>';
					}
	
					$html .= '		    <ul>';
	
					foreach ( $rsvps_n as $rsvp) {
	
						$comment = stripslashes ($rsvp->comment); // De-escape the DB data.
							
						$user_info   = get_userdata ($rsvp->user_id);
							
						if (isset ($user_info->user_login)) {
							$avatar = get_avatar ($rsvp->user_id, null, null, $user_info->user_login);
	
							$html .= '<li class="AvatarListSide"><div class="permalink"></div><a href="">'. $avatar;
	
							$html .= '<p><a href="/profile/' . urlencode ($user_info->user_nicename) .'/">' . $user_info->nickname;
	
							$html .= '</a><p></li>';
						}
					}
	
					$html .= '		    </ul>';
				}
			}
	
			//$html .= '		</form>';
			$html .= '		    </div> <!-- #rsvp-list-->';
			$html .= '		</div><!-- #footer Wrap-->';
	
			return $html;
		}
		
		
		
		function get_current_attendance_status ($rsvp_id) {
			global $wpdb;
			
			$rsvp_db_table = 'bimbler_rsvp';
			
			$table_name = $wpdb->base_prefix . $rsvp_db_table;
			
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
				
			$table_name = $wpdb->base_prefix . $rsvp_db_table;

			if (true == $null_status) {
				//error_log ('Null flag set.');

				$sql  = 'UPDATE '. $table_name;
				$sql .= ' SET attended = null ';
				$sql .= ' WHERE id = %s';
				
				$upd = $wpdb->query( $wpdb->prepare ($sql, $rsvp_id));
			}
			else {
				//error_log ('Null flag not set.');
				
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
		 * Determines if the user can execute Ajax, and checks if the Ajax Bimbler plugin is loaded.
		*/
		function can_modify_attendance () {
		
			if (!class_exists (BIMBLER_AJAX_CLASS)) {
				error_log ('User can\'t run Ajax - BIMBLER_AJAX_CLASS not loaded.');
				return false;
			}
		
			if (!current_user_can ('manage_options')) {
				error_log ('User can\'t run Ajax - not an admin.');
				return false;
			}
		
			return true;
		}
		
		function rsvp_ajax_submit () {

			parse_str ($_POST['input'], $post_input);
			

			$response = json_encode( $this->render_rsvp_table ($post_input['rsvp_post_id']));
			//$response = 'Hello.';
					
			//$post_input = json_decode ($_POST['input'], true);
				
			//error_log ('rsvp_ajax_submit: received ' . print_r ($post_input['rsvp_post_id'], true));
			error_log ('rsvp_ajax_submit: received object: ' . print_r ($post_input, true));
			error_log ('rsvp_ajax_submit: received RSVP \'' . $_POST['rsvp'] . '\' for post ID ' . $post_input['rsvp_post_id'] . ' for user ID ' . $post_input['rsvp_user_id'] . ' with ' . $post_input['rsvp_guests'] . ' guests.');
				
			header( "Content-Type: application/json" );
			
			echo $response;
			
			exit;
		}
		
		function checkin_ajax_submit () {
			//error_log ('Ajax submit fired.');
			
			// get the submitted parameters
			$rsvp_id = $_POST['container'];

			$status = $this->get_current_attendance_status ($rsvp_id);
			
			if (!isset ($status)) {
				header( "Content-Type: application/json" );
				
				// generate the response
				$response = json_encode( 'SQL Error' );
	
				error_log ('Bimbler AJAX: Error - sending: \'' . $response . '\'.');

				echo $response;
				
				exit;
			}
			
			// No attendance - set to 'Here'.
			if ('X' == $status) {
				//error_log ('Was null, now Y');
				$next = 'Y';
				$null_status = false;
				$send = '<div class="rsvp-checkin-indicator-yes"><i class="fa-check-circle"></i></div>';
			}
			elseif ('Y' == $status) { // If here, set to not.
				//error_log ('Was Y, now N');
				$next = 'N';
				$null_status = false;
				$send = '<div class="rsvp-checkin-indicator-no"><i class="fa-times-circle"></i></div>';
			}
			else { // If not here, set to 'unknown'.
				//error_log ('Was N, now null');
				$next = null;
				$null_status = true;
				$send = '<div class="rsvp-checkin-indicator-none"><i class="fa-question-circle"></i></div>';
			}
			
			if (!$this->set_current_attendance_status ($rsvp_id, $next, $null_status)) {
				$response = json_encode( 'SQL Error' );
				
				error_log ('Bimbler AJAX: Error - sending: \'' . $response . '\'.');
				
				echo $response;
				
				exit;
			}
			
			//error_log ('RSVP ID: ' . $container);

			header( "Content-Type: application/json" );
			
			// generate the response
			$response = json_encode ($send);

			//error_log ('Sending:' . $response);
			
			// response output
			echo $response;
			
			exit;
		}

		function locator_ajax_submit () {
			error_log ('Locator Ajax submit fired.');
				
/*			// get the submitted parameters
			$rsvp_id = $_POST['container'];
		
			$status = $this->get_current_attendance_status ($rsvp_id);
				
			if (!isset ($status)) {
				header( "Content-Type: application/json" );
		
				// generate the response
				$response = json_encode( 'SQL Error' );
		
				error_log ('Bimbler AJAX: Error - sending: \'' . $response . '\'.');
		
				echo $response;
		
				exit;
			}
				
			// No attendance - set to 'Here'.
			if ('X' == $status) {
				//error_log ('Was null, now Y');
				$next = 'Y';
				$null_status = false;
				$send = '<div class="rsvp-checkin-indicator-yes"><i class="fa-check-circle"></i></div>';
			}
			elseif ('Y' == $status) { // If here, set to not.
				//error_log ('Was Y, now N');
				$next = 'N';
				$null_status = false;
				$send = '<div class="rsvp-checkin-indicator-no"><i class="fa-times-circle"></i></div>';
			}
			else { // If not here, set to 'unknown'.
				//error_log ('Was N, now null');
				$next = null;
				$null_status = true;
				$send = '<div class="rsvp-checkin-indicator-none"><i class="fa-question-circle"></i></div>';
			}
				
			if (!$this->set_current_attendance_status ($rsvp_id, $next, $null_status)) {
				$response = json_encode( 'SQL Error' );
		
				error_log ('Bimbler AJAX: Error - sending: \'' . $response . '\'.');
		
				echo $response;
		
				exit;
			}
	*/			
			//error_log ('RSVP ID: ' . $container);
		
			// get the submitted parameters
			$event_id = $_POST['event'];
			
			error_log ('Locator AJAX: Event Post ID: ' . $event_id);
			
			if (!wp_verify_nonce($_POST['nonce'], 'bimbler_locator')) {
				error_log ('locator_ajax_submit: Cannot validate nonce.');
				
				$send = 'Error: cannot validate nonce.';
			} else {
				// Fetch RSVP table contents for this event.
				$rsvps = Bimbler_RSVP::get_instance()->get_event_rsvp_object ($event_id);
				
				if (!isset ($rsvps)) {
					$send = 'Error';
					
				} else	{
					$send = $rsvps;
				}
			}

			header( "Content-Type: application/json" );

			// generate the response
			$response = json_encode ($send);
			
			error_log ('Locator AJAX - Sending:' . print_r ($response, true));
				
			// response output
			echo $response;
				
			exit;
		}
		
		function cuesheet_ajax_submit () {
			error_log ('Cuesheet Ajax submit fired.');
		
			// get the submitted parameters
			$rwgps_id = $_POST['rwgps_id'];
				
			error_log ('Cuesheet AJAX: RWGPS ID: ' . $rwgps_id);
		
			// Fetch RSVP table contents for this event.
			/*$rsvps = Bimbler_RSVP::get_instance()->get_event_rsvp_object ($event_id);
				
			if (!isset ($rsvps)) {
				$send = 'Error';
		
			} else	{
				$send = $rsvps;
			} */
			
			$send = '<p>Hello.</p>';
				
			header( "Content-Type: application/json" );
		
			// generate the response
			$response = json_encode ($send);
				
			error_log ('Cuesheet AJAX - Sending:' . print_r ($response, true));
		
			// response output
			echo $response;
		
			exit;
		}
		
		function avatar_ajax_submit () {
			error_log ('Avatar Ajax submit fired.');

			if (!wp_verify_nonce($_POST['nonce'], 'bimbler_locator')) {
				error_log ('locator_ajax_submit: Cannot validate nonce.');
			
				$send = 'Error: cannot validate nonce.';
			} else {
					
			
				// get the submitted parameters
				$user_id = $_POST['user_id'];

				$avatar = get_avatar ($user_id, 32);
				
				// Fetch RSVP table contents for this event.
				/*$rsvps = Bimbler_RSVP::get_instance()->get_event_rsvp_object ($event_id);
			
				if (!isset ($rsvps)) {
				$send = 'Error';
			
				} else	{
				$send = $rsvps;
				} */
				
				//$match = '';
				
				//preg_match("/src=(['\"])(.*?)\1/", $avatar, $match);
				
				//preg_match("/src='(.*?)'/i", $avatar, $match);
					
				preg_match( '#src=["|\'](.+)["|\']#Uuis', $avatar, $match );
				
				$send = $match[1];
			}
		
			header( "Content-Type: application/json" );
		
			// generate the response
			$response = json_encode ($send);
		
			error_log ('Avatar AJAX - Sending:' . print_r ($response, true));
		
					// response output
			echo $response;

			exit;
		}
		
		
} // End class
