<?php
/**
 * Front-end event registration.
 *
 * @package Event_Listing
 * @since   1.0.0
 */
namespace Event_Listing;

defined('ABSPATH') || exit;

class Registration {

	private const ACTION = 'events_register';

	public function register(): void {
		add_filter('the_content', array($this, 'append_form'));
		add_action('admin_post_' . self::ACTION, array($this, 'handle_submission'));
		add_action('admin_post_nopriv_' . self::ACTION, array($this, 'handle_submission'));
	}

	public function append_form(string $content): string {
		if (!is_singular(Post_Type::POST_TYPE) || !in_the_loop() || !is_main_query()) {
			return $content;
		}

		$event_id = get_the_ID();
		
		if (!$event_id) {
			return $content;
		}

		return $content . $this->render_notice() . $this->render_registration_block($event_id);
	}

	public function handle_submission(): void {
		$event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
		$redirect = $event_id ? get_permalink($event_id) : home_url('/');

		if (
			!isset($_POST['events_register_nonce'])
			|| !is_string($_POST['events_register_nonce'])
			|| !wp_verify_nonce(wp_unslash($_POST['events_register_nonce']), self::ACTION)
		) {
			$this->redirect_with_status($redirect, 'invalid');
		}

		if (!$this->is_valid_event($event_id)) {
			$this->redirect_with_status($redirect, 'invalid');
		}

		$name = isset($_POST['registrant_name']) && is_string($_POST['registrant_name'])
			? sanitize_text_field(wp_unslash($_POST['registrant_name']))
			: '';

		$email = isset($_POST['registrant_email']) && is_string($_POST['registrant_email'])
			? sanitize_email(wp_unslash($_POST['registrant_email']))
			: '';

		$seats = isset($_POST['registrant_seats']) ? absint($_POST['registrant_seats']) : 0;

		if ('' === $name || !is_email($email) || $seats < 1) {
			$this->redirect_with_status($redirect, 'invalid');
		}

		$max = $this->get_max_attendees($event_id);

		if ($max <= 0) {
			$this->redirect_with_status($redirect, 'unavailable');
		}

		$current = Registration_Table::get_seat_count($event_id);

		if (($current + $seats) > $max) {
			$this->redirect_with_status($redirect, 'full');
		}

		$insert_id = Registration_Table::insert($event_id, $name, $email, $seats);

		if (!$insert_id) {
			$this->redirect_with_status($redirect, 'error');
		}

		Registration_Table::sync_registration_count($event_id);

		$this->redirect_with_status($redirect, 'success');
	}

	private function render_registration_block(int $event_id): string {
		$max = $this->get_max_attendees($event_id);

		if ($max <= 0) {
			return $this->wrap_message(__('Registration is not available for this event.', 'events'));
		}

		if ($this->is_full($event_id, $max)) {
			return $this->wrap_message(__('Registration is closed. This event has reached maximum capacity.', 'events'));
		}

		$remaining = $max - Registration_Table::get_seat_count($event_id);
		ob_start();
		?>
		<div class="events-registration">

			<h2><?php esc_html_e('Register for this event', 'events'); ?></h2>

			<p>
				<?php
				printf(
					esc_html(_n('%d seat remaining.', '%d seats remaining.', $remaining, 'events')),
					(int) $remaining
				);
				?>
			</p>

			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<?php wp_nonce_field(self::ACTION, 'events_register_nonce'); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>" />
				<input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>" />

				<p>
					<label for="registrant_name"><?php esc_html_e('Name', 'events'); ?></label>
					<input type="text" id="registrant_name" name="registrant_name" required class="widefat" />
				</p>

				<p>
					<label for="registrant_email"><?php esc_html_e('Email', 'events'); ?></label>
					<input type="email" id="registrant_email" name="registrant_email" required class="widefat" />
				</p>

				<p>
					<label for="registrant_seats"><?php esc_html_e('Seats', 'events'); ?></label>
					<input type="number" id="registrant_seats" name="registrant_seats" value="1" min="1" max="<?php echo esc_attr($remaining); ?>" required class="widefat" />
				</p>

				<p>
					<button type="submit" class="button button-primary"><?php esc_html_e('Register', 'events'); ?></button>
				</p>
			</form>

		</div>
		<?php

		return (string) ob_get_clean();
	}

	private function render_notice(): string {
		if (!isset($_GET['events_registration'])) {
			return '';
		}

		$status = sanitize_key(wp_unslash($_GET['events_registration']));

		$messages = array(
			'success'     => __('Registration successful.', 'events'),
			'full'        => __('Not enough seats remaining for that request.', 'events'),
			'invalid'     => __('Please check your registration details and try again.', 'events'),
			'unavailable' => __('Registration is not available for this event.', 'events'),
			'error'       => __('Registration could not be saved. Please try again.', 'events'),
		);

		if (!isset($messages[$status])) {
			return '';
		}

		return $this->wrap_message($messages[$status]);
	}

	private function wrap_message(string $message): string {
		return '<div class="events-registration-notice"><p>' . esc_html($message) . '</p></div>';
	}

	private function is_valid_event(int $event_id): bool {
		if ($event_id <= 0) {
			return false;
		}

		$post = get_post($event_id);
		if (!$post || Post_Type::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status) {
			return false;
		}

		return true;
	}

	private function get_max_attendees(int $event_id): int {
		return absint(get_post_meta($event_id, Event_Fields::MAXIMUM_ATTENDEES, true));
	}

	private function is_full(int $event_id, int $max): bool {
		return Registration_Table::get_seat_count($event_id) >= $max;
	}

	private function redirect_with_status(string $url, string $status): void {
		wp_safe_redirect(
			add_query_arg(
				array('events_registration' => $status),
				$url
			)
		);

		exit;
	}
}
