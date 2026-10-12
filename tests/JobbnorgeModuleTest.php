<?php
/**
 * Tests for the Hogan Jobbnorge module.
 *
 * @package Hogan
 */

declare( strict_types = 1 );

namespace DSS\Hogan\Jobbnorge\Tests;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Dekode\Hogan\Jobbnorge;

final class JobbnorgeModuleTest extends TestCase {

	/**
	 * The employer list as provided by the server integration.
	 *
	 * @return array
	 */
	private function employers() : array {
		return [
			[
				'label'    => 'Velg arbeidsgivere',
				'value'    => '',
				'disabled' => true,
			],
			[
				'label' => 'FIN',
				'value' => '1994',
			],
			[
				'label' => 'DSS',
				'value' => '1956',
			],
		];
	}

	/**
	 * Build a module instance.
	 *
	 * @return Jobbnorge
	 */
	private function module() : Jobbnorge {
		Functions\when( 'plugins_url' )->justReturn( 'https://example.test/plugin' );

		return new Jobbnorge();
	}

	/**
	 * Without server choices, the field allows entering employer IDs.
	 */
	public function testEmployerFieldFallsBackToTextInput() : void {
		$fields = $this->module()->get_fields();

		self::assertSame( 'text', $fields[0]['type'] );
		self::assertSame( 'feed', $fields[0]['name'] );
		self::assertArrayNotHasKey( 'multiple', $fields[0] );
		self::assertStringContainsString( 'separated by commas', $fields[0]['instructions'] );
	}

	/**
	 * Choices are populated at render time, because the employer list is
	 * registered during admin_enqueue_scripts and may not exist yet.
	 */
	public function testEmployerChoicesArePopulatedOnPrepareField() : void {
		$module = $this->module();
		$fields = $module->get_fields();

		self::assertSame( 'text', $fields[0]['type'] );
		self::assertArrayNotHasKey( 'choices', $fields[0] );
		self::assertTrue(
			has_filter( 'acf/prepare_field/key=' . $module->field_key . '_feed' )
		);

		Filters\expectApplied( 'jobbnorge_employers' )
			->zeroOrMoreTimes()
			->andReturn( $this->employers() );

		$prepared = $module->prepare_employer_field( $fields[0] );

		self::assertSame(
			[
				'1994' => 'FIN',
				'1956' => 'DSS',
			],
			$prepared['choices']
		);
		self::assertSame( 'select', $prepared['type'] );
		self::assertSame( 1, $prepared['multiple'] );
	}

	/**
	 * The disabled placeholder entry is never offered as a choice.
	 */
	public function testPlaceholderEntryIsNotAChoice() : void {
		Filters\expectApplied( 'jobbnorge_employers' )
			->zeroOrMoreTimes()
			->andReturn( $this->employers() );

		$prepared = $this->module()->prepare_employer_field( [] );

		self::assertArrayNotHasKey( '', $prepared['choices'] );
		self::assertNotContains( 'Velg arbeidsgivere', $prepared['choices'] );
	}

	/**
	 * An unusable employer list retains the text input fallback.
	 */
	public function testInvalidEmployerListYieldsNoChoices() : void {
		Filters\expectApplied( 'jobbnorge_employers' )
			->zeroOrMoreTimes()
			->andReturn( 'not-a-list' );

		$prepared = $this->module()->prepare_employer_field( [] );

		self::assertSame( 'text', $prepared['type'] );
		self::assertArrayNotHasKey( 'choices', $prepared );
	}

	/**
	 * ACF stores multi-selects as arrays; the API client expects a string.
	 */
	public function testMultipleSelectedEmployersAreNormalisedToACommaSeparatedString() : void {
		$module = $this->module();

		$module->load_args_from_layout_content(
			[
				'feed'        => [ '1994', '1956' ],
				'items'       => 5,
				'words'       => 20,
				'placeholder' => '',
			]
		);

		self::assertSame( '1994,1956', $module->employer_ids );
	}

	/**
	 * Legacy layouts stored a single feed URL string, which must still load.
	 */
	public function testLegacyStringValueIsPreserved() : void {
		$module     = $this->module();
		$legacy_url = 'https://www.jobbnorge.no/apps/joblist/JobListBuilder.ashx?id=b0c9c2bf-37da-4278-97a2-d0d397184bb6&amp;trid=1';

		$module->load_args_from_layout_content(
			[
				'feed'        => $legacy_url,
				'items'       => 5,
				'words'       => 20,
				'placeholder' => '',
			]
		);

		self::assertSame( $legacy_url, $module->employer_ids );
	}

	/**
	 * Missing field values must not raise warnings.
	 */
	public function testMissingFieldValuesDefaultToEmptyStrings() : void {
		$module = $this->module();

		$module->load_args_from_layout_content( [] );

		self::assertSame( '', $module->employer_ids );
		self::assertSame( '', $module->items );
		self::assertSame( '', $module->words );
		self::assertSame( '', $module->placeholder );
	}

	/**
	 * A module without employers and without a placeholder renders nothing.
	 */
	public function testValidationFailsWithoutEmployersAndPlaceholder() : void {
		$module = $this->module();

		$module->load_args_from_layout_content( [] );

		self::assertFalse( $module->validate_args() );
	}

	/**
	 * A placeholder alone is enough to render the module.
	 */
	public function testValidationPassesWithPlaceholderOnly() : void {
		$module = $this->module();

		$module->load_args_from_layout_content( [ 'placeholder' => 'Ingen ledige stillinger.' ] );

		self::assertTrue( $module->validate_args() );
	}

	/**
	 * Selected employers are enough to render the module.
	 */
	public function testValidationPassesWithEmployersOnly() : void {
		$module = $this->module();

		$module->load_args_from_layout_content( [ 'feed' => [ '1994' ] ] );

		self::assertTrue( $module->validate_args() );
	}
}
