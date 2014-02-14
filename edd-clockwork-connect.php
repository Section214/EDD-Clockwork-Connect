<?php
/**
 * Plugin Name:		Easy Digital Downloads - Clockwork Connect
 * Plugin URI:		https://easydigitaldownloads.com/extension/clockwork-connect
 * Description:		Get real-time SMS notifications from Clockwork when you make sales!
 * Version:			1.2.0
 * Author:			Daniel J Griffiths
 * Author URI:		http://section214.com
 * Text Domain:		edd-clockwork-connect
 *
 * @package			EDD\ClockworkConnect
 * @author			Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright		Copyright (c) 2014, Daniel J Griffiths
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'EDD_Clockwork_Connect' ) ) {


	/**
	 * Main EDD_Clockwork_Connect class
	 *
	 * @since		1.0.0
	 */
	class EDD_Clockwork_Connect {

		/**
		 * @var			EDD_Clockwork_Connect $instance The one true EDD_Clockwork_Connect
		 * @since		1.0.0
		 */
		private static $instance;

		/**
		 * Get active instance
		 *
		 * @access		public
		 * @since		1.1.0
		 * @return		object self::$instance The one true EDD_Clockwork_Connect
		 */
		public static function instance() {
			if( !self::$instance ) {
				self::$instance = new EDD_Clockwork_Connect();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
				self::$instance->hooks();
			}

			return self::$instance;
		}


		/**
		 * Setup plugin constants
		 *
		 * @access		private
		 * @since		1.1.0
		 * @return		void
		 */
		private function setup_constants() {
			// Plugin path
			define( 'CLOCKWORK_CONNECT_PLUGIN_DIR', dirname( __FILE__ ) );

			// Plugin URL
			define( 'CLOCKWORK_CONNECT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

			// Plugin version
			define( 'CLOCKWORK_CONNECT_PLUGIN_VER', '1.2.0' );
		}


		/**
		 * Include necessary files
		 *
		 * @access		private
		 * @since		1.2.0
		 * @return		void
		 */
		private function includes() {

		}


		/**
		 * Run action and filter hooks
		 *
		 * @access		private
		 * @since		1.1.0
		 * @return		void
		 */
		private function hooks() {
			// Edit plugin metalinks
			add_filter( 'plugin_row_meta', array( $this, 'plugin_metalinks' ), null, 2 );

			// Handle licensing
			if( class_exists( 'EDD_License' ) ) {
				$license = new EDD_License( __FILE__, 'Clockwork Connect', CLOCKWORK_CONNECT_PLUGIN_VER, 'Daniel J Griffiths' );
			}

			// Register settings
			add_filter( 'edd_settings_extensions', array( $this, 'settings' ), 1 );

			// Build SMS message on purchase
			add_action( 'edd_complete_purchase', array( $this, 'build_sms' ), 100, 1 );
		}


		/**
		 * Internationalization
		 *
		 * @access		public
		 * @since		1.1.0
		 * @return		void
		 */
		public function load_textdomain() {
			// Set filter for language directory
			$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$lang_dir = apply_filters( 'EDD_Clockwork_Connect_lang_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale		= apply_filters( 'plugin_locale', get_locale(), '' );
			$mofile		= sprintf( '%1$s-%2$s.mo', 'edd-clockwork-connect', $locale );

			// Setup paths to current locale file
			$mofile_local	= $lang_dir . $mofile;
			$mofile_global	= WP_LANG_DIR . '/edd-clockwork-connect/' . $mofile;

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


		/**
		 * Modify plugin metalinks
		 *
		 * @access		public
		 * @since		1.2.0
		 * @param		array $links The current links array
		 * @param		string $file A specific plugin table entry
		 * @return		array $links The modified links array
		 */
		public function plugin_metalinks( $links, $file ) {
            if( $file == plugin_basename( __FILE__ ) ) {
                $help_link = array(
                    '<a href="https://easydigitaldownloads.com/support/forum/add-on-plugins/clockwork-connect/" target="_blank">' . __( 'Support Forum', 'edd-clockwork-connect' ) . '</a>'
                );

                $docs_link = array(
                    '<a href="http://section214.com/docs/category/edd-clockwork-connect/" target="_blank">' . __( 'Docs', 'edd-clockwork-connect' ) . '</a>'
                );

                $links = array_merge( $links, $help_link, $docs_link );
            }

			return $links;
		}


		/**
		 * Add settings
		 *
		 * @access		public
		 * @since		1.0.0
		 * @param		array $settings The existing plugin settings
		 * @return		array
		 */
		public function settings( $settings ) {
			$new_settings = array(
				array(
					'id'	=> 'edd_clockwork_connect_settings',
					'name'	=> '<strong>' . __( 'Clockwork Connect Settings', 'edd-clockwork-connect' ) . '</strong>',
					'desc'	=> __( 'Configure Clockwork Connect Settings', 'edd-clockwork-connect' ),
					'type'	=> 'header'
				),
				array(
					'id'	=> 'edd_clockwork_connect_api_key',
					'name'	=> __( 'API Key', 'edd-clockwork-connect' ),
					'desc'	=> __( 'Enter your Clockwork Connect API Key (available on the <a href="https://app5.clockworksms.com/Sending/Keys" target="_blank">API Keys page</a>)', 'edd-clockwork-connect' ),
					'type'	=> 'text',
					'size'	=> 'regular'
				),
				array(
					'id'	=> 'edd_clockwork_connect_phone_number',
					'name'	=> __( 'Phone Number', 'edd-clockwork-connect' ),
					'desc'	=> __( 'Enter the number(s) you want messages delivered to, comma separated in the format \'xxxxxxxxxxx\'', 'edd-clockwork-connect' ),
					'type'	=> 'text',
					'size'	=> 'regular'
				),
				array(
					'id'	=> 'edd_clockwork_connect_itemize',
					'name'	=> __( 'Itemized Notification', 'edd-clockwork-connect' ),
					'desc'	=> __( 'Select whether or not you want itemized SMS notifications', 'edd-clockwork-connect' ),
					'type'	=> 'checkbox'
				)
			);

			return array_merge( $settings, $new_settings );
		}


		/**
		 * Build the message to be passed to Clockwork
		 *
		 * @access		public
		 * @since		1.0.0
		 * @param		string $payment_id
		 * @return		void
		 */
		public function build_sms( $payment_id ) {
			if( edd_get_option( 'edd_clockwork_connect_api_key' ) && edd_get_option( 'edd_clockwork_connect_phone_number' ) ) {

				$payment_meta	= edd_get_payment_meta( $payment_id );
				$user_info		= edd_get_payment_meta_user_info( $payment_id );

				$cart_items		= isset( $payment_meta['cart_details'] ) ? maybe_unserialize( $payment_meta['cart_details'] ) : false;

				if( empty( $cart_items ) || !$cart_items ) {
					$cart_items = maybe_unserialize( $payment_meta['downloads'] );
				}

				if( $cart_items ) {
					$i = 0;

					$message = __( 'New Order', 'edd-clockwork-connect' ) . ' @ ' . get_bloginfo( 'name' ) . urldecode( '%0a' );

					if( edd_get_option( 'edd_clockwork_connect_itemize' ) ) {
						foreach( $cart_items as $key => $cart_item ) {
							$id = isset( $payment_meta['cart_details'] ) ? $cart_item['id'] : $cart_item;
							$price_override = isset( $payment_meta['cart_details'] ) ? $cart_item['price'] : null;
							$price = edd_get_download_final_price( $id, $user_info, $price_override );

							$message .= get_the_title( $id );

							if( isset( $cart_items[$key]['item_number'] ) ) {
								$price_options = $cart_items[$key]['item_number']['options'];

								if( isset( $price_options['price_id'] ) ) {
									$message .= ' - ' . edd_get_price_option_name( $id, $price_options['price_id'], $payment_id );
								}
							}

							$message .= ' - ' . html_entity_decode( edd_currency_filter( edd_format_amount( $price ) ) ) . urldecode( '%0a' );
						}
					}

					$message .= __( 'TOTAL', 'edd-clockwork-connect' ) . ' - ' . html_entity_decode( edd_currency_filter( edd_format_amount( edd_get_payment_amount( $payment_id ) ) ) );

					if( strlen( $message ) > 160 ) {
						$messages = str_split( $message, 140 );
						$max = count( $messages );
						$count = 1;

						foreach( $messages as $message ) {
							$message = $count . '/' . $max . urldecode( '%0a' ) . $message;
							$error = $this->send_sms( $message );
						}
					} else {
						$error = $this->send_sms( $message );
					}
				}
			}
		}


		/**
		 * Send an SMS
		 *
		 * @access		public
		 * @since		1.0.0
		 * @param		string $message the message to send
		 * @return		void
		 */
		public function send_sms( $message ) {
			if( edd_get_option( 'edd_clockwork_connect_api_key' ) && edd_get_option( 'edd_clockwork_connect_phone_number' ) ) {

				require_once ( dirname( __FILE__ ) . '/includes/clockwork-php/class-Clockwork.php' );

				$api_key = edd_get_option( 'edd_clockwork_connect_api_key', '' );
				$phone_numbers = explode( ',', edd_get_option( 'edd_clockwork_connect_phone_number', '' ) );

				foreach( $phone_numbers as $phone_number ) {
					try {
						$client = new Clockwork( $api_key );

						$data = array(
							"from"		=> $phone_number,
							"to"		=> $phone_number,
							"message"	=> $message
						);

						$result = $client->send( $data );

						if( $result['success'] ) {
							return;
						} else {
							return $result['error_message'];
						}
					} catch( ClockworkException $e ) {
						return $e->getMessage();
					}
				}
			}
		}
	}
}


/**
 * The main function responsible for returning the one true EDD_Clockwork_Connect
 * instance to functions everywhere
 *
 * @since		1.0.0
 * @return		EDD_Clockwork_Connect The one true EDD_Clockwork_Connect
 */
function EDD_Clockwork_Connect_load() {
	if( !class_exists( 'Easy_Digital_Downloads' ) ) {
		deactivate_plugins( __FILE__ );
		unset( $_GET['activate'] );

		// Display notice
		add_action( 'admin_notices', 'EDD_Clockwork_Connect_missing_edd_notice' );
	} else {
		return EDD_Clockwork_Connect::instance();
	}
}
add_action( 'plugins_loaded', 'EDD_Clockwork_Connect_load' );


/**
 * We need Easy Digital Downloads... if it isn't present, notify the user!
 *
 * @since		1.2.0
 * @return		void
 */
function EDD_Clockwork_Connect_missing_edd_notice() {
	echo '<div class="error"><p>' . __( 'Clockwork Connect requires Easy Digital Downloads! Please install it to continue!', 'edd-clockwork-connect' ) . '</p></div>';
}
