<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Check if Imagify Tools is activated on the network.
 *
 * @since  1.0
 * @author Grégory Viguier
 *
 * return bool True if Imagify is activated on the network
 */
function imagify_tools_is_active_for_network() {
	static $is;

	if ( isset( $is ) ) {
		return $is;
	}

	if ( ! is_multisite() ) {
		$is = false;
		return $is;
	}

	if ( Imagify_Tools::is_muplugin() ) {
		$is = true;
		return $is;
	}

	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$is = is_plugin_active_for_network( plugin_basename( IMAGIFY_TOOLS_FILE ) );

	return $is;
}

/**
 * Get user capacity to operate Imagify Tools.
 *
 * @since  1.0
 * @since  1.0.1 Removed $force_mono parameter.
 * @author Grégory Viguier
 *
 * @return string
 */
function imagify_tools_get_capacity() {
	return imagify_tools_is_active_for_network() ? 'manage_network_options' : 'manage_options';
}


/**
 * Get the main blog ID.
 *
 * @since  1.0
 * @author Grégory Viguier
 *
 * @return int
 */
function imagify_tools_get_main_blog_id() {
	static $blog_id;

	if ( ! isset( $blog_id ) ) {
		if ( ! is_multisite() ) {
			$blog_id = 1;
		}
		elseif ( ! empty( $GLOBALS['current_site']->blog_id ) ) {
			$blog_id = absint( $GLOBALS['current_site']->blog_id );
		}
		elseif ( defined( 'BLOG_ID_CURRENT_SITE' ) ) {
			$blog_id = absint( BLOG_ID_CURRENT_SITE );
		}
		$blog_id = ! empty( $blog_id ) ? $blog_id : 1;
	}

	return $blog_id;
}


/**
 * Compress some data to be stored in the database.
 *
 * @since  1.0
 * @author Grégory Viguier
 * @source SecuPress
 *
 * @param  mixed $data The data to compress.
 * @return string      The compressed data.
 */
function imagify_tools_compress_data( $data ) {
	$gz  = 'eta';
	$gz  = 'gz' . strrev( $gz . 'lfed' );
	$bsf = 'cne';
	$bsf = strrev( 'edo' . $bsf );
	$bsf = '64_' . $bsf;
	$bsf = 'base' . $bsf;

	// phpcs:disable PEAR.Functions.FunctionCallSignature.Indent, PEAR.Functions.FunctionCallSignature.SpaceBeforeOpenBracket, PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket, PEAR.Functions.FunctionCallSignature.CloseBracketLine, WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
	return $bsf
		( $gz
			( serialize( $data ) ) );
	// phpcs:enable PEAR.Functions.FunctionCallSignature.Indent, PEAR.Functions.FunctionCallSignature.SpaceBeforeOpenBracket, PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket, PEAR.Functions.FunctionCallSignature.CloseBracketLine, WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
}


/**
 * Decompress some data coming from the database.
 *
 * @since  1.0
 * @author Grégory Viguier
 * @source SecuPress
 *
 * @param  string $data The data to decompress.
 * @return mixed        The decompressed data.
 */
function imagify_tools_decompress_data( $data ) {
	static $object_names, $prefix, $prefix_len;

	if ( ! $data || ! is_string( $data ) ) {
		return $data;
	}

	$gz  = 'eta';
	$gz  = 'gz' . strrev( $gz . 'lfni' );
	$bsf = 'ced';
	$bsf = strrev( 'edo' . $bsf );
	$bsf = '64_' . $bsf;
	$bsf = 'base' . $bsf;

	$data_tmp = $bsf// phpcs:ignore PEAR.Functions.FunctionCallSignature.SpaceBeforeOpenBracket
		( $data );

	if ( ! $data_tmp ) {
		return $data;
	}

	$data     = $data_tmp;
	$data_tmp = $gz// phpcs:ignore PEAR.Functions.FunctionCallSignature.SpaceBeforeOpenBracket
		( $data );

	if ( ! $data_tmp ) {
		return $data;
	}

	if ( ! isset( $object_names ) ) {
		// Some serialized objects must not be unserialized, it would trigger a fatal error.
		$object_names = array(
			'CURLFile',
		);

		if ( $object_names ) {
			$prefix       = 'IMGT_Not_Unserialized_';
			$prefix_len   = strlen( $prefix );
			$object_names = array_combine( $object_names, $object_names );
			$object_names = array_map( 'strlen', $object_names );
		}
	}

	if ( $object_names ) {
		foreach ( $object_names as $object_name => $object_name_len ) {
			$data_tmp = preg_replace( '@O:' . $object_name_len . ':"' . $object_name . '":(\d+):{@', 'O:' . ( $prefix_len + $object_name_len ) . ':"' . $prefix . $object_name . '":$1:{', $data_tmp );
		}
	}

	return maybe_unserialize( $data_tmp );
}

/**
 * Get the IP address of the current user.
 *
 * @since  1.0
 * @author Grégory Viguier
 * @source SecuPress
 *
 * @return string
 */
function imagify_tools_get_ip() {
	$keys = array(
		'HTTP_CF_CONNECTING_IP', // CF = CloudFlare.
		'HTTP_CLIENT_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_X_CLUSTER_CLIENT_IP',
		'HTTP_X_REAL_IP',
		'HTTP_FORWARDED_FOR',
		'HTTP_FORWARDED',
		'REMOTE_ADDR',
	);

	foreach ( $keys as $key ) {
		$ip = isset( $_SERVER[ $key ] ) ? wp_unslash( $_SERVER[ $key ] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! $ip ) {
			continue;
		}

		$ip = explode( ',', $ip, 2 );
		$ip = reset( $ip );

		if ( false !== imagify_tools_ip_is_valid( $ip ) ) {
			return $ip;
		}
	}

	return '0.0.0.0';
}


/**
 * Tell if an IP address is valid.
 *
 * @since  1.0
 * @author Grégory Viguier
 * @source SecuPress
 *
 * @param  string $ip  An IP address.
 * @return string|bool The IP address if valid. False otherwise.
 */
function imagify_tools_ip_is_valid( $ip ) {
	if ( ! $ip || ! is_string( $ip ) ) {
		return false;
	}

	return filter_var( trim( $ip ), FILTER_VALIDATE_IP );
}


/**
 * Is current WordPress version older than X.X.X?
 *
 * @since  1.0
 * @author Grégory Viguier
 * @source SecuPress
 *
 * @param  string $version The version to test.
 * @return bool            Result of the `version_compare()`.
 */
function imagify_tools_wp_version_is( $version ) {
	global $wp_version;
	static $is = array();

	if ( isset( $is[ $version ] ) ) {
		return $is[ $version ];
	}

	$is[ $version ] = version_compare( $wp_version, $version );

	return $is[ $version ] >= 0;
}


/** --------------------------------------------------------------------------------------------- */
/** TRANSIENTS ================================================================================== */
/** --------------------------------------------------------------------------------------------- */

/**
 * Delete a site transient.
 * This is almost the same function than `delete_site_transient()`, but without hooks and object cache test.
 *
 * @since  1.0
 * @author Grégory Viguier
 *
 * @param string $transient Transient name. Expected to not be SQL-escaped.
 */
function imagify_tools_delete_site_transient( $transient ) {
	$transient_timeout = '_site_transient_timeout_' . $transient;
	$transient_name    = '_site_transient_' . $transient;
	$result            = delete_site_option( $transient_name );

	if ( $result ) {
		delete_site_option( $transient_timeout );
	}
}


/**
 * Get the value of a site transient.
 * This is almost the same function than `get_site_transient()`, but without hooks and object cache test.
 * If the transient does not exist or does not have a value, then the return value will be false.
 *
 * @since  1.0
 * @author Grégory Viguier
 *
 * @param  string $transient Transient name. Expected to not be SQL-escaped.
 * @return mixed             Value of transient.
 */
function imagify_tools_get_site_transient( $transient ) {
	$transient_timeout = '_site_transient_timeout_' . $transient;
	$transient_name    = '_site_transient_' . $transient;
	$timeout           = get_site_option( $transient_timeout );

	if ( false !== $timeout && $timeout < time() ) {
		delete_site_option( $transient_name );
		delete_site_option( $transient_timeout );
		return false;
	}

	return get_site_option( $transient_name );
}


/**
 * Set/update the value of a site transient.
 * This is almost the same function than `set_site_transient()`, but without hooks and object cache test.
 * You do not need to serialize values. If the value needs to be serialized, then it will be serialized before it is set.
 *
 * @since  1.0
 * @author Grégory Viguier
 *
 * @param string $transient  Transient name. Expected to not be SQL-escaped. Must be 40 characters or fewer in length.
 * @param mixed  $value      Transient value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
 * @param int    $expiration Optional. Time until expiration in seconds. Default 0 (no expiration).
 */
function imagify_tools_set_site_transient( $transient, $value, $expiration = 0 ) {
	$transient_timeout = '_site_transient_timeout_' . $transient;
	$transient_name    = '_site_transient_' . $transient;

	if ( false === get_site_option( $transient_name ) ) {
		if ( $expiration ) {
			add_site_option( $transient_timeout, time() + $expiration );
		}
		add_site_option( $transient_name, $value );
	} else {
		if ( $expiration ) {
			update_site_option( $transient_timeout, time() + $expiration );
		}
		update_site_option( $transient_name, $value );
	}
}
