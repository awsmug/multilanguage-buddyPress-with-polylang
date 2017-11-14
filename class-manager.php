<?php
/**
 * Class BPPL_Manager
 *
 * @since 1.0.0
 *
 * This class manages the objects which have to be started for plugin functionality
 */

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BPPL_Manager{

	/**
	 * Plugin object holder
	 *
	 * @since 1.0.0
	 *
	 * @var BPPL_Loader
	 */
	private $plugin = null;

	/**
	 * User object holder
	 *
	 * @since 1.0.0
	 *
	 * @var BPPL_User
	 */
	private $user = null;

	/**
	 * Polylang object holder
	 *
	 * @since 1.0.0
	 *
	 * @var BPPL_Polylang
	 */
	private $polylang = null;

	/**
	 * BuddyPress object holder
	 *
	 * @since 1.0.0
	 *
	 * @var BPPL_BuddyPress
	 */
	private $buddypress = null;

	/**
	 * Email object holder
	 *
	 * @since 1.0.0
	 *
	 * @var BPPL_BuddyPress_Emails
	 */
	private $buddypress_emails = null;

	/**
	 * Instance
	 *
	 * @since 1.0.0
	 *
	 * @var BPPL_Manager $instance ;
	 */
	protected static $instance = null;

	/**
	 * BuddyPress_Polylang constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->plugin = new BPPL_Loader();
		$this->user = new BPPL_User();
		$this->polylang = new BPPL_Polylang();
		$this->buddypress = new BPPL_BuddyPress();
		$this->buddypress_emails = new BPPL_BuddyPress_Emails();
	}

	/**
	 * Getting instance
	 *
	 * @since 1.0.0
	 *
	 * @return BPPL_Manager $instance
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * User object
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID
	 *
	 * @return BPPL_User
	 */
	public function user( $user_id = null ) {
		if( ! empty( $user_id ) ) {
			$this->user->set_user( $user_id );
		}
		return $this->user;
	}

	/**
	 * Polylang object
	 *
	 * @since 1.0.0
	 *
	 * @return BPPL_Polylang
	 */
	public function polylang() {
		return $this->polylang;
	}

	/**
	 * BuddyPress object
	 *
	 * @since 1.0.0
	 *
	 * @return BPPL_BuddyPress
	 */
	public function buddypress() {
		return $this->buddypress;
	}

	/**
	 * BuddyPress Emails object
	 *
	 * @since 1.0.0
	 *
	 * @return BPPL_BuddyPress_Emails
	 */
	public function buddypress_emails() {
		return $this->buddypress_emails;
	}
}