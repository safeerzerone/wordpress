<?php
if (!class_exists('ARM_members_activity'))
{
	class ARM_members_activity
	{
		function __construct()
		{
			global $wpdb, $ARMember, $arm_slugs;
			
			add_action('arm_record_activity', array($this, 'arm_add_activity'), 1);
			/* Ajax Load More Activities */
			add_action('wp_ajax_nopriv_arm_crop_iamge', array($this, 'arm_crop_image'));
            add_action('wp_ajax_arm_crop_iamge', array($this, 'arm_crop_image'));


            add_action('wp_ajax_arm_upload_front', array($this, 'arm_upload_front'), 1);
	        add_action('wp_ajax_nopriv_arm_upload_front', array($this, 'arm_upload_front'), 1);

	        add_action('wp_ajax_arm_upload_cover', array($this, 'arm_upload_cover'), 1);
	        add_action('wp_ajax_nopriv_arm_upload_cover', array($this, 'arm_upload_cover'), 1);

	        add_action('wp_ajax_arm_upload_profile', array($this, 'arm_upload_profile'), 1);
	        add_action('wp_ajax_nopriv_arm_upload_profile', array($this, 'arm_upload_profile'), 1);

	        add_action('wp_ajax_arm_upload_badge', array($this, 'arm_upload_badge'), 1);

	        add_action('wp_ajax_arm_upload_social_icon', array($this, 'arm_upload_social_icon'), 1);

	        add_action('wp_ajax_arm_import_user', array($this, 'arm_import_user'), 1);
			
			add_action('wp_ajax_armactivatelicense', array($this, 'armreqact'));

			add_action('wp_ajax_armrenewlicense', array($this, 'arm_renew_license'));

			add_action('wp_ajax_armrenewuserbadge', array($this, 'arm_renew_user_badge'));
			
			add_action('wp_ajax_armdeactlic', array($this, 'armreqlicdeact'));

			add_filter( 'arm_verify_license_package', array($this, 'arm_verify_packg'), 10, 2 );
			global $check_sorting;
       		$check_sorting = "checksorting";
			
			global $check_version;
       		$check_version = "checkversion";
                
            add_action('admin_init', array($this, 'upgrade_data'));
		}
                
        function upgrade_data() {
			global $arm_newdbversion, $arm_crons;
	
			if (!isset($arm_newdbversion) || $arm_newdbversion == "")
				$arm_newdbversion = get_option('arm_version');

			if( version_compare($arm_newdbversion,'2.0','<') ){
				update_option('arm_update_to_new_version',true);
			    update_option('arm_new_version','2.0');
			    $url = admin_url('admin.php?page=arm_update_page');
			    if( $_REQUEST['page'] != 'arm_update_page' ){//phpcs:ignore
			        wp_redirect($url);
			        die();
			    }
			}
	
		if (version_compare($arm_newdbversion, '7.1', '<') && version_compare($arm_newdbversion,'1.8.1','>')) {
				$path = MEMBERSHIP_VIEWS_DIR . '/upgrade_latest_data.php';
				include($path); //phpcs:ignore
				$arm_crons->armember_send_anonymous_data_cron_func();
			}
		}
		
		function arm_add_activity($activity = array())
		{
			global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_social_feature;
			return false;
		}
		
		function armreqlicdeact() {
			global $armember_check_plugin_copy, $ARMember;
			if( empty( $armember_check_plugin_copy ) )
			{
				$plugres = $this->armdeactivatelicense();
		
				if (isset($plugres) && $plugres != "") {
					echo $plugres; //phpcs:ignore
					exit;
				} else {
					echo "Invalid Request";
					exit;
				}
				exit;
			}
			else {
				$_POST['arm_license_key'] = get_option('arm_pkg_key');
				$_POST['arm_package_dactvte'] = 1;
				$return_response = 1;
				$user_badge_update = $ARMember->arm_armember_package_update($return_response);
				if( !empty( $user_badge_update['msg'] ) )
				{
					echo $user_badge_update['msg'];
					exit;
				}
				else
				{
					echo "Invalid Request";
					exit;
				}
			}
		}
		
		function checkversion($case = '') {
			
			global $armember_check_plugin_copy;
			if( empty($armember_check_plugin_copy) )
			{
				$sortorder = get_option("armSortOrder");
				$sortid = get_option("armSortId");
				$issorted = get_option("armIsSorted");
				$isinfo = get_option("armSortInfo");
		
				if ($sortorder == "" || $sortid == "" || $issorted == "") {
					return 0;
				} else {
					$sortfield = $sortorder;
					$sortorderval = base64_decode($sortfield);
		
					$ordering = array();
					$ordering = explode("^", $sortorderval);
		
					$domain_name = str_replace('www.', '', $ordering[3]);
					$recordid = $ordering[4];
					$ipaddress = $ordering[5];
		
					$mysitename = get_bloginfo('name');
					$siteipaddr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
					$servername = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : '';
					$serverhost = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : '';
					$mysitedomain = str_replace('www.', '', $servername);
					$mysitedomain1 = str_replace('www.', '', $serverhost);
					$mysitedomain2 = str_replace('www.', '', $siteipaddr);
				
					if (($domain_name == $mysitedomain || $domain_name == $mysitedomain1 || $domain_name == $mysitedomain2) && ($recordid == $sortid)) {
						return 1;
					} else {
						return 0;
					}
				}
			}
			else {
				$arm_check_update_badge_status = $this->arm_check_update_badge_status();
				if( !empty($arm_check_update_badge_status) )
				{
					$domain_name = get_option('arm_pkg_dmn');

					$mysitename = get_bloginfo('name');
					$siteipaddr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
					$servername = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : '';
					$serverhost = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : '';
					
					$mysitedomain1 = str_replace('www.', '', $serverhost);
					$mysitedomain2 = str_replace('www.', '', $siteipaddr);
					
					if ( ( $domain_name == $servername || $domain_name == $mysitedomain1 || $domain_name == $mysitedomain2 ) )
					{
						return 1;
					}
					else {
						return 0;
					}
				}
				return $arm_check_update_badge_status;
			}
		}
		
		function arm_renew_user_badge()
		{
			global $wp_version;
			$lidata = "";
			$verifycode = get_option("armSortOrder");
			
			if($verifycode == "")
			{
				echo "Invalid Request Parameters";
				exit;
			}
			
			$urltopost = "https://www.reputeinfosystems.com/tf/plugins/armember/verify/update_arm_badge.php";
			$response = wp_remote_post($urltopost, array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => array('verifycode' => $verifycode),
				'user-agent' => 'ARM-WordPress/' . $wp_version . '; ' . ARM_HOME_URL,
				'cookies' => array()
					)
			);

			if (array_key_exists('body', $response) && isset($response["body"]) && $response["body"] != "")
				$responsemsg = $response["body"];
			else
				$responsemsg = "";

			if ($responsemsg != "") {
				$responsemsg = explode("|^|", $responsemsg);
				if (is_array($responsemsg) && count($responsemsg) > 0) {

					if (isset($responsemsg[0]) && $responsemsg[0] != "") {
						$msg = $responsemsg[0];
					} else {
						$msg = "";
					}
					
					if (isset($responsemsg[1]) && $responsemsg[1] != "") {
						$info = $responsemsg[1];
					} else {
						$info = "";
					}

					if ($msg == "1") {
						update_option("armSortOrder", $info);
						update_option("armIsBadgeUpdated", $info);
						delete_option('arm_badgeupdaterequired');
						echo "VERIFIED";
						exit;
					}
					else 
					{
						echo $msg; //phpcs:ignore
						exit;
					}
				}
			}
			else 
			{
				echo "Invalid Request";
				exit;
			}			
		}
		
		
		function checksorting() {
			
			global $armember_check_plugin_copy;
			if( empty($armember_check_plugin_copy) )
			{
				$sortorder = get_option("armSortOrder");
				$sortid = get_option("armSortId");
				$issorted = get_option("armIsSorted");
				$isinfo = get_option("armSortInfo");
		
				if ($sortorder == "" || $sortid == "" || $issorted == "") {
					return 0;
				} else {
					$sortfield = $sortorder;
					$sortorderval = base64_decode($sortfield);
		
					$ordering = array();
					$ordering = explode("^", $sortorderval);
					
					$domain_name = str_replace('www.', '', $ordering[3]);
					$recordid = $ordering[4];
					$ipaddress = $ordering[5];
		
					$mysitename = get_bloginfo('name');
					$siteipaddr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
					$servername = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : '';
					$serverhost = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : '';
					
					$mysitedomain = str_replace('www.', '', $servername);
					$mysitedomain1 = str_replace('www.', '', $serverhost);
					$mysitedomain2 = str_replace('www.', '', $siteipaddr);
					
					if (($domain_name == $mysitedomain || $domain_name == $mysitedomain1 || $domain_name == $mysitedomain2) && ($recordid == $sortid)) {
						return 1;
					}else {

						$arm_sort_string_data = "Domain name==>".$domain_name."|^|Site Domain ==>".$mysitedomain."|^|Site Domain1 ==>".$mysitedomain1."|^|Site Domain2 ==>".$mysitedomain2."|^|Record ID ==>".$recordid."|^|Sort ID ==>".$sortid;
						update_option('arm_is_sort_data_mismatched_'.time(),$arm_sort_string_data);

						if($mysitedomain != "" && $mysitedomain1 != "" && $mysitedomain2 != "")
						{
									$isoptionstored = "";
									update_option('arm_is_user_private_content_feature', 0);
									update_option('arm_is_social_feature', 0);
									update_option('arm_is_social_login_feature', 0);
									update_option('arm_is_pro_ration_feature', 0);
									update_option('arm_is_drip_content_feature', 0);
									//update_option('arm_is_opt_ins_feature', 0);
									update_option('arm_is_coupon_feature', 0);
									update_option('arm_is_buddypress_feature', 0);
									update_option('arm_is_woocommerce_feature', 0);
									update_option('arm_is_multiple_membership_feature', 0);
									update_option('arm_is_mycred_feature', 0);
									update_option('arm_is_plan_limit_feature',0);
									update_option('arm_is_invoice_tax_feature', 0);
									update_option('arm_is_gutenberg_block_restriction_feature', 0);
									update_option('arm_is_beaver_builder_restriction_feature', 0);
									update_option('arm_is_divi_builder_restriction_feature', 0);
									update_option('arm_is_wpbakery_page_builder_restriction_feature', 0);
									update_option('arm_is_fusion_builder_restriction_feature', 0);
									update_option('arm_is_oxygen_builder_restriction_feature', 0);
									update_option('arm_is_siteorigin_builder_restriction_feature', 0);
									update_option('arm_is_bricks_builder_restriction_feature', 0);
									update_option('arm_is_pay_per_post_feature', 0);
									update_option('arm_is_api_service_feature', 0);
							
									delete_option("armIsSorted");
									delete_option("armSortOrder");
									delete_option("armSortId");
									delete_option("armSortInfo");
									delete_option("armBadgeUpdated");
									delete_option("armIsBadgeUpdated");
									
									delete_option('arm_pkg_key');
									delete_option('arm_pkg');
									delete_option('arm_pkg_status');
									delete_option('arm_pkg_data_actvte_respnc');
									delete_option('arm_pkg_dmn');
							
									delete_site_option("armIsSorted");
									delete_site_option("armSortOrder");
									delete_site_option("armSortId");
									delete_site_option("armSortInfo");
									delete_site_option("armBadgeUpdated");
									delete_site_option("armIsBadgeUpdated");

									delete_site_option('arm_pkg_key');
									delete_site_option('arm_pkg');
									delete_site_option('arm_pkg_status');
									delete_site_option('arm_pkg_data_actvte_respnc');
									delete_site_option('arm_pkg_dmn');
										
									update_option('arm_isoptionstored', 1);
						}

						return 0;
					}
				}
			}
			else
			{
				$arm_check_update_badge_status = $this->arm_check_update_badge_status();
				if( !empty($arm_check_update_badge_status) )
				{
					$domain_name = get_option('arm_pkg_dmn');

					$mysitename = get_bloginfo('name');
					$siteipaddr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
					$servername = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : '';
					$serverhost = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : '';
					
					$mysitedomain1 = str_replace('www.', '', $serverhost);
					$mysitedomain2 = str_replace('www.', '', $siteipaddr);
					
					if ( ( $domain_name == $servername || $domain_name == $mysitedomain1 || $domain_name == $mysitedomain2 ) )
					{
						return 1;
					}
					else {

						$arm_sort_string_data = "Domain name==>".$domain_name."|^|Site Domain ==>".$servername."|^|Site Domain1 ==>".$mysitedomain1."|^|Site Domain2 ==>".$mysitedomain2;
						update_option('arm_is_another_sort_data_mismatched_'.time(),$arm_sort_string_data);

						if($servername != "" && $mysitedomain1 != "" && $mysitedomain2 != "")
						{
							$isoptionstored = "";
							update_option('arm_is_user_private_content_feature', 0);
							update_option('arm_is_social_feature', 0);
							update_option('arm_is_social_login_feature', 0);
							update_option('arm_is_pro_ration_feature', 0);
							update_option('arm_is_drip_content_feature', 0);
							//update_option('arm_is_opt_ins_feature', 0);
							update_option('arm_is_coupon_feature', 0);
							update_option('arm_is_buddypress_feature', 0);
							update_option('arm_is_woocommerce_feature', 0);
							update_option('arm_is_multiple_membership_feature', 0);
							update_option('arm_is_mycred_feature', 0);
							update_option('arm_is_plan_limit_feature',0);
							update_option('arm_is_invoice_tax_feature', 0);
							update_option('arm_is_gutenberg_block_restriction_feature', 0);
							update_option('arm_is_beaver_builder_restriction_feature', 0);
							update_option('arm_is_divi_builder_restriction_feature', 0);
							update_option('arm_is_wpbakery_page_builder_restriction_feature', 0);
							update_option('arm_is_fusion_builder_restriction_feature', 0);
							update_option('arm_is_oxygen_builder_restriction_feature', 0);
							update_option('arm_is_siteorigin_builder_restriction_feature', 0);
							update_option('arm_is_bricks_builder_restriction_feature', 0);
							update_option('arm_is_pay_per_post_feature', 0);
							update_option('arm_is_api_service_feature', 0);
					
							delete_option("armIsSorted");
							delete_option("armSortOrder");
							delete_option("armSortId");
							delete_option("armSortInfo");
							delete_option("armBadgeUpdated");
							delete_option("armIsBadgeUpdated");

							delete_option('arm_pkg_key');
							delete_option('arm_pkg');
							delete_option('arm_pkg_status');
							delete_option('arm_pkg_data_actvte_respnc');
							delete_option('arm_pkg_dmn');
					
							delete_site_option("armIsSorted");
							delete_site_option("armSortOrder");
							delete_site_option("armSortId");
							delete_site_option("armSortInfo");
							delete_site_option("armBadgeUpdated");
							delete_site_option("armIsBadgeUpdated");

							delete_site_option('arm_pkg_key');
							delete_site_option('arm_pkg');
							delete_site_option('arm_pkg_status');
							delete_site_option('arm_pkg_data_actvte_respnc');
							delete_site_option('arm_pkg_dmn');
								
							update_option('arm_isoptionstored', 1);
						}
						
						return 0;
					}
				}
				return $arm_check_update_badge_status;
			}
		}
		
		function arm_renew_license() {
			global $wp_version;
       
        $lidata = "";

        $lidata = $_POST["purchase_info"]; //phpcs:ignore

        $verifycode = get_option("armSortOrder");
        
        $valstring =  $lidata;
        $urltopost = "https://www.reputeinfosystems.com/tf/plugins/armember/verify/lic_renew_arm.php";

        $response = wp_remote_post($urltopost, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array('verifyrenew' => $valstring, 'verifycode' => $verifycode),
			'user-agent' => 'ARM-WordPress/' . $wp_version . '; ' . ARM_HOME_URL,
            'cookies' => array()
                )
        );

        if (array_key_exists('body', $response) && isset($response["body"]) && $response["body"] != "")
            $responsemsg = $response["body"];
        else
            $responsemsg = "";


        if ($responsemsg != "") {
            $responsemsg = explode("|^|", $responsemsg);
            if (is_array($responsemsg) && count($responsemsg) > 0) {

                if (isset($responsemsg[0]) && $responsemsg[0] != "") {
                    $msg = $responsemsg[0];
                } else {
                    $msg = "";
                }
                
                if (isset($responsemsg[1]) && $responsemsg[1] != "") {
                    $info = $responsemsg[1];
                } else {
                    $info = "";
                }

                if ($msg == "1") {
                    update_option("armSortInfo", $info);
                    echo "VERIFIED";
                    exit;
                }
                else 
                {
                	echo $msg; //phpcs:ignore
                	exit;
            	}
            }
        } else {
            echo "Invalid Request";
            exit;
        }
    }


		function armdeactivatelicense() {
			global $wp_version;
			$siteinfo = array();
	
			$siteinfo[] = get_bloginfo('name');
			$siteinfo[] = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : ''; //phpcs:ignore
			$siteinfo[] = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : ''; //phpcs:ignore
			$siteinfo[] = MEMBERSHIP_URL;
			$siteinfo[] = get_option("arm_version");
	
			$newstr = implode("||", $siteinfo);
			$postval = base64_encode($newstr);
	
			$verifycode = get_option("armSortOrder");
	
			if (isset($verifycode) && $verifycode != "") {
				$urltopost = "https://www.reputeinfosystems.com/tf/plugins/armember/verify/lic_de_act.php";
	
	
				$response = wp_remote_post($urltopost, array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => array(),
					'body' => array('verifypurchase' => $verifycode, 'postval' => $postval),
					'user-agent' => 'ARM-WordPress/' . $wp_version . '; ' . ARM_HOME_URL,
					'cookies' => array()
						)
				);
				
				if(is_wp_error($response)) 
				{
					$urltopost = "http://www.reputeinfosystems.com/tf/plugins/armember/verify/lic_de_act.php";
	
	
					$response = wp_remote_post($urltopost, array(
						'method' => 'POST',
						'timeout' => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking' => true,
						'headers' => array(),
						'body' => array('verifypurchase' => $verifycode, 'postval' => $postval),
						'user-agent' => 'ARM-WordPress/' . $wp_version . '; ' . ARM_HOME_URL,
						'cookies' => array()
							)
					);
				}
				
				
	
				if (array_key_exists('body', $response) && isset($response["body"]) && $response["body"] != "")
					$responsemsg = $response["body"];
				else
					$responsemsg = "";
	
				$chkplugver = $this->chkplugversionth($responsemsg);
	
				return $chkplugver;
				exit;
			}
			else {
				$resp = "Invalid Request";
				return $resp;
				exit;
			}
		}
		function armreqact( $force_check = 0, $check_time = 0, $message = '' ) {

			global $armember_check_plugin_copy, $ARMember;
			if( empty($armember_check_plugin_copy) || $force_check == 1 )
			{
				global $arm_capabilities_global,$arm_slugs;
				$arm_manage_license_key = !isset(($arm_capabilities_global['arm_manage_license']))?$arm_slugs->licensing:$arm_capabilities_global['arm_manage_license'];

        		$ARMember->arm_check_user_cap($arm_manage_license_key, '1', '0');

				$plugres = $this->armverifypurchasecode();
		
				if (isset($plugres) && $plugres != "") {
					$responsetext = $plugres;
		
					if ($responsetext == "License Activated Successfully.") {
						$flag = "VERIFIED";
						if( $force_check == 1 )
						{
							return $flag;
						}
						else 
						{
							echo $flag;
							exit;
						}
					} else if( $check_time == 0 ) {

						$_POST['arm_license_key'] = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
						$return_response = 1;
						$user_badge_update = $ARMember->arm_armember_package_update($return_response);
						if( !empty( $user_badge_update['arm_license_status'] ) )
						{
							$ARMember->arm_pkg_key_update( 1 );
							$plugres = "VERIFIED";
						}

						if( $force_check == 1 )
						{
							return $plugres;
						}
						else 
						{
							echo $plugres; //phpcs:ignore
							exit;
						}
					}
					else {
						if( $force_check == 1 )
						{
							if( !empty( $message ) )
							{
								return $message;
							}
							else
							{
								return $plugres;
							}
						}
						else 
						{
							if( !empty( $message ) )
							{
								echo $message;
								exit;
							}
							else
							{
								echo $plugres; //phpcs:ignore
								exit;
							}
						}
					}
				} else {
					$flag = "Invalid Request";
					if( $force_check == 1 )
					{
						return $flag;
					}
					else 
					{
						echo $flag;
						exit;
					}
					
				}
			}
			else
			{
				$_POST['arm_license_key'] = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
				$return_response = 1;
				$user_badge_update = $ARMember->arm_armember_package_update($return_response);
				if( !empty( $user_badge_update['arm_license_status'] ) )
				{
					echo "VERIFIED";
					exit;
				}
				else
				{
					echo "Invalid Request";
					exit;
				}
			}
		}

		function arm_check_update_badge_status(){
			$arm_badge_status=get_option('arm_pkg_status');
			return ($arm_badge_status=='valid')?1:0;
		}
	
		function chkplugversionth($myresponse) {
			if ($myresponse != "" && $myresponse == 1) {
				
				$new_key = '';
	
				$new_key = rand();
	
				$thresp = $this->checkthisvalidresp($new_key);
	
				if ($thresp == 1) {
					return "License Deactivted Sucessfully.";
					exit;
				} else {
					$resp = "Invalid Request";
					return $resp;
					exit;
				}
			} else {
				$resp = "Invalid Request";
				return $resp;
				exit;
			}
		}
		 function arm_get_remote_post_params($plugin_info = "") {
			global $wpdb;
	
			$action = "";
			$action = $plugin_info;
	
			if (!function_exists('get_plugins')) {
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}
			$plugin_list = get_plugins();
			$site_url = ARM_HOME_URL;
			$plugins = array();
	
			$active_plugins = get_option('active_plugins');
	
			foreach ($plugin_list as $key => $plugin) {
				$is_active = in_array($key, $active_plugins);
	
				//filter for only armember ones, may get some others if using our naming convention
				if (strpos(strtolower($plugin["Title"]), "armember") !== false) {
					$name = substr($key, 0, strpos($key, "/"));
					$plugins[] = array("name" => $name, "version" => $plugin["Version"], "is_active" => $is_active);
				}
			}
			$plugins = json_encode($plugins);
	
			//get theme info
			$theme = wp_get_theme();
			$theme_name = $theme->get("Name");
			$theme_uri = $theme->get("ThemeURI");
			$theme_version = $theme->get("Version");
			$theme_author = $theme->get("Author");
			$theme_author_uri = $theme->get("AuthorURI");
	
			$im = is_multisite();
			$sortorder = get_option("armSortOrder");
	
			$post = array("wp" => get_bloginfo("version"), "php" => phpversion(), "mysql" => $wpdb->db_version(), "plugins" => $plugins, "tn" => $theme_name, "tu" => $theme_uri, "tv" => $theme_version, "ta" => $theme_author, "tau" => $theme_author_uri, "im" => $im, "sortorder" => $sortorder);
	
			return $post;
		}
	
		
		function armgetapiurl() {
			$api_url = 'https://arpluginshop.com/';
			return $api_url;
		}
		
		function checkthisvalidresp($new_key) {
			global $ARMember;
			if ($new_key != "") {
				delete_option("armIsSorted");
				delete_option("armSortOrder");
				delete_option("armSortId");
				delete_option("armSortInfo");
	
				delete_site_option("armIsSorted");
				delete_site_option("armSortOrder");
				delete_site_option("armSortId");
				delete_site_option("armSortInfo");

				delete_option('arm_pkg_key');
				delete_option('arm_pkg');
				delete_option('arm_pkg_status');
				delete_option('arm_pkg_data_actvte_respnc');
				delete_option('arm_pkg_dmn');

				delete_site_option('arm_pkg_key');
				delete_site_option('arm_pkg');
				delete_site_option('arm_pkg_status');
				delete_site_option('arm_pkg_data_actvte_respnc');
				delete_site_option('arm_pkg_dmn');
				
				update_option('arm_is_user_private_content_feature', 0);
				update_option('arm_is_social_feature', 0);
				update_option('arm_is_social_login_feature', 0);
				update_option('arm_is_pro_ration_feature', 0);
				update_option('arm_is_drip_content_feature', 0);
				//update_option('arm_is_opt_ins_feature', 0);
				update_option('arm_is_coupon_feature', 0);
				update_option('arm_is_buddypress_feature', 0);
                update_option('arm_is_woocommerce_feature', 0);
                update_option('arm_is_multiple_membership_feature', 0);
                update_option('arm_is_mycred_feature', 0);
				update_option('arm_is_plan_limit_feature',0);
                update_option('arm_is_invoice_tax_feature', 0);
                update_option('arm_is_gutenberg_block_restriction_feature', 0);
                update_option('arm_is_beaver_builder_restriction_feature', 0);
                update_option('arm_is_divi_builder_restriction_feature', 0);
                update_option('arm_is_wpbakery_page_builder_restriction_feature', 0);
                update_option('arm_is_fusion_builder_restriction_feature', 0);
                update_option('arm_is_oxygen_builder_restriction_feature', 0);
                update_option('arm_is_siteorigin_builder_restriction_feature', 0);
                update_option('arm_is_bricks_builder_restriction_feature', 0);
				update_option('arm_is_pay_per_post_feature', 0);
				update_option('arm_is_api_service_feature', 0);
					
				$ARMember->armember_deactivate_addons();
	
				return "1";
				exit;
			} else {
				$resp = "Invalid Request";
				return $resp;
				exit;
			}
		}
		   function getwpversion() {

			global $arm_version;
			$bloginformation = array();
			$str = $this->get_rand_alphanumeric(10);
	
			if (is_multisite())
				$multisiteenv = "Multi Site";
			else
				$multisiteenv = "Single Site";
	
			$bloginformation[] = get_bloginfo('name');
			$bloginformation[] = get_bloginfo('description');
			$bloginformation[] = ARM_HOME_URL;
			$bloginformation[] = '';
			$bloginformation[] = get_bloginfo('version');
			$bloginformation[] = get_bloginfo('language');
			$bloginformation[] = $arm_version;
			$bloginformation[] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''; //phpcs:ignore
			$bloginformation[] = $str;
			$bloginformation[] = $multisiteenv;
	
			$this->checksite($str);
	
			$valstring = implode("||", $bloginformation);
			$encodedval = base64_encode($valstring);
	
			$urltopost = "https://reputeinfosystems.net/armember/wp_in.php";
			$response = wp_remote_post($urltopost, array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => array('wpversion' => $encodedval),
				'cookies' => array()
					)
			);
		}

		  function get_rand_alphanumeric($length) {
			  
			  if ($length > 0) {
				  $rand_id = "";
				  for ($i = 1; $i <= $length; $i++) {
					  mt_srand((double) microtime() * 1000000);
					  $num = mt_rand(1, 36);
					  $rand_id .= $this->assign_rand_value($num);
				  }
			  }
			  return $rand_id;
		  }
	  	 
		 
		 function armverifypurchasecode() {
       global $wp_version;
        $lidata = array();

        $lidata[] = $_POST["cust_name"]; //phpcs:ignore
        $lidata[] = $_POST["cust_email"]; //phpcs:ignore
        $lidata[] = $_POST["license_key"]; //phpcs:ignore
        $lidata[] = $_POST["domain_name"]; //phpcs:ignore
		
		

        $pluginuniquecode = $this->generateplugincode();
        $lidata[] = $pluginuniquecode;
        $lidata[] = MEMBERSHIP_URL;//phpcs:ignore
        $lidata[] = get_option("arm_version");
		$lidata[] = isset( $_POST["is_receive_updates"] ) ? $_POST["is_receive_updates"] : ''; //phpcs:ignore

        $valstring = implode("||", $lidata);
        $encodedval = base64_encode($valstring);

        $urltopost = "https://www.reputeinfosystems.com/tf/plugins/armember/verify/lic_act_arm.php";

        $response = wp_remote_post($urltopost, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array('verifypurchase' => $encodedval),
			'user-agent' => 'ARM-WordPress/' . $wp_version . '; ' . ARM_HOME_URL,
            'cookies' => array()
                )
        );
		
		if(is_wp_error($response)) 
		{
			$urltopost = "http://www.reputeinfosystems.com/tf/plugins/armember/verify/lic_act_arm.php";

			$response = wp_remote_post($urltopost, array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => array('verifypurchase' => $encodedval),
				'user-agent' => 'ARM-WordPress/' . $wp_version . '; ' . ARM_HOME_URL,
				'cookies' => array()
					)
			);
		}

        if (array_key_exists('body', $response) && isset($response["body"]) && $response["body"] != "")
            $responsemsg = $response["body"];
        else
            $responsemsg = "";


        if ($responsemsg != "") {
            $responsemsg = explode("|^|", $responsemsg);
            if (is_array($responsemsg) && count($responsemsg) > 0) {

                if (isset($responsemsg[0]) && $responsemsg[0] != "") {
                    $msg = $responsemsg[0];
                } else {
                    $msg = "";
                }
                if (isset($responsemsg[1]) && $responsemsg[1] != "") {
                    $code = $responsemsg[1];
                } else {
                    $code = "";
                }
                if (isset($responsemsg[2]) && $responsemsg[2] != "") {
                    $info = $responsemsg[2];
                } else {
                    $info = "";
                }

                if ($msg == "1") {
                    $checklic = $this->checksoringcode($code, $info);

                    if ($checklic == "1") {
                        return "License Activated Successfully.";
                        exit;
                    } else {
                        return "Invalid Request";
                        exit;
                    }
                } else if ($msg == "THIS PURCHASED CODE IS ALREADY USED FOR ANOTHER DOMAIN") {

                    return $responsemsg[0] . '||' . $responsemsg[1];
                    exit;
                } else {
                    return $responsemsg[0];
                    exit;
                }
            } else {
                return $responsemsg;
                exit;
            }
        } else {
            return "Invalid Request";
            exit;
        }
    }
	
	function checksoringcode($code, $info) {
        

        $mysortid = base64_decode($code);
        $mysortid = explode("^", $mysortid);

        if ($mysortid != "" && count($mysortid) > 0) {
            $setdata = $this->setdata($code, $info);

            return $setdata;
            exit;
        } else {
            return 0;
            exit;
        }
    }
	
	 function setdata($code, $info) {
        if ($code != "") {
            $mysortid = base64_decode($code);
            $mysortid = explode("^", $mysortid);
            $mysortid = $mysortid[4];

            update_option("armIsSorted", "Yes");
            update_option("armSortOrder", $code);
            update_option("armSortId", $mysortid);
            update_option("armSortInfo", $info);

            return 1;
            exit;
        } else {
            return 0;
            exit;
        }
    }
	
	function generateplugincode() {
        $siteinfo = array();

        $siteinfo[] = get_bloginfo('name');
        $siteinfo[] = get_bloginfo('description');
        $siteinfo[] = ARM_HOME_URL;
        $siteinfo[] = get_bloginfo('admin_email');
        $siteinfo[] = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : ''; //phpcs:ignore

        $newstr = implode("^", $siteinfo);
        $postval = base64_encode($newstr);

        return $postval;
    }
		  function assign_rand_value($num) {
	  
			  switch ($num) {
				  case "1" : $rand_value = "a";
					  break;
				  case "2" : $rand_value = "b";
					  break;
				  case "3" : $rand_value = "c";
					  break;
				  case "4" : $rand_value = "d";
					  break;
				  case "5" : $rand_value = "e";
					  break;
				  case "6" : $rand_value = "f";
					  break;
				  case "7" : $rand_value = "g";
					  break;
				  case "8" : $rand_value = "h";
					  break;
				  case "9" : $rand_value = "i";
					  break;
				  case "10" : $rand_value = "j";
					  break;
				  case "11" : $rand_value = "k";
					  break;
				  case "12" : $rand_value = "l";
					  break;
				  case "13" : $rand_value = "m";
					  break;
				  case "14" : $rand_value = "n";
					  break;
				  case "15" : $rand_value = "o";
					  break;
				  case "16" : $rand_value = "p";
					  break;
				  case "17" : $rand_value = "q";
					  break;
				  case "18" : $rand_value = "r";
					  break;
				  case "19" : $rand_value = "s";
					  break;
				  case "20" : $rand_value = "t";
					  break;
				  case "21" : $rand_value = "u";
					  break;
				  case "22" : $rand_value = "v";
					  break;
				  case "23" : $rand_value = "w";
					  break;
				  case "24" : $rand_value = "x";
					  break;
				  case "25" : $rand_value = "y";
					  break;
				  case "26" : $rand_value = "z";
					  break;
				  case "27" : $rand_value = "0";
					  break;
				  case "28" : $rand_value = "1";
					  break;
				  case "29" : $rand_value = "2";
					  break;
				  case "30" : $rand_value = "3";
					  break;
				  case "31" : $rand_value = "4";
					  break;
				  case "32" : $rand_value = "5";
					  break;
				  case "33" : $rand_value = "6";
					  break;
				  case "34" : $rand_value = "7";
					  break;
				  case "35" : $rand_value = "8";
					  break;
				  case "36" : $rand_value = "9";
					  break;
			  }
			  return $rand_value;
		  }
	  
		  function checksite($str) {
			  update_option('arm_wp_get_version', $str);
		  }

		  function arm_verify_packg($flg, $pkg_d_code) {
			global $armember_check_plugin_copy;
			if( !empty( $armember_check_plugin_copy ) )
			{
				$pkg_key=get_option('arm_pkg_key');
				if($pkg_key==$pkg_d_code)
				{
					$flg = 0;
				}
				else
				{
					$flg = 1;
				}
			}
			return $flg;
		  }
		
		
		function arm_get_activity_by($field = '', $value = '', $limit = '', $object_type = ARRAY_A)
		{
			global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_subscription_plans;
			$object_type = !empty($object_type) ? $object_type : ARRAY_A;
			$limit = (!empty($limit)) ? " LIMIT " . $limit : "";
			$result = false;
			if (!empty($field) && $value != '') {
				$result = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `".$ARMember->tbl_arm_activity."` WHERE `$field`=%s ORDER BY `arm_activity_id` DESC $limit",$value), $object_type); //phpcs:ignore --Reason $ARMember->tbl_arm_activity is a table name
			}
			return $result;
		}
        function arm_crop_image() {
			global $ARMember;
			$ARMember->arm_check_user_cap( '',0,1 );//phpcs:ignore --Reason:Verifying nonce
			$ARMember->arm_session_start();
			$update_meta = isset( $_POST['update_meta'] ) ? sanitize_text_field( $_POST['update_meta'] ) : ''; //phpcs:ignore
			$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : ''; //phpcs:ignore
			$cord = isset( $_POST['cord'] ) ? sanitize_text_field( $_POST['cord'] ) : ''; //phpcs:ignore
			$user_id = get_current_user_id();

			$arm_src = isset( $_POST['src'] ) ? esc_url_raw( $_POST['src'] ) : ''; //phpcs:ignore
			$arm_rotate = isset( $_POST['rotate'] ) ? $_POST['rotate'] : 'undefined'; //phpcs:ignore

			/*this change need to confirm with multisite*/
			$file_base_name = $ARMember->arm_get_basename( $arm_src );
			
			$meta_field_name = $type;
			if(isset($type) && ($type == 'profile' || $type == 'profile_pic' || $type == 'cover' || $type == 'profile_cover')){
				switch ($type) {
					case 'profile':
					case 'profile_pic':
						$meta_field_name = "avatar";
						break;
					case 'cover':
					case 'profile_cover':
						$meta_field_name = "profile_cover";
						break;
				}
			}

			$allowed = false;
			$allowed = apply_filters('arm_modify_file_validation_external', $allowed);
			
			if ( !empty( $meta_field_name ) && ( ( !empty($_SESSION['arm_file_upload_arr'][$meta_field_name]) && ($_SESSION['arm_file_upload_arr'][$meta_field_name]==$file_base_name || (is_array($_SESSION['arm_file_upload_arr'][$meta_field_name]) && in_array($file_base_name,$_SESSION['arm_file_upload_arr'][$meta_field_name] ) ) ) ) || $allowed ) ) //phpcs:ignore
			{
				$arm_src = MEMBERSHIP_UPLOAD_URL . '/' . $ARMember->arm_get_basename( $arm_src );

				$info     = getimagesize( MEMBERSHIP_UPLOAD_DIR . '/' . $ARMember->arm_get_basename( $arm_src ) );
				$file     = esc_url_raw( $arm_src );
				$file1    = MEMBERSHIP_UPLOAD_DIR . '/' . $ARMember->arm_get_basename( $arm_src );
				$orgnl_hw = getimagesize( $file1 );
				$orgnl_w  = $orgnl_hw[0];
				$orgnl_h  = $orgnl_hw[1];
				$targ_x1  = 0;
				$targ_y1  = 0;
				$targ_x2  = $orgnl_w;
				$targ_y2  = $orgnl_h;
				$is_crop  = false;
				if ( !empty( $cord ) ) {
					$crop = explode( ',', $cord );
					if ( $crop[2] != 0 && $crop[3] != 0 ) {
						$targ_x1 = intval($crop[0]);
						$targ_y1 = intval($crop[1]);
						$targ_x2 = intval($crop[2]);
						$targ_y2 = intval($crop[3]);
						$is_crop = true;
					}
				}

				if ( $type == 'profile' ) {

					if ( $update_meta != 'no' ) {
						update_user_meta( $user_id, 'avatar', $file );
						do_action( 'arm_upload_bp_avatar', $user_id );
					}

					$thumb_w = 220;
					$thumb_h = 220;
				} elseif ( $type == 'cover' ) {
					$thumb_w = 918;
					$thumb_h = 320;

					if ( $update_meta != 'no' ) {
						update_user_meta( $user_id, 'profile_cover', $file );
						do_action( 'arm_upload_bp_profile_cover', $user_id );
					}
				}
				if ( $arm_rotate != 'undefined' ) {
					if ( $arm_rotate == -90 || $arm_rotate == 270 ) {
						$arm_rotate = 90;
					} elseif ( $arm_rotate == -180 || $arm_rotate == 180 ) {
						$arm_rotate = 180;
					} elseif ( $arm_rotate == -270 || $arm_rotate == 90 ) {
						$arm_rotate = 270;
					}
					$new_targ_x1     = $targ_x1;
					$new_targ_y1     = $targ_y1;
					$fileTemp        = $file1;
					$image_info      = getimagesize( $fileTemp );
					$original_width  = $image_info[0];
					$original_height = $image_info[1];
					$new_width       = abs( $targ_x2 - $new_targ_x1 );
					$new_height      = abs( $targ_y2 - $new_targ_y1 );
					if ( $info['mime'] == 'image/png' ) {
						$source         = imagecreatefrompng( $fileTemp );
						$imageRotate    = imagerotate( $source, $arm_rotate, 0 );
						$rotated_width  = imagesx( $imageRotate );
						$rotated_height = imagesy( $imageRotate );
						$dx             = $rotated_width - $original_width;
						$dy             = $rotated_height - $original_height;
						$crop_x         = 0;
						$crop_y         = 0;
						if ( $is_crop ) {
							$crop_x = $dx / 2 + $new_targ_x1;
							$crop_y = $dy / 2 + $new_targ_y1;
						}
						$new_image = imagecreatetruecolor( $targ_x2, $targ_y2 );
						if ( $is_crop ) {
							imagealphablending( $new_image, false );
							imagesavealpha( $new_image, true );
							imagecopyresampled( $new_image, $imageRotate, 0, 0, $targ_x1, $targ_y1, $targ_x2, $targ_y2, $targ_x2, $targ_y2 );
							$upload = imagepng( $new_image, $fileTemp );
						} else {
							$upload = imagepng( $imageRotate, $fileTemp );
						}
						
						$original_info = getimagesize( $file1 );
						$original_w    = $original_info[0];
						$original_h    = $original_info[1];
						$original_img  = imagecreatefrompng( $file1 );
						$thumb_img     = imagecreatetruecolor( $thumb_w, $thumb_h );
						imagealphablending( $thumb_img, false );
						imagesavealpha( $thumb_img, true );
						imagecopyresampled( $thumb_img, $original_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $original_w, $original_h );
						imagepng( $thumb_img, MEMBERSHIP_UPLOAD_DIR . '/' . basename( $file ) );
					} elseif ( $info['mime'] == 'image/bmp' ) {
						$source         = imagecreatefrombmp( $fileTemp );
						$imageRotate    = imagerotate( $source, $arm_rotate, 0 );
						$rotated_width  = imagesx( $imageRotate );
						$rotated_height = imagesy( $imageRotate );
						$dx             = $rotated_width - $original_width;
						$dy             = $rotated_height - $original_height;
						$crop_x         = 0;
						$crop_y         = 0;
						$targ_x1        = $new_targ_x1;
						if ( $is_crop ) {
							$crop_x = $dx / 2 + $new_targ_x1;
							$crop_y = $dy / 2 + $new_targ_y1;
						}
						$new_image = imagecreatetruecolor( $targ_x2, $targ_y2 );
						if ( $is_crop ) {
							imagecopyresampled( $new_image, $imageRotate, 0, 0, $targ_x1, $targ_y1, $targ_x2, $targ_y2, $targ_x2, $targ_y2 );
							$upload = imagebmp( $new_image, $fileTemp );
						} else {
							$upload = imagebmp( $imageRotate, $fileTemp );
						}

						$original_info = getimagesize( $file1 );
						$original_w    = $original_info[0];
						$original_h    = $original_info[1];
						$original_img  = imagecreatefrombmp( $file1 );
						$thumb_img     = imagecreatetruecolor( $thumb_w, $thumb_h );
						imagecopyresampled( $thumb_img, $original_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $original_w, $original_h );
						imagebmp( $thumb_img, MEMBERSHIP_UPLOAD_DIR . '/' . basename( $file ) );
					} else {
						$source         = imagecreatefromjpeg( $fileTemp );
						$imageRotate    = imagerotate( $source, $arm_rotate, 0 );
						$rotated_width  = imagesx( $imageRotate );
						$rotated_height = imagesy( $imageRotate );
						$dx             = $rotated_width - $original_width;
						$dy             = $rotated_height - $original_height;
						$crop_x         = 0;
						$crop_y         = 0;
						$targ_x1        = $new_targ_x1;
						if ( $is_crop ) {
							$crop_x = $dx / 2 + $new_targ_x1;
							$crop_y = $dy / 2 + $new_targ_y1;
						}
						$new_image = imagecreatetruecolor( $targ_x2, $targ_y2 );
						if ( $is_crop ) {
							imagecopyresampled( $new_image, $imageRotate, 0, 0, $targ_x1, $targ_y1, $targ_x2, $targ_y2, $targ_x2, $targ_y2 );
							$upload = imagejpeg( $new_image, $fileTemp );
						} else {
							$upload = imagejpeg( $imageRotate, $fileTemp );
						}

						$original_info = getimagesize( $file1 );
						$original_w    = $original_info[0];
						$original_h    = $original_info[1];
						$original_img  = imagecreatefromjpeg( $file1 );
						$thumb_img     = imagecreatetruecolor( $thumb_w, $thumb_h );
						imagecopyresampled( $thumb_img, $original_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $original_w, $original_h );
						imagejpeg( $thumb_img, MEMBERSHIP_UPLOAD_DIR . '/' . basename( $file ) );
					}
				}

				if ( $type == 'profile' ) {
					if ( $update_meta != 'no' ) {
						update_user_meta( $user_id, 'avatar', $file );
						do_action( 'arm_after_upload_bp_avatar', $user_id );
					}
				} elseif ( $type == 'cover' ) {
					if ( $update_meta != 'no' ) {
						update_user_meta( $user_id, 'profile_cover', $file );
						do_action( 'arm_after_upload_bp_profile_cover', $user_id );
					}
				}

				echo esc_url($file);
			}
			die();
		}

        function path_only($file) {
            return trailingslashit(dirname($file));
        }

        function arm_allowed_wp_mime_types()
        {
        	$mimes = get_allowed_mime_types();
	        ksort($mimes);
	        $mcount = count($mimes);
	        $third = ceil($mcount / 3);
	        $c = 0;
	        $mimes['exe'] = '';
	        unset($mimes['exe']);

	        $allowed_mimes = array();

	        foreach( $mimes as $ext => $type ){
	            if( strpos($ext, '|') !== false ){
	                $exts = explode('|',$ext);
	                foreach( $exts as $extension){
	                    if( $extension != '' ){
	                        array_push($allowed_mimes,$extension);
	                    }
	                }
	            } else {
	                array_push($allowed_mimes,$ext);
	            }
	        }

	        return $allowed_mimes;
        }

        function arm_upload_front() {
		global $ARMember, $arm_capabilities_global;
		//$ARMember->arm_check_user_cap( '',0, 1 ); //phpcs:ignore --Reason:Verifying nonce
	        $upload_dir = MEMBERSHIP_UPLOAD_DIR.'/';
	        $upload_url = MEMBERSHIP_UPLOAD_URL.'/';

			$ARMember->arm_session_start();
	        $file_name = (isset($_SERVER['HTTP_X_FILENAME']) ? "arm_".arm_generate_random_code()."_".($ARMember->arm_get_basename($_SERVER['HTTP_X_FILENAME'])) : false); //phpcs:ignore
	        $file_meta_name = (isset($_SERVER['HTTP_FILEMETANAME']) ? sanitize_text_field($_SERVER['HTTP_FILEMETANAME']) : false); //phpcs:ignore

			if($file_meta_name){
				switch ($file_meta_name) {
					case 'profile':
					case 'profile_pic':
						$file_meta_name = "avatar";
						break;
					case 'cover':
						$file_meta_name = "profile_cover";
						break;
					case 'arm_mcard_logo':
						$file_meta_name = "company_logo";
						break;
					case 'arm_mcard_bg_img':
						$file_meta_name = "card_background";
						break;
					case 'arm_form_settings[style][form_bg_file]':
						$file_meta_name = "form_bg_file";
						break;
					default:
						$file_meta_name = apply_filters('arm_modify_file_meta_key_external', $file_meta_name);
						break;
				}
			}
	        $response = "";
	        if ($file_name)
	        {
				$content_length = (int) $_SERVER['CONTENT_LENGTH']; //phpcs:ignore
	        	$file_size_new = number_format( ($content_length/1048576), 2, '.', '');
				
	        	$arm_is_valid_file = $this->arm_check_valid_file_ext_data($file_name, $file_size_new, $_FILES['armfileselect'] );//phpcs:ignore

				$allowed = false;
				$allowed = apply_filters('arm_modify_file_validation_external', $allowed);
	        	if( $arm_is_valid_file && ( isset($_SESSION['arm_file_upload_arr'][$file_meta_name]) || $allowed ) )
	        	{
					$this->session_for_file_handle($file_meta_name,$file_name);
	            	$arm_upload_file_path = $upload_dir.$file_name;
	            	$file_result = $this->arm_upload_file_function($_FILES['armfileselect']['tmp_name'], $arm_upload_file_path); //phpcs:ignore

	                if(isset($_REQUEST['arm_file_type']) && $_REQUEST['arm_file_type'] == 'arm_stripe_icon' ) 
					{
						$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
	                	if(file_exists($upload_dir . $file_name)) {
							$response = array();
		                	$image = getimagesize($upload_dir . $file_name);
							$width = isset($image[0]) ? $image[0] : 0;
							$height = isset($image[1]) ? $image[1] : 0;
							if($width != 70 || $height != 70) {
								unlink($upload_dir . $file_name);
								$response['status'] = "error";
								$response['message'] = esc_html__("Select Logo image with 70X70 px", "ARMember");
								echo json_encode($response);
								die;
							}
		                }
	                }
	                $response = $upload_url . $file_name;
	            }
	            echo $response; //phpcs:ignore
	            exit;
	        } else {
	            $files = $_FILES['armfileselect']; //phpcs:ignore
	            $file_size = (isset($_REQUEST['allow_size'])) ? intval( $_REQUEST['allow_size'] ) : '';
	            $file_name = !empty( $_REQUEST['fname'] ) ? "arm_".arm_generate_random_code()."_".(sanitize_text_field( $_REQUEST['fname'] )) : '';
	            $file_size_new = intval( $files['size'] );
	            $file_size_new = number_format($file_size_new / 1048576, 2, '.', '');
	            $arm_is_valid_file = $this->arm_check_valid_file_ext_data($file_name, $file_size_new, $files);

				$allowed = false;
				$allowed = apply_filters('arm_modify_file_validation_external', $allowed);
				if( $arm_is_valid_file && ( isset($_SESSION['arm_file_upload_arr'][$file_meta_name]) || $allowed ) )
	        	{
					if (!empty($file_size) && ($file_size_new > $file_size)) {
						$response = "<p class='error_upload_size'>".esc_html__('File size not allowed', 'ARMember')."</p>";
	                } else {	                		
						$this->session_for_file_handle($file_meta_name,$file_name);
	                	$arm_upload_file_path = $upload_dir . $file_name;
	                	$this->arm_upload_file_function($files['tmp_name'], $arm_upload_file_path);//phpcs:ignore
	                    $response = $upload_url . $file_name;
	                    echo "<p class='uploaded'>" . esc_html($upload_url) . esc_html($file_name) . "</p>"; //phpcs:ignore
	                }
	            }
	        }
	        exit;
	    }

	    function arm_upload_cover() {
	        global $ARMember;
	        //$ARMember->arm_check_user_cap( '', '1' ); //phpcs:ignore --Reason:Verifying nonce
	        $upload_dir = MEMBERSHIP_UPLOAD_DIR.'/';
	        $upload_url = MEMBERSHIP_UPLOAD_URL.'/';
			$ARMember->arm_session_start();
	        $file_name = (isset($_SERVER['HTTP_X_FILENAME']) ? "arm_".arm_generate_random_code()."_".($ARMember->arm_get_basename($_SERVER['HTTP_X_FILENAME'])) : false); //phpcs:ignore
			$file_meta_name = (isset($_SERVER['HTTP_FILEMETANAME']) ? sanitize_text_field($_SERVER['HTTP_FILEMETANAME']) : false); //phpcs:ignore
			switch ($file_meta_name) {
				case 'profile_cover':
				case 'cover':
					$file_meta_name = "profile_cover";
					break;
			}
	        $response = "";
	        $userID = get_current_user_id();
	        if ($file_name && !empty($userID) && $userID != 0) {
				
				$content_length = (int) $_SERVER['CONTENT_LENGTH']; //phpcs:ignore
	        	$file_size_new = number_format( ($content_length/1048576), 2, '.', '');
				
	        	$arm_is_valid_file = $this->arm_check_valid_file_ext_data($file_name, $file_size_new, $_FILES['armfileselect'] ); //phpcs:ignore

				$allowed = false;
				$allowed = apply_filters('arm_modify_file_validation_external', $allowed);
	        	if( $arm_is_valid_file && (isset($_SESSION['arm_file_upload_arr'][$file_meta_name]) || $allowed ) )
	        	{
					//$oldCover = get_user_meta($userID, 'profile_cover', true);
					$this->session_for_file_handle($file_meta_name,$file_name);

					$arm_upload_file_path = $upload_dir.$file_name;
	                $this->arm_upload_file_function($_FILES['armfileselect']['tmp_name'], $arm_upload_file_path); //phpcs:ignore

	                $response = $upload_url . $file_name;
	                echo $response;//phpcs:ignore
	                exit;
	            }
	        } else {
	            $files = !empty($_FILES['armfileselect']) ? $_FILES['armfileselect'] : ''; //phpcs:ignore
	            $file_size = (isset($_REQUEST['allow_size'])) ? intval( $_REQUEST['allow_size'] ) : '';
	            $file_name = !empty($_REQUEST['fname']) ? "arm_".arm_generate_random_code()."_".(sanitize_text_field( $_REQUEST['fname'] )) : '';
	            $file_size_new = !empty( $_FILES['armfileselect']['size'] ) ? intval( $_FILES['armfileselect']['size'] ) : '';
	            $file_size_new = number_format($file_size_new / 1048576, 2, '.', '');

	            $arm_is_valid_file = $this->arm_check_valid_file_ext_data($file_name, $file_size_new, $files );
				
				$allowed = false;
				$allowed = apply_filters('arm_modify_file_validation_external', $allowed);
	        	if( $arm_is_valid_file && ( isset( $_SESSION['arm_file_upload_arr'][$file_meta_name] ) || $allowed ) )
	        	{
					if (!empty($file_size) && ($file_size_new > $file_size)) {
						$response = "<p class='error_upload_size'>" . esc_html__('File size not allowed', 'ARMember') . "</p>";
	                } else {
						$this->session_for_file_handle($file_meta_name,$file_name);
	                	$arm_upload_file_path = $upload_dir . $file_name;
	                	$this->arm_upload_file_function($files['tmp_name'], $arm_upload_file_path);
	                    $response = $upload_url . $file_name;
	                    echo "<p class='uploaded'>" . esc_html($upload_url) . esc_html($file_name) . "</p>"; //phpcs:ignore
	                }
	            }
	        }
	        exit;
	    }

	    function arm_upload_profile() {
	        global $ARMember;
	        //$ARMember->arm_check_user_cap( '', '1' ); //phpcs:ignore --Reason:Verifying nonce
	        $upload_dir = MEMBERSHIP_UPLOAD_DIR.'/';
	        $upload_url = MEMBERSHIP_UPLOAD_URL.'/';
			$ARMember->arm_session_start();
	        $file_name = (isset($_SERVER['HTTP_X_FILENAME']) ? "arm_".arm_generate_random_code()."_".($ARMember->arm_get_basename($_SERVER['HTTP_X_FILENAME'])) : false); //phpcs:ignore
			$file_meta_name = (isset($_SERVER['HTTP_FILEMETANAME']) ? sanitize_text_field($_SERVER['HTTP_FILEMETANAME']) : false); //phpcs:ignore
			
			switch ($file_meta_name) {
				case 'profile':
				case 'avatar':
					$file_meta_name = "avatar";
					break;
			}
			$response = "";
	        $userID = get_current_user_id();
	        if ($file_name && !empty($userID) && $userID != 0) {
				
				//$oldCover = get_user_meta($userID, 'profile_cover', true);
	            $content_length = (int) $_SERVER['CONTENT_LENGTH']; //phpcs:ignore
	    	    $file_size_new = number_format( ($content_length/1048576), 2, '.', '');
				
	            $arm_is_valid_file = $this->arm_check_valid_file_ext_data($file_name, $file_size_new, $_FILES['armfileselect'] ); //phpcs:ignore

				$allowed = false;
				$allowed = apply_filters('arm_modify_file_validation_external', $allowed);
	        	if( $arm_is_valid_file && ( isset($_SESSION['arm_file_upload_arr'][$file_meta_name]) || $allowed ) )
	        	{
					$this->session_for_file_handle($file_meta_name,$file_name);
					$arm_upload_file_path = $upload_dir.$file_name;
	                $this->arm_upload_file_function($_FILES['armfileselect']['tmp_name'], $arm_upload_file_path); //phpcs:ignore

	                $response = $upload_url . $file_name;
	                echo $response; //phpcs:ignore
	                exit;
	            }
	        } else {
	            $files = $_FILES['armfileselect']; //phpcs:ignore
	            $file_size = (isset($_REQUEST['allow_size'])) ? intval( $_REQUEST['allow_size'] ) : '';
	            $file_name =  !empty( $_REQUEST['fname'] ) ? "arm_".arm_generate_random_code()."_".(sanitize_text_field( $_REQUEST['fname'] )) : '';
	            $file_size_new = $files['size'];
	    	    $file_size_new = number_format($file_size_new / 1048576, 2, '.', '');

	            $arm_is_valid_file = $this->arm_check_valid_file_ext_data($file_name, $file_size_new, $files );
				$allowed = false;
				$allowed = apply_filters('arm_modify_file_validation_external', $allowed);
	        	if( $arm_is_valid_file && ( isset($_SESSION['arm_file_upload_arr'][$file_meta_name]) || $allowed ) )
	        	{
					if (!empty($file_size) && ($file_size_new > $file_size)) {
						$response = "<p class='error_upload_size'>" . esc_html__('File size not allowed', 'ARMember') . "</p>";
	                } else {
						$this->session_for_file_handle($file_meta_name,$file_name);
	                	$arm_upload_file_path = $upload_dir . $file_name;
	                	$this->arm_upload_file_function($files['tmp_name'], $arm_upload_file_path);
	                    $response = $upload_url . $file_name;
	                    echo "<p class='uploaded'>" . $upload_url . $file_name . "</p>";//phpcs:ignore
	                }
	            }
	        }
	        exit;
	   }

	   function arm_upload_badge()
	   {
	   		global $arm_capabilities_global,$ARMember;
	   		if(current_user_can($arm_capabilities_global['arm_badges']))
	   		{
				$ARMember->arm_check_user_cap('',0,1); //phpcs:ignore --Reason:Verifying nonce
		   		$upload_dir = MEMBERSHIP_UPLOAD_DIR.'/social_badges/';
		        $upload_url = MEMBERSHIP_UPLOAD_URL.'/social_badges/';
				$ARMember->arm_session_start();
		        if (!is_dir($upload_dir)) {
					wp_mkdir_p($upload_dir);
				}

				$file_name = (isset($_SERVER['HTTP_X_FILENAME']) ? "arm_".arm_generate_random_code()."_".($ARMember->arm_get_basename($_SERVER['HTTP_X_FILENAME'])) : false); //phpcs:ignore
				$file_meta_name = (isset($_SERVER['HTTP_FILEMETANAME']) ? sanitize_text_field($_SERVER['HTTP_FILEMETANAME']) : false); //phpcs:ignore
				
				$response = "";
				if ($file_name)
				{
					$content_length = (int) $_SERVER['CONTENT_LENGTH'];//phpcs:ignore
					$file_size_new = number_format( ($content_length/1048576), 2, '.', '');
					
					$arm_is_valid_file = $this->arm_check_valid_file_ext_data($file_name, $file_size_new, $_FILES['armfileselect'] ); //phpcs:ignore

					$allowed = false;
					$allowed = apply_filters('arm_modify_file_validation_external', $allowed);
		        	if( $arm_is_valid_file && ( isset($_SESSION['arm_file_upload_arr'][$file_meta_name]) || $allowed ) )
		        	{
						$this->session_for_file_handle($file_meta_name,$file_name);
						$arm_upload_file_path = $upload_dir.$file_name;
		            	$this->arm_upload_file_function($_FILES['armfileselect']['tmp_name'], $arm_upload_file_path); //phpcs:ignore
						$response = $file_name;
					}
					echo $response;//phpcs:ignore
					exit;
				} else {
					$files = $_FILES['armfileselect']; //phpcs:ignore
					$file_size = (isset($_REQUEST['allow_size'])) ? intval( $_REQUEST['allow_size'] ) : '';
					$file_name = !empty( $_REQUEST['fname'] ) ? "arm_".arm_generate_random_code()."_".(sanitize_text_field( $_REQUEST['fname'] )) : '';
					$file_size_new = intval( $files['size'] );
					$file_size_new = number_format($file_size_new / 1048576, 2, '.', '');

					$arm_is_valid_file = $this->arm_check_valid_file_ext_data($file_name, $file_size_new, $files );

					$allowed = false;
					$allowed = apply_filters('arm_modify_file_validation_external', $allowed);
		        	if( $arm_is_valid_file && (isset($_SESSION['arm_file_upload_arr'][$file_meta_name]) || $allowed ) )
		        	{
						if (!empty($file_size) && ($file_size_new > $file_size)) {
							$response = "<p class='error_upload_size'>".esc_html__('File size not allowed', 'ARMember')."</p>";
						} else {
							$this->session_for_file_handle($file_meta_name,$file_name);
		                	$arm_upload_file_path = $upload_dir . $file_name;
		                	$this->arm_upload_file_function($files['tmp_name'], $arm_upload_file_path);
							$response = $upload_url . $file_name;
							echo "<p class='uploaded'>" . esc_html($file_name) . "</p>"; //phpcs:ignore
						}
					}
				}
			}
			exit;
	   }

	   function arm_upload_social_icon()
	   {
			global $ARMember;
	   		$upload_dir = MEMBERSHIP_UPLOAD_DIR.'/social_icon/';
	        $upload_url = MEMBERSHIP_UPLOAD_URL.'/social_icon/';
			$ARMember->arm_session_start();
	        if (!is_dir($upload_dir)) {
				wp_mkdir_p($upload_dir);
			}

			$ARMember->arm_check_user_cap('',0,1); //phpcs:ignore --Reason:Verifying nonce

			$file_name = (isset($_SERVER['HTTP_X_FILENAME']) ? "arm_".arm_generate_random_code()."_".($ARMember->arm_get_basename($_SERVER['HTTP_X_FILENAME'])) : false); //phpcs:ignore
			$file_meta_name = (isset($_SERVER['HTTP_FILEMETANAME']) ? sanitize_text_field($_SERVER['HTTP_FILEMETANAME']) : false); //phpcs:ignore
			
			$response = "";
			if ($file_name)
			{
				$content_length = (int) $_SERVER['CONTENT_LENGTH']; //phpcs:ignore
				$file_size_new = number_format( ($content_length/1048576), 2, '.', '');

				
				$arm_is_valid_file = $this->arm_check_valid_file_ext_data($file_name, $file_size_new, $_FILES['armfileselect'] ); //phpcs:ignore

				$allowed = false;
				$allowed = apply_filters('arm_modify_file_validation_external', $allowed);
				if( $arm_is_valid_file && (isset($_SESSION['arm_file_upload_arr'][$file_meta_name]) || $allowed ) )
	        	{
					$this->session_for_file_handle($file_meta_name,$file_name);
	            	$arm_upload_file_path = $upload_dir.$file_name;
	            	$this->arm_upload_file_function($_FILES['armfileselect']['tmp_name'], $arm_upload_file_path); //phpcs:ignore
					$response = $upload_url.$file_name;
				}
				echo $response; //phpcs:ignore
				exit;
			} else {
				$files = $_FILES['armfileselect']; //phpcs:ignore
				$file_size = (isset($_REQUEST['allow_size'])) ? intval($_REQUEST['allow_size']) : '';
				$file_name = !empty( $_REQUEST['fname'] ) ? "arm_".arm_generate_random_code()."_".(sanitize_text_field($_REQUEST['fname'])) : '';
				$file_type_new = $_FILES['armfileselect']['type']; //phpcs:ignore
				$file_size_new = $_FILES['armfileselect']['size']; //phpcs:ignore
				$file_size_new = number_format($file_size_new / 1048576, 2, '.', '');

				
				$arm_is_valid_file = $this->arm_check_valid_file_ext_data($file_name, $file_size_new, $_FILES['armfileselect'] );//phpcs:ignore

				$allowed = false;
				$allowed = apply_filters('arm_modify_file_validation_external', $allowed);
	        	if( $arm_is_valid_file && (isset($_SESSION['arm_file_upload_arr'][$file_meta_name]) || $allowed ) )
	        	{
					if (!empty($file_size) && ($file_size_new > $file_size)) {
						$response = "<p class='error_upload_size'>".esc_html__('File size not allowed', 'ARMember')."</p>";
					} else {
						$this->session_for_file_handle($file_meta_name,$file_name);
	                	$arm_upload_file_path = $upload_dir.$file_name;
	                	$this->arm_upload_file_function($files['tmp_name'], $arm_upload_file_path);
						$response = $upload_url . $file_name;
						echo "<p class='uploaded'>" . $upload_url.$file_name . "</p>";//phpcs:ignore
					}
				}
			}

	   }

        function arm_import_user() 
	    {
	    	global $ARMember, $arm_capabilities_global;
	    	$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce

	        $upload_dir = MEMBERSHIP_UPLOAD_DIR.'/';
	        $upload_url = MEMBERSHIP_UPLOAD_URL.'/';

	        $file_name = (isset($_SERVER['HTTP_X_FILENAME']) ? "arm_".arm_generate_random_code()."_".($ARMember->arm_get_basename($_SERVER['HTTP_X_FILENAME'])) : false); //phpcs:ignore
			$file_meta_name = (isset($_SERVER['HTTP_FILEMETANAME']) ? sanitize_text_field($_SERVER['HTTP_FILEMETANAME']) : false); //phpcs:ignore

	        $response = "";
	        $userID = get_current_user_id();
	        if ($file_name && !empty($userID) && $userID != 0) {
				$file_size_new =  !empty($_FILES['armfileselect']['size']) ? intval($_FILES['armfileselect']['size']): '';
	        	$file_size_new = number_format($file_size_new / 1048576, 2, '.', '');
				
	        	add_filter( 'upload_mimes', array($this, 'arm_allow_mime_type'), 1);
				
				$arm_is_valid_file = $this->arm_check_valid_file_ext_data($file_name, $file_size_new, $_FILES['armfileselect'] ); //phpcs:ignore
	        	if( $arm_is_valid_file )
	        	{
		    		$arm_upload_file_path = $upload_dir.$file_name;
	            	$this->arm_upload_file_function($_FILES['armfileselect']['tmp_name'], $arm_upload_file_path); //phpcs:ignore
	                $response = $upload_url . $file_name;
	                echo $response;//phpcs:ignore
	                exit;
	            }
	        }
	        echo $response; //phpcs:ignore
	        exit;
	    }
	    function arm_allow_mime_type($mime_type_array)
	    {
	    	if(is_array($mime_type_array) && !array_key_exists('xml', $mime_type_array))
	    	{
	    		$mime_type_array['xml'] = 'text/xml';
	    	}
	    	return $mime_type_array;
	    }

	    function arm_upload_file_function($source, $destination){
            if( empty( $source ) || empty( $destination ) ){
                return false;
            }

            if( !function_exists('WP_Filesystem' ) ){
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            WP_Filesystem();
            global $wp_filesystem;
            
            $file_content = $wp_filesystem->get_contents( $source );

            $result = $wp_filesystem->put_contents( $destination, $file_content, 0777 );

            return $result;
        }

	    function arm_check_for_invalid_data( $file_content = '', $arm_file_array = array() ){
	    	$arm_valid_pattern_with_short_tag = '/(\<\?(php)|\<\?\=)|(\<(script|iframe))/';
			$arm_valid_pattern_without_short_tag = '/(\<\?(php))|(\<(script|iframe))/';

			$is_short_tag_enabled = ini_get( 'short_open_tag' ); //phpcs:ignore

			$arm_valid_pattern_script = '/(\<(script))/';
			$arm_valid_pattern_iframe = '/(\<(iframe))/';
			$arm_valid_pattern_array = array( 'onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel', 'javascript' );
			
			$arm_valid_pattern_val_flag = true;
			foreach($arm_valid_pattern_array as $arm_invalid_keyword)
			{
				if( false !== stripos( $file_content, $arm_invalid_keyword ) )
				{
					$arm_valid_pattern_val_flag = false;
					break;
				}
			}

			$arm_wp_filetype_check   = wp_check_filetype_and_ext( $arm_file_array['tmp_name'], $arm_file_array['name'] );

			$ext             = empty( $arm_wp_filetype_check['ext'] ) ? '' : $arm_wp_filetype_check['ext'];
			$type            = empty( $arm_wp_filetype_check['type'] ) ? '' : $arm_wp_filetype_check['type'];
			//$proper_filename = empty( $arm_wp_filetype_check['proper_filename'] ) ? '' : $arm_wp_filetype_check['proper_filename'];

			if ( ( (1 == $is_short_tag_enabled || true == $is_short_tag_enabled) && preg_match( $arm_valid_pattern_with_short_tag, $file_content ) ) || ( (1 != $is_short_tag_enabled || false == $is_short_tag_enabled) && preg_match( $arm_valid_pattern_without_short_tag, $file_content ) ) || $arm_valid_pattern_val_flag==false || ( ( ! $type || ! $ext ) && !current_user_can( 'unfiltered_upload' ) ) )
			{
				return false;
			}

	        return true;
	    }

	    function arm_check_valid_file_ext_data($file_name, $file_size, $arm_files_arr)
        {
        	$is_valid_file = 0;
        	if ($file_name && $file_size <= 20 )
	        {
	        	$arm_allowed_mimes = $this->arm_allowed_wp_mime_types();
        		$denyExts = array("php", "php2", "php3", "php4", "php5", "pl", "py", "jsp", "asp", "exe", "cgi", "css", "js", "html", "htm", 'htaccess' );

	        	$checkext = explode(".", $file_name);
	            $ext = strtolower( $checkext[count($checkext) - 1] );
	            
	            $actual_file_name = $arm_files_arr['name'];
	            $actual_checkext = explode(".", $actual_file_name);
	            $actual_ext = strtolower( $actual_checkext[count($actual_checkext) - 1] );

	            if (!in_array($ext, $denyExts) && in_array($ext,$arm_allowed_mimes) && !in_array($actual_ext, $denyExts) && in_array($actual_ext,$arm_allowed_mimes)) 
	            {
	            	if( !function_exists('WP_Filesystem' ) )
	            	{
		                require_once(ABSPATH . 'wp-admin/includes/file.php');
		            }
	            	WP_Filesystem();
		            global $wp_filesystem;
		            $file_content = $wp_filesystem->get_contents($arm_files_arr['tmp_name']);

            		$valid_data = $this->arm_check_for_invalid_data( $file_content, $arm_files_arr );

            		if( ! $valid_data ){
            			echo "<p class='error_upload_size'>" . esc_html__('The file could not be uploaded due to security reason as it contains malicious code', 'ARMember'). "</p>";
            			header('HTTP/1.0 401 Unauthorized');
        				die;
            		}
            		else {
            			$is_valid_file = 1;
            		}
	            }
	        }
	        else {
	        	echo "<p class='error_upload_size'>" . esc_html__('This file could not be processed due file limit exceeded.', 'ARMember'). "</p>";
        		die;
	        }
	        return $is_valid_file;
        }

	function session_for_file_handle($file_key,$file_name,$is_multiple=1){
		
		global $ARMember;
		$ARMember->arm_session_start();
		
		if(!isset($_SESSION['arm_file_upload_arr'])){
			$_SESSION['arm_file_upload_arr'] = array();
		}
		if(!empty($file_key)){
			if(!isset($_SESSION['arm_file_upload_arr'][$file_key])){
				if($is_multiple!=0){
					$_SESSION['arm_file_upload_arr'][$file_key] = array();
				}else{
					$_SESSION['arm_file_upload_arr'][$file_key] = '-';
				}
			}
			if(!empty($file_name)){
				if (!($_SESSION['arm_file_upload_arr'][$file_key]==$file_name || (is_array($_SESSION['arm_file_upload_arr'][$file_key]) && in_array($file_name,$_SESSION['arm_file_upload_arr'][$file_key])))){ //phpcs:ignore
					if(is_array($_SESSION['arm_file_upload_arr'][$file_key])){
						$_SESSION['arm_file_upload_arr'][$file_key][] = $file_name;
					}else{
						$_SESSION['arm_file_upload_arr'][$file_key] = $file_name;
					}
				}
			}
		}

	}

    }
}
global $arm_members_activity;
$arm_members_activity = new ARM_members_activity();