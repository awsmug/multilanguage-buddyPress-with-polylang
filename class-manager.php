<?php

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
}