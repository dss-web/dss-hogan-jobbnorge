<?php
/**
 * PHPUnit bootstrap.
 *
 * Loads the plugin against Brain Monkey so WordPress itself is never required.
 *
 * @package Hogan
 */

declare( strict_types = 1 );

require_once __DIR__ . '/../vendor/autoload.php';

define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'DSS_HOGAN_JOBBNORGE_VERSION', '1.2.0' );

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error test double.
	 */
	class WP_Error {
		/**
		 * Error code.
		 *
		 * @var string
		 */
		private $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Constructor.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 */
		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		/**
		 * Get the error code.
		 *
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * Get the error message.
		 *
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}
	}
}

if ( ! class_exists( '\\Dekode\\Hogan\\Module' ) ) {
	require_once __DIR__ . '/stubs/class-hogan-module.php';
}

require_once __DIR__ . '/../assets/class-jobbnorge-api.php';
require_once __DIR__ . '/../class-dss-jobbnorge.php';
