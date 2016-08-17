<?php
/**
 * Settings
 *
 * @package         EDD\ClockworkConnect\Functions
 * @since           1.1.1
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}



/**
 * Build the message to be passed to Clockwork
 *
 * @since       1.0.0
 * @param       string $payment_id
 * @return      void
 */
function edd_clockwork_connect_build_sms( $payment_id ) {
	if( edd_get_option( 'edd_clockwork_connect_api_key' ) && edd_get_option( 'edd_clockwork_connect_phone_number' ) ) {

		$payment_meta = edd_get_payment_meta( $payment_id );
		$user_info    = edd_get_payment_meta_user_info( $payment_id );
		$cart_items   = isset( $payment_meta['cart_details'] ) ? maybe_unserialize( $payment_meta['cart_details'] ) : false;

		if( empty( $cart_items ) || ! $cart_items ) {
			$cart_items = maybe_unserialize( $payment_meta['downloads'] );
		}

		if( $cart_items ) {
			$i = 0;

			$message = __( 'New Order', 'edd-clockwork-connect' ) . ' @ ' . get_bloginfo( 'name' ) . urldecode( '%0a' );

			if( edd_get_option( 'edd_clockwork_connect_itemize' ) ) {
				foreach( $cart_items as $key => $cart_item ) {
					$id             = isset( $payment_meta['cart_details'] ) ? $cart_item['id'] : $cart_item;
					$price_override = isset( $payment_meta['cart_details'] ) ? $cart_item['price'] : null;
					$price          = edd_get_download_final_price( $id, $user_info, $price_override );

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
				$max      = count( $messages );
				$count    = 1;

				foreach( $messages as $message ) {
					$message = $count . '/' . $max . urldecode( '%0a' ) . $message;
					$error   = edd_clockwork_connect_send_sms( $message );
				}
			} else {
				$error = edd_clockwork_connect_send_sms( $message );
			}
		}
	}
}
add_action( 'edd_complete_purchase', 'edd_clockwork_connect_build_sms', 100, 1 );


/**
 * Send an SMS
 *
 * @since       1.0.0
 * @param       string $message the message to send
 * @return      void
 */
function edd_clockwork_connect_send_sms( $message ) {
	if( edd_get_option( 'edd_clockwork_connect_api_key' ) && edd_get_option( 'edd_clockwork_connect_phone_number' ) ) {

		require_once EDD_CLOCKWORK_CONNECT_DIR . '/includes/libraries/clockwork-php/class-Clockwork.php';

		$api_key       = edd_get_option( 'edd_clockwork_connect_api_key', '' );
		$phone_numbers = explode( ',', edd_get_option( 'edd_clockwork_connect_phone_number', '' ) );

		foreach( $phone_numbers as $phone_number ) {
			try {
				$client = new Clockwork( $api_key );

				$data = array(
					"from"    => $phone_number,
					"to"      => $phone_number,
					"message" => $message
				);

				$result = $client->send( $data );

				if( $result['success'] ) {
					return;
				} else {
					if( edd_getresponse()->debugging ) {
						s214_debug_log_error( 'SMS Error', 'Failed to send SMS:<br />' . print_r( $result, true ), 'EDD Clickatell Connect' );
					}

					return $result['error_message'];
				}
			} catch( ClockworkException $e ) {
				if( edd_getresponse()->debugging ) {
					s214_debug_log_error( 'SMS Error', 'Failed to send SMS: ' . $e->getMessage(), 'EDD Clickatell Connect' );
				}

				return $e->getMessage();
			}
		}
	}
}