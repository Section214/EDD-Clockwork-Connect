<?php
/**
 * Settings
 *
 * @package         EDD\ClockworkConnect\Admin\Settings
 * @since           1.1.1
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add settings section
 *
 * @since       1.0.1
 * @param       array $sections The existing extensions sections
 * @return      array The modified extensions settings
 */
function edd_clockwork_connect_add_settings_section( $sections ) {
	$sections['clockwork-connect'] = __( 'Clockwork Connect', 'edd-clockwork-connect' );

	return $sections;
}
add_filter( 'edd_settings_sections_extensions', 'edd_clockwork_connect_add_settings_section' );


/**
 * Add settings
 *
 * @since       1.0.0
 * @param       array $settings the existing plugin settings
 * @return      array
 */
function edd_clockwork_connect_register_settings( $settings ) {
	$new_settings = array(
		'clockwork-connect' => array(
			array(
				'id'   => 'edd_clockwork_connect_settings',
				'name' => '<strong>' . __( 'Clockwork Connect Settings', 'edd-clockwork-connect' ) . '</strong>',
				'desc' => __( 'Configure Clockwork Settings', 'edd-clockwork-connect' ),
				'type' => 'header'
			),
			array(
				'id'   => 'edd_clockwork_connect_api_key',
				'name' => __( 'API Key', 'edd-clockwork-connect' ),
				'desc' => __( 'Enter your Clockwork Connect API Key (available on the <a href="https://app5.clockworksms.com/Sending/Keys" target="_blank">API Keys page</a>)', 'edd-clockwork-connect' ),
				'type' => 'text',
				'size' => 'regular'
			),
			array(
				'id'   => 'edd_clockwork_connect_phone_number',
				'name' => __( 'Phone Number', 'edd-clockwork-connect' ),
				'desc' => __( 'Enter the number(s) you want messages delivered to, comma separated in the format \'xxxxxxxxxxx\'', 'edd-clockwork-connect' ),
				'type' => 'text',
				'size' => 'regular'
			),
			array(
				'id'   => 'edd_clockwork_connect_itemize',
				'name' => __( 'Itemized Notification', 'edd-clockwork-connect' ),
				'desc' => __( 'Select whether or not you want itemized SMS notifications', 'edd-clockwork-connect' ),
				'type' => 'checkbox'
			)
		)
	);

	return array_merge( $settings, $new_settings );
}
add_filter( 'edd_settings_extensions', 'edd_clockwork_connect_register_settings', 1 );


function edd_clockwork_connect_register_license_settings( $settings ) {
	$new_settings = array(
		'edd_clockwork_connect_license' => array(
			'id'   => 'edd_clockwork_connect_license',
			'name' => __( 'Clockwork Connect', 'edd-clockwork-connect' ),
			'desc' => sprintf( __( 'Enter your Clockwork Connect license key. This is required for automatic updates and <a href="%s">support</a>.' ), 'https://section214.com/contact' ),
			'type' => 's214_license_key'
		)
	);

	return array_merge( $settings, $new_settings );
}
add_filter( 'edd_settings_licenses', 'edd_clockwork_connect_register_license_settings', 1 );


/**
 * License key sanitization
 *
 * @since       1.0.0
 * @param       mixed $value The value of the field
 * @param       string $key The key we are sanitizing
 * @return      mixed $value The sanitized value
 */
function edd_clockwork_connect_license_key_sanitize( $value, $key ) {
	$current_value = edd_get_option( 'edd_clockwork_connect_license', false );

	if( ( $value && $value !== $current_value ) || ! $value ) {
		delete_option( 'edd_clockwork_connect_license_status' );
	}

	if( isset( $_POST['edd_clockwork_connect_license_activate'] ) && $value ) {
		edd_clockwork_connect_activate_license( $value );
	} elseif( isset( $_POST['edd_clockwork_connect_license_deactivate'] ) ) {
		edd_clockwork_connect_deactivate_license( $value );
		$value = '';
	}

	return $value;
}
add_filter( 'edd_settings_sanitize_s214_license_key', 'edd_clockwork_connect_license_key_sanitize', 10, 2 );


/**
 * License activation
 *
 * @since       1.0.0
 * @param       string $value The license key to activate
 * @return      void
 */
function edd_clockwork_connect_activate_license( $license ) {
	if( ! check_admin_referer( 'edd_clockwork_connect_license-nonce', 'edd_clockwork_connect_license-nonce' ) ) {
		return;
	}

	$license = trim( $license );

	if( $license ) {
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => 'EDD Clockwork Connect',
			'url'        => home_url()
		);

		// Call the API
		$response = wp_remote_post( 'https://section214.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		if( is_wp_error( $response ) ) {
			return false;
		}

		// Decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		update_option( 'edd_clockwork_connect_license_status', $license_data );
	}
}


/**
 * License deactivation
 *
 * @since       1.0.0
 * @return      void
 */
function edd_clockwork_connect_deactivate_license( $license ) {
	if( ! check_admin_referer( 'edd_clockwork_connect_license-nonce', 'edd_clockwork_connect_license-nonce' ) ) {
		return;
	}

	$license = trim( $license );
	$status  = get_option( 'edd_clockwork_connect_license_status', false );

	if( $license && is_object( $status ) && $status->license == 'valid' ) {
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => urlencode( 'EDD Clockwork Connect' ),
			'url'        => home_url()
		);

		$response = wp_remote_post( 'https://section214.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		if( is_wp_error( $response ) ) {
			return false;
		}

		// Decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if( $license_data->license == 'deactivated' ) {
			delete_option( 'edd_clockwork_connect_license_status' );
		}
	}
}