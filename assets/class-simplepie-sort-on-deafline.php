<?php
if ( ! class_exists( 'SimplePieSortOnDeadline' ) && defined( 'SIMPLE_NAMESPACE_JOBBNORGE' ) ) {
	/**
	 * Override the sort_items method.
	 *
	 * @link https://wordpress.stackexchange.com/a/87099/14546
	 */
	class SimplePieSortOnDeadline extends \SimplePie {
		/**
		 * Sort on deadline, closest first.
		 *
		 * @param \SimplePie $a
		 * @param \SimplePie $b
		 * @return boolean
		 */
		public static function sort_items( $a, $b ) {
			$a_date = $a->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'deadline' )[0]['data'] ?? '';
			$b_date = $b->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'deadline' )[0]['data'] ?? '';

			return $a_date >= $b_date;
		}
	}
}
