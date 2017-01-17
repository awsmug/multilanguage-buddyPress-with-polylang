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

require dirname( __FILE__ ) . '/lib/class-messages.php';
require dirname( __FILE__ ) . '/class-loader.php';


/**
 * Message function
 *
 * @return BPPL_Messages
 */
function bppl_messages() {
	$messages = BPPL_Messages::get_instance();
	$messages->prefix( 'BuddyPress Polylang: ' );
	return $messages;
}

// Loading includes and doing checks
BPPL_Loader::init();

/**
 * Plugin Superfunction
 *
 * @return BPPL_Manager
 */
function bppl(){
	return BPPL_Manager::get_instance();
}

// Getting it running!
bppl();