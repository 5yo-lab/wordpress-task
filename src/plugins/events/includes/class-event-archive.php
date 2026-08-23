<?php
namespace Event_Listing;

defined('ABSPATH') || exit;

class Event_Archive {

	public function register(): void {
		add_action('pre_get_posts', array($this, 'order_by_event_date'));
		add_filter('template_include', array($this, 'load_template'));
	}

	public function order_by_event_date(\WP_Query $query): void {
		if (is_admin() || !$query->is_main_query() || !$query->is_post_type_archive(Post_Type::POST_TYPE)) {
			return;
		}

		$query->set('meta_key', Event_Fields::DATE);
		$query->set('orderby', 'meta_value');
		$query->set('order', 'ASC');
	}

	public function load_template(string $template): string {
		if (!is_post_type_archive(Post_Type::POST_TYPE)) {
			return $template;
		}

		$file = EVENTS_PATH . 'templates/event-archive.php';
		if (file_exists($file)) {
			return $file;
		}

		return $template;
	}

	public static function get_map_url(int $post_id): string {
		$query = self::get_map_query($post_id);
		if ('' === $query) {
			return '';
		}

		return add_query_arg(
			array('api' => '1', 'query' => $query),
			'https://www.google.com/maps/search/'
		);
	}

	public static function render_map(int $post_id): string {
		$map_url = self::get_map_url($post_id);
		if ('' === $map_url) {
			return '';
		}

		if (!defined('EVENTS_GOOGLE_MAPS_API_KEY') || '' === EVENTS_GOOGLE_MAPS_API_KEY) {
			return '<p><a href="' . esc_url($map_url) . '" target="_blank" rel="noopener">' . esc_html__('View on Google Maps', 'events') . '</a></p>';
		}

		$embed_url = add_query_arg(
			array(
				'key' => EVENTS_GOOGLE_MAPS_API_KEY,
				'q'   => self::get_map_query($post_id),
			),
			'https://www.google.com/maps/embed/v1/place'
		);

		return wp_kses(
			'<iframe class="events-map-embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="' . esc_url($embed_url) . '" width="600" height="300" style="border:0;" allowfullscreen></iframe>',
			array(
				'iframe' => array(
					'class'            => true,
					'loading'          => true,
					'referrerpolicy'   => true,
					'src'              => true,
					'width'            => true,
					'height'           => true,
					'style'            => true,
					'allowfullscreen'  => true,
				),
			)
		);
	}

	private static function get_map_query(int $post_id): string {
		if ('physical' !== get_post_meta($post_id, Event_Fields::TYPE, true)) {
			return '';
		}

		$latitude = get_post_meta($post_id, Event_Fields::LATITUDE, true);
		$longitude = get_post_meta($post_id, Event_Fields::LONGITUDE, true);
		$location = get_post_meta($post_id, Event_Fields::LOCATION, true);

		if (is_numeric($latitude) && is_numeric($longitude)) {
			return $latitude . ',' . $longitude;
		}

		if ('' !== $location) {
			return (string) $location;
		}

		return '';
	}

	public static function get_google_calendar_url(int $post_id): string {
		$date = get_post_meta($post_id, Event_Fields::DATE, true);

		if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			return '';
		}

		$start = str_replace('-', '', $date);

		$end_date = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

		if (!$end_date) {
			return '';
		}
        
		$end = $end_date->modify('+1 day')->format('Ymd');

		$args = array(
			'action' => 'TEMPLATE',
			'text'   => get_the_title($post_id),
			'dates'  => $start . '/' . $end,
		);

		if ('physical' === get_post_meta($post_id, Event_Fields::TYPE, true)) {
			$location = get_post_meta($post_id, Event_Fields::LOCATION, true);
			if ($location) {
				$args['location'] = $location;
			}
		}

		return add_query_arg($args, 'https://calendar.google.com/calendar/render');
	}

	public static function get_outlook_calendar_url(int $post_id): string {
		$date = get_post_meta($post_id, Event_Fields::DATE, true);
		if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			return '';
		}

		$end_date = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
		if (!$end_date) {
			return '';
		}

		$args = array(
			'subject' => get_the_title($post_id),
			'startdt' => $date,
			'enddt'   => $end_date->modify('+1 day')->format('Y-m-d'),
			'allday'  => 'true',
		);

		if ('physical' === get_post_meta($post_id, Event_Fields::TYPE, true)) {
			$location = get_post_meta($post_id, Event_Fields::LOCATION, true);
			if ($location) {
				$args['location'] = $location;
			}
		}

		return add_query_arg($args, 'https://outlook.live.com/calendar/0/deeplink/compose');
	}
}
