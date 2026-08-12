<?php
/**
 * Plugin Name: Imagify Skipper
 * Description: Adds a Tools settings page to configure which file formats, uploads, and folders Imagify should skip or exclude from optimization, without editing code.
 * Plugin URI:  https://github.com/wp-media/imagify-helpers
 * Author:      Saransh Jain
 * Author URI:  http://imagify.io/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: imagify-skipper
 * Domain Path: /languages
 *
 * Copyright SAS WP MEDIA 2026
 */

namespace ImagifyPlugin\Helpers\skipper;

defined( 'ABSPATH' ) || die();

const OPTION_KEY = 'imagify_skipper_settings';

const DOC_URL = 'https://imagify.io/documentation/imagify-optimization-filter-hooks/';

const REVIEW_URL = 'https://wordpress.org/support/plugin/imagify/reviews/#new-post';

const SUPPORT_URL = 'https://imagify.io/contact/';

/**
 * Distinct MIME types for the auto-optimize and bulk-optimize exclusions.
 */
const MIME_TYPES = array(
	'image/gif'       => 'GIF',
	'image/jpeg'      => 'JPG / JPEG',
	'image/png'       => 'PNG',
	'application/pdf' => 'PDF',
);

function load_textdomain() {
	load_plugin_textdomain( 'imagify-skipper', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', __NAMESPACE__ . '\load_textdomain' );

/**
 * @return array {
 *     @type array  $skip_auto_optimize_mimes Mime type strings to skip on upload.
 *     @type array  $bulk_exclude_mimes       Mime type strings to skip during bulk optimization.
 *     @type string $bulk_exclude_before_date Y-m-d date; media uploaded before this is excluded from bulk runs.
 *     @type array  $bulk_exclude_paths       Path substrings excluded from bulk runs.
 * }
 */
function get_settings() {
	$defaults = array(
		'skip_auto_optimize_mimes' => array(),
		'bulk_exclude_mimes'       => array(),
		'bulk_exclude_before_date' => '',
		'bulk_exclude_paths'       => array(),
	);

	$saved = get_option( OPTION_KEY, array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return wp_parse_args( $saved, $defaults );
}

function register_settings() {
	register_setting(
		'imagify_skipper_group',
		OPTION_KEY,
		array(
			'type'              => 'array',
			'sanitize_callback' => __NAMESPACE__ . '\sanitize_settings',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', __NAMESPACE__ . '\register_settings' );

/**
 * @param mixed $input Raw posted value for the option.
 * @return array Sanitized settings.
 */
function sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	$clean = array();

	$posted_skip_auto                  = isset( $input['skip_auto_optimize_mimes'] ) && is_array( $input['skip_auto_optimize_mimes'] )
		? array_map( 'sanitize_text_field', $input['skip_auto_optimize_mimes'] )
		: array();
	$clean['skip_auto_optimize_mimes'] = array_values( array_intersect( $posted_skip_auto, array_keys( MIME_TYPES ) ) );

	$posted_bulk_mimes            = isset( $input['bulk_exclude_mimes'] ) && is_array( $input['bulk_exclude_mimes'] )
		? array_map( 'sanitize_text_field', $input['bulk_exclude_mimes'] )
		: array();
	$clean['bulk_exclude_mimes'] = array_values( array_intersect( $posted_bulk_mimes, array_keys( MIME_TYPES ) ) );

	$posted_date                       = isset( $input['bulk_exclude_before_date'] ) ? sanitize_text_field( wp_unslash( $input['bulk_exclude_before_date'] ) ) : '';
	$clean['bulk_exclude_before_date'] = is_valid_ymd_date( $posted_date ) ? $posted_date : '';

	$posted_paths                = isset( $input['bulk_exclude_paths'] ) ? sanitize_textarea_field( wp_unslash( $input['bulk_exclude_paths'] ) ) : '';
	$path_lines                  = array_filter( array_map( 'trim', explode( "\n", $posted_paths ) ) );
	$clean['bulk_exclude_paths'] = array_values( array_map( 'sanitize_text_field', $path_lines ) );

	return $clean;
}

/**
 * @param string $date Date string to check.
 * @return bool
 */
function is_valid_ymd_date( $date ) {
	if ( ! is_string( $date ) || '' === $date ) {
		return false;
	}

	$parsed = \DateTime::createFromFormat( 'Y-m-d', $date );

	return $parsed && $parsed->format( 'Y-m-d' ) === $date;
}

function register_settings_page() {
	add_management_page(
		__( 'Imagify Skipper', 'imagify-skipper' ),
		__( 'Imagify Skipper', 'imagify-skipper' ),
		'manage_options',
		'imagify-skipper',
		__NAMESPACE__ . '\render_settings_page'
	);
}
add_action( 'admin_menu', __NAMESPACE__ . '\register_settings_page' );

/**
 * @param array $links Existing action links (Activate/Deactivate, etc).
 * @return array
 */
function add_settings_link( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'tools.php?page=imagify-skipper' ) ) . '">' . esc_html__( 'Settings', 'imagify-skipper' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), __NAMESPACE__ . '\add_settings_link' );

function render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = get_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Imagify Skipper', 'imagify-skipper' ); ?></h1>
		<p><?php esc_html_e( 'Configure which formats and uploads Imagify should skip, without editing any code.', 'imagify-skipper' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'imagify_skipper_group' ); ?>

			<h2><?php esc_html_e( 'Skip auto-optimization on upload', 'imagify-skipper' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Uses the imagify_auto_optimize_attachment filter. New uploads of these formats will not be optimized automatically.', 'imagify-skipper' ); ?>
			</p>
			<table class="form-table" role="presentation">
				<?php foreach ( MIME_TYPES as $mime => $label ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $label ); ?></th>
						<td>
							<label>
								<input
									type="checkbox"
									name="<?php echo esc_attr( OPTION_KEY ); ?>[skip_auto_optimize_mimes][]"
									value="<?php echo esc_attr( $mime ); ?>"
									<?php checked( in_array( $mime, $settings['skip_auto_optimize_mimes'], true ) ); ?>
								/>
								<?php
								printf(
									/* translators: %s: mime type, e.g. image/gif */
									esc_html__( 'Skip auto-optimization for %s', 'imagify-skipper' ),
									esc_html( $mime )
								);
								?>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2><?php esc_html_e( 'Exclude from Bulk Optimization', 'imagify-skipper' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Uses the imagify_bulk_optimize_media filter. Applies only to Bulk Optimization on WordPress Media Library items, not Bulk Restore, Next-Gen generation, or custom folders. Excluded items still count toward the displayed total.', 'imagify-skipper' ); ?>
			</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'By format', 'imagify-skipper' ); ?></th>
					<td>
						<?php foreach ( MIME_TYPES as $mime => $label ) : ?>
							<label style="display:block;">
								<input
									type="checkbox"
									name="<?php echo esc_attr( OPTION_KEY ); ?>[bulk_exclude_mimes][]"
									value="<?php echo esc_attr( $mime ); ?>"
									<?php checked( in_array( $mime, $settings['bulk_exclude_mimes'], true ) ); ?>
								/>
								<?php echo esc_html( $label . ' (' . $mime . ')' ); ?>
							</label>
						<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="imagify-skipper-cutoff-date"><?php esc_html_e( 'Exclude uploads before date', 'imagify-skipper' ); ?></label>
					</th>
					<td>
						<input
							type="date"
							id="imagify-skipper-cutoff-date"
							name="<?php echo esc_attr( OPTION_KEY ); ?>[bulk_exclude_before_date]"
							value="<?php echo esc_attr( $settings['bulk_exclude_before_date'] ); ?>"
						/>
						<p class="description"><?php esc_html_e( 'Media uploaded before this date is skipped during bulk runs. Leave blank to disable.', 'imagify-skipper' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="imagify-skipper-paths"><?php esc_html_e( 'Exclude by folder/path', 'imagify-skipper' ); ?></label>
					</th>
					<td>
						<textarea
							id="imagify-skipper-paths"
							name="<?php echo esc_attr( OPTION_KEY ); ?>[bulk_exclude_paths]"
							rows="4"
							cols="50"
							class="large-text code"
							placeholder="/uploads/2019/&#10;/uploads/elementor/"
						><?php echo esc_textarea( implode( "\n", $settings['bulk_exclude_paths'] ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One path fragment per line. Simple text match, not a pattern or regex.', 'imagify-skipper' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>

		<p>
			<?php
			/* translators: %s: URL of Imagify's filter hooks documentation */
			$doc_notice = __( 'Want to customize this further, or write your own filter? See Imagify\'s official filter hooks documentation: %s', 'imagify-skipper' );
			$doc_link   = '<a href="' . esc_url( DOC_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( DOC_URL ) . '</a>';

			echo wp_kses(
				sprintf( $doc_notice, $doc_link ),
				array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
			);
			?>
		</p>

		<p>
			<?php
			printf(
				/* translators: 1: link to leave a review, 2: link to contact support */
				esc_html__( 'Enjoying Imagify? %1$s. Need help? %2$s.', 'imagify-skipper' ),
				'<a href="' . esc_url( REVIEW_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Leave a review', 'imagify-skipper' ) . '</a>',
				'<a href="' . esc_url( SUPPORT_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Contact support', 'imagify-skipper' ) . '</a>'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * @param bool  $optimize      Whether to optimize the attachment.
 * @param int   $attachment_id The WordPress attachment ID.
 * @param array $metadata      Attachment metadata array.
 * @return bool
 */
function filter_auto_optimize_attachment( $optimize, $attachment_id, $metadata ) {
	unset( $metadata );

	if ( ! $optimize ) {
		return $optimize;
	}

	$settings = get_settings();
	$mime     = get_post_mime_type( $attachment_id );

	if ( $mime && in_array( $mime, $settings['skip_auto_optimize_mimes'], true ) ) {
		return false;
	}

	return $optimize;
}

/**
 * @param bool   $optimize           Whether to optimize this item.
 * @param int    $media_id           The media item ID.
 * @param string $context            Optimization context ('wp' or 'custom-folders').
 * @param int    $optimization_level Selected optimization level.
 * @return bool
 */
function filter_bulk_optimize_media( $optimize, $media_id, $context, $optimization_level ) {
	unset( $optimization_level );

	if ( ! $optimize || 'wp' !== $context ) {
		return $optimize;
	}

	$settings = get_settings();

	$mime = get_post_mime_type( $media_id );
	if ( $mime && in_array( $mime, $settings['bulk_exclude_mimes'], true ) ) {
		return false;
	}

	if ( '' !== $settings['bulk_exclude_before_date'] ) {
		$upload_date = get_the_date( 'Y-m-d', $media_id );
		if ( $upload_date && $upload_date < $settings['bulk_exclude_before_date'] ) {
			return false;
		}
	}

	if ( ! empty( $settings['bulk_exclude_paths'] ) ) {
		$file_path = get_attached_file( $media_id );
		if ( $file_path ) {
			foreach ( $settings['bulk_exclude_paths'] as $fragment ) {
				if ( '' !== $fragment && false !== strpos( $file_path, $fragment ) ) {
					return false;
				}
			}
		}
	}

	return $optimize;
}

function hook_imagify_filters() {
	add_filter( 'imagify_auto_optimize_attachment', __NAMESPACE__ . '\filter_auto_optimize_attachment', 10, 3 );
	add_filter( 'imagify_bulk_optimize_media', __NAMESPACE__ . '\filter_bulk_optimize_media', 10, 4 );
}
add_action( 'imagify_loaded', __NAMESPACE__ . '\hook_imagify_filters' );
