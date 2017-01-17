<?php
/**
 * Created by PhpStorm.
 * User: wagesve
 * Date: 17.01.17
 * Time: 14:28
 */

class BPPL_Manager{

	/**
	 * @var BPPL_Loader
	 */
	private $plugin = null;

	/**
	 * @var BPPL_Messages
	 */
	private $messages = null;

	/**
	 * @var BPPL_Polylang
	 */
	private $polylang = null;

	/**
	 * @var BPPL_BuddyPress
	 */
	private $buddypress = null;

	/**
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
		$this->messages = new BPPL_Messages();
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

	/**
	 * Message object
	 *
	 * @return BPPL_Messages
	 */
	public function messages() {
		return $this->messages;
	}
}