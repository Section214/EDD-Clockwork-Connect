<?php
/*
Plugin Name: Easy Digital Downloads - Clockwork Connect
Plugin URI: https://easydigitaldownloads.com/extension/clockwork-connect
Description: Get real-time SMS notifications from Clockwork when you make sales!
Version: 1.1.1
Author: Daniel J Griffiths
Author URI: http://ghost1227.com
*/

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_Clockwork_Connect' ) ) {

	class EDD_Clockwork_Connect {

		private static $instance;


		/**
		 * Get active instance
		 *
		 * @since		1.1.0
		 * @access		public
		 * @static
		 * @return		object self::$instance
		 */
		public static function get_instance() {
			if( !self::$instance )
				self::$instance = new EDD_Clockwork_Connect();

			return self::$instance;
		}


		/**
		 * Class constructor
		 *
		 * @since		1.1.0
		 * @access		public
		 * @return		void
		 */
		public function __construct() {	
			// Load our custom updater
			if( !class_exists( 'EDD_License' ) )
				include( dirname( __FILE__ ) . '/includes/EDD_License_Handler.php' );

			$this->init();
		}


		/**
		 * Run action and filter hooks
		 *
		 * @since		1.1.0
		 * @access		private
		 * @return		void
		 */
		private function init() {
			// Make sure EDD is active
			if( !class_exists( 'Easy_Digital_Downloads' ) ) return;

			global $edd_options;

			// Internationalization
			add_action( 'init', array( $this, 'textdomain' ) );

			// Register settings
			add_filter( 'edd_settings_extensions', array( $this, 'settings' ), 1 );

			// Handle licensing
			$license = new EDD_License( __FILE__, 'Clockwork Connect', '1.1.1', 'Daniel J Griffiths' );

			// Build SMS message on purchase
			add_action( 'edd_complete_purchase', array( $this, 'build_sms' ), 100, 1 );
		}


		/**
		 * Internationalization
		 *
		 * @since		1.1.0
		 * @access		public
		 * @static
		 * @return		void
		 */
		public static function textdomain() {
			// Set filter for language directory
			$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$lang_dir = apply_filters( 'edd_clockwork_connect_lang_directory', $lang_dir );

			// Load translations
			load_plugin_textdomain( 'edd-clockwork-connect', false, $lang_dir );
		}


		/**
		 * Add settings
		 *
		 * @since		1.0.0
		 * @access		public
		 * @param		array $settings the existing plugin settings
		 * @return		array
		 */
		public function settings( $settings ) {
			$clockwork_settings = array(
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

			return array_merge( $settings, $clockwork_settings );
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
			global $edd_options;

			if( !empty( $edd_options['edd_clockwork_connect_api_key'] ) && !empty( $edd_options['edd_clockwork_connect_phone_number'] ) ) {

				$payment_meta	= edd_get_payment_meta( $payment_id );
				$user_info		= edd_get_payment_meta_user_info( $payment_id );

				$cart_items		= isset( $payment_meta['cart_details'] ) ? maybe_unserialize( $payment_meta['cart_details'] ) : false;

				if( empty( $cart_items ) || !$cart_items )
					$cart_items = maybe_unserialize( $payment_meta['downloads'] );

				if( $cart_items ) {
					$i = 0;

					$message = __( 'New Order', 'edd-clockwork-connect' ) . ' @ ' . get_bloginfo( 'name' ) . urldecode( '%0a' );

					if( $edd_options['edd_clockwork_connect_itemize'] ) {
						foreach( $cart_items as $key => $cart_item ) {
							$id = isset( $payment_meta['cart_details'] ) ? $cart_item['id'] : $cart_item;
							$price_override = isset( $payment_meta['cart_details'] ) ? $cart_item['price'] : null;
							$price = edd_get_download_final_price( $id, $user_info, $price_override );

							$message .= get_the_title( $id );

							if( isset( $cart_items[$key]['item_number'] ) ) {
								$price_options = $cart_items[$key]['item_number']['options'];

								if( isset( $price_options['price_id'] ) )
									$message .= ' - ' . edd_get_price_option_name( $id, $price_options['price_id'], $payment_id );
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
			global $edd_options;

			if( !empty( $edd_options['edd_clockwork_connect_api_key'] ) && !empty( $edd_options['edd_clockwork_connect_phone_number'] ) ) {

				require_once ( dirname( __FILE__ ) . '/includes/clockwork-php/class-Clockwork.php' );

				$api_key = $edd_options['edd_clockwork_connect_api_key'];
				$phone_numbers = explode( ',', $edd_options['edd_clockwork_connect_phone_number'] );

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


function edd_clockwork_connect_load() {
	$edd_clockwork_connect = new EDD_Clockwork_Connect();
}
add_action( 'plugins_loaded', 'edd_clockwork_connect_load' );
