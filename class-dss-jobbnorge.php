<?php
/**
 * DSS Jobbnorge module class
 *
 * @package Hogan
 *
 * @author: Per Søderlind / DSS
 * @since: 29/09/2019
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
		 * Comma-separated Jobbnorge employer IDs.
		 *
		 * @var $employer_ids
		 */
		public $employer_ids;

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
		 * Text to display when there are no jobs.
		 *
		 * @var $placeholder
		 */
		public $placeholder;

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
			wp_enqueue_script( 'dss-hogan-jobbnorge', plugins_url( '/assets/js/dss-hogan-jobbnorge.js', __FILE__ ), [ 'jquery' ], DSS_HOGAN_JOBBNORGE_VERSION );
			wp_enqueue_style( 'dss-hogan-jobbnorge', plugins_url( '/assets/css/dss-hogan-jobbnorge.css', __FILE__ ), [], DSS_HOGAN_JOBBNORGE_VERSION );

		}

		/**
		 * Field definitions for module.
		 *
		 * @return array $fields Fields for this module
		 */
		public function get_fields() : array {
			$employer_field_key = $this->field_key . '_feed';
			$employer_field     = [
				'type'          => 'text',
				'key'           => $employer_field_key,
				'label'         => __( 'Employer', 'dss-hogan-jobbnorge' ),
				'name'          => 'feed',
				'instructions'  => __( 'Add one or more Jobbnorge employer IDs, separated by commas.', 'dss-hogan-jobbnorge' ),
				'allow_null'    => 1,
				'default_value' => '',
				'return_format' => 'value',
			];

			add_filter( 'acf/prepare_field/key=' . $employer_field_key, [ $this, 'prepare_employer_field' ] );
			$employer_field = $this->prepare_employer_field( $employer_field );

			return [
				$employer_field,
				[
					'type'          => 'number',
					'key'           => $this->field_key . '_items',
					'label'         => __( 'Number of items', 'dss-hogan-jobbnorge' ),
					'name'          => 'items',
					'instructions'  => __( 'Max number of items display', 'dss-hogan-jobbnorge' ),
					'required'      => 0,
					'default_value' => apply_filters( 'dss/hogan/module/jobbnorge/items', 5 ),
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
				[
					'type'          => 'text',
					'key'           => $this->field_key . '_placeholder',
					'label'         => __( 'Placeholder', 'dss-hogan-jobbnorge' ),
					'name'          => 'placeholder',
					'instructions'  => __( 'Text to display when feed is empty', 'dss-hogan-jobbnorge' ),
					'required'      => 0,
					'default_value' => apply_filters( 'dss/hogan/module/feed/placeholder', 'Vi har ingen ledige stillinger nå.' ),

				],
			];

		}

		/**
		 * Populate employer choices immediately before ACF renders the field.
		 *
		 * @param array $field ACF field definition.
		 * @return array
		 */
		public function prepare_employer_field( array $field ) : array {
			$choices = $this->get_employer_choices();

			if ( empty( $choices ) ) {
				$field['type']         = 'text';
				$field['instructions'] = __( 'Add one or more Jobbnorge employer IDs, separated by commas.', 'dss-hogan-jobbnorge' );
				unset( $field['choices'], $field['multiple'], $field['ui'] );

				return $field;
			}

			$field['type']         = 'select';
			$field['instructions'] = __( 'Select one or more Jobbnorge employers.', 'dss-hogan-jobbnorge' );
			$field['choices']      = $choices;
			$field['multiple']     = 1;
			$field['ui']           = 1;

			return $field;
		}

		/**
		 * Get employer choices supplied by the Jobbnorge block integration.
		 *
		 * @return array
		 */
		private function get_employer_choices() : array {
			$employers = apply_filters( 'jobbnorge_employers', [] );
			$choices   = [];

			if ( ! is_array( $employers ) ) {
				return $choices;
			}

			foreach ( $employers as $employer ) {
				if (
					! is_array( $employer )
					|| empty( $employer['value'] )
					|| empty( $employer['label'] )
					|| ! ctype_digit( (string) $employer['value'] )
				) {
					continue;
				}

				$choices[ (string) $employer['value'] ] = (string) $employer['label'];
			}

			return $choices;
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

			$employer_ids = isset( $raw_content['feed'] ) ? $raw_content['feed'] : '';

			$this->employer_ids = is_array( $employer_ids ) ? implode( ',', $employer_ids ) : (string) $employer_ids;
			$this->items        = $raw_content['items'] ?? '';
			$this->words        = $raw_content['words'] ?? '';
			$this->placeholder  = $raw_content['placeholder'] ?? '';

		}

		/**
		 * Validate module content before template is loaded.
		 *
		 * @return bool Whether validation of the module is successful / filled with content.
		 */
		public function validate_args() : bool {
			return ! empty( $this->employer_ids ) || ! empty( $this->placeholder );
		}
	}
} // End if().
