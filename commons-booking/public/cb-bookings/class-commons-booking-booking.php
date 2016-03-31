<?php

/**
 * Commons Booking Bookings Class
 *
 * @package   Commons_Booking
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @author    Christian Wenzel <christian@wielebenwir.de>
 * @license   GPL-2.0+
 * @link      http://www.wielebenwir.de
 * @copyright 2015 wielebenwir
 */

/**
 * This class includes all frontend functions for bookings
 * *
 * @package Commons_Booking_Booking
 * @author    Florian Egermann <florian@wielebenwir.de>
 *
 */

class Commons_Booking_Booking { 

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since   0.0.1
     *
     * @var     string
     */

    public function __construct() {

        $this->settings = new CB_Admin_Settings();
        $this->data = new Commons_Booking_Data;

        $this->prefix = 'commons-booking';

    	global $wpdb;
    	$this->user_id = get_current_user_id();  // get user id
    	$this->table_timeframe = $wpdb->prefix . 'cb_timeframes';
        $this->table_codes = $wpdb->prefix . 'cb_codes';
    	$this->table_bookings = $wpdb->prefix . 'cb_bookings';

        $this->secret = '8L1i92OaxcnbNbveOLR6MWs3CUJ22e4P'; /* TODO */



    }

/**
 * Get location id based on booking-data and item
 *
 * @return array
 */
    public function get_booking_location_id( $date_start, $date_end, $item_id ) {
    	
    	global $wpdb;

    	// get location_id & item_id from timeframe-database
    	 $sqlresult = $wpdb->get_results($wpdb->prepare(
    	 	"
    	 	SELECT location_id 
	 		FROM " . $this->table_timeframe . " 
 			WHERE  date_start <= '%s' AND date_end >= '%s' AND item_id = '%s'
 			", 
 			$date_start, $date_end, $item_id), ARRAY_A); // get dates from db

    	 // @TODO: Insert check an error-handling if result-numer > 1

    	 return $sqlresult[0]['location_id'];

     }
  
 /**
 * Create a hash for booking url
 *
 * @return string - hash
 */   
    public function create_hash( ) {

    if ( empty( $wp_hasher ) ) {
        require_once ABSPATH . WPINC . '/class-phpass.php';
        $wp_hasher = new PasswordHash( 4, true );
        }
    $hashed = time() . ':' . $wp_hasher->HashPassword( $key );
    return $hashed;

    }


 /**
 * Get booking-code based on start date and item-id
 *
 * @return array
 */   
    public function get_booking_code_id( $date_start, $item_id ) {
    	
    	global $wpdb;

    	// get booking_code-id fromt codes database
    	 $sqlresult = $wpdb->get_results($wpdb->prepare(
    	 	"
    	 	SELECT id AS booking_code_id
    	 	FROM " . $this->table_codes . " 
    	 	WHERE booking_date = '%s' AND item_id = '%s'
    	 	", 
    	 	$date_start, $item_id), ARRAY_A); // get dates from 
    	 
    	 // @TODO: Insert check an error-handling if result-numer > 1

    	 return $sqlresult[0]['booking_code_id'];

    }

 /**
 * Get bookinc-code text
 *
 * @return array
 */   
    public function get_code( $code_id ) {
    	
    	global $wpdb;

		$sqlresult = $wpdb->get_row("SELECT * FROM $this->table_codes WHERE id = $code_id", ARRAY_A);

    	return $sqlresult['bookingcode'];

    }


 /**
 * Get item-data title
 *
 * @return array
 */   
    public function get_item( $posts_id ) {
    	
    	global $wpdb;

		$sqlresult = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE id = $posts_id", ARRAY_A);

		$item['item_title'] = $sqlresult['post_title'];

    	return $item['item_title'];

    }

 /**
 * Get location-data
 *
 * @return array
 */   
    public function get_location( $posts_id ) {
    	
    	global $wpdb;

		$sqlresult_posts = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE id = $posts_id", ARRAY_A);

		$item['location_title'] = $sqlresult_posts['post_title'];

		//get meta-data
		$sqlresult_meta = $wpdb->get_row("SELECT * FROM $wpdb->postmeta WHERE post_id = $posts_id AND meta_key = 'commons-booking_location_contactinformation'", ARRAY_A);

		$item['location_contactinformation'] = $sqlresult_meta['meta_value'];

    	return $item;

    }


 /**
 * Get a list of all booked days
 *
 * @return array
 */  
public function get_booked_days( $item_id, $status= 'confirmed' ) {
    
    global $wpdb;

    // get booking_code-id fromt codes database
     $sqlresult = $wpdb->get_results($wpdb->prepare(
        "
        SELECT date_start, date_end
        FROM " . $this->table_bookings . " 
        WHERE item_id = '%s' AND status = '%s'
        ", 
        $item_id, $status), ARRAY_A); // get dates from 
     
     $booked_days = array();


     foreach ($sqlresult as $date) {

        $datediff = strtotime( $date['date_end'] ) - strtotime( $date['date_start'] );
        $datediff = floor( $datediff / ( 60*60*24 ));
  
        for($i = 0; $i < $datediff + 1; $i++){
            $thedate = date("Y-m-d", strtotime( $date['date_start'] . ' + ' . $i . 'day'));
            array_push( $booked_days,  date( 'Y-m-d', strtotime($thedate)) );
        }
     }
     return $booked_days;

}


 /**
 * Store all booking relevant data into booking-table, set status pending. Return booking_id
 *
 * @return id
 */   
    public function create_booking( $date_start, $date_end, $item_id ) {
    	
    	global $wpdb;

    	// get relevant dat
        $code_id = $this->get_booking_code_id( $date_start, $item_id );
        $location_id = $this->get_booking_location_id( $date_start, $date_end, $item_id );    	

        	$wpdb->insert( 
    			$this->table_bookings, 
    			array( 
    				'date_start' 	=> $date_start , 
    				'date_end' 		=> $date_end,
    				'item_id' 		=> $item_id,
    				'user_id' 		=> $this->user_id, 
    				'code_id' 		=> $code_id,
    				'location_id' 	=> $location_id,
    				'booking_time' 	=> date('Y-m-d H:i:s'),
    				'status' => 'pending'
    			), 
    				array( 
    				'%s', 
    				'%s',
    				'%s',
    				'%s',
    				'%s',
    				'%s',
    				'%s',
    				'%s' 
    			) 
    		);

    		return $wpdb->insert_id;
	}



 /**
 * get all booking-dataa as array
 *
 * @return array
 */   
    public function get_booking( $booking_id) {
        
        global $wpdb;
        $table_bookings = $wpdb->prefix . 'cb_bookings';

        $sqlresult = $wpdb->get_row("SELECT * FROM $table_bookings WHERE id = $booking_id", ARRAY_A);          

        $booking_data['id']             = $sqlresult['id'];
        $booking_data['date_start']     = $sqlresult['date_start'];
        $booking_data['date_end']       = $sqlresult['date_end'];
        $booking_data['item_id']            = $sqlresult['item_id'];
        $booking_data['code_id']            = $sqlresult['code_id'];
        $booking_data['user_id']            = $sqlresult['user_id'];
        $booking_data['location_id']        = $sqlresult['location_id'];
        $booking_data['booking_time']   = $sqlresult['booking_time'];
        $booking_data['status']         = $sqlresult['status'];

        return $booking_data;
    } 

 /**
 * set status in booking table.
 * parameter: booking_id (id), status = new statu (e.g. "confirmed")
 *
 * @return array
 */   
    private function set_booking_status( $booking_id, $status ) {
        
        global $wpdb;
        $table_bookings = $wpdb->prefix . 'cb_bookings';

        $wpdb->query(
            "
            UPDATE $table_bookings 
            SET status = '" . $status . "'
            WHERE id = $booking_id
            "
        );

        return;

    }  
 /**
 * set hash in booking table.
 * parameter: booking_id (id), hash
 *
 * @return array
 */   
    private function set_booking_hash( $booking_id, $hash ) {
        
        global $wpdb;
        $table_bookings = $wpdb->prefix . 'cb_bookings';

        $wpdb->query(
            "
            UPDATE $table_bookings 
            SET hash = '" . $hash . "'
            WHERE id = $booking_id
            "
        );

        return;

    } 
    /**
     * Sends the confirm booking email.
     * 
     *@param $to, 
     */   
    public function send_mail( $to ) {

        $body_template = ( $this->email_messages['mail_confirmation_body'] );  // get template
        $subject_template = ( $this->email_messages['mail_confirmation_subject'] );  // get template
    	
        $headers = array('Content-Type: text/html; charset=UTF-8');

        $body = replace_template_tags( $body_template, $this->b_vars);
        $subject = replace_template_tags( $subject_template, $this->b_vars);

        wp_mail( $to, $subject, $body, $headers );

    }

    /**
    * Validation: The booked days.
    *
    * @param $item_id, $date_start, $date_end
    * @return bool
    */  
    private function validate_days ( $item_id, $date_start, $date_end ) {

        $booked_days = $this->get_booked_days ( $item_id, 'confirmed' );
        $count_days = count ( get_dates_between( $date_start, $date_end ));
        $max_days = $this->data->get_settings( 'bookings', 'bookingsettings_maxdays');
        $allow_closed = $this->data->get_settings( 'bookings', 'bookingsettings_allowclosed');
        
        if ( $allow_closed  == "on" ) {
            $max_days = $max_days + 5; // @TODO this should be a calculated number of days instead of a fixed 
        }  

        if ( in_array( $date_start, $booked_days ) OR in_array( $date_end, $booked_days ) OR $count_days > $max_days  ) {
            die ('Error: There was an error with your request.');
        } else {
            return TRUE;
        }
    } 
    /**
     * Check if entry already in database.
     * 
     * @return BOOL
     */  
    private function validate_creation ( ) {
        $pending_days = $this->get_booked_days ( $this->item_id, 'pending' );
        if ( in_array( $this->date_start, $pending_days ) OR in_array( $this->date_end, $pending_days ) ) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Encrypt the booking array.
     * 
     * @param array booking array
     *
     * @return string encoded
     */ 
    public function encrypt( $id ) {

        $id = base_convert($id, 10, 36); // Save some space
        $data = mcrypt_encrypt(MCRYPT_BLOWFISH, $this->secret, $id, 'ecb');
        $data = bin2hex($data);

        return $data;
    }  
    /**
     * Decrypt the booking array.
     * 
     * @param string encoded
     *
     * @return array decoded
     */   
    public function decrypt( $encrypted_id ) {

        $data = pack('H*', $encrypted_id); // Translate back to binary
        $data = mcrypt_decrypt(MCRYPT_BLOWFISH, $this->secret, $data, 'ecb');
        $data = base_convert($data, 36, 10);

        return $data;

    }     
    /**
     * Set all needed variabls for template.
     * 
     * @param BOOL include code 
     *
     */
    private function set_booking_vars( $include_code = FALSE ) {

        $this->item = $this->data->get_item( $this->item_id );
        $this->location = $this->data->get_location( $this->location_id );
        $this->user = $this->data->get_user( $this->user_id );

        $b_vars['date_start'] = date_i18n( get_option( 'date_format' ), strtotime( $this->date_start ) );
        $b_vars['date_end'] = date_i18n( get_option( 'date_format' ), strtotime( $this->date_end ) );
        $b_vars['item_name'] = get_the_title ($this->item_id );
        $b_vars['item_thumb'] = get_thumb( $this->item_id ); 
        $b_vars['item_content'] =  get_post_meta( $this->item_id, 'commons-booking_item_descr', TRUE  );
        $b_vars['location_name'] = get_the_title ($this->location_id );
        $b_vars['location_content'] = get_the_content( $this->location_id  );
        $b_vars['location_address'] = implode(', ', $this->location['address']);
        $b_vars['location_thumb'] = get_thumb( $this->location_id ); 
        $b_vars['location_contact'] = $this->location['contact']; 
        $b_vars['location_openinghours'] = $this->location['openinghours']; 

        $b_vars['page_confirmation'] = $this->settings->get_settings('pages', 'bookingconfirm_page_select');
        $b_vars['hash'] = $this->encrypt( $this->booking_id );
        
        $b_vars['site_email'] = $this->email_messages['mail_confirmation_sender']; 

        $b_vars['user_name'] = $this->user['name'];
        $b_vars['user_email'] = $this->user['email']; 

        $b_vars['first_name'] = $this->user['first_name']; 
        $b_vars['last_name'] = $this->user['last_name']; 

        $b_vars['user_address'] = $this->user['address'];    
        $b_vars['user_phone'] = $this->user['phone'];    
        if ( $include_code ) {
            $b_vars['code'] = $this->get_code( $this->booking['code_id'] ); 
            $b_vars['url'] = get_the_permalink() . '?booking=' . $this->hash; 
        }
        $this->b_vars = $b_vars;

    }
    /**
     * Main Booking function. 
     *
     */
    public function render_bookingreview( ) {
          if (is_user_logged_in() ) {

            $current_user = wp_get_current_user();

            $booking_messages = $this->settings->get_settings( 'messages' ); // get messages templates from settings page
            $this->email_messages = $this->settings->get_settings( 'mail' ); // get email templates from settings page

            if ( !empty($_POST['create']) && $_POST['create'] == 1) { // we create a new booking

               if ( !empty($_POST['date_start']) && !empty($_POST['date_end']) && !empty($_POST['timeframe_id']) && !empty($_POST['item_id']) && !empty($_POST['location_id']) && !empty($_POST['_wpnonce']) ) { // all needed vars available

                  if (! wp_verify_nonce($_POST['_wpnonce'], 'booking-review-nonce') ) die ('Your session has expired');

                    // DATA FROM FORM
                    $this->date_start = date( 'Y-m-d', ( sanitize_text_field( $_POST['date_start'] ) ) );  
                    $this->date_end = date( 'Y-m-d', ( sanitize_text_field( $_POST['date_end'] ) ) );  
                    $this->location_id = sanitize_text_field( $_POST['location_id'] );  
                    $this->item_id = sanitize_text_field( $_POST['item_id'] );  
                    $this->timeframe_id = sanitize_text_field( $_POST['timeframe_id'] );  
                    
                    $this->user_id = get_current_user_id();

                    // Set Variable for Template

                    // check if days are not already booked, and count <  maxdays
                    if ( $this->validate_days( $this->item_id, $this->date_start, $this->date_end )) {

                        $msg = ( $booking_messages['messages_booking_pleaseconfirm'] );  // get message part

                            $this->booking_id = $this->create_booking( $this->date_start, $this->date_end, $this->item_id);
                            $this->set_booking_vars();

                            return display_cb_message( $msg, $this->b_vars ) . cb_get_template_part( 'booking-review', $this->b_vars , true ) . cb_get_template_part( 'booking-review-submit', $this->b_vars , true );

                    } // end if validated - days

                } else { // not all needed vars present  
                   
                    echo __( 'Error: Variables missing', 'commons-booking' );

              } // end if all variables present
            } else if ( !empty($_GET['booking']) ) { // we confirm the booking 

                    // DATA FROM URL
                    $this->hash = sanitize_text_field ( $_GET['booking'] );

                    if (! ctype_alnum( $this->hash ) ) {
                        die ("Wrong Code");
                    }

                    $user_id = get_current_user_id();
                    $b_id = $this->decrypt( $this->hash );
                    
                    $this->booking = $this->get_booking( $b_id );

                    if ( ( $this->booking['user_id'] ==  $user_id ) ) { // user that booked or admin

                        $this->date_start = ( $this->booking['date_start'] ); 
                        $this->date_end = ( $this->booking['date_end'] ); 
                        $this->location_id = ( $this->booking['location_id'] );  
                        $this->item_id = ( $this->booking['item_id'] ); 
                        $this->user_id = ( $this->booking['user_id'] );
                        $this->booking_id = ( $b_id );

                        // Set Variable for Template
                        $this->set_booking_vars( TRUE );

                        // Finalise the booking
                        if ( $this->booking['status'] == 'pending' && $_GET['confirm'] == 1 ) { 
                            // check if status is pending and confirm = 1 

                            // Display the Message
                            $msg = ( $booking_messages[ 'messages_booking_confirmed' ] );  // get message   
                            
                            $this->set_booking_status( $this->booking['id'], 'confirmed' ); // set booking status to confirmed
                            $this->set_booking_hash( $this->booking['id'],  $this->hash ); // set booking hash
                            $this->send_mail( $this->user['email'] );

                            // PRINT: Booking review, Cancel Button
                            $message = display_cb_message( $msg, $this->b_vars );
                            return $message . cb_get_template_part( 'booking-review-code', $this->b_vars , true ) . cb_get_template_part( 'booking-review', $this->b_vars , true ) . cb_get_template_part( 'booking-review-cancel', $this->b_vars , true );

                        } elseif ( $this->booking['status'] == 'confirmed' && empty($_GET['cancel']) ) {
                            // booking is confirmed and we are not cancelling

                            // PRINT: Code, Booking review, Cancel Button
                            return cb_get_template_part( 'booking-review-code', $this->b_vars , true ) .  cb_get_template_part( 'booking-review', $this->b_vars , true ) . cb_get_template_part( 'booking-review-cancel', $this->b_vars , true );
  

                        } elseif ( $this->booking['status'] == 'confirmed' && !empty($_GET['cancel']) && $_GET['cancel'] == 1 ) {
                            // booking is confirmed and we are cancelling
                        
                            $msg = ( $booking_messages['messages_booking_canceled'] );  // get message                      

                            $this->set_booking_status( $this->booking['id'], 'canceled' ); // set booking status to canceled
                            return display_cb_message( $msg, $this->b_vars );
                        
                        } else {
                            // canceled booking, page refresh

                            $msg = __( 'Error: Booking not found', $this->prefix ); // @TODO: set canceled message
                            return display_cb_message( $msg, array(), FALSE );

                        }

                    } else {
                        die ('You have no right to view this page');
                    }


            } // end if confirm
          

          } else { // not logged in     
            
            return display_cb_message( "Error: You must be logged in to access this page.", array(), FALSE );
        
        } // end if logged in 

    }

}
?>