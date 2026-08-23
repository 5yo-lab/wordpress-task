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
}