<?php

add_action( 'admin_init', 'nextgenthemes_init_edd_updaters', 0 );
add_action( 'admin_init', 'nextgenthemes_activation_notices' );
add_action( 'admin_init', 'nextgenthemes_register_settings' );
add_action( 'admin_menu', 'nextgenthemes_menus' );

function nextgenthemes_activation_notices() {

	$products = nextgenthemes_get_products();

	foreach ( $products as $key => $value ) {

		if( $value['active'] && ! $value['valid_key'] ) {

			$msg = sprintf(
				__( 'Hi there, thanks for your purchase. One last step, please activate your %s <a href="%s">here now</a>.', ARVE_SLUG ),
				$value['name'],
				get_admin_url() . 'admin.php?page=nextgenthemes-licenses'
			);
			new ARVE_Admin_Notice_Factory( $key . '-activation-notice', "<p>$msg</p>", false );
		}
	}
}

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

		$products[ $key ]['slug']      = $key;
		$products[ $key ]['installed'] = false;
		$products[ $key ]['active']    = false;
		$products[ $key ]['valid_key'] = nextgenthemes_has_valid_key( $key );

		$version_define = strtoupper( $key ) . '_VERSION';
		$file_define    = strtoupper( $key ) . '_FILE';

		if( defined( $version_define ) ) {
			$products[ $key ]['version'] = constant( $version_define );
		}
		if( defined( $file_define ) ) {
			$products[ $key ]['file'] = constant( $file_define );
		}

		if ( 'plugin' == $value['type'] ) {

			$file_slug = str_replace( '_', '-', $key );

			$products[ $key ]['installed'] = nextgenthemes_is_plugin_installed( "$file_slug/$file_slug.php" );

			if ( ! empty( $products[ $key ]['file'] ) ) {
				$plugin_basename = plugin_basename( $products[ $key ]['file'] );
				$products[ $key ]['active'] = is_plugin_active( $plugin_basename );
			}
		}
	}

	return $products;
}

function nextgenthemes_is_plugin_installed( $plugin_basename ) {

	$plugins = get_plugins();

	if( array_key_exists( $plugin_basename, $plugins ) ) {
		return true;
	} else {
		return false;
	}
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
		'80.892'                          # position
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
					'class' => 'arve-license-input',
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
	$key         = nextgenthemes_get_key(         $args['product']['slug'] );

	if( $defined_key || ! empty( $key ) ) {

		submit_button( __('Activate License',   ARVE_SLUG ), 'primary',   $args['option_basename'] . '[activate_key]',   false );
		submit_button( __('Deactivate License', ARVE_SLUG ), 'secondary', $args['option_basename'] . '[deactivate_key]', false );
		submit_button( __('Check License',      ARVE_SLUG ), 'secondary', $args['option_basename'] . '[check_key]',      false );
  }
	echo '</p>';

  echo '<p>';
  echo __( 'License Status: ', ARVE_SLUG ) . nextgenthemes_get_key_status( $args['product']['slug'] );
  echo '</p>';

  if( $args['product']['installed'] && ! $args['product']['active'] ) {
		printf( '<strong>%s</strong>', __( 'Plugin is installed but not activated', ARVE_SLUG ) );
	} elseif( ! $args['product']['active'] ) {
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

function nextgenthemes_validate_license( $input ) {

	if( ! is_array( $input ) ) {
		return sanitize_text_field( $input );
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
			'theme-license'             => __( 'Theme License', ARVE_SLUG ),
			'enter-key'                 => __( 'Enter your theme license key.', ARVE_SLUG ),
			'license-key'               => __( 'License Key', ARVE_SLUG ),
			'license-action'            => __( 'License Action', ARVE_SLUG ),
			'deactivate-license'        => __( 'Deactivate License', ARVE_SLUG ),
			'activate-license'          => __( 'Activate License', ARVE_SLUG ),
			'status-unknown'            => __( 'License status is unknown.', ARVE_SLUG ),
			'renew'                     => __( 'Renew?', ARVE_SLUG ),
			'unlimited'                 => __( 'unlimited', ARVE_SLUG ),
			'license-key-is-active'     => __( 'License key is active.', ARVE_SLUG ),
			'expires%s'                 => __( 'Expires %s.', ARVE_SLUG ),
			'expires-never'             => __( 'Lifetime License.', ARVE_SLUG ),
			'%1$s/%2$-sites'            => __( 'You have %1$s / %2$s sites activated.', ARVE_SLUG ),
			'license-key-expired-%s'    => __( 'License key expired %s.', ARVE_SLUG ),
			'license-key-expired'       => __( 'License key has expired.', ARVE_SLUG ),
			'license-keys-do-not-match' => __( 'License keys do not match.', ARVE_SLUG ),
			'license-is-inactive'       => __( 'License is inactive.', ARVE_SLUG ),
			'license-key-is-disabled'   => __( 'License key is disabled.', ARVE_SLUG ),
			'site-is-inactive'          => __( 'Site is inactive.', ARVE_SLUG ),
			'license-status-unknown'    => __( 'License status is unknown.', ARVE_SLUG ),
			'update-notice'             => __( "Updating this theme will lose any customizations you have made. 'Cancel' to stop, 'OK' to update.", ARVE_SLUG ),
			'update-available'          => __('<strong>%1$s %2$s</strong> is available. <a href="%3$s" class="thickbox" title="%4s">Check out what\'s new</a> or <a href="%5$s"%6$s>update now</a>.', ARVE_SLUG ),
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

	$response = wp_remote_post( 'https://nextgenthemes.com', array( 'timeout' => 15, 'sslverify' => true, 'body' => $api_params ) );

	// make sure the response came back okay
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
		} else {
			$message = __( 'An error occurred, please try again.', ARVE_SLUG );
		}

	} else {

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( false === $license_data->success ) :

			switch( $license_data->error ) {

				case 'expired' :

					$message = sprintf(
						__( 'Your license key expired on %s.', ARVE_SLUG ),
						date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
					);
					break;

				case 'revoked' :

					$message = __( 'Your license key has been disabled.', ARVE_SLUG );
					break;

				case 'missing' :

					$message = __( 'Invalid license.', ARVE_SLUG );
					break;

				case 'invalid' :
				case 'site_inactive' :

					$message = __( 'Your license is not active for this URL.', ARVE_SLUG );
					break;

				case 'item_name_mismatch' :

					$message = sprintf( __( 'This appears to be an invalid license key for %s.', ARVE_SLUG ), $item_name );
					break;

				case 'no_activations_left':

					$message = __( 'Your license key has reached its activation limit.', ARVE_SLUG );
					break;

				default :

					$message = __( 'An error occurred, please try again.', ARVE_SLUG );
					break;
			}

		endif; // false === $license_data->success
	}

	if( empty( $message ) ) {
		$message = $license_data->license;
	}

	return $message;
}
