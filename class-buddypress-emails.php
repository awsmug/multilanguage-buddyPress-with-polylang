<?php
/**
 * Class BuddyPress_Polylang_Emails
 *
 * @since 1.0.0
 *
 * This class contains the functionalities to install and send out emails in the correct language
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BPPL_BuddyPress_Emails {

	/**
	 * All languages added by Polylang
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $languages = array();

	/**
	 * Locale for setting up emails
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $temp_locale = null;

	/**
	 * Locales which have to be reloaded on email sending
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $reload_locales = array();

	/**
	 * Post language relationships
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $post_lang_rel = array();

	/**
	 * Current recipient to send out emails
	 *
	 * @since 1.0.0
	 *
	 * @var null|WP_User
	 */
	private $current_recipient = null;

	/**
	 * BuddyPress_Polylang constructor
	 *
	 * @since 1.0.0
	 */
	final public function __construct() {
		add_action( 'bp_core_install_emails', array( $this, 'reinstall_bp_emails' ), 100 );

		add_filter( 'pll_get_post_types', array( $this, 'add_post_type_slug' ) );
		add_filter( 'pll_get_taxonomies', array( $this, 'add_taxonomy' ) );

		add_action( 'bp_send_email', array( $this, 'bp_get_recipient' ), 5, 3 );
		add_filter( 'bp_send_email', array( $this, 'replace_email_content' ), 10, 4 );

		add_filter( 'bp_get_email_args', array( $this, 'bp_set_email_args' ) );
	}

	/**
	 * Installing all emails of the Polylang added languages
	 *
	 * @since 1.0.0
	 */
	public function reinstall_bp_emails() {
		// Just add this one time!
		remove_action( 'bp_core_install_emails', array( $this, 'reinstall_bp_emails' ), 100 );

		$this->delete_emails();
		$locales = pll_languages_list( array( 'fields' => 'locale' ) );

		add_filter( 'plugin_locale', array( $this, 'set_temporary_locale' ) );
		unload_textdomain( 'buddypress' );
		do_action( 'bppl_unload_plugin_textdomain' );

		foreach ( $locales AS $locale ) {
			$this->temp_locale = $locale;

			load_plugin_textdomain( 'buddypress' );
			do_action( 'bppl_load_plugin_textdomain' );


			$installed = $this->install_emails( $locale );

			if ( is_wp_error( $installed ) ) {
				bppl_messages()->add( $installed->get_error_message() );
				break;
			}

			unload_textdomain( 'buddypress' );
			do_action( 'bppl_unload_plugin_textdomain' );
		}

		remove_filter( 'plugin_locale', array( $this, 'set_temporary_locale' ) );
		load_plugin_textdomain( 'buddypress' );
		do_action( 'bppl_load_plugin_textdomain' );

		$this->post_lang_rel = apply_filters( 'bppl_post_lang_rel', $this->post_lang_rel );

		// Saving relations between posts
		foreach ( $this->post_lang_rel AS $post_lang_rel ) {
			$posts = array();
			foreach ( $post_lang_rel AS $lang => $post_id ) {
				if ( is_wp_error( $lang ) ) {
					bppl()->message( $lang->get_error_message() );
					break;
				}

				$posts[ $lang ] = $post_id;
			}

			pll_save_post_translations( $posts );
		}
	}

	/**
	 * Needed in the moment, because buddypress term ID is the same in every language
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale
	 *
	 * @return boolean|WP_Error
	 */
	private function install_emails( $locale ) {
		$lang = bppl()->polylang()->get_lang_slug_by_locale( $locale );

		if ( is_wp_error( $lang ) ) {
			return $lang;
		}

		$defaults = array(
			'post_status' => 'publish',
			'post_type'   => bp_get_email_post_type(),
		);

		$emails       = bp_email_get_schema();
		$descriptions = bp_email_get_type_schema( 'description' );

		// Add these emails to the database.
		foreach ( $emails as $id => $email ) {
			$post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ) );
			if ( ! $post_id ) {
				continue;
			}
			pll_set_post_language( $post_id, $lang );

			$this->post_lang_rel[ $id ][ $lang ] = $post_id;

			$term_id = $id . '-' . $lang;

			$tt_ids = wp_set_object_terms( $post_id, $term_id, bp_get_email_tax_type() );
			foreach ( $tt_ids as $tt_id ) {
				$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
				wp_update_term( (int) $term->term_id, bp_get_email_tax_type(), array(
					'description' => $descriptions[ $id ],
				) );

				pll_set_term_language( $term->term_id, $lang );
			}
		}

		bp_update_option( 'bp-emails-unsubscribe-salt', base64_encode( wp_generate_password( 64, true, true ) ) );

		/**
		 * Fires after BuddyPress adds the posts for its emails.
		 *
		 * @since 1.0.0
		 */
		do_action( 'bp_core_install_emails' );

		/**
		 * Fires after BuddyPress adds the posts for its emails.
		 *
		 * @since 1.0.0
		 */
		do_action( 'bppl_core_install_emails', $locale );

		return true;
	}

	/**
	 * Deleting everything which was created before
	 *
	 * @since 1.0.0
	 */
	public function delete_emails() {
		$emails = get_posts( array(
			'fields'           => 'idsBP_Email_Translate',
			'post_status'      => 'publish',
			'post_type'        => bp_get_email_post_type(),
			'posts_per_page'   => - 1,
			'suppress_filters' => false,
		) );

		if ( $emails ) {
			foreach ( $emails as $email_id ) {
				wp_trash_post( $email_id );
			}
		}

		// Make sure we have no orphaned email type terms.
		$email_types = get_terms( bp_get_email_tax_type(), array(
			'fields'                 => 'ids',
			'hide_empty'             => false,
			'update_term_meta_cache' => false,
		) );

		if ( $email_types ) {
			foreach ( $email_types as $term_id ) {
				wp_delete_term( (int) $term_id, bp_get_email_tax_type() );
			}
		}
	}

	/**
	 * Setting the locale temporary
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale Actual locale
	 *
	 * @return string $locale Filtered locale
	 */
	public function set_temporary_locale( $locale ) {
		if ( empty( $this->temp_locale ) ) {
			return $locale;
		}

		return $this->temp_locale;
	}

	public function get_temporary_locale() {
		return $this->temp_locale;
	}

	/**
	 * Adding Email Post Type to Polylang translations
	 *
	 * @since 1.0.0
	 *
	 * @param array $post_types Array of post types Polylang knows
	 *
	 * @return array$post_types Filterd array of post types Polylang knows
	 */
	public function add_post_type_slug( $post_types ) {
		$post_types[ bp_get_email_post_type() ] = bp_get_email_post_type();

		return $post_types;
	}

	/**
	 * Adding Taxonomies to Polylang translations
	 *
	 * @since 1.0.0
	 *
	 * @param $taxonomies
	 *
	 * @return mixed
	 */
	public function add_taxonomy( $taxonomies ) {
		$taxonomies[ bp_get_email_tax_type() ] = bp_get_email_tax_type();

		return $taxonomies;
	}

	/**
	 * Setting up recipient
	 *
	 * @since 1.0.0
	 *
	 * @param BP_Email $email Email object for email to send out
	 * @param string $email_type Unique identifier for a particular type of email.
	 * @param string $to Either a email address, user ID, WP_User object,
	 *                   or an array containg the address and name.
	 */
	public function bp_get_recipient( $email, $email_type, $to ) {
		$user = $this->get_recipient_user( $to );
		$this->current_recipient = $user;
	}

	/**
	 * Setting up query args for getting posts in correct language
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Unfiltered query arguments
	 *
	 * @return array $args Filtered query arguments
	 */
	public function bp_set_email_args( $args ) {
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		// If no recipient is set, just ask for all languages
		if ( ! isset( $this->current_recipient ) ) {
			$languages = bppl()->polylang()->get_languages();

			$base_term = $args['tax_query'][0]['terms'];

			$terms = array();
			foreach ( $languages AS $language ) {
				$terms[] = $base_term . '-' . $language['lang'];
			}

			$args['numberposts']           = - 1;
			$args['tax_query'][0]['terms'] = $terms;

			return $args;
		}

		// Getting correct language
		$locale = get_user_meta( $this->current_recipient->ID, 'locale', true );

		if ( empty( $locale ) ) {
			$lang = bppl()->polylang()->get_default_lang();
		} else {
			$lang = bppl()->polylang()->get_lang_slug_by_locale( $locale );
		}

		$args['tax_query'][0]['terms'] = $args['tax_query'][0]['terms'] . '-' . $lang;

		return $args;
	}

	/**
	 * Replacing Email Content with correct language
	 *
	 * @since 1.0.0
	 *
	 * @param $email BP_Email       BuddyPress Email
	 * @param $email_type string    Email type
	 * @param $to mixed            Either a email address, user ID, WP_User object, or an array containg the address and name.
	 * @param $args array $args {
	 *     Optional. Array of extra parameters.
	 *     @type array $tokens Optional. Assocative arrays of string replacements for the email.
	 * }
	 */
	public function replace_email_content( &$email, $email_type, $to, $args ) {
		$user = $this->get_recipient_user( $to );

		if( false === $user ) {
			return;
		}

		$switched = false;
		if( ! bp_is_root_blog() ) {
			switch_to_blog( bp_get_root_blog_id() );
			$switched = true;
		}

		$this->load_user_locales( $user->ID );

		$new_email = bp_get_email( $email_type );
		if ( is_wp_error( $new_email ) ) {
			return;
		}

		$email = $new_email;

		$lang_page_ids = bppl()->buddypress()->get_directory_page_ids();

		$args['tokens'] = $this->replace_promote_to_string( $args['tokens'], $email_type, $user->ID );
		$args['tokens'] = apply_filters( 'bppl_email_tokens', $args['tokens'], $email_type, $user->ID );

		// From, subject, content are set automatically.
		$email->set_to( $user->ID );
		$email->set_tokens( $args['tokens'] );

		$sender_locale  = get_locale();
		$sender_lang    = bppl()->polylang()->get_lang_slug_by_locale( $sender_locale );
		$recipient_lang = bppl()->polylang()->get_lang_slug_by_locale( $this->temp_locale );

		if( $sender_lang === $recipient_lang ) {
			return;
		}

		$origin_domain  = bp_core_get_root_domain();
		
		//patch
		if (!is_string($sender_lang)) {     
		                     $sender_lang='de';
		}
		//patch
		
		
		
		$replace_domain = str_replace( '/' . $sender_lang, '/' . $recipient_lang, $origin_domain );

		$sender_page_ids = $lang_page_ids[ $sender_lang ];
		$recipient_page_ids = $lang_page_ids[ $recipient_lang ];

		foreach( $sender_page_ids AS $component => $page_id ) {
			$origin_url = get_permalink( $page_id );
			$replace_url = get_permalink(  $recipient_page_ids[ $component ] );

			$this->replace_email_links( $email, $origin_url, $replace_url );
		}

		$this->replace_email_links( $email, $origin_domain, $replace_domain );

		$this->reset_plugin_locales();

		if( $switched ) {
			restore_current_blog();
		}
	}

	/**
	 * Replacing promote to tokens, because used before
	 *
	 * @since 1.0.0
	 *
	 * @param array $tokens
	 * @param string $email_type
	 * @param int $user_id
	 *
	 * @return array $tokens
	 */
	private function replace_promote_to_string( $tokens, $email_type, $user_id ) {
		if( 'groups-member-promoted' !== $email_type ) {
			return $tokens;
		}

		$group_id = $tokens[ 'group.id' ];

		if ( groups_is_user_admin( $user_id, $group_id ) ) {
			$promoted_to = __( 'an administrator', 'buddypress' );
		} else {
			$promoted_to = __( 'a moderator', 'buddypress' );
		}

		$tokens[ 'promoted_to' ] = $promoted_to;

		return $tokens;
	}

	/**
	 * Getting recipient from BuddyPress $to
	 *
	 * @since 1.0.0
	 *
	 * @param $to mixed Either a email address, user ID, WP_User object, or an array containg the address and name.
	 *
	 * @return bool|false|WP_User $user_id
	 */
	private function get_recipient_user( $to ) {
		if ( is_object( $to ) ) {
			return $to;
		}

		if( is_int( $to ) ) {
			return get_user_by('id', $to );
		}

		if( is_string( $to ) ) {
			return get_user_by('email', $to );
		}

		return false;
	}

	/**
	 * Replacing links in Email
	 *
	 * @since 1.0.0
	 *
	 * @param $email BP_Email BuddyPress Email
	 * @param $link string Link which have to be replaced
	 * @param $replacement string Replacement
	 */
	private function replace_email_links( &$email, $link, $replacement ) {
		// Replacing links in tokens
		$tokens = $email->get_tokens();

		foreach ( $tokens AS $key => $token ) {
			if ( ! is_string( $token ) ) {
				continue;
			}

			$tokens[ $key ] = str_replace( $link, $replacement, $token );
		}

		$email->set_tokens( $tokens );

		// Replacing links in HTML and text content
		$html = str_replace( $link, $replacement, $email->get_content_html() );
		$text = str_replace( $link, $replacement, $email->get_content_plaintext() );

		$email->set_content_html( $html );
		$email->set_content_plaintext( $text );
	}

	/**
	 * Loading plugin locales for user
	 *
	 * @since 1.0.0
	 *
	 * @param $to int $user_id User ID
	 */
	private function load_user_locales( $to ) {
		// Todo: Checking on User object and array...
		add_filter( 'plugin_locale', array( $this, 'set_temporary_locale' ) );

		$this->temp_locale = bppl()->polylang()->get_user_locale( $to );
		$this->reload_locales = apply_filters( 'bppl_reload_locales', array( 'buddypress' ) );

		foreach( $this->reload_locales AS $locale ) {
			unload_textdomain( $locale );
			do_action( 'bppl_unload_plugin_textdomain' );

			load_plugin_textdomain( $locale );
			do_action( 'bppl_load_plugin_textdomain' );
		}
	}

	/**
	 * Loading site plugin locales
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	private function reset_plugin_locales() {
		remove_filter( 'plugin_locale', array( $this, 'set_temporary_locale' ) );

		foreach( $this->reload_locales AS $locale ) {
			unload_textdomain( $locale );
			do_action( 'bppl_unload_plugin_textdomain' );

			load_plugin_textdomain( $locale );
			do_action( 'bppl_load_plugin_textdomain' );
		}
	}
}

