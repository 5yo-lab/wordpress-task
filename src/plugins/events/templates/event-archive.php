<?php
defined('ABSPATH') || exit;

get_header();
?>

<main class="events-archive">
	<h1><?php post_type_archive_title(); ?></h1>

	<?php if (have_posts()) : ?>
		<ul class="events-list">

			<?php while (have_posts()) : the_post(); ?>
				<li class="events-list__item">
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php
					$date = get_post_meta(get_the_ID(), Event_Listing\Event_Fields::DATE, true);
					if ($date) {
						echo '<p>' . esc_html($date) . '</p>';
					}
					?>
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