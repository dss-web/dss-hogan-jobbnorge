<?php
/**
 * Base test case.
 *
 * @package Hogan
 */

declare( strict_types = 1 );

namespace DSS\Hogan\Jobbnorge\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase {

	/**
	 * Set up Brain Monkey and the WordPress functions used by the plugin.
	 */
	protected function setUp() : void {
		parent::setUp();
		Monkey\setUp();

		Functions\stubTranslationFunctions();

		Functions\when( 'is_wp_error' )->alias(
			static function ( $thing ) {
				return $thing instanceof \WP_Error;
			}
		);

		Functions\when( 'untrailingslashit' )->alias(
			static function ( $value ) {
				return rtrim( (string) $value, '/\\' );
			}
		);

		Functions\when( 'trailingslashit' )->alias(
			static function ( $value ) {
				return rtrim( (string) $value, '/\\' ) . '/';
			}
		);

		Functions\when( 'wp_parse_url' )->alias(
			static function ( $url, $component = -1 ) {
				return parse_url( $url, $component );
			}
		);

		Functions\when( 'wp_mkdir_p' )->alias(
			static function ( $path ) {
				return is_dir( $path ) || mkdir( $path, 0777, true );
			}
		);

		Functions\when( 'home_url' )->justReturn( 'https://example.test/' );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\when( 'wp_json_encode' )->alias(
			static function ( $value ) {
				return json_encode( $value );
			}
		);
	}

	/**
	 * Tear down Brain Monkey.
	 */
	protected function tearDown() : void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Build a successful HTTP response array.
	 *
	 * @param array $jobs   Jobs returned by the API.
	 * @param int   $status HTTP status code.
	 * @return array
	 */
	protected function response( array $jobs, int $status = 200 ) : array {
		return [
			'response' => [ 'code' => $status ],
			'body'     => (string) json_encode( [ 'jobs' => $jobs ] ),
		];
	}

	/**
	 * Build a raw HTTP response array.
	 *
	 * @param string $body   Response body.
	 * @param int    $status HTTP status code.
	 * @return array
	 */
	protected function rawResponse( string $body, int $status = 200 ) : array {
		return [
			'response' => [ 'code' => $status ],
			'body'     => $body,
		];
	}

	/**
	 * Stub the HTTP response helpers for a given response.
	 *
	 * @param array $response Response array.
	 * @return void
	 */
	protected function stubHttpHelpers() : void {
		Functions\when( 'wp_remote_retrieve_response_code' )->alias(
			static function ( $response ) {
				return $response['response']['code'];
			}
		);

		Functions\when( 'wp_remote_retrieve_body' )->alias(
			static function ( $response ) {
				return $response['body'];
			}
		);
	}

	/**
	 * Build a job fixture.
	 *
	 * @param string $title    Job title.
	 * @param string $deadline Deadline in d.m.Y format.
	 * @return array
	 */
	protected function job( string $title, string $deadline ) : array {
		return [
			'title'      => $title,
			'deadline'   => $deadline,
			'link'       => 'https://www.jobbnorge.no/ledige-stillinger/stilling/1',
			'summary'    => 'Summary for ' . $title,
			'employerID' => 1994,
		];
	}
}
