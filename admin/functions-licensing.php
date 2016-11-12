<?php

function nextgenthemes_get_products() {

	$products = array(
		'arve_pro' => array(
			'name'    => 'Advanced Responsive Video Embedder Pro',
	    'type'    => 'plugin',
			'author'  => 'Nicolas Jonas',
			'url'     => 'https://nextgenthemes.com/plugins/advanced-responsive-video-embedder-pro/',
		),
		'arve_amp' => array(
			'name'   => 'ARVE Accelerated Mobile Pages Addon',
			'type'   => 'plugin',
			'author' => 'Nicolas Jonas',
			'url'    => 'https://nextgenthemes.com/plugins/arve-accelerated-mobile-pages-addon/',
		)
	);

	$products = apply_filters( 'nextgenthemes_products', $products );

	foreach ( $products as $key => $value ) {
		$products[ $key ]['slug'] = $key;
	}

	return $products;
}

/**
 * Register the administration menu for this plugin into the WordPress Dashboard menu.
 *
 * @since    1.0.0
 */
function nextgenthemes_menus() {

 	$plugin_screen_hook_suffix = add_menu_page(
 		__( 'Nextgenthemes', ARVE_SLUG ), # Page Title
 		__( 'Nextgenthemes', ARVE_SLUG ), # Menu Tile
 		'manage_options',                 # capability
 		'nextgenthemes',                  # menu-slug
 		'__return_empty_string',          # function
		'dashicons-admin-settings',       # icon_url
		null                              # position
 	);

	/*
  add_submenu_page(
    'nextgenthemes',                      # parent_slug
    __( 'Addons and Themes', ARVE_SLUG ), # Page Title
    __( 'Addons and Themes', ARVE_SLUG ), # Menu Tile
    'manage_options',                     # capability
    'nextgenthemes',                      # menu-slug
    function() {
      require_once plugin_dir_path( __FILE__ ) . 'html-ad-page.php';
    }
  );
	*/

	add_submenu_page(
		'nextgenthemes',              # parent_slug
		__( 'Licenses', ARVE_SLUG ),  # Page Title
		__( 'Licenses', ARVE_SLUG ),  # Menu Tile
		'manage_options',             # capability
		'nextgenthemes-licenses',     # menu-slug
		'nextgenthemes_licenses_page' # function
	);
}

function nextgenthemes_register_settings() {

	add_settings_section(
		'keys',                      # id,
		__( 'Licenses', ARVE_SLUG ), # title,
		'__return_empty_string',     # callback,
		'nextgenthemes-licenses'     # page
	);

	foreach ( nextgenthemes_get_products() as $product_slug => $product ) :

		$option_basename = "nextgenthemes_{$product_slug}_key";
		$option_keyname  = $option_basename . '[key]';

		add_settings_field(
			$option_keyname,              # id,
			$product['name'],             # title,
			'nextgenthemes_key_callback', # callback,
			'nextgenthemes-licenses',     # page,
			'keys',                       # section
			array(                        # args
				'product'         => $product,
				'label_for'       => $option_keyname,
				'option_basename' => $option_basename,
				'attr'            => array(
					'type'  => 'text',
					'id'    => $option_keyname,
					'name'  => $option_keyname,
					'class' => 'medium-text',
					'value' => nextgenthemes_get_defined_key( $product_slug ) ? __( 'is defined (wp-config.php?)', ARVE_SLUG ) : nextgenthemes_get_key( $product_slug, 'option_only' ),
				)
			)
		);

		register_setting(
			'nextgenthemes',  # option_group
			$option_basename, # option_name
			'nextgenthemes_validate_license' # validation callback
		);

	endforeach;
}

function nextgenthemes_key_callback( $args ) {

	echo '<p>';

	printf( '<input%s>', arve_attr( array(
		'type'  => 'hidden',
		'id'    => $args['option_basename'] . '[product]',
		'name'  => $args['option_basename'] . '[product]',
		'value' => $args['product']['slug'],
	) ) );

	printf(
		'<input%s%s>',
		arve_attr( $args['attr'] ),
		nextgenthemes_get_defined_key( $args['product']['slug'] ) ? ' disabled' : ''
	);

	$defined_key = nextgenthemes_get_defined_key( $args['product']['slug'] );
	$key = nextgenthemes_get_key( $args['product']['slug'] );

	if( $defined_key || ! empty( $key ) ) {

		submit_button( __('Activate License',   ARVE_SLUG ), 'primary',   $args['option_basename'] . '[activate_key]',   false );
		submit_button( __('Deactivate License', ARVE_SLUG ), 'secondary', $args['option_basename'] . '[deactivate_key]', false );
		submit_button( __('Check License',      ARVE_SLUG ), 'secondary', $args['option_basename'] . '[check_key]',      false );
  }
	echo '</p>';

  echo '<p>';
  echo __( 'License Status: ', ARVE_SLUG ) . nextgenthemes_get_key_status( $args['product']['slug'] );
  echo '</p>';

  if ( 'plugin' == $args['product']['type'] ) {

    if ( empty( $args['product']['file'] ) ) {
      $plugin_active = false;
    } else {
			$plugin_active = nextgenthemes_is_plugin_active( $args['product']['file'] );
		}

    if( ! $plugin_active && nextgenthemes_is_plugin_installed( $args['product']['slug'] ) ) {
			_e( 'Plugin is installed but not activated', ARVE_SLUG );
		} elseif( ! $plugin_active ) {
      printf(
				'<a%s>%s</a>',
				arve_attr( array(
					'href'  => $args['product']['url'],
					'class' => 'button button-primary',
				) ),
				__( 'Not installed, check it out', ARVE_SLUG )
			);
    }
  }
}

function nextgenthemes_is_plugin_active( $file ) {

	$dir_file = basename( dirname( $file ) ) . DIRECTORY_SEPARATOR . basename( $file );

	return is_plugin_active( $dir_file );
}

function nextgenthemes_is_plugin_installed( $slug ) {

	$plugins = get_plugins();

	$slug = str_replace( '_', '-', $slug );

	$dir_file = $slug . DIRECTORY_SEPARATOR . $slug . '.php';

	if( array_key_exists( $dir_file, $plugins ) ) {
		return true;
	} else {
		return false;
	}
}

function nextgenthemes_validate_license( $input ) {

	if( ! is_array( $input ) ) {
		return 'bug, report please';
	}

	$product = $input['product'];

	if ( $defined_key = nextgenthemes_get_defined_key( $product ) ) {
		$option_key = $key = $defined_key;
	} else {
		$key        = sanitize_text_field( $input['key'] );
		$option_key = nextgenthemes_get_key( $product );
	}

	if( ( $key != $option_key ) || isset( $input['activate_key'] ) ) {

		nextgenthemes_api_update_key_status( $product, $key, 'activate' );

	} elseif ( isset( $input['deactivate_key'] ) ) {

		nextgenthemes_api_update_key_status( $product, $key, 'deactivate' );

	} elseif ( isset( $input['check_key'] ) ) {

		nextgenthemes_api_update_key_status( $product, $key, 'check' );
	}

	return $key;
}

function nextgenthemes_get_key( $product, $option_only = false ) {

	if( ! $option_only && $defined_key = nextgenthemes_get_defined_key( $product ) ) {
		return $defined_key;
	}

	return get_option( "nextgenthemes_{$product}_key" );
}
function nextgenthemes_get_key_status( $product ) {
	return get_option( "nextgenthemes_{$product}_key_status" );
}
function nextgenthemes_update_key_status( $product, $key ) {
	update_option( "nextgenthemes_{$product}_key_status", $key );
}
function nextgenthemes_has_valid_key( $product ) {
	return ( 'valid' == nextgenthemes_get_key_status( $product ) ) ? true : false;
}

function nextgenthemes_api_update_key_status( $product, $key, $action ) {

	$products   = nextgenthemes_get_products();
	$key_status = nextgenthemes_api_action( $products[ $product ]['name'], $key, $action );

	nextgenthemes_update_key_status( $product, $key_status );
}

function nextgenthemes_get_defined_key( $slug ) {

	$constant_name = str_replace( '-', '_', strtoupper( $slug . '_KEY' ) );

	if( defined( $constant_name ) && constant( $constant_name ) ) {
		return constant( $constant_name );
	} else {
		return false;
	}
}

function nextgenthemes_licenses_page() {
?>
	<div class="wrap">

		<h2><?php esc_html_e( get_admin_page_title() ); ?></h2>

		<form method="post" action="options.php">

			<?php do_settings_sections( 'nextgenthemes-licenses' ); ?>
			<?php settings_fields( 'nextgenthemes' ); ?>
			<?php submit_button( __( 'Save Changes' ), 'primary', 'submit', false ); ?>
		</form>

	</div>
<?php
}

function nextgenthemes_init_edd_updaters() {

	$products = nextgenthemes_get_products();

	foreach ( $products as $product ) {

		if ( 'plugin' == $product['type'] && ! empty( $product['file'] ) ) {
			nextgenthemes_init_plugin_updater( $product );
		} elseif ( 'theme' == $product['type'] ) {
			nextgenthemes_init_theme_updater( $product );
		}
	}
}

function nextgenthemes_init_plugin_updater( $product ) {

	// setup the updater
	new EDD_SL_Plugin_Updater(
		'https://nextgenthemes.com',
		$product['file'],
		array(
			'version' 	=> $product['version'],
			'license' 	=> nextgenthemes_get_key( $product['slug'] ),
			'item_name' => $product['name'],
			'author' 	  => $product['author']
		)
	);
}

function nextgenthemes_init_theme_updater( $product ) {

	new EDD_Theme_Updater(
		array(
			'remote_api_url' 	=> 'https://nextgenthemes.com',
			'version' 			  => $product['version'],
			'license' 			  => nextgenthemes_get_key( $product['slug'] ),
			'item_name' 		  => $product['name'],
			'author'			    => $product['author'],
			'theme_slug'      => $product['slug'],
			'download_id'     => $product['download_id'], // Optional, used for generating a license renewal link
			#'renew_url'       => $product['renew_link'], // Optional, allows for a custom license renewal link
		),
		array(
			'theme-license'             => __( 'Theme License', 'edd-theme-updater' ),
			'enter-key'                 => __( 'Enter your theme license key.', 'edd-theme-updater' ),
			'license-key'               => __( 'License Key', 'edd-theme-updater' ),
			'license-action'            => __( 'License Action', 'edd-theme-updater' ),
			'deactivate-license'        => __( 'Deactivate License', 'edd-theme-updater' ),
			'activate-license'          => __( 'Activate License', 'edd-theme-updater' ),
			'status-unknown'            => __( 'License status is unknown.', 'edd-theme-updater' ),
			'renew'                     => __( 'Renew?', 'edd-theme-updater' ),
			'unlimited'                 => __( 'unlimited', 'edd-theme-updater' ),
			'license-key-is-active'     => __( 'License key is active.', 'edd-theme-updater' ),
			'expires%s'                 => __( 'Expires %s.', 'edd-theme-updater' ),
			'expires-never'             => __( 'Lifetime License.', 'edd-theme-updater' ),
			'%1$s/%2$-sites'            => __( 'You have %1$s / %2$s sites activated.', 'edd-theme-updater' ),
			'license-key-expired-%s'    => __( 'License key expired %s.', 'edd-theme-updater' ),
			'license-key-expired'       => __( 'License key has expired.', 'edd-theme-updater' ),
			'license-keys-do-not-match' => __( 'License keys do not match.', 'edd-theme-updater' ),
			'license-is-inactive'       => __( 'License is inactive.', 'edd-theme-updater' ),
			'license-key-is-disabled'   => __( 'License key is disabled.', 'edd-theme-updater' ),
			'site-is-inactive'          => __( 'Site is inactive.', 'edd-theme-updater' ),
			'license-status-unknown'    => __( 'License status is unknown.', 'edd-theme-updater' ),
			'update-notice'             => __( "Updating this theme will lose any customizations you have made. 'Cancel' to stop, 'OK' to update.", 'edd-theme-updater' ),
			'update-available'          => __('<strong>%1$s %2$s</strong> is available. <a href="%3$s" class="thickbox" title="%4s">Check out what\'s new</a> or <a href="%5$s"%6$s>update now</a>.', 'edd-theme-updater' ),
		)
	);
}

function nextgenthemes_api_action( $item_name, $key, $action ) {

	if ( ! in_array( $action, array( 'activate', 'deactivate', 'check' ) ) ) {
		wp_die( 'invalid action' );
	}

	// Data to send to the API
	$api_params = array(
		'edd_action' => $action . '_license',
		'license'    => sanitize_text_field( $key ),
		'item_name'  => urlencode( $item_name ),
		'url'        => home_url(),
	);

	$response = wp_remote_post( 'https://nextgenthemes.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

	// Make sure there are no errors
	if ( is_wp_error( $response ) ) {
		return $response->get_error_message();
	}

	// Tell WordPress to look for updates
	set_site_transient( 'update_plugins', null );

	// Decode the license data
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	if( ! (bool) $license_data->success ) {
		set_transient( 'arve_license_error', $license_data, 1000 );

		if( empty( $license_data->error ) ) {
			return var_export( $license_data, true );
		} else {
			return $license_data->error;
		}
	} else {
		delete_transient( 'arve_license_error' );

		if( empty( $license_data->license ) ) {
			return 'API seems not to be accessible';
		} else {
			return $license_data->license;
		}
	}
}

function arve_pro_action_admin_notices() {

	$license_error = get_transient( 'arve_license_error' );

	if( false === $license_error ) {
		return;
	}

	if( ! empty( $license_error->error ) ) {

		switch( $license_error->error ) {

			case 'item_name_mismatch':

				$message = __( 'This license does not belong to the product you have entered it for.', 'arve-pro' );
				break;

			case 'no_activations_left':

				$message = __( 'This license does not have any activations left', 'arve-pro' );
				break;

			case 'expired':

				$message = __( 'This license key is expired. Please renew it.', 'arve-pro' );
				break;

			default:

				$message = sprintf( __( 'There was a problem activating your license key, please try again or contact support. Error code: %s', 'arve-pro' ), $license_error->error );
				break;
		}
	}

	if( ! empty( $message ) ) {

		echo '<div class="error">';
			echo '<p>' . $message . '</p>';
		echo '</div>';

	}

	delete_transient( 'edd_license_error' );
}