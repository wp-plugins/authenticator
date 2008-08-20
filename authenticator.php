<?php
/*
Plugin Name: Authenticator
Plugin URI: http://bueltge.de/authenticator-wordpress-login-frontend-plugin/721/
Description: This plugin allows you to make your WordPress site accessible to logged in users only. In other words to view your site they have to create / have an account in your site and be logged in. No configuration necessary, simply activating - thats all.
Author: Frank Bueltge
Version: 0.2
Author URI: http://bueltge.de/
License: GPL
*/

if (!class_exists('Authenticator')) {
	class Authenticator {
	
		function fb_authenticator_redirect() {
			// Checks if a user is logged in, if not redirects them to the login page
			if ( !is_user_logged_in() ) {
				nocache_headers();
				header("HTTP/1.1 302 Moved Temporarily");
				header('Location: ' . get_option('siteurl') . '/wp-login.php?redirect_to=' . urlencode($_SERVER['REQUEST_URI']));
				header("Status: 302 Moved Temporarily");
				exit();
			}
		}
	
		function Authenticator() {
			global $pagenow;

			if ('wp-login.php' != $pagenow && 'wp-register.php' != $pagenow)
				add_action( 'template_redirect', array($this, 'fb_authenticator_redirect') );
		}
	
	}
	
	$Authenticator = new Authenticator();

}
?>