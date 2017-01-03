<?php

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BP_Translate_Emails {

	/**
	 * Instance
	 *
	 * @since 1.0.0
	 *
	 * @var BP_Translate_Emails $instance;
	 */
	protected static $instance;

	/**
	 * All languages added by Polylang
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
     * Post language relationships
     *
     * @var array
     */
	private $post_lang_rel = array();

	/**
	 * BuddyPress_Polylang constructor
	 *
	 * @since 1.0.0
	 */
	final private function __construct() {
		$this->init();
	}

	/**
	 * Adding Actionhooks & Co.
	 *
	 * @since 1.0.0
	 */
	protected function init() {
		add_action( 'bp_core_install_emails', array( $this, 'reinstall_bp_emails_with_languages' ) );
		add_filter( 'pll_get_post_types', array( $this, 'add_post_type_slug' ) );
		add_filter( 'pll_get_taxonomies', array( $this, 'add_taxonomy' ) );
	}

	/**
	 * Getting instance
	 *
	 * @since 1.0.0
	 *
	 * @return BP_Translate_Emails $instance
	 */
	final public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static;
		}
		return static::$instance;
	}

	/**
	 * Installing all emails of the Polylang added languages
	 *
	 * @since 1.0.0
	 */
	public function reinstall_bp_emails_with_languages() {
		// Just add this one time!
		remove_action( 'bp_core_install_emails', array( $this, 'reinstall_bp_emails_with_languages' ) );

		$locales = pll_languages_list( array( 'fields' => 'locale' ) );

		// Deleting everything created before
		$this->get_rid_of_shit();

		add_filter( 'locale', array( $this, 'set_temporary_locale' ) );
		foreach( $locales AS $locale ) {
			$this->temp_locale = $locale;

			unload_textdomain( 'buddypress' );
			load_plugin_textdomain( 'buddypress' );
			$installed = $this->install_emails( $locale );

			if( is_wp_error( $installed ) ) {
                bppl()->message( $installed->get_error_message() );
                break;
            }

			// And so on...
		}
		remove_filter( 'locale', array( $this, 'set_temporary_locale' ) );

		// Reset to system language
		unload_textdomain( 'buddypress' );
		load_plugin_textdomain( 'buddypress' );

		// Saving relations between posts
		foreach( $this->post_lang_rel AS $post_lang_rel ) {
		    $posts = array();
		    foreach( $post_lang_rel AS $locale => $post_id ) {
                $lang = bppl()->polylang()->get_lang_by_locale( $locale );
                if( is_wp_error( $lang ) ) {
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
     * @return WP_Error
	 */
	private function install_emails( $locale ) {
		$lang = bppl()->polylang()->get_lang_by_locale( $locale );

		if( is_wp_error( $lang ) ) {
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
			pll_set_post_language( $post_id, $locale );

			$this->post_lang_rel[ $id ][ $locale ] = $post_id;

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
		 * @since 2.5.0
		 */
		do_action( 'bp_core_install_emails' );
	}

	/**
	 * Deleting everything which was created before
	 *
	 * @since 1.0.0
	 */
	public function get_rid_of_shit() {
		$emails = get_posts( array(
			                     'fields'           => 'idsBP_Email_Translate',
			                     'post_status'      => 'publish',
			                     'post_type'        => bp_get_email_post_type(),
			                     'posts_per_page'   => -1,
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
		if( empty( $this->temp_locale ) ) {
			return $locale;
		}
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

	public function add_taxonomy( $taxonomies ) {
		$taxonomies[ bp_get_email_tax_type() ] = bp_get_email_tax_type();
		return $taxonomies;
	}
}

