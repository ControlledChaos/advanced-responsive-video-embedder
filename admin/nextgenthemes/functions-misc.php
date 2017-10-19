<?php
namespace nextgenthemes\admin;

function html_attr( $attr = array() ) {

	if ( empty( $attr ) || ! is_array( $attr ) ) {
		return '';
	}

	$html = '';

	foreach ( $attr as $key => $value ) {

		if ( false === $value || null === $value ) {
			continue;
		} elseif ( '' === $value || true === $value ) {
			$html .= sprintf( ' %s', esc_html( $key ) );
		} elseif ( in_array( $key, array( 'href', 'data-href', 'src', 'data-src' ) ) ) {
			$html .= sprintf( ' %s="%s"', esc_html( $key ), esc_url( $value ) );
		} else {
			$html .= sprintf( ' %s="%s"', esc_html( $key ), esc_attr( $value ) );
		}
	}

	return $html;
}

function remote_get( $url, $args = array(), $json = true ) {

	$response      = wp_remote_post( $url, $args );
	$response_code = wp_remote_retrieve_response_code( $response );

	// retry with wp_remote_GET
	if ( is_wp_error( $response ) || 200 !== $response_code ) {
		$response      = wp_remote_get( $url, $args );
		$response_code = wp_remote_retrieve_response_code( $response );
	}

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if ( 200 !== $response_code ) {

		return new WP_Error(
			'remote_get',
			sprintf(
				__( 'remote_get error: Status code was expected to be 200 but was %s.', TEXTDOMAIN ),
				$response_code
			)
		);
	}

	$body = wp_remote_retrieve_body( $response );

	if ( '' === $body ) {
		return new WP_Error( 'remote_get', __( 'Empty body', TEXTDOMAIN ) );
	}

	if( $json ) {
		$response = json_decode( $body );

		if ( null == $response ) {
			return new WP_Error( 'remote_get', __( 'json_decode returned null', TEXTDOMAIN ) );
		}
	}

	return $response;
};

function remote_get_cached( $args ) {

	$defaults = array(
		'args' => array(),
		'json' => true,
		'cache_time' => 3600,
	);

	$args = wp_parse_args( $args, $defaults );

	$transient_name = 'nextgenthemes_remote_get_' . $args['url'];
	$cache = get_transient( $transient_name );

	if ( false === $cache || defined( 'ARVE_DEBUG' ) ) {

		$cache = remote_get( $args['url'], $args['args'], $args['json'] );

		if ( ! is_wp_error( $cache ) ) {
			set_transient( $transient_name, $cache, $args['cache_time'] );
		}
	}

	return $cache;
}