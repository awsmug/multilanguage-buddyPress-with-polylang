<?php

/**
 * Trait BPPL_Messages
 *
 * Adding messages to WP/BP for informing user.
 */
trait BPPL_WP_Messages {

    /**
     * All added messages
     *
     * @var array
     */
    private $messages = array();

    /**
     * Prefix for messages
     *
     * @var string
     */
    private $messages_prefix = null;

    /**
     * BPPL_Messages constructor.
     *
     * Running Actionhooks.
     */
    public function messages_init(){
        add_action( 'admin_notices', array( $this, 'admin_show_messages' ) );
    }

    /**
     * Adds a prefix text to all messages
     *
     * @param string $text Text to show
     */
    private function messages_prefix( $text ) {
        $this->messages_prefix = $text;
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
    public function message( $text, $type = 'error', $where = 'auto', $capability = 'none' ) {
        $this->messages[] = array (
            'text' => $text,
            'type' => $type,
        );

        return true;
    }

    /**
     * Showing admin messages
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

