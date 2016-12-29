<?php

/**
 * Class BPPL_Messages
 *
 * Adding messages to WP/BP for informing user.
 */
class BPPL_Messages {

    /**
     * BPPL_Messages constructor.
     *
     * Running Actionhooks.
     */
    public function __construct(){
        add_action( 'plugins_loaded', array( __CLASS__, 'init_messages' ) );
    }

    /**
     * All added messages
     *
     * @var array
     */
    private static $messages = array();

    /**
     * All added admin messages
     *
     * @var array
     */
    private $messages_admin = array();

    /**
     * All added BuddyPress messages
     *
     * @var array
     */
    private $messages_bp = array();

    /**
     * Adds a message
     *
     * @param string $text Message text
     * @param string $type Possible values 'success', 'error', 'notice'.
     * @param string $where Possible values 'auto', 'admin' and 'frontend'.
     * @param string $capability Needed capability to show message
     *
     * @return bool
     */
    public static function message( $text, $type = 'error', $where = 'auto', $capability = 'none' ) {
        self::$messages[] = array (
            'text' => $text,
            'type' => $type,
            'where' => $where,
            'capability' => $capability
        );

        return true;
    }


    /**
     * Initializing and routing added messages
     */
    private function init_messages() {
        foreach( self::$messages AS $message ) {
            if( 'auto' === $message[ 'where' ] ) {
                // Todo: Adding capability check

                if( is_admin() ) {
                    $this->message_admin( $message[ 'text' ], $message[ 'type' ] );
                } else {
                    $this->message_bp( $message[ 'text' ], $message[ 'type' ] );
                }
            }

            /**
             * Adding other kind of messages
             *
             * @param array $message Message and message information.
             */
            do_action( 'bppl_init_message', $message );
        }

        if( count( $this->messages_admin ) > 0 ) {
            add_action( 'admin_notices', array( $this, 'admin_show' ) );
        }

        if( count( $this->messages_bp ) > 0 ) {
            add_action( 'bp_init', array( $this, 'bp_show' ) );
        }
    }

    /**
     * Adding a message to the admin messages queue
     *
     * @param string $text Text of message
     * @param string $type Possible values 'success', 'error', 'notice'.
     */
    private function message_admin( $text, $type = 'error' ) {
        $this->messages_admin[] = array(
            'text'  => $text,
            'type'  => $type
        );
    }

    /**
     * Adding a message to the bp messages queue
     *
     * @param string $text Text of message
     * @param string $type Possible values 'success', 'error', 'notice'.
     */
    private function message_bp( $text, $type = 'error' ) {
        $this->messages_bp[] = array(
            'text'  => $text,
            'type'  => $type
        );
    }

    /**
     * Showing admin messages
     */
    private function admin_show() {
        foreach( $this->messages_admin AS $message ) {
            echo '<div class="' . $message[ 'type' ]. '"><p>' . $message[ 'text' ] . '</p></div>';
        }
    }

    /**
     * Show BuddyPress messages
     */
    private function bp_show() {
        $html = '';

        foreach( $this->messages_bp AS $message ) {
            $html.= '<p>' . $message[ 'text' ] . '</p>';
        }

        bp_core_add_message( $message );
    }

    /**
     * Good for hiding functions the user should not see on autocomplete
     *
     * @param $name
     * @param $arguments
     * @return bool|mixed
     */
    public function __call( $name, $arguments ){
        switch( $name ) {
            case 'init_message':
                return call_user_func( $name, $arguments );
                break;
            default:
                return false;
                break;
        }
    }
}

/**
 * Adds a message
 *
 * @param string $text Message text
 * @param string $type Possible values 'success', 'error', 'notice'.
 * @param string $where Possible values 'auto', 'admin' and 'frontend'.
 * @param string $capability Needed capability to show message
 *
 * @return bool
 */
function bppl_message( $text, $type = 'error', $where = 'auto', $capability = 'none' ) {
    return BPPL_Messages::message( $text, $type, $where, $capability );
}