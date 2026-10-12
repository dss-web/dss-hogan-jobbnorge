<?php
/**
 * Client for the public Jobbnorge API.
 *
 * @package Hogan
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DSS_Jobbnorge_API' ) ) {
	class DSS_Jobbnorge_API {
		/**
		 * Employer IDs.
		 *
		 * @var array
		 */
		private $employer_ids;

		/**
		 * Constructor.
		 *
		 * @param string $employer_ids Comma-separated employer IDs or a legacy feed URL.
		 */
		public function __construct( string $employer_ids ) {
			$employer_ids       = html_entity_decode( $employer_ids, ENT_QUOTES, 'UTF-8' );
			$legacy_employer_id = $this->get_legacy_employer_id( $employer_ids );

			if ( '' !== $legacy_employer_id ) {
				$employer_ids = $legacy_employer_id;
			}

			$this->employer_ids = array_values(
				array_filter(
					array_map( 'trim', explode( ',', $employer_ids ) ),
					static function( $employer_id ) {
						return ctype_digit( $employer_id ) && 0 < (int) $employer_id;
					}
				)
			);
		}

		/**
		 * Resolve a legacy JobListBuilder URL to an employer ID.
		 *
		 * @param string $value Employer IDs or legacy URL.
		 * @return string
		 */
		private function get_legacy_employer_id( string $value ) : string {
			if ( false === filter_var( $value, FILTER_VALIDATE_URL ) ) {
				return '';
			}

			$query = wp_parse_url( $value, PHP_URL_QUERY );
			if ( ! is_string( $query ) ) {
				return '';
			}

			parse_str( $query, $query_args );
			$builder_id = isset( $query_args['id'] ) ? strtolower( (string) $query_args['id'] ) : '';
			$map        = (array) apply_filters(
				'dss/hogan/module/jobbnorge/legacy_employer_map',
				[
					'b0c9c2bf-37da-4278-97a2-d0d397184bb6' => '1994',
				]
			);

			return isset( $map[ $builder_id ] ) ? (string) $map[ $builder_id ] : '';
		}

		/**
		 * Retrieve jobs.
		 *
		 * @return array|\WP_Error
		 */
		public function get_jobs() {
			if ( empty( $this->employer_ids ) ) {
				return new \WP_Error(
					'jobbnorge_invalid_employer',
					__( 'Add a valid Jobbnorge employer ID.', 'dss-hogan-jobbnorge' )
				);
			}

			$url            = $this->get_url();
			$cache_key      = 'dss_jobbnorge_' . md5( $url );
			$cache_duration = $this->get_cache_duration( $url );
			$cached_jobs    = 0 < $cache_duration ? $this->get_file_cache( $cache_key, $cache_duration ) : false;

			if ( false === $cached_jobs ) {
				$cached_jobs = 0 < $cache_duration ? get_transient( $cache_key ) : false;
			}

			if ( is_array( $cached_jobs ) ) {
				return $cached_jobs;
			}

			$response = wp_remote_get(
				$url,
				[
					'timeout' => 10,
					'headers' => [
						'Accept'     => 'application/json',
						'User-Agent' => 'DSSHoganJobbnorge/' . DSS_HOGAN_JOBBNORGE_VERSION . ' ' . home_url( '/' ),
					],
				]
			);

			if ( is_wp_error( $response ) ) {
				do_action( 'dss/hogan/module/jobbnorge/api_error', $response, $url );
				return $response;
			}

			$status_code = wp_remote_retrieve_response_code( $response );
			if ( 200 > $status_code || 300 <= $status_code ) {
				$error = new \WP_Error(
					'jobbnorge_http_error',
					sprintf(
						/* translators: %d: HTTP response status code. */
						__( 'Jobbnorge returned HTTP status %d.', 'dss-hogan-jobbnorge' ),
						$status_code
					)
				);
				do_action( 'dss/hogan/module/jobbnorge/api_error', $error, $url );
				return $error;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $data ) || ! isset( $data['jobs'] ) || ! is_array( $data['jobs'] ) ) {
				$error = new \WP_Error(
					'jobbnorge_invalid_response',
					__( 'Jobbnorge returned an invalid response.', 'dss-hogan-jobbnorge' )
				);
				do_action( 'dss/hogan/module/jobbnorge/api_error', $error, $url );
				return $error;
			}

			$jobs = $data['jobs'];
			usort( $jobs, [ $this, 'sort_by_deadline' ] );

			if ( 0 < $cache_duration ) {
				set_transient( $cache_key, $jobs, $cache_duration );
				$this->set_file_cache( $cache_key, $jobs );
			}

			return $jobs;
		}

		/**
		 * Read jobs from the configured file cache.
		 *
		 * @param string $cache_key      Cache key.
		 * @param int    $cache_duration Cache lifetime in seconds.
		 * @return array|false
		 */
		private function get_file_cache( string $cache_key, int $cache_duration ) {
			$cache_file = $this->get_cache_file( $cache_key );

			if ( false === $cache_file || ! is_readable( $cache_file ) ) {
				return false;
			}

			$modified_time = filemtime( $cache_file );
			if ( false === $modified_time || $modified_time + $cache_duration <= time() ) {
				return false;
			}

			$cached_jobs = include $cache_file;

			return is_array( $cached_jobs ) ? $cached_jobs : false;
		}

		/**
		 * Get the configured cache lifetime.
		 *
		 * @param string $url API URL.
		 * @return int
		 */
		private function get_cache_duration( string $url ) : int {
			$cache_duration = (int) apply_filters(
				'jobbnorge_cache_time',
				30 * MINUTE_IN_SECONDS
			);

			return (int) apply_filters(
				'dss/hogan/module/jobbnorge/cache_duration',
				$cache_duration,
				$url
			);
		}

		/**
		 * Write jobs to the configured file cache.
		 *
		 * @param string $cache_key Cache key.
		 * @param array  $jobs      Jobs to cache.
		 * @return void
		 */
		private function set_file_cache( string $cache_key, array $jobs ) : void {
			$cache_file = $this->get_cache_file( $cache_key, true );

			if ( false === $cache_file ) {
				return;
			}

			$cache_data = "<?php\nreturn " . var_export( $jobs, true ) . ";\n";
			$result     = file_put_contents( $cache_file, $cache_data, LOCK_EX );

			if ( false === $result ) {
				do_action( 'dss/hogan/module/jobbnorge/cache_error', $cache_file );
			}
		}

		/**
		 * Get the configured cache file path.
		 *
		 * @param string $cache_key Cache key.
		 * @param bool   $create    Whether to create the cache directory.
		 * @return string|false
		 */
		private function get_cache_file( string $cache_key, bool $create = false ) {
			$cache_path = (string) apply_filters(
				'jobbnorge_cache_path',
				''
			);

			if ( '' === $cache_path ) {
				return false;
			}

			$cache_path = untrailingslashit( $cache_path );
			if ( $create && ! file_exists( $cache_path ) && ! wp_mkdir_p( $cache_path ) ) {
				do_action( 'dss/hogan/module/jobbnorge/cache_error', $cache_path );
				return false;
			}

			if ( ! is_dir( $cache_path ) || ! is_writable( $cache_path ) ) {
				return false;
			}

			return trailingslashit( $cache_path ) . $cache_key . '.php';
		}

		/**
		 * Sort jobs by deadline, closest first.
		 *
		 * @param array $first First job.
		 * @param array $second Second job.
		 * @return int
		 */
		public function sort_by_deadline( array $first, array $second ) : int {
			$first_timestamp  = $this->get_deadline_timestamp( $first );
			$second_timestamp = $this->get_deadline_timestamp( $second );

			if ( $first_timestamp === $second_timestamp ) {
				return 0;
			}

			return $first_timestamp < $second_timestamp ? -1 : 1;
		}

		/**
		 * Build the API URL.
		 *
		 * @return string
		 */
		private function get_url() : string {
			$base_url = (string) apply_filters(
				'dss/hogan/module/jobbnorge/api_url',
				'https://publicapi.jobbnorge.no/v3/Jobs'
			);
			$params   = [];

			foreach ( $this->employer_ids as $employer_id ) {
				$params[] = 'employer=' . rawurlencode( $employer_id );
			}

			return $base_url . '?' . implode( '&', $params );
		}

		/**
		 * Get a sortable deadline timestamp.
		 *
		 * @param array $job Job data.
		 * @return int
		 */
		private function get_deadline_timestamp( array $job ) : int {
			if ( empty( $job['deadline'] ) ) {
				return PHP_INT_MAX;
			}

			$date = \DateTime::createFromFormat( '!d.m.Y', (string) $job['deadline'] );

			return false === $date ? PHP_INT_MAX : $date->getTimestamp();
		}
	}
}
