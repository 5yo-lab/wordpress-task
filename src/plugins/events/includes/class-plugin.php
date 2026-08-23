<?php
/**
 * Plugin bootstrap.
 *
 * @package Event_Listing
 * @since   1.0.0
 */
namespace Event_Listing;

defined( 'ABSPATH' ) || exit;

/**
 * Loads plugin components.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var   Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Event post type handler.
	 *
	 * @since 1.0.0
	 * @var   Post_Type
	 */
	private Post_Type $post_type;

	/**
	 * Event custom fields.
	 *
	 * @since 1.0.0
	 * @var   Event_Fields
	 */
	private Event_Fields $event_fields;

	/**
	 * Get the shared instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hook components.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->post_type = new Post_Type();
		$this->post_type->register();

		$this->event_fields = new Event_Fields();
		$this->event_fields->register();
	}

	/**
	 * Register the CPT and flush permalinks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function activate(): void {
		$post_type = new Post_Type();
		$post_type->register_post_type();
		flush_rewrite_rules();
	}

	/**
	 * Flush permalinks on deactivate.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
