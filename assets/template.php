<?php
/**
 * Template for dss jobbnorge module
 *
 * $this is an instance of the Jobbnorge object.
 *
 * Available properties:
 * $this->employer_ids (string) Comma-separated Jobbnorge employer IDs.
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

$employer_ids    = ( isset( $this->employer_ids ) ) ? $this->employer_ids : '';
$number_of_items = ( isset( $this->items ) && is_numeric( $this->items ) ) ? $this->items : 5;
$number_of_words = ( isset( $this->words ) && is_numeric( $this->words ) ) ? $this->words : 20;
$placeholder     = ( isset( $this->placeholder ) ) ? $this->placeholder : '';
$items           = [];

require_once __DIR__ . '/class-jobbnorge-api.php';

if ( '' !== $employer_ids ) {
	$api  = new \DSS_Jobbnorge_API( $employer_ids );
	$jobs = $api->get_jobs();

	if ( ! is_wp_error( $jobs ) ) {
		$items = array_slice( $jobs, 0, (int) $number_of_items );
	}
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
		$title       = isset( $item['title'] ) ? (string) $item['title'] : '';
		$deadline    = isset( $item['deadline'] ) ? (string) $item['deadline'] : '';
		$permalink   = isset( $item['link'] ) ? (string) $item['link'] : '';
		$description = isset( $item['summary'] ) ? (string) $item['summary'] : '';

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
			esc_html( $deadline ),
			esc_url( $permalink ),
			esc_html( $title ),
			wp_kses(
				wp_trim_words(
					wp_strip_all_tags( $description ),
					$number_of_words,
					''
				),
				'strip'
			),
			sprintf( ' ... <a href="%s">%s</a>', esc_url( $permalink ), __( 'Read more', 'dss-hogan-jobbnorge' ) )
		);
	}
	echo apply_filters( 'dss/hogan/module/jobbnorge/html/wrapper/end', '</ul>' );

	?>
<!--</ul>-->
