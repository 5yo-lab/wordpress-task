<?php
/**
 * Plugin bootstrap.
 *
 * @package Event_Listing
 * @since   1.0.0
 */
namespace Event_Listing;

defined('ABSPATH') || exit;

final class Plugin {

	private static ?Plugin $instance = null;

	private Post_Type $post_type;

	private Event_Fields $event_fields;

	private Registration $registration;

	public static function instance(): Plugin {
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		Registration_Table::register();

		$this->post_type = new Post_Type();
		$this->post_type->register();

		$this->event_fields = new Event_Fields();
		$this->event_fields->register();

		$this->registration = new Registration();
		$this->registration->register();
	}

	public static function activate(): void {
		$post_type = new Post_Type();
		$post_type->register_post_type();

		Registration_Table::create_table();
		update_option('events_db_version', Registration_Table::DB_VERSION);

		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
