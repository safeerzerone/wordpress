<?php
if (!class_exists('ARM_members_badges'))
{
	class ARM_members_badges
	{
		var $badges;
		var $arm_badges_dir;
		var $arm_badges_url;
		protected $arm_slugs;
		function __construct() {
			global $wpdb, $ARMember, $arm_slugs;
                        $this->arm_badges_dir = MEMBERSHIP_UPLOAD_DIR . '/social_badges/';
                        $this->arm_badges_url = MEMBERSHIP_UPLOAD_URL . '/social_badges/';
			$this->arm_slugs = $arm_slugs;			
			add_action('wp_ajax_arm_badges_operation', array($this, 'arm_badges_operation'));
			add_action('wp_ajax_arm_edit_badges_data', array($this, 'arm_edit_badges_data'));
			add_action('wp_ajax_arm_delete_single_badges', array($this, 'arm_delete_single_badges'));
			add_action('wp_ajax_arm_delete_single_achievements', array($this, 'arm_delete_single_achievements'));
			add_action('wp_ajax_arm_save_achievements', array($this, 'arm_save_achievements_func'));
			add_action('wp_ajax_arm_edit_achievements_data', array($this, 'arm_edit_achievements_data'));
			add_action('wp_ajax_arm_delete_single_user_achievements', array($this, 'arm_delete_single_user_achievements'));
			add_action('wp_ajax_arm_badge_achievements_list', array($this, 'arm_badge_achievements_list'));
			add_action('wp_ajax_arm_add_user_badges', array($this, 'arm_add_user_badges_func'));
            add_action('arm_member_update_meta', array($this, 'arm_member_update_meta_achievements'), 100, 3);
			/*add_action('save_post', array($this, 'arm_save_user_post_achieve'), 22, 3);
			add_action('delete_post', array($this, 'arm_delete_user_post_achieve'), 22);
			add_action('comment_post', array($this, 'arm_save_user_comment_achieve'), 10, 2);
			add_action('delete_comment', array($this, 'arm_delete_user_comment_achieve'), 10, 1);*/
            add_filter('upload_mimes', array($this, 'arm_upload_mimes'), 1, 1);
            add_action('wp_ajax_get_user_achievements',array($this,'arm_get_user_achievements_grid_data'));

            add_action('wp_ajax_get_user_badges',array($this,'arm_get_user_badges_grid_data'));

            add_action('wp_ajax_get_achievements',array($this,'arm_get_achievements_grid_data'));
            
			/* Email template slug */
			$this->badges = new stdClass;
			$this->badges->achievement = 'achievement';
			$this->badges->author = 'author';
			$this->badges->award = 'award';
			$this->badges->comments = 'comments';
			$this->badges->diamond = 'diamond';
			$this->badges->favourite = 'favourite';
			$this->badges->like = 'like';
			$this->badges->most_active = 'most_active';
			$this->badges->star_rated = 'star_rated';
			$this->badges->trending = 'trending';
		}
		function arm_default_badges()
		{
			$badges_slugs = $this->badges;
			$arm_badges = array(
				$badges_slugs->achievement => array(
					'arm_badge_name' => 'Achievement',
					'arm_badge_icon' => 'achievement.svg',
				),
				$badges_slugs->author => array(
					'arm_badge_name' => 'Author',
					'arm_badge_icon' => 'author.svg',
				),
				$badges_slugs->award => array(
					'arm_badge_name' => 'Award',
					'arm_badge_icon' => 'award.svg',
				),
				$badges_slugs->comments => array(
					'arm_badge_name' => 'Comments',
					'arm_badge_icon' => 'comments.svg',
				),
				$badges_slugs->diamond => array(
					'arm_badge_name' => 'Diamond',
					'arm_badge_icon' => 'diamond.svg',
				),
				$badges_slugs->favourite => array(
					'arm_badge_name' => 'Favourite',
					'arm_badge_icon' => 'favourite.svg',
				),
				$badges_slugs->like => array(
					'arm_badge_name' => 'Like',
					'arm_badge_icon' => 'like.svg',
				),
				$badges_slugs->most_active => array(
					'arm_badge_name' => 'Most Active',
					'arm_badge_icon' => 'most_active.svg',
				),
				$badges_slugs->star_rated => array(
					'arm_badge_name' => 'Star Rated',
					'arm_badge_icon' => 'star_rated.svg',
				),
				$badges_slugs->trending => array(
					'arm_badge_name' => 'Trending',
					'arm_badge_icon' => 'trending.svg',
				),
                
			);
			$arm_badges = apply_filters('arm_default_badges', $arm_badges);
			return $arm_badges;
		}
        /**
         * Added 'svg' extension to allow upload SVG files.
         */
        function arm_upload_mimes($mime_types)
        {
            $mime_types['svg'] = 'image/svg+xml';
            return $mime_types;
        }
        function arm_insert_default_badges()
		{
			global $wpdb, $ARMember;
            $oldBadges = $this->arm_get_all_badges();
            if (!empty($oldBadges)) {
                return false;
            }
            $default_badges = $this->arm_default_badges();
            if (empty($default_badges)) {
                return false;
            }
            if (count($default_badges) > 1) {
                $icon_upload_dir = MEMBERSHIP_UPLOAD_DIR . '/social_badges/';
                $icon_upload_url = MEMBERSHIP_UPLOAD_URL . '/social_badges/';
                if (!is_dir($icon_upload_dir)) {
                    wp_mkdir_p($icon_upload_dir);
                }
                foreach ($default_badges as $key => $badges) {
                    $badges_name = $badges['arm_badge_name'];
                    $file_extension = explode('.',$badges['arm_badge_icon']);
                    $file_ext = $file_extension[count($file_extension) - 1];
                    $new_file_name = 'arm_badges_' . wp_generate_password(15, false) . '.' . $file_ext;
                    $old_file = MEMBERSHIP_DIR . "/images/social_badges/" . $badges['arm_badge_icon'];
                    $new_file = $icon_upload_dir . $new_file_name;
                    $file = @copy($old_file, $new_file);
                    if (TRUE === $file) {
                        $badges_data = array(
                            'arm_badges_parent' => 0,
                            'arm_badges_type' => 'badge',
                            'arm_badges_name' => $badges_name,
                            'arm_badges_icon' => $new_file_name,
                        );
                        $ins = $wpdb->insert($ARMember->tbl_arm_badges_achievements, $badges_data);
                    }
                }
            }
        }
		function arm_get_all_badges()
		{
			global $wpdb, $ARMember;
            $sql = $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_type` = %s ORDER BY `arm_badges_id` DESC",'badge'); //phpcs:ignore --Reason $ARMember->tbl_arm_badges_achievements is a table name
            $results = $wpdb->get_results($sql); //phpcs:ignore --Reason $sql is a prepareed query
            if (!empty($results)) {
                $badge_data = array();
                foreach ($results as $badge) {
                    $badgeID = $badge->arm_badges_id;
                    $badge->arm_badges_achievement = maybe_unserialize($badge->arm_badges_achievement);
                    $badge_data[$badgeID] = $badge;
                    if(filter_var($badge->arm_badges_icon, FILTER_VALIDATE_URL))
                    {
                        $badge_data[$badgeID]->arm_badges_icon = $badge->arm_badges_icon;                        
                    }
                    else{
                        $badge_data[$badgeID]->arm_badges_icon = $this->arm_badges_url.$badge->arm_badges_icon;
                    }
                }
                return $badge_data;
            }
            return false;
		}
                
                function arm_get_single_badge_from_array($badge_array = array()){
                    global $wp, $wpdb, $ARMember, $arm_global_settings;
			if (!empty($badge_array)) {
                            $badge_data_array = array();
                /* Query Monitor Change */
                $badgeArrayQuery = implode(',',$badge_array);
                if(!empty($badgeArrayQuery))
                {
                    $badgeArrayQuery = str_replace(',,', ',', $badgeArrayQuery);
                }
                if( isset($GLOBALS['arm_single_badge_data']) && isset($GLOBALS['arm_single_badge_data'][$badgeArrayQuery]) ){
                    $badge_data = $GLOBALS['arm_single_badge_data'][$badgeArrayQuery];
                } else {
                    $super_admin_placeholders = ' WHERE `arm_badges_id` IN (';
                    $super_admin_placeholders .= rtrim( str_repeat( '%s,', count( $badge_array ) ), ',' );
                    $super_admin_placeholders .= ')';
                    array_unshift( $badge_array, $super_admin_placeholders );
                    $user_where = call_user_func_array(array( $wpdb, 'prepare' ), $badge_array );
                    $badge_data = $wpdb->get_results("SELECT * FROM `" . $ARMember->tbl_arm_badges_achievements . "`  ".$user_where, ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_badges_achievements is a table name
                    if( !isset($GLOBALS['arm_single_badge_data']) ){
                        $GLOBALS['arm_single_badge_data'] = array();
                    }
                    $GLOBALS['arm_single_badge_data'][$badgeArrayQuery] = $badge_data;
                }
                /* Query Monitor Change */
                                
                if (!empty($badge_data)) {
                    
                    foreach($badge_data as $badge_arr){
                        $arm_badges_id = $badge_arr['arm_badges_id'];
                                
                            if (!empty($badge_arr['arm_badges_parent']) && $badge_arr['arm_badges_parent'] != 0) {
                                $parentBadge = $wpdb->get_row( $wpdb->prepare("SELECT `arm_badges_name`, `arm_badges_icon` FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_id`=%d" , $badge_data->arm_badges_parent), ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_badges_achievements is a table name
                                if (!empty($parentBadge)) {
                                    $badge_arr['arm_badges_name'] = $parentBadge['arm_badges_name'];
                                    $badge_arr['arm_badges_icon'] = $this->arm_badges_url.$parentBadge['arm_badges_icon'];
                                }
                            } else {
                                $badge_arr['arm_badges_icon'] = $this->arm_badges_url.$badge_arr['arm_badges_icon'];
                            }
                            $badge_arr['arm_badges_achievement'] = maybe_unserialize($badge_arr['arm_badges_achievement']);
                            
                            $badge_data_array[$arm_badges_id] = $badge_arr;
                    }
                                    
                                    
				}
				return $badge_data_array;
                        }
                    
                }
                
		function arm_get_single_badge($badge_id = 0)
		{
			global $wp, $wpdb, $ARMember, $arm_global_settings;
			if (is_numeric($badge_id) && $badge_id != 0) {
				$badge_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_id`=%d" , $badge_id), ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_badges_achievements is a table name
				if (!empty($badge_data)) {
                                    if (!empty($badge_data['arm_badges_parent']) && $badge_data['arm_badges_parent'] != 0) {
                                        $parentBadge = $wpdb->get_row( $wpdb->prepare("SELECT `arm_badges_name`, `arm_badges_icon` FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_id`=%d" , $badge_data['arm_badges_parent']), ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_badges_achievements is a table name
                                        if (!empty($parentBadge)) {
                                            $badge_data['arm_badges_name'] = $parentBadge['arm_badges_name'];
                                            $badge_data['arm_badges_icon'] = $this->arm_badges_url.$parentBadge['arm_badges_icon'];
                                        }
                                    } else {
                                        $badge_data['arm_badges_icon'] = $this->arm_badges_url.$badge_data['arm_badges_icon'];
                                    }
                                    $badge_data['arm_badges_achievement'] = maybe_unserialize($badge_data['arm_badges_achievement']);
				}
				return $badge_data;
			}
			return false;
		}
        function arm_get_all_achievements_count_by_badge($arm_badges_id=0)
        {
            global $wpdb, $ARMember;
            $achievements_count = 0;
            $totalAchievements = $wpdb->get_results( $wpdb->prepare("SELECT `arm_badges_parent`, `arm_badges_achievement`, `arm_badges_achievement_type` FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_type`=%s ",'achievement'), ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_badges_achievements is a table name
            if(!empty($totalAchievements))
            {
                foreach($totalAchievements as $totalAchievements_val)
                {
                    if(!empty($totalAchievements_val['arm_badges_achievement_type']))
                    {
                        if($totalAchievements_val['arm_badges_achievement_type']=='require')
                        {
                            $arm_badges_achievement = maybe_unserialize($totalAchievements_val['arm_badges_achievement']);
                            if(!empty($arm_badges_achievement['arm_achieve_badge_id']))
                            {
                                foreach ($arm_badges_achievement['arm_achieve_badge_id'] as $key => $value) 
                                {
                                    if($arm_badges_id==$value)
                                    {
                                        $achievements_count++;
                                    }
                                }
                            }
                        }
                        else
                        {
                            if($arm_badges_id==$totalAchievements_val['arm_badges_parent'])
                            {
                                $achievements_count++;
                            }
                        }
                    }
                }
            }
            return $achievements_count;
        }
        function arm_get_count_achievements_by_badge($badge_id = 0)
		{
            global $wpdb, $ARMember;
            $totalAchievements = 0;
            if (!empty($badge_id) && $badge_id != 0) {
                $totalAchievements = $wpdb->get_var( $wpdb->prepare("SELECT count(`arm_badges_id`) as total FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_type`=%s AND `arm_badges_parent`=%d",'achievement',$badge_id)); //phpcs:ignore --Reason $ARMember->tbl_arm_badges_achievements is a table name
            }
            return $totalAchievements;
        }
        function arm_get_all_achievements_by_badge($badge_id = 0)
		{
			global $wpdb, $ARMember;
            $allAchievements = array();
            if (!empty($badge_id) && $badge_id != 0) {
                $result = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_type`=%s ORDER BY `arm_badges_id` DESC",'achievement'), ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_badges_achievements is a table name
                if (!empty($result)) {
                    foreach ($result as $badge) {
                        if(!empty($badge['arm_badges_achievement_type']))
                        {
                            if($badge['arm_badges_achievement_type']=='require')
                            {
                                $arm_badges_achievement = maybe_unserialize($badge['arm_badges_achievement']);
                                if(!empty($arm_badges_achievement['arm_achieve_badge_id']))
                                {
                                    foreach ($arm_badges_achievement['arm_achieve_badge_id'] as $key => $value) 
                                    {
                                        if($badge_id==$value)
                                        {
                                            $badgeID = $badge['arm_badges_id'];
                                            $badgeParent = $badge['arm_badges_parent'];
                                            $badge['arm_badges_achievement'] = maybe_unserialize($badge['arm_badges_achievement']);
                                            $allAchievements[$badgeID] = $badge;
                                        }
                                    }
                                }
                            }
                            else
                            {
                                if($badge_id==$badge['arm_badges_parent'])
                                {
                                    $badgeID = $badge['arm_badges_id'];
                                    $badgeParent = $badge['arm_badges_parent'];
                                    $badge['arm_badges_achievement'] = maybe_unserialize($badge['arm_badges_achievement']);
                                    $allAchievements[$badgeID] = $badge;
                                }
                            }
                        }
                    }
                }
            }
            return $allAchievements;
		}
        function arm_get_all_achievements()
		{
			global $wpdb, $ARMember;
                        $badges_and_achievement_array = array();
            $allAchievements = array();
            $allBadges = $this->arm_get_all_badges();
            $result = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_type`=%s ORDER BY `arm_badges_id` DESC",'achievement'), ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_badges_achievements is a table name
            if (!empty($result)) {
                foreach ($result as $badge) {
                    $badgeID = $badge['arm_badges_id'];
                    $badgeParent = $badge['arm_badges_parent'];
                    $parentBadge = isset($allBadges[$badgeParent]) ? $allBadges[$badgeParent] : array();
                    $badge['arm_badges_name'] = isset($parentBadge->arm_badges_name) ? $parentBadge->arm_badges_name : '';
                    $badge['arm_badges_icon'] = isset($parentBadge->arm_badges_icon) ? $parentBadge->arm_badges_icon : '';
                    $badge['arm_badges_achievement'] = maybe_unserialize($badge['arm_badges_achievement']);
                    $allAchievements[$badgeID] = $badge;
                }
            }
            
            $badges_and_achievement_array['badges'] = $allBadges;
            $badges_and_achievement_array['achievements'] = $allAchievements;
            
            return $badges_and_achievement_array;
		}
        function arm_get_achievement_types()
        {
            global $wp, $wpdb, $ARMember, $arm_global_settings;
            $achieve_types = array(
                'post' => esc_html__('Posts', 'ARMember'),
                'page' => esc_html__('Pages', 'ARMember'),
                'comments' => esc_html__('Comments', 'ARMember'),
                'days' => esc_html__('Days (since registration)', 'ARMember'),
            );
            $custom_post_types = get_post_types(array('public' => true, '_builtin' => false, 'show_ui' => true), 'objects');
            if (!empty($custom_post_types)) {
                foreach ($custom_post_types as $cpt) {
                    $achieve_types[$cpt->name] = $cpt->label;
                }
            }
            return $achieve_types;
        }
		function arm_badges_operation()
		{
			global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_badges'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $ARMember->arm_session_start();
            $status = 'error';
            $message = esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember');
			$response = array('type' => 'error', 'msg' => esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember'));
			$op_type = sanitize_text_field($_REQUEST['op_type']); //phpcs:ignore
            $badges_name = isset($_POST['arm_badges_name']) ? sanitize_text_field($_POST['arm_badges_name']) : ''; //phpcs:ignore
			$badges_icon = isset($_POST['arm_badges_icon']) ? sanitize_text_field($_POST['arm_badges_icon']) : ''; //phpcs:ignore
			$badges_data = array(
                'arm_badges_parent' => 0,
                'arm_badges_type' => 'badge',
                'arm_badges_name' => $badges_name,
				'arm_badges_icon' => $badges_icon,
			);
            // sesion handling
            if(!empty($_SESSION['arm_file_upload_arr'])){
                foreach ($_SESSION['arm_file_upload_arr'] as $upload_key => $upload_arr) {
                    if(isset($badges_data[$upload_key])){
                        $base_name = $ARMember->arm_get_basename($badges_data[$upload_key]);
                        if(!empty($upload_arr) && (is_string($upload_arr) && $upload_arr!=$base_name) || (is_array($upload_arr) && !in_array($base_name,$upload_arr))){
                            unset($badges_data[$upload_key]);
                        }
                    }
                }
            }
           
			if ($op_type == 'add') {
					$ins = $wpdb->insert($ARMember->tbl_arm_badges_achievements, $badges_data);
				if ($ins) {
					$message = esc_html__('Badge Added Successfully.', 'ARMember');
					$status = 'success';
				} else {
					$message = esc_html__('Error Adding Message, Please Try Again.', 'ARMember');
					$status = 'failed';
				}
			} else {
				$badge_id = intval($_REQUEST['edit_id']);//phpcs:ignore
				$where = array('arm_badges_id' => $badge_id);
				$up_badge = $wpdb->update($ARMember->tbl_arm_badges_achievements, $badges_data, $where);
				$message = esc_html__('Badge Updated Successfully', 'ARMember');
				$status = 'success';
			}
			$response = array('status' => $status, 'message' => $message);
                        

			echo arm_pattern_json_encode($response);
			die();
		}
		function arm_edit_badges_data()
		{
			global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_global_settings, $arm_email_settings, $arm_manage_coupons, $arm_manage_communication, $arm_capabilities_global; 
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_badges'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $ARMember->arm_session_start();
			$return = array('status' => 'error');
			if (isset($_REQUEST['action']) && isset($_REQUEST['badge_id']) && $_REQUEST['badge_id'] != '') {
				$bid = intval($_REQUEST['badge_id']);
				$result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_id`= %d" , $bid)); //phpcs:ignore --Reason $ARMember->tbl_arm_badges_achievements is a table name
				$global_settings = $arm_global_settings->global_settings;
				$badge_width = !empty($global_settings['badge_width']) ? $global_settings['badge_width'] : 30;
				$badge_height = !empty($global_settings['badge_height']) ? $global_settings['badge_height'] : 30;
				$badge_css = "width:".$badge_width."px; height:".$badge_height."px;margin-left:8px;";               
				$badges_icon = (!empty($result->arm_badges_icon)) ? $this->arm_badges_url.$result->arm_badges_icon : '';
                if(!empty($badges_icon) && !file_exists($badges_icon))
                {
                    $badges_icon = basename($result->arm_badges_icon);
                }               
                $badge_icon_file_name = basename($badges_icon);
                $file_ids = explode('.',$badge_icon_file_name);
                $file_id = $file_ids[0];
				$display_file = !empty($badges_icon) ? true : false;				
				$badge_html = '';
				$badge_html .= '<div class="armFileUploadWrapper" data-iframe="arm_badges_icon">';
					$browser_info = $ARMember->getBrowser($_SERVER['HTTP_USER_AGENT']); // phpcs:ignore
					$inputType = "type=file";
					$inputclass = '';
					if ($browser_info['name'] == 'Internet Explorer' and $browser_info['version'] <= '9') {
						$inputType = 'type="text" data-iframe="arm_badges_icon"';
						$inputclass = ' armIEFileUpload';
						$badge_html .= '<div id="arm_badges_icon_iframe_div" class="arm_iframe_wrapper" style="display:none;"><iframe id="arm_badges_icon_iframe" src="'.MEMBERSHIP_VIEWS_URL.'/iframeupload.php"></iframe></div>';
					}
                    $badge_html .= '<div class="armNormalFileUpload">';
                        $badge_html .= '<div class="arm-ffw__file-upload-box">';                       
                            $badge_html .= '<div class="arm_old_file arm_field_file_display">';
                            if ($display_file) {
                                $badge_html .= '<div class="arm_uploaded_file_info" id="'.$file_id.'">';
                                
                                $badge_html .= '<img alt="" class="arm_edit_badges_icon" src="' . (MEMBERSHIP_UPLOAD_URL.'/social_badges/'.basename($badges_icon)) . '" style="'.$badge_css.'" />';
                                $badge_html .= '<label>'.$badge_icon_file_name.'</label>';
                                $badge_html .= '<div class="armFileRemoveContainer" id="'.$file_id.'" style="'.(($display_file) ? 'display:inline-block;': '').'"><img src="'.MEMBERSHIPLITE_IMAGES_URL.'/delete.svg" onmouseover="this.src=\''.MEMBERSHIPLITE_IMAGES_URL.'/delete_hover.svg\'" onmouseout="this.src=\''.MEMBERSHIPLITE_IMAGES_URL.'/delete.svg\'"/></div>';
                                $badge_html .= '</div>';
                            }
                            $badge_html .= '</div>';
                            $badge_html .= '<div class="armbar" style="width:0%;"></div>';
                            $badge_html .= '<label class="armFileDragAreaText" for="arm_badges_icon" style="'.(($display_file) ? 'display:none;': '').'">';
                                $file_disabled = ($display_file) ? "disabled='disabled'": '';
                                $badge_html .= '<div class="armFileUploaderWrapper armFileUploaderPlaceholder" id="armFileUploaderWrapper_arm_badges_icon" data-id="arm_badges_icon" data-file_type="badges">'.esc_html__('Drop file here or click to select.','ARMember').'</div>';
                            $badge_html .= '</label>';
                            $badge_html .= '<input armfileuploader id="arm_badges_icon" accept=".jpg,.jpeg,.png,.bmp,.ico" class="arm-df__file-upload-control" type="file"  data-file_type="badges" '.$file_disabled.'/>';
                            $badge_html .= '<input class="arm_file_url" type="hidden" name="arm_badges_icon" value="'.$badges_icon.'" data-file_type="badges" required="required" data-msg-required="'.esc_html__('Badge icon cannot be empty.','ARMember').'" data-msg-invalid="' . esc_html__('Invalid Badge icon.','ARMember').'"></input>';
                        $badge_html .= '</div">';
                    $badge_html .= '</div">';
				$badge_html .= '</div>';
				$badge_html .= '<script type="text/javascript" src="'.MEMBERSHIP_URL . '/js/arm_admin_file_upload_js.js"></script>';
                $arm_badges_name = !empty($result->arm_badges_name) ? stripslashes_deep($result->arm_badges_name) : '';
				$return = array(
					'status' => 'success',
					'id' => $bid,
					'popup_heading' => $arm_badges_name,					
					'arm_badges_name' => $arm_badges_name,
					'arm_edit_badge_file_container' => $badge_html,
				);
			}
			echo arm_pattern_json_encode($return);
			exit;
		}
		function arm_delete_single_badges()
		{
			global $wpdb, $ARMember, $arm_slugs, $arm_subscription_plans, $arm_global_settings, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_badges'], '1',1); //phpcs:ignore --Reason verifying nonce
			$action = sanitize_text_field($_POST['act']); //phpcs:ignore
			$id = intval($_POST['id']);//phpcs:ignore
                        if ($action == 'delete') {
                            if (empty($id)) {
                                $errors[] = esc_html__('Invalid action.', 'ARMember');
                            } else {
                                if (!current_user_can('arm_badges')) {
                                    $errors[] = esc_html__('Sorry, You do not have permission to perform this action.', 'ARMember');
                                } else {
                                    $wpdb->delete($ARMember->tbl_arm_badges_achievements, array('arm_badges_parent' => $id));
                                    $res_var = $wpdb->delete($ARMember->tbl_arm_badges_achievements, array('arm_badges_id' => $id));
                                    if ($res_var) {
                                        $this->arm_delete_single_badge_user_data($id);
                                        $message = esc_html__('Badge has been deleted successfully.', 'ARMember');
                                    }
                                }
                            }
                        }
                        $return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
			echo arm_pattern_json_encode($return_array);
			exit;
		}
		function arm_save_achievements_func()
		{
			global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_badges'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $user_achievement_arr = array();
            $status = 'error';
            $message = esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember');
            $response = array('status' => 'error', 'message' => esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember'));
            $badge_id = isset($_POST['arm_badges_id']) ? intval($_POST['arm_badges_id']) : 0;//phpcs:ignore
            $arm_require_achive_badges_id = isset($_POST['arm_require_achive_badges_id']) ? $_POST['arm_require_achive_badges_id'] : array(); //phpcs:ignore
            
            $edit_badge_id = isset($_POST['edit_badge_id']) ? intval($_POST['edit_badge_id']) : 0;//phpcs:ignore
            $action = isset($_POST['b_action']) ? sanitize_text_field($_POST['b_action']) : '';//phpcs:ignore
            $badge_id = (!empty($badge_id)) ? $badge_id : $arm_require_achive_badges_id[0];
            $badgeData = $this->arm_get_single_badge($badge_id);
            if (!empty($badgeData) && !empty($action)) {
                $achievement_type = isset($_POST['arm_achievement_type']) ? sanitize_text_field($_POST['arm_achievement_type']) : '';//phpcs:ignore
                $arm_badges_tooltip = !empty($_POST['arm_badges_tooltip']) ? sanitize_text_field($_POST['arm_badges_tooltip']) : '';//phpcs:ignore
                $arm_require_badges_tootip = !empty($_POST['arm_require_badges_tootip']) ? map_deep($_POST['arm_require_badges_tootip'], 'sanitize_text_field') : array();
                $achieve_num = isset($_POST['arm_achieve_num']) ? $_POST['arm_achieve_num'] : array(); //phpcs:ignore
                $achievement_options = isset($_POST['arm_achievement_options']) ? $_POST['arm_achievement_options'] : array(); //phpcs:ignore
                $achieveOptions = isset($achievement_options[$achievement_type]) ? $achievement_options[$achievement_type] : '';
                $newBadgeData = array(
                    'arm_achieve' => $achieveOptions,
                    'arm_achieve_num' => $achieve_num,
                    'arm_achieve_badge_id' => $arm_require_achive_badges_id,
                    'arm_achieve_badges_tooltip' => $arm_require_badges_tootip
                );
                $achievement_data = array(
                    'arm_badges_parent' => $badge_id,
                    'arm_badges_type' => 'achievement',
                    'arm_badges_achievement' => maybe_serialize($newBadgeData),
                    'arm_badges_achievement_type' => $achievement_type,
                    'arm_badges_tooltip' => $arm_badges_tooltip
                );
                if ($action == 'update') {
                    $oldBadgeData = $this->arm_get_single_badge($edit_badge_id);
                    $oldAchievementOpt = maybe_unserialize($oldBadgeData['arm_badges_achievement']);
                    $up_badge = $wpdb->update($ARMember->tbl_arm_badges_achievements, $achievement_data, array('arm_badges_id' => $edit_badge_id));
                        $status = 'success';
                    $message = esc_html__('Achievement Updated Successfully.', 'ARMember');
                    $response = array('status' => 'success', 'message' => $message);
                    $user_achievement_arr = array(
                        'arm_achieve_action' => 'update_achieve',
                        'badge_id' => $edit_badge_id,
                        'parent_badge_id' => $badge_id,
                        'arm_achievement_type' => $achievement_type,
                        'arm_achieve' => $achieveOptions,
                        'arm_achieve_num' => $achieve_num,
                        'arm_achieve_badge_id' => $arm_require_achive_badges_id,
                        'arm_achieve_badges_tooltip' => $arm_require_badges_tootip,
                        'arm_old_achievement_type' => $oldBadgeData['arm_badges_achievement_type'],
                        'arm_old_achieve' => $oldAchievementOpt['arm_achieve'],
                        'arm_old_achieve_num' => $oldAchievementOpt['arm_achieve_num'],
                        'arm_old_achieve_badge_id' => $oldAchievementOpt['arm_achieve_badge_id'],
                        'arm_old_achieve_badges_tooltip' => $oldAchievementOpt['arm_achieve_badges_tooltip']
                    );
                } else {
                    $ins = $wpdb->insert($ARMember->tbl_arm_badges_achievements, $achievement_data);
                    $edit_badge_id = $wpdb->insert_id;
                    if ($ins) {
                        $status = 'success';
                        $message = esc_html__('Achievement Added Successfully.', 'ARMember');
                        }
                    else
                    {
                        $status = 'error';
                        $message = esc_html__('Error While Adding Achievement, Please Try Again.', 'ARMember'); 
                    }
                    $response = array('status' => $status, 'message' =>$message);
                    
                        $user_achievement_arr = array(
                            'arm_achieve_action' => 'add_achieve',
                            'badge_id' => $edit_badge_id,
                            'parent_badge_id' => $badge_id,
                            'arm_achievement_type' => $achievement_type,
                            'arm_achieve' => $achieveOptions,
                            'arm_achieve_num' => $achieve_num,
                            'arm_achieve_badge_id' => $arm_require_achive_badges_id,
                            'arm_achieve_badges_tooltip' => $arm_require_badges_tootip,
                            'arm_old_achievement_type' => '',
                            'arm_old_achieve' => '',
                            'arm_old_achieve_num' => '',
                            'arm_old_achieve_badge_id' => '',
                            'arm_old_achieve_badges_tooltip' => '',
                        );
                    
                }
                if (!empty($user_achievement_arr)) {
                    if ($achievement_type == 'require') {
                        $this->arm_add_user_require_achievement($user_achievement_arr);
                    } else {
                        $this->arm_add_user_other_achievement($user_achievement_arr);
                    }
                }
            }
            echo arm_pattern_json_encode($response);
            die();
        }
        function arm_edit_achievements_data()
        {
            global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_badges'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $global_settings = $arm_global_settings->global_settings;
            $badges_list = $this->arm_get_all_badges();
            $badge_width = !empty($global_settings['badge_width']) ? $global_settings['badge_width'] : 30;
            $badge_height = !empty($global_settings['badge_height']) ? $global_settings['badge_height'] : 30;
            $badge_css = "width:" . $badge_width . "px; height:" . $badge_height . "px;";
            $return = array('status' => 'error');
            if (isset($_REQUEST['action']) && isset($_REQUEST['badge_id']) && $_REQUEST['badge_id'] != '') {
                $bid = intval($_REQUEST['badge_id']);
                $badgeData = $this->arm_get_single_badge($bid);
                if ($badgeData) {
                    $badge_icon = '<img src="' . $badgeData['arm_badges_icon'] . '" alt="" style="'.$badge_css.'" />';
                    $badges_parent = isset($badgeData['arm_badges_parent']) ? $badgeData['arm_badges_parent'] : array();
                    $badgesAchievements = isset($badgeData['arm_badges_achievement']) ? $badgeData['arm_badges_achievement'] : array();
                    $achieveOptions = isset($badgesAchievements['arm_achieve']) ? $badgesAchievements['arm_achieve'] : '';
                    
                    $achieve_num = isset($badgesAchievements['arm_achieve_num']) ? $badgesAchievements['arm_achieve_num'] : array();
                    $achieve_badge_icon_id = isset($badgesAchievements['arm_achieve_badge_id']) ? $badgesAchievements['arm_achieve_badge_id'] : array();

                    $arm_badges_tooltip = isset($badgeData['arm_badges_tooltip']) ? stripslashes_deep($badgeData['arm_badges_tooltip']) : '';
                    $arm_achieve_badges_tooltip = (isset($badgesAchievements['arm_achieve_badges_tooltip'])) ? $badgesAchievements['arm_achieve_badges_tooltip'] : array();
                    
                    $achievement_type = isset($badgeData['arm_badges_achievement_type']) ? $badgeData['arm_badges_achievement_type'] : '';

                    if($achievement_type=='plans' || $achievement_type=='roles')
                    {
                        $achieveOptions_val = array();
                        if(isset($achieveOptions))
                        {
                            $achieveOptions_val = explode(',', $achieveOptions);
                            
                        }
                        $achieveOptions = isset($achieveOptions_val) ? $achieveOptions_val[0] : array();

                        $arm_badges_tooltip = empty($arm_badges_tooltip) ? esc_html__('Achieve this badge for', 'ARMember') . ' ' . $achievement_type : $arm_badges_tooltip;//phpcs:ignore
                        
                    }
                    else if($achievement_type=='admin')
                    {
                        $arm_badges_tooltip = empty($arm_badges_tooltip) ? esc_html__('Achieve this badge by', 'ARMember') . ' ' . $achievement_type : $arm_badges_tooltip;//phpcs:ignore
                    }
                    else
                    {
                        $arm_badges_tooltip = empty($arm_badges_tooltip) ? esc_html__('Achieve this badge for', 'ARMember') . ' ' . $achievement_type :$arm_badges_tooltip;//phpcs:ignore
                    }

                    if(is_array($achieve_num))
                    {
                        $arm_achivement_edit_response = '';
                        $arm_achivement_edit_response .= '<div class="arm_achievement_helptip">
                                                                <span>('. esc_html__('Please add value in numerical order of achievements (Lower Value First)', 'ARMember').')</span>
                                                                </div>';
                        $arm_achievement_cnt = 0;
                        foreach($achieve_num as $achieve_num_value)
                        { 
                            //$achieve_num_value = ($achieve_num_value==0) ? '' : $achieve_num_value;
                            $arm_achivement_edit_response .= '<div class="arm_achievement_has_complete ">';
                            $arm_achivement_edit_response .= '<span>'. esc_html__('User has completed', 'ARMember').'&nbsp;';
                            $arm_achivement_edit_response .= '</span>';
                            $arm_achivement_edit_response .= '<input type="text" id="arm_edit_achieve_num" 
                                                                name="arm_achieve_num[]" class="arm_achieve_num arm_width_50 arm_min_width_50 arm_text_align_center arm_padding_left_0 arm_margin_top_12 arm_margin_left_5" onkeypress="javascript:return isNumber (event);"  value="'.$achieve_num_value.'" data-msg-required="'. esc_attr__("Please enter number", "ARMember").'" >';
                            $arm_achivement_edit_response .= '<input type="hidden" id="arm_require_achive_badges_id_'.$arm_achievement_cnt.'" 
                                                                name="arm_require_achive_badges_id[]" class="arm_achivement_badge_icon" value="'.$achieve_badge_icon_id[$arm_achievement_cnt].'"/>';
                                                                $arm_badges_require_icon = $this->arm_get_single_badge($achieve_badge_icon_id[$arm_achievement_cnt]);
                                                                
                                                                $arm_selected_achievement_badge = '<img src=\'' . esc_attr($arm_badges_require_icon['arm_badges_icon']) . '\' alt=\'\' class=\'arm_badge_icon\' style=\''.$badge_css.'\' />'.stripslashes_deep($arm_badges_require_icon['arm_badges_name']).'';
                            $arm_achivement_edit_response .= '<dl class="arm_selectbox arm_achievement_badge_select arm_badge_select column_level_dd arm_width_250" >
                                                                <dt><span class="arm_no_auto_complete arm_display_flex">'.$arm_selected_achievement_badge.'</span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                                <dd>
                                                                    <ul data-id="arm_require_achive_badges_id_'.$arm_achievement_cnt.'">';
                                                                    
                                                                    if(!empty($badges_list))
                                                                    {
                                                                        foreach ($badges_list as $badge) {
                                                                            /* 
                                                                            if(file_exists(strstr($badge->arm_badges_icon, "//")))
                                                                            {
                                                                                $badge->arm_badges_icon =strstr($badge->arm_badges_icon, "//");
                                                                            }
                                                                            else if(file_exists($badge->arm_badges_icon))
                                                                            {
                                                                                $badge->arm_badges_icon = $badge->arm_badges_icon;
                                                                            }else{
                                                                                $badge->arm_badges_icon = $badge->arm_badges_icon;
                                                                            } 
                                                                            */
                                                                            $arm_achivement_edit_response .= '<li data-value="'.$badge->arm_badges_id.'" class="arm_display_flex"><img src="' . ($badge->arm_badges_icon) . '" alt="" class="arm_badge_icon" align="middle" style="'.$badge_css.'" />'.stripslashes_deep($badge->arm_badges_name).'</li>';
                                                                        }
                                                                    }
                                                                    $arm_achivement_edit_response .= '</ul>
                                                                </dd>
                                                                </dl>';
                                                                if(empty($arm_achieve_badges_tooltip[$arm_achievement_cnt]) && !empty($achieve_num_value))
                                                                {
                                                                    $arm_achieve_badges_tooltip_title = esc_html__('Achieve this badge for', 'ARMember') . " {$achieve_num_value} {$achieveOptions}";
                                                                }
                                                                else
                                                                {
                                                                    $arm_achieve_badges_tooltip_title = esc_html($arm_achieve_badges_tooltip[$arm_achievement_cnt]);//phpcs:ignore
                                                                }
                            $arm_achivement_edit_response .= '<input type="text" id="arm_require_badges_tootip" name="arm_require_badges_tootip[]" class="arm_achivement_badges_tootip arm_min_width_140" placeholder="'. esc_attr__('Tooltip Title', 'ARMember').'" value="'.esc_attr($arm_achieve_badges_tooltip_title).'">';
                            $arm_achivement_edit_response .= '<div class="arm_achievement_helptip_icon arm_margin_left_5">
                                                                        <div class="arm_achievement_plus_icon arm_achieve_edit_plus_icon arm_helptip_icon tipso_style arm_margin_0" title="'. esc_attr__('Add Achievement', 'ARMember').'"  ></div>

                                                                        <div class="arm_achievement_minus_icon arm_achieve_edit_minus_icon arm_helptip_icon tipso_style arm_margin_0" title="'. esc_attr__('Remove Achievement', 'ARMember').'" ></div>
                                                                    </div>';
                            $arm_achivement_edit_response .= '</div>';
                            $arm_achievement_cnt++;
                        }
                        $arm_achivement_edit_response .= '<input type="hidden" id="arm_require_achive_counter" name="arm_require_achive_counter" value="'.esc_attr($arm_achievement_cnt).'">';
                    }
                    else
                    {
                        $arm_achivement_edit_response = '';
                        $arm_achivement_edit_response .= '<div class="arm_achievement_helptip">
                                                                <span>('. esc_html__('Please add value in numerical order of achievements (Lower Value First)', 'ARMember').')</span>
                                                                </div>';
                        $arm_achievement_cnt = 0;
                         
                        //$achieve_num = ($achieve_num==0) ? '' : $achieve_num;
                        $arm_achivement_edit_response .= '<div class="arm_achievement_has_complete ">';
                        $arm_achivement_edit_response .= '<span class="arm_margin_bottom_12" >'. esc_html__('User has completed', 'ARMember').'&nbsp;';
                        $arm_achivement_edit_response .= '</span>';
                        $arm_achivement_edit_response .= '<input type="text" id="arm_edit_achieve_num" 
                                                            name="arm_achieve_num[]" class="arm_achieve_num arm_width_50 arm_min_width_50 arm_text_align_center arm_padding_left_0 arm_margin_top_12 arm_margin_left_5" onkeypress="javascript:return isNumber (event);"  value="'.esc_attr($achieve_num).'" data-msg-required="'. esc_attr__("Please enter number", "ARMember").'" >';
                        $arm_achivement_edit_response .= '<input type="hidden" id="arm_require_achive_badges_id_'.$arm_achievement_cnt.'" 
                                                            name="arm_require_achive_badges_id[]" class="arm_achivement_badge_icon" value="'.esc_attr($badges_parent).'"/>';
                                                            $arm_badges_require_icon = $this->arm_get_single_badge($badges_parent);
                                                            $arm_selected_achievement_badge = '<img src="' . esc_attr($arm_badges_require_icon['arm_badges_icon']) . '" alt="" class="arm_badge_icon" style="'.$badge_css.'" /> '.stripslashes_deep($arm_badges_require_icon['arm_badges_name']).'';
                        $arm_achivement_edit_response .= '<dl class="arm_selectbox arm_achievement_badge_select arm_badge_select column_level_dd arm_width_190 arm_margin_left_12">
                                                            <dt><span class="arm_no_auto_complete arm_display_flex">'.$arm_selected_achievement_badge.'</span><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                            <dd>
                                                                <ul data-id="arm_require_achive_badges_id">';
                                                                
                                                                if(!empty($badges_list))
                                                                {
                                                                    foreach ($badges_list as $badge) 
                                                                    {
                                                                        /*
                                                                        if(file_exists(strstr($badge->arm_badges_icon, "//")))
                                                                        {
                                                                            $badge->arm_badges_icon =strstr($badge->arm_badges_icon, "//");
                                                                        }else if(file_exists($badge->arm_badges_icon)){
                                                                            $badge->arm_badges_icon = $badge->arm_badges_icon;
                                                                        }else{
                                                                            $badge->arm_badges_icon = $badge->arm_badges_icon;
                                                                        }
                                                                        */
                                                                        $arm_achivement_edit_response .= '<li data-value="'.esc_attr($badge->arm_badges_id).'" class="arm_display_flex"><img src="' . esc_attr($badge->arm_badges_icon) . '" alt="" class="arm_badge_icon" align="middle" style="'.$badge_css.'" />'.stripslashes_deep($badge->arm_badges_name).'</li>';
                                                                        
                                                                    }
                                                                }
                                                                $arm_achivement_edit_response .= '</ul>
                                                            </dd>
                                                            </dl>';
                        $arm_achieve_badges_tooltip_title = "";
                        if(empty($arm_achieve_badges_tooltip[$arm_achievement_cnt]) && !empty($achieve_num))
                        {
                            $arm_achieve_badges_tooltip_title = esc_html__('Achieve this badge for', 'ARMember') . " {$achieve_num} {$achieveOptions}";
                        }
                        $arm_achivement_edit_response .= '<input type="text" id="arm_require_badges_tootip" name="arm_require_badges_tootip[]" placeholder="'. esc_attr__('Tooltip Title', 'ARMember').'" class="arm_achivement_badges_tootip arm_min_width_140" value="'.$arm_achieve_badges_tooltip_title.'">';
                        $arm_achivement_edit_response .= '<div class="arm_achievement_helptip_icon">
                                                                    <div class="arm_achievement_plus_icon arm_achieve_edit_plus_icon arm_helptip_icon tipso_style " title="'. esc_attr__('Add Achievement', 'ARMember').'"  ></div>

                                                                    <div class="arm_achievement_minus_icon arm_achieve_edit_minus_icon arm_helptip_icon tipso_style " title="'. esc_attr__('Remove Achievement', 'ARMember').'" ></div>
                                                                </div>';
                        $arm_achivement_edit_response .= '</div>';
                           
                        $arm_achivement_edit_response .= '<input type="hidden" id="arm_require_achive_counter" name="arm_require_achive_counter" value="'.$arm_achievement_cnt.'">';
                    }
                    
                    
                    $return = array(
                        'status' => 'success',
                        'arm_badges_id' => $bid,
                        'arm_badges_parent' => $badges_parent,
                        'arm_badges_icon' => $badge_icon,
                        'arm_edit_achieve' => $achieve_num,
                        'arm_edit_achieve_type' => $achievement_type,
                        'arm_achievement_type' => $achievement_type,
                        'arm_achieve' => $achieveOptions,
                        'arm_achieve_num' => $achieve_num,
                        'arm_badges_tooltip' => $arm_badges_tooltip,
                        'arm_achivement_edit_response' => $arm_achivement_edit_response,
                    );
                }
            }
            echo arm_pattern_json_encode($return);
            exit;
        }
        function arm_delete_single_achievements()
        {
            global $wpdb, $ARMember, $arm_slugs, $arm_subscription_plans, $arm_global_settings, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_badges'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $action = sanitize_text_field($_POST['act']);//phpcs:ignore
            $id = intval($_POST['id']);//phpcs:ignore
            if ($action == 'delete') {
                if (empty($id)) {
                    $errors[] = esc_html__('Invalid action.', 'ARMember');
                } else {
                    if (!current_user_can('arm_badges')) {
                        $errors[] = esc_html__('Sorry, You do not have permission to perform this action.', 'ARMember');
                    } else {
                        $oldBadgeData = $this->arm_get_single_badge($id);
                        $oldAchievementOpt = maybe_unserialize($oldBadgeData['arm_badges_achievement']);
                        $res_var = $wpdb->delete($ARMember->tbl_arm_badges_achievements, array('arm_badges_id' => $id));
                        if ($res_var) {
                            $this->arm_delete_single_badge_user_data($id);
                            $user_achievement_arr = array(
                                'arm_achieve_action' => 'delete_achieve',
                                'badge_id' => $id,
                                'parent_badge_id' => $oldBadgeData['arm_badges_parent'],
                                'arm_achievement_type' => $oldBadgeData['arm_badges_achievement_type'],
                                'arm_achieve' => $oldAchievementOpt['arm_achieve'],
                                'arm_achieve_num' => $oldAchievementOpt['arm_achieve_num'],
                                'arm_achieve_badge_id' => $oldAchievementOpt['arm_achieve_badge_id'],
                                'arm_badges_tooltip' => $oldBadgeData['arm_badges_tooltip'],
                                'arm_achieve_badges_tooltip' => $oldAchievementOpt['arm_achieve_badges_tooltip'],
                                'arm_old_achievement_type' => $oldBadgeData['arm_badges_achievement_type'],
                                'arm_old_achieve' => $oldAchievementOpt['arm_achieve'],
                                'arm_old_achieve_num' => $oldAchievementOpt['arm_achieve_num'],
                                'arm_old_achieve_badge_id' => $oldAchievementOpt['arm_achieve_badge_id'],
                                'arm_old_badges_tooltip' => $oldBadgeData['arm_badges_tooltip'],
                                'arm_old_achieve_badges_tooltip' => $oldAchievementOpt['arm_achieve_badges_tooltip'],
                            );
                            if ($oldBadgeData['arm_badges_achievement_type'] == 'require') {
                                $this->arm_add_user_require_achievement($user_achievement_arr);
                            } else {
                                $this->arm_add_user_other_achievement($user_achievement_arr);
                            }
                            $message = esc_html__('Achievement has been deleted successfully.', 'ARMember');
                        }
                    }
                }
            }
            $return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
            echo arm_pattern_json_encode($return_array);
            exit;
        }
        function arm_delete_single_achievements_old()
        {
            global $wpdb, $ARMember;
            $badge_id = intval($_REQUEST['badge_id']);//phpcs:ignore
            $achieve_num = intval($_REQUEST['achieve']);//phpcs:ignore
            $arm_require_achive_badges_id = intval($_REQUEST['arm_achieve_badge_id']);//phpcs:ignore
            $achievement_type = sanitize_text_field($_REQUEST['achieve_type']);//phpcs:ignore
            $badgeData = $this->arm_get_single_badge($badge_id);
            $old_arm_achievement = $badgeData['arm_badges_achievement'];
            $old_achievement_data = isset($old_arm_achievement[$achievement_type][$achieve_num]) ? $old_arm_achievement[$achievement_type][$achieve_num] : array();
            unset($old_arm_achievement[$achievement_type][$achieve_num]);
            $new_arm_achievement = maybe_serialize($old_arm_achievement);
            $up_badge = $wpdb->update($ARMember->tbl_arm_badges_achievements, array('arm_badges_achievement' => $new_arm_achievement), array('arm_badges_id' => $badge_id));
            $user_achievement_arr = array(
                'arm_achieve_action' => 'delete_achieve',
                'badge_id' => $badge_id,
                'arm_achievement_type' => $achievement_type,
                'arm_achieve' => (isset($old_achievement_data['arm_achieve']) ? $old_achievement_data['arm_achieve'] : ''),
                'arm_achieve_num' => $achieve_num,
                'arm_achieve_badge_id' => $arm_require_achive_badges_id,
                'arm_old_achievement_type' => '',
                'arm_old_achieve' => '',
                'arm_old_achieve_num' => '',
                'arm_old_achieve_badge_id' => '',
            );
            if ($achievement_type == 'require') {
                $this->arm_add_user_require_achievement($user_achievement_arr);
            } else {
                $this->arm_add_user_other_achievement($user_achievement_arr);
            }
            $response = array('status' => 'success', 'message' => esc_html__('Achievement has been deleted successfully.', 'ARMember'));            
            echo json_encode($response);
            die();
        }
        function arm_add_user_require_achievement($user_achievement_arr = array())
        { 
            global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_social_feature,$arm_members_class;
            $defaultAchievementArr = array(
                'arm_achieve_action' => 'add_achieve',
                'badge_id' => 0,
                'parent_badge_id' => 0,
                'arm_achievement_type' => '',
                'arm_achieve' => '',
                'arm_achieve_num' => 0,
                'arm_achieve_badge_id' => 0,
                'arm_badges_tooltip' => '',
                'arm_achieve_badges_tooltip' => '',
                'arm_old_achievement_type' => '',
                'arm_old_achieve' => '',
                'arm_old_achieve_num' => 0,
                'arm_old_achieve_badge_id' => 0,
                'arm_old_badges_tooltip' => '',
                'arm_old_achieve_badges_tooltip' => '',
            );
            $newData = shortcode_atts($defaultAchievementArr, $user_achievement_arr, 'arm_add_update_achievements');
            $nowTime = strtotime(current_time('mysql'));
            $achievement_action = !empty($newData['arm_achieve_action']) ? $newData['arm_achieve_action'] : 'add_achieve';
            $badge_id = $newData['badge_id'];
            $parent_badge_id = $newData['parent_badge_id'];
            $achievement_type = $newData['arm_achievement_type'];
            $arm_achieve = $newData['arm_achieve'];
            $arm_achieve_num = $newData['arm_achieve_num'];
            $arm_achieve_badges_tooltip = $newData['arm_achieve_badges_tooltip'];
            $arm_achieve_badge_id = $newData['arm_achieve_badge_id'];
            $old_achievement_type = $newData['arm_old_achievement_type'];
            $old_achieve = $newData['arm_old_achieve'];
            $arm_old_achieve_badge_id = $newData['arm_old_achieve_badge_id'];
            $arm_old_achieve_badges_tooltip = $newData['arm_old_achieve_badges_tooltip'];
            
            $profileTemplate = $ARMember->tbl_arm_member_templates;
            $templateOptions = $wpdb->get_var($wpdb->prepare("SELECT `arm_options` FROM {$profileTemplate} WHERE arm_type = %s", 'profile')); //phpcs:ignore --Reason $profileTemplate is a table name
            $display_admin_user = 0;
            if (!empty($templateOptions)) {
                $templateOptions = maybe_unserialize($templateOptions);
                $display_admin_user = isset($templateOptions['show_admin_users']) ? $templateOptions['show_admin_users'] : 0;
            }
            
            if($display_admin_user == 1)
            {
                $users_data = $arm_members_class->arm_get_all_members_with_administrators(0,0);
            }
            else
            {
                $users_data = $arm_members_class->arm_get_all_members_without_administrator(0,0);
            }            
            
            if (!empty($users_data)) {
                foreach ($users_data as $users) {
                    $user_id = $users->ID;
                    if ($parent_badge_id && $badge_id) {
                        $old_user_achievements = $this->arm_get_user_achievements_data($user_id);
                        if(is_array($arm_old_achieve_badge_id))
                        {
                            foreach ($arm_old_achieve_badge_id as $arm_old_achieve_badge_id_val) {
                                if (isset($old_user_achievements[$arm_old_achieve_badge_id_val][$badge_id])) {
                                    if (isset($old_user_achievements[$arm_old_achieve_badge_id_val][$badge_id][$old_achievement_type])) {
                                        unset($old_user_achievements[$arm_old_achieve_badge_id_val][$badge_id][$old_achievement_type]);
                                    }
                                    if (isset($old_user_achievements[$arm_old_achieve_badge_id_val][$badge_id][$achievement_type])) {
                                        unset($old_user_achievements[$arm_old_achieve_badge_id_val][$badge_id][$achievement_type]);
                                    }
                                    if (count($old_user_achievements[$arm_old_achieve_badge_id_val][$badge_id]) == 0) {
                                        unset($old_user_achievements[$arm_old_achieve_badge_id_val][$badge_id]);
                                    }
                                }
                                if( isset($old_user_achievements[$arm_old_achieve_badge_id_val]) && is_array($old_user_achievements[$arm_old_achieve_badge_id_val]) && count($old_user_achievements[$arm_old_achieve_badge_id_val]) == 0) {
                                    unset($old_user_achievements[$arm_old_achieve_badge_id_val]);
                                }
                            }
                        }
                        else
                        {
                            if (isset($old_user_achievements[$parent_badge_id])) {
                                if (isset($old_user_achievements[$parent_badge_id][$badge_id])) {
                                    if (isset($old_user_achievements[$parent_badge_id][$badge_id][$old_achievement_type])) {
                                        unset($old_user_achievements[$parent_badge_id][$badge_id][$old_achievement_type]);
                                    }
                                    if (isset($old_user_achievements[$parent_badge_id][$badge_id][$achievement_type])) {
                                        unset($old_user_achievements[$parent_badge_id][$badge_id][$achievement_type]);
                                    }
                                    if (count($old_user_achievements[$parent_badge_id][$badge_id]) == 0) {
                                        unset($old_user_achievements[$parent_badge_id][$badge_id]);
                                    }
                                }
                                if (count($old_user_achievements[$parent_badge_id]) == 0) {
                                    unset($old_user_achievements[$parent_badge_id]);
                                }
                            }
                        }
                        if (in_array($achievement_action, array('add_achieve', 'update_achieve'))) {
                            if ($arm_achieve == 'days') {
                                $datediff = $nowTime - strtotime($users->user_registered);
                                $subDays = floor($datediff / (60 * 60 * 24));
                                
                                if(is_array($arm_achieve_num))
                                {
                                    arsort($arm_achieve_num);
                                    foreach ($arm_achieve_num as $arm_achieve_num_key => $arm_achieve_number) 
                                    {
                                        if ($subDays >= $arm_achieve_number) {
                                            $old_user_achievements[$arm_achieve_badge_id[$arm_achieve_num_key]][$badge_id][$achievement_type][$arm_achieve] = $arm_achieve_number;
                                            $old_user_achievements[$arm_achieve_badge_id[$arm_achieve_num_key]][$badge_id][$achievement_type]['arm_achieve_badges_tooltip'] = $arm_achieve_badges_tooltip[$arm_achieve_num_key];
                                            break;
                                        }
                                    }
                                }
                                else
                                {
                                    if ($subDays >= $arm_achieve_num) {
                                        $old_user_achievements[$parent_badge_id][$badge_id][$achievement_type][$arm_achieve] = $arm_achieve_num;
                                    }
                                }
                            } else {
                                $user_achieve_meta_key = 'arm_total_user_' . $achievement_type . '_' . $arm_achieve;
                                $total_achieve = get_user_meta($user_id, $user_achieve_meta_key, true);
                                $total_achieve = (!empty($total_achieve) && $total_achieve != 0) ? $total_achieve : 0;
                                
                                if(is_array($arm_achieve_num))
                                {
                                    arsort($arm_achieve_num);
                                    $arm_flag_inside_condition = '';
                                    foreach ($arm_achieve_num as $arm_achieve_num_key => $arm_achieve_number) {
                                        if ($total_achieve >= $arm_achieve_number && $arm_flag_inside_condition=='') {
                                            $old_user_achievements[$arm_achieve_badge_id[$arm_achieve_num_key]][$badge_id][$achievement_type][$arm_achieve]['achieve_num'] = $arm_achieve_number;
                                            $old_user_achievements[$arm_achieve_badge_id[$arm_achieve_num_key]][$badge_id][$achievement_type][$arm_achieve]['arm_achieve_badges_tooltip'] = $arm_achieve_badges_tooltip[$arm_achieve_num_key];
                                            
                                            $arm_flag_inside_condition = 1;
                                        }
                                    }
                                }
                                else
                                {
                                    if ($total_achieve >= $arm_achieve_num) {

                                        $old_user_achievements[$parent_badge_id][$badge_id][$achievement_type][$arm_achieve] = $arm_achieve_num;
                                        
                                    }
                                }

                            }
                        }
                        $new_user_achievements = maybe_serialize($old_user_achievements);
                        $up_user_achievements = update_user_meta($user_id, 'arm_achievements', $new_user_achievements);
                    }
                }
            }
            return false;
        }
        function arm_add_user_other_achievement($user_achievement_arr = array())
        {
            global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_social_feature, $arm_members_class;
            $defaultAchievementArr = array(
                'arm_achieve_action' => 'add_achieve',
                'badge_id' => 0,
                'parent_badge_id' => 0,
                'arm_achievement_type' => '',
                'arm_achieve' => '',
                'arm_achieve_num' => 0,
                'arm_old_achievement_type' => '',
                'arm_old_achieve' => '',
                'arm_old_achieve_num' => 0,
            );
            $newData = shortcode_atts($defaultAchievementArr, $user_achievement_arr, 'arm_add_update_achievements');
            $achievement_action = !empty($newData['arm_achieve_action']) ? $newData['arm_achieve_action'] : 'add_achieve';
            $badge_id = $newData['badge_id'];
            $parent_badge_id = $newData['parent_badge_id'];
            $achievement_type = $newData['arm_achievement_type'];
            $arm_achieve = $newData['arm_achieve'];
            $arm_achieve_num = $newData['arm_achieve_num'];
            $old_achievement_type = $newData['arm_old_achievement_type'];
            $old_achieve = $newData['arm_old_achieve'];
            $profileTemplate = $ARMember->tbl_arm_member_templates;
            $templateOptions = $wpdb->get_var($wpdb->prepare("SELECT `arm_options` FROM {$profileTemplate} WHERE arm_type = %s", 'profile')); //phpcs:ignore --Reason $profileTemplate is a table name
            $display_admin_user = 0; 
            if (!empty($templateOptions)) {
                $templateOptions = maybe_unserialize($templateOptions);
                $display_admin_user = isset($templateOptions['show_admin_users']) ? $templateOptions['show_admin_users'] : 0;
            }
            
            if($display_admin_user == 1)
            {
                $users_data = $arm_members_class->arm_get_all_members_with_administrators(0,0);
            }
            else
            {
                $users_data = $arm_members_class->arm_get_all_members_without_administrator(0,0);
            } 
            if (!empty($users_data)) {
                foreach ($users_data as $user) {
                    $user_id = $user->ID;
                    $usermetadata = get_userdata($user_id);
                    if ($parent_badge_id && $badge_id) {
                        $old_user_achievements = $this->arm_get_user_achievements_data($user_id);
                        if (isset($old_user_achievements[$parent_badge_id])) {
                            if (isset($old_user_achievements[$parent_badge_id][$badge_id])) {
                                if (isset($old_user_achievements[$parent_badge_id][$badge_id][$old_achievement_type])) {
                                    unset($old_user_achievements[$parent_badge_id][$badge_id][$old_achievement_type]);
                                }
                                if (isset($old_user_achievements[$parent_badge_id][$badge_id][$achievement_type])) {
                                    unset($old_user_achievements[$parent_badge_id][$badge_id][$achievement_type]);
                                }
                                if (count($old_user_achievements[$parent_badge_id][$badge_id]) == 0) {
                                    unset($old_user_achievements[$parent_badge_id][$badge_id]);
                                }
                            }
                            if (count($old_user_achievements[$parent_badge_id]) == 0) {
                                unset($old_user_achievements[$parent_badge_id]);
                            }
                        }
                        if (in_array($achievement_action, array('add_achieve', 'update_achieve'))) {
                            switch ($achievement_type) {
                                case 'defaultbadge':
                                    $old_user_achievements[$parent_badge_id][$badge_id]['defaultbadge'] = '1';
                                    break;
                                case 'roles':
                                    $badgeRoles = explode(',', $arm_achieve);
                                    if (!empty($usermetadata->roles)) {
                                       
                                        foreach($usermetadata->roles as $u_roles)
                                        {
                                        
                                        if (in_array($u_roles, $badgeRoles)) {
                                            $old_user_achievements[$parent_badge_id][$badge_id]['roles'] = $u_roles;
                                        }
                                        }
                                    }
                                    break;
                                case 'plans':
                                    $badgePlans = explode(',', $arm_achieve);
                                    $planIDs = get_user_meta($user_id, 'arm_user_plan_ids', true);
                                    if(!empty($planIDs) && is_array($planIDs)){
                                        foreach($planIDs as $planID){
                                            if (in_array($planID, $badgePlans)) {
                                                $old_user_achievements[$parent_badge_id][$badge_id]['plans'] = $planID;
                                            }  
                                        }
                                    }
                                    
                                    
                                    break;
                                default:
                                    break;
                            }
                        }
                        $new_user_achievements = maybe_serialize($old_user_achievements);
                        $up_user_achievements = update_user_meta($user_id, 'arm_achievements', $new_user_achievements);
                    }
                }
            }
            return false;
        }
        function arm_add_user_achieve_by_type($user_id = 0, $total_achieve = 0, $type = '',$bbrole= '')
        {
            global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_social_feature;
            if (!$arm_social_feature->isSocialFeature) {
                return false;
            }
            if (empty($type) || $type == '') {
                return false;
            }
            $badges_and_achievements_list = $this->arm_get_all_achievements();
            $badges_list = $badges_and_achievements_list['achievements'];
            if (!empty($badges_list) && !empty($user_id) && $user_id != 0) {
                $old_user_achievements = $this->arm_get_user_achievements_data($user_id);
                foreach ($badges_list as $badges) {
                    $badges = (object) $badges;
                    $badge_id = $badges->arm_badges_id;
                    $parent_badge_id = $badges->arm_badges_parent;
                    if (!empty($badges->arm_badges_achievement)) {
                        $achievement_type = $badges->arm_badges_achievement_type;
                        $achieve = maybe_unserialize($badges->arm_badges_achievement);
                        $arm_achieve = $achieve['arm_achieve'];
                        $arm_achieve_num = $achieve['arm_achieve_num'];
                        $arm_achieve_badge_id = isset($achieve['arm_achieve_badge_id']) ? $achieve['arm_achieve_badge_id'] : array();
                        $arm_achieve_badges_tooltip = isset($achieve['arm_achieve_badges_tooltip']) ? $achieve['arm_achieve_badges_tooltip'] : array();
                        if ($parent_badge_id && $badge_id) {
                            $isRemoveOld = false;
                            if ($achievement_type == 'require') {
                                if ($arm_achieve == $type) {
                                    if(is_array($arm_achieve_num))
                                    {
                                        arsort($arm_achieve_num);
                                        $arm_flag_inside_condition = '';
                                        foreach ($arm_achieve_num as $arm_achieve_num_key => $arm_achieve_number) {
                                           
                                            if ($total_achieve >= $arm_achieve_number && $arm_flag_inside_condition=='') {
                                                $old_user_achievements[$arm_achieve_badge_id[$arm_achieve_num_key]][$badge_id][$achievement_type][$arm_achieve]['achieve_num'] = $arm_achieve_number;
                                                $old_user_achievements[$arm_achieve_badge_id[$arm_achieve_num_key]][$badge_id][$achievement_type][$arm_achieve]['arm_achieve_badges_tooltip'] = $arm_achieve_badges_tooltip[$arm_achieve_num_key];
                                                $arm_flag_inside_condition = 1;
                                                
                                            } else {
                                                
                                                if (isset($old_user_achievements[$arm_achieve_badge_id[$arm_achieve_num_key]])) {
                                                    if (isset($old_user_achievements[$arm_achieve_badge_id[$arm_achieve_num_key]][$badge_id])) {
                                                        unset($old_user_achievements[$arm_achieve_badge_id[$arm_achieve_num_key]][$badge_id]);
                                                    }
                                                    if (count($old_user_achievements[$arm_achieve_badge_id[$arm_achieve_num_key]]) == 0) {
                                                        unset($old_user_achievements[$arm_achieve_badge_id[$arm_achieve_num_key]]);
                                                    }
                                                }
                                            }
                                        
                                        }
                                    }
                                    else
                                    {
                                        if ($total_achieve >= $arm_achieve_num) {
                                                $old_user_achievements[$parent_badge_id][$badge_id][$achievement_type][$arm_achieve] = $arm_achieve_num;
                                        } else {
                                            if (isset($old_user_achievements[$parent_badge_id])) {
                                                if (isset($old_user_achievements[$parent_badge_id][$badge_id])) {
                                                    unset($old_user_achievements[$parent_badge_id][$badge_id]);
                                                }
                                                if (count($old_user_achievements[$parent_badge_id]) == 0) {
                                                    unset($old_user_achievements[$parent_badge_id]);
                                                }
                                            }
                                            $isRemoveOld = true;
                                        }
                                    }
                                    
                                }
                            } elseif ($achievement_type == $type) {
                                if ($achievement_type == 'plans') {
                                    $badgePlans = explode(',', $arm_achieve);
                             
                                    $planIDs = get_user_meta($user_id, 'arm_user_plan_ids', true);
                                    $planIDs = (isset($planIDs) && !empty($planIDs)) ? $planIDs : array(); 
                                    
                                    if(!empty($planIDs) && is_array($planIDs)){
                                        foreach ($planIDs as $planID){
                                            if (in_array($planID, $badgePlans)) {
                                                $old_user_achievements[$parent_badge_id][$badge_id]['plans'] = $planID;
                                            }
                                        }
                                    }
                                    $return_array = array_intersect($planIDs, $badgePlans);
                                    if (empty($return_array)) {
                                        $isRemoveOld = true;
                                    }
                                       
                                } elseif ($achievement_type == 'roles') {
                                    $badgeRoles = explode(',', $arm_achieve);
                                    $userInfo = get_userdata($user_id);
                                    if (!empty($userInfo->roles)) {
                                        $u_roles = array_shift($userInfo->roles);
                                        if (in_array($u_roles, $badgeRoles)) {
                                            $old_user_achievements[$parent_badge_id][$badge_id]['roles'] = $u_roles;
                                        } else if(is_plugin_active('bbpress/bbpress.php') && class_exists('bbPress')){
                                            
                                             if (in_array($bbrole, $badgeRoles)) {
                                             $old_user_achievements[$parent_badge_id][$badge_id]['roles'] = $bbrole;
                                             }
                                             else
                                             {
                                                 $isRemoveOld = true;
                                             }
                                        }
                                             else
                                             {
                                            $isRemoveOld = true;
                                        }
                                    }
                                } elseif ($achievement_type == 'defaultbadge') {
                                    $old_user_achievements[$parent_badge_id][$badge_id]['defaultbadge'] = '1';
                                }
                            }
                            if ($isRemoveOld) {
                                if (isset($old_user_achievements[$parent_badge_id])) {
                                    if (isset($old_user_achievements[$parent_badge_id][$badge_id])) {
                                        unset($old_user_achievements[$parent_badge_id][$badge_id]);
                                    }
                                    if (count($old_user_achievements[$parent_badge_id]) == 0) {
                                        unset($old_user_achievements[$parent_badge_id]);
                                    }
                                }
                            }
                        }
                    }
                }
                $new_user_achievements = maybe_serialize($old_user_achievements);
                $up_user_achievements = update_user_meta($user_id, 'arm_achievements', $new_user_achievements);
            }
            return false;
        }
        function arm_add_user_achieve_by_cron()
        {   
            global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_social_feature;
            if (!$arm_social_feature->isSocialFeature) {
                return false;
            }
            $badges_and_achievements_list = $this->arm_get_all_achievements();
                        $badges_list = $badges_and_achievements_list['achievements'];
            if (!empty($badges_list)) {
                foreach ($badges_list as $badges) {
                    $badges = (object) $badges;
                    $badge_id = $badges->arm_badges_id;
                    $parent_badge_id = $badges->arm_badges_parent;
                    if (!empty($badges->arm_badges_achievement)) {
                        $achievement_type = $badges->arm_badges_achievement_type;
                        $achieve = maybe_unserialize($badges->arm_badges_achievement);
                        $arm_achieve = isset($achieve['arm_achieve']) ? $achieve['arm_achieve'] : '';
                        $arm_achieve_num = isset($achieve['arm_achieve_num']) ? $achieve['arm_achieve_num'] : 0;
                        $arm_achieve_badge_id = isset($achieve['arm_achieve_badge_id']) ? $achieve['arm_achieve_badge_id'] : 0;
                        $arm_achieve_badges_tooltip = isset($achieve['arm_achieve_badges_tooltip']) ? $achieve['arm_achieve_badge_id'] : '';
                        if ($parent_badge_id && $badge_id) {
                            if ($achievement_type == 'require' && $arm_achieve == 'days') {
                                if(is_array($arm_achieve_num))
                                {
                                    arsort($arm_achieve_num);
                                    $arm_flag_inside_condition = '';
                                    foreach ($arm_achieve_num as $arm_achieve_num_key => $arm_achieve_number) 
                                    {
                                        $args = array(
                                        'date_query' => array(
                                            array(
                                                'column' => 'user_registered',
                                                'before' => "$arm_achieve_number day ago",
                                                ),
                                            ),
                                        );
                                        $users_data = get_users($args);
                                        if (!empty($users_data)) {
                                            foreach ($users_data as $users) {
                                                $user_id = $users->ID;
                                                $user_register_date = $users->user_registered;
                                                if (user_can($user_id, 'administrator')) {
                                                    continue;
                                                }
                                                $old_user_achievements = $this->arm_get_user_achievements_data($user_id);
                                                if($arm_flag_inside_condition == '')
                                                {
                                                    $old_user_achievements[$parent_badge_id][$badge_id][$achievement_type][$arm_achieve] = (!empty($old_user_achievements[$parent_badge_id][$badge_id][$achievement_type][$arm_achieve])) ? $old_user_achievements[$parent_badge_id][$badge_id][$achievement_type][$arm_achieve] : array();
                                                    $old_user_achievements[$parent_badge_id][$badge_id][$achievement_type][$arm_achieve]['achieve_num'] = $arm_achieve_number;
                                                    $old_user_achievements[$parent_badge_id][$badge_id][$achievement_type][$arm_achieve]['arm_achieve_badges_tooltip'] = $arm_achieve_badges_tooltip[$arm_achieve_num_key];
                                                    $arm_flag_inside_condition = 1;
                                                }
                                                $new_user_achievements = maybe_serialize($old_user_achievements);
                                                $up_user_achievements = update_user_meta($user_id, 'arm_achievements', $new_user_achievements);
                                                //do_action('arm_general_log_entry', 'cron', 'user achievements achieve_num array check', 'armember', array('user_id'=>$user_id,'user_register_date'=>$user_register_date,'new_user_meta'=>$new_user_achievements));
                                            }
                                        }
                                    }
                                }
                                else
                                {
                                    $args = array(
                                        'date_query' => array(
                                            array(
                                                'column' => 'user_registered',
                                                'before' => "$arm_achieve_num day ago",
                                            ),
                                        ),
                                    );
                                    $users_data = get_users($args);
                                    if (!empty($users_data)) {
                                        foreach ($users_data as $users) {
                                            $user_id = $users->ID;
                                            $user_register_date = $users->user_registered;
                                            if (user_can($user_id, 'administrator')) {
                                                continue;
                                            }
                                            $old_user_achievements = $this->arm_get_user_achievements_data($user_id);
                                            $old_user_achievements[$parent_badge_id][$badge_id][$achievement_type][$arm_achieve] = $arm_achieve_num;
                                            $new_user_achievements = maybe_serialize($old_user_achievements);
                                            $up_user_achievements = update_user_meta($user_id, 'arm_achievements', $new_user_achievements);
                                            do_action('arm_general_log_entry', 'cron', 'user achievements achieve_num not array check', 'armember', array('user_id'=>$user_id,'user_register_date'=>$user_register_date,'new_user_meta'=>$new_user_achievements));
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return false;
        }
        function arm_get_achievements_users($badge_id = 0)
        {
            global $wpdb, $ARMember;
            $achievementsUsers = array();
            if (!empty($badge_id) && $badge_id != 0) {
                $args = array(
                    'meta_query' => array(
                        array(
                            'key' => 'arm_achievements',
                            'value' => '',
                            'compare' => '!='
                        ),
                    )
                );
                $users_data = get_users($args);
               }
            return $achievementsUsers;
        }
        function arm_add_user_badges_func()
        {
            global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_badges'], '1',1);
            $status = 'error';
            $message = esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember');
            $response = array('status' => 'error', 'message' => esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember'));
            $badge_id = isset($_POST['arm_user_badge_id']) ? intval($_POST['arm_user_badge_id']) : 0;//phpcs:ignore
            $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST ); //phpcs:ignore
            $user_ids = isset($posted_data['arm_user_ids']) ? $posted_data['arm_user_ids'] : array();
            if (!empty($badge_id) && $badge_id != 0 && !empty($user_ids)) {
                foreach ($user_ids as $uID) {
                    $old_user_achievements = $this->arm_get_user_achievements_data($uID);
                    $old_user_achievements[$badge_id][] = array('admin' => '1');
                    $new_user_achievements = maybe_serialize($old_user_achievements);
                    $up_user_achievements = update_user_meta($uID, 'arm_achievements', $new_user_achievements);
                }
                $status = 'success';
                $message = esc_html__('User Achievement Added Successfully.', 'ARMember');
                $response = array('status' => 'success', 'message' => esc_html__('User Achievement Added Successfully.', 'ARMember'));
                    
			}else{
                $message = esc_html__('Sorry, User not found.', 'ARMember');
                $response = array('status' => 'error', 'message' => $message);
                echo arm_pattern_json_encode($response);
                die();
            }
                        
                        $redirect_link = admin_url('admin.php?page=' . $arm_slugs->badges_achievements .'&action=manage_user_achievements');
                        $response['redirect_to'] = $redirect_link;
            echo arm_pattern_json_encode($response);
            die();
        }
        function arm_delete_single_user_achievements()
        {
            global $wpdb, $ARMember,$arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_badges'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $user_id = intval($_REQUEST['user_id']);//phpcs:ignore
            $badge_id = intval($_REQUEST['badge_id']);//phpcs:ignore
            $parent_badge_id = intval($_REQUEST['parent_badge_id']);//phpcs:ignore
            if (!empty($user_id) && $user_id != 0) {
                $old_user_achievements = $this->arm_get_user_achievements_data($user_id);
                if (isset($old_user_achievements[$parent_badge_id])) {
                    if (isset($old_user_achievements[$parent_badge_id][$badge_id])) {
                        unset($old_user_achievements[$parent_badge_id][$badge_id]);
                    }
                    if (count($old_user_achievements[$parent_badge_id]) == 0) {
                        unset($old_user_achievements[$parent_badge_id]);
                    }
                }
                $new_user_achievements = maybe_serialize($old_user_achievements);
                $up_user_achievements = update_user_meta($user_id, 'arm_achievements', $new_user_achievements);
            }
            $response = array('status' => 'success', 'message' => esc_html__('User badge has been deleted successfully.', 'ARMember'));
            echo arm_pattern_json_encode($response);
            die();
        }
        function arm_delete_single_badge_user_data($badge_id = 0)
        {
            global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_social_feature, $arm_members_class;
            if(!empty($badge_id) && $badge_id != 0)
            {
                $users_data = $arm_members_class->arm_get_all_members_without_administrator(0,0);
                if(!empty($users_data)){
                    foreach($users_data as $users){
                        $user_id = $users->ID;
                        if (!empty($user_id) && $user_id != 0 && $badge_id) {
                            $old_user_achievements = $this->arm_get_user_achievements_data($user_id);
                            unset($old_user_achievements[$badge_id]);
                            $new_user_achievements = maybe_serialize($old_user_achievements);
                            $up_user_achievements = update_user_meta($user_id, 'arm_achievements', $new_user_achievements);                 
                        }
                    }
                }
            }   
            return false;
        }
        function arm_get_user_achievements_data($user_id = 0)
        {
            global $wp, $wpdb, $ARMember, $arm_global_settings;
            $user_achievements = array();
            if (is_numeric($user_id) && $user_id != 0) {
                $user_achievements = get_user_meta($user_id, 'arm_achievements', true);
                $user_achievements = (!empty($user_achievements)) ? maybe_unserialize($user_achievements) : array();
                if(!empty($user_achievements) && !is_array($user_achievements))
                {
                    $user_achievements = array($user_achievements);
                }
            }
            return $user_achievements;
        }
        function arm_get_user_achievements_id($user_id = 0)
        {
            global $wp, $wpdb, $ARMember, $arm_global_settings;
            if (is_numeric($user_id) && $user_id != 0) {
                $user_achievements = $this->arm_get_user_achievements_data($user_id);
                $user_achievementsIDs = array();
                if (!empty($user_achievements)) {
                    foreach($user_achievements as $bkey=>$badge){
                        $badgeData = $this->arm_get_single_badge($bkey);
                        if(!empty($badgeData)){
                            $user_achievementsIDs[] = $bkey;
                        }                       
                    }
                }
                return $user_achievementsIDs;
            }
            return false;
        }       
        function arm_get_user_achievements_detail($user_id = 0)
        {
            global $wp, $wpdb, $ARMember, $arm_global_settings;
            $user_achievements_detail = array();
            if (is_numeric($user_id) && $user_id != 0) {                
                $user_achievements = $this->arm_get_user_achievements_data($user_id);
                if (!empty($user_achievements)) {
                    
                    $all_badge_ids = array_keys($user_achievements);
                    $total_badge_array = $this->arm_get_single_badge_from_array($all_badge_ids);
                
                    $user_suspended_plan_ids = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
                    $user_suspended_plan_ids = (isset($user_suspended_plan_ids) && !empty($user_suspended_plan_ids)) ? $user_suspended_plan_ids : array(); 
                    
                    foreach ($user_achievements as $parentBadgeID => $achievementsData) {
                        $parentBadgeData = !empty($total_badge_array[$parentBadgeID]) ? $total_badge_array[$parentBadgeID] : array();
                        if (!empty($achievementsData) && !empty($parentBadgeData)) {
                           $badge_icon = $parentBadgeData['arm_badges_icon']; 
                            foreach ($achievementsData as $achieveID => $achieveData) {
                                if (!empty($achieveData)) {
                                    $achievementbadgeData = $this->arm_get_single_badge($achieveID);
                                    $arm_badges_tooltip = isset($achievementbadgeData['arm_badges_tooltip']) ? $achievementbadgeData['arm_badges_tooltip'] : array();
                                    foreach ($achieveData as $achieveType => $achieveTotal) {
                                        $row_id = $parentBadgeID . '_' . $achieveType;
                                        $badge_title = '';
                                        if ($achieveType == 'require') {
                                            if (!empty($achieveTotal)) {
                                                foreach ($achieveTotal as $achieveTotal_key => $achieveTotal_val) 
                                                {
                                                    if(is_array($achieveTotal_val))    
                                                    {
                                                        foreach ($achieveTotal_val as $achievetype_key => $achievetype_val) 
                                                        {
                                                            if($achievetype_key=='arm_achieve_badges_tooltip' && !empty($achieveTotal_val['arm_achieve_badges_tooltip']))
                                                            {
                                                                $badge_title = esc_html($achieveTotal_val['arm_achieve_badges_tooltip']);
                                                            }
                                                            else
                                                            {
                                                                $badge_title = esc_html__('Achieve this badge for', 'ARMember') . " {$achieveTotal_val['achieve_num']} {$achieveTotal_key}";
                                                            }
                                                        }
                                                    }
                                                    else
                                                    {
                                                        if($achieveTotal_key=='arm_achieve_badges_tooltip' && !empty($achieveTotal['arm_achieve_badges_tooltip']))
                                                        {
                                                            $badge_title = esc_html($achieveTotal['arm_achieve_badges_tooltip']);
                                                        }
                                                        else
                                                        {
                                                            if(!empty($achieveTotal_val))
                                                            {
                                                                $badge_title = esc_html__('Achieve this badge for', 'ARMember')." ".$achieveTotal_val." ".$achieveTotal_key;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        } elseif ($achieveType == 'admin') {
                                            $badge_title = isset($parentBadgeData['arm_badges_name']) ? $parentBadgeData['arm_badges_name'] : esc_html__('Achieve this badge by', 'ARMember') . ' ' . $achieveType;
                                        } elseif($achieveType == 'plans'){
                                            if(is_array($user_suspended_plan_ids) && !in_array($achieveTotal, $user_suspended_plan_ids)){
                                               if(!empty($arm_badges_tooltip))
                                               {
                                                    $badge_title = esc_html($arm_badges_tooltip);
                                               }
                                               else
                                               {
                                                    $badge_title = esc_html__('Achieve this badge for', 'ARMember') . ' ' . $achieveType;
                                               }
                                                
                                            }
                                        } else {
                                            if(!empty($arm_badges_tooltip))
                                            {
                                                $badge_title = esc_html($arm_badges_tooltip);
                                            }
                                            else
                                            {
                                                $badge_title = esc_html__('Achieve this badge for', 'ARMember') . ' ' . $achieveType;
                                            }
                                        }
                                        if (!empty($badge_title)) {
                                            $user_achievements_detail[] = array(
                                                'row_id' => $row_id,
                                                'badge_id' => $achieveID,
                                                'parent_badge_id' => $parentBadgeID,
                                                'type_id' => $achieveType,
                                                'badge_title' => $badge_title,
                                                'badge_icon' => $badge_icon,
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $user_achievements_detail;
        }
        function arm_get_user_achievements_grid_list($user_id = 0)
        {
            global $wp, $wpdb, $ARMember, $arm_global_settings;
            $res = '';
            if (is_numeric($user_id) && $user_id != 0) {
                $user_achievements_detail = $this->arm_get_user_achievements_detail($user_id);
                if (!empty($user_achievements_detail)) {
                    $global_settings = $arm_global_settings->global_settings;
                    $badge_width = !empty($global_settings['badge_width']) ? $global_settings['badge_width'] : 30;
                    $badge_height = !empty($global_settings['badge_height']) ? $global_settings['badge_height'] : 30;
                    $badge_css = "width:".$badge_width."px; height:".$badge_height."px;";
                    foreach($user_achievements_detail as $user_achieve){
                        $achieveID = $user_achieve['badge_id'];
                        $badge_title = !empty($user_achieve['badge_title']) ? stripslashes_deep($user_achieve['badge_title']) : '';
                        $parentBadgeID = $user_achieve['parent_badge_id'];
                        $achieveType = $user_achieve['type_id'];
                        $rowID = "{$user_id}_{$parentBadgeID}_{$achieveID}";
                        $res .= '<div class="arm_edit_user_badge_icon_wrapper user_row_' . $rowID . '">';
                        /*
                        if(file_exists(strstr($user_achieve['badge_icon'], "//")))
                        {
                            $user_achieve['badge_icon'] =strstr($user_achieve['badge_icon'], "//");
                        }
                        else if(file_exists($user_achieve['badge_icon']))
                        {
                            $user_achieve['badge_icon'] = $user_achieve['badge_icon'];
                        }
                        else
                        {
                            $user_achieve['badge_icon'] = $user_achieve['badge_icon'];
                        }
                        */
                        if(!empty($user_achieve['badge_icon'])) 
                        {
                            $res .= '<span id="arm_edit_admin_badge" class="armhelptip" title="'.esc_attr($badge_title).'"><img src="' . esc_attr($user_achieve['badge_icon']) . '" alt="" style="'.$badge_css.'" /></span>';
                            $res .= '</div>';
                        }
                    }
                }
            }
            return $res;
        }       
        function arm_get_user_achievements_list($user_id = 0)
        {
            global $wp, $wpdb, $ARMember, $arm_global_settings;
            $res = '';
            if (is_numeric($user_id) && $user_id != 0) {                
                $user_achievements_detail = $this->arm_get_user_achievements_detail($user_id);
                if (!empty($user_achievements_detail)) {
                    $global_settings = $arm_global_settings->global_settings;
                    $badge_width = !empty($global_settings['badge_width']) ? $global_settings['badge_width'] : 30;
                    $badge_height = !empty($global_settings['badge_height']) ? $global_settings['badge_height'] : 30;
                    $badge_css = "width:".$badge_width."px; height:".$badge_height."px;";
                    foreach($user_achievements_detail as $user_achieve){
                        $achieveID = $user_achieve['badge_id'];
                        $parentBadgeID = $user_achieve['parent_badge_id'];
                        $achieveType = $user_achieve['type_id'];
                        $badge_title = !empty($user_achieve['badge_title']) ? stripslashes_deep($user_achieve['badge_title']) : '';
                        $rowID = "{$user_id}_{$parentBadgeID}_{$achieveID}";
                        $res .= '<div class="arm_badge_edit_res_inner_data user_row_' . esc_attr($rowID) . '">';
                            $res .= '<span class="arm_badge_lable_wrapper"><label>' . $badge_title . '</label></span>';
                            $res .= '<div class="arm_delete_user_achievement_wrapper">';
                            $res .= '<span class="arm_badge_edit_res_inner_img">';
                            /*
                            if(file_exists(strstr($user_achieve['badge_icon'], "//")))
                            {
                                $user_achieve['badge_icon'] =strstr($user_achieve['badge_icon'], "//");
                            }
                            else if(file_exists($user_achieve['badge_icon']))
                            {
                               $user_achieve['badge_icon'] = $user_achieve['badge_icon'];
                            }
                            else
                            {
                                $user_achieve['badge_icon'] = $user_achieve['badge_icon'];
                            }
                            */
                            $res .= '<span class="arm_edit_admin_badge"><img src="' . esc_attr($user_achieve['badge_icon']) . '" alt="" style="'.$badge_css.'" /></span>';
                            $res .= '</span>';
                            $res .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback(\"{$rowID}\");'><img src='".MEMBERSHIP_IMAGES_URL."/grid_delete.svg' class='armhelptip arm_delete_user_achievements_link' title='".esc_attr__('Delete','ARMember')."' onmouseover=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_delete_hover.svg';\" onmouseout=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_delete.svg';\" /></a>";

                            $res .= $arm_global_settings->arm_get_confirm_box($rowID, esc_html__("Are you sure you want to delete user badge?", 'ARMember').'<input type="hidden" id="data-badge_id" value="'.$achieveID.'"><input type="hidden" id="data-user_id" value="'.esc_attr($user_id).'"> <input type="hidden" id="data-parent_badge_id" value="'.esc_attr($parentBadgeID).'">', 'arm_delete_user_achievements_btn','',esc_html__("Delete", 'ARMember'),esc_html__("Cancel", 'ARMember'),esc_html__("Delete", 'ARMember'));

                            $res .= '</div>';
                        $res.= '</div>';
                    }
                }                       
            }
            return $res;
        }
                function arm_get_user_achievements_badges_list($user_id = 0)
		{
			global $wp, $wpdb, $ARMember, $arm_global_settings;
			$res = '';
			if (is_numeric($user_id) && $user_id != 0) {				
				$user_achievements_detail = $this->arm_get_user_achievements_detail($user_id);
				if (!empty($user_achievements_detail)) {
					$global_settings = $arm_global_settings->global_settings;
					$badge_width = !empty($global_settings['badge_width']) ? $global_settings['badge_width'] : 30;
					$badge_height = !empty($global_settings['badge_height']) ? $global_settings['badge_height'] : 30;
					$badge_css = "width:".$badge_width."px; height:".$badge_height."px;";
                                        $i=0;
					foreach($user_achievements_detail as $user_achieve){
                                            $i++;
						$achieveID = $user_achieve['badge_id'];
						$parentBadgeID = $user_achieve['parent_badge_id'];
						$achieveType = $user_achieve['type_id'];
						$badge_title = !empty($user_achieve['badge_title']) ? stripslashes_deep($user_achieve['badge_title']) : '';
                                                $rowID = "{$user_id}_{$parentBadgeID}_{$achieveID}";
                                                $res .= ' <input type="hidden" value="'.esc_attr($achieveID).'" id="arm_badge_id_'.esc_attr($user_id).'_'.esc_attr($i).'">';
                                                $res .= ' <input type="hidden" value="'.esc_attr($parentBadgeID).'" id="arm_parent_badge_id_'.esc_attr($user_id).'_'.esc_attr($i).'">';
						$res .= '<input class="user_chk_'.esc_attr($user_id).'_'.esc_attr($parentBadgeID).'_'.esc_attr($achieveID).'" type="checkbox" id="arm_badge_chk_'.esc_attr($rowID).'_'.esc_attr($i).'" style="display:none;">';
                                                $res .= '<span class="arm_badge_edit_res_inner_img user_img_'.esc_attr($user_id).'_'.esc_attr($parentBadgeID).'_'.esc_attr($achieveID).' armhelptip arm_padding_right_0" title="'.esc_attr($badge_title).'" >';
                                                $res .= '<span class="arm_edit_admin_badge arm_edit_user_admin_badge arm_margin_0"><img src="' . strstr($user_achieve['badge_icon'],"//") . '" alt="" style="'.$badge_css.'" class="arm_badge_img" id="'.esc_attr($rowID).'_'.esc_attr($i).'"/></span>';
                                                $res .= '</span>';
						
					}
                                        $res .= ' <input type="hidden" value="'.esc_attr($i).'" id="arm_total_badges_'.esc_attr($user_id).'">';
				}						
			}
			return $res;
		}
		function arm_get_not_user_achievements_list($user_id = 0)
		{
			global $wp, $wpdb, $ARMember, $arm_global_settings;
			$res = '';
			if (is_numeric($user_id) && $user_id != 0) {
				$user_achievementsIDs = $this->arm_get_user_achievements_id($user_id);
				$badges_list = $this->arm_get_all_badges();
				$global_settings = $arm_global_settings->global_settings;
				$badge_width = !empty($global_settings['badge_width']) ? $global_settings['badge_width'] : 30;
				$badge_height = !empty($global_settings['badge_height']) ? $global_settings['badge_height'] : 30;
				$badge_css = "width:".$badge_width."px; height:".$badge_height."px;";
				foreach($badges_list as $badge){
					if(!in_array($badge->arm_badges_id, $user_achievementsIDs))
                    {
                        /*
                        if(file_exists(strstr($badge->arm_badges_icon, "//")))
                        {
                            $badge->arm_badges_icon =strstr($badge->arm_badges_icon, "//");
                        }
                        else if(file_exists($badge->arm_badges_icon))
                        {
                            $badge->arm_badges_icon = $badge->arm_badges_icon;
                        }
                        else
                        {
                            $badge->arm_badges_icon = $badge->arm_badges_icon;
                        }
                        */
						$res .= '<span class="arm_add_admin_badge armhelptip_front" data-badge_id="'.esc_attr($badge->arm_badges_id).'" title="'.esc_attr($badge->arm_badges_name).'"><img src="' . esc_attr($badge->arm_badges_icon) . '" alt="" style="'.$badge_css.'" /></span>';
					}						
				}
			}
			return $res;
		}
        function arm_member_update_meta_achievements($user_id, $posted_data = array(), $admin_save_flag = 0) {
            global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_subscription_plans, $arm_social_feature;
            if (!$arm_social_feature->isSocialFeature) {
                return;
            }
            /**
             * Update User's Achievements.
             */
            
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            $this->arm_add_user_achieve_by_type($user_id, 0, 'defaultbadge');
            $this->arm_add_user_achieve_by_type($user_id, 0, 'roles');
            $this->arm_add_user_achieve_by_type($user_id, 0, 'plans');
        }
        function arm_save_user_post_achieve($post_id, $post, $update=false)
		{
			global $wpdb, $post, $pagenow, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_social_feature;
            if (!$arm_social_feature->isSocialFeature) {
                return;
            }
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
				return;
			}
            if (current_user_can('administrator') && (isset($post->post_author) && $post->post_author == get_current_user_id())) {
				return;
			}
			$user_id = isset($post->post_author) ? $post->post_author : get_current_user_id();
            if (!empty($user_id) && $user_id != 0 && !empty($post_id) && $post_id != 0 && !empty($post->post_type)) {
                $total_posts = count_user_posts($user_id, $post->post_type);
                update_user_meta($user_id, 'arm_total_user_require_' . $post->post_type, $total_posts);
                if (!empty($total_posts) && $total_posts > 0) {
                    $this->arm_add_user_achieve_by_type($user_id, $total_posts, $post->post_type);
                }
            }
		}
		function arm_delete_user_post_achieve($postID)
		{
			global $wpdb, $post, $pagenow, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_social_feature;
            if (!$arm_social_feature->isSocialFeature) {
                return;
            }
			if (!empty($postID) && $postID != 0) {
				if (!$delete_post_data = get_post($postID)) {
                    return false;
                }
                $post_type = $delete_post_data->post_type;
                $user_id = $delete_post_data->post_author;
                if (!empty($user_id) && $user_id != 0 && !empty($post_type)) {
                    $total_posts = count_user_posts($user_id, $post_type);
                    update_user_meta($user_id, 'arm_total_user_require_' . $post_type, $total_posts);
                    if (!empty($total_posts) && $total_posts > 0) {
                        $this->arm_add_user_achieve_by_type($user_id, $total_posts, $post_type);
                    }
                    $comments_args = array(
						'user_id' => $user_id,
						'count' => true,
						'status' => 'any'
					);
					$total_comments = get_comments($comments_args);
					update_user_meta($user_id, 'arm_total_user_require_comments', $total_comments);
					if(!empty($total_comments) && $total_comments > 0){
						$this->arm_add_user_achieve_by_type($user_id, $total_comments, 'comments');
					}
                }
            }
        }
		function arm_save_user_comment_achieve($comment_ID, $comment_approved)
		{
			global $wpdb, $post, $pagenow, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_social_feature;
            if (!$arm_social_feature->isSocialFeature) {
                return;
            }
			if (current_user_can('administrator')) {
				return;
			}
			$user_id = get_current_user_id();
			if (!empty($user_id) && $user_id != 0 && !empty($comment_ID) && $comment_ID != 0){
				$comments_args = array(
					'user_id' => $user_id,
					'count' => true
				);
				$total_comments = get_comments($comments_args);
				update_user_meta($user_id, 'arm_total_user_require_comments', $total_comments);

				if(!empty($total_comments) && $total_comments > 0){
					$this->arm_add_user_achieve_by_type($user_id, $total_comments, 'comments');
				}
			}
		}
		function arm_delete_user_comment_achieve($commentID)
		{
			global $wpdb, $post, $pagenow, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_social_feature;
            if (!$arm_social_feature->isSocialFeature) {
                return;
            }
			if (!empty($commentID) && $commentID != 0) {
				if ( !$delete_comment_data = get_comment($commentID) )
					return false;
				
				$user_id = $delete_comment_data->user_id;
				if (!empty($user_id) && $user_id != 0){					
					$comments_args = array(
						'user_id' => $user_id,
						'count' => true,
						'status' => 'any'
					);
					$total_comments = get_comments($comments_args);
                    			$total_comments_user = $total_comments-1;
                    
					update_user_meta($user_id, 'arm_total_user_require_comments', $total_comments_user);
					if(!empty($total_comments) && $total_comments > 0){
						$this->arm_add_user_achieve_by_type($user_id, $total_comments_user, 'comments');
					}
				}
			}

		}
		function arm_user_registered_time_elapsed($ptime)
		{
			$etime = current_time('timestamp') - $ptime;
			if ($etime < 1) {
				return array('total' => 0, 'period' => '');
			}
			$a = array(12 * 30 * 24 * 60 * 60 => esc_html__('years', 'ARMember'),
				30 * 24 * 60 * 60 => esc_html__('months', 'ARMember'),
				24 * 60 * 60 => esc_html__('days', 'ARMember'),
				60 * 60 => esc_html__('hours', 'ARMember'),
				60 => esc_html__('minutes', 'ARMember'),
				1 => esc_html__('seconds', 'ARMember')
			);
			foreach ($a as $secs => $str) {
				$d = $etime / $secs;
				if ($d >= 1) {
					$r = round($d);
					return array('total' => $r, 'period' => $str);
				}
			}
			return array('total' => 0, 'period' => '');
		}
        function arm_badge_achievements_list()
		{
			global $wp, $wpdb, $arm_slugs, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_drip_rules, $arm_capabilities_global,$arm_ajax_pattern_start,$arm_ajax_pattern_end,$arm_subscription_plans;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_badges'], '1',1);
			$badge_id = isset($_POST['badge_id']) ? intval($_POST['badge_id']) : 0;//phpcs:ignore
			if (!empty($badge_id) && $badge_id != 0) {
                echo $arm_ajax_pattern_start;
				$achivements = $this->arm_get_all_achievements_by_badge($badge_id);
				if(!empty($achivements))
				{
					?>
                    <table width="100%" cellspacing="0">
                        <tr>
                            <th><?php esc_html_e('Achievement Type','ARMember');?></th>
                            <th><?php esc_html_e('Required','ARMember');?></th>
                        </tr>
                        <?php foreach($achivements as $badges): ?>
                            <?php 
                            $badges = (object) $badges;
                            if (!empty($badges->arm_badges_achievement)){
                            ?>
                            <tr>
                                <td><?php 
                                $achieve = maybe_unserialize($badges->arm_badges_achievement);
                                if ($badges->arm_badges_achievement_type == 'require') {
                                    echo $achieve['arm_achieve'];//phpcs:ignore
                                } else {
                                    echo $badges->arm_badges_achievement_type; //phpcs:ignore
                                }
                                ?></td>
                                <td><?php 
                                $achieveNum = (isset($achieve['arm_achieve_num']) && $achieve['arm_achieve_num'] != 0) ? $achieve['arm_achieve_num'] : array();
                                $achieve_badge_icon_id = (isset($achieve['arm_achieve_badge_id']) && $achieve['arm_achieve_badge_id'] != 0) ? $achieve['arm_achieve_badge_id'] : array();
                                if ($badges->arm_badges_achievement_type == 'defaultbadge') {
                                    echo "-";
                                } elseif ($badges->arm_badges_achievement_type == 'require') {
                                    if(!empty($achieveNum))
                                    {
                                        
                                        if(is_array($achieveNum) && !empty($achieve_badge_icon_id))
                                        {
                                            $arm_achivement_badge = '';
                                            foreach ($achieve_badge_icon_id as $badges_icon_key => $badges_icon_value) 
                                            {
                                                if($badge_id == $badges_icon_value)
                                                {
                                                    if(empty($arm_achivement_badge))
                                                    {
                                                        $arm_achivement_badge = $achieveNum[$badges_icon_key];
                                                    }
                                                    else
                                                    {
                                                        $arm_achivement_badge .= ', '.$achieveNum[$badges_icon_key];
                                                    }
                                                }
                                            }
                                            echo $arm_achivement_badge; //phpcs:ignore
                                        }
                                        else
                                        {
                                            echo $achieveNum; //phpcs:ignore
                                        }
                                    }
                                } elseif ($badges->arm_badges_achievement_type == 'plans') {
                                    $plans_id = @explode(',', $achieve['arm_achieve']);
                                    $subs_plan_title = $arm_subscription_plans->arm_get_comma_plan_names_by_ids($plans_id);
                                    echo (!empty($subs_plan_title)) ? $subs_plan_title : '--'; //phpcs:ignore
                                }
                                elseif ($badges->arm_badges_achievement_type == 'roles') {
                                $roles_key = @explode(',', $achieve['arm_achieve']);
                                $user_roles = $arm_global_settings->arm_get_all_roles_for_badges();
                                $role_names = array();    
                                foreach($roles_key as $role_key_val)
                                {
                                    if (!empty($user_roles)){
                                        foreach ($user_roles as $key => $val){
                                            if($key == $role_key_val)
                                            {
                                                $role_names[]= $val;
                                            }
                                        }
                                    }
                                }
                                echo implode(", ",$role_names); //phpcs:ignore
                            }
                                else {
                                    echo str_replace(',', ', ', $achieve['arm_achieve']); //phpcs:ignore
                                }
                                ?></td>
                            </tr>
                            <?php } ?>
                        <?php endforeach;?>
                    </table>
							
					<?php
				}
                echo $arm_ajax_pattern_end;
			}
			exit;
		}

        function arm_get_achievements_grid_data(){
            global $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings,$arm_members_badges,$arm_email_settings,$arm_manage_coupons, $arm_subscription_plans,$arm_common_lite,$arm_capabilities_global;

            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_badges'], '1',1);

            $get_all_achievements = $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_type`=%s ORDER BY `arm_badges_id` DESC",'achievement'); //phpcs:ignore --Reason $ARMember->tbl_arm_badges_achievements is a table name

            $global_settings = $arm_global_settings->global_settings;
            $badge_width = !empty($global_settings['badge_width']) ? $global_settings['badge_width'] : 30;
            $badge_height = !empty($global_settings['badge_height']) ? $global_settings['badge_height'] : 30;
            $badgeIconStyle = "width:" . $badge_width . "px; height:" . $badge_height . "px;";

            $row = $wpdb->get_results($get_all_achievements); //phpcs:ignore --Reason $sel_administrator is a query without where clause.
            $total_before_filter = count($row);

            $user_offset = isset($_REQUEST['iDisplayStart']) ? intval($_REQUEST['iDisplayStart']) : 0;
            $user_number = isset($_REQUEST['iDisplayLength']) ? intval($_REQUEST['iDisplayLength']) : 15;

            $search = isset( $_REQUEST['sSearch'] ) ? sanitize_text_field($_REQUEST['sSearch']) : '';
            $allBadges = $this->arm_get_all_badges();

            $LIMIT = " LIMIT {$user_offset},{$user_number}";
            $data = array();
            $sSearch_ = "";
            if( $search !== '' ){
                $sSearch_ = $wpdb->prepare(" AND (arm_badges_achievement LIKE %s OR arm_badges_achievement_type LIKE %s) ",'%'.$search.'%','%'.$search.'%');
            }

            $select_badges_sql = $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_type`=%s ".$sSearch_." ORDER BY `arm_badges_id` DESC",'achievement'); //
            $select_badges = $wpdb->get_results($select_badges_sql); //phpcs:ignore --Reason $select_users is a Query name
            $after_filter = (!empty($select_badges) ) ? count($select_badges) : 0;

            $select_achievement_sql_limit = $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_type` = %s ".$sSearch_." ORDER BY `arm_badges_id` DESC".$LIMIT,'achievement');

            $allAchievements = $wpdb->get_results($select_achievement_sql_limit);//phpcs:ignore --Reason $select_badges is a Query name

            if (!empty($allAchievements)){
                $i = 1;
                $ai = 0;
                foreach ($allAchievements as $badges){
                    // $badges = (object) $badges;
                    $badgesID = $badges->arm_badges_id;
                    if (!empty($badges->arm_badges_achievement)) {
                        $achieve = maybe_unserialize($badges->arm_badges_achievement);                       
                        $arm_achieve_badge_id = (isset($achieve['arm_achieve_badge_id']) && $achieve['arm_achieve_badge_id'] != 0) ? $achieve['arm_achieve_badge_id'] : 0;
                        $arm_achieve_require_badge_id_array = maybe_unserialize($arm_achieve_badge_id);
                        $achiement_type = 'Default Badge';
                        if ($badges->arm_badges_achievement_type == 'require') {
                            $achiement_type = ucwords($achieve['arm_achieve']); //phpcs:ignore
                        } else {
                            if($badges->arm_badges_achievement_type=='defaultbadge')
                            {
                                $achiement_type = 'Default Badge';
                            }
                            else
                            {
                                $achiement_type = ucwords($badges->arm_badges_achievement_type); //phpcs:ignore
                            }
                        }
                        $data[$ai][0] = $achiement_type;
                        $badge_icon = '--';
                        $badgeParent = $badges->arm_badges_parent;
                        $parentBadge = isset($allBadges[$badgeParent]) ? $allBadges[$badgeParent] : array();
                        $arm_badges_name = isset($parentBadge->arm_badges_name) ? $parentBadge->arm_badges_name : '';
                        
                        if($badges->arm_badges_achievement_type == 'require'){
                            if(is_array($arm_achieve_require_badge_id_array))
                            {
                                $badge_icon = '';
                                foreach ($arm_achieve_require_badge_id_array as $arm_achieve_require_badge_id_value) 
                                {
                                    $arm_badges_require_icon = $arm_members_badges->arm_get_single_badge($arm_achieve_require_badge_id_value);
                                    
                                    if(!empty($arm_badges_require_icon['arm_badges_icon']))
                                    { 
                                        $badge_icon .= '<img src="' . esc_attr($arm_badges_require_icon['arm_badges_icon']) . '" class="arm_grid_badges_icon armhelptip_front" alt="" title="'. esc_attr($arm_badges_require_icon['arm_badges_name']).'" style="' . $badgeIconStyle . '" >';//phpcs:ignore
                                    }
                                }
                            }
                            else
                            {
                                if(empty($badges->arm_badges_icon))
                                {
                                    $badge_icon = '--';
                                } 
                                else 
                                {
                                    $badge_icon ='<img src="' . esc_attr($badges->arm_badges_icon) . '" class="arm_grid_badges_icon armhelptip_front" title="'.esc_attr($badges->arm_badges_name).'" alt="" style="' . $badgeIconStyle . '" >';//phpcs:ignore
                                }
                            }
                        }else{    
                            $badge_icon = isset($parentBadge->arm_badges_icon) ? '<img src="'.$parentBadge->arm_badges_icon.'" class="arm_grid_badges_icon armhelptip_front" title="'.$arm_badges_name.'" style="width:30px; height:30px;"/>' : '--';
                        }    
                        $data[$ai][1] = $badge_icon;

                        $achieveNum = (isset($achieve['arm_achieve_num']) && $achieve['arm_achieve_num'] != 0) ? $achieve['arm_achieve_num'] : 0;
                        $arm_num_of_achievement = '-';
                        if ($badges->arm_badges_achievement_type == 'defaultbadge') {
                            $arm_num_of_achievement = "-";
                        } elseif ($badges->arm_badges_achievement_type == 'require') {
                            $arm_achive_num_ser =  maybe_unserialize($achieve['arm_achieve_num']);
                            if(is_array($arm_achive_num_ser))
                            {
                                $arm_num_of_achievement = implode(',', $arm_achive_num_ser); //phpcs:ignore
                            }
                            else
                            {
                                $arm_num_of_achievement = esc_html($achieveNum);
                            }
                        } elseif ($badges->arm_badges_achievement_type == 'plans') {
                            $plans_id = @explode(',', $achieve['arm_achieve']);
                            $subs_plan_title = $arm_subscription_plans->arm_get_comma_plan_names_by_ids($plans_id);
                            $arm_num_of_achievement = (!empty($subs_plan_title)) ? esc_html($subs_plan_title) : '--';
                        }
                        elseif ($badges->arm_badges_achievement_type == 'roles') {
                            $roles_key = @explode(',', $achieve['arm_achieve']);
                            $user_roles = $arm_global_settings->arm_get_all_roles_for_badges();
                            $role_names = array();    
                            foreach($roles_key as $role_key_val)
                            {
                                if (!empty($user_roles)){
                                    foreach ($user_roles as $key => $val){
                                        if($key == $role_key_val)
                                        {
                                            $role_names[]= $val;
                                        }
                                    }
                                }
                            }
                            $arm_num_of_achievement = implode(", ",$role_names); //phpcs:ignore
                        }
                        else {
                            $arm_num_of_achievement = str_replace(',', ', ', $achieve['arm_achieve']); //phpcs:ignore
                        }
                        $data[$ai][2] = $arm_num_of_achievement;
                        $gridAction = "<div class='arm_grid_action_btn_container'>";
                        $gridAction .= "<a class='arm_edit_achievements_btn arm_padding_0 armhelptip' title='" . esc_html__('Edit Achievement', 'ARMember') . "' href='javascript:void(0);' data-achievement_id='" . $badgesID . "'><svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M13.2594 3.60022L5.04936 12.2902C4.73936 12.6202 4.43936 13.2702 4.37936 13.7202L4.00936 16.9602C3.87936 18.1302 4.71936 18.9302 5.87936 18.7302L9.09936 18.1802C9.54936 18.1002 10.1794 17.7702 10.4894 17.4302L18.6994 8.74022C20.1194 7.24022 20.7594 5.53022 18.5494 3.44022C16.3494 1.37022 14.6794 2.10022 13.2594 3.60022Z' stroke='#617191' stroke-width='1.5' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/><path d='M11.8906 5.0498C12.3206 7.8098 14.5606 9.9198 17.3406 10.1998' stroke='#617191' stroke-width='1.5' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/><path d='M3 22H21' stroke='#617191' stroke-width='1.5' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/></svg></a>";
                        $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$badgesID});' class='arm_grid_delete_action armhelptip' title='" . esc_html__('Delete', 'ARMember') . "'><svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M3 5.33333H21M16.5 5.33333L16.1956 4.43119C15.9005 3.55694 15.7529 3.11982 15.4793 2.79664C15.2376 2.51126 14.9274 2.29036 14.5768 2.1542C14.1798 2 13.7134 2 12.7803 2H11.2197C10.2866 2 9.8202 2 9.4232 2.1542C9.07266 2.29036 8.76234 2.51126 8.5207 2.79664C8.24706 3.11982 8.09954 3.55694 7.80447 4.43119L7.5 5.33333M18.75 5.33333V16.6667C18.75 18.5336 18.75 19.4669 18.3821 20.18C18.0586 20.8072 17.5423 21.3171 16.9072 21.6367C16.1852 22 15.2402 22 13.35 22H10.65C8.75982 22 7.81473 22 7.09278 21.6367C6.45773 21.3171 5.94143 20.8072 5.61785 20.18C5.25 19.4669 5.25 18.5336 5.25 16.6667V5.33333M14.25 9.77778V17.5556M9.75 9.77778V17.5556' stroke='#617191' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/></svg></a>";
                        $gridAction .= $arm_global_settings->arm_get_confirm_box($badgesID, esc_html__("Are you sure you want to delete this achievement?", 'ARMember'), 'arm_delete_achievements_btn', 'achievement',esc_html__("Delete", 'ARMember'),esc_html__("Cancel", 'ARMember'),esc_html__("Delete", 'ARMember'));
                        $gridAction .= "</div>";
                        $data[$ai][3] = $gridAction; //phpcs:ignore
                    }
                    $ai++;
                }
            }
            $sEcho = isset( $_REQUEST['sEcho'] ) ? intval($_REQUEST['sEcho'] ) : intval(15);
            $columns = esc_html__('Achievement Type','ARMember').','.esc_html__('Badge icon','ARMember').','.esc_html__('Required','ARMember').',';
            $output = array(
                'sColumns' => $columns,
                'sEcho' => $sEcho,
                'iTotalRecords' => $total_before_filter,
                'iTotalDisplayRecords' => $after_filter,
                'aaData' => $data
            );
            echo json_encode( $output );
            die();
        }

        function arm_get_user_badges_grid_data(){
            global $wpdb,$arm_members_badges,$arm_global_settings, $ARMember, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_badges'], '1',1);
            $get_all_badges = $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_type` = %s ORDER BY `arm_badges_id` DESC",'badge'); //phpcs:ignore --Reason $ARMember->tbl_arm_badges_achievements is a table name
            $row = $wpdb->get_results($get_all_badges); //phpcs:ignore --Reason $sel_administrator is a query without where clause.
            $total_before_filter = count($row);

            $user_offset = isset($_REQUEST['iDisplayStart']) ? intval($_REQUEST['iDisplayStart']) : 0;
            $user_number = isset($_REQUEST['iDisplayLength']) ? intval($_REQUEST['iDisplayLength']) : 15;

            $search = isset( $_REQUEST['sSearch'] ) ? sanitize_text_field($_REQUEST['sSearch']) : '';

            $LIMIT = " LIMIT {$user_offset},{$user_number}";

            $sSearch_ = "";
            if( $search !== '' ){
                $sSearch_ = $wpdb->prepare(" AND arm_badges_name LIKE %s ",'%'.$search.'%');
            }

            $select_badges_sql = $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_type` = %s ".$sSearch_." ORDER BY `arm_badges_id` DESC",'badge'); //
            $select_badges = $wpdb->get_results($select_badges_sql); //phpcs:ignore --Reason $select_users is a Query name
            $after_filter = (!empty($select_badges) ) ? count($select_badges) : 0;

            $select_badges_sql_limit = $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_badges_achievements . "` WHERE `arm_badges_type` = %s ".$sSearch_." ORDER BY `arm_badges_id` DESC".$LIMIT,'badge');

            $badges_list = $wpdb->get_results($select_badges_sql_limit);//phpcs:ignore --Reason $select_badges is a Query name
            $data = array();
            if (!empty($badges_list)) {
                $global_settings = $arm_global_settings->global_settings;
                $badge_width = !empty($global_settings['badge_width']) ? $global_settings['badge_width'] : 30;
                $badge_height = !empty($global_settings['badge_height']) ? $global_settings['badge_height'] : 30;
                $badge_css = "width:" . $badge_width . "px; height:" . $badge_height . "px;";
                //$arm_all_achievements_count = $arm_members_badges->arm_get_all_achievements_count_by_badge();
                $ai = 0;
                foreach ($badges_list as $key => $badges) {
                    /*print_r($badges);
                    exit;*/
                    $badgeID = $badges->arm_badges_id;
                    $badge_icon = '--';
                    if(!empty($badges->arm_badges_icon)){
                        
                        $arm_badges_icon = $badges->arm_badges_icon;
                        
                        if(filter_var($badges->arm_badges_icon, FILTER_VALIDATE_URL))
                        {
                            $arm_badges_icon = $arm_badges_icon;                        
                        }
                        else{
                            $arm_badges_icon = $this->arm_badges_url.$arm_badges_icon;
                        }
                        $arm_badges_icon_arr = explode('/', $arm_badges_icon);
                        $arm_badges_icon_end = end($arm_badges_icon_arr);
                        $arm_badges_icon_url = $badges->arm_badges_icon;
                        
                        if( file_exists(MEMBERSHIP_UPLOAD_DIR.'/social_badges/'.$arm_badges_icon_end) ){
                            $arm_badges_icon_url = strstr($arm_badges_icon, "//");
                        }else if( file_exists(MEMBERSHIP_UPLOAD_DIR.'/social_badges/'.$arm_badges_icon_end) ){
                            $arm_badges_icon_url = $arm_badges_icon;
                        }else{
                            $arm_badges_icon_url = $arm_badges_icon;
                        }                       
                        $badge_icon = '<img src="' . esc_attr($arm_badges_icon_url) . '" class="arm_grid_badges_icon" alt="" style="' . $badge_css . '" >';//phpcs:ignore
                    }
                    $data[$ai][0] = '<center>'.$badge_icon.'</center>';
                    $data[$ai][1] = esc_html(stripslashes_deep($badges->arm_badges_name));

                    $childAchivements = $arm_members_badges->arm_get_all_achievements_count_by_badge($badgeID);
                    //$childAchivements = isset($arm_all_achievements_count[$badgeID]) ? $arm_all_achievements_count[$badgeID] : 0; 
                    $arm_badge_confirm_box = '';
                    if ($childAchivements > 0) {
                        $data[$ai][2] =  '<a href="javascript:void(0);" class="arm_preview_badge_achievements" data-id="' . esc_attr($badgeID) . '">' . esc_html($childAchivements) . '</a>';
                        $arm_badge_confirm_box = $arm_global_settings->arm_get_confirm_box($badgeID, esc_html__("Are you sure you want to delete this badge and achievement(s)?", 'ARMember'), 'arm_delete_badges_btn','',esc_html__("Delete", 'ARMember'),esc_html__("Cancel", 'ARMember'),esc_html__("Delete", 'ARMember'));
                    } else {
                        $data[$ai][2] = 0;
                        $arm_badge_confirm_box = $arm_global_settings->arm_get_confirm_box($badgeID, esc_html__("Are you sure you want to delete this badge?", 'ARMember'), 'arm_delete_badges_btn','',esc_html__("Delete", 'ARMember'),esc_html__("Cancel", 'ARMember'),esc_html__("Delete", 'ARMember'));
                    }
                        
                    $gridAction = "<div class='arm_grid_action_btn_container'>";
                    $gridAction .= "<a class='arm_edit_badges_btn arm_padding_0 armhelptip' title='" . esc_html__('Edit Badge', 'ARMember') . "' href='javascript:void(0);' data-badge_id='" . $badgeID . "'><svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M13.2594 3.60022L5.04936 12.2902C4.73936 12.6202 4.43936 13.2702 4.37936 13.7202L4.00936 16.9602C3.87936 18.1302 4.71936 18.9302 5.87936 18.7302L9.09936 18.1802C9.54936 18.1002 10.1794 17.7702 10.4894 17.4302L18.6994 8.74022C20.1194 7.24022 20.7594 5.53022 18.5494 3.44022C16.3494 1.37022 14.6794 2.10022 13.2594 3.60022Z' stroke='#617191' stroke-width='1.5' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/><path d='M11.8906 5.0498C12.3206 7.8098 14.5606 9.9198 17.3406 10.1998' stroke='#617191' stroke-width='1.5' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/><path d='M3 22H21' stroke='#617191' stroke-width='1.5' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/></svg></a>";
                    $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$badgeID});' class='arm_grid_delete_action armhelptip' title='" . esc_html__('Delete', 'ARMember') . "'><svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M3 5.33333H21M16.5 5.33333L16.1956 4.43119C15.9005 3.55694 15.7529 3.11982 15.4793 2.79664C15.2376 2.51126 14.9274 2.29036 14.5768 2.1542C14.1798 2 13.7134 2 12.7803 2H11.2197C10.2866 2 9.8202 2 9.4232 2.1542C9.07266 2.29036 8.76234 2.51126 8.5207 2.79664C8.24706 3.11982 8.09954 3.55694 7.80447 4.43119L7.5 5.33333M18.75 5.33333V16.6667C18.75 18.5336 18.75 19.4669 18.3821 20.18C18.0586 20.8072 17.5423 21.3171 16.9072 21.6367C16.1852 22 15.2402 22 13.35 22H10.65C8.75982 22 7.81473 22 7.09278 21.6367C6.45773 21.3171 5.94143 20.8072 5.61785 20.18C5.25 19.4669 5.25 18.5336 5.25 16.6667V5.33333M14.25 9.77778V17.5556M9.75 9.77778V17.5556' stroke='#617191' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/></svg></a>";
                    $gridAction .= $arm_badge_confirm_box;
                    $gridAction .= "</div>";
                    $data[$ai][3] = $gridAction;//phpcs:ignore
                    $ai++;
                }
            }
            $sEcho = isset( $_REQUEST['sEcho'] ) ? intval($_REQUEST['sEcho'] ) : intval(15);
            $columns = esc_html__('Username','ARMember').','.esc_html__('Email Address','ARMember').','.esc_html__('Badges','ARMember').',';
            $output = array(
                'sColumns' => $columns,
                'sEcho' => $sEcho,
                'iTotalRecords' => $total_before_filter,
                'iTotalDisplayRecords' => $after_filter,
                'aaData' => $data
            );
            echo json_encode( $output );
            die();

        }
        
        function arm_get_user_achievements_grid_data() {
            global $wpdb,$arm_members_badges,$arm_global_settings, $ARMember, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_badges'], '1',1);
            $user_table = $wpdb->users;
           
            $capability_column = $wpdb->get_blog_prefix($GLOBALS['blog_id']) . 'capabilities';

            $user_where = " WHERE 1=1";
            $filter_where = " WHERE 1=1";
           
            $sel_administrator = "SELECT ID FROM `{$user_table}` $user_where GROUP BY ID"; 
            $row = $wpdb->get_results($sel_administrator); //phpcs:ignore --Reason $sel_administrator is a query without where clause.
            $total_before_filter = count($row);

            $user_offset = isset($_REQUEST['iDisplayStart']) ? intval($_REQUEST['iDisplayStart']) : 0;
            $user_number = isset($_REQUEST['iDisplayLength']) ? intval($_REQUEST['iDisplayLength']) : 15;
            $search = isset( $_REQUEST['sSearch'] ) ? sanitize_text_field($_REQUEST['sSearch']) : '';
            $LIMIT = " LIMIT {$user_offset},{$user_number}";
            $sSearch_ = "";
            if( $search !== '' ){
                $sSearch_ = $wpdb->prepare(" AND (user_login LIKE %s OR user_email LIKE %s)",'%'.$search.'%','%'.$search.'%');
            }
            $sort_column = isset($_REQUEST['iSortCol_0']) ? intval($_REQUEST['iSortCol_0']) : 0;
            $sort_by = isset($_REQUEST['sSortDir_0']) ? sanitize_text_field($_REQUEST['sSortDir_0']) : 'asc';
            $sort_by = strtolower($sort_by);
            if ( 'asc'!=$sort_by && 'desc'!=$sort_by ) {
                $sort_by = 'asc';
            }
            $order_by = "user_login";
            if( $sort_column == 0 ){
                $order_by = "user_login";
            }
            if( $sort_column == 1 ){
                $order_by = "`user_email`";
            }
         
            $select_users = "SELECT COUNT(ID) as total_users FROM `{$user_table}` {$filter_where} {$sSearch_}";  
            $select_total_users = $wpdb->get_results($select_users); //phpcs:ignore --Reason $select_users is a Query name
            $after_filter = (isset($select_total_users[0]->total_users) && $select_total_users[0]->total_users !== '') ? $select_total_users[0]->total_users : 0;

            $select_users = "SELECT ID,user_login,user_email FROM `{$user_table}` {$filter_where} {$sSearch_} GROUP BY ID ORDER BY {$order_by} {$sort_by} {$LIMIT}";   
            $users_data = $wpdb->get_results($select_users);//phpcs:ignore --Reason $select_users is a Query name

            $data = array();
            
            if( is_multisite() ){
	            foreach( $users_data as $_key => $gusers ){
	            	$auser = new WP_User($gusers->ID);
	            	if( !is_user_member_of_blog($auser->ID)){
	            		unset($users_data[$_key]);
	            	}
	            }
            }
            $total_administrators = 0;
            if( !empty($users_data) ){
                $ai = 0;
                foreach( $users_data as $key => $users ){
                    $userID = $users->ID;
		    
		    /* 
                    $profileTemplate = $ARMember->tbl_arm_member_templates;
                    $templateOptions = $wpdb->get_var($wpdb->prepare("SELECT `arm_options` FROM {$profileTemplate} WHERE arm_type = %s", 'profile'));//phpcs:ignore --Reason $profileTemplate is a table name
                    $display_admin_user = 0;
                    if (!empty($templateOptions)) {
                        $templateOptions = maybe_unserialize($templateOptions);
                        $display_admin_user = isset($templateOptions['show_admin_users']) ? $templateOptions['show_admin_users'] : 0;
                    }
                
                    if($display_admin_user == 1)
                    {
                        
                    }
                    else
                    {
                        if (user_can($userID, 'administrator')) {
                            $total_administrators++;
                                continue;
                            }
                    }  */

                    $data[$ai][0] = $users->user_login;
                    $data[$ai][1] = $users->user_email;
                    $user_achievements_list = $arm_members_badges->arm_get_user_achievements_grid_list($userID);
                    $data[$ai][2] = !empty($user_achievements_list) ? $user_achievements_list : '--';
                    if(!empty($user_achievements_list)){
                        $gridAction = "<div class='arm_grid_action_btn_container'>";
                        $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$userID});' class='arm_grid_delete_action armhelptip' title='" . esc_attr__('Delete', 'ARMember') . "'><svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M3 5.33333H21M16.5 5.33333L16.1956 4.43119C15.9005 3.55694 15.7529 3.11982 15.4793 2.79664C15.2376 2.51126 14.9274 2.29036 14.5768 2.1542C14.1798 2 13.7134 2 12.7803 2H11.2197C10.2866 2 9.8202 2 9.4232 2.1542C9.07266 2.29036 8.76234 2.51126 8.5207 2.79664C8.24706 3.11982 8.09954 3.55694 7.80447 4.43119L7.5 5.33333M18.75 5.33333V16.6667C18.75 18.5336 18.75 19.4669 18.3821 20.18C18.0586 20.8072 17.5423 21.3171 16.9072 21.6367C16.1852 22 15.2402 22 13.35 22H10.65C8.75982 22 7.81473 22 7.09278 21.6367C6.45773 21.3171 5.94143 20.8072 5.61785 20.18C5.25 19.4669 5.25 18.5336 5.25 16.6667V5.33333M14.25 9.77778V17.5556M9.75 9.77778V17.5556' stroke='#617191' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/></svg></a>";
                        $gridAction .= $arm_global_settings->arm_get_badges_confirm_box($userID, esc_html__("Select Badges you want to delete.", 'ARMember'), 'arm_delete_user_badges_btn');
                        $gridAction .= "</div>";
                        $data[$ai][3] = $gridAction;
                    } else {
                        $data[$ai][3] = "<div class='arm_grid_action_btn_container' style='display:none;'></div>";
                    }
                    $ai++;
                }
            }
            $sEcho = isset( $_REQUEST['sEcho'] ) ? intval($_REQUEST['sEcho'] ) : intval(15);
            $columns = esc_html__('Username','ARMember').','.esc_html__('Email Address','ARMember').','.esc_html__('Badges','ARMember').',';
            $arm_iTotalRecords = intval($total_before_filter) - intval($total_administrators);
            $arm_iTotalDisplayRecords = intval($after_filter) - intval($total_administrators);
            $output = array(
                'sColumns' => $columns,
                'sEcho' => $sEcho,
                'iTotalRecords' => $arm_iTotalRecords,
                'iTotalDisplayRecords' => $arm_iTotalDisplayRecords,
                'aaData' => $data
            );
            echo json_encode( $output );
            die();
        }

    }
}

global $arm_members_badges;
$arm_members_badges = new ARM_members_badges();