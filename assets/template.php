<?php
/**
 * Template for dss feed module
 *
 * $this is an instance of the Jobbnorge object.
 *
 * Available properties:
 * $this->feed (array) Array containing all download items.
 * $this->preview_image (string) If a preview image should be displayed or a generic icon.
 *
 * @package Hogan
 */
declare( strict_types = 1 );
namespace Dekode\Hogan;

if ( ! defined( 'ABSPATH' ) || ! ( $this instanceof Jobbnorge ) ) {
	return; // Exit if accessed directly.
}


$feed_url        = ( isset( $this->feed ) ) ? $this->feed : '';
$number_of_items = ( isset( $this->items ) && is_numeric( $this->items ) ) ? $this->items : 5;
$number_of_words = ( isset( $this->words ) && is_numeric( $this->words ) ) ? $this->words : 20;


require_once ABSPATH . WPINC . '/feed.php';
require_once 'class-jobbnorge-item.php';

if ( function_exists( 'fetch_feed' ) ) {
	$feed = fetch_feed( $feed_url );
	if ( ! is_wp_error( $feed ) ) :
		$feed->set_item_class( '\Jobbnorge_Item' );
		$feed->init();
		$feed->set_output_encoding( 'UTF-8' );
		$feed->handle_content_type();
		$feed->set_cache_duration( HOUR_IN_SECONDS * 6 );
		$limit = $feed->get_item_quantity( $number_of_items );
		$items = $feed->get_items( 0, $limit );
	endif;
} else {
	echo '<!-- dss-hogan-jobbnorge: fetch_feed() not found -->';
	return;
}

if ( empty( $items ) || ! is_array( $items ) ) {
	return;
}
?>
<ul class="list-items card-type-large">
	<?php
	foreach ( $items as $item ) {
		printf(
			'<li class="list-item">
					<div class="column">
						<p>%1$s %2$s</p>
						<h3 class="entry-title"><a href="%3$s">%4$s</a></h3>
					 	<div class="entry-summary"><p>%5$s %6$s</p></div>
					</div>
				</a>
			</li>',
			__( 'Due date:', 'dss-hogan-jobbnorge' ),
			esc_html( $item->get_jn_deadline() ),
			esc_url( $item->get_permalink() ),
			esc_html( $item->get_jn_title() ),
			wp_trim_words( wp_kses( $item->get_jn_text(), [
				'br'  => [],
				'p'   => [],
				'div' => [],
			] ), $number_of_words, '' ),
			sprintf( ' ... <a href="%s">%s</a>', esc_url( $item->get_permalink() ), __( 'Read more', 'dss-hogan-jobbnorge' ) )
		);
	}
	?>
</ul>
