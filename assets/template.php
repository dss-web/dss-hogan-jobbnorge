<?php
/**
 * Template for dss jobbnorge module
 *
 * $this is an instance of the Jobbnorge object.
 *
 * Available properties:
 * $this->feed (string) Jobbnorge feed url.
 * $this->items (int) Number of items.
 * $this->words (int) Number of word pr item.
 * $this->placeholder (string) Placeholder text.
 *
 * @package Hogan
 *
 * @author: Per Søderlind / DSS
 * @since: 29/09/2019
 */
declare( strict_types = 1 );
namespace Dekode\Hogan;

if ( ! defined( 'ABSPATH' ) || ! ( $this instanceof Jobbnorge ) ) {
	return; // Exit if accessed directly.
}

add_action(
	'wp_feed_options',
	function( &$feed, $url = null ) {
		if ( ! $url ) {
			$url = $feed->feed_url;
		}

		if ( false !== strstr( $url, 'jobbnorge' ) ) {

			require_once 'class-simplepie-sort-on-deafline.php';

			$feed = new \SimplePieSortOnDeadline();

			$feed->set_sanitize_class( '\WP_SimplePie_Sanitize_KSES' );
			// We must manually overwrite $feed->sanitize because SimplePie's
			// constructor sets it before we have a chance to set the sanitization class
			$feed->sanitize = new \WP_SimplePie_Sanitize_KSES();

			$feed->set_cache_class( '\WP_Feed_Cache' );
			$feed->set_file_class( '\WP_SimplePie_File' );

			$feed->set_feed_url( $url );
			$feed->set_cache_duration( apply_filters( 'wp_feed_cache_transient_lifetime', 12 * HOUR_IN_SECONDS, $url ) );
		}
	}
);

$feed_url        = ( isset( $this->feed ) ) ? $this->feed : '';
$number_of_items = ( isset( $this->items ) && is_numeric( $this->items ) ) ? $this->items : 5;
$number_of_words = ( isset( $this->words ) && is_numeric( $this->words ) ) ? $this->words : 20;
$placeholder     = ( isset( $this->placeholder ) ) ? $this->placeholder : '';

require_once ABSPATH . WPINC . '/feed.php';
require_once 'class-jobbnorge-item.php';

if ( function_exists( 'fetch_feed' ) && '' !== $feed_url ) {
	$feed = fetch_feed( $feed_url );
	if ( ! is_wp_error( $feed ) ) :
		$feed->set_item_class( '\Jobbnorge_Item' );
		$feed->init();
		$feed->handle_content_type();
		$limit = $feed->get_item_quantity( $number_of_items );
		$items = $feed->get_items( 0, $limit );
	endif;
} else {
	$items = [];
}

if ( empty( $items ) || ! is_array( $items ) ) {
	if ( ! empty( $placeholder ) ) {
		printf(
			'<div class="hogan-module hogan-module-text text-wrap"><div class="hogan-module-inner clearfix"><p>%s</p></div></div>',
			esc_textarea( $placeholder )
		);
	}
	return;
}

?>
<!--
<ul class="list-items card-type-large">
-->
	<?php
	echo apply_filters( 'dss/hogan/module/jobbnorge/html/wrapper/start', '<ul class="list-items card-type-large">' );

	foreach ( $items as $item ) {
		printf(
			apply_filters(
				'dss/hogan/module/jobbnorge/html/item',
				'<li class="list-item">
					<div class="column">
						<p>%1$s %2$s</p>
						<h3 class="entry-title"><a href="%3$s">%4$s</a></h3>
					 	<div class="entry-summary"><p>%5$s %6$s</p></div>
					</div>
				</a>
			</li>'
			),
			__( 'Due date:', 'dss-hogan-jobbnorge' ),
			esc_html( $item->get_jn_deadline() ),
			esc_url( $item->get_permalink() ),
			esc_html( $item->get_jn_title() ),
			wp_kses(
				wp_trim_words(
					$item->get_description(),
					$number_of_words,
					''
				),
				'strip'
			),
			sprintf( ' ... <a href="%s">%s</a>', esc_url( $item->get_permalink() ), __( 'Read more', 'dss-hogan-jobbnorge' ) )
		);
	}
	echo apply_filters( 'dss/hogan/module/jobbnorge/html/wrapper/end', '</ul>' );

	?>
<!--</ul>-->
