<?php
/**
 * Plugin Name:       Events
 * Description:       Custom Event post type, fields, and listing.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.3
 * Text Domain:       events
 *
 * @package Event_Listing
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

define( 'EVENTS_VERSION', '1.0.0' );
define( 'EVENTS_FILE', __FILE__ );
define( 'EVENTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'EVENTS_URL', plugin_dir_url( __FILE__ ) );

require_once EVENTS_PATH . 'includes/class-post-type.php';
require_once EVENTS_PATH . 'includes/class-event-fields.php';
require_once EVENTS_PATH . 'includes/class-registration-table.php';
require_once EVENTS_PATH . 'includes/class-registration.php';
require_once EVENTS_PATH . 'includes/class-plugin.php';

Event_Listing\Plugin::instance();

register_activation_hook( EVENTS_FILE, array( Event_Listing\Plugin::class, 'activate' ) );
register_deactivation_hook( EVENTS_FILE, array( Event_Listing\Plugin::class, 'deactivate' ) );
