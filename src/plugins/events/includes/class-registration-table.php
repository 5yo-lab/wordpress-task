<?php
/**
 * Event registrations database table.
 *
 * @package Event_Listing
 * @since   1.0.0
 */
namespace Event_Listing;

defined('ABSPATH') || exit;

class Registration_Table {

	public const DB_VERSION = '1.0.0';

	public static function register(): void {
		add_action('plugins_loaded', array(__CLASS__, 'upgrade'));
	}

	public static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'event_registrations';
	}

	public static function upgrade(): void {
		if (self::DB_VERSION === get_option('events_db_version', '')) {
			return;
		}

		self::create_table();
		update_option('events_db_version', self::DB_VERSION);
	}

	public static function create_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = self::table_name();
		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id bigint(20) unsigned NOT NULL,
			name varchar(255) NOT NULL,
			email varchar(255) NOT NULL,
			seats smallint(5) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY event_id (event_id)
		) {$wpdb->get_charset_collate()};";

		dbDelta($sql);
	}

	/**
	 * @param int    $event_id Event post ID.
	 * @param string $name     Registrant name.
	 * @param string $email    Registrant email.
	 * @param int    $seats    Number of seats.
	 * @return int|false Insert ID or false on failure.
	 */
	public static function insert(int $event_id, string $name, string $email, int $seats = 1) {
		global $wpdb;

		$result = $wpdb->insert(
			self::table_name(),
			array(
				'event_id'   => $event_id,
				'name'       => $name,
				'email'      => $email,
				'seats'      => max(1, $seats),
				'created_at' => current_time('mysql'),
			),
			array('%d', '%s', '%s', '%d', '%s')
		);

		if (!$result) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	public static function get_seat_count(int $event_id): int {
		global $wpdb;

		$table = self::table_name();
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(seats), 0) FROM {$table} WHERE event_id = %d",
				$event_id
			)
		);

		return (int) $count;
	}

	public static function sync_registration_count(int $event_id): int {
		$count = self::get_seat_count($event_id);
		update_post_meta($event_id, Event_Fields::REGISTRATION_COUNT, $count);

		return $count;
	}
}
