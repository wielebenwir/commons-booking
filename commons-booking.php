<?php

/**
 * Commons Booking
 *
 *
 * @package   Commons Booking
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @license   GPL-2.0+
 * @link      http://www.wielebenwir.de
 * @copyright 2015 wielebenwir
 *
 * @wordpress-plugin
 * Plugin Name:       Commons Booking
 * Plugin URI:        http://www.wielebenwir.de/projekte/commons-booking
 * Description:       A wordpress plugin for management and booking of common goods. 
 * Version:           0.9.4.7
 * Author:            Florian Egermann
 * Author URI:        http://www.wielebenwir.de
 * Text Domain:       commons-booking
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * WordPress-Plugin-Boilerplate-Powered: v1.1.0
 */
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

/* ----------------------------------------------------------------------------*
 * Public-Facing Functionality
 * ---------------------------------------------------------------------------- */

/*
 * Load library for simple and fast creation of Taxonomy and Custom Post Type
 */
require_once( plugin_dir_path( __FILE__ ) . 'includes/CPT_Core/CPT_Core.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/Taxonomy_Core/Taxonomy_Core.php' );

/*
 * Load main public class
 */
require_once( plugin_dir_path( __FILE__ ) . 'public/class-commons-booking.php' );

/*
 * Load classes for items, comments, bookings...
 */
// Booking functionality
require_once( plugin_dir_path( __FILE__ ) . 'public/cb-bookings/class-cb-booking.php' );
// Items
require_once( plugin_dir_path( __FILE__ ) . 'public/cb-bookings/class-cb-public-items.php' );
// Data: Class for accessing all Data generated by the plugin
require_once( plugin_dir_path( __FILE__ ) . 'public/cb-bookings/class-cb-data.php' );
// Comments on Bookings
require_once( plugin_dir_path( __FILE__ ) . 'public/cb-bookings/class-cb-booking-comments.php' );
// Timeframe object
require_once( plugin_dir_path( __FILE__ ) . 'admin/cb-timeframes/cb-timeframes.php' );
// Users
require_once( plugin_dir_path( __FILE__ ) . 'admin/cb-users/class-cb-users.php' );
// Load the settings module
require_once( plugin_dir_path( __FILE__ ) . 'admin/cb-settings/cb-admin-settings.php' );

/*
 * Installers
 */
  // Codes: Classes for Install/Update functionality for database tables
require_once( plugin_dir_path( __FILE__ ) . 'admin/cb-codes/class-cb-codes-setup.php' );
// Bookings: Classes for Install/Update the database tables
require_once( plugin_dir_path( __FILE__ ) . 'admin/cb-bookings/class-cb-bookings-setup.php' );
// Users: Classes for Install/Update the database Tables

// TIMEFRAMES

/*
 * Helper Functions
 */
require_once( plugin_dir_path( __FILE__ ) . 'includes/commons-booking-helpers.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/array_column.php' );

/*
 * Load template system
 */
require_once( plugin_dir_path( __FILE__ ) . 'includes/template.php' );


/*
 * Load Widgets
 */
require_once( plugin_dir_path( __FILE__ ) . 'admin/cb-widgets/cb-user-widget.php' );
require_once( plugin_dir_path( __FILE__ ) . 'admin/cb-widgets/cb-category-widget.php' );
  


/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 */

register_activation_hook( __FILE__, array( 'Commons_Booking', 'single_activate' ) );
register_deactivation_hook( __FILE__, array( 'Commons_Booking', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Commons_Booking', 'get_instance' ) );

/* ----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 * ---------------------------------------------------------------------------- */
if ( is_admin() && (!defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-cb-admin.php' );
	add_action( 'plugins_loaded', array( 'Commons_Booking_Admin', 'get_instance' ) );
}
