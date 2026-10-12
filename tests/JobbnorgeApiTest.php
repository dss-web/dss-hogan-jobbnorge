<?php
/**
 * Tests for the Jobbnorge API client.
 *
 * @package Hogan
 */

declare( strict_types = 1 );

namespace DSS\Hogan\Jobbnorge\Tests;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use DSS_Jobbnorge_API;

final class JobbnorgeApiTest extends TestCase {

	/**
	 * Temporary cache directory.
	 *
	 * @var string
	 */
	private $cache_dir;

	/**
	 * Create a unique cache directory for each test.
	 */
	protected function setUp() : void {
		parent::setUp();

		$this->cache_dir = sys_get_temp_dir() . '/dss-jobbnorge-' . uniqid( '', true );
		$this->stubHttpHelpers();
	}

	/**
	 * Remove the temporary cache directory.
	 */
	protected function tearDown() : void {
		if ( is_dir( $this->cache_dir ) ) {
			foreach ( (array) glob( $this->cache_dir . '/*' ) as $file ) {
				unlink( $file );
			}
			rmdir( $this->cache_dir );
		}

		parent::tearDown();
	}

	/**
	 * Route the file cache to the temporary directory.
	 */
	private function useFileCache() : void {
		Filters\expectApplied( 'jobbnorge_cache_path' )
			->zeroOrMoreTimes()
			->andReturn( $this->cache_dir );
	}

	/**
	 * An empty employer ID must not trigger a request.
	 */
	public function testReturnsErrorWhenEmployerIdIsEmpty() : void {
		Functions\expect( 'wp_remote_get' )->never();

		$jobs = ( new DSS_Jobbnorge_API( '' ) )->get_jobs();

		self::assertInstanceOf( \WP_Error::class, $jobs );
		self::assertSame( 'jobbnorge_invalid_employer', $jobs->get_error_code() );
	}

	/**
	 * A non-numeric employer ID must not trigger a request.
	 */
	public function testReturnsErrorWhenEmployerIdIsNotNumeric() : void {
		Functions\expect( 'wp_remote_get' )->never();

		$jobs = ( new DSS_Jobbnorge_API( 'not-an-id' ) )->get_jobs();

		self::assertInstanceOf( \WP_Error::class, $jobs );
		self::assertSame( 'jobbnorge_invalid_employer', $jobs->get_error_code() );
	}

	/**
	 * Every valid employer ID is sent as its own query argument.
	 */
	public function testBuildsUrlWithEveryEmployerId() : void {
		$requested_url = '';

		Functions\expect( 'wp_remote_get' )
			->once()
			->andReturnUsing(
				function ( $url ) use ( &$requested_url ) {
					$requested_url = $url;
					return $this->response( [ $this->job( 'FIN job', '01.12.2026' ) ] );
				}
			);

		( new DSS_Jobbnorge_API( '1994, 1956 ,abc,' ) )->get_jobs();

		self::assertSame(
			'https://publicapi.jobbnorge.no/v3/Jobs?employer=1994&employer=1956',
			$requested_url
		);
	}

	/**
	 * The legacy FIN feed URL keeps working after the API migration.
	 */
	public function testMapsLegacyFinFeedUrlToEmployerId() : void {
		$requested_url = '';

		Functions\expect( 'wp_remote_get' )
			->once()
			->andReturnUsing(
				function ( $url ) use ( &$requested_url ) {
					$requested_url = $url;
					return $this->response( [ $this->job( 'FIN job', '01.12.2026' ) ] );
				}
			);

		$legacy_url = 'https://www.jobbnorge.no/apps/joblist/JobListBuilder.ashx?id=b0c9c2bf-37da-4278-97a2-d0d397184bb6&amp;trid=1';

		$jobs = ( new DSS_Jobbnorge_API( $legacy_url ) )->get_jobs();

		self::assertSame(
			'https://publicapi.jobbnorge.no/v3/Jobs?employer=1994',
			$requested_url
		);
		self::assertSame( 'FIN job', $jobs[0]['title'] );
	}

	/**
	 * An unknown feed URL is rejected rather than silently requested.
	 */
	public function testReturnsErrorForUnmappedLegacyFeedUrl() : void {
		Functions\expect( 'wp_remote_get' )->never();

		$jobs = ( new DSS_Jobbnorge_API( 'https://www.jobbnorge.no/apps/joblist/JobListBuilder.ashx?id=unknown' ) )->get_jobs();

		self::assertInstanceOf( \WP_Error::class, $jobs );
	}

	/**
	 * Jobs are ordered by deadline, closest first.
	 */
	public function testSortsJobsByDeadlineClosestFirst() : void {
		Functions\expect( 'wp_remote_get' )
			->once()
			->andReturn(
				$this->response(
					[
						$this->job( 'Later', '20.12.2026' ),
						$this->job( 'Soonest', '01.02.2026' ),
						$this->job( 'Middle', '05.06.2026' ),
					]
				)
			);

		$jobs = ( new DSS_Jobbnorge_API( '1994' ) )->get_jobs();

		self::assertSame(
			[ 'Soonest', 'Middle', 'Later' ],
			array_column( $jobs, 'title' )
		);
	}

	/**
	 * Jobs without a usable deadline are listed last.
	 */
	public function testSortsJobsWithoutDeadlineLast() : void {
		Functions\expect( 'wp_remote_get' )
			->once()
			->andReturn(
				$this->response(
					[
						$this->job( 'No deadline', '' ),
						$this->job( 'Unparsable', 'in a while' ),
						$this->job( 'With deadline', '01.12.2026' ),
					]
				)
			);

		$jobs = ( new DSS_Jobbnorge_API( '1994' ) )->get_jobs();

		self::assertSame( 'With deadline', $jobs[0]['title'] );
	}

	/**
	 * Transport failures are returned and reported.
	 */
	public function testReturnsErrorOnTransportFailure() : void {
		$transport_error = new \WP_Error( 'http_request_failed', 'Connection refused' );

		Functions\expect( 'wp_remote_get' )->once()->andReturn( $transport_error );
		Actions\expectDone( 'dss/hogan/module/jobbnorge/api_error' )->once();

		$jobs = ( new DSS_Jobbnorge_API( '1994' ) )->get_jobs();

		self::assertSame( $transport_error, $jobs );
	}

	/**
	 * Error status codes are surfaced as a WP_Error.
	 */
	public function testReturnsErrorOnHttpErrorStatus() : void {
		Functions\expect( 'wp_remote_get' )
			->once()
			->andReturn( $this->rawResponse( 'Server error', 503 ) );

		Actions\expectDone( 'dss/hogan/module/jobbnorge/api_error' )->once();

		$jobs = ( new DSS_Jobbnorge_API( '1994' ) )->get_jobs();

		self::assertInstanceOf( \WP_Error::class, $jobs );
		self::assertSame( 'jobbnorge_http_error', $jobs->get_error_code() );
		self::assertStringContainsString( '503', $jobs->get_error_message() );
	}

	/**
	 * A malformed body is surfaced as a WP_Error.
	 */
	public function testReturnsErrorOnInvalidJson() : void {
		Functions\expect( 'wp_remote_get' )
			->once()
			->andReturn( $this->rawResponse( 'not json' ) );

		Actions\expectDone( 'dss/hogan/module/jobbnorge/api_error' )->once();

		$jobs = ( new DSS_Jobbnorge_API( '1994' ) )->get_jobs();

		self::assertInstanceOf( \WP_Error::class, $jobs );
		self::assertSame( 'jobbnorge_invalid_response', $jobs->get_error_code() );
	}

	/**
	 * A valid response without a jobs collection is rejected.
	 */
	public function testReturnsErrorWhenJobsCollectionIsMissing() : void {
		Functions\expect( 'wp_remote_get' )
			->once()
			->andReturn( $this->rawResponse( '{"message":"no jobs here"}' ) );

		Actions\expectDone( 'dss/hogan/module/jobbnorge/api_error' )->once();

		$jobs = ( new DSS_Jobbnorge_API( '1994' ) )->get_jobs();

		self::assertInstanceOf( \WP_Error::class, $jobs );
		self::assertSame( 'jobbnorge_invalid_response', $jobs->get_error_code() );
	}

	/**
	 * An empty jobs collection is a valid response.
	 */
	public function testReturnsEmptyArrayWhenThereAreNoJobs() : void {
		Functions\expect( 'wp_remote_get' )->once()->andReturn( $this->response( [] ) );

		$jobs = ( new DSS_Jobbnorge_API( '1994' ) )->get_jobs();

		self::assertSame( [], $jobs );
	}

	/**
	 * The configured cache directory is used for cache files.
	 */
	public function testWritesCacheFileToConfiguredPath() : void {
		$this->useFileCache();

		Functions\expect( 'wp_remote_get' )
			->once()
			->andReturn( $this->response( [ $this->job( 'Cached', '01.12.2026' ) ] ) );

		( new DSS_Jobbnorge_API( '1994' ) )->get_jobs();

		self::assertCount( 1, (array) glob( $this->cache_dir . '/*.php' ) );
	}

	/**
	 * A fresh cache file is reused instead of calling the API again.
	 */
	public function testUsesFreshFileCacheWithoutSecondRequest() : void {
		$this->useFileCache();

		Functions\expect( 'wp_remote_get' )
			->once()
			->andReturn( $this->response( [ $this->job( 'From API', '01.12.2026' ) ] ) );

		$api   = new DSS_Jobbnorge_API( '1994' );
		$first = $api->get_jobs();
		$again = $api->get_jobs();

		self::assertSame( 'From API', $first[0]['title'] );
		self::assertSame( $first, $again );
	}

	/**
	 * A cache file older than the cache duration is refreshed.
	 */
	public function testRefreshesExpiredFileCache() : void {
		$this->useFileCache();

		Filters\expectApplied( 'jobbnorge_cache_time' )
			->zeroOrMoreTimes()
			->andReturn( 60 );

		Functions\expect( 'wp_remote_get' )
			->twice()
			->andReturn(
				$this->response( [ $this->job( 'Stale', '01.12.2026' ) ] ),
				$this->response( [ $this->job( 'Refreshed', '01.12.2026' ) ] )
			);

		$api   = new DSS_Jobbnorge_API( '1994' );
		$first = $api->get_jobs();

		$cache_files = (array) glob( $this->cache_dir . '/*.php' );
		touch( $cache_files[0], time() - 61 );
		clearstatcache( true, $cache_files[0] );

		$refreshed = $api->get_jobs();

		self::assertSame( 'Stale', $first[0]['title'] );
		self::assertSame( 'Refreshed', $refreshed[0]['title'] );
	}

	/**
	 * A zero cache duration disables both reading and writing the cache.
	 */
	public function testDoesNotCacheWhenDurationIsZero() : void {
		$this->useFileCache();

		Filters\expectApplied( 'jobbnorge_cache_time' )
			->zeroOrMoreTimes()
			->andReturn( 0 );

		Functions\expect( 'set_transient' )->never();

		Functions\expect( 'wp_remote_get' )
			->twice()
			->andReturn( $this->response( [ $this->job( 'Uncached', '01.12.2026' ) ] ) );

		$api = new DSS_Jobbnorge_API( '1994' );
		$api->get_jobs();
		$api->get_jobs();

		self::assertSame( [], (array) glob( $this->cache_dir . '/*.php' ) );
	}

	/**
	 * Without a cache path the transient cache is used.
	 */
	public function testFallsBackToTransientWhenNoCachePathIsConfigured() : void {
		$cached = [ $this->job( 'From transient', '01.12.2026' ) ];

		Functions\when( 'get_transient' )->justReturn( $cached );
		Functions\expect( 'wp_remote_get' )->never();

		$jobs = ( new DSS_Jobbnorge_API( '1994' ) )->get_jobs();

		self::assertSame( $cached, $jobs );
	}

	/**
	 * The Hogan specific filter can override the shared cache duration.
	 */
	public function testHoganFilterOverridesSharedCacheDuration() : void {
		$this->useFileCache();

		Filters\expectApplied( 'jobbnorge_cache_time' )
			->zeroOrMoreTimes()
			->andReturn( 3600 );

		Filters\expectApplied( 'dss/hogan/module/jobbnorge/cache_duration' )
			->zeroOrMoreTimes()
			->andReturn( 0 );

		Functions\expect( 'wp_remote_get' )
			->twice()
			->andReturn( $this->response( [ $this->job( 'Uncached', '01.12.2026' ) ] ) );

		$api = new DSS_Jobbnorge_API( '1994' );
		$api->get_jobs();
		$api->get_jobs();

		self::assertSame( [], (array) glob( $this->cache_dir . '/*.php' ) );
	}

	/**
	 * The API endpoint can be replaced, for example in tests or staging.
	 */
	public function testApiUrlCanBeFiltered() : void {
		$requested_url = '';

		Filters\expectApplied( 'dss/hogan/module/jobbnorge/api_url' )
			->zeroOrMoreTimes()
			->andReturn( 'https://staging.test/v3/Jobs' );

		Functions\expect( 'wp_remote_get' )
			->once()
			->andReturnUsing(
				function ( $url ) use ( &$requested_url ) {
					$requested_url = $url;
					return $this->response( [] );
				}
			);

		( new DSS_Jobbnorge_API( '1994' ) )->get_jobs();

		self::assertSame( 'https://staging.test/v3/Jobs?employer=1994', $requested_url );
	}

	/**
	 * Additional legacy feed URLs can be mapped through the filter.
	 */
	public function testLegacyEmployerMapCanBeFiltered() : void {
		$requested_url = '';

		Filters\expectApplied( 'dss/hogan/module/jobbnorge/legacy_employer_map' )
			->zeroOrMoreTimes()
			->andReturn( [ 'aaaaaaaa-0000-0000-0000-000000000000' => '1956' ] );

		Functions\expect( 'wp_remote_get' )
			->once()
			->andReturnUsing(
				function ( $url ) use ( &$requested_url ) {
					$requested_url = $url;
					return $this->response( [] );
				}
			);

		$legacy_url = 'https://www.jobbnorge.no/apps/joblist/JobListBuilder.ashx?id=AAAAAAAA-0000-0000-0000-000000000000&trid=1';

		( new DSS_Jobbnorge_API( $legacy_url ) )->get_jobs();

		self::assertSame( 'https://publicapi.jobbnorge.no/v3/Jobs?employer=1956', $requested_url );
	}
}
