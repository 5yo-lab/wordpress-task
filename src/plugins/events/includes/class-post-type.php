<?php
/**
 * Event custom post type.
 *
 * @package Event_Listing
 * @since   1.0.0
 */
namespace Event_Listing;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the `event` post type.
 *
 * @since 1.0.0
 */
class Post_Type {

	/**
	 * Post type key.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public const POST_TYPE = 'event';

	/**
	 * Wire hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Register the Event post type.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'                  => __( 'Events', 'events' ),
			'singular_name'         => __( 'Event', 'events' ),
			'add_new'               => __( 'Add New', 'events' ),
			'add_new_item'          => __( 'Add New Event', 'events' ),
			'edit_item'             => __( 'Edit Event', 'events' ),
			'new_item'              => __( 'New Event', 'events' ),
			'view_item'             => __( 'View Event', 'events' ),
			'view_items'            => __( 'View Events', 'events' ),
			'search_items'          => __( 'Search Events', 'events' ),
			'not_found'             => __( 'No events found.', 'events' ),
			'not_found_in_trash'    => __( 'No events found in Trash.', 'events' ),
			'all_items'             => __( 'All Events', 'events' ),
			'archives'              => __( 'Event Archives', 'events' ),
			'attributes'            => __( 'Event Attributes', 'events' ),
			'insert_into_item'      => __( 'Insert into event', 'events' ),
			'uploaded_to_this_item' => __( 'Uploaded to this event', 'events' ),
			'featured_image'        => __( 'Event banner', 'events' ),
			'set_featured_image'    => __( 'Set event banner', 'events' ),
			'remove_featured_image' => __( 'Remove event banner', 'events' ),
			'use_featured_image'    => __( 'Use as event banner', 'events' ),
			'menu_name'             => __( 'Events', 'events' ),
		);

		$args = array(
			'labels'           => $labels,
			'description'      => __( 'Events listing.', 'events' ),
			'public'           => true,
			'hierarchical'     => false,
			'show_in_rest'     => true,
			'menu_position'    => 20,
			'menu_icon'        => 'dashicons-calendar-alt',
			'supports'         => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'has_archive'      => true,
			'rewrite'          => array(
				'slug'       => 'events',
				'with_front' => false,
			),
			'query_var'        => true,
			'delete_with_user' => false,
		);

		register_post_type( self::POST_TYPE, $args );
	}
}
