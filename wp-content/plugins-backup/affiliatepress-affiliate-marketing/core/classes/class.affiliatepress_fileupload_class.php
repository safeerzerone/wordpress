<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_fileupload_class') ) {
    class affiliatepress_fileupload_class Extends AffiliatePress_Core
    {
        var $affiliatepress_file;
        var $affiliatepress_check_cap;
        var $affiliatepress_capabilities;
        var $affiliatepress_check_nonce;
        var $affiliatepress_nonce_data;
        var $affiliatepress_nonce_action;
        var $affiliatepress_check_only_image;
        var $affiliatepress_check_specific_ext;
        var $affiliatepress_allowed_ext;
        var $affiliatepress_invalid_ext;
        var $affiliatepress_compression_ext;
        var $affiliatepress_error_message;
        var $affiliatepress_default_error_msg;
        var $affiliatepress_check_file_size;
        var $affiliatepress_file_size;
        var $affiliatepress_max_file_size;
        var $affiliatepress_field_error_msg;
        var $affiliatepress_field_size_error_msg;
        var $affiliatepress_copy_file;
        var $affiliatepress_manage_junks;
        var $affiliatepress_image_exts;

        
        /**
         * Load all variables from file object
         *
         * @param  mixed $affiliatepress_file
         * @return void
         */
        function __construct( $affiliatepress_file, $affiliatepress_import = false )
        {
            global $AffiliatePress;
            if (empty($affiliatepress_file) && ! $affiliatepress_import ) {
                $this->affiliatepress_error_message = esc_html__('Please select a file to process', 'affiliatepress-affiliate-marketing');
                return false;
            }

            if( !empty( $affiliatepress_file ) && !$affiliatepress_import ){
                $affiliatepress_file = ! empty($affiliatepress_file) ? array_map(array( $AffiliatePress, 'affiliatepress_array_sanatize_field' ), $affiliatepress_file) : array();
            }

            $this->affiliatepress_file      = $affiliatepress_file;
            if( !$affiliatepress_import ){
                $this->affiliatepress_file_size = $affiliatepress_file['size'];
            }

            $this->affiliatepress_copy_file = $affiliatepress_import;
            $this->affiliatepress_invalid_ext = apply_filters('affiliatepress_restricted_file_ext', array( 'php', 'php3', 'php4', 'php5', 'py', 'pl', 'jsp', 'asp', 'cgi', 'ext' ));
            $this->affiliatepress_compression_ext = apply_filters('affiliatepress_exclude_file_check_ext', array( 'tar', 'zip', 'gz', 'gzip', 'rar', '7z' ));
            $affiliatepress_mimes = get_allowed_mime_types();
            $affiliatepress_type_img = array();

            foreach ( $affiliatepress_mimes as $affiliatepress_ext => $affiliatepress_type ) {
                if (preg_match('/(image\/)/', $affiliatepress_type) ) {
                    if (preg_match('/(\|)/', $affiliatepress_ext) ) {
                        $affiliatepress_type_imgs = explode('|', $affiliatepress_ext);
                        $affiliatepress_type_img  = array_merge($affiliatepress_type_img, $affiliatepress_type_imgs);
                    } else {
                        $affiliatepress_type_img[] = $affiliatepress_ext;
                    }
                }
            }

            $this->affiliatepress_image_exts = $affiliatepress_type_img;

            $this->affiliatepress_capabilities = array( 'affiliatepress' );
        }

        
        /**
         * Core function for upload file
         *
         * @param  mixed $affiliatepress_destination
         * @return void
         */
        function affiliatepress_process_upload( $affiliatepress_destination ){
            $affiliatepress_allow_file_upload = 1;
            $affiliatepress_allow_file_upload = apply_filters('affiliatepress_allow_file_uploads', $affiliatepress_allow_file_upload);	
            if(empty($affiliatepress_allow_file_upload)){
                return false;
            }
            if ($this->affiliatepress_check_cap ) {
                $affiliatepress_capabilities = $this->affiliatepress_capabilities;
                if (! empty($affiliatepress_capabilities) ) {
                    if (is_array($affiliatepress_capabilities) ) {
                        $affiliatepress_isFailed = false;
                        foreach ( $affiliatepress_capabilities as $affiliatepress_caps ) {
                            if (! current_user_can($affiliatepress_caps) ) {
                                $affiliatepress_isFailed            = true;
                                $this->affiliatepress_error_message = esc_html__("Sorry, you don't have permission to perform this action.", 'affiliatepress-affiliate-marketing');
                                break;
                            }
                        }
                        if ($affiliatepress_isFailed ) {
                            return false;
                        }
                    } else {
                        if (! current_user_can($affiliatepress_capabilities) ) {
                            $this->affiliatepress_error_message = esc_html__("Sorry, you don't have permission to perform this action.", 'affiliatepress-affiliate-marketing');
                        }
                    }
                } else {
                    $this->affiliatepress_error_message = esc_html__("Sorry, you don't have permission to perform this action.", 'affiliatepress-affiliate-marketing');
                    return false;
                }
            }

            if ($this->affiliatepress_check_nonce ) {
                if (empty($this->affiliatepress_nonce_data) || empty($this->affiliatepress_nonce_action) ) {
                    $this->affiliatepress_error_message = esc_html__('Sorry, Your request could not be processed due to security reasons.', 'affiliatepress-affiliate-marketing');
                    return false;
                }

                if (! wp_verify_nonce($this->affiliatepress_nonce_data, $this->affiliatepress_nonce_action) ) {
                    $this->affiliatepress_error_message = esc_html__('Sorry, Your request could not be processed due to security reasons.', 'affiliatepress-affiliate-marketing');
                    return false;
                }
            }

            if( $this->affiliatepress_copy_file ){
                $affiliatepress_ext_data = explode( '.', sanitize_file_name( $this->affiliatepress_file ) );
            } else {
                $affiliatepress_ext_data = explode('.', sanitize_file_name( $this->affiliatepress_file['name'] ) );
            }
            $affiliatepress_ext      = end($affiliatepress_ext_data);
            $affiliatepress_ext      = strtolower($affiliatepress_ext);

            if (in_array($affiliatepress_ext, $this->affiliatepress_invalid_ext) ) {
                $this->affiliatepress_error_message = esc_html__('The file could not be uploaded due to security reasons.', 'affiliatepress-affiliate-marketing');
                return false;
            }

            if ($this->affiliatepress_check_only_image ) {
                if ( !$this->affiliatepress_copy_file && !preg_match('/(image\/)/', $this->affiliatepress_file['type']) ) {
                    $this->affiliatepress_error_message = esc_html__('Please select image file only.', 'affiliatepress-affiliate-marketing');
                    if (! empty($this->affiliatepress_default_error_msg) ) {
                        $this->affiliatepress_error_message = $this->affiliatepress_default_error_msg;
                    }
                    return false;
                }

                if( $this->affiliatepress_copy_file ){
                    if( ! in_array( $affiliatepress_ext, $this->affiliatepress_image_exts ) ){
                        $this->affiliatepress_error_message = esc_html__( "Please select image file only.", "affiliatepress-affiliate-marketing");
                        if( !empty( $this->affiliatepress_default_error_msg ) ){
                            $this->affiliatepress_error_message = $this->affiliatepress_default_error_msg;
                        }
                        return false;
                    }
                }
            }

            if ($this->affiliatepress_check_specific_ext ) {
                if (empty($this->affiliatepress_allowed_ext) ) {
                    $this->affiliatepress_error_message = esc_html__('Please set extensions to validate file.', 'affiliatepress-affiliate-marketing');
                    return false;
                }
                if (! in_array($affiliatepress_ext, $this->affiliatepress_allowed_ext) ) {
                    $this->affiliatepress_error_message = esc_html__('Invalid file extension. Please select valid file', 'affiliatepress-affiliate-marketing');
                    if (! empty($this->affiliatepress_default_error_msg) ) {
                        $this->affiliatepress_error_message = $this->affiliatepress_default_error_msg;
                    }

                    if (! empty($this->affiliatepress_field_error_msg) ) {
                        $this->affiliatepress_error_message = $this->affiliatepress_field_error_msg;
                    }

                    return false;
                }
            }
            
            if ($this->affiliatepress_check_file_size ) {
                $affiliatepress_size_in_bytes = $this->affiliatepress_convert_to_bytes();
                if ($affiliatepress_size_in_bytes < $this->affiliatepress_file_size || $this->affiliatepress_file_size == 0 ) {
                    $this->affiliatepress_error_message = esc_html__('Invalid File Size.', 'affiliatepress-affiliate-marketing');

                    if (! empty($this->affiliatepress_field_size_error_msg) ) {
                        $this->affiliatepress_error_message = $this->affiliatepress_field_size_error_msg;
                    }
                    return false;
                }
            }   

            if (! function_exists('WP_Filesystem') ) {
                include_once ABSPATH . 'wp-admin/includes/file.php';
            }

            WP_Filesystem();
            global $wp_filesystem;
            
            if( $this->affiliatepress_copy_file ){
                if( filter_var( $this->affiliatepress_file, FILTER_VALIDATE_URL ) ){
                    
                    $affiliatepress_args = array(
                        'timeout' => 4500
                    );
                    $affiliatepress_getFileContent= wp_remote_get( $this->affiliatepress_file, $affiliatepress_args );

                    if( !is_wp_error( $affiliatepress_getFileContent ) ){
						if( !empty( $affiliatepress_getFileContent['response']['code'] ) && '200' == $affiliatepress_getFileContent['response']['code'] ){
							$affiliatepress_file_content = wp_remote_retrieve_body( $affiliatepress_getFileContent );	
						} else {
							$affiliatepress_file_content  = $wp_filesystem->get_contents( $this->affiliatepress_file );
							if( false == $affiliatepress_file_content ){
                                $affiliatepress_file_content = $this->affiliatepress_retrieve_file_content_from_temporary_directory();
							}
						}
						
                    } else {
                        $this->affiliatepress_file = str_replace('https', 'http', $this->affiliatepress_file);
                        $affiliatepress_getFileContent= wp_remote_get( $this->affiliatepress_file, $affiliatepress_args );
                        if( !is_wp_error( $affiliatepress_getFileContent ) ){
                            if( !empty( $affiliatepress_getFileContent['response']['code'] ) && '200' == $affiliatepress_getFileContent['response']['code'] ){
                                $affiliatepress_file_content = wp_remote_retrieve_body( $affiliatepress_getFileContent );	
                            } else {
                                $affiliatepress_file_content  = $wp_filesystem->get_contents( $this->affiliatepress_file );
                                if( false == $affiliatepress_file_content ){
                                    $affiliatepress_file_content = $this->affiliatepress_retrieve_file_content_from_temporary_directory();
                                }
                            }
                        }else{
                            $affiliatepress_file_content  = $wp_filesystem->get_contents( $this->affiliatepress_file );
							if( false == $affiliatepress_file_content ){
                                $affiliatepress_file_content = $this->affiliatepress_retrieve_file_content_from_temporary_directory();
							}
                        }
                    }
                } else {
                    $affiliatepress_file_content  = $wp_filesystem->get_contents( $this->affiliatepress_file );
                }
            } else {
                $affiliatepress_file_content  = $wp_filesystem->get_contents($this->affiliatepress_file['tmp_name']);
            }
            $affiliatepress_is_valid_file = $this->affiliatepress_read_file($affiliatepress_file_content, $affiliatepress_ext);

            if (! $affiliatepress_is_valid_file ) {
                return false;
            }              

            if ('' == $affiliatepress_file_content || ! $wp_filesystem->put_contents($affiliatepress_destination, $affiliatepress_file_content, 0777) ) {
                $this->affiliatepress_error_message = esc_html__('There is an issue while uploading a file. Please try again', 'affiliatepress-affiliate-marketing');
                return false;
            }

            $affiliatepress_junk_files = array();
            if( $this->affiliatepress_manage_junks ){
                $affiliatepress_junk_files[] = current_time( 'timestamp' ) . '<|>' . $affiliatepress_destination;
            }

            if( $this->affiliatepress_manage_junks && !empty( $affiliatepress_junk_files ) ){
                $affiliatepress_remove_junk_files = wp_json_encode( $affiliatepress_junk_files );
                $affiliatepress_opt_val = get_option('affiliatepress_remove_junk_files');
                $affiliatepress_opt_val = json_decode( $affiliatepress_opt_val, true );
                if ( empty($affiliatepress_opt_val) ) {
                    update_option('affiliatepress_remove_junk_files', $affiliatepress_remove_junk_files);
                } else {
                    $affiliatepress_opt_val = array_merge($affiliatepress_junk_files, $affiliatepress_opt_val);
                    $affiliatepress_update_opt_val = wp_json_encode( $affiliatepress_opt_val );
                    update_option('affiliatepress_remove_junk_files', $affiliatepress_update_opt_val);
                }
            }

            return 1;
        }

        function affiliatepress_retrieve_file_content_from_temporary_directory(){
            global $wp_filesystem;
            $affiliatepress_file_name = AFFILIATEPRESS_TMP_IMAGES_DIR .'/'. basename( $this->affiliatepress_file );
            $affiliatepress_file_content = false;
            if( file_exists( $affiliatepress_file_name ) ){
                $affiliatepress_file_content = $wp_filesystem->get_contents( $affiliatepress_file_name );
            }
            return $affiliatepress_file_content;
        }
        
        /**
         * Core function for read specific file content
         *
         * @param  mixed $affiliatepress_file_content
         * @param  mixed $affiliatepress_ext
         * @return void
        */
        function affiliatepress_read_file( $affiliatepress_file_content, $affiliatepress_ext )
        {
            if ('' == $affiliatepress_file_content ) {
                return true;
            }

            if (in_array($affiliatepress_ext, $this->affiliatepress_compression_ext) ) {
                return true;
            }

            $affiliatepress_file_bytes = $this->affiliatepress_file_size;

            $affiliatepress_file_size = number_format($affiliatepress_file_bytes / 1048576, 2);

            if ($affiliatepress_file_size > 10 ) {
                return true;
            }

            $affiliatepress_valid_pattern = '/(\<\?(php)|\<\?\=)/';

            if ( preg_match( $affiliatepress_valid_pattern, $affiliatepress_file_content ) ) {
                $this->affiliatepress_error_message = esc_html__('The file could not be uploaded due to security reason as it contains malicious code', 'affiliatepress-affiliate-marketing');
                return false;
            }

            $affiliatepress_is_short_tag_enabled = ini_get( 'short_open_tag' );

            if( (1 == $affiliatepress_is_short_tag_enabled || true == $affiliatepress_is_short_tag_enabled) && ( preg_match( '/\<\?(.*?)\?\>/', $affiliatepress_file_content ) || preg_match( '/\<\?(.*?)$/', $affiliatepress_file_content ) ) ){
                $this->affiliatepress_error_message = esc_html__('The file could not be uploaded due to security reason as it contains malicious code', 'affiliatepress-affiliate-marketing');
                return false;
            }

            return true;
        }

        function affiliatepress_convert_to_bytes()
        {
            $affiliatepress_units_arr = array(
            'B'  => 0,
            'K'  => 1,
            'KB' => 1,
            'M'  => 2,
            'MB' => 2,
            'G'  => 3,
            'GB' => 3,
            'T'  => 4,
            'TB' => 4,
            'P'  => 5,
            'PB' => 5,
            );

            $affiliatepress_numbers = preg_replace('/[^\d.]/', '', $this->affiliatepress_max_file_size);
            $affiliatepress_suffix  = preg_replace('/[\d.]+/', '', $this->affiliatepress_max_file_size);
            if (is_numeric(substr($affiliatepress_suffix, 0, 1)) ) {
                return preg_replace('/[^\d.]/', '', $this->affiliatepress_max_file_size);
            }
            $affiliatepress_exponent = ! empty($affiliatepress_units_arr[ $affiliatepress_suffix ]) ? $affiliatepress_units_arr[ $affiliatepress_suffix ] : null;
            if (null == $affiliatepress_exponent ) {
                return null;
            }
            return $affiliatepress_numbers * ( 1024 ** $affiliatepress_exponent );
        }
    }
}
