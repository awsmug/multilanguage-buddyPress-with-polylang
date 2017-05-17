<?php
/**
 * Class BPPL_Messages
 *
 * Adding messages to WP/BP for informing user.
 */
class BPPL_Messages {

	/**
	 * Instance
	 *
	 * @since 1.0.0
	 *
	 * @var BPPL_Messages $instance ;
	 */
	protected static $instance = null;

    /**
     * All added messages
     *
     * @since 1.0.0
     *
     * @var array
     */
    private $messages = array();

    /**
     * Prefix for messages
     *
     * @since 1.0.0
     *
     * @var string
     */
    private $messages_prefix = null;

	/**
	 * Getting instance
	 *
	 * @since 1.0.0
	 *
	 * @return BPPL_Messages $instance
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static;
		}

		return static::$instance;
	}

    /**
     * BPPL_Messages constructor.
     *
     * @since 1.0.0
     *
     * Running Actionhooks.
     */
    private function __construct(){
        add_action( 'admin_notices', array( $this, 'admin_show_messages' ) );
    }

    /**
     * Adds a prefix text to all messages
     *
     * @since 1.0.0
     *
     * @param string $text Text to show
     */
    public function prefix( $text ) {
        $this->messages_prefix = $text;
    }

    /**
     * Adds a message
     *
     * @since 1.0.0
     *
     * @param string $text Message text
     * @param string $type Possible values 'success', 'error', 'notice'.
     * @param string $where Possible values 'auto', 'admin' and 'frontend'.
     * @param string $capability Needed capability to show message
     *
     * @return bool
     */
    public function add( $text, $type = 'error', $where = 'auto', $capability = 'none' ) {
        $this->messages[] = array (
            'text' => $text,
            'type' => $type,
        );

        return true;
    }

    /**
     * Showing admin messages
     *
     * @since 1.0.0
     */
    public function admin_show_messages() {

        foreach( $this->messages AS $message ) {
            $message_text = '';
            if( ! empty( $this->messages_prefix ) ) {
                $message_text.= $this->messages_prefix;
            }

            $message_text.= $message[ 'text' ];

            echo '<div class="' . $message[ 'type' ]. '"><p>' . $message_text . '</p></div>';
        }
    }
}

