<?php
/**
 * Plugin Name: Multilanguage BuddyPress with Polylang
 * Plugin URI:  http://awesome.ug
 * Description: Getting BuddyPress and polylang together. Early first trying out version.
 * Author:      Awsome UG
 * Version:     0.1.0
 * Author URI:  http://awesome.ug
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require dirname( __FILE__ ) . '/class-loader.php';
BPPL_Loader::init();

/**
 * Plugin Superfunction
 *
 * @return BPPL_Manager
 */
function bppl(){
	return BPPL_Manager::get_instance();
}

bppl();