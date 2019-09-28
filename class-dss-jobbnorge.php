<?php
/**
 * DSS Jobbnorge module class
 *
 * @package Hogan
 */

declare( strict_types = 1 );

namespace Dekode\Hogan;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\\Dekode\\Hogan\\Jobbnorge' ) && class_exists( '\\Dekode\\Hogan\\Module' ) ) {

	/**
	 * Simple Posts module class (WYSIWYG).
	 *
	 * @extends Modules base class.
	 */
	class Jobbnorge extends Module {

		/**
		 * Preview image
		 *
		 * @var string
		 */
		public $preview_image = 'off';

		/**
		 * URL of feed.
		 *
		 * @var $feed
		 */
		public $feed;

		/**
		 * Number of items.
		 *
		 * @var $items
		 */
		public $items;

		/**
		 * Number of words.
		 *
		 * @var $words
		 */
		public $words;

		/**
		 * Module constructor.
		 */
		public function __construct() {

			$this->label    = __( 'Jobbnorge', 'dss-hogan-jobbnorge' );
			$this->template = __DIR__ . '/assets/template.php';

			parent::__construct();
		}

		/**
		 * Enqueue module assets
		 */
		public function enqueue_assets() {
			wp_enqueue_script( 'dss-hogan-jobbnorge', plugins_url( '/assets/js/dss-hogan-jobbnorge.js', __FILE__ ), [ 'jquery' ], '1.2.0' );
			wp_enqueue_style( 'dss-hogan-jobbnorge', plugins_url( '/assets/css/dss-hogan-jobbnorge.css', __FILE__ ), [], '1.2.0' );

		}

		/**
		 * Field definitions for module.
		 *
		 * @return array $fields Fields for this module
		 */
		public function get_fields() : array {

			return [
				[
					'type'          => 'url',
					'key'           => $this->field_key . '_feed',
					'label'         => __( 'URL', 'dss-hogan-jobbnorge' ),
					'name'          => 'feed',
					'instructions'  => __( 'Add Jobb Norge feed URL', 'dss-hogan-jobbnorge' ),
					'allow_null'    => 0,
					'default_value' => '',
					'return_format' => 'value',
				],
				[
					'type'          => 'number',
					'key'           => $this->field_key . '_items',
					'label'         => __( 'Number of items', 'dss-hogan-jobbnorge' ),
					'name'          => 'items',
					'instructions'  => __( 'Max number of items display', 'dss-hogan-jobbnorge' ),
					'required'      => 0,
					'default_value' => apply_filters( 'dss/hogan/module/feed/items', 5 ),
				],
				[
					'type'          => 'number',
					'key'           => $this->field_key . '_words',
					'label'         => __( 'Max number of words', 'dss-hogan-jobbnorge' ),
					'name'          => 'words',
					'instructions'  => __( 'Max number of words per item', 'dss-hogan-jobbnorge' ),
					'required'      => 0,
					'default_value' => apply_filters( 'dss/hogan/module/feed/words', 20 ),

				],
			];

		}

		/**
		 * Map raw fields from acf to object variable.
		 *
		 * @param array $raw_content Content values.
		 * @param int   $counter Module location in page layout.
		 *
		 * @return void
		 */
		public function load_args_from_layout_content( array $raw_content, int $counter = 0 ) {

			parent::load_args_from_layout_content( $raw_content, $counter );

			$this->feed  = $raw_content['feed'];
			$this->items = $raw_content['items'];
			$this->words = $raw_content['words'];

		}

		/**
		 * Validate module content before template is loaded.
		 *
		 * @return bool Whether validation of the module is successful / filled with content.
		 */
		public function validate_args() : bool {
			return ! empty( $this->feed );
		}
	}
} // End if().
