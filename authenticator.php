<?php
/*
Plugin Name: Authenticator
Plugin URI:  http://bueltge.de/authenticator-wordpress-login-frontend-plugin/721/
Description: This plugin allows you to make your WordPress site accessible to logged in users only. In other words to view your site they have to create / have an account in your site and be logged in. No configuration necessary, simply activating - thats all.
Author:      Inpsyde GmbH
Version:     1.1.0
Author URI:  http://inpsyde.com/
License:     GPLv3
Textdomain:  authenticator
*/

// check for uses in WP
if ( ! function_exists( 'add_filter' ) ) {
	echo "Hi there! I'm just a part of plugin, not much I can do when called directly.";
	exit;
}

spl_autoload_register( array( 'Authenticator', 'load_classes' ) );
register_uninstall_hook( __FILE__, array( 'Authenticator', 'uninstall' ) );
add_action( 'plugins_loaded', array( 'Authenticator', 'get_instance' ) );

class Authenticator {

	/**
	 * option key
	 *
	 * @since 1.1.0
	 * @const sring
	 */
	const KEY = 'authenticator_options';

	/**
	 * textdomain
	 *
	 * @since 1.1.0
	 * @const string
	 */
	const TEXTDOMAIN = 'authenticator';

	/**
	 * Version
	 *
	 * @since 1.1.0
	 * @const string
	 */
	const VERSION = '1.1.0-RC1';

	/**
	 * absolute path to this directory
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public static $dir = '';

	/**
	 * absolute URL to this directory
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public static $url = '';

	/**
	 * instance of self
	 *
	 * @var Authenticator
	 */
	private static $instance = NULL;

	/**
	 * Array for pages, there are checked for exclude the redirect
	 */
	public static $pagenows = array( 'wp-login.php', 'wp-register.php' );

	/**
	 * options
	 *
	 * @var array
	 */
	protected static $options = array();

	/**
	 * instance of settins handling class
	 *
	 * @var Authenticator_Settings
	 */
	public $settings = NULL;

	/**
	 * get the instance
	 *
	 * @since   1.1.0
	 * @return  Authenticator
	 */
	public static function get_instance() {

		if ( ! self::$instance instanceof self )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Constructor, init redirect on defined hooks
	 *
	 * @since   0.4.0
	 * @return  Authenticator
	 */
	public function __construct() {

		self::$dir = plugin_dir_path( __FILE__ );
		self::$url = plugins_url( '', __FILE__ );

		$this->localize();
		if ( ! isset( $GLOBALS['pagenow'] ) ||
			 ! in_array( $GLOBALS['pagenow'], self :: $pagenows )
			)
			add_action( 'template_redirect', array( __CLASS__, 'redirect' ) );
		else
			wp_enqueue_script(
				'authenticator-backtoblog',
				plugin_dir_url( __FILE__ ) . 'js/authenticator.js',
				array( 'jquery' ),
				'1.0.0'
			);
		
		add_action( 'admin_init', array( $this, 'init_settings' ) );
		add_filter( 'authenticator_get_options', array( $this, 'get_options' ) );
		self::$options = get_option( self::KEY, array() );
	}

	/**
	 * load the language files
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function localize() {

		load_plugin_textdomain(
			'authenticator',
			FALSE,
			dirname( plugin_basename( __FILE__ ) ) . '/language'
		);
	}

	/**
	 * init the settings api
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function init_settings() {

		$this->settings = new Authenticator_Settings();
	}

	/*
	 * Get redirect to login-page, if user not logged in blogs of network and single install
	 *
	 * @since  0.4.2
	 * @retur  void
	 */
	public static function redirect() {

		if ( is_feed() ) {
			if ( TRUE === apply_filters( 'authenticator_bypass_feed_auth', FALSE ) )
				return;

			switch ( self::$options[ 'feed_authentication' ] ) {
				case 'http' :
					return self::http_auth_feed();
				case 'token' :
					if ( isset( $_GET[ self::$options[ 'auth_token' ] ] ) )
						return;
					$protocol = 'HTTP/1.1' ===  $_SERVER[ 'SERVER_PROTOCOL' ]
						? 'HTTP/1.1'
						: 'HTTP/1.0';
					header( $protocol . ' 403 Forbidden' );
					exit( '<h1>403 Forbidden</h1>' );

				case 'none' :
				default :
					# nothing to do
					break;
			}
		}

		/**
		 * Checks if a user is logged in or has rights on the blog in multisite,
		 * if not redirects them to the login page
		 */
		$reauth = ! current_user_can( 'read' ) &&
			function_exists('is_multisite') &&
			is_multisite() ? TRUE : FALSE;

		if ( ! is_user_logged_in() || $reauth ) {
			nocache_headers();
			wp_redirect(
				wp_login_url( $_SERVER[ 'REQUEST_URI' ], $reauth ),
				$status = 302
			);
			exit();
		}
	}

	/**
	 * authenticate users requesting feeds via HTTP Basic auth
	 *
	 * @since   1.1.0
	 * @return  void
	 */
	protected static function http_auth_feed() {

		$auth = new HTTP_Auth( 'Feed of ' . get_bloginfo( 'name' ) );
		$user = $auth->get_user();
		$user = wp_authenticate( $user[ 'name'], $user[ 'pass' ] );

		if ( ! is_a( $user, 'WP_User' ) || ! user_can( $user, 'read' ) )
			$auth->auth_required();
	}

	/**
	 * get the options
	 *
	 * @return string
	 */
	public function get_options() {

		return self::$options;
	}

	/**
	 * autoloader
	 *
	 * @since   1.1.0
	 * @param   string $class_name
	 * @return  void
	 */
	public static function load_classes( $class_name ) {

		$file_name = dirname( __FILE__ ) . '/inc/class-' . $class_name . '.php';
		if ( file_exists( $file_name ) )
			require_once $file_name;

	}

	/**
	 * uninstall routine
	 *
	 * @since   1.1.0
	 * @global  $wpdb
	 * @return  void
	 */
	public static function uninstall() {
		global $wpdb;

		ignore_user_abort( -1 );
		if ( is_network_admin() && isset( $wpdb->blogs ) ) {
			$blogs = $wpdb->get_results(
				'SELECT blog_id FROM ' .
					$wpdb->blogs,
				ARRAY_A
			);
			foreach ( $blogs as $key => $row ) {
				$id = ( int ) $row[ 'blog_id' ];
				delete_blog_option( $id, self::KEY );
			}

			return;
		}

		delete_option( self::KEY );
	}

} // end class
