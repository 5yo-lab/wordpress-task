<?php
/**
 * Event custom fields.
 *
 * @package Event_Listing
 * @since   1.0.0
 */
namespace Event_Listing;

defined('ABSPATH') || exit;

class Event_Fields {

	public const DATE               = '_event_date';
	public const TYPE               = '_event_type';
	public const LOCATION           = '_event_location';
	public const URL                = '_event_url';
	public const MAXIMUM_ATTENDEES  = '_event_maximum_attendees';
	public const REGISTRATION_COUNT = '_event_registration_count';
	public const VIDEO              = '_event_video';
	public const LATITUDE           = '_event_latitude';
	public const LONGITUDE          = '_event_longitude';
	public const PLACE_ID           = '_event_place_id';

	private const EVENT_TYPES = array('online', 'physical');

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		add_action('init', array($this, 'register_meta'));
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
		add_action('save_post_' . Post_Type::POST_TYPE, array($this, 'save_meta'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
	}

	public function register_meta(): void {
		$auth = static function ($allowed, $meta_key, $post_id): bool {
			unset($allowed, $meta_key);
			return current_user_can('edit_post', $post_id);
		};

		register_post_meta(
			Post_Type::POST_TYPE,
			self::DATE,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array($this, 'sanitize_date'),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			Post_Type::POST_TYPE,
			self::TYPE,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => 'online',
				'sanitize_callback' => array($this, 'sanitize_type'),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			Post_Type::POST_TYPE,
			self::LOCATION,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			Post_Type::POST_TYPE,
			self::URL,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			Post_Type::POST_TYPE,
			self::MAXIMUM_ATTENDEES,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			Post_Type::POST_TYPE,
			self::REGISTRATION_COUNT,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			Post_Type::POST_TYPE,
			self::VIDEO,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => 0,
				'sanitize_callback' => array($this, 'sanitize_attachment_id'),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			Post_Type::POST_TYPE,
			self::LATITUDE,
			array(
				'type'              => 'number',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array($this, 'sanitize_coordinate'),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			Post_Type::POST_TYPE,
			self::LONGITUDE,
			array(
				'type'              => 'number',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array($this, 'sanitize_coordinate'),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			Post_Type::POST_TYPE,
			self::PLACE_ID,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => $auth,
			)
		);
	}

	public function enqueue_admin_assets(string $hook): void {
		if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
			return;
		}

		$screen = get_current_screen();
		if (!$screen || Post_Type::POST_TYPE !== $screen->post_type) {
			return;
		}

		wp_enqueue_media();

		$deps = array('jquery');

		if ($this->get_maps_api_key()) {
			wp_enqueue_script(
				'google-maps',
				add_query_arg(
					array(
						'key'       => $this->get_maps_api_key(),
						'libraries' => 'places',
					),
					'https://maps.googleapis.com/maps/api/js'
				),
				array(),
				null,
				true
			);
			$deps[] = 'google-maps';
		}

		wp_enqueue_script(
			'events-admin',
			EVENTS_URL . 'assets/js/admin.js',
			$deps,
			EVENTS_VERSION,
			true
		);

		wp_localize_script(
			'events-admin',
			'eventsAdmin',
			array(
				'selectVideoTitle' => __('Select video', 'events'),
				'useVideoLabel'    => __('Use video', 'events'),
				'viewVideoLabel'   => __('View selected video', 'events'),
			)
		);
	}

	public function add_meta_boxes(): void {
		add_meta_box(
			'event-details',
			__('Event details', 'events'),
			array($this, 'render_meta_box'),
			Post_Type::POST_TYPE,
			'side'
		);
	}

	public function render_meta_box(\WP_Post $post): void {
		wp_nonce_field('event_save_meta', 'event_meta_nonce');

		$date               = get_post_meta($post->ID, self::DATE, true);
		$type               = get_post_meta($post->ID, self::TYPE, true);
		$location           = get_post_meta($post->ID, self::LOCATION, true);
		$latitude           = get_post_meta($post->ID, self::LATITUDE, true);
		$longitude          = get_post_meta($post->ID, self::LONGITUDE, true);
		$place_id           = get_post_meta($post->ID, self::PLACE_ID, true);
		$url                = get_post_meta($post->ID, self::URL, true);
		$maximum_attendees  = get_post_meta($post->ID, self::MAXIMUM_ATTENDEES, true);
		$registration_count = get_post_meta($post->ID, self::REGISTRATION_COUNT, true);
		$video_id           = (int) get_post_meta($post->ID, self::VIDEO, true);
		$is_physical        = ('physical' === $type);
		?>
		<p>
			<label for="event_date"><?php esc_html_e('Event date', 'events'); ?></label>
			<input type="date" id="event_date" name="event_date" value="<?php echo esc_attr($date); ?>" class="widefat" />
		</p>

		<p>
			<label for="event_type"><?php esc_html_e('Event type', 'events'); ?></label>
			<select id="event_type" name="event_type" class="widefat">
				<option value=""><?php esc_html_e('Select type', 'events'); ?></option>
				<?php foreach (self::EVENT_TYPES as $event_type) : ?>
					<option value="<?php echo esc_attr($event_type); ?>" <?php selected($type, $event_type); ?>>
						<?php echo esc_html(ucfirst($event_type)); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<div id="event-location-fields" <?php echo $is_physical ? '' : 'style="display:none;"'; ?>>
			<p>
				<label for="event_location"><?php esc_html_e('Event location', 'events'); ?></label>
				<input type="text" id="event_location" name="event_location" value="<?php echo esc_attr($location); ?>" class="widefat" />
			</p>
			<input type="hidden" id="event_latitude" name="event_latitude" value="<?php echo esc_attr($latitude); ?>" />
			<input type="hidden" id="event_longitude" name="event_longitude" value="<?php echo esc_attr($longitude); ?>" />
			<input type="hidden" id="event_place_id" name="event_place_id" value="<?php echo esc_attr($place_id); ?>" />
		</div>

		<p>
			<label for="event_url"><?php esc_html_e('Event URL', 'events'); ?></label>
			<input type="url" id="event_url" name="event_url" value="<?php echo esc_attr($url); ?>" class="widefat" />
		</p>

		<p>
			<label for="event_maximum_attendees"><?php esc_html_e('Maximum attendees', 'events'); ?></label>
			<input type="number" id="event_maximum_attendees" name="event_maximum_attendees" value="<?php echo esc_attr($maximum_attendees); ?>" min="1" step="1" class="widefat" />
		</p>

		<p>
			<label for="event_registration_count"><?php esc_html_e('Registration count', 'events'); ?></label>
			<input type="number" id="event_registration_count" value="<?php echo esc_attr($registration_count); ?>" readonly class="widefat" />
			<small><?php esc_html_e('This value is managed automatically.', 'events'); ?></small>
		</p>

		<p><label><?php esc_html_e('Event video', 'events'); ?></label></p>
		<div id="event-video-preview">
			<?php if ($video_id) : ?>
				<?php $video_url = wp_get_attachment_url($video_id); ?>
				<?php if ($video_url) : ?>
					<p>
						<a href="<?php echo esc_url($video_url); ?>" target="_blank" rel="noopener">
							<?php esc_html_e('View selected video', 'events'); ?>
						</a>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<input type="hidden" id="event_video" name="event_video" value="<?php echo esc_attr($video_id); ?>" />
		<button type="button" class="button" id="event-video-select"><?php esc_html_e('Select video', 'events'); ?></button>
		<button type="button" class="button" id="event-video-remove" <?php echo $video_id ? '' : 'style="display:none;"'; ?>>
			<?php esc_html_e('Remove video', 'events'); ?>
		</button>
		<?php
	}

	public function save_meta(int $post_id): void {
		if (
			!isset($_POST['event_meta_nonce'])
			|| !is_string($_POST['event_meta_nonce'])
			|| !wp_verify_nonce(wp_unslash($_POST['event_meta_nonce']), 'event_save_meta')
		) {
			return;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		$raw_date = isset($_POST['event_date']) && is_string($_POST['event_date'])
			? wp_unslash($_POST['event_date'])
			: '';
		$date = $this->sanitize_date($raw_date);

		if ('' === $date) {
			delete_post_meta($post_id, self::DATE);
		} else {
			update_post_meta($post_id, self::DATE, $date);
		}

		$type = isset($_POST['event_type']) && is_string($_POST['event_type'])
			? $this->sanitize_type(wp_unslash($_POST['event_type']))
			: '';

		if (in_array($type, self::EVENT_TYPES, true)) {
			update_post_meta($post_id, self::TYPE, $type);
		} else {
			delete_post_meta($post_id, self::TYPE);
			$type = '';
		}

		if ('physical' === $type) {
			$location = isset($_POST['event_location']) && is_string($_POST['event_location'])
				? sanitize_text_field(wp_unslash($_POST['event_location']))
				: '';

			if ('' === $location) {
				delete_post_meta($post_id, self::LOCATION);
			} else {
				update_post_meta($post_id, self::LOCATION, $location);
			}

			$latitude = isset($_POST['event_latitude']) && is_numeric($_POST['event_latitude'])
				? $this->sanitize_coordinate(wp_unslash($_POST['event_latitude']))
				: null;

			if (null === $latitude) {
				delete_post_meta($post_id, self::LATITUDE);
			} else {
				update_post_meta($post_id, self::LATITUDE, $latitude);
			}

			$longitude = isset($_POST['event_longitude']) && is_numeric($_POST['event_longitude'])
				? $this->sanitize_coordinate(wp_unslash($_POST['event_longitude']))
				: null;

			if (null === $longitude) {
				delete_post_meta($post_id, self::LONGITUDE);
			} else {
				update_post_meta($post_id, self::LONGITUDE, $longitude);
			}

			$place_id = isset($_POST['event_place_id']) && is_string($_POST['event_place_id'])
				? sanitize_text_field(wp_unslash($_POST['event_place_id']))
				: '';

			if ('' === $place_id) {
				delete_post_meta($post_id, self::PLACE_ID);
			} else {
				update_post_meta($post_id, self::PLACE_ID, $place_id);
			}
		} else {
			delete_post_meta($post_id, self::LOCATION);
			delete_post_meta($post_id, self::LATITUDE);
			delete_post_meta($post_id, self::LONGITUDE);
			delete_post_meta($post_id, self::PLACE_ID);
		}

		$url = isset($_POST['event_url']) && is_string($_POST['event_url'])
			? esc_url_raw(wp_unslash($_POST['event_url']))
			: '';

		if ('' === $url) {
			delete_post_meta($post_id, self::URL);
		} else {
			update_post_meta($post_id, self::URL, $url);
		}

		$maximum_attendees = isset($_POST['event_maximum_attendees'])
			? absint($_POST['event_maximum_attendees'])
			: 0;

		if ($maximum_attendees > 0) {
			update_post_meta($post_id, self::MAXIMUM_ATTENDEES, $maximum_attendees);
		} else {
			delete_post_meta($post_id, self::MAXIMUM_ATTENDEES);
		}

		$video_id = isset($_POST['event_video']) ? absint($_POST['event_video']) : 0;

		if ($video_id > 0 && 'attachment' === get_post_type($video_id)) {
			update_post_meta($post_id, self::VIDEO, $video_id);
		} else {
			delete_post_meta($post_id, self::VIDEO);
		}
	}

	public function sanitize_date($value): string {
		$value = sanitize_text_field((string) $value);

		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
			return '';
		}

		return $value;
	}

	public function sanitize_type($value): string {
		$value = sanitize_key((string) $value);

		if (!in_array($value, self::EVENT_TYPES, true)) {
			return 'online';
		}

		return $value;
	}

	public function sanitize_attachment_id($value): int {
		$attachment_id = absint($value);

		if (!$attachment_id || 'attachment' !== get_post_type($attachment_id)) {
			return 0;
		}

		return $attachment_id;
	}

	public function sanitize_coordinate($value): ?float {
		if (!is_numeric($value)) {
			return null;
		}

		return round((float) $value, 7);
	}

	private function get_maps_api_key(): string {
		if (defined('EVENTS_GOOGLE_MAPS_API_KEY')) {
			return (string) EVENTS_GOOGLE_MAPS_API_KEY;
		}

		return '';
	}
}
