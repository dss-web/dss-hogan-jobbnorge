<?php
/**
 * Test double for the Hogan core module base class.
 *
 * Mirrors the public surface of \Dekode\Hogan\Module used by this plugin, so the
 * test suite does not depend on hogan-core being installed.
 *
 * @package Hogan
 */

declare( strict_types = 1 );

namespace Dekode\Hogan;

abstract class Module {

	/**
	 * Module name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Module extra helper fields.
	 *
	 * @var array
	 */
	public $helper_fields = [];

	/**
	 * Module field key prefix.
	 *
	 * @var string
	 */
	public $field_key;

	/**
	 * Module label.
	 *
	 * @var string
	 */
	public $label;

	/**
	 * Module output template.
	 *
	 * @var string
	 */
	public $template;

	/**
	 * Module raw field values.
	 *
	 * @var array
	 */
	public $raw_content;

	/**
	 * Module location in page layout.
	 *
	 * @var int
	 */
	public $counter;

	/**
	 * Path to plugin.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Constructor.
	 *
	 * @param string $path Path to plugin.
	 */
	public function __construct( $path = '' ) {
		$this->name      = strtolower( substr( strrchr( get_class( $this ), '\\' ), 1 ) );
		$this->field_key = 'hogan_module_' . $this->name;
		$this->path      = trailingslashit( $path );
	}

	/**
	 * Field definitions for module.
	 *
	 * @return array
	 */
	abstract protected function get_fields() : array;

	/**
	 * Map raw fields from acf to object variable.
	 *
	 * @param array $raw_content Content values.
	 * @param int   $counter     Module location in page layout.
	 * @return void
	 */
	public function load_args_from_layout_content( array $raw_content, int $counter = 0 ) {
		foreach ( $this->helper_fields as $helper_field ) {
			$this->{$helper_field} = $raw_content[ $helper_field ] ?? '';
		}

		$this->raw_content = $raw_content;
		$this->counter     = $counter;
	}

	/**
	 * Validate module content before template is loaded.
	 *
	 * @return bool
	 */
	abstract protected function validate_args() : bool;
}
