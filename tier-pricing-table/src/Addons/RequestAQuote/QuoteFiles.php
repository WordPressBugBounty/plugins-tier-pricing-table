<?php namespace TierPricingTable\Addons\RequestAQuote;

use TierPricingTable\Addons\RequestAQuote\CPT\QuoteRequestCPT;
use TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Files customers attach to quote requests. They are kept in a folder of their own that the web server is told
 * not to serve, under names nobody can guess, and store managers get them through a download link that checks
 * who is asking.
 */
class QuoteFiles {

	/**
	 * The folder inside the uploads folder.
	 */
	public const FOLDER = 'tpt-quote-uploads';

	public const DOWNLOAD_ACTION = 'tpt_quote_file';

	public static function registerDownloadHandler() {
		add_action( 'admin_post_' . self::DOWNLOAD_ACTION, array( __CLASS__, 'download' ) );
		// a store manager who opens the link from an e-mail logs in first, then gets the file
		add_action( 'admin_post_nopriv_' . self::DOWNLOAD_ACTION, 'auth_redirect' );
	}

	/**
	 * Save one uploaded file in the private folder.
	 *
	 * @param  array  $file  One entry of $_FILES.
	 *
	 * @return array{file: string, name: string}|WP_Error The saved file's path and the customer's file name.
	 */
	public static function upload( array $file ) {
		$folder = self::getFolder();

		if ( is_wp_error( $folder ) ) {
			return $folder;
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$toFolder = function ( $dirs ) {
			$dirs['subdir'] = '/' . self::FOLDER;
			$dirs['path']   = $dirs['basedir'] . $dirs['subdir'];
			$dirs['url']    = $dirs['baseurl'] . $dirs['subdir'];

			return $dirs;
		};

		add_filter( 'upload_dir', $toFolder, 999 );

		$moved = wp_handle_upload( $file, array(
			'test_form'                => false,
			// a random part nobody can guess, in case the server ignores the folder's .htaccess (nginx)
			'unique_filename_callback' => function ( $dir, $name, $ext ) {
				$random = strtolower( wp_generate_password( 24, false ) );

				return '' !== $name ? $random . '-' . $name : $random . $ext;
			},
		) );

		remove_filter( 'upload_dir', $toFolder, 999 );

		if ( ! is_array( $moved ) || isset( $moved['error'] ) || empty( $moved['file'] ) ) {
			return new WP_Error( 'upload_failed', is_array( $moved ) && ! empty( $moved['error'] ) ? $moved['error'] : __( 'File upload failed.', 'tier-pricing-table' ), array( 'status' => 500 ) );
		}

		return array(
			'file' => $moved['file'],
			'name' => sanitize_file_name( (string) $file['name'] ),
		);
	}

	/**
	 * The private folder, created on first use with the files that keep it closed.
	 *
	 * @return string|WP_Error The folder's path.
	 */
	public static function getFolder() {
		$uploads = wp_get_upload_dir();

		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error( 'upload_failed', $uploads['error'], array( 'status' => 500 ) );
		}

		$folder = trailingslashit( $uploads['basedir'] ) . self::FOLDER;

		if ( ! wp_mkdir_p( $folder ) ) {
			return new WP_Error( 'upload_failed', __( 'File upload failed.', 'tier-pricing-table' ), array( 'status' => 500 ) );
		}

		$files = array(
			// Apache 2.4, Apache 2.2 and LiteSpeed (which reads rewrite rules only)
			'.htaccess'  => "# Quote request files: served only to store managers, through WordPress.\n"
			                . "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
			                . "<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n"
			                . "<IfModule mod_rewrite.c>\n\tRewriteEngine On\n\tRewriteRule .* - [F,L]\n</IfModule>\n",
			'index.php'  => "<?php\n// Silence is golden.\n",
			'index.html' => '',
		);

		foreach ( $files as $name => $content ) {
			$path = $folder . '/' . $name;

			if ( ! file_exists( $path ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $path, $content );
			}
		}

		return $folder;
	}

	/**
	 * The link a store manager downloads a request's file with.
	 *
	 * @param  int  $quoteId
	 * @param  string  $fieldName
	 *
	 * @return string
	 */
	public static function getDownloadUrl( int $quoteId, string $fieldName ): string {
		return add_query_arg( array(
			'action' => self::DOWNLOAD_ACTION,
			'quote'  => $quoteId,
			'field'  => rawurlencode( $fieldName ),
		), admin_url( 'admin-post.php' ) );
	}

	/**
	 * Where a stored File field's file is, but only when it is inside the uploads folder.
	 *
	 * @param  mixed  $field  A stored field.
	 *
	 * @return string The real path, or '' when there is no such file.
	 */
	public static function getStoredPath( $field ): string {
		if ( ! is_array( $field ) || empty( $field['file_path'] ) || ! is_string( $field['file_path'] ) ) {
			return '';
		}

		$uploadsDir = realpath( wp_get_upload_dir()['basedir'] );
		$path       = realpath( $field['file_path'] );

		if ( ! $uploadsDir || ! $path || ! is_file( $path ) ) {
			return '';
		}

		return 0 === strpos( wp_normalize_path( $path ), trailingslashit( wp_normalize_path( $uploadsDir ) ) ) ? $path : '';
	}

	/**
	 * The name of the customer's file. Requests keep it as the field's value; older ones kept the file's public URL.
	 *
	 * @param  array  $field  A stored field.
	 *
	 * @return string
	 */
	public static function getFileName( array $field ): string {
		$value = isset( $field['value'] ) && is_scalar( $field['value'] ) ? (string) $field['value'] : '';

		if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
			$value = wp_basename( (string) wp_parse_url( $value, PHP_URL_PATH ) );
		}

		if ( '' === $value && ! empty( $field['file_path'] ) && is_string( $field['file_path'] ) ) {
			$value = wp_basename( $field['file_path'] );
		}

		return sanitize_file_name( $value );
	}

	/**
	 * Send a request's file to a store manager. Always as a download, never shown in the browser.
	 */
	public static function download() {
		$quoteId   = isset( $_GET['quote'] ) ? absint( $_GET['quote'] ) : 0;
		$fieldName = isset( $_GET['field'] ) ? sanitize_text_field( wp_unslash( $_GET['field'] ) ) : '';

		if ( ! $quoteId || QuoteRequestCPT::POST_TYPE !== get_post_type( $quoteId ) || ! current_user_can( 'edit_post', $quoteId ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to download this file.', 'tier-pricing-table' ), '', array( 'response' => 403 ) );
		}

		$fields = ( new QuoteRequest( $quoteId ) )->getCustomFields();
		$field  = $fields[ $fieldName ] ?? null;
		$path   = is_array( $field ) && 'file' === ( $field['type'] ?? '' ) ? self::getStoredPath( $field ) : '';

		if ( ! $path ) {
			wp_die( esc_html__( 'This file no longer exists.', 'tier-pricing-table' ), '', array( 'response' => 404 ) );
		}

		$name = self::getFileName( $field );
		$name = '' !== $name ? $name : wp_basename( $path );
		$type = wp_check_filetype( $name )['type'];

		while ( ob_get_level() ) {
			ob_end_clean();
		}

		nocache_headers();
		header( 'Content-Type: ' . ( $type ? $type : 'application/octet-stream' ) );
		header( 'Content-Disposition: attachment; filename="' . str_replace( array( '"', "\r", "\n" ), '', $name ) . '"; filename*=UTF-8\'\'' . rawurlencode( $name ) );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $path );
		exit;
	}
}
