<?php
/**
 * Plugin Name:     Easy Digital Downloads - Clockwork Connect
 * Plugin URI:      https://section214.com/product/edd-clockwork-connect
 * Description:     Get real-time SMS notifications from Clockwork when you make sales!
 * Version:         1.1.2
 * Author:          Daniel J Griffiths
 * Author URI:      https://section214.com
 * Text Domain:     edd-clockwork-connect
 *
 * @package         EDD\ClockworkConnect
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright       Copyright (c) 2014, Daniel J Griffiths
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


if( ! class_exists( 'EDD_Clockwork_Connect' ) ) {


	/**
	 * Main EDD_Clockwork_Connect class
	 *
	 * @since       1.0.0
	 */
	class EDD_Clockwork_Connect {


		/**
		 * @var         EDD_Clockwork_Connect $instance The one true EDD_Clockwork_Connect
		 * @since       1.0.0
		 */
		private static $instance;


		/**
		 * @var         bool $debugging Whether or not debugging is available
		 * @since       1.0.2
		 */
		public $debugging = false;


		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       1.1.0
		 * @return      object self::$instance The one true EDD_Clockwork_Connect
		 */
		public static function instance() {
			if( ! self::$instance ) {
				self::$instance = new EDD_Clockwork_Connect();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
				self::$instance->hooks();

				if( class_exists( 'S214_Debug' ) ) {
					if( edd_get_option( 'edd_clockwork_connect_enable_debug', false ) ) {
						self::$instance->debugging = true;
					}
				}
			}

			return self::$instance;
		}


		/**
		 * Setup plugin constants
		 *
		 * @access      private
		 * @since       1.1.0
		 * @return      void
		 */
		private function setup_constants() {
			// Plugin path
			define( 'EDD_CLOCKWORK_CONNECT_DIR', plugin_dir_path( __FILE__ ) );

			// Plugin URL
			define( 'EDD_CLOCKWORK_CONNECT_URL', plugin_dir_url( __FILE__ ) );

			// Plugin version
			define( 'EDD_CLOCKWORK_CONNECT_VER', '1.1.2' );
		}


		/**
		 * Include necessary files
		 *
		 * @access      private
		 * @since       1.1.0
		 * @return      void
		 */
		private function includes() {
			require_once EDD_CLOCKWORK_CONNECT_DIR . 'includes/functions.php';

			if( is_admin() ) {
				require_once EDD_CLOCKWORK_CONNECT_DIR . 'includes/admin/settings/register.php';
				require_once EDD_CLOCKWORK_CONNECT_DIR . 'includes/libraries/S214_License_Field.php';
			}

			if( ! class_exists( 'S214_Plugin_Updater' ) ) {
				require_once EDD_CLOCKWORK_CONNECT_DIR . 'includes/libraries/S214_Plugin_Updater.php';
			}
		}


		/**
		 * Run action and filter hooks
		 *
		 * @access      private
		 * @since       1.1.0
		 * @return      void
		 */
		private function hooks() {
			// Handle licensing
			$license = edd_get_option( 'edd_clockwork_connect_license', false );

			if( $license ) {
				$update = new S214_Plugin_Updater( 'https://section214.com', __FILE__, array(
					'version' => EDD_CLOCKWORK_CONNECT_VER,
					'license' => $license,
					'item_id' => 842,
					'author'  => 'Daniel J Griffiths'
				) );
			}
		}


		/**
		 * Internationalization
		 *
		 * @access      public
		 * @since       1.1.0
		 * @return      void
		 */
		public function load_textdomain() {
			// Set filter for language directory
			$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$lang_dir = apply_filters( 'edd_clockwork_connect_lang_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), '' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'edd-clockwork-connect', $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/edd-clockwork-connect/' . $mofile;

			if( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/edd-clockwork-connect/ folder
				load_textdomain( 'edd-clockwork-connect', $mofile_global );
			} elseif( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/edd-clockwork-connect/languages/ folder
				load_textdomain( 'edd-clockwork-connect', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'edd-clockwork-connect', false, $lang_dir );
			}
		}
	}
}


/**
 * The main function responsible for returning the one true EDD_Clockwork_Connect
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      EDD_Clockwork_Connect The one true EDD_Clockwork_Connect
 */
function edd_clockwork_connect() {
	if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
		if( ! class_exists( 'S214_EDD_Activation' ) ) {
			require_once 'includes/library/class.s214-edd-activation.php';
		}

		$activation = new S214_EDD_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();
		return;
	}

	return EDD_Clockwork_Connect::instance();
}
add_action( 'plugins_loaded', 'edd_clockwork_connect' );
