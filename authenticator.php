<?php
/*
Plugin Name: Authenticator
Plugin URI: http://bueltge.de/authenticator-wordpress-login-frontend-plugin/721/
Description: This plugin allows you to make your WordPress site accessible to logged in users only. In other words to view your site they have to create / have an account in your site and be logged in. No configuration necessary, simply activating - thats all.
Author: Frank B&uuml;ltge
Version: 1.0.0
Author URI: http://bueltge.de/
License: GPLv3
*/

// check for uses in WP
if ( ! function_exists( 'add_filter' ) ) {
	echo "Hi there! I'm just a part of plugin, not much I can do when called directly.";
	exit;
}

class Authenticator {
	
	/**
	 * Array for pages, there are checked for exclude the redirect
	 */
	public static $pagenows = array( 'wp-login.php', 'wp-register.php' );
	
	/**
	 * Constructor, init redirect on defined hooks
	 * 
	 * @since   0.4.0
	 * @return  void
	 */
	public function __construct() {
		
		if ( ! isset( $GLOBALS['pagenow'] ) ||
			 ! in_array( $GLOBALS['pagenow'], self :: $pagenows )
			)
			add_action( 'template_redirect', array( __CLASS__, 'redirect' ) );
	}
	
	/*
	 * Get redirect to login-page, if user not logged in blogs of network and single install
	 * 
	 * @since  0.4.2
	 * @retur  void
	 */
	public static function redirect() {
		
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

} // end class

$authenticator = new authenticator();

