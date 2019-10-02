<?php
/**
 * Plugin Name: DSS Hogan Module: Jobbnorge
 * Plugin URI: https://github.com/dss-web/dss-hogan-jobbnorge
 * GitHub Plugin URI: https://github.com/dss-web/dss-hogan-jobbnorge
 * Description: DSS Jobbnorge Module for Hogan.
 * Version: 1.0.3
 * Author: Per Soderlind
 * Author URI: https://soderlind.no
 * License: GPL-3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * Text Domain: dss-hogan-jobbnorge
 * Domain Path: /languages/
 *
 * @package Hogan
 * @author Dekode
 */

declare( strict_types = 1 );

namespace DSS\Hogan\Jobbnorge;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\hogan_load_textdomain' );
add_action( 'hogan/include_modules', __NAMESPACE__ . '\\hogan_register_module' );
add_filter( 'hogan/module/jobbnorge/heading/enabled', '__return_true' );
/**
 * Register module text domain
 */
function hogan_load_textdomain() {
	\load_plugin_textdomain( 'dss-hogan-jobbnorge', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Register module in Hogan
 *
 * @param \Dekode\Hogan\Core $core Hogan Core instance.
 * @return void
 */
function hogan_register_module( \Dekode\Hogan\Core $core ) {
	require_once 'class-dss-jobbnorge.php';

	$core->register_module( new \Dekode\Hogan\Jobbnorge() );
}
