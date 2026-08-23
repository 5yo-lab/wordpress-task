<?php
defined('ABSPATH') || exit;

use Event_Listing\Event_Archive;
use Event_Listing\Event_Fields;

get_header();
?>

<main class="events-archive">
	<h1><?php post_type_archive_title(); ?></h1>

	<?php if (have_posts()) : ?>
		<ul class="events-list">
			<?php while (have_posts()) : the_post(); ?>
				<?php
				$post_id = get_the_ID();
				$date = get_post_meta($post_id, Event_Fields::DATE, true);
				$type = get_post_meta($post_id, Event_Fields::TYPE, true);
				$location = get_post_meta($post_id, Event_Fields::LOCATION, true);
				$source_url = get_post_meta($post_id, Event_Fields::URL, true);
				$google_calendar_url = Event_Archive::get_google_calendar_url($post_id);
				$outlook_calendar_url = Event_Archive::get_outlook_calendar_url($post_id);
				?>
				<li class="events-list__item">
					<?php if (has_post_thumbnail()) : ?>
						<p><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('medium'); ?></a></p>
					<?php endif; ?>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

					<?php if ('physical' === $type) : ?>
						<?php if ($location) : ?>
							<p><?php echo esc_html($location); ?></p>
						<?php endif; ?>
						<?php echo Event_Archive::render_map($post_id); ?>
					<?php endif; ?>

					<?php if ($date) : ?>
						<p><?php echo esc_html($date); ?></p>
					<?php endif; ?>

					<?php if ($source_url) : ?>
						<p><a href="<?php echo esc_url($source_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('External source', 'events'); ?></a></p>
					<?php endif; ?>

					<?php if ($google_calendar_url) : ?>
						<p><a href="<?php echo esc_url($google_calendar_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Add to Google Calendar', 'events'); ?></a></p>
					<?php endif; ?>

					<?php if ($outlook_calendar_url) : ?>
						<p><a href="<?php echo esc_url($outlook_calendar_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Add to Outlook Calendar', 'events'); ?></a></p>
					<?php endif; ?>
				</li>
			<?php endwhile; ?>
		</ul>

		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e('No events found.', 'events'); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
