<?php
if( !defined( 'ABSPATH' ) ) exit;
class arflitefilecontroller {

	var $file;
	var $check_cap;
	var $capabilities;
	var $check_nonce;
	var $nonce_data;
	var $nonce_action;
	var $check_only_image;
	var $check_specific_ext;
	var $allowed_ext;
	var $invalid_ext;
	var $compression_ext;
	var $error_message;
	var $default_error_msg;
	var $check_file_size;
	var $field_error_msg;
	var $field_size_error_msg;
	var $generate_thumb;
	var $thumb_path;
	var $import;
	var $file_size;
	var $image_exts;

	function __construct( $file, $import ) {

		if ( empty( $file ) && ! $import ) {
			$this->error_message = __( 'Please select a file to process', 'arforms-form-builder' );
			return false;
		}

		$this->file = $file;

		if ( ! $import ) {

			$this->file_size = $file['size'];

		}

		$this->import = $import;

		$this->invalid_ext = apply_filters( 'arflite_restricted_file_ext', array( 'html','js','css','php', 'php2', 'php3', 'php4', 'php5', 'php7', 'php8', 'phar', 'phtml', 'py', 'pl', 'jsp', 'asp', 'aspx', 'cgi', 'ext', 'htm', 'htaccess', 'shtml', 'xhtml', 'tar', 'zip', 'gz', 'gzip', 'rar', '7z' ) );

		$this->compression_ext = apply_filters( 'arflite_exclude_file_check_ext', array( 'tar', 'zip', 'gz', 'gzip', 'rar', '7z' ) );

		$mimes = get_allowed_mime_types();

		$type_img = array();

		foreach ( $mimes as $ext => $type ) {
			if ( preg_match( '/(image\/)/', $type ) ) {
				if ( preg_match( '/(\|)/', $ext ) ) {
					$type_imgs = explode( '|', $ext );
					$type_img  = array_merge( $type_img, $type_imgs );
				} else {
					$type_img[] = $ext;
				}
			}
		}

		$this->image_exts = $type_img;

	}

	function arflite_process_upload( $destination ) {

		if ( $this->check_cap ) {
			$capabilities = $this->capabilities;

			if ( ! empty( $capabilities ) ) {
				if ( is_array( $capabilities ) ) {
					$isFailed = false;
					foreach ( $capabilities as $caps ) {
						if ( ! current_user_can( $caps ) ) {
							$isFailed            = true;
							$this->error_message = __( "Sorry, you don't have permission to perform this action.", 'arforms-form-builder' );
							break;
						}
					}

					if ( $isFailed ) {
						return false;
					}
				} else {
					if ( ! current_user_can( $capabilities ) ) {
						$this->error_message = __( "Sorry, you don't have permission to perform this action.", 'arforms-form-builder' );
					}
				}
			} else {
				$this->error_message = __( "Sorry, you don't have permission to perform this action.", 'arforms-form-builder' );
				return false;
			}
		}

		if ( $this->check_nonce ) {
			if ( empty( $this->nonce_data ) || empty( $this->nonce_action ) ) {
				$this->error_message = __( 'Sorry, Your request could not be processed due to security reasons.', 'arforms-form-builder' );
				return false;
			}

			if ( ! wp_verify_nonce( $this->nonce_data, $this->nonce_action ) ) {
				$this->error_message = __( 'Sorry, Your request could not be processed due to security reasons.', 'arforms-form-builder' );
				return false;
			}
		}

		if ( $this->import ) {
			$ext_data = explode( '.', $this->file );
		} else {
			$ext_data = explode( '.', $this->file['name'] );
		}

		$ext = end( $ext_data );
		$ext = strtolower( $ext );

		if ( in_array( $ext, $this->invalid_ext ) ) {
			$this->error_message = __( 'The file could not be uploaded due to security reasons.', 'arforms-form-builder' );
			return false;
		}

		if ( $this->check_only_image ) {

			if ( ! $this->import && ! preg_match( '/(image\/)/', $this->file['type'] ) ) {
				$this->error_message = __( 'Please select image file only.', 'arforms-form-builder' );
				if ( ! empty( $this->default_error_msg ) ) {
					$this->error_message = esc_html( $this->default_error_msg );
				}
				return false;
			}

			if ( $this->import ) {
				if ( ! in_array( $ext, $this->image_exts ) ) {
					$this->error_message = __( 'Please select image file only.', 'arforms-form-builder' );
					if ( ! empty( $this->default_error_msg ) ) {
						$this->error_message = esc_html( $this->default_error_msg );
					}
					return false;
				}
			}
		}

		if ( $this->check_specific_ext ) {
			if ( empty( $this->allowed_ext ) ) {
				$this->error_message = esc_html__( 'Please set extensions to validate file.', 'arforms-form-builder' );
				return false;
			}
			if ( ! in_array( $ext, $this->allowed_ext ) ) {
				$this->error_message = __( 'Invalid file extension. Please select valid file', 'arforms-form-builder' );
				if ( ! empty( $this->default_error_msg ) ) {
					$this->error_message = esc_html( $this->default_error_msg );
				}

				if ( ! empty( $this->field_error_msg ) ) {
					$this->error_message = esc_html( $this->field_error_msg );
				}

				return false;
			}
		}

		if ( $this->check_file_size ) {

			$size_in_bytes = $this->arflite_convert_to_bytes();

			if ( $size_in_bytes < $this->file_size ) {
				$this->error_message = __( 'Invalid File Size.', 'arforms-form-builder' );

				if ( ! empty( $this->field_size_error_msg ) ) {
					$this->error_message = esc_html( $this->field_size_error_msg );
				}
				return false;
			}
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();
		global $wp_filesystem;

		if ( $this->import ) {
			if( filter_var( $this->file, FILTER_VALIDATE_URL ) ){

				// Block SSRF on non-local environments: private/loopback addresses
				// are only permitted when WordPress is running locally or in development.
				$arf_env = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
				if ( ! in_array( $arf_env, array( 'local', 'development' ), true ) ) {
					$arf_parsed_url = wp_parse_url( $this->file );
					$arf_host       = strtolower( trim( $arf_parsed_url['host'] ?? '' ) );

					$scheme = strtolower( $arf_parsed_url['scheme'] ?? '' );
					if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
						return false;
					}

					// Private / loopback ranges checked against binary IPs (IPv4 + IPv6).
					$arf_private_ranges = array(
						// IPv4 loopback: 127.0.0.0/8
						array( "\x7f\x00\x00\x00", "\x7f\xff\xff\xff" ),
						// IPv4 private: 10.0.0.0/8
						array( "\x0a\x00\x00\x00", "\x0a\xff\xff\xff" ),
						// IPv4 private: 172.16.0.0/12
						array( "\xac\x10\x00\x00", "\xac\x1f\xff\xff" ),
						// IPv4 private: 192.168.0.0/16
						array( "\xc0\xa8\x00\x00", "\xc0\xa8\xff\xff" ),
						// IPv4 link-local: 169.254.0.0/16 (AWS metadata et al.)
						array( "\xa9\xfe\x00\x00", "\xa9\xfe\xff\xff" ),
						// IPv6 loopback: ::1
						array( "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01",
						       "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01" ),
						// IPv6 link-local: fe80::/10
						array( "\xfe\x80\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
						       "\xfe\xbf\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff" ),
					);

					// Helper: check a single binary IP against all private ranges.
					$arf_is_private_ip = function( $arf_binary_ip ) use ( $arf_private_ranges ) {
						foreach ( $arf_private_ranges as $arf_range ) {
							if ( strcmp( $arf_binary_ip, $arf_range[0] ) >= 0 &&
							     strcmp( $arf_binary_ip, $arf_range[1] ) <= 0 ) {
								return true;
							}
						}
						return false;
					};

					$arf_ssrf_blocked = false;

					// Step 1 — reject bare loopback hostnames before any IP parsing.
					if ( in_array( $arf_host, array( 'localhost', '::1' ), true ) ) {
						$arf_ssrf_blocked = true;
					}

					// Step 2 — if host is a raw IP literal, check it directly.
					if ( ! $arf_ssrf_blocked ) {
						$arf_ip = @inet_pton( $arf_host );
						if ( $arf_ip !== false && $arf_is_private_ip( $arf_ip ) ) {
							$arf_ssrf_blocked = true;
						}
					}

					// Step 3 — host is a domain name: resolve it
					// and check every returned IP, catching internal hostnames that map to
					// private addresses regardless of how the domain is named.
					if ( ! $arf_ssrf_blocked ) {
						$arf_resolved = @dns_get_record( $arf_host, DNS_A | DNS_AAAA );
						if ( ! empty( $arf_resolved ) ) {
							foreach ( $arf_resolved as $arf_record ) {
								$arf_resolved_ip_str = isset( $arf_record['ip'] ) ? $arf_record['ip'] : ( $arf_record['ipv6'] ?? '' );
								$arf_resolved_ip_bin = @inet_pton( $arf_resolved_ip_str );
								if ( $arf_resolved_ip_bin !== false && $arf_is_private_ip( $arf_resolved_ip_bin ) ) {
									$arf_ssrf_blocked = true;
									break;
								}
							}
						} elseif ( @inet_pton( $arf_host ) === false ) {
							// dns_get_record returned nothing and it is not an IP literal —
							// unresolvable hostname; block it to fail safely.
							$arf_ssrf_blocked = true;
						}
					}

					if ( $arf_ssrf_blocked ) {
						$this->error_message = esc_html__( 'The file could not be fetched due to security reasons.', 'arforms-form-builder' );
						return false;
					}
				}

				$args = array(
					'timeout'   => 30,
					'sslverify' => true,
					'redirection' => 0,
					'limit_response_size' => 5 * 1024 * 1024, // Prevent memory DoS
				);
				$getFileContent = wp_safe_remote_get( $this->file, $args );
				if( !is_wp_error( $getFileContent ) ){
					$file_content = wp_remote_retrieve_body( $getFileContent );
				}
			} else {
				$file_content = $wp_filesystem->get_contents( $this->file );
			}
		} else {
			$file_content = $wp_filesystem->get_contents( $this->file['tmp_name'] );
		}

		$is_valid_file = $this->arflite_read_file( $file_content, $ext );

		if ( ! $is_valid_file ) {
			return false;
		}

		if ( '' == $file_content || ! $wp_filesystem->put_contents( $destination, $file_content, 0777 ) ) {
			$this->error_message = __( 'There is an issue while uploading a file. Please try again', 'arforms-form-builder' );
			return false;
		}

		if ( $this->generate_thumb && preg_match( '/(image\/)/', $this->file['type'] ) ) {

			require_once ARFLITE_FORMPATH . '/js/filedrag/simple_image.php';

			$image = new SimpleImage();
			$image->load( $destination );
			$image->resizeToHeight( 100 );
			$image->save( $this->thumb_path );

		}

		return true;
	}

	function arflite_read_file( $file_content, $ext ) {

		if ( '' == $file_content ) {
			return true;
		}

		if ( in_array( $ext, $this->compression_ext ) ) {
			return true;
		}

		$file_bytes = $this->file_size;

		$file_size = number_format( $file_bytes / 1048576, 2 );


		$arflite_valid_pattern = '/(<\?(php|\=)|<script[^>]+language\s*=\s*["\']?\s*php\s*["\']?)/i';

		if ( preg_match( $arflite_valid_pattern, $file_content ) ) {
			$this->error_message = __( 'The file could not be uploaded due to security reason as it contains malicious code', 'arforms-form-builder' );
			return false;
		}

		return true;

	}

	function arflite_convert_to_bytes() {

		$units_arr = array(
			"B"  => 0,
			"K"  => 1,
			"KB" => 1,
			"M"  => 2,
			"MB" => 2,
			"G"  => 3,
			"GB" => 3,
			"T"  => 4,
			"TB" => 4,
			"P"  => 5,
			"PB" => 5
		);

		$numbers = preg_replace( '/[^\d.]/', '', $this->max_file_size );

		$suffix = preg_replace( '/[\d.]+/', '', $this->max_file_size );

		if ( is_numeric( substr( $suffix, 0, 1 ) ) ) {
			return preg_replace( '/[^\d.]/', '', $this->max_file_size );
		}

		$exponent = ! empty( $units_arr[ $suffix ] ) ? $units_arr[ $suffix ] : null;

		if ( null == $exponent ) {
			return null;
		}

		return $numbers * ( 1024 ** $exponent );

	}

}
