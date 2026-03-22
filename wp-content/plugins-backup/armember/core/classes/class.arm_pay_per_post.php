<?php

if (!class_exists('ARM_pay_per_post_feature')) {

    class ARM_pay_per_post_feature {
    	
        var $paid_post_settings;
        
        var $isPayPerPostFeature;
        var $isPayPerPostFeatureLimit;

        function __construct() {
        	
            global $wpdb, $ARMember, $arm_slugs,$arm_is_plan_limit_feature;
        	
            $is_paid_post_feature = get_option('arm_is_pay_per_post_feature', 0);
        	$this->isPayPerPostFeature = ($is_paid_post_feature == '1') ? true : false;
            $this->isPayPerPostFeatureLimit = ($arm_is_plan_limit_feature == '1') ? true : false;
        	// add_action( 'wp_ajax_arm_install_plugin', array( $this, 'arm_plugin_install' ), 10 );
        
            if( $this->isPayPerPostFeature == true ){

                add_action( 'arm_shortcode_add_other_tab_buttons',array( $this,'arm_paid_post_shortcode_add_tab_buttons' ) );

                add_action( 'wp_ajax_get_paid_post_data', array( $this, 'arm_retrieve_paid_post_data' ) );

                add_action( 'wp_ajax_arm_get_paid_post_members_data', array( $this, 'arm_get_paid_post_members_data_func' ) );

                add_action( 'wp_ajax_arm_get_paid_post_item_options', array( $this, 'arm_get_paid_post_item_options' ) );

                add_action( 'wp_ajax_arm_delete_single_paid_post', array( $this, 'arm_delete_single_paid_post' ) );

                add_action( 'arm_display_field_add_membership_plan', array( $this, 'display_field_add_membership_plan_page' ) );
		
                add_filter( 'arm_display_shortcode_buttons_on_tinymce', array( $this, 'arm_display_shortcode_buttons_for_alternate_button' ), 10, 2 );

                add_filter( 'arm_allowed_pages_for_media_buttons', array( $this, 'arm_allowed_pages_for_media_buttons_buttons' ), 10, 2 );

                add_filter( 'arm_allowed_post_type_for_external_editors', array( $this, 'arm_allowed_post_type_for_external_editors_callback' ), 10, 2 );

                add_filter( 'arm_allowed_pages_for_shortcode_popup', array( $this, 'arm_allowed_pages_for_shortcode_popup_callback' ), 10 );
                
                add_filter( 'arm_enqueue_shortcode_styles', array( $this, 'arm_enqueue_shortcode_styles_callback' ), 10 );

                add_filter( 'arm_modify_restriction_plans_outside', array( $this, 'arm_add_paid_post_plan_for_restriction' ), 10, 2 );

                add_filter( 'arm_setup_data_before_setup_shortcode', array( $this, 'arm_modify_setup_data_for_paid_post_type_setup'), 10, 2 );

                add_filter( 'arm_all_active_subscription_plans', array( $this, 'arm_add_paid_post_plan_in_active_subscription_pans'), 10 );

                add_filter( 'arm_after_setup_plan_section', array( $this, 'arm_add_paid_post_plan_id'), 10, 3 );
		
        		add_shortcode( 'arm_paid_post_buy_now', array( $this, 'arm_paid_post_buy_now_func' ) );
        		
        		add_action( 'wp_ajax_arm_paid_post_plan_paging_action', array( $this, 'arm_paid_post_plan_paging_action' ) );
        		
                add_action( 'wp_ajax_arm_paid_post_plan_modal_paging_action', array( $this, 'arm_paid_post_plan_modal_paging_action' ) );
        		add_action( 'arm_after_add_transaction', array( $this, 'arm_update_paid_post_transaction' ), 10 );

                add_action('arm_after_new_user_update_transaction',array($this,'arm_assign_paid_post_to_user'),10,4);

                add_filter( 'arm_setup_data_before_submit', array( $this, 'arm_add_paid_post_plan_in_setup_data'), 10, 2 );

                add_filter( 'arm_notification_add_message_types', array( $this, 'arm_add_paid_post_message_types'), 10 );

                add_action( 'arm_update_access_rules_from_outside', array( $this, 'arm_update_paid_post_access_rules' ), 10 );
		
                add_action('wp_ajax_arm_display_paid_post_cycle', array($this, 'arm_ajax_display_paid_post_cycle'));

                add_action( 'arm_update_access_plan_for_drip_rules', array( $this, 'arm_update_access_plan_for_drip_rules_callback'), 10, 1);

                add_action('wp_ajax_get_arm_paid_post_plan_list', array($this, 'get_arm_paid_post_plan_list_func'));

                add_filter('arm_add_paid_post_metabox_html',array($this,'arm_admin_add_edit_paid_post_metabox_html_func'),10,4);

                add_action( 'wp_ajax_arm_admin_save_paid_post_detail', array( $this, 'arm_add_update_paid_post' ) );

                add_action('wp_ajax_arm_paid_post_edit_detail',array($this,'arm_paid_post_edit_details_func'));

                add_action('wp_ajax_arm_paid_post_pages_lists',array($this,'arm_paid_post_pages_lists_func'));
		
		add_filter('arm_paid_post_wrapper_popup_filter',array($this,'arm_paid_post_wrapper_popup_filter_func'),10,1);
	        }
        }
	
	function arm_paid_post_wrapper_popup_filter_func($arm_paid_post_popup_wrapper_filter = ''){
            $arm_paid_post_popup_wrapper_filter = '<div class="arm_member_paid_post_popup popup_wrapper arm_import_user_list_detail_popup_wrapper" style="width:1200px; min-height: 200px;">
                <form method="GET" id="arm_member_manage_plan_user_form" class="arm_admin_form">
                    <div class="popup_wrapper_inner">
                        <div class="popup_header">
                            <span class="popup_close_btn arm_popup_close_btn arm_member_paid_post_popup_close_btn"></span>
                            <span class="add_rule_content">'. esc_html__('Manage Paid Post','ARMember' ).' <span class="arm_manage_plans_username" id="arm_manage_plans_username"></span></span>
                            <input type="hidden" id="arm_delete_paid_post_plan" value="" />
                            <input type="hidden" id="arm_add_paid_post_plan" value="" />
                        </div>
                        <div class="popup_content_text arm_member_manage_post_detail_popup_text arm_text_align_center arm_padding_0" style="height: auto;">
                            <div style="width: 100%; margin: 45px auto;">
                                <img src="'. esc_attr(MEMBERSHIPLITE_IMAGES_URL) . '/arm_loader.gif">
                            </div>
                        </div>
                    </div>
                </form>
            </div>';
            return $arm_paid_post_popup_wrapper_filter;
        }

        function arm_paid_post_pages_lists_func(){
            global $arm_payment_gateways,$ARMember,$wpdb, $arm_global_settings,$arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_pay_per_post'], '1',1);
            $post_type = !empty($_REQUEST['post_type']) ? $_REQUEST['post_type'] : 'page';

            $paid_post_data = $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, p.post_title FROM {$wpdb->posts} p WHERE  p.post_type = %s AND p.post_status = %s", $post_type, 'publish' ) );
            $paid_post_html = '';
            if (!empty($paid_post_data)) {
                                                
                foreach ($paid_post_data as $k => $postData) {
            
                    $isEnablePaidPost = get_post_meta( $postData->ID, 'arm_is_paid_post', true );
                    if( 0 == $isEnablePaidPost || empty($isEnablePaidPost) ){
                        $paid_post_html .= '<li data-label="' . esc_attr($postData->post_title) .'" data-value="' . esc_attr($postData->ID) .'">' . esc_html($postData->post_title) .'</li>';
                    }
            
                }
            
            }
            echo arm_pattern_json_encode( array('status' => 'success',"paid_post_html" => $paid_post_html));
            die();

        }

        function arm_paid_post_edit_details_func()
        {
            global $ARMember, $arm_capabilities_global,$wp,$wpdb;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_pay_per_post'], '1',1);

            if(!empty($_REQUEST['id']) && $_REQUEST['arm_action'] == 'edit_paid_post')
            {
                
                $post_id = $_REQUEST['id'];
                $post_obj = get_post($post_id);
               

                $paid_post_plan_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_post_id` = %d", $post_id) );

                $paid_post_html = $post_obj->post_title;
                
                $plan_type = isset( $paid_post_plan_data->arm_subscription_plan_type ) ? $paid_post_plan_data->arm_subscription_plan_type : 'paid_infinite';

                $plan_options = isset( $paid_post_plan_data->arm_subscription_plan_options ) ? maybe_unserialize( $paid_post_plan_data->arm_subscription_plan_options ) : array();

                $paid_post_amount = !empty($paid_post_plan_data->arm_subscription_plan_amount) ? $paid_post_plan_data->arm_subscription_plan_amount : 0;

                $enable_ppost_alternate_content = get_post_meta( $post_id, 'arm_enable_paid_post_alternate_content', true );

                $post_alternative_content = get_post_meta( $post_id, 'arm_paid_post_alternative_content', true );
                $response['paid_post_content_type'] = $post_obj -> post_type;
                $response['paid_post_title_name'] = $paid_post_html;
                $response['arm_paid_post_plan'] = $paid_post_amount;
                $response['paid_post_type'] = $plan_type;
                $response['post_options'] = $plan_options;
                $response['enable_ppost_alternate_content'] = $enable_ppost_alternate_content;
                $response['post_alternative_content'] = $post_alternative_content;
                $response['arm_post_id'] = $_REQUEST['id'];
                $response['status'] = 'success';
                $response = apply_filters( 'arm_paid_post_field_section',$response );
                echo arm_pattern_json_encode(array('status'=>'success','response' => $response));
                die();
            }
        }

        function arm_add_edit_paid_post_metabox_html_func($content, $postBlankObj)
        {
            $metabox_obj = array();
            $content = $this->arm_add_paid_post_metabox_html( $postBlankObj, $metabox_obj, true,true );
            return $content;
        }

        function arm_assign_paid_post_to_user($user_ID, $plan_id, $log_id=0, $pgateway='',$is_plan_assigned = 0)
        {
            $plan = new ARM_Plan( $plan_id );

            $plan_options = !empty($plan->options) ? $plan->options : array();
                            
            $is_allow_paid_post_purchase = !empty($plan_options['arm_allow_paid_post_purchase']) ? $plan_options['arm_allow_paid_post_purchase'] : 0;

            $arm_assign_paid_posts_ids = !empty($plan_options['arm_assign_paid_posts']) ? $plan_options['arm_assign_paid_posts'] : array();

            
            if($is_allow_paid_post_purchase && !empty($arm_assign_paid_posts_ids))
            {
                $plan_ids = get_user_meta($user_ID, 'arm_user_plan_ids', true);
                foreach($arm_assign_paid_posts_ids as $paid_post_subscription_id)
                {
                    if(empty($plan_ids) || (is_array($plan_ids) && !in_array($paid_post_subscription_id,$plan_ids)))
                    {
                        do_action( 'arm_apply_plan_to_member', $paid_post_subscription_id, $user_ID, $is_plan_assigned);
                    }
                }
            }
        }

        function display_field_add_membership_plan_page($plan_options)
        {
            $arm_paid_post_settings = $this->arm_get_plan_paid_post_settings_content($plan_options);
            echo $arm_paid_post_settings; //phpcs:ignore
        }

        function arm_get_plan_paid_post_settings_content($plan_options)
        {
            global $wp, $wpdb,$ARMember,$arm_payment_gateways;

            $get_all_paid_post = $wpdb->get_results($wpdb->prepare("SELECT arm_subscription_plan_id,arm_subscription_plan_name FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_is_delete`= %d AND arm_subscription_plan_post_id > %d",0,0),ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name

            $arm_allow_paid_post_purchase = (!empty($plan_options["arm_allow_paid_post_purchase"])) ? $plan_options["arm_allow_paid_post_purchase"] : 0;

            $arm_allow_paid_post_lists = (!empty($plan_options["arm_assign_paid_posts"])) ? $plan_options["arm_assign_paid_posts"] : array();

            $arm_paid_post_assign_title  = esc_html__('Assign Paid Post' ,'ARMember');

            $arm_paid_post_list_title  = esc_html__('Select Paid Posts' ,'ARMember');
            
            $arm_paid_post_settings = esc_html__('Assign Paid Post Settings','ARMember');

            if((is_plugin_active('armembercourses/armembercourses.php')))
            {

                $arm_paid_post_settings = esc_html__('Assign Paid Post/Course Settings','ARMember');

                $arm_paid_post_assign_title  = esc_html__('Assign Paid Post/Course' ,'ARMember');

                $arm_paid_post_list_title = esc_html__('Select Paid Posts/Courses' ,'ARMember');
            }


            $arm_paid_post_plan_settings = '<div class="arm_spacing_div"></div>';
            $arm_paid_post_plan_settings .= '<div class="form-field arm_plan_price_section arm_form_main_content">';
                $arm_paid_post_plan_settings .= '<div class="arm_form_header_label">'. $arm_paid_post_settings . '</div>';
                $arm_paid_post_plan_settings .= '<div id="arm_plan_price_box_content" class="arm_plan_price_box">';
                    $arm_paid_post_plan_settings .= '<div class="page_sub_content">';
                        
                            $arm_page = isset($_REQUEST['page']) ? sanitize_text_field($_REQUEST['page']) : '';
                            $arm_paid_post_plan_settings .= '<input type="hidden" name="page" id="page" value="'. $arm_page .'" />';
                            $arm_paid_post_plan_settings .= '<table class="form-table arm_member_plan_addon_section">';
                            $is_allowed = (!empty($arm_allow_paid_post_purchase)) ? '' : 'hidden_section';
                            $is_allowed_attr = (!empty($arm_allow_paid_post_purchase)) ? 'required' : '';
                            $armpp_isChecked = checked($arm_allow_paid_post_purchase, 1, false);
                            $arm_paid_post_plan_settings .= '<tr class="form-field arm_paid_post_custom_row">';
                                $arm_paid_post_plan_settings .= '<td class="arm_padding_top_0 arm_margin_bottom_8">';
                                    $arm_paid_post_plan_settings .= '<div class="armclear"></div>';
                                    $arm_paid_post_plan_settings .= '<div class="armswitch arm_global_setting_switch" style="vertical-align: middle;">';
                                        $arm_paid_post_plan_settings .= '<input type="checkbox" id="arm_allow_paid_post_purchase" '.$armpp_isChecked.' value="1" class="armswitch_input" name="arm_subscription_plan_options[arm_allow_paid_post_purchase]"/>';
                                        $arm_paid_post_plan_settings .= '<label for="arm_allow_paid_post_purchase" class="armswitch_label" style="min-width:40px;"></label>';
                                    $arm_paid_post_plan_settings .= '</div><label for="arm_allow_paid_post_purchase" class="arm_padding_left_10">'. $arm_paid_post_assign_title . '</label>';
                                    $arm_paid_post_plan_settings .= '<div class="armclear"></div>';
                                $arm_paid_post_plan_settings .= '</td>';
                            $arm_paid_post_plan_settings .= '</tr>';
                            $arm_paid_post_plan_settings .= '<tr class="form-field form-required arm_selected_paid_post_section '.$is_allowed.'">';
                                $arm_paid_post_plan_settings .= '<th class="arm_padding_top_0"><label class="arm_setup_section_title arm_font_size_20 arm_font_weight_500">'. $arm_paid_post_list_title . '</label></th>';
                                $arm_paid_post_plan_settings .= '<td>';
                                $arm_paid_post_plan_settings .= '<div class="multi_select_container">';
                                    $arm_paid_post_plan_settings .= '<div>';
                                        $arm_paid_post_plan_settings .= '<label class="arm-black-350 arm_margin_bottom_13">'.esc_html__('Available Paid Posts','ARMember').'</label>';
                                        $arm_paid_post_plan_settings .= '<div class="list-box" id="available-posts">';
                                            $arm_paid_post_plan_settings .= '<input type="text" value="" placeholder="'.esc_html__('Search Posts','ARMember').'" id="arm_filter_transfer_data_available_posts">';
                                            $arm_paid_post_plan_settings .= '<div class="list-box-inner" id="available-posts-list">';
                                            if (!empty($get_all_paid_post)){
                                                foreach ($get_all_paid_post as $paid_post){
                                                    if(!in_array( $paid_post['arm_subscription_plan_id'], $arm_allow_paid_post_lists) )
                                                    {
                                                        $arm_paid_post_plan_settings .= '<label><input type="checkbox" id="arm_paid_post_plan" class="arm_paid_post_plan arm_icheckbox" value="'. esc_attr($paid_post['arm_subscription_plan_id']).'"><span>'. stripslashes( esc_html($paid_post['arm_subscription_plan_name'])).'</span></label>';
                                                    }
                                                }
                                                $arm_paid_post_plan_settings .= '<label class="arm_empty_label hidden_section">'.esc_html__('No Paid Post Available','ARMember').'</label>';
                                            }
                                            else{
                                                $arm_paid_post_plan_settings .= '<label class="arm_empty_label">'.esc_html__('No Paid Post Available','ARMember').'</label>';
                                            }
                                            $arm_paid_post_plan_settings .= '</div>';
                                        $arm_paid_post_plan_settings .= '</div>';
                                    $arm_paid_post_plan_settings .= '</div>
                                    <div class="buttons">
                                        <button type="button" id="to-available" onclick="selected_fields(\'selected-posts-list\',\'available-posts-list\');"></button>
                                        <button type="button" id="to-selected" onclick="selected_fields(\'available-posts-list\',\'selected-posts-list\');"></button>
                                    </div>';
                                    $arm_paid_post_plan_settings .= '<div>';
                                        $arm_paid_post_plan_settings .= '<label class="arm-black-350 arm_margin_bottom_13">'.esc_html__('Selected Paid Posts','ARMember').'</label>';
                                        $arm_paid_post_plan_settings .= '<div class="list-box" id="selected-posts">';
                                            $arm_paid_post_plan_settings .= '<input type="text" value="" placeholder="'.esc_html__('Search Posts','ARMember').'" id="arm_filter_transfer_data_selected_posts">';
                                            $arm_paid_post_plan_settings .= '<div class="list-box-inner" id="selected-posts-list">';
                                            if (!empty($arm_allow_paid_post_lists) && !empty($get_all_paid_post)){
                                                foreach ($get_all_paid_post as $paid_post){
                                                    if(in_array( $paid_post['arm_subscription_plan_id'], $arm_allow_paid_post_lists) )
                                                    {
                                                        $arm_paid_post_plan_settings .= '<label><input type="checkbox" id="arm_paid_post_plan" class="arm_paid_post_plan arm_icheckbox" checked="checked" value="'. esc_attr($paid_post['arm_subscription_plan_id']).'"><span>'. stripslashes( esc_html($paid_post['arm_subscription_plan_name'])).'</span></label>';
                                                    }
                                                }
                                                $arm_paid_post_plan_settings .= '<label class="arm_empty_label hidden_section">'.esc_html__('No Paid Post Available','ARMember').'</label>';
                                            }
                                            else{
                                                $arm_paid_post_plan_settings .= '<label class="arm_empty_label">'.esc_html__('No Paid Post Available','ARMember').'</label>';
                                            }
                                            $arm_paid_post_plan_settings .= '</div>';
                                        $arm_paid_post_plan_settings .= '</div>';
                                    $arm_paid_post_plan_settings .= '</div>';
                                $arm_paid_post_plan_settings .= '</div">';
                                
                                $arm_paid_post_plan_settings .= '<select id="arm_assign_paid_posts_hidden" class="arm_width_500 hidden_section arm_assign_paid_posts_hidden"  data-msg-required="'. esc_attr__('Please select atleast one or more paid post.', 'ARMember').'" name="arm_subscription_plan_options[arm_assign_paid_posts][]" data-placeholder="'. esc_attr__('Select Paid Post(s)..', 'ARMember').'" multiple="multiple" '.$is_allowed_attr.'>';
                                if (!empty($get_all_paid_post)){
                                    foreach ($get_all_paid_post as $paid_post){
                                        $is_selected = ( in_array( $paid_post['arm_subscription_plan_id'], $arm_allow_paid_post_lists) ) ? ' selected="selected"' : '';
                                        $arm_paid_post_plan_settings .= '<option class="arm_message_selectbox_op" value="'. esc_attr($paid_post['arm_subscription_plan_id']).'" '.$is_selected.'>'. stripslashes( esc_html($paid_post['arm_subscription_plan_name'])).'</option>';
                                    }
                                }
                                $arm_paid_post_plan_settings .= '</select>';
                                $arm_paid_post_plan_settings .= '<span class="error arm_invalid" id="invalid_paid_posts"></span>';
                                $arm_paid_post_plan_settings .= '</td>';
                            $arm_paid_post_plan_settings .= '</tr>';
                        $arm_paid_post_plan_settings .= '</table>';
                    $arm_paid_post_plan_settings .= '</div>';
                $arm_paid_post_plan_settings .= '</div>';
                $arm_paid_post_plan_settings .= '</div>';
            return $arm_paid_post_plan_settings;
        }

        function arm_move_to_trash_paid_post( $post_id ){
            global $ARMember, $wpdb;

            $is_post_exists = $wpdb->get_row( $wpdb->prepare( "SELECT arm_subscription_plan_id FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_post_id` = %d AND `arm_subscription_plan_is_delete` = %d", $post_id, 0 ) ); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name

            if( isset( $is_post_exists->arm_subscription_plan_id ) &&  '' != $is_post_exists->arm_subscription_plan_id ){

                //update_post_meta( $post_id, 'arm_is_paid_post', 0 );
                update_post_meta( $post_id, 'arm_is_enable_paid_post_before_trash', 1);

                $wpdb->update(
                    $ARMember->tbl_arm_subscription_plans,
                    array(
                        'arm_subscription_plan_is_delete' => 1
                    ),
                    array(
                        'arm_subscription_plan_id' => $is_post_exists->arm_subscription_plan_id
                    )
                );
            }
        }

        function arm_move_to_published_paid_post( $post_id ){
            global $ARMember, $wpdb;

            $is_enabled_before = get_post_meta( $post_id, 'arm_is_enable_paid_post_before_trash', true );

            if( 1 == $is_enabled_before ){

                $is_post_exists = $wpdb->get_row( $wpdb->prepare( "SELECT arm_subscription_plan_id FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_post_id` = %d AND `arm_subscription_plan_is_delete` = %d", $post_id, 1 ) );//phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name

                if( '' != $is_post_exists->arm_subscription_plan_id ){
                    update_post_meta( $post_id, 'arm_is_paid_post', 1 );
                    delete_post_meta( $post_id, 'arm_is_enable_paid_post_before_trash' );

                    $wpdb->update(
                        $ARMember->tbl_arm_subscription_plans,
                        array(
                            'arm_subscription_plan_is_delete' => 0
                        ),
                        array(
                            'arm_subscription_plan_id' => $is_post_exists->arm_subscription_plan_id
                        )
                    );
                }

            }
        }

        function arm_add_pay_per_post_script_data(){

            if( in_array( basename( sanitize_text_field( $_SERVER['PHP_SELF']) ), array( 'post.php', 'post-new.php' ) ) ){//phpcs:ignore
                wp_enqueue_script( 'arm_validate', MEMBERSHIP_URL . '/js/jquery.validate.min.js', array('jquery'), MEMBERSHIP_VERSION );
            }

            if( isset( $_GET['page'] ) && 'arm_manage_pay_per_post' == $_GET['page']  && (!empty($_GET['action']))){
                $this->arm_add_paid_post_metabox_script_data();
            }
        }

        function arm_add_paid_post_metabox_script_data(){
            
            global $arm_payment_gateways;

            $global_currency = $arm_payment_gateways->arm_get_global_currency();
            $all_currencies = $arm_payment_gateways->arm_get_all_currencies();
            $global_currency_sym = isset($all_currencies) ? $all_currencies[strtoupper($global_currency)] : '';
            $global_currency_sym = apply_filters('arm_admin_membership_plan_currency_format', $global_currency_sym, strtoupper($global_currency)); //phpcs:ignore
            $global_currency_sym_pos = $arm_payment_gateways->arm_currency_symbol_position($global_currency);
            $global_currency_sym_pos_pre = (!empty($global_currency_sym_pos) && $global_currency_sym_pos == 'prefix' ? '' : 'hidden_section');
            $global_currency_sym_pos_suf = (!empty($global_currency_sym_pos) && $global_currency_sym_pos == 'suffix' ? '' : 'hidden_section');

            $script_data  = 'var CYCLEAMOUNT = "'.esc_html__('Amount', 'ARMember').'";
            var BILLINGCYCLE = "'.esc_html__('Billing Cycle', 'ARMember').'";
            var ARMCYCLELABEL = "'.esc_html__('Label', 'ARMember').'";
            var RECURRINGTIME = "'.esc_html__('Recurring Time', 'ARMember').'";
            var AMOUNTERROR = "'.esc_html__('Amount should not be blank..','ARMember').'";
            var LABELERROR = "'.esc_html__('Label should not be blank..','ARMember').'";
            var DAY = "'.esc_html__('Day(s)', 'ARMember').'";
            var MONTH = "'.esc_html__('Month(s)', 'ARMember').'";
            var YEAR = "'.esc_html__('Year(s)', 'ARMember').'";
            var INFINITE = "'.esc_html__('Infinite', 'ARMember').'";
            var EMESSAGE = "'.esc_html__('You cannot remove all payment cycles.', 'ARMember').'";
            var ARMREMOVECYCLE = "'.esc_html__('Remove Cycle', 'ARMember').'";
            var CURRENCYPREF = "'.$global_currency_sym_pos_pre.'";
            var CURRENCYSUF = "'.$global_currency_sym_pos_suf.'";
            var CURRENCYSYM_LBL = "'.strtoupper($global_currency).'";
            var CURRENCYSYM = "'.$global_currency_sym.'";
            var ARM_RR_CLOSE_IMG = "'.MEMBERSHIPLITE_IMAGES_URL.'/arm_close_icon.png";
            var ARM_RR_CLOSE_IMG_HOVER = "'.MEMBERSHIPLITE_IMAGES_URL.'/arm_close_icon_hover.png";
            var ADDCYCLE = "'.esc_html__('Add Payment Cycle', 'ARMember').'";
            var REMOVECYCLE = "'.esc_html__('Remove Payment Cycle', 'ARMember').'";
            var INVALIDAMOUNTERROR = "'.esc_html__('Please enter valid amount','ARMember').'";
            var ARMEDITORNOTICELABEL = "'.esc_html__('ARMember settings','ARMember').': ";';

            if( function_exists( 'wp_add_inline_script' ) ){
                wp_add_inline_script( 'arm_tinymce', $script_data, 'after' );
            } else {
                echo '<script>' . $script_data . '</script>'; //phpcs:ignore
            }
        }

        function arm_add_paid_post_metabox( $post_type, $post ){
            $arm_page_action = !empty($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : ''; //phpcs:ignore
            if($arm_page_action!= 'editcomment'){
                $this->arm_add_paid_post_metabox_script_data();

                add_meta_box(
                    'arm_paid_post_metabox_wrapper',
                     esc_html__( 'ARMember Paid Post Settings', 'ARMember' ),
                     array( $this,'arm_add_paid_post_metabox_callback'), 
                     $post_type,
                     'normal',
                     'high',
                     array(
                         '__block_editor_compatible_meta_box' => true,
                    )
                );
            }
            

        }

        function arm_get_plan_expiry_time( $posted_data ){

            $final_expiry_time = '';

            if( 'buy_now' == $posted_data['paid_post_type'] && 'fixed_duration' == $posted_data['paid_post_duration'] ){
                $duration_type = $posted_data['arm_paid_plan_one_time_duration']['type'];

                $duration_d_time = $posted_data['arm_paid_plan_one_time_duration']['days'];
                $duration_w_time = $posted_data['arm_paid_plan_one_time_duration']['week'];
                $duration_m_time = $posted_data['arm_paid_plan_one_time_duration']['month'];
                $duration_y_time = $posted_data['arm_paid_plan_one_time_duration']['year'];


                if( 'd' == $duration_type ){
                    $timestamp = '+' . $duration_d_time . ' day';
                } else if( 'w' == $duration_type ){
                    $timestamp = '+' . $duration_w_time . ' week';   
                } else if( 'w' == $duration_type ){
                    $timestamp = '+' . $duration_m_time . ' month';
                } else if( 'w' == $duration_type ){
                    $timestamp = '+' . $duration_y_time . ' year';
                } else {
                    $timestamp = '+' . $duration_d_time . ' days';
                }

                $final_expiry_time = date( 'Y-m-d', strtotime( $timestamp ) ) . ' 23:59:59';
            }

            return $final_expiry_time;
        }

        function arm_add_update_paid_post() {
            global $ARMember,$arm_capabilities_global;
            if( isset( $_POST['arm_action'] ) && 'arm_add_update_paid_post_plan' == $_POST['arm_action'] ){//phpcs:ignore
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_pay_per_post'], '1',1);
                $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST ); //phpcs:ignore
                $_POST['arm_enable_paid_post'] = 1;
                if( isset( $posted_data['arm_post_action'] ) && 'edit_paid_post' == $posted_data['arm_post_action'] ){
                    $selected_post = isset( $posted_data['edit_paid_post_id'] ) ? $posted_data['edit_paid_post_id'] : '';
                    $this->arm_save_paid_post_metabox( $selected_post );
                    $this->arm_update_access_rule_for_paid_post( $selected_post );
                    $message = addslashes( esc_html__('Paid Post updated successfully', 'ARMember') );
                    $response['type'] = 'success';
                    $response['status'] = 'success';
                    $response['message'] = esc_html($message);

                    $metabox_obj = array();
                    $postBlankObj = array();
                    // $form_data_html = $this->arm_add_paid_post_metabox_html( $postBlankObj, $metabox_obj, false,true );
                    // $response['form_data_html'] = $form_data_html;
                    echo arm_pattern_json_encode($response);
                    die;
                } else {
                    $post_id = isset( $posted_data['arm_paid_post_items_input'] ) ? $posted_data['arm_paid_post_items_input'] : 0;
                    if(!empty($post_id))
                    {
                        $this->arm_save_paid_post_metabox( $post_id );
                        $this->arm_update_access_rule_for_paid_post( $post_id );
                        $message = addslashes( esc_html__('Paid Post added successfully', 'ARMember') );
                        $response['type'] = 'success';
                        $response['status'] = 'success';
                        $response['message'] = esc_html($message);
    
                        $metabox_obj = array();
                        $postBlankObj = array();
                    }
                    // $form_data_html = $this->arm_add_paid_post_metabox_html( $postBlankObj, $metabox_obj, false,true );
                    // $response['form_data_html'] = $form_data_html;
                    echo arm_pattern_json_encode($response);
                    die;
                }
            }
        }

        function arm_update_access_rule_for_paid_post( $post_id ){

            $isEnablePaidPost = get_post_meta( $post_id, 'arm_is_paid_post', true );

            if( 1 == $isEnablePaidPost ){

                global $ARMember, $wpdb;
                $hasAccessRule = get_post_meta( $post_id, 'arm_access_plan', false );
                $getRow = $wpdb->get_row( $wpdb->prepare( "SELECT arm_subscription_plan_id FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_post_id` = %d", $post_id ) );  //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name

                if( isset( $getRow->arm_subscription_plan_id ) && '' != $getRow->arm_subscription_plan_id ){

                    $plan_id = $getRow->arm_subscription_plan_id;

                    if( isset( $plan_id ) &&  isset( $hasAccessRule ) && is_array( $hasAccessRule ) && in_array( '0', $hasAccessRule ) && !in_array( $plan_id, $hasAccessRule) ){
                        add_post_meta( $post_id, 'arm_access_plan', $plan_id );
                    }
                }


            }

        }

        function arm_save_paid_post_metabox($post_id, $post = array(), $update=false){

            global $ARMember,$wpdb;
            if( empty( $_POST ) ){//phpcs:ignore
                return;
            }

            if (!isset($_POST['arm_enable_paid_post_hidden']) && ( empty($_REQUEST['page']) ) ) {//phpcs:ignore
                //Special condition for WP All Import plugin.
                return;
            }

            if( array_key_exists('arm_enable_paid_post', $_POST ) && ! wp_is_post_revision( $post_id ) ){//phpcs:ignore

                update_post_meta( $post_id, 'arm_is_paid_post', 1 );

                $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST ); //phpcs:ignore

                $enable_alternative_content = isset( $posted_data['arm_enable_paid_post_alternate_content'] ) ? $posted_data['arm_enable_paid_post_alternate_content'] : '';
                update_post_meta( $post_id, 'arm_enable_paid_post_alternate_content', $enable_alternative_content );

                if( !empty( $enable_alternative_content )) {
                    
                    if ( !empty($posted_data['arm_paid_post_alternative_content-edit']) ) {
                        $post_alternative_content = isset( $posted_data['arm_paid_post_alternative_content-edit'] ) ? $posted_data['arm_paid_post_alternative_content-edit'] : '';
                    } else {                        
                        $post_alternative_content = isset( $posted_data['arm_paid_post_alternative_content'] ) ? $posted_data['arm_paid_post_alternative_content'] : '';
                    }
                    
                    update_post_meta( $post_id, 'arm_paid_post_alternative_content', $post_alternative_content );
                }

                $plan_type = '';
                $plan_options = array();
                $plan_name = isset( $posted_data['post_title'] ) ? $posted_data['post_title'] : '';

                if( empty($plan_name) ){
                    $postObj = get_post( $post_id );
                    $plan_name = $postObj->post_title;
                }


                $plan_options['pricetext'] = $plan_name;

                if($this->isPayPerPostFeatureLimit == true){
                    $plan_options['limit'] = !empty($posted_data['limit']) ? intval($posted_data['limit']) : 0;
                }

                if( 'buy_now' == $posted_data['paid_post_type'] ){
                    
                    $expiry_date = $this->arm_get_plan_expiry_time( $posted_data );

                    if( 'forever' == $posted_data['paid_post_duration'] ){
                        $plan_type = 'paid_infinite';
                        $plan_options['access_type'] = 'lifetime';
                        $plan_options['payment_type'] = 'one_time';
                    } else {
                        $plan_type = 'paid_finite';
                        $plan_options['access_type'] = 'finite';
                        $plan_options['payment_type'] = 'one_time';
                        $plan_options['expiry_type'] = 'joined_date_expiry';
                        $plan_options['eopa'] = array(
                            'days' => $posted_data['arm_paid_plan_one_time_duration']['days'],
                            'weeks' => $posted_data['arm_paid_plan_one_time_duration']['week'],
                            'months' => $posted_data['arm_paid_plan_one_time_duration']['month'],
                            'years' => $posted_data['arm_paid_plan_one_time_duration']['year'],
                            'type' => $posted_data['arm_paid_plan_one_time_duration']['type']
                        );

                        if( '' != $expiry_date ){
                            $plan_options['expiry_date']  = $expiry_date;
                        }
                        $plan_options['eot'] = 'block';
                        $plan_options['grace_period'] = array(
                            'end_of_term' => 0,
                            'failed_payment' => 0
                        );

                        $plan_options['upgrade_action'] = 'immediate';
                        $plan_options['downgrade_action'] = 'on_expire';
                    }
                    $plan_amount = isset( $posted_data['arm_paid_post_plan'] ) ? $posted_data['arm_paid_post_plan'] : '';
                } else if( 'subscription' == $posted_data['paid_post_type'] ){
                    $plan_type = 'recurring';
                    $plan_options['access_type'] = 'finite';
                    $plan_options['payment_type'] = 'subscription';

                    if( !empty($posted_data['arm_paid_post_subscription_plan_options']['payment_cycles']) ){
                        $plan_options['payment_cycles'] = array_values( $posted_data['arm_paid_post_subscription_plan_options']['payment_cycles'] );
                    } else {
                        $plan_options['payment_cycles'] = array();
                    }

                    $plan_options['trial'] = array(
                        'amount' => 0,
                        'days' => 1,
                        'months' => 1,
                        'years' => 1,
                        'type' => 'D'
                    );


                    $arm_paid_post_data = $plan_options['payment_cycles'][0];

                    $arm_post_days = 1;
                    $arm_post_months = 1;
                    $arm_post_years = 1;

                    if($arm_paid_post_data['billing_type'] == 'D')
                    {
                        $arm_post_days = $arm_paid_post_data['billing_cycle'];
                    }
                    else if($arm_paid_post_data['billing_type'] == 'D')
                    {
                        $arm_post_months = $arm_paid_post_data['billing_cycle'];
                    }
                    else
                    {
                        $arm_post_years = $arm_paid_post_data['billing_cycle'];
                    }

                    //$plan_options['recurring'] = $arm_paid_post_data;
                    $plan_options['recurring'] = array(
                        'days'                 => $arm_post_days,
                        'months'               => $arm_post_months,
                        'years'                => $arm_post_years,
                        'type'                 => $arm_paid_post_data['billing_type'],
                        'time'                 => $arm_paid_post_data['recurring_time'],
                        'manual_billing_start' => 'transaction_day',
                    );
                    $plan_options['cancel_action'] = 'block';
                    $plan_options['cancel_plan_action'] = 'immediate';
                    $plan_options['eot'] = 'block';
                    $plan_options['grace_period'] = array(
                        'end_of_term' => 0,
                        'failed_payment' => 0
                    );

                    $plan_options['payment_failed_action'] = 'block';
                    $plan_options['upgrade_action'] = 'immediate';
                    $plan_options['downgrade_action'] = 'on_expire';
                    $plan_amount = isset( $arm_paid_post_data['cycle_amount'] ) ? $arm_paid_post_data['cycle_amount'] : '';
                } else if( 'free' == $posted_data['paid_post_type'] ){
                    $plan_type = 'free';
                    $plan_options['access_type'] = 'lifetime';
                    $plan_options['payment_type'] = 'one_time';
		    $plan_amount = 0;
                }

                $status = 1;
                $plan_role = 'armember';

                $arm_subscription_plan_created_date = current_time('mysql');
                $post_data_array = array(
                    'arm_subscription_plan_name' => $plan_name,
                    'arm_subscription_plan_type' => $plan_type,
                    'arm_subscription_plan_options' => maybe_serialize( $plan_options ),
                    'arm_subscription_plan_amount' => $plan_amount,
                    'arm_subscription_plan_status' => $status,
                    'arm_subscription_plan_role'    => $plan_role,
                    'arm_subscription_plan_post_id' => $post_id,
                    'arm_subscription_plan_is_delete' => 0,
                    'arm_subscription_plan_created_date' => $arm_subscription_plan_created_date
                );
                $post_data_array = apply_filters('arm_admin_paid_post_change_data_external', $post_data_array,$posted_data);

                $is_post_exists = $wpdb->get_row( $wpdb->prepare( "SELECT arm_subscription_plan_id FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_post_id` = %d", $post_id ) ); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name

                if( isset( $is_post_exists->arm_subscription_plan_id ) && '' != $is_post_exists->arm_subscription_plan_id ){
                    $wpdb->update(
                        $ARMember->tbl_arm_subscription_plans,
                        $post_data_array,
                        array(
                            'arm_subscription_plan_id' => $is_post_exists->arm_subscription_plan_id
                        )
                    );
                    $plan_id = $is_post_exists->arm_subscription_plan_id;
                } else {

                    $wpdb->insert(
                        $ARMember->tbl_arm_subscription_plans,
                        $post_data_array
                    );
                    $plan_id = $wpdb->insert_id;
                }

            } else {
                update_post_meta( $post_id, 'arm_is_paid_post', 0 );

                $is_post_exists = $wpdb->get_row( $wpdb->prepare( "SELECT arm_subscription_plan_id FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_post_id` = %d", $post_id ) ); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name
                if(!empty($is_post_exists))
                {
                    if( '' != $is_post_exists->arm_subscription_plan_id ){

                        delete_post_meta( $post_id, 'arm_access_plan', $is_post_exists->arm_subscription_plan_id );

                        $wpdb->update(
                            $ARMember->tbl_arm_subscription_plans,
                            array(
                                'arm_subscription_plan_is_delete' => 1
                            ),
                            array(
                                'arm_subscription_plan_id' => $is_post_exists->arm_subscription_plan_id
                            )
                        );
                    }
                }
            }

        }

        function arm_add_paid_post_plan_for_restriction( $all_plans, $post_id ){

            global $wpdb, $ARMember;

            $isEnablePaidPost = get_post_meta( $post_id, 'arm_is_paid_post', true );

            if( 1 == $isEnablePaidPost ){

                $isRestricted = get_post_meta( $post_id, 'arm_access_plan', true );

                $getPlanId = $this->arm_get_plan_from_post_id( $post_id );


                if( '0' == $isRestricted && !empty( $getPlanId ) ){
                    $plan_id = $getPlanId;

                    if( empty($all_plans) ){
                        $all_plans = $plan_id;
                    } else {

                        $all_plans .= ',' . $plan_id;

                    }

                }

            }

            return $all_plans;

        }

        function arm_add_paid_post_metabox_callback( $post_obj, $metabox_data ){

            return $this->arm_admin_add_edit_paid_post_metabox_html_func( $post_obj, $metabox_data );
        }

        function arm_admin_add_edit_paid_post_metabox_html_func( $post_obj, $metabox_data, $paid_post_page = false, $return = false ) {

            global $arm_payment_gateways,$ARMember,$wpdb, $arm_global_settings;

            /* Add CSS for Metaboxes */
            if(!wp_style_is( 'arm_lite_post_metabox_css', 'enqueued' ) && defined('MEMBERSHIPLITE_URL')){
                wp_enqueue_style('arm_lite_post_metabox_css', MEMBERSHIPLITE_URL . '/css/arm_post_metaboxes.css', array(), MEMBERSHIP_VERSION);
            }
            wp_enqueue_style('arm_post_metaboxes_css', MEMBERSHIP_URL . '/css/arm_post_metaboxes.css', array(), MEMBERSHIP_VERSION);

            $global_currency = $arm_payment_gateways->arm_get_global_currency();
            $all_currencies = $arm_payment_gateways->arm_get_all_currencies();
            $global_currency_sym = isset($all_currencies) ? $all_currencies[strtoupper($global_currency)] : '';
            $global_currency_sym = apply_filters('arm_admin_membership_plan_currency_format', $global_currency_sym, strtoupper($global_currency)); //phpcs:ignore
            $global_currency_sym_pos = $arm_payment_gateways->arm_currency_symbol_position($global_currency);
            $global_currency_sym_pos_pre = ( !empty( $global_currency_sym_pos ) && $global_currency_sym_pos == 'prefix' ? '' : 'hidden_section' );
            $global_currency_sym_pos_suf = ( !empty( $global_currency_sym_pos ) && $global_currency_sym_pos == 'suffix' ? '' : 'hidden_section' );
            $arm_currency_pos_class = ($global_currency_sym_pos == 'suffix') ? 'arm_curr_sym_suff' : 'arm_curr_sym_pref';

            $payment_cycles_data = array();

            $post_id = isset( $post_obj->ID ) ? $post_obj->ID : '';

            $is_paid_post_enabled = get_post_meta( $post_id, 'arm_is_paid_post', true );

            $total_paid_post_setups = $this->arm_get_paid_post_setup();

            if( $total_paid_post_setups < 1 && ! $paid_post_page ){

                $paid_post_html = '<div class="arm_paid_post_container">';

                    $arm_setup_link = admin_url( 'admin.php?page=arm_membership_setup&action=new_setup' );

                    $paid_post_html .= '<div class="arm_paid_post_notice">'. sprintf( esc_html__( 'You don\'t have created paid post type membership setup. Please create at least one membership setup for paid post from %s and then reload this page.', 'ARMember' ), '<a href="'.$arm_setup_link.'">here</a>' ).'</div>';//phpcs:ignore

                $paid_post_html .= '</div>';

                echo $paid_post_html; //phpcs:ignore

                return;
            }

            if( $post_id ){
                $paid_post_plan_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_post_id` = %d", $post_id) ); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name
            } else {
                $paid_post_plan_data = new stdClass();
            }

            $plan_type = isset( $paid_post_plan_data->arm_subscription_plan_type ) ? $paid_post_plan_data->arm_subscription_plan_type : 'paid_infinite';

            $plan_options = isset( $paid_post_plan_data->arm_subscription_plan_options ) ? maybe_unserialize( $paid_post_plan_data->arm_subscription_plan_options ) : array();

            if( isset( $plan_options['payment_cycles'] ) && !empty( $plan_options['payment_cycles'] ) ){
                $payment_cycles_data = $plan_options['payment_cycles'];
            }

            if( !isset( $payment_cycles_data ) || empty( $payment_cycles_data ) ){
                $payment_cycles_data[] = array(
                    'cycle_key' => 'arm0',
                    'cycle_label' => '',
                    'cycle_amount' => '',
                    'billing_cycle' => 1,
                    'billing_type' => 'D',
                    'recurring_time' => 'infinite',
                    'payment_cycle_order' => 1
                );
            }
            $arm_paid_post_action_attr='';
            $paid_post_html  = '<div class="arm_paid_post_container">';
                
                if( !$paid_post_page ){
                    $arm_paid_post_action_attr=' data-arm_is_post_page="1"';
                    $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_no_margin">';

                        $paid_post_html .= '<div class="arm_paid_post_row_left arm_width_auto">' . esc_html__('Enable Pay Per Post', 'ARMember') . '</div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right">';

                            $paid_post_html .= '<input type="hidden" value="'.esc_attr($is_paid_post_enabled).'" name="arm_enable_paid_post_hidden" id="arm_enable_paid_post_hidden" />';

                            $paid_post_html .= '<div class="armswitch armswitchbig">';

                                $enable_paid_post = checked( 1, $is_paid_post_enabled, false );
                                
                                $paid_post_html .= '<input type="checkbox" value="1" '.esc_attr($enable_paid_post).' class="armswitch_input" name="arm_enable_paid_post" id="arm_enable_paid_post" />';

                                $paid_post_html .= '<label for="arm_enable_paid_post" class="armswitch_label"></label>';

                                $paid_post_html .= '<div class="armclear"></div>';

                            $paid_post_html .= '</div>';

                        $paid_post_html .= '</div>';

                    $paid_post_html .= '</div>';
                }

                $arm_show_paid_post_container = 'hidden_section';

                if( $is_paid_post_enabled || $paid_post_page ){
                    $arm_show_paid_post_container = '';
                }
                $paid_post_html .= '<div class="arm_paid_post_items_list_container" id="arm_paid_post_items_list_container"></div>';
                $paid_post_html .= '<div class="arm_paid_post_inner_container '.esc_attr($arm_show_paid_post_container).'">';

                    if( $paid_post_page ){

                        $paid_post_html .= '<div class="arm_form_main_content">';
                            $paid_post_html .= '<div class="arm_form_header_label">'. esc_html__('Content Type','ARMember').'</div>';

                        

                            $paid_post_html .= '<div class="arm_paid_post_row arm_edit_paid_post_section hidden_section">';

                                $paid_post_html .= '<div class="arm_paid_post_row_left">' . esc_html__( 'Paid Post Title', 'ARMember' ) . '</div>';

                                $paid_post_html .= '<div class="arm_paid_post_row_right arm_page_post_title"></div>';

                            $paid_post_html .= '</div>';
                       

                            $paid_post_html .= '<div class="arm_paid_post_row arm_add_paid_post_section">';

                                $paid_post_html .= '<div class="arm_paid_post_row_left"><label class="arm-black-350">' . esc_html__('Select Content Type', 'ARMember') . '</label></div>';

                                $paid_post_html .= '<div class="arm_paid_post_row_right arm_width_33_pct">';

                                    $post_type = isset( $post_obj->post_type ) ? $post_obj->post_type : 'page';

                                    $PaidPostContentTypes = array('page' => esc_html__('Page', 'ARMember'), 'post' => esc_html__('Post', 'ARMember'));

                                    $custom_post_types = get_post_types(array('public' => true, '_builtin' => false, 'show_ui' => true), 'objects');

                                    if( !in_array( $post_type, array( 'page', 'post' ) ) ){
                                        $post_type_label = $custom_post_types[$post_type]->label;
                                    } else {
                                        $post_type_label = $PaidPostContentTypes[$post_type];
                                    }

                                    $paid_post_html .= '<input type="hidden" id="arm_add_paid_post_item_type" class="arm_paid_post_item_type_input" name="arm_add_paid_post_item_type" data-type="'.esc_attr($post_type_label).'" value="'.esc_attr($post_type).'"/>';

                                    $paid_post_html .= '<dl class="arm_selectbox arm_width_100_pct">';

                                        $paid_post_html .= '<dt><span>'.$post_type_label.'</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                        $paid_post_html .= '<dd>';

                                            $paid_post_html .= '<ul data-id="arm_add_paid_post_item_type">';
                                                
                                                if (!empty($custom_post_types)) {
                                                
                                                    foreach ($custom_post_types as $cpt) {
                                                
                                                        $PaidPostContentTypes[$cpt->name] = $cpt->label;
                                                
                                                    }
                                                
                                                }

                                                if (!empty($PaidPostContentTypes)) {

                                                    foreach ($PaidPostContentTypes as $key => $val) {

                                                        $paid_post_html .= '<li data-label="' . esc_attr($val) .'" data-value="' . esc_attr($key) .'" data-type="' . esc_attr($val) .'">' . esc_html($val) .'</li>';

                                                    }
                                                }

                                            $paid_post_html .= '</ul>';

                                        $paid_post_html .= '</dd>';

                                    $paid_post_html .= '</dl>';

                                $paid_post_html .= '</div>';

                                $paid_post_html .= '</div>';

                                $paid_post_html .= '<div class="arm_margin_top_0"></div>';

                                $paid_post_html .= '<div class="arm_paid_post_row arm_add_paid_post_section arm_padding_bottom_40">';

                                $paid_post_data = $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, p.post_title FROM {$wpdb->posts} p WHERE p.post_type = %s AND p.post_status = %s", $post_type, 'publish' ) );

                                

                                $paid_post_html .= '<div class="arm_paid_post_row_left arm_paid_post_row_select_post_type_label arm_paid_post_row_float_left arm_padding_top_0 arm-black-350 arm_font_size_16">' . esc_html__('Select', 'ARMember'). ' <span class="arm_paid_post_item_type_text">' . $post_type_label .'</span> *</div>';

                                $paid_post_html .= '<div class="arm_paid_post_row_right arm_width_33_pct">';
                                
                                $paid_post_html .= '<input type="hidden" id="arm_paid_post_items_input" class="arm_paid_post_items_input arm-selectpicker-input-control" name="arm_paid_post_items_input" value="" data-msg-required="'. esc_attr__('Select a paid post','ARMember') . '"/>';

                                    $paid_post_html .= '<dl class="arm_selectbox arm_paid_post_selection arm_width_100_pct arm_margin_0">';

                                        $paid_post_html .= '<dt><span>'.esc_html__('Select Paid Post','ARMember').'</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                        $paid_post_html .= '<dd>';

                                            $paid_post_html .= '<ul data-id="arm_paid_post_items_input">';
                                                $paid_post_html .= '<li data-label="' . esc_html__('Select Paid Post','ARMember') .'" data-value="">' . esc_html__('Select Paid Post','ARMember') .'</li>';
                                                if (!empty($paid_post_data)) {
                                                
                                                    foreach ($paid_post_data as $k => $postData) {
                                                
                                                        $isEnablePaidPost = get_post_meta( $postData->ID, 'arm_is_paid_post', true );
                                                        if( 0 == $isEnablePaidPost || empty($isEnablePaidPost) ){
                                                            $paid_post_html .= '<li data-label="' . esc_attr($postData->post_title) .'" data-value="' . esc_attr($postData->ID) .'">' . esc_html($postData->post_title) .'</li>';
                                                        }
                                                
                                                    }
                                                
                                                }

                                            $paid_post_html .= '</ul>';

                                        $paid_post_html .= '</dd>';

                                    $paid_post_html .= '</dl>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';
                        $paid_post_html .= '</div>';

                        

                    }
                    $paid_post_html .= ' <div class="arm_spacing_div"></div>';
                    $paid_post_html .= '<div class="arm_form_main_content">';
                        $paid_post_html .= '<div class="arm_setup_section_title arm_form_header_label">'. esc_html__('Post Type','ARMember').'</div>
                        <div class="arm_paid_post_row arm_margin_bottom_0">';

                        $paid_post_html .= '<div class="arm_paid_post_row_left arm_padding_top_0 arm_padding_bottom_20 arm-black-350 arm_font_size_16">' . esc_html__('Setup Type', 'ARMember') . '</div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right">';
                            $free_post_checked = '';
                            if( 'free' == $plan_type ){
                                $free_post_checked =  ' checked="checked" ';
                            }

                            $paid_post_html .= '<input type="radio" '.$free_post_checked.' name="paid_post_type" id="arm_paid_free_post" value="free" class="arm_iradio" />';

                            $paid_post_html .= '<label class="form_popup_type_radio arm_padding_right_0 arm_margin_right_45" for="arm_paid_free_post">';

                                $paid_post_html .= '&nbsp;'.esc_html__('Free Post','ARMember');

                            $paid_post_html .= '</label>';

                            $paid_post_html .= '&nbsp;';
                            

                            $buy_now_checked = '';
                            if( 'paid_infinite' == $plan_type || 'paid_finite' == $plan_type ){
                                $buy_now_checked = ' checked="checked" ';
                            }

                            $paid_post_html .= '<input type="radio" '.$buy_now_checked.' name="paid_post_type" id="arm_paid_post_buynow" value="buy_now" class="arm_iradio" />';

                            $paid_post_html .= '<label class="form_popup_type_radio arm_margin_right_0" for="arm_paid_post_buynow">';

                                $paid_post_html .= '&nbsp;'.esc_html__('Buy Now','ARMember');

                            $paid_post_html .= '</label>';

                            $paid_post_html .= '&nbsp;';

                            $subscription_checked = '';

                            if( 'recurring' == $plan_type ){
                                $subscription_checked =  ' checked="checked" ';
                            }

                            $paid_post_html .= '<input type="radio" '.$subscription_checked.' name="paid_post_type" id="arm_paid_post_subscription" value="subscription" class="arm_iradio" />';

                            $paid_post_html .= '<label class="form_popup_type_radio" for="arm_paid_post_subscription">';

                                $paid_post_html .= '&nbsp;'.esc_html__('Subscription/Recurring Post','ARMember');

                            $paid_post_html .= '</label>';

                        $paid_post_html .= '</div>';

                    $paid_post_html .= '</div>';

                    $show_paid_post_amount = ' hidden_section ';
                    $show_paid_post_duration = ' hidden_section ';
                    if( empty($plan_type) || 'paid_infinite' == $plan_type || 'paid_finite' == $plan_type ){
                        $show_paid_post_amount = '';
                        $show_paid_post_duration = '';
                    }
                    $paid_post_html .='<div class="arm_post_type_sections">';
                    $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_one_time_amount arm_sub_amout arm_padding_bottom_28'.$show_paid_post_amount.'">';
                        $paid_post_html .= '<div class="arm_paid_post_row_left arm_padding_0 arm_margin_bottom_12"><label class=" arm-black-350 arm_font_size_16 arm_padding_0 arm_font_wight_500">' . esc_html__( 'Paid Post Amount', 'ARMember' ) . ' *</label></div>';
                        $paid_post_html .= '<div class="arm_paid_post_row_right arm_display_flex">';     
                        
                            $paid_post_html .= '<span class="arm_paid_post_plan_currency_symbol arm_paid_post_plan_currency_symbol_post arm_plan_currency_symbol_prefix '.esc_attr($global_currency_sym_pos_pre).'">' . $global_currency_sym . '</span>';

                            $paid_post_html .= '<input type="text" name="arm_paid_post_plan" class="arm_paid_post_input_field arm_max_width_32_pct arm_padding_right_20 '.$arm_currency_pos_class.'" value="'. ( isset( $paid_post_plan_data->arm_subscription_plan_amount ) ? esc_attr($paid_post_plan_data->arm_subscription_plan_amount) : '' ).'"  onkeypress="javascript:return ArmNumberValidation(event, this)" data-msg-required="'. esc_attr__('Amount should not be blank.','ARMember') . '"/>';

                            $paid_post_html .= '<span class="arm_paid_post_plan_currency_symbol arm_paid_post_plan_currency_symbol_post arm_plan_currency_symbol_suffix '.esc_attr($global_currency_sym_pos_suf).'">' . $global_currency_sym . '</span>';

                        $paid_post_html .= '</div>';

                    $paid_post_html .= '</div>';

                    $arm_add_plan_amount_html_for_paid_plan = '';
                    $arm_plan_type_paid_post = 'paid_post';
                    $arm_payment_cycle_num = '';
                    $plan_data = (array)$paid_post_plan_data;
                    $paid_post_html .= apply_filters('arm_admin_membership_plan_html_after_amount', $arm_add_plan_amount_html_for_paid_plan, $plan_data, $arm_plan_type_paid_post, $arm_payment_cycle_num);

                    $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_plan_one_time_duration arm_sub_amout arm_padding_top_0 arm_margin_bottom_0'.$show_paid_post_duration.' ">';

                        $paid_post_html .= '<div class="arm_paid_post_row_left arm_margin_top_0 arm_padding_bottom_20">' . esc_html__('Duration Type','ARMember') . '</div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right">';

                            $checked_forever_type = '';
                            if( !isset( $plan_options['access_type'] ) || ( isset( $plan_options['access_type'] ) && 'lifetime' == $plan_options['access_type'] ) ){
                                $checked_forever_type = ' checked="checked" ';
                            }

                            $paid_post_html .= '<input type="radio" name="paid_post_duration" id="arm_paid_post_duration_lifetime" value="forever" class="arm_iradio" ' . $checked_forever_type . ' />';

                            $paid_post_html .= '<label class="form_popup_type_radio" for="arm_paid_post_duration_lifetime">';
                                $paid_post_html .= '&nbsp;'.esc_html__('Lifetime','ARMember');

                            $paid_post_html .= '</label>';

                            $paid_post_html .= '&nbsp;';

                            $checked_fixed_duration_type = '';
                            if( isset( $plan_options['access_type'] ) && 'finite' == $plan_options['access_type'] ){
                                $checked_fixed_duration_type = ' checked="checked" ';
                            }

                            $paid_post_html .= '<input type="radio" name="paid_post_duration" id="arm_paid_post_duration_fixed" value="fixed_duration" class="arm_iradio" ' . $checked_fixed_duration_type . ' />';

                            $paid_post_html .= '<label class="form_popup_type_radio" for="arm_paid_post_duration_fixed">';

                                $paid_post_html .= '&nbsp;'.esc_html__('Fixed Duration','ARMember');

                            $paid_post_html .= '</label>';

                        $paid_post_html .= '</div>';

                    $paid_post_html .= '</div>';

                    $arm_show_fixed_duration = ' hidden_section ';
                    if( isset( $plan_options['access_type'] ) && 'finite' == $plan_options['access_type'] && 'recurring' != $plan_type ){
                        $arm_show_fixed_duration = '';
                    }

                    $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_plan_one_time_duration_value arm_sub_amout arm_padding_top_28 arm_margin_bottom_0 '.$arm_show_fixed_duration.'">'; 

                        $paid_post_html .= '<div class="arm_paid_post_row_left arm_margin_top_0">' . esc_html__('Duration', 'ARMember') . '</div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right">';

                            $arm_show_day_duration = ' hidden_section ';
                            if( ! isset( $plan_options['eopa']['type'] ) || ( isset( $plan_options['eopa']['type'] ) && 'D' == $plan_options['eopa']['type'] ) ){
                                $arm_show_day_duration = '';
                            }

                            $paid_post_html .= '<div class="arm_paid_post_duration_dropdown arm_paid_post_duration_d ' . $arm_show_day_duration . '">';

                                $paid_post_html .= '<div class="arm_paid_post_selectbox_wrapper">';

                                    $paid_post_html .= '<input type="hidden" name="arm_paid_plan_one_time_duration[days]" value="' . ( isset( $plan_options['eopa']['days'] ) ? $plan_options['eopa']['days'] : 1 ) . '" id="arm_paid_plan_one_time_duration_d" />';

                                    $paid_post_html .= '<dl class="arm_selectbox arm_width_100">';

                                        $paid_post_html .= '<dt><span>' . ( isset( $plan_options['eopa']['days'] ) ? $plan_options['eopa']['days'] : 1 ) . '</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                        $paid_post_html .= '<dd>';
                                                    
                                            $paid_post_html .= '<ul data-id="arm_paid_plan_one_time_duration_d">';
                                                
                                                for ($i = 1; $i <= 90; $i++) {
                                                    $paid_post_html .= '<li data-label="' . esc_attr($i) . '" data-value="'. esc_attr($i) . '">'. esc_html($i). '</li>';
                                                }

                                            $paid_post_html .= '</ul>';

                                        $paid_post_html .= '</dd>';

                                    $paid_post_html .= '</dl>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                            $arm_show_week_duration = ' hidden_section ';
                            if( isset( $plan_options['eopa']['type'] ) && 'W' == $plan_options['eopa']['type'] ){
                                $arm_show_week_duration = '';
                            }
                            $paid_post_html .= '<div class="arm_paid_post_duration_dropdown arm_paid_post_duration_w ' . $arm_show_week_duration . '">';

                                $paid_post_html .= '<div class="arm_paid_post_selectbox_wrapper">';

                                    $paid_post_html .= '<input type="hidden" name="arm_paid_plan_one_time_duration[week]" value="' . ( isset( $plan_options['eopa']['weeks'] ) ? $plan_options['eopa']['weeks'] : 1 ) . '" id="arm_paid_plan_one_time_duration_w" />';

                                    $paid_post_html .= '<dl class="arm_selectbox arm_width_100">';

                                        $paid_post_html .= '<dt><span>' . ( isset( $plan_options['eopa']['weeks'] ) ? $plan_options['eopa']['weeks'] : 1 ) . '</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                        $paid_post_html .= '<dd>';
                                                    
                                            $paid_post_html .= '<ul data-id="arm_paid_plan_one_time_duration_w">';
                                                
                                                for ($i = 1; $i <= 52; $i++) {
                                                    $paid_post_html .= '<li data-label="' . esc_attr($i) . '" data-value="'. esc_attr($i) . '">'. esc_html($i). '</li>';
                                                }

                                            $paid_post_html .= '</ul>';

                                        $paid_post_html .= '</dd>';

                                    $paid_post_html .= '</dl>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                            $arm_show_month_duration = ' hidden_section ';
                            if( isset( $plan_options['eopa']['type'] ) && 'M' == $plan_options['eopa']['type'] ){
                                $arm_show_month_duration = '';
                            }

                            $paid_post_html .= '<div class="arm_paid_post_duration_dropdown arm_paid_post_duration_m ' . $arm_show_month_duration . '">';

                                $paid_post_html .= '<div class="arm_paid_post_selectbox_wrapper">';

                                    $paid_post_html .= '<input type="hidden" name="arm_paid_plan_one_time_duration[month]" value="' . ( isset( $plan_options['eopa']['months'] ) ? $plan_options['eopa']['months'] : 1 ) . '" id="arm_paid_plan_one_time_duration_m" />';

                                    $paid_post_html .= '<dl class="arm_selectbox arm_width_100">';

                                        $paid_post_html .= '<dt><span>' . ( isset( $plan_options['eopa']['months'] ) ? $plan_options['eopa']['months'] : 1 ) . '</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                        $paid_post_html .= '<dd>';
                                                    
                                            $paid_post_html .= '<ul data-id="arm_paid_plan_one_time_duration_m">';
                                                
                                                for ($i = 1; $i <= 24; $i++) {
                                                    $paid_post_html .= '<li data-label="' . esc_attr($i) . '" data-value="'. esc_attr($i) . '">'. esc_html($i). '</li>';
                                                }

                                            $paid_post_html .= '</ul>';

                                        $paid_post_html .= '</dd>';

                                    $paid_post_html .= '</dl>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                            $arm_show_year_duration = ' hidden_section';
                            if( isset( $plan_options['eopa']['type'] ) && 'Y' == $plan_options['eopa']['type'] ){
                                $arm_show_year_duration = '';
                            }

                            $paid_post_html .= '<div class="arm_paid_post_duration_dropdown arm_paid_post_duration_y ' . $arm_show_year_duration . '">';

                                $paid_post_html .= '<div class="arm_paid_post_selectbox_wrapper">';

                                    $paid_post_html .= '<input type="hidden" name="arm_paid_plan_one_time_duration[year]" value="' . ( isset( $plan_options['eopa']['years'] ) ? $plan_options['eopa']['years'] : 1 ) . '" id="arm_paid_plan_one_time_duration_y" />';

                                    $paid_post_html .= '<dl class="arm_selectbox arm_width_100">';

                                        $paid_post_html .= '<dt><span>' . ( isset( $plan_options['eopa']['years'] ) ? $plan_options['eopa']['years'] : 1 ) . '</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                        $paid_post_html .= '<dd>';
                                                    
                                            $paid_post_html .= '<ul data-id="arm_paid_plan_one_time_duration_y">';
                                                
                                                for ($i = 1; $i <= 15; $i++) {
                                                    $paid_post_html .= '<li data-label="' . esc_attr($i) . '" data-value="'. esc_attr($i) . '">'. esc_html($i) . '</li>';
                                                }

                                            $paid_post_html .= '</ul>';

                                        $paid_post_html .= '</dd>';

                                    $paid_post_html .= '</dl>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                            $paid_post_html .= '&nbsp;';

                            $paid_post_html .= '<div class="arm_paid_post_duration_dropdown">';

                                $paid_post_html .= '<div class="arm_paid_post_selectbox_wrapper">';

                                    $paid_post_html .= '<input type="hidden" name="arm_paid_plan_one_time_duration[type]" value="' . ( isset( $plan_options['eopa']['type'] )? $plan_options['eopa']['type'] : 'D' ) . '" id="arm_paid_plan_one_time_duration_type" />';

                                    $paid_post_html .= '<dl class="arm_selectbox arm_width_130">';

                                        $arm_paid_post_duration_label = esc_html__( 'Day(s)', 'ARMember' );
                                        if( isset( $plan_options['eopa']['type'] ) && 'W' == $plan_options['eopa']['type'] ){
                                            $arm_paid_post_duration_label = esc_html__( 'Week(s)', 'ARMember' );
                                        } else if( isset( $plan_options['eopa']['type'] ) && 'M' == $plan_options['eopa']['type'] ){
                                            $arm_paid_post_duration_label = esc_html__( 'Month(s)', 'ARMember' );
                                        } else if( isset( $plan_options['eopa']['type'] ) && 'Y' == $plan_options['eopa']['type'] ){
                                            $arm_paid_post_duration_label = esc_html__( 'Year(s)', 'ARMember' );
                                        }

                                        $paid_post_html .= '<dt><span>' . $arm_paid_post_duration_label . '</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                        $paid_post_html .= '<dd>';

                                            $paid_post_html .= '<ul data-id="arm_paid_plan_one_time_duration_type">';

                                                $paid_post_html .= '<li data-label="'.esc_attr__( 'Day(s)', 'ARMember' ).'" data-value="D">'. esc_html__( 'Day(s)', 'ARMember' ) .'</li>';
                                                $paid_post_html .= '<li data-label="'.esc_attr__( 'Week(s)', 'ARMember' ).'" data-value="W">'. esc_html__( 'Week(s)', 'ARMember' ) .'</li>';
                                                $paid_post_html .= '<li data-label="'.esc_attr__( 'Month(s)', 'ARMember' ).'" data-value="M">'. esc_html__( 'Month(s)', 'ARMember' ) .'</li>';
                                                $paid_post_html .= '<li data-label="'.esc_attr__( 'Year(s)', 'ARMember' ).'" data-value="Y">'. esc_html__( 'Year(s)', 'ARMember' ) .'</li>';

                                            $paid_post_html .= '</ul>';

                                        $paid_post_html .= '</dd>';

                                    $paid_post_html .= '</dl>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                        $paid_post_html .= '</div>';

                    $paid_post_html .= '</div>';

                    $arm_show_payment_cycles = ' hidden_section ';
                    if( 'recurring' == $plan_type ){
                        $arm_show_payment_cycles = '';
                    }

                    $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_plan_subscription_cycle arm_sub_amout ' . $arm_show_payment_cycles . '">';

                        $paid_post_html .= '<div class="arm_paid_post_row_left arm_padding_0  arm_margin_bottom_18 arm_paid_post_row_float_left arm-black-600">' . esc_html__( 'Payment Cycles', 'ARMember' ) . ' </div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right arm_margin_bottom_0">';

                            $paid_post_html .= '<div class="paid_subscription_options_recurring_payment_cycles_main_box_label">
                                <div class="arm_display_grid arm_payment_cycle_grid">
                                    <label class="arm_plan_payment_cycle_label_text">'.esc_html__('Label', 'ARMember').'</label>
                                    <label class="arm_plan_payment_cycle_amount_text">' . esc_html__( 'Amount', 'ARMember' ) . '</label>
                                </div>
                                <label class="arm_plan_payment_cycle_billing_text">' . esc_html__( 'Billing Cycle', 'ARMember' ) . '</label>
                                <label class="arm_plan_payment_cycle_recurring_text">' . esc_html__('Recurring Time', 'ARMember') . '</label>
                                <label class="arm_plan_payment_cycle_recurring_text arm_plan_payment_cycle_label_text"></label>
                            </div>';

                            $paid_post_html .= '<div class="arm_paid_post_subscription_options_recurring_payment_cycles_main_box">';

                                $paid_post_html .= '<ul class="arm_plan_payment_cycle_ul">';

                                    $total_inirecurring_cycle = count($payment_cycles_data); 
                                    $gi = 1;
                                    foreach( $payment_cycles_data as $arm_pc => $arm_value ){

                                        $paid_post_html .= '<li class="arm_plan_payment_cycle_li paid_subscription_options_recurring_payment_cycles_child_box" id="paid_subscription_options_recurring_payment_cycles_child_box'. esc_attr($arm_pc).'">';

                                        $paid_post_html .= '<div class="arm_display_grid arm_payment_cycle_grid">';
                                            $paid_post_html .= '<div class="arm_plan_payment_cycle_label">';
                                                $paid_post_html .= '<div class="arm_plan_payment_cycle_label_input arm_padding_top_12">';

                                                    $paid_post_html .= '<input type="hidden" name="arm_paid_post_subscription_plan_options[payment_cycles]['.esc_attr($arm_pc).'][cycle_key]" value="' . ( !empty($arm_value['cycle_key']) ? esc_attr($arm_value['cycle_key']) : 'arm'.rand() ). '"/>';

                                                    $paid_post_html .= '<input type="text" class="arm_paid_post_input_field paid_subscription_options_recurring_payment_cycle_label" name="arm_paid_post_subscription_plan_options[payment_cycles]['.esc_attr($arm_pc).'][cycle_label]" data-msg-required="'.esc_attr__('Label should not be blank', 'ARMember').'" value="' . ( !empty($arm_value['cycle_label']) ? esc_attr(stripslashes_deep($arm_value['cycle_label'])) : '' ). '" />';

                                                $paid_post_html .= '</div>';

                                            $paid_post_html .= '</div>';

                                            $paid_post_html .= '<div class="arm_plan_payment_cycle_amount">';

                                                $paid_post_html .= '<div class="arm_plan_payment_cycle_amount_input arm_padding_top_12">';           
                                                
                                                    $paid_post_html .= '<span class="arm_plan_currency_symbol arm_plan_currency_symbol_prefix '.esc_attr($global_currency_sym_pos_pre).'">' . $global_currency_sym . '</span>';

                                                    $paid_post_html .= '<input type="text" name="arm_paid_post_subscription_plan_options[payment_cycles]['.$arm_pc.'][cycle_amount]" value="'. (!empty($arm_value['cycle_amount']) ? esc_attr($arm_value['cycle_amount']) : '' ).'" class="paid_subscription_options_recurring_payment_cycle_amount arm_paid_post_input_field '. $arm_currency_pos_class.'" data-msg-required="'.esc_attr__('Amount should not be blank.', 'ARMember').'" onkeypress="javascript:return ArmNumberValidation(event, this)"/>';

                                                    $paid_post_html .= '<span class="arm_paid_post_plan_currency_symbol arm_paid_post_plan_currency_symbol_post arm_plan_currency_symbol_suffix arm_margin_top_0 '.esc_attr($global_currency_sym_pos_suf).'">' . $global_currency_sym . '</span>';

                                                $paid_post_html .= '</div>';

                                            $paid_post_html .= '</div>';
                                            $paid_post_html .= '</div>';

                                            $arm_add_plan_amount_html_for_plan = '';
                                            $arm_plan_type_paid_post = 'recurring';
                                            $paid_post_html .= apply_filters('arm_admin_membership_plan_html_after_amount', $arm_add_plan_amount_html_for_plan, $plan_data, $arm_plan_type_paid_post, $arm_pc);

                                            $paid_post_html .= '<div class="arm_plan_payment_cycle_billing_cycle">';

                                                $paid_post_html .= '<div class="arm_plan_payment_cycle_billing_input arm_display_grid arm_grid_col_2">';

                                                    $paid_post_html .= '<div class="arm_form_fields_wrapper  arm_plan_payment_cycle_billing_input arm_padding_top_12">
                                                        <div class="arm-df__form-field-wrap_select arm-df__form-field-wrap arm-controls" id="arm-df__form-field-wrap_arm_ipc_billing'.$arm_pc.'">';

                                                    $paid_post_html .= '<input type="text" class="arm-selectpicker-input-control"  id="arm_ipc_billing'.$arm_pc.'" name="arm_paid_post_subscription_plan_options[payment_cycles]['.esc_attr($arm_pc).'][billing_cycle]" value="'.(!empty($arm_value['billing_cycle']) ? esc_attr($arm_value['billing_cycle']) : 1).'" />';

                                                    $paid_post_html .= '<dl class="arm_selectbox arm_margin_0  arm_min_width_50 arm_width_100_pct">';

                                                        $paid_post_html .= '<dt><span>'.( !empty( $arm_value['billing_cycle'] ) ? $arm_value['billing_cycle'] : 1 ).'</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';
                                                            
                                                        $paid_post_html .= '<dd>';

                                                            $paid_post_html .= '<ul data-id="arm_ipc_billing' . $arm_pc . '">';
                                                                
                                                                for ($i = 1; $i <= 90; $i++) {

                                                                    $paid_post_html .= '<li data-label="' . esc_attr($i) .'" data-value="' . esc_attr($i) . '">' . esc_html($i) . '</li>';
                                                                
                                                                }
                                                                 
                                                            $paid_post_html .= '</ul>';

                                                        $paid_post_html .= '</dd>';

                                                    $paid_post_html .= '</dl>
                                                        </div>
                                                    </div>';                                                   

                                                    $paid_post_html .= '
                                                    <div class="arm_form_fields_wrapper  arm_plan_payment_cycle_billing_input arm_padding_top_12">

																<div class="arm-df__form-field-wrap_select arm-df__form-field-wrap arm-controls " id="arm-df__form-field-wrap_arm_ipc_billing_type'.$arm_pc.'">
                                                    
                                                        <input type="text" class="arm-selectpicker-input-control"  id="arm_ipc_billing_type'.$arm_pc.'" name="arm_paid_post_subscription_plan_options[payment_cycles]['.esc_attr($arm_pc).'][billing_type]" value="' . ( !empty( $arm_value['billing_type'] ) ? esc_attr($arm_value['billing_type']) : "D" ) . '" />';

                                                        $paid_post_html .= '<dl class="arm_selectbox arm_margin_0 arm_min_width_75 arm_width_100_pct">';

                                                            $paid_post_html .= '<dt class="arm_width_100_pct"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                                            $paid_post_html .= '<dd>';

                                                                $paid_post_html .= '<ul data-id="arm_ipc_billing_type'.$arm_pc.'">';

                                                                    $paid_post_html .= '<li data-label="'.esc_attr__('Day(s)', 'ARMember').'" data-value="D">'.esc_html__('Day(s)', 'ARMember').'</li>';
                                                                    $paid_post_html .= '<li data-label="'.esc_attr__('Month(s)', 'ARMember').'" data-value="M">'.esc_html__('Month(s)', 'ARMember').'</li>';
                                                                    $paid_post_html .= '<li data-label="'.esc_attr__('Year(s)', 'ARMember').'" data-value="Y">'.esc_html__('Year(s)', 'ARMember').'</li>';

                                                                $paid_post_html .= '</ul>';

                                                            $paid_post_html .= '</dd>';

                                                        $paid_post_html .= '</dl>';
                                                    $paid_post_html .= '</div>';

                                                    $paid_post_html .= '</div>';

                                                $paid_post_html .= '</div>';

                                            $paid_post_html .= '</div>';

                                            $paid_post_html .= '<div class="arm_plan_payment_cycle_recurring_time">';

                                                $paid_post_html .= '
                                                <div class="arm_form_fields_wrapper  arm_plan_payment_cycle_billing_input arm_padding_top_12">

																<div class="arm-df__form-field-wrap_select arm-df__form-field-wrap arm-controls " id="arm-df__form-field-wrap_arm_ipc_recurring'.$arm_pc.'">';

                                                $paid_post_html .= '<input type="text" class="arm-selectpicker-input-control" id="arm_ipc_recurring'.$arm_pc.'" name="arm_paid_post_subscription_plan_options[payment_cycles]['.esc_attr($arm_pc).'][recurring_time]" value="' . (!empty($arm_value['recurring_time']) ? esc_attr($arm_value['recurring_time']) : 'infinite' ).'" />';

                                                $paid_post_html .= '<dl class="arm_selectbox arm_margin_0 arm_width_100_pct">';

                                                    $paid_post_html .= '<dt class="arm_width_100_pct"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                                    $paid_post_html .= '<dd>';

                                                        $paid_post_html .= '<ul data-id="arm_ipc_recurring' . esc_attr($arm_pc) . '">';

                                                            $paid_post_html .= '<li data-label="' . esc_attr__('Infinite', 'ARMember') . '" data-value="infinite">' . esc_html__('Infinite', 'ARMember') . '</li>';

                                                            for ($i = 2; $i <= 30; $i++) {

                                                                $paid_post_html .= '<li data-label="' . esc_attr($i) . '" data-value="'. esc_attr($i) . '">' . esc_html($i) . '</li>';

                                                            }

                                                        $paid_post_html .= '</ul>';

                                                    $paid_post_html .= '</dd>';

                                                $paid_post_html .= '</dl>';
                                                $paid_post_html .= '</div>';
                                            $paid_post_html .= '</div>';

                                            $paid_post_html .= '</div>';
                                            $arm_paid_post_action_cls ='';
                                            if(empty($arm_paid_post_action_attr))
                                            {
                                                $arm_paid_post_action_cls ='hidden_section';
                                            }
                                            $paid_post_html .= '<div class="arm_plan_payment_cycle_action_buttons '.$arm_paid_post_action_cls.'">';
                                                
                                                $paid_post_html .= '<div class="arm_plan_cycle_plus_icon arm_helptip_icon tipso_style arm_add_plan_icon" title="'.esc_attr__('Add Payment Cycle','ARMember').'" id="arm_admin_add_payment_cycle_recurring" '.$arm_paid_post_action_attr.' data-field_index="'. ( isset($total_inirecurring_cycle) ? esc_attr($total_inirecurring_cycle) : 1 ).'"></div>';

                                                $paid_post_html .= '<div class="arm_plan_cycle_minus_icon arm_helptip_icon tipso_style arm_add_plan_icon arm_remove_user_plan_icon2 arm_margin_0" '.$arm_paid_post_action_attr.' title="'.esc_attr__('Remove Payment Cycle', 'ARMember').'" id="arm_remove_recurring_payment_cycle" data_index="'. ( isset($total_inirecurring_cycle) ? esc_attr($total_inirecurring_cycle) : 1 ).'"></div>';
                                                
                                                $paid_post_html .= '<div class="arm_plan_cycle_sortable_icon ui-sortable-handle"></div>';
                                                        
                                            $paid_post_html .= '</div>';

                                            $paid_post_html .= '<input type="hidden" name="arm_paid_post_subscription_plan_options[payment_cycles]['.esc_attr($arm_pc).'][payment_cycle_order]" value="'.esc_attr($gi).'" class="arm_module_payment_cycle_order" />';

                                        $paid_post_html .= '</li>';


                                        $gi++;
                                    }

                                $paid_post_html .= '</ul>';

                                $paid_post_html .= '<div class="paid_subscription_options_recurring_payment_cycles_link">';

                                    $paid_post_html .= '<input type="hidden" name="arm_total_recurring_plan_cycles" id="arm_total_recurring_plan_cycles" value="1"/>';

                                    $paid_post_html .= '<input type="hidden" name="arm_total_recurring_plan_cycles" id="arm_total_recurring_plan_cycles_order" value="2"/>';

                                    $paid_post_html .= '<input type="hidden" name="arm_total_recurring_plan_cycles_counter" id="arm_total_recurring_plan_cycles_counter" value="1"/>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                        $paid_post_html .= '</div>';

                        $paid_post_html .= '</div>';
                    $paid_post_html .= '</div>';
                    $paid_post_html .= '</div>';

                    if($this->isPayPerPostFeatureLimit == true && $paid_post_page)
                    {

                        $paid_post_html .= '<div class="arm_spacing_div"></div>';
                        $paid_post_html .= '<div class="arm_form_main_content">';
                            $paid_post_html .= '<div class="arm_setup_section_title arm_form_header_label">'. esc_html__('Paid Post purchase limit','ARMember').'</div>';
                            $paid_post_html .= '<div id="arm_paid_post_row" class="arm_paid_post_row arm_paid_post_plan_limit">';
                                $paid_post_html .= '<div class="arm_paid_post_row_left arm_paid_post_row_float_left arm_padding_0 arm_margin_bottom_12">' . esc_html__('Purchase limit', 'ARMember') . '</div>';
                                $paid_post_html .= '<div class="arm_paid_post_row_right arm_width_33_pct">';
                                $plan_purchase_limit = !empty($plan_options['limit']) ? intval($plan_options['limit']) : 0;
                                    $paid_post_html .= '<input type="text" name="limit" value="'. esc_attr($plan_purchase_limit).'" class="arm_no_paste arm_width_170 arm_margin_bottom_12" data-msg-required="Label should not be blank." wfd-id="id28" aria-invalid="false" onkeypress="javascript:return ArmNumberValidation(event, this)">';
                                    $paid_post_html .= '<span class="arm_subscription_limit_note" style="left:0">'.esc_html__('Leave blank or 0 for unlimited paid post purchases.','ARMember').'</span>';
                                $paid_post_html .= '</div>';
                            $paid_post_html .= '</div>';
                        $paid_post_html .= '</div>';
                    }
                    $paid_post_html .= '<div class="arm_spacing_div"></div>';

                    $paid_post_html .= '<div class="arm_form_main_content">';

                        $paid_post_html .= '<div class="arm_setup_section_title arm_form_header_label">'. esc_html__('Alternative Content','ARMember').'</div>
                        <span class="arm_info_text arm_paid_post_info_text arm-gray-500 arm_font_size_14 arm_margin_bottom_24 arm_padding_0">'. esc_html__("Display alternative content to the member who has not buy this post. If this disable then default content will be displayed from ARMember -> General Settings -> Paid Post Settings page.","ARMember"). '</span>';
                    
                        $paid_post_html .= '<div class="arm_paid_post_row">';

                            $paid_post_html .= '<div class="arm_paid_post_row_left arm_padding_0">';
                            $enable_ppost_alternate_content = get_post_meta( $post_id, 'arm_enable_paid_post_alternate_content', true );

                                $checked_paid_post_alt_content = checked( 1, $enable_ppost_alternate_content, false );

                                $paid_post_html .= '<div class="armswitch armswitchbig arm_display_flex arm_margin_left_0">';

                                $alternate_switch_text = '';

                                if( isset( $_GET['action'] ) && 'edit_paid_post' == $_GET['action'] ){
                                    $alternate_switch_text = '-edit';
                                }
                                
                                $paid_post_html .= '<input type="checkbox" value="1" ' . $checked_paid_post_alt_content . ' class="armswitch_input" name="arm_enable_paid_post_alternate_content" id="arm_enable_ppost_alternate_content'.esc_attr($alternate_switch_text).'" />';

                                $paid_post_html .= '<label for="arm_enable_ppost_alternate_content'.esc_attr($alternate_switch_text).'" class="armswitch_label arm_margin_left_0"></label><label for="arm_enable_ppost_alternate_content'.esc_attr($alternate_switch_text).'" class="arm_padding_left_10 arm_font_size_16 arm-black-600 ">' . esc_html__( 'Alternative Content', 'ARMember' ). '</label></div>';

                        
                            $paid_post_html .= '</div>';


                        $paid_post_html .= '</div>';

                    $arm_show_ppost_alt_content_wrapper = ' hidden_section ';

                    if( !empty($enable_ppost_alternate_content) ){
                        $arm_show_ppost_alt_content_wrapper = '';
                    }

                    $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_row_alternate_content_row arm_margin_top_0 arm_padding_bottom_40' . $arm_show_ppost_alt_content_wrapper . '">';

                        $paid_post_html .= '<div class="arm_paid_post_row_left arm_paid_post_row_float_left arm_padding_bottom_16">' . esc_html__('Enter Alternative Content', 'ARMember') . '</div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right arm_paid_post_row_alternative_content_wrapper arm_width_80_pct">';

                            $arm_alternate_arr_settings = array(
                                'media_buttons' => true,
                                'textarea_rows' => 15,
                                'default_editor' => 'html'
                            );

                            $arm_pp_alt_content_id = '';
                            if( isset( $_GET['arm_action'] ) && 'edit_paid_post' == $_GET['arm_action'] ) {
                                $arm_pp_alt_content_id = '-edit';
                            }

                            $post_alternative_content = get_post_meta( $post_id, 'arm_paid_post_alternative_content', true );

                            $arm_global_settings_general_settings_default_content = !empty($arm_global_settings->global_settings['arm_pay_per_post_default_content']) ? stripslashes($arm_global_settings->global_settings['arm_pay_per_post_default_content']) : esc_html__('Content is Restricted. Buy this post to get access to full content.', 'ARMember');

                            if( !empty( $arm_show_ppost_alt_content_wrapper ) && empty( $post_alternative_content ) ){
                                if( !empty( $arm_global_settings_general_settings_default_content ) ){
                                    $post_alternative_content = $arm_global_settings_general_settings_default_content;
                                }
                            } else if( empty( $post_alternative_content ) )
                            {
                                $post_alternative_content = $arm_global_settings_general_settings_default_content;
                            }

                            ob_start();

                            wp_editor(stripslashes_deep($post_alternative_content), 'arm_paid_post_alternative_content' . $arm_pp_alt_content_id, $arm_alternate_arr_settings);

                            $paid_post_alternate_content_editor = ob_get_clean();

                            $paid_post_html .= $paid_post_alternate_content_editor;

                        $paid_post_html .= '</div>';

                    $paid_post_html .= '</div>';

                $paid_post_html .= '</div>';

            $paid_post_html .= '</div>';
            $paid_post_html .= '</div>';

            $paid_post_html = apply_filters('arm_display_field_add_paid_post', $paid_post_html);

            $paid_post_html .= '<script>
                                jQuery(document).on("change", "#arm_enable_paid_post", function(e) {
                                    if(jQuery(this).is(":checked")) {
                                        jQuery("#arm_enable_paid_post_hidden").val(1);
                                    } else {
                                        jQuery("#arm_enable_paid_post_hidden").val(0);
                                    }
                                    
                                });
                            </script>';

            if( $return ){
                return $paid_post_html;
            } else {
                echo $paid_post_html;//phpcs:ignore --Reason $paid_post_html contains HTML code
            }

        }

        function arm_add_paid_post_metabox_html( $post_obj, $metabox_data, $paid_post_page = false, $return = false ) {

            global $arm_payment_gateways,$ARMember,$wpdb, $arm_global_settings;

            /* Add CSS for Metaboxes */
            if(!wp_style_is( 'arm_lite_post_metabox_css', 'enqueued' ) && defined('MEMBERSHIPLITE_URL')){
                wp_enqueue_style('arm_lite_post_metabox_css', MEMBERSHIPLITE_URL . '/css/arm_post_metaboxes.css', array(), MEMBERSHIP_VERSION);
            }
            wp_enqueue_style('arm_post_metaboxes_css', MEMBERSHIP_URL . '/css/arm_post_metaboxes.css', array(), MEMBERSHIP_VERSION);

            $global_currency = $arm_payment_gateways->arm_get_global_currency();
            $all_currencies = $arm_payment_gateways->arm_get_all_currencies();
            $global_currency_sym = isset($all_currencies) ? $all_currencies[strtoupper($global_currency)] : '';
            $global_currency_sym = apply_filters('arm_admin_membership_plan_currency_format', $global_currency_sym, strtoupper($global_currency)); //phpcs:ignore
            $global_currency_sym_pos = $arm_payment_gateways->arm_currency_symbol_position($global_currency);
            $global_currency_sym_pos_pre = ( !empty( $global_currency_sym_pos ) && $global_currency_sym_pos == 'prefix' ? '' : 'hidden_section' );
            $global_currency_sym_pos_suf = ( !empty( $global_currency_sym_pos ) && $global_currency_sym_pos == 'suffix' ? '' : 'hidden_section' );

            $payment_cycles_data = array();

            $post_id = isset( $post_obj->ID ) ? $post_obj->ID : '';

            $is_paid_post_enabled = get_post_meta( $post_id, 'arm_is_paid_post', true );

            $total_paid_post_setups = $this->arm_get_paid_post_setup();

            if( $total_paid_post_setups < 1 && ! $paid_post_page ){

                $paid_post_html = '<div class="arm_paid_post_container">';

                    $arm_setup_link = admin_url( 'admin.php?page=arm_membership_setup&action=new_setup' );

                    $paid_post_html .= '<div class="arm_paid_post_notice">'. sprintf( esc_html__( 'You don\'t have created paid post type membership setup. Please create at least one membership setup for paid post from %s and then reload this page.', 'ARMember' ), '<a href="'.$arm_setup_link.'">here</a>' ).'</div>';//phpcs:ignore

                $paid_post_html .= '</div>';

                echo $paid_post_html; //phpcs:ignore

                return;
            }

            if( $post_id ){
                $paid_post_plan_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_post_id` = %d", $post_id) ); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name
            } else {
                $paid_post_plan_data = new stdClass();
            }

            $plan_type = isset( $paid_post_plan_data->arm_subscription_plan_type ) ? $paid_post_plan_data->arm_subscription_plan_type : 'paid_infinite';

            $plan_options = isset( $paid_post_plan_data->arm_subscription_plan_options ) ? maybe_unserialize( $paid_post_plan_data->arm_subscription_plan_options ) : array();

            if( isset( $plan_options['payment_cycles'] ) && !empty( $plan_options['payment_cycles'] ) ){
                $payment_cycles_data = $plan_options['payment_cycles'];
            }

            if( !isset( $payment_cycles_data ) || empty( $payment_cycles_data ) ){
                $payment_cycles_data[] = array(
                    'cycle_key' => 'arm0',
                    'cycle_label' => '',
                    'cycle_amount' => '',
                    'billing_cycle' => 1,
                    'billing_type' => 'D',
                    'recurring_time' => 'infinite',
                    'payment_cycle_order' => 1
                );
            }
           
            $paid_post_html  = '<div class="arm_paid_post_container">';

                if( !$paid_post_page ){

                    $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_no_margin">';

                        $paid_post_html .= '<div class="arm_paid_post_row_left">' . esc_html__('Enable Pay Per Post', 'ARMember') . '</div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right">';

                            $paid_post_html .= '<input type="hidden" value="'.esc_attr($is_paid_post_enabled).'" name="arm_enable_paid_post_hidden" id="arm_enable_paid_post_hidden" />';

                            $paid_post_html .= '<div class="armswitch armswitchbig">';

                                $enable_paid_post = checked( 1, $is_paid_post_enabled, false );
                                
                                $paid_post_html .= '<input type="checkbox" value="1" '.esc_attr($enable_paid_post).' class="armswitch_input" name="arm_enable_paid_post" id="arm_enable_paid_post" />';

                                $paid_post_html .= '<label for="arm_enable_paid_post" class="armswitch_label"></label>';

                                $paid_post_html .= '<div class="armclear"></div>';

                            $paid_post_html .= '</div>';

                        $paid_post_html .= '</div>';

                    $paid_post_html .= '</div>';
                }

                $arm_show_paid_post_container = 'hidden_section';

                if( $is_paid_post_enabled || $paid_post_page ){
                    $arm_show_paid_post_container = '';
                }
                $paid_post_html .= '<div class="arm_paid_post_items_list_container" id="arm_paid_post_items_list_container"></div>';
                $paid_post_html .= '<div class="arm_paid_post_inner_container '.esc_attr($arm_show_paid_post_container).'">';

                    if( $paid_post_page ){

                        

                            $paid_post_html .= '<div class="arm_paid_post_row">';

                                $paid_post_html .= '<div class="arm_paid_post_row_left">' . esc_html__( 'Paid Post Title', 'ARMember' ) . '</div>';

                                $paid_post_html .= '<div class="arm_paid_post_row_right">&nbsp;&nbsp;' . $post_obj->post_title . '</div>';

                            $paid_post_html .= '</div>';

                        

                            $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_content_type">';

                                $paid_post_html .= '<div class="arm_paid_post_row_left">' . esc_html__('Content Type', 'ARMember') . '</div>';

                                $paid_post_html .= '<div class="arm_paid_post_row_right arm_width_33_pct">';

                                    $post_type = isset( $post_obj->post_type ) ? $post_obj->post_type : 'page';

                                    $PaidPostContentTypes = array('page' => esc_html__('Page', 'ARMember'), 'post' => esc_html__('Post', 'ARMember'));

                                    $custom_post_types = get_post_types(array('public' => true, '_builtin' => false, 'show_ui' => true), 'objects');

                                    if( !in_array( $post_type, array( 'page', 'post' ) ) ){
                                        $post_type_label = $custom_post_types[$post_type]->label;
                                    } else {
                                        $post_type_label = $PaidPostContentTypes[$post_type];
                                    }

                                    $paid_post_html .= '<input type="hidden" id="arm_add_paid_post_item_type" class="arm_paid_post_item_type_input" name="arm_add_paid_post_item_type" data-type="'.esc_attr($post_type_label).'" value="'.esc_attr($post_type).'"/>';

                                    $paid_post_html .= '<dl class="arm_selectbox arm_width_100_pct">';

                                        $paid_post_html .= '<dt><span>'.$post_type_label.'</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                        $paid_post_html .= '<dd>';

                                            $paid_post_html .= '<ul data-id="arm_add_paid_post_item_type">';
                                                
                                                if (!empty($custom_post_types)) {
                                                
                                                    foreach ($custom_post_types as $cpt) {
                                                
                                                        $PaidPostContentTypes[$cpt->name] = $cpt->label;
                                                
                                                    }
                                                
                                                }

                                                if (!empty($PaidPostContentTypes)) {

                                                    foreach ($PaidPostContentTypes as $key => $val) {

                                                        $paid_post_html .= '<li data-label="' . esc_attr($val) .'" data-value="' . esc_attr($key) .'" data-type="' . esc_attr($val) .'">' . esc_html($val) .'</li>';

                                                    }
                                                }

                                            $paid_post_html .= '</ul>';

                                        $paid_post_html .= '</dd>';

                                    $paid_post_html .= '</dl>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                            $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_select_page_post">';

                                $paid_post_html .= '<div class="arm_paid_post_row_left arm_paid_post_row_select_post_type_label arm_paid_post_row_float_left">' . esc_html__('Select', 'ARMember'). ' <span class="arm_paid_post_item_type_text">' . $post_type_label .'</span> *</div>';

                                $paid_post_html .= '<div class="arm_paid_post_row_right arm_width_33_pct">';

                                    $paid_post_html .= '<div class="arm_text_align_center" style="width: 100%;"><img src="'.MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif" id="arm_loader_img_paid_post_items" class="arm_loader_img_paid_post_items" style="display: none;" width="20" height="20" /></div>';
                                    $paid_post_html .= '<input id="arm_paid_post_items_input" type="text" value="" placeholder="'. esc_attr__( 'Search by title...', 'ARMember').'" required data-msg-required="'.esc_attr__('Please select atleast one page/post.', 'ARMember').'" />';

                                    $paid_post_html .= '<div class="arm_paid_post_items arm_required_wrapper" id="arm_paid_post_items" style="display: none;"></div>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                        

                    }

                    $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_type">';

                        $paid_post_html .= '<div class="arm_paid_post_row_left">' . esc_html__('Post Type', 'ARMember') . '</div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right">';
                            $free_post_checked = '';
                            if( 'free' == $plan_type ){
                                $free_post_checked =  ' checked="checked" ';
                            }

                            $paid_post_html .= '<input type="radio" '.$free_post_checked.' name="paid_post_type" id="arm_paid_free_post" value="free" class="arm_iradio" />';

                            $paid_post_html .= '<label class="form_popup_type_radio" for="arm_paid_free_post">';

                                $paid_post_html .= '&nbsp;'.esc_html__('Free Post','ARMember');

                            $paid_post_html .= '</label>';

                            $paid_post_html .= '&nbsp;';
                            

                            $buy_now_checked = '';
                            if( 'paid_infinite' == $plan_type || 'paid_finite' == $plan_type ){
                                $buy_now_checked = ' checked="checked" ';
                            }

                            $paid_post_html .= '<input type="radio" '.$buy_now_checked.' name="paid_post_type" id="arm_paid_post_buynow" value="buy_now" class="arm_iradio" />';

                            $paid_post_html .= '<label class="form_popup_type_radio" for="arm_paid_post_buynow">';

                                $paid_post_html .= '&nbsp;'.esc_html__('Buy Now','ARMember');

                            $paid_post_html .= '</label>';

                            $paid_post_html .= '&nbsp;';

                            $subscription_checked = '';

                            if( 'recurring' == $plan_type ){
                                $subscription_checked =  ' checked="checked" ';
                            }

                            $paid_post_html .= '<input type="radio" '.$subscription_checked.' name="paid_post_type" id="arm_paid_post_subscription" value="subscription" class="arm_iradio" />';

                            $paid_post_html .= '<label class="form_popup_type_radio" for="arm_paid_post_subscription">';

                                $paid_post_html .= '&nbsp;'.esc_html__('Subscription/Recurring Post','ARMember');

                            $paid_post_html .= '</label>';

                        $paid_post_html .= '</div>';

                    $paid_post_html .= '</div>';

                    $show_paid_post_amount = ' hidden_section ';
                    $show_paid_post_duration = ' hidden_section ';
                    if( empty($plan_type) || 'paid_infinite' == $plan_type || 'paid_finite' == $plan_type ){
                        $show_paid_post_amount = '';
                        $show_paid_post_duration = '';
                    }

                    $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_one_time_amount '.$show_paid_post_amount.'">';

                        $paid_post_html .= '<div class="arm_paid_post_row_left">' . esc_html__( 'Paid Post Amount', 'ARMember' ) . ' *</div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right">';

                            $paid_post_html .= '<span class="arm_paid_post_plan_currency_symbol arm_paid_post_plan_currency_symbol_pre ' . esc_attr($global_currency_sym_pos_pre) . '">'.$global_currency_sym.'</span>';

                            $paid_post_html .= '<input type="text" name="arm_paid_post_plan" class="arm_paid_post_input_field" value="'. ( isset( $paid_post_plan_data->arm_subscription_plan_amount ) ? esc_attr($paid_post_plan_data->arm_subscription_plan_amount) : '' ).'"  onkeypress="javascript:return ArmNumberValidation(event, this)" data-msg-required="'. esc_attr__('Amount should not be blank.','ARMember') . '" />';

                            $paid_post_html .= '<span class="arm_paid_post_plan_currency_symbol arm_paid_post_plan_currency_symbol_post ' . esc_attr($global_currency_sym_pos_suf) . '">'.$global_currency_sym.'</span>';

                        $paid_post_html .= '</div>';

                    $paid_post_html .= '</div>';

                    $arm_add_plan_amount_html_for_paid_plan = '';
                    $arm_plan_type_paid_post = 'paid_post';
                    $arm_payment_cycle_num = '';
                    $plan_data = (array)$paid_post_plan_data;
                    $paid_post_html .= apply_filters('arm_admin_membership_plan_html_after_amount', $arm_add_plan_amount_html_for_paid_plan, $plan_data, $arm_plan_type_paid_post, $arm_payment_cycle_num);

                    $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_plan_one_time_duration '.$show_paid_post_duration.' ">';

                        $paid_post_html .= '<div class="arm_paid_post_row_left">' . esc_html__('Duration Type','ARMember') . '</div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right">';

                            $checked_forever_type = '';
                            if( !isset( $plan_options['access_type'] ) || ( isset( $plan_options['access_type'] ) && 'lifetime' == $plan_options['access_type'] ) ){
                                $checked_forever_type = ' checked="checked" ';
                            }

                            $paid_post_html .= '<input type="radio" name="paid_post_duration" id="arm_paid_post_duration_lifetime" value="forever" class="arm_iradio" ' . $checked_forever_type . ' />';

                            $paid_post_html .= '<label class="form_popup_type_radio" for="arm_paid_post_duration_lifetime">';
                                $paid_post_html .= '&nbsp;'.esc_html__('Lifetime','ARMember');

                            $paid_post_html .= '</label>';

                            $paid_post_html .= '&nbsp;';

                            $checked_fixed_duration_type = '';
                            if( isset( $plan_options['access_type'] ) && 'finite' == $plan_options['access_type'] ){
                                $checked_fixed_duration_type = ' checked="checked" ';
                            }

                            $paid_post_html .= '<input type="radio" name="paid_post_duration" id="arm_paid_post_duration_fixed" value="fixed_duration" class="arm_iradio" ' . $checked_fixed_duration_type . ' />';

                            $paid_post_html .= '<label class="form_popup_type_radio" for="arm_paid_post_duration_fixed">';

                                $paid_post_html .= '&nbsp;'.esc_html__('Fixed Duration','ARMember');

                            $paid_post_html .= '</label>';

                        $paid_post_html .= '</div>';

                    $paid_post_html .= '</div>';

                    $arm_show_fixed_duration = ' hidden_section ';
                    if( isset( $plan_options['access_type'] ) && 'finite' == $plan_options['access_type'] && 'recurring' != $plan_type ){
                        $arm_show_fixed_duration = '';
                    }

                    $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_plan_one_time_duration_value '.$arm_show_fixed_duration.'">'; 

                        $paid_post_html .= '<div class="arm_paid_post_row_left">' . esc_html__('Duration', 'ARMember') . '</div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right">';

                            $arm_show_day_duration = ' hidden_section ';
                            if( ! isset( $plan_options['eopa']['type'] ) || ( isset( $plan_options['eopa']['type'] ) && 'D' == $plan_options['eopa']['type'] ) ){
                                $arm_show_day_duration = '';
                            }

                            $paid_post_html .= '<div class="arm_paid_post_duration_dropdown arm_paid_post_duration_d ' . $arm_show_day_duration . '">';

                                $paid_post_html .= '<div class="arm_paid_post_selectbox_wrapper">';

                                    $paid_post_html .= '<input type="hidden" name="arm_paid_plan_one_time_duration[days]" value="' . ( isset( $plan_options['eopa']['days'] ) ? $plan_options['eopa']['days'] : 1 ) . '" id="arm_paid_plan_one_time_duration_d" />';

                                    $paid_post_html .= '<dl class="arm_selectbox arm_width_100">';

                                        $paid_post_html .= '<dt><span>' . ( isset( $plan_options['eopa']['days'] ) ? $plan_options['eopa']['days'] : 1 ) . '</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                        $paid_post_html .= '<dd>';
                                                    
                                            $paid_post_html .= '<ul data-id="arm_paid_plan_one_time_duration_d">';
                                                
                                                for ($i = 1; $i <= 90; $i++) {
                                                    $paid_post_html .= '<li data-label="' . esc_attr($i) . '" data-value="'. esc_attr($i) . '">'. esc_html($i). '</li>';
                                                }

                                            $paid_post_html .= '</ul>';

                                        $paid_post_html .= '</dd>';

                                    $paid_post_html .= '</dl>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                            $arm_show_week_duration = ' hidden_section ';
                            if( isset( $plan_options['eopa']['type'] ) && 'W' == $plan_options['eopa']['type'] ){
                                $arm_show_week_duration = '';
                            }
                            $paid_post_html .= '<div class="arm_paid_post_duration_dropdown arm_paid_post_duration_w ' . $arm_show_week_duration . '">';

                                $paid_post_html .= '<div class="arm_paid_post_selectbox_wrapper">';

                                    $paid_post_html .= '<input type="hidden" name="arm_paid_plan_one_time_duration[week]" value="' . ( isset( $plan_options['eopa']['weeks'] ) ? $plan_options['eopa']['weeks'] : 1 ) . '" id="arm_paid_plan_one_time_duration_w" />';

                                    $paid_post_html .= '<dl class="arm_selectbox arm_width_100">';

                                        $paid_post_html .= '<dt><span>' . ( isset( $plan_options['eopa']['weeks'] ) ? $plan_options['eopa']['weeks'] : 1 ) . '</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                        $paid_post_html .= '<dd>';
                                                    
                                            $paid_post_html .= '<ul data-id="arm_paid_plan_one_time_duration_w">';
                                                
                                                for ($i = 1; $i <= 52; $i++) {
                                                    $paid_post_html .= '<li data-label="' . esc_attr($i) . '" data-value="'. esc_attr($i) . '">'. esc_html($i). '</li>';
                                                }

                                            $paid_post_html .= '</ul>';

                                        $paid_post_html .= '</dd>';

                                    $paid_post_html .= '</dl>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                            $arm_show_month_duration = ' hidden_section ';
                            if( isset( $plan_options['eopa']['type'] ) && 'M' == $plan_options['eopa']['type'] ){
                                $arm_show_month_duration = '';
                            }

                            $paid_post_html .= '<div class="arm_paid_post_duration_dropdown arm_paid_post_duration_m ' . $arm_show_month_duration . '">';

                                $paid_post_html .= '<div class="arm_paid_post_selectbox_wrapper">';

                                    $paid_post_html .= '<input type="hidden" name="arm_paid_plan_one_time_duration[month]" value="' . ( isset( $plan_options['eopa']['months'] ) ? $plan_options['eopa']['months'] : 1 ) . '" id="arm_paid_plan_one_time_duration_m" />';

                                    $paid_post_html .= '<dl class="arm_selectbox arm_width_100">';

                                        $paid_post_html .= '<dt><span>' . ( isset( $plan_options['eopa']['months'] ) ? $plan_options['eopa']['months'] : 1 ) . '</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                        $paid_post_html .= '<dd>';
                                                    
                                            $paid_post_html .= '<ul data-id="arm_paid_plan_one_time_duration_m">';
                                                
                                                for ($i = 1; $i <= 24; $i++) {
                                                    $paid_post_html .= '<li data-label="' . esc_attr($i) . '" data-value="'. esc_attr($i) . '">'. esc_html($i). '</li>';
                                                }

                                            $paid_post_html .= '</ul>';

                                        $paid_post_html .= '</dd>';

                                    $paid_post_html .= '</dl>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                            $arm_show_year_duration = ' hidden_section';
                            if( isset( $plan_options['eopa']['type'] ) && 'Y' == $plan_options['eopa']['type'] ){
                                $arm_show_year_duration = '';
                            }

                            $paid_post_html .= '<div class="arm_paid_post_duration_dropdown arm_paid_post_duration_y ' . $arm_show_year_duration . '">';

                                $paid_post_html .= '<div class="arm_paid_post_selectbox_wrapper">';

                                    $paid_post_html .= '<input type="hidden" name="arm_paid_plan_one_time_duration[year]" value="' . ( isset( $plan_options['eopa']['years'] ) ? $plan_options['eopa']['years'] : 1 ) . '" id="arm_paid_plan_one_time_duration_y" />';

                                    $paid_post_html .= '<dl class="arm_selectbox arm_width_100">';

                                        $paid_post_html .= '<dt><span>' . ( isset( $plan_options['eopa']['years'] ) ? $plan_options['eopa']['years'] : 1 ) . '</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                        $paid_post_html .= '<dd>';
                                                    
                                            $paid_post_html .= '<ul data-id="arm_paid_plan_one_time_duration_y">';
                                                
                                                for ($i = 1; $i <= 15; $i++) {
                                                    $paid_post_html .= '<li data-label="' . esc_attr($i) . '" data-value="'. esc_attr($i) . '">'. esc_html($i) . '</li>';
                                                }

                                            $paid_post_html .= '</ul>';

                                        $paid_post_html .= '</dd>';

                                    $paid_post_html .= '</dl>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                            $paid_post_html .= '&nbsp;';

                            $paid_post_html .= '<div class="arm_paid_post_duration_dropdown">';

                                $paid_post_html .= '<div class="arm_paid_post_selectbox_wrapper">';

                                    $paid_post_html .= '<input type="hidden" name="arm_paid_plan_one_time_duration[type]" value="' . ( isset( $plan_options['eopa']['type'] )? $plan_options['eopa']['type'] : 'D' ) . '" id="arm_paid_plan_one_time_duration_type" />';

                                    $paid_post_html .= '<dl class="arm_selectbox arm_width_300">';

                                        $arm_paid_post_duration_label = esc_html__( 'Day(s)', 'ARMember' );
                                        if( isset( $plan_options['eopa']['type'] ) && 'W' == $plan_options['eopa']['type'] ){
                                            $arm_paid_post_duration_label = esc_html__( 'Week(s)', 'ARMember' );
                                        } else if( isset( $plan_options['eopa']['type'] ) && 'M' == $plan_options['eopa']['type'] ){
                                            $arm_paid_post_duration_label = esc_html__( 'Month(s)', 'ARMember' );
                                        } else if( isset( $plan_options['eopa']['type'] ) && 'Y' == $plan_options['eopa']['type'] ){
                                            $arm_paid_post_duration_label = esc_html__( 'Year(s)', 'ARMember' );
                                        }

                                        $paid_post_html .= '<dt><span>' . $arm_paid_post_duration_label . '</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                        $paid_post_html .= '<dd>';

                                            $paid_post_html .= '<ul data-id="arm_paid_plan_one_time_duration_type">';

                                                $paid_post_html .= '<li data-label="'.esc_attr__( 'Day(s)', 'ARMember' ).'" data-value="D">'. esc_html__( 'Day(s)', 'ARMember' ) .'</li>';
                                                $paid_post_html .= '<li data-label="'.esc_attr__( 'Week(s)', 'ARMember' ).'" data-value="W">'. esc_html__( 'Week(s)', 'ARMember' ) .'</li>';
                                                $paid_post_html .= '<li data-label="'.esc_attr__( 'Month(s)', 'ARMember' ).'" data-value="M">'. esc_html__( 'Month(s)', 'ARMember' ) .'</li>';
                                                $paid_post_html .= '<li data-label="'.esc_attr__( 'Year(s)', 'ARMember' ).'" data-value="Y">'. esc_html__( 'Year(s)', 'ARMember' ) .'</li>';

                                            $paid_post_html .= '</ul>';

                                        $paid_post_html .= '</dd>';

                                    $paid_post_html .= '</dl>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                        $paid_post_html .= '</div>';

                    $paid_post_html .= '</div>';

                    $arm_show_payment_cycles = ' hidden_section ';
                    if( 'recurring' == $plan_type ){
                        $arm_show_payment_cycles = '';
                    }

                    $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_plan_subscription_cycle ' . $arm_show_payment_cycles . '">';

                        $paid_post_html .= '<div class="arm_paid_post_row_left arm_paid_post_row_float_left">' . esc_html__( 'Payment Cycles', 'ARMember' ) . ' </div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right">';

                            $paid_post_html .= '<div class="arm_paid_post_subscription_options_recurring_payment_cycles_main_box">';

                                $paid_post_html .= '<ul class="arm_plan_payment_cycle_ul">';

                                    $total_inirecurring_cycle = count($payment_cycles_data); 
                                    $gi = 1;
                                    foreach( $payment_cycles_data as $arm_pc => $arm_value ){

                                        $paid_post_html .= '<li class="arm_plan_payment_cycle_li paid_subscription_options_recurring_payment_cycles_child_box" id="paid_subscription_options_recurring_payment_cycles_child_box'. esc_attr($arm_pc).'">';

                                            $paid_post_html .= '<div class="arm_plan_payment_cycle_label">';

                                                $paid_post_html .= '<label class="arm_plan_payment_cycle_label_text">'.esc_html__('Label', 'ARMember').'</label>';

                                                $paid_post_html .= '<div class="arm_plan_payment_cycle_label_input">';

                                                    $paid_post_html .= '<input type="hidden" name="arm_paid_post_subscription_plan_options[payment_cycles]['.esc_attr($arm_pc).'][cycle_key]" value="' . ( !empty($arm_value['cycle_key']) ? esc_attr($arm_value['cycle_key']) : 'arm'.rand() ). '"/>';

                                                    $paid_post_html .= '<input type="text" class="arm_paid_post_input_field paid_subscription_options_recurring_payment_cycle_label" name="arm_paid_post_subscription_plan_options[payment_cycles]['.esc_attr($arm_pc).'][cycle_label]" data-msg-required="'.esc_attr__('Label should not be blank', 'ARMember').'" value="' . ( !empty($arm_value['cycle_label']) ? esc_attr($arm_value['cycle_label']) : '' ). '" />';

                                                $paid_post_html .= '</div>';

                                            $paid_post_html .= '</div>';

                                            $paid_post_html .= '<div class="arm_plan_payment_cycle_amount">';

                                                $paid_post_html .= '<label class="arm_plan_payment_cycle_amount_text">' . esc_html__( 'Amount', 'ARMember' ) . '</label>';

                                                $paid_post_html .= '<div class="arm_plan_payment_cycle_amount_input">';

                                                    $paid_post_html .= '<span class="arm_paid_post_plan_currency_symbol arm_paid_post_plan_currency_symbol_pre '.esc_attr($global_currency_sym_pos_pre).'">' . $global_currency_sym . '</span>';

                                                    $paid_post_html .= '<input type="text" name="arm_paid_post_subscription_plan_options[payment_cycles]['.$arm_pc.'][cycle_amount]" value="'. (!empty($arm_value['cycle_amount']) ? esc_attr($arm_value['cycle_amount']) : '' ).'" class="paid_subscription_options_recurring_payment_cycle_amount arm_paid_post_input_field" data-msg-required="'.esc_attr__('Amount should not be blank.', 'ARMember').'" onkeypress="javascript:return ArmNumberValidation(event, this)" />';

                                                    $paid_post_html .= '<span class="arm_paid_post_plan_currency_symbol arm_paid_post_plan_currency_symbol_post '.esc_attr($global_currency_sym_pos_suf).'">' . $global_currency_sym . '</span>';

                                                $paid_post_html .= '</div>';

                                            $paid_post_html .= '</div>';

                                            $arm_add_plan_amount_html_for_plan = '';
                                            $arm_plan_type_paid_post = 'recurring';
                                            $paid_post_html .= apply_filters('arm_admin_membership_plan_html_after_amount', $arm_add_plan_amount_html_for_plan, $plan_data, $arm_plan_type_paid_post, $arm_pc);

                                            $paid_post_html .= '<div class="arm_plan_payment_cycle_billing_cycle">';

                                                $paid_post_html .= '<label class="arm_plan_payment_cycle_billing_text">' . esc_html__( 'Billing Cycle', 'ARMember' ) . '</label>';

                                                $paid_post_html .= '<div class="arm_plan_payment_cycle_billing_input">';

                                                    $paid_post_html .= '<input type="hidden" id="arm_ipc_billing'.$arm_pc.'" name="arm_paid_post_subscription_plan_options[payment_cycles]['.esc_attr($arm_pc).'][billing_cycle]" value="'.(!empty($arm_value['billing_cycle']) ? esc_attr($arm_value['billing_cycle']) : 1).'" />';

                                                    $paid_post_html .= '<dl class="arm_selectbox arm_margin_0  arm_min_width_50" ">';

                                                        $paid_post_html .= '<dt><span>'.( !empty( $arm_value['billing_cycle'] ) ? $arm_value['billing_cycle'] : 1 ).'</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';
                                                            
                                                        $paid_post_html .= '<dd>';

                                                            $paid_post_html .= '<ul data-id="arm_ipc_billing' . $arm_pc . '">';
                                                                
                                                                for ($i = 1; $i <= 90; $i++) {

                                                                    $paid_post_html .= '<li data-label="' . esc_attr($i) .'" data-value="' . esc_attr($i) . '">' . esc_html($i) . '</li>';
                                                                
                                                                }
                                                                 
                                                            $paid_post_html .= '</ul>';

                                                        $paid_post_html .= '</dd>';

                                                    $paid_post_html .= '</dl>';

                                                    $paid_post_html .= '&nbsp;&nbsp;';

                                                    $paid_post_html .= '<input type="hidden" id="arm_ipc_billing_type'.$arm_pc.'" name="arm_paid_post_subscription_plan_options[payment_cycles]['.esc_attr($arm_pc).'][billing_type]" value="' . ( !empty( $arm_value['billing_type'] ) ? esc_attr($arm_value['billing_type']) : "D" ) . '" />';

                                                    $paid_post_html .= '<dl class="arm_selectbox arm_margin_0 arm_min_width_75">';

                                                        $paid_post_html .= '<dt class="arm_width_80"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                                        $paid_post_html .= '<dd>';

                                                            $paid_post_html .= '<ul data-id="arm_ipc_billing_type'.$arm_pc.'">';

                                                                $paid_post_html .= '<li data-label="'.esc_attr__('Day(s)', 'ARMember').'" data-value="D">'.esc_html__('Day(s)', 'ARMember').'</li>';
                                                                $paid_post_html .= '<li data-label="'.esc_attr__('Month(s)', 'ARMember').'" data-value="M">'.esc_html__('Month(s)', 'ARMember').'</li>';
                                                                $paid_post_html .= '<li data-label="'.esc_attr__('Year(s)', 'ARMember').'" data-value="Y">'.esc_html__('Year(s)', 'ARMember').'</li>';

                                                            $paid_post_html .= '</ul>';

                                                        $paid_post_html .= '</dd>';

                                                    $paid_post_html .= '</dl>';

                                                $paid_post_html .= '</div>';

                                            $paid_post_html .= '</div>';

                                            $paid_post_html .= '<div class="arm_plan_payment_cycle_recurring_time">';

                                                $paid_post_html .= '<label class="arm_plan_payment_cycle_recurring_text">' . esc_html__('Recurring Time', 'ARMember') . '</label>';

                                                $paid_post_html .= '<input type="hidden" id="arm_ipc_recurring'.$arm_pc.'" name="arm_paid_post_subscription_plan_options[payment_cycles]['.esc_attr($arm_pc).'][recurring_time]" value="' . (!empty($arm_value['recurring_time']) ? esc_attr($arm_value['recurring_time']) : 'infinite' ).'" />';

                                                $paid_post_html .= '<dl class="arm_selectbox arm_margin_0 arm_width_100 arm_min_width_70" >';

                                                    $paid_post_html .= '<dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';

                                                    $paid_post_html .= '<dd>';

                                                        $paid_post_html .= '<ul data-id="arm_ipc_recurring' . esc_attr($arm_pc) . '">';

                                                            $paid_post_html .= '<li data-label="' . esc_attr__('Infinite', 'ARMember') . '" data-value="infinite">' . esc_html__('Infinite', 'ARMember') . '</li>';

                                                            for ($i = 2; $i <= 30; $i++) {

                                                                $paid_post_html .= '<li data-label="' . esc_attr($i) . '" data-value="'. esc_attr($i) . '">' . esc_html($i) . '</li>';

                                                            }

                                                        $paid_post_html .= '</ul>';

                                                    $paid_post_html .= '</dd>';

                                                $paid_post_html .= '</dl>';

                                            $paid_post_html .= '</div>';

                                            $paid_post_html .= '<div class="arm_plan_payment_cycle_action_buttons">';
                                                
                                                $paid_post_html .= '<div class="arm_plan_cycle_plus_icon arm_helptip_icon tipso_style arm_add_plan_icon arm_remove_user_plan_icon2" title="'.esc_attr__('Add Payment Cycle','ARMember').'" id="arm_add_payment_cycle_recurring" data-field_index="'. ( isset($total_inirecurring_cycle) ? esc_attr($total_inirecurring_cycle) : 1 ).'"></div>';

                                                $paid_post_html .= '<div class="arm_plan_cycle_minus_icon arm_helptip_icon tipso_style arm_add_plan_icon arm_remove_user_plan_icon2" title="'.esc_attr__('Remove Payment Cycle', 'ARMember').'" id="arm_remove_recurring_payment_cycle" data_index="'. ( isset($total_inirecurring_cycle) ? esc_attr($total_inirecurring_cycle) : 1 ).'"></div>';
                                                
                                                $paid_post_html .= '<div class="arm_plan_cycle_sortable_icon ui-sortable-handle"></div>';
                                                        
                                            $paid_post_html .= '</div>';

                                            $paid_post_html .= '<input type="hidden" name="arm_paid_post_subscription_plan_options[payment_cycles]['.esc_attr($arm_pc).'][payment_cycle_order]" value="'.esc_attr($gi).'" class="arm_module_payment_cycle_order" />';

                                        $paid_post_html .= '</li>';


                                        $gi++;
                                    }

                                $paid_post_html .= '</ul>';

                                $paid_post_html .= '<div class="paid_subscription_options_recurring_payment_cycles_link">';

                                    $paid_post_html .= '<input type="hidden" name="arm_total_recurring_plan_cycles" id="arm_total_recurring_plan_cycles" value="'. ( isset($total_inirecurring_cycle) ? $total_inirecurring_cycle : 1 ).'"/>';

                                    $paid_post_html .= '<input type="hidden" name="arm_total_recurring_plan_cycles" id="arm_total_recurring_plan_cycles_order" value="2"/>';

                                    $paid_post_html .= '<input type="hidden" name="arm_total_recurring_plan_cycles_counter" id="arm_total_recurring_plan_cycles_counter" value="'. ( isset($total_inirecurring_cycle) ? $total_inirecurring_cycle : 1 ).'"/>';

                                $paid_post_html .= '</div>';

                            $paid_post_html .= '</div>';

                        $paid_post_html .= '</div>';

                    $paid_post_html .= '</div>';

                    if($this->isPayPerPostFeatureLimit == true)
                    {
                        $paid_post_html .= '<div id="arm_paid_post_row" class="arm_paid_post_row arm_paid_post_plan_limit">';
                            $paid_post_html .= '<div class="arm_paid_post_row_left arm_paid_post_row_float_left">' . esc_html__('Paid Post purchase limit', 'ARMember') . '</div>';
                            $paid_post_html .= '<div class="arm_paid_post_row_right">';
                            $plan_purchase_limit = !empty($plan_options['limit']) ? intval($plan_options['limit']) : 0;
                                $paid_post_html .= '<input type="text" name="limit" value="'. esc_attr($plan_purchase_limit).'" class="arm_no_paste arm_width_235 arm_margin_bottom_12" data-msg-required="Label should not be blank." wfd-id="id28" aria-invalid="false" onkeypress="javascript:return ArmNumberValidation(event, this)">';
                                $paid_post_html .= '<span class="arm_subscription_limit_note arm_margin_top_12">'.esc_html__('Leave blank or 0 for unlimited paid post purchases.','ARMember').'</span>';
                            $paid_post_html .= '</div>';
                        $paid_post_html .= '</div>';
                    }

                    $paid_post_html .= '<div class="arm_paid_post_row_separator"></div>';

                    $paid_post_html .= '<div class="arm_paid_post_row">';

                        $paid_post_html .= '<div class="arm_paid_post_row_left">' . esc_html__( 'Alternative Content', 'ARMember' ). '</div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right">';

                            $enable_ppost_alternate_content = get_post_meta( $post_id, 'arm_enable_paid_post_alternate_content', true );

                            $checked_paid_post_alt_content = checked( 1, $enable_ppost_alternate_content, false );

                            $paid_post_html .= '<div class="armswitch armswitchbig">';

                            $alternate_switch_text = '';

                            if( isset( $_GET['action'] ) && 'edit_paid_post' == $_GET['action'] ){
                                $alternate_switch_text = '-edit';
                            }
                            
                            $paid_post_html .= '<input type="checkbox" value="1" ' . $checked_paid_post_alt_content . ' class="armswitch_input" name="arm_enable_paid_post_alternate_content" id="arm_enable_ppost_alternate_content'.esc_attr($alternate_switch_text).'" />';

                            $paid_post_html .= '<label for="arm_enable_ppost_alternate_content'.esc_attr($alternate_switch_text).'" class="armswitch_label"></label>';

                            $paid_post_html .= '<div class="armclear"></div>';

                        $paid_post_html .= '</div>';
                    $paid_post_html .= '</div>';

                        $paid_post_html .= '<div class="arm_paid_post_row">';
                            $paid_post_html .= '<div class="arm_paid_post_row_left">&nbsp;</div>';
                            $paid_post_html .= '<div class="arm_paid_post_row_right">';
                                $paid_post_html .= '<span class="arm_info_text" style="margin: 10px 0 0;">'. esc_html__("Display alternative content to the member who has not buy this post. If this disable then default content will be displayed from ARMember -> General Settings -> Paid Post Settings page.","ARMember"). '</span>';
                            $paid_post_html .= '</div>';
                        $paid_post_html .= '</div>';


                    $paid_post_html .= '</div>';

                    $arm_show_ppost_alt_content_wrapper = ' hidden_section ';

                    if( $enable_ppost_alternate_content ){
                        $arm_show_ppost_alt_content_wrapper = '';
                    }

                    $paid_post_html .= '<div class="arm_paid_post_row arm_paid_post_row_alternate_content_row ' . $arm_show_ppost_alt_content_wrapper . '">';

                        $paid_post_html .= '<div class="arm_paid_post_row_left arm_paid_post_row_float_left">' . esc_html__('Enter Alternative Content', 'ARMember') . '</div>';

                        $paid_post_html .= '<div class="arm_paid_post_row_right arm_paid_post_row_alternative_content_wrapper">';

                            $arm_alternate_arr_settings = array(
                                'media_buttons' => true,
                                'textarea_rows' => 15,
                                'default_editor' => 'html'
                            );

                            $arm_pp_alt_content_id = '';
                            if( isset( $_GET['action'] ) && 'edit_paid_post' == $_GET['action'] ) {
                                $arm_pp_alt_content_id = '-edit';
                            }

                            $post_alternative_content = get_post_meta( $post_id, 'arm_paid_post_alternative_content', true );

                            $arm_global_settings_general_settings_default_content = !empty($arm_global_settings->global_settings['arm_pay_per_post_default_content']) ? stripslashes($arm_global_settings->global_settings['arm_pay_per_post_default_content']) : esc_html__('Content is Restricted. Buy this post to get access to full content.', 'ARMember');

                            if( !empty( $arm_show_ppost_alt_content_wrapper ) && empty( $post_alternative_content ) ){
                                if( !empty( $arm_global_settings_general_settings_default_content ) ){
                                    $post_alternative_content = $arm_global_settings_general_settings_default_content;
                                }
                            } else if( empty( $post_alternative_content ) )
                            {
                                $post_alternative_content = $arm_global_settings_general_settings_default_content;
                            }

                            ob_start();

                            wp_editor(stripslashes_deep($post_alternative_content), 'arm_paid_post_alternative_content' . $arm_pp_alt_content_id, $arm_alternate_arr_settings);

                            $paid_post_alternate_content_editor = ob_get_clean();

                            $paid_post_html .= $paid_post_alternate_content_editor;

                        $paid_post_html .= '</div>';

                    $paid_post_html .= '</div>';

                $paid_post_html .= '</div>';

            $paid_post_html .= '</div>';

            $paid_post_html = apply_filters('arm_display_field_add_paid_post', $paid_post_html);

            $paid_post_html .= '<script>
                                jQuery(document).on("change", "#arm_enable_paid_post", function(e) {
                                    if(jQuery(this).is(":checked")) {
                                        jQuery("#arm_enable_paid_post_hidden").val(1);
                                    } else {
                                        jQuery("#arm_enable_paid_post_hidden").val(0);
                                    }
                                    
                                });
                            </script>';

            if( $return ){
                return $paid_post_html;
            } else {
                echo $paid_post_html;//phpcs:ignore --Reason $paid_post_html contains HTML code
            }

        }

        function arm_display_shortcode_buttons_for_alternate_button( $post_type, $editor_id ){

            if( 'arm_paid_post_alternative_content' == $editor_id || 'arm_paid_post_alternative_content-edit' == $editor_id ){
                global $post;
                if( isset( $post ) && isset( $post->post_type ) ){
                    array_push( $post_type, $post->post_type );
                } else if( !empty($_GET['page']) && 'arm_manage_pay_per_post' == $_GET['page'] ){
                    array_push( $post_type, 'arm_pay_per_post' );
                } else if( !empty($_POST['action']) && 'edit_paid_post' == $_POST['action'] && 'arm_paid_post_alternative_content-edit' == $editor_id ){//phpcs:ignore
                    array_push( $post_type, 'arm_pay_per_post' );
                }
            } else if( 'arm_pay_per_post_content' == $editor_id ){
                array_push( $post_type, 'arm_pay_per_post' );
            }

            array_unique( $post_type );

            return $post_type;
        }

        function arm_allowed_pages_for_media_buttons_buttons( $pages, $editor_id ){

            if( 'arm_pay_per_post_content' == $editor_id && isset( $_GET['page'] ) && 'arm_general_settings' == $_GET['page'] && isset( $_GET['action'] ) && 'pay_per_post_setting' == $_GET['action'] ){
                array_push( $pages, basename( sanitize_text_field( $_SERVER['PHP_SELF'] ) ) );//phpcs:ignore
            } else if( 'arm_paid_post_alternative_content' == $editor_id && isset( $_GET['page'] ) && 'arm_manage_pay_per_post' == $_GET['page'] ){
                array_push( $pages, basename( sanitize_text_field( $_SERVER['PHP_SELF'] ) ) );//phpcs:ignore
            } else if( 'arm_paid_post_alternative_content-edit' == $editor_id && isset( $_GET['action'] ) && 'edit_paid_post' == $_GET['action'] ){
                array_push( $pages, basename( sanitize_text_field( $_SERVER['PHP_SELF'] ) ) );//phpcs:ignore
            }

            return $pages;

        }

        function arm_allowed_post_type_for_external_editors_callback( $post_type, $editor_id ){

            if( 'arm_pay_per_post_content' == $editor_id && empty($post_type) ){
                $post_type = 'arm_pay_per_post';
            } else if( ( 'arm_paid_post_alternative_content' == $editor_id || 'arm_paid_post_alternative_content-edit' == $editor_id ) && empty($post_type) ){
                $post_type = 'arm_pay_per_post';
            }

            return $post_type;
        }

        function arm_allowed_pages_for_shortcode_popup_callback( $pages ){
            if( isset( $_GET['page'] ) && 'arm_general_settings' == $_GET['page'] && isset( $_GET['action'] ) && 'pay_per_post_setting' == $_GET['action'] ){
                array_push( $pages, basename( sanitize_text_field( $_SERVER['PHP_SELF'] ) ) );//phpcs:ignore
            } else if( isset( $_GET['page'] ) && 'arm_manage_pay_per_post' == $_GET['page'] && (!empty($_GET['action']) && in_array($_GET['action'], array("add_paid_post", "edit_paid_post")) ) ){
                array_push( $pages, basename( sanitize_text_field( $_SERVER['PHP_SELF'] ) ) );//phpcs:ignore
            }
            return $pages;

        }

        function arm_enqueue_shortcode_styles_callback( $pages ){
            
            if( isset( $_GET['page'] ) && 'arm_general_settings' == $_GET['page'] && isset( $_GET['action'] ) && 'pay_per_post_setting' == $_GET['action'] ){
                array_push( $pages, basename( sanitize_text_field( $_SERVER['PHP_SELF'] ) ) );//phpcs:ignore
            } else if( isset( $_GET['page'] ) && 'arm_manage_pay_per_post' == $_GET['page'] ){
                array_push( $pages, basename( sanitize_text_field( $_SERVER['PHP_SELF'] ) ) );//phpcs:ignore
            }
            return $pages;

        }

        function arm_paid_post_shortcode_add_tab_buttons($tab_buttons =array()){
            $tab_buttons =' <div class="arm_group_footer arm_shortcode_other_opts arm_shortcode_other_opts_arm_paid_post arm_hidden">
                                    <div class="popup_content_btn_wrapper">
                                        <a class="arm_cancel_btn popup_close_btn arm_margin_right_12" href="javascript:void(0)">'.esc_html__('Cancel', 'ARMember').'</a>
                                        <button type="button" class="arm_shortcode_insert_btn arm_insrt_btn arm_margin_right_0" id="arm_shortcode_other_opts_arm_paid_post" data-code="arm_user_paid_post">'.esc_html__('Add Shortcode', 'ARMember').'</button>
                                    </div>
                            </div>';
            echo $tab_buttons; //phpcs:ignore
        }

        /*
        
        //! this function is duplicate function which is available in class.arm_social_feature.php

        function arm_plugin_install() {
            global $ARMember, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_pay_per_post'], '1',1);
            if (empty($_POST['slug'])) {//phpcs:ignore
                wp_send_json_error(array(
                    'slug' => '',
                    'errorCode' => 'no_plugin_specified',
                    'errorMessage' => esc_html__('No plugin specified.', 'ARMember'),
                ));
            }

            $status = array(
                'install' => 'plugin',
                'slug' => sanitize_key(wp_unslash($_POST['slug'])),//phpcs:ignore
            );

            if (!current_user_can('install_plugins')) {
                $status['errorMessage'] = esc_html__('Sorry, you are not allowed to install plugins on this site.', 'ARMember');
                wp_send_json_error($status);
            }
            if (file_exists(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php')) {
                include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
            }
            if (file_exists(ABSPATH . 'wp-admin/includes/plugin-install.php'))
                include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

            $api = plugins_api('plugin_information', array(
                'slug' => sanitize_key(wp_unslash($_POST['slug'])),//phpcs:ignore
                'fields' => array(
                    'sections' => false,
                ),
            ));

            if (is_wp_error($api)) {
                $status['errorMessage'] = $api->get_error_message();
                wp_send_json_error($status);
            }

            $status['pluginName'] = $api->name;

            $skin = new WP_Ajax_Upgrader_Skin();
            $upgrader = new Plugin_Upgrader($skin);

            $result = $upgrader->install($api->download_link);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                $status['debug'] = $skin->get_upgrade_messages();
            }

            if (is_wp_error($result)) {
                $status['errorCode'] = $result->get_error_code();
                $status['errorMessage'] = $result->get_error_message();
                wp_send_json_error($status);
            } elseif (is_wp_error($skin->result)) {
                $status['errorCode'] = $skin->result->get_error_code();
                $status['errorMessage'] = $skin->result->get_error_message();
                wp_send_json_error($status);
            } elseif ($skin->get_errors()->get_error_code()) {
                $status['errorMessage'] = $skin->get_error_messages();
                wp_send_json_error($status);
            } elseif (is_null($result)) {
                global $wp_filesystem;

                $status['errorCode'] = 'unable_to_connect_to_filesystem';
                $status['errorMessage'] = esc_html__('Unable to connect to the filesystem. Please confirm your credentials.', 'ARMember');

                if ($wp_filesystem instanceof WP_Filesystem_Base && is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->get_error_code()) {
                    $status['errorMessage'] = esc_html($wp_filesystem->errors->get_error_message());
                }

                wp_send_json_error($status);
            }
            $install_status = $this->arm_install_plugin_install_status($api);


            if (current_user_can('activate_plugins') && is_plugin_inactive($install_status['file'])) {
                $status['activateUrl'] = add_query_arg(array(
                    '_wpnonce' => wp_create_nonce('activate-plugin_' . $install_status['file']),
                    'action' => 'activate',
                    'plugin' => $install_status['file'],
                        ), network_admin_url('plugins.php'));
            }

            if (is_multisite() && current_user_can('manage_network_plugins')) {
                $status['activateUrl'] = add_query_arg(array('networkwide' => 1), $status['activateUrl']);
            }
            $status['pluginFile'] = $install_status['file'];

            wp_send_json_success($status);
        }
	*/

        // For manage page retrive paid post data 
        function arm_retrieve_paid_post_data(){

            global $wpdb, $ARMember, $arm_global_settings, $arm_slugs,$arm_capabilities_global;
            $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST );//phpcs:ignore
            $offset = isset( $posted_data['iDisplayStart'] ) ? $posted_data['iDisplayStart'] : 0;//phpcs:ignore
            $limit = isset( $_POST['iDisplayLength'] ) ? $posted_data['iDisplayLength'] : 10;//phpcs:ignore
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_pay_per_post'], '1',1);//phpcs:ignore --Reason:Verifying nonce
            $search_term = ( isset( $posted_data['sSearch'] ) && '' != $posted_data['sSearch'] ) ? true : false;

            $search_query = '';
            if( $search_term ){
                $search_query = $wpdb->prepare("AND (arm_subscription_plan_name LIKE %s )",'%'.$posted_data['sSearch'].'%');
            }

            $sortOrder = isset( $posted_data['sSortDir_0'] ) ? $posted_data['sSortDir_0'] : 'DESC';
            $sortOrder = strtolower($sortOrder);
            if ( 'asc'!=$sortOrder && 'desc'!=$sortOrder ) {
                $sortOrder = 'desc';
            }


            $orderBy = 'ORDER BY  arm_subscription_plan_post_id ' . $sortOrder;
            if( !empty( $_POST['iSortCol_0'] ) ){//phpcs:ignore
                if( $_POST['iSortCol_0'] == 0 ){//phpcs:ignore
                    $orderBy = 'ORDER BY arm_subscription_plan_post_id ' . $sortOrder;
                }else if($_POST['iSortCol_0'] == 1){//phpcs:ignore
                    $orderBy = 'ORDER BY arm_subscription_plan_name ' . $sortOrder;
                }
            }

            $arm_paid_post_query_where = '';
            $arm_paid_post_query_where = apply_filters( 'arm_admin_paid_post_list_modify_query_where', $arm_paid_post_query_where );

            $post_query = "SELECT * FROM {$ARMember->tbl_arm_subscription_plans} WHERE arm_subscription_plan_post_id != 0 AND arm_subscription_plan_is_delete = 0 {$arm_paid_post_query_where} {$search_query} {$orderBy}  LIMIT {$offset}, {$limit}";

            $get_posts = $wpdb->get_results( $post_query ); //phpcs:ignore --Reason $post_query is a predefined query

            $totalPosts_query =  "SELECT COUNT(arm_subscription_plan_post_id) AS total FROM {$ARMember->tbl_arm_subscription_plans} WHERE arm_subscription_plan_post_id != 0 AND arm_subscription_plan_is_delete = 0 {$arm_paid_post_query_where} {$orderBy}";

            $totalPosts_result = $wpdb->get_results( $totalPosts_query ); //phpcs:ignore --Reason $totalPosts_query is a predefined query
            $totalPosts = $totalPosts_result[0]->total;
                                              
            $grid_data = array();
            $ai = 0;
            if( !empty( $get_posts )){
                foreach ($get_posts as $key => $post) {
                    if( !isset($grid_data[$ai]) || !is_array( $grid_data[$ai] ) ){
                        $grid_data[$ai] = array();
                    }

                    $planObj = new ARM_Plan();
                    $planObj->init((object) $post);
                    $arm_subscription_plan_post_id = $post->arm_subscription_plan_post_id;
                    $planID = $post->arm_subscription_plan_id;
                    $total_users = $this->arm_get_total_members_in_paid_post($arm_subscription_plan_post_id);
                    
                    $edit_link = admin_url('admin.php?page=arm_manage_pay_per_post&action=edit_paid_post&post_id='.$arm_subscription_plan_post_id);
                    $grid_data[$ai][] =  $arm_subscription_plan_post_id;
                    $grid_data[$ai][] =  $planID;
                    $grid_data[$ai][] =  $post->arm_subscription_plan_name;
                    if( $planObj->is_recurring() && isset($planObj->options['payment_cycles']) && count($planObj->options['payment_cycles']) > 1 ) {
                        $duration =  '<span class="arm_item_status_text arm_margin_right_8 active">' . esc_html__('Paid', 'ARMember') . '</span>
                        <a href="javascript:void(0);" onclick="arm_paid_post_cycle('. $arm_subscription_plan_post_id .')">' . esc_html__('Multiple Cycle', 'ARMember') . '</a>';
                    } else {
                        $duration = $planObj->plan_text(true);
                    }
                    
                    $grid_data[$ai][] = $duration;


                    $planMembers = $total_users;
                    
                    $grid_data[$ai][] = $planMembers;                                   

                    $gridAction = "<div class='arm_grid_action_btn_container'>";
                    
                        $gridAction .= "<a class='arm_paid_post_members_list_detail armhelptip' title='".esc_attr__('View Members','ARMember')."' href='javascript:void(0);' data-list_id='".esc_attr($arm_subscription_plan_post_id)."' data-list_type='drip' data-paid-post-name='".esc_attr($post->arm_subscription_plan_name)."'><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none'><path d='M3.27489 15.2957C2.42496 14.1915 2 13.6394 2 12C2 10.3606 2.42496 9.80853 3.27489 8.70433C4.97196 6.49956 7.81811 4 12 4C16.1819 4 19.028 6.49956 20.7251 8.70433C21.575 9.80853 22 10.3606 22 12C22 13.6394 21.575 14.1915 20.7251 15.2957C19.028 17.5004 16.1819 20 12 20C7.81811 20 4.97196 17.5004 3.27489 15.2957Z' stroke='#617191' stroke-width='1.5'/><path d='M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z' stroke='#617191' stroke-width='1.5'/></svg></a>";
                        
                        $gridAction .= "<a class='arm_edit_paid_post_btn armhelptip' title='".esc_attr__('Edit Paid Post','ARMember')."' href='javascript:void(0)' data-post_id='".esc_attr($arm_subscription_plan_post_id)."'><svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M13.2594 3.60022L5.04936 12.2902C4.73936 12.6202 4.43936 13.2702 4.37936 13.7202L4.00936 16.9602C3.87936 18.1302 4.71936 18.9302 5.87936 18.7302L9.09936 18.1802C9.54936 18.1002 10.1794 17.7702 10.4894 17.4302L18.6994 8.74022C20.1194 7.24022 20.7594 5.53022 18.5494 3.44022C16.3494 1.37022 14.6794 2.10022 13.2594 3.60022Z' stroke='#617191' stroke-width='1.5' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/><path d='M11.8906 5.0498C12.3206 7.8098 14.5606 9.9198 17.3406 10.1998' stroke='#617191' stroke-width='1.5' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/><path d='M3 22H21' stroke='#617191' stroke-width='1.5' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/></svg></a>";
                        $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback(".$arm_subscription_plan_post_id.");' class='arm_grid_delete_action armhelptip' title='".esc_attr__('Delete','ARMember')."'><svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M3 5.33333H21M16.5 5.33333L16.1956 4.43119C15.9005 3.55694 15.7529 3.11982 15.4793 2.79664C15.2376 2.51126 14.9274 2.29036 14.5768 2.1542C14.1798 2 13.7134 2 12.7803 2H11.2197C10.2866 2 9.8202 2 9.4232 2.1542C9.07266 2.29036 8.76234 2.51126 8.5207 2.79664C8.24706 3.11982 8.09954 3.55694 7.80447 4.43119L7.5 5.33333M18.75 5.33333V16.6667C18.75 18.5336 18.75 19.4669 18.3821 20.18C18.0586 20.8072 17.5423 21.3171 16.9072 21.6367C16.1852 22 15.2402 22 13.35 22H10.65C8.75982 22 7.81473 22 7.09278 21.6367C6.45773 21.3171 5.94143 20.8072 5.61785 20.18C5.25 19.4669 5.25 18.5336 5.25 16.6667V5.33333M14.25 9.77778V17.5556M9.75 9.77778V17.5556' stroke='#617191' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/></svg></a>";
                        if (empty($planMembers) || $planMembers == 0) {
                            $gridAction .= $arm_global_settings->arm_get_confirm_box($post->arm_subscription_plan_post_id, esc_html__("Are you sure you want to delete the Paid Post?", 'ARMember'), 'arm_paid_post_delete_btn','',esc_html__("Delete", 'ARMember'),esc_html__("Cancel", 'ARMember'),esc_html__("Delete", 'ARMember'));
                        }
                        else{
                            $gridAction .= $arm_global_settings->arm_get_confirm_box($arm_subscription_plan_post_id, esc_html__("This Paid post has one or more subscribers. So this paid post can not be deleted.", 'ARMember'), 'arm_plan_delete_btn_not arm_hide','','',esc_html__("Close",'ARMember'),esc_attr__('Delete', 'ARMember'));
                        }
                    
                    $gridAction .= "</div>";

                    $grid_data[$ai][] = '<div class="arm_grid_action_wrapper">'.$gridAction.'</div>';

                    $ai++;
                }
            }

            $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : intval(10);
            $after_filter = $totalPosts;
            if( $search_term ){
                $after_filter = $ai;
            }

            $response = array(
                'sColumns' => implode(',',array('Post ID','Post Title','Post Type','Paid Post Members')),
                'sEcho' => $sEcho,
                'iTotalRecords' => $totalPosts,
                'iTotalDisplayRecords' => $after_filter,
                'aaData' => $grid_data
            );

            echo json_encode( $response );
            die;

        }
        // For manage page retrive paid post members data 
        function arm_get_paid_post_members_data_func() {
            global $wpdb,$ARMember, $arm_capabilities_global,$arm_slugs;
            
            $postID = isset($_REQUEST['post_id']) ? intval( $_REQUEST['post_id'] ) : 0;
            $response = array('status' => 'error', 'data' => array());
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_pay_per_post'], '1',1);//phpcs:ignore --Reason:Verifying nonce
            if(0 != $postID) {
                $membersDatasDefault = array();
                $response['status'] = "success";
                $response['data'] = $membersDatasDefault;

                $arm_post_query = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$ARMember->tbl_arm_subscription_plans}` WHERE `arm_subscription_plan_post_id` = %d",$postID)); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name
                
                $arm_user_query = $wpdb->get_results($wpdb->prepare("SELECT `user_id`, `meta_value` FROM `".$wpdb->usermeta."` WHERE `meta_key` = %s",'arm_user_plan_ids'));

                $arm_user_array = array(); 
                if(!empty($arm_user_query)){
                     foreach($arm_user_query as $arm_user){
                         $user_meta=get_userdata($arm_user->user_id);
                         $user_roles=$user_meta->roles;
                         if(!in_array('administrator', $user_roles)) {
                             $arm_user_array[$arm_user->user_id] = maybe_unserialize($arm_user->meta_value);
                         }
                     }
                 }

                    if(!empty($arm_post_query)){
                        foreach ($arm_post_query as $arm_post_key => $arm_post_id) {

                            $planObj = new ARM_Plan();
                            $planObj->init((object) $arm_post_id);
                            $planID = $arm_post_id->arm_subscription_plan_id;

                            $total_users = 0;
                            if(!empty($arm_user_array)){

                                $membersData = array();
                                 
                                foreach($arm_user_array as $arm_user_id => $arm_user_plans){

                                    if(in_array($planID, $arm_user_plans)){
                                       
                                       $membersDatas = array();
                                       $user_data = get_user_by('ID',$arm_user_id);
                                        
                                       $membersDatas['username'] = $user_data->user_login;
                                       $membersDatas['user_email'] = $user_data->user_email;
                                       $view_link = admin_url('admin.php?page=' . $arm_slugs->manage_members . '&action=view_member&id=' . $user_data->ID);
                                       $membersDatas['view_detail'] = "<center><a class='arm_openpreview arm_openpreview_popup' href='javascript:void(0)' data-id=".$user_data->ID." data-arm_hide_edit='1' data-arm_popup_opened='1'>" . esc_html__('View Detail', 'ARMember') . "</a></center>";
                                       $membersData[] = array_values($membersDatas); 
                                    }
                                }
                                
                            }
                        }
                        $response['status'] = "success";
                        $response['data'] = $membersData;
                    }
            }
            echo arm_pattern_json_encode($response);
            die;
        }

        function get_arm_paid_post_plan_list_func(){
            
            if(isset($_REQUEST['action']) && $_REQUEST['action']=='get_arm_paid_post_plan_list') {
                

                $text = sanitize_text_field( $_REQUEST['txt'] );//phpcs:ignore
                $type = 0;
                global $wp, $wpdb, $arm_errors, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings,$arm_capabilities_global;

                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_pay_per_post'],'1',1);
                $arm_subscription_plans_table = $ARMember->tbl_arm_subscription_plans;
                
               $paid_post_where = " WHERE ";
               $paid_post_where .= $wpdb->prepare("(`arm_subscription_plan_name` LIKE %s)",$text.'%');

                $paid_post_where .= " AND ";

                $paid_post_where .= $wpdb->prepare("`arm_subscription_plan_status`=%d AND `arm_subscription_plan_is_delete`=%d AND `arm_subscription_plan_post_id`!=%d ",1,0,0);
                $paid_post_fields = "arm_subscription_plan_name,arm_subscription_plan_description,arm_subscription_plan_id,arm_subscription_plan_post_id";
                $paid_post_order_by = " ORDER BY arm_subscription_plan_id DESC limit 0,10";
                
                $paid_post_query = "SELECT {$paid_post_fields} FROM `{$arm_subscription_plans_table}` {$paid_post_where} {$paid_post_order_by} ";
                $paid_post_plan_details = $wpdb->get_results($paid_post_query); //phpcs:ignore --Reason $paid_post_query is a predefined query

                $all_paid_post_plans = $paid_post_plan_details;
                
                $ppData = array();
                if(!empty($all_paid_post_plans)) {
                    foreach ( $all_paid_post_plans as $paid_post_plan ) {
                        $ppData[] = array(
                                    'id' => $paid_post_plan->arm_subscription_plan_id,
                                    'value' => $paid_post_plan->arm_subscription_plan_name,
                                    'label' => $paid_post_plan->arm_subscription_plan_name,
                                    'arm_paid_post_id' => $paid_post_plan->arm_subscription_plan_post_id
                                );
                    }
                }
                $response = array('status' => 'success', 'data' => $ppData);
                echo arm_pattern_json_encode($response);
                die;
            }    
        }

        function arm_get_paid_post_item_options(){
            global $wpdb, $ARMember,$arm_capabilities_global;

            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_pay_per_post'], '1',1);//phpcs:ignore --Reason:Verifying nonce
            $arm_post_type = isset( $_POST['arm_post_type'] ) ? sanitize_text_field( $_POST['arm_post_type'] ) : 'page';//phpcs:ignore

            $search_key = isset( $_POST['search_key'] ) ? sanitize_text_field( $_POST['search_key'] ) : '';//phpcs:ignore

            if( $search_key != '' ){
                $postQuery = $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, p.post_title FROM {$wpdb->posts} p WHERE p.post_type = %s AND p.post_title LIKE %s AND p.post_status = %s LIMIT 0,10", $arm_post_type, '%' . $wpdb->esc_like( $search_key ) . '%', 'publish' ) );
            } else {
                $postQuery = $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, p.post_title FROM {$wpdb->posts} p WHERE p.post_type = %s AND p.post_status = %s LIMIT 0,10", $arm_post_type, 'publish' ) );
            }

            $ppData = array();
            if( isset( $postQuery ) && !empty( $postQuery ) ){
                foreach( $postQuery as $k => $postData ){
                    $isEnablePaidPost = get_post_meta( $postData->ID, 'arm_is_paid_post', true );
                    if( 0 == $isEnablePaidPost || empty($isEnablePaidPost) ){
                        $ppData[] = array(
                            'id' => $postData->ID,
                            'value' => $postData->post_title,
                            'label' => $postData->post_title
                        );
                    }
                }
            }

            $response = array('status' => 'success', 'data' => $ppData);
            echo json_encode($response);
            die;
        }

        function arm_delete_single_paid_post() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_pay_per_post'], '1',1);//phpcs:ignore --Reason:Verifying nonce
            $action = sanitize_text_field( $_POST['act'] );//phpcs:ignore
            $post_id = intval($_POST['id']);//phpcs:ignore
            if ($action == 'delete') {
                if (empty($post_id)) {
                    $errors[] = esc_html__('Invalid action.', 'ARMember');
                } else {
                    update_post_meta( $post_id, 'arm_is_paid_post', 0 );

                    $is_post_exists = $wpdb->get_row( $wpdb->prepare( "SELECT arm_subscription_plan_id FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_post_id` = %d", $post_id ) ); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name

                    if( '' != $is_post_exists->arm_subscription_plan_id ){

                        delete_post_meta( $post_id, 'arm_access_plan', $is_post_exists->arm_subscription_plan_id );

                        $wpdb->update(
                            $ARMember->tbl_arm_subscription_plans,
                            array(
                                'arm_subscription_plan_is_delete' => 1
                            ),
                            array(
                                'arm_subscription_plan_id' => $is_post_exists->arm_subscription_plan_id
                            )
                        );
                    }
                    $message[] = esc_html__('Paid Post removed successfully', 'ARMember');
                }
            }
            $return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
            echo arm_pattern_json_encode($return_array);
            exit;
        }


        /**
         * Get all posts
         * @return array of posts, False if there is no post(s).
         */
        function arm_get_all_subscription_posts($fields = 'all', $object_type = ARRAY_A, $allow_user_no_post = false) {
            global $wp, $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings;
            $selectFields = '*';
            if (!empty($fields)) {
                if ($fields != 'all' && $fields != '*') {
                    $selectFields = $fields;
                }
            }
            $object_type = !empty($object_type) ? $object_type : ARRAY_A;
            $results = $wpdb->get_results( $wpdb->prepare("SELECT {$selectFields}, `arm_subscription_plan_id` FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_is_delete`=%d AND `arm_subscription_plan_post_id`!=%d ORDER BY `arm_subscription_plan_id` DESC",0,0), $object_type); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name
            if (!empty($results) || $allow_user_no_post) {
                $posts_data = array();
                if ($allow_user_no_post) {
                    $plnID = -2;
                    $plnName = esc_html__('Users Having No Post', 'ARMember');
                    if ($object_type == OBJECT || $object_type == OBJECT_K) {
                        $sp->arm_subscription_plan_id = $plnID;
                        $sp->arm_subscription_plan_name = $plnName;
                        $sp->arm_subscription_plan_description = '';
                        $sp->arm_subscription_plan_options = array();
                    } else {
                        $sp['arm_subscription_plan_id'] = $plnID;
                        $sp['arm_subscription_plan_name'] = $plnName;
                        $sp['arm_subscription_plan_description'] = '';
                        $sp['arm_subscription_plan_options'] = array();
                    }
                    $posts_data[$plnID] = $sp;
                }
                if (!empty($results)) {
                    foreach ($results as $sp) {
                        if ($object_type == OBJECT || $object_type == OBJECT_K) {
                            $plnID = $sp->arm_subscription_plan_id;
                            if (isset($sp->arm_subscription_plan_name)) {
                                $sp->arm_subscription_plan_name = stripslashes($sp->arm_subscription_plan_name);
                            }
                            if (isset($sp->arm_subscription_plan_description)) {
                                $sp->arm_subscription_plan_description = stripslashes($sp->arm_subscription_plan_description);
                            }
                            if (isset($sp->arm_subscription_plan_options)) {
                                $sp->arm_subscription_plan_options = maybe_unserialize($sp->arm_subscription_plan_options);
                            }
                        } else {
                            $plnID = $sp['arm_subscription_plan_id'];
                            if (isset($sp['arm_subscription_plan_name'])) {
                                $sp['arm_subscription_plan_name'] = stripslashes($sp['arm_subscription_plan_name']);
                            }
                            if (isset($sp['arm_subscription_plan_description'])) {
                                $sp['arm_subscription_plan_description'] = stripslashes($sp['arm_subscription_plan_description']);
                            }
                            if (isset($sp['arm_subscription_plan_options'])) {
                                $sp['arm_subscription_plan_options'] = maybe_unserialize($sp['arm_subscription_plan_options']);
                            }
                        }
                        $posts_data[$plnID] = $sp;
                    }
                }
                return $posts_data;
            } else {
                return FALSE;
            }
        }

        function arm_get_all_active_subscription_posts($orderby = '', $order = '', $allow_user_no_post = false) {
            global $wp, $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings;
            $orderby = (!empty($orderby)) ? $orderby : 'arm_subscription_plan_id';
            $order = (!empty($order) && $order == 'ASC') ? 'ASC' : 'DESC';
            /* Query Monitor Settings */
            if( isset($GLOBALS['arm_active_subscription_post_data'])){
                $results = $GLOBALS['arm_active_subscription_post_data'];
            } else {
                $results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_status`=%d AND `arm_subscription_plan_is_delete`=%d AND `arm_subscription_plan_post_id`!=%d ORDER BY `" . $orderby . "` " . $order . "",1,0,0), ARRAY_A); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name

                $GLOBALS['arm_active_subscription_post_data'] = $results;
            }
            if (!empty($results) || $allow_user_no_post) {
                $posts_data = array();
                if ($allow_user_no_post) {
                    $sp['arm_subscription_plan_id'] = -2;
                    $sp['arm_subscription_plan_name'] = esc_html__('Users Having No Plan', 'ARMember');
                    $sp['arm_subscription_plan_description'] = '';
                    $sp['arm_subscription_plan_options'] = array();
                    $posts_data[$sp['arm_subscription_plan_id']] = $sp;
                }
                if (!empty($results)) {
                    foreach ($results as $sp) {
                        $sp['arm_subscription_plan_name'] = stripslashes($sp['arm_subscription_plan_name']);
                        $sp['arm_subscription_plan_description'] = stripslashes($sp['arm_subscription_plan_description']);
                        $sp['arm_subscription_plan_options'] = maybe_unserialize($sp['arm_subscription_plan_options']);
                        $posts_data[$sp['arm_subscription_plan_id']] = $sp;
                    }
                }
                return $posts_data;
            } else {
                return FALSE;
            }
        }

        function arm_get_total_active_post_counts() {
            global $wp, $wpdb, $ARMember, $arm_global_settings;
            $post_counts = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(`arm_subscription_plan_id`) FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_status`=%d AND `arm_subscription_plan_is_delete`=%d AND `arm_subscription_plan_post_id`!=%d",1,0,0)); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name
            return $post_counts;
        }

        function arm_get_total_post_counts() {
            global $wp, $wpdb, $ARMember, $arm_global_settings;
            $post_counts = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(`arm_subscription_plan_id`) FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_is_delete`=%d AND `arm_subscription_plan_post_id`!=%d",0,0)); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name
            return $post_counts;
        }

        function arm_update_user_paid_post_ids( $null, $obj_id, $meta_key, $meta_value, $prev_value ){

            if( 'arm_user_plan_ids' == $meta_key ){

                $meta_value_arr = maybe_unserialize( $meta_value );

                $this->arm_update_user_post_ids( $obj_id, $meta_value_arr );

            }

            return $null;

        }
	
	    function arm_update_user_post_ids($user_id, $plan_id){
            global $wp, $wpdb, $ARMember;
            
            if($this->isPayPerPostFeature==true){

                $post_ids = $this->arm_get_post_from_plan_id( $plan_id );
                
                //$arm_post_meta_data = get_user_meta($user_id, 'arm_user_post_ids', true);

                //if(empty($arm_post_meta_data)){
                    $arm_post_meta_data = array();
                //}

                if( !empty( $post_ids ) ){
                    foreach( $post_ids as $post_id ){
                        if( !empty($post_id['arm_subscription_plan_post_id']) ){
                            $arm_post_meta_data[$post_id['arm_subscription_plan_id']] =  $post_id['arm_subscription_plan_post_id'];
                        }
                    }
                }
                update_user_meta($user_id, 'arm_user_post_ids', $arm_post_meta_data);
            }
        }

        function arm_add_paid_post_plan_in_setup_data( $setup_data, $posted_data ){

            if( isset( $posted_data['arm_paid_post'] ) && '' != $posted_data['arm_paid_post'] ){
                $setup_modules = $setup_data['arm_setup_modules']['modules'];
                $setup_modules2 = $setup_data['setup_modules']['modules'];

                if( !isset( $setup_modules['plans'] ) ){
                    $setup_data['arm_setup_modules']['modules']['plans']  = array( $posted_data['arm_paid_post'] );
                } else if( isset( $setup_modules['plans'] ) && !in_array( $posted_data['arm_paid_post'], $setup_modules['plans'] ) ){
                    $setup_data['arm_setup_modules']['modules']['plans'][] = $posted_data['arm_paid_post'];
                }

                if( !isset( $setup_modules2['plans'] ) ){
                    $setup_data['setup_modules']['modules']['plans']  = array( $posted_data['arm_paid_post'] );
                } else if( isset( $setup_modules2['plans'] ) && !in_array( $posted_data['arm_paid_post'], $setup_modules2['plans'] ) ){
                    $setup_data['setup_modules']['modules']['plans'][] = $posted_data['arm_paid_post'];
                }
            }

            return $setup_data;

        }
	
        function arm_modify_setup_data_for_paid_post_type_setup( $setup_data, $args ){
            global $arm_global_settings;
            $all_global_settings = $arm_global_settings;
            $general_settings = isset($all_global_settings->global_settings) ? $all_global_settings->global_settings : array();

            $arm_pay_per_post_buynow_var = (!empty($general_settings['arm_pay_per_post_buynow_var'])) ? $general_settings['arm_pay_per_post_buynow_var'] : 'arm_paid_post';

            $setup_type = isset( $setup_data['arm_setup_type'] ) ? $setup_data['arm_setup_type'] : 0;

            if( 1 == $setup_type ){
                $setup_data['arm_setup_modules']['modules']['plans'] = !empty($setup_data['arm_setup_modules']['modules']['plans']) ? $setup_data['arm_setup_modules']['modules']['plans'] : array();
                $setup_data['setup_modules']['modules']['plans'] = !empty($setup_data['setup_modules']['modules']['plans']) ? $setup_data['setup_modules']['modules']['plans'] : array();
                $setup_data['arm_setup_modules']['modules']['plans'] = $this->arm_remove_non_paid_post_plan( $setup_data['arm_setup_modules']['modules']['plans'] );
                $setup_data['setup_modules']['modules']['plans'] = $this->arm_remove_non_paid_post_plan( $setup_data['setup_modules']['modules']['plans'] );
            }

            $paid_post_id = "";

            if( !isset( $_GET[$arm_pay_per_post_buynow_var] ) ) {
                if(function_exists('get_the_ID')){
                	$paid_post_id = get_the_ID();
                }
                if( !empty( $args['is_arm_paid_post'] ) && 1 == $args['is_arm_paid_post'] ){
                    $paid_post_id = $args['paid_post_id'];
                    
                }
                if(empty($paid_post_id)){
                	return $setup_data;
                }
            } else {
                $paid_post_id = isset( $_GET[$arm_pay_per_post_buynow_var] ) ? sanitize_text_field($_GET[$arm_pay_per_post_buynow_var]) : '';
            }

            if( empty($paid_post_id) ){
                return $setup_data;
            }


            if( !isset( $setup_type ) || ( isset( $setup_type ) && 1 != $setup_type ) ){
                return $setup_data;
            }

            $plan_id = $this->arm_get_plan_from_post_id( $paid_post_id );

            if( isset( $plan_id ) && '' != $plan_id ){

                if( !isset( $setup_data['arm_setup_modules']['modules']['plans'] ) ){
                    $setup_data['arm_setup_modules']['modules']['plans'] = array( $plan_id );
                } else {
                    $setup_data['arm_setup_modules']['modules']['plans'][] = $plan_id;
                }

                $plan_order = isset( $setup_data['arm_setup_modules']['modules']['plans_order'] ) ? $setup_data['arm_setup_modules']['modules']['plans_order'] : array();

                if( empty( $plan_order ) ){
                    $setup_data['arm_setup_modules']['modules']['plans_order'][$plan_id] = 1;
                } else {
                    $maxOrder = max( $plan_order );
                    $nextOrder = $maxOrder + 1;
                    $setup_data['arm_setup_modules']['modules']['plans_order'][$plan_id] = $nextOrder;
                }

                if( !isset( $setup_data['setup_modules']['modules']['plans'] ) ){
                    $setup_data['setup_modules']['modules']['plans'] = array( $plan_id );
                } else {
                    $setup_data['setup_modules']['modules']['plans'][] = $plan_id;
                }

                $plan_order2 = isset( $setup_data['setup_modules']['modules']['plans_order'] ) ? $setup_data['setup_modules']['modules']['plans_order'] : array();

                if( empty( $plan_order2 ) ){
                    $setup_data['setup_modules']['modules']['plans_order'][$plan_id] = 1;
                } else {
                    $maxOrder = max( $plan_order2 );
                    $nextOrder = $maxOrder + 1;
                    $setup_data['setup_modules']['modules']['plans_order'][$plan_id] = $nextOrder;
                }

                $setup_data['arm_paid_post_plan_id'] = $plan_id;

            }


            if(!current_user_can('administrator'))
            {
                global $wpdb, $ARMember;

                $planType = $wpdb->get_row( $wpdb->prepare( "SELECT arm_subscription_plan_type FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE arm_subscription_plan_id = %d AND arm_subscription_plan_is_delete = %d", $plan_id, 0 ) ); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name

                if(is_user_logged_in() && (!empty($planType->arm_subscription_plan_type)) && $planType->arm_subscription_plan_type == "free" )
                {
                    $login_user_id = get_current_user_id();
                    $arm_paid_plan_ids = get_user_meta($login_user_id, 'arm_user_plan_ids', true);
                    if( is_array($arm_paid_plan_ids) && !in_array($plan_id, $arm_paid_plan_ids) )
                    {
                        do_action( 'arm_apply_plan_to_member', $plan_id, $login_user_id);
                        $paid_post_redirect = get_permalink($paid_post_id);
                        wp_redirect($paid_post_redirect);
                    }
                }
            }

            return $setup_data;
        }

        function arm_get_plan_from_post_id( $post_id = '' ){
            if( empty($post_id) ){
                return;
            }

            global $wpdb, $ARMember;

            $planId = $wpdb->get_row( $wpdb->prepare( "SELECT arm_subscription_plan_id FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE arm_subscription_plan_post_id = %d AND arm_subscription_plan_is_delete = %d", $post_id, 0 ) ); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name  

            if( isset( $planId->arm_subscription_plan_id ) ){
                return $planId->arm_subscription_plan_id;
            } else {
                return '';
            }

        }

        function arm_get_post_from_plan_id( $plan_id ){
            if( empty($plan_id) ){
                return;
            }

            global $wpdb, $ARMember;

            if( is_array( $plan_id ) && !empty($plan_id) ){
                $page_placeholders = 'arm_subscription_plan_id IN (';
                $page_placeholders .= rtrim( str_repeat( '%s,', count( $plan_id ) ), ',' );
                $page_placeholders .= ')';
                array_unshift( $plan_id, $page_placeholders );
                $where = call_user_func_array(array( $wpdb, 'prepare' ), $plan_id );
                $postId = $wpdb->get_results( "SELECT arm_subscription_plan_id,arm_subscription_plan_post_id FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE ".$where, ARRAY_A );//phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name
            } else {
                $postId = $wpdb->get_results( $wpdb->prepare( "SELECT arm_subscription_plan_id,arm_subscription_plan_post_id FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE arm_subscription_plan_id = %d", $plan_id ), ARRAY_A );//phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name
            }

            return $postId;            
        }

        function arm_add_paid_post_plan_in_active_subscription_pans( $all_active_plans ){
            $paid_post_id = "";
	    global $arm_global_settings;
            $all_global_settings = $arm_global_settings;
            $general_settings = isset($all_global_settings->global_settings) ? $all_global_settings->global_settings : array();

            $arm_pay_per_post_buynow_var = (!empty($general_settings['arm_pay_per_post_buynow_var'])) ? $general_settings['arm_pay_per_post_buynow_var'] : 'arm_paid_post';
            if( !isset( $_REQUEST[$arm_pay_per_post_buynow_var] ) ) {
                if(function_exists('get_the_ID'))
		{
                	$paid_post_id = get_the_ID();
		}
		if(empty($paid_post_id))
		{
                	return $all_active_plans;
		}
            }else{
                $paid_post_id = isset( $_REQUEST[$arm_pay_per_post_buynow_var] ) ? sanitize_text_field( $_REQUEST[$arm_pay_per_post_buynow_var] ) : '';
            }


            if( empty($paid_post_id) ){
                return $all_active_plans;
            }

            global $wpdb, $ARMember;

            $planId = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE arm_subscription_plan_post_id = %d AND arm_subscription_plan_is_delete = %d", $paid_post_id, 0 ), ARRAY_A ); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name

            if( isset( $planId ) && !empty( $planId ) ){

                $plan_id = $planId['arm_subscription_plan_id'];

                $planId['arm_subscription_plan_name'] = stripslashes($planId['arm_subscription_plan_name']);
                
                $planId['arm_subscription_plan_description'] = !empty($planId['arm_subscription_plan_description'])?stripslashes($planId['arm_subscription_plan_description']):'';
                
                $planId['arm_subscription_plan_options'] = maybe_unserialize($planId['arm_subscription_plan_options']);

                $all_active_plans[ $plan_id ] = $planId;

            }
        
            return $all_active_plans;
        }

        function arm_add_paid_post_plan_id( $module_content, $setupID, $setup_data ){


            if( isset( $setup_data['arm_paid_post_plan_id'] ) && '' != $setup_data['arm_paid_post_plan_id'] ){
                global $wpdb, $ARMember;

                $plan_id = $setup_data['arm_paid_post_plan_id'];

                $get_pp_id = $wpdb->get_row( $wpdb->prepare( "SELECT arm_subscription_plan_post_id FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE arm_subscription_plan_id = %d", $plan_id ) ); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name

                if( isset( $get_pp_id->arm_subscription_plan_post_id ) && '' != $get_pp_id->arm_subscription_plan_post_id ){

                    $module_content .= '<input type="hidden" name="arm_paid_post" value="'. $get_pp_id->arm_subscription_plan_post_id .'" />';

                }
                
            }

            return $module_content;
        }
	
	function arm_get_paid_post_setup(){
            global $wpdb, $ARMember;
            
            $getTotalPaidPostSetup = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(arm_setup_id) FROM `" . $ARMember->tbl_arm_membership_setup . "` WHERE `arm_setup_type` = %d", 1 ) ); //phpcs:ignore --Reason $ARMember->tbl_arm_membership_setup is a table name

            return $getTotalPaidPostSetup;
        }
	
	function arm_remove_non_paid_post_plan( $setup_plans = array() ){
            global $wpdb, $ARMember;

            if( empty( $setup_plans ) ){
                return $setup_plans;
            }

            $updated_setup_plans = array();
            foreach( $setup_plans as $plan_id ){
                $planData = new ARM_Plan( $plan_id );

                if( 0 < $planData->isPaidPost ){
                    array_push( $plan_id, $updated_setup_plans );
                }
            }

            return $updated_setup_plans;

        }

        function arm_update_paid_post_transaction( $payment_data ){
            global $wpdb, $ARMember,$arm_membership_setup;

            $plan_id = isset( $payment_data['arm_plan_id'] ) ? $payment_data['arm_plan_id'] : 0;
            $arm_user_id = isset($payment_data['arm_user_id']) ? $payment_data['arm_user_id'] : 0;
            if( !empty( $plan_id )){

                $planData = new ARM_Plan( $plan_id );
                $plan_options = $planData->options;
                if( !empty( $planData->isPaidPost ) ){
                    $wpdb->update(
                        $ARMember->tbl_arm_payment_log,
                        array(
                            'arm_is_post_payment' => 1,
                            'arm_paid_post_id' => $planData->isPaidPost
                        ),
                        array(
                            'arm_log_id' => $payment_data['arm_log_id']
                        )
                    );
                }
                
                if(empty( $planData->isPaidPost ))
                {
                    $is_plan_assigned = 1;
                    $pgateway = '';
                    $log_id=0;
                    $this->arm_assign_paid_post_to_user($arm_user_id,$plan_id,$log_id,$pgateway, $is_plan_assigned);
                }
            }

        }


        /*
        function armpay_per_post_add_fancy_url_rule()
        {
            if($this->isPayPerPostFeature)
            {
                
                // if( get_option( 'armpay_per_post_flush_rewrites' ) ) {
                //     flush_rewrite_rules();
                //     delete_option( 'armpay_per_post_flush_rewrites' );
                // }


                global $arm_global_settings;
                $all_global_settings = $arm_global_settings->arm_get_all_global_settings();
                $general_settings = $all_global_settings['general_settings'];

                $arm_pay_per_post_referral_var = (!empty($general_settings['arm_pay_per_post_referral_var'])) ? $general_settings['arm_pay_per_post_referral_var'] : 'arm_paid_post_id';
                $arm_pay_per_post_allow_fancy_url = (!empty($general_settings['arm_pay_per_post_allow_fancy_url'])) ? $general_settings['arm_pay_per_post_allow_fancy_url'] : 0;

                
                // if($arm_pay_per_post_allow_fancy_url>0)
                // {
                //     $taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ), 'objects' );
                //     foreach( $taxonomies as $tax_id => $tax ) {
                //         add_rewrite_rule( $tax->rewrite['slug'] . '/(.+?)/' . $arm_pay_per_post_referral_var . '(/(.*))?/?$', 'index.php?' . $tax_id . '=$matches[1]&' . $arm_pay_per_post_referral_var . '=$matches[3]', 'top');
                //     }
                //     //add_rewrite_rule( '/(.+?)/' . $arm_pay_per_post_referral_var . '(/(.*))?/?$', 'index.php?' . $arm_pay_per_post_referral_var . '=$matches[1]', 'top');
                //     add_rewrite_endpoint( $arm_pay_per_post_referral_var, EP_ALL );
                // }
                
            }
        }*/
	
	/**
        * `[arm_paid_post_buy_now]` shortcode function
        */
        function arm_paid_post_buy_now_func($atts, $content, $tag) {

            global $ARMember, $arm_global_settings;
            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }

            /* ====================/.Begin Set Shortcode Attributes./==================== */
            $atts = shortcode_atts(array(
                'label' => esc_html__('Buy Now', 'ARMember'),
                'type' => 'link',
                'redirect_url' => '',
                'success_url' => '',
                'link_css' => '',
                'link_hover_css' => '',
                    ), $atts, $tag);
            
            $atts = array_map( array( $ARMember, 'arm_recursive_sanitize_data_extend_only_kses'), $atts ); //phpcs:ignore
            
            /* ====================/.End Set Shortcode Attributes./==================== */
            global $wp, $wpdb, $current_user, $arm_slugs;

            $paid_post_shortcode_redirect_url = !empty($atts['redirect_url']) ? $atts['redirect_url'] : '';
            $paid_post_shortcode_success_url = !empty($atts['success_url']) ? $atts['success_url'] : '';
            
            $arm_all_global_settings = $arm_global_settings->arm_get_all_global_settings();
            $page_settings = $arm_all_global_settings['page_settings'];
            $redirct_url = (!empty($page_settings['paid_post_page_id'])) ? $page_settings['paid_post_page_id'] : '';

            $redirect_to = "";
            $current_post_id = get_the_ID();
            $arm_post_hasaccess = false;
            if (!current_user_can('administrator') && is_singular() && in_the_loop() && is_main_query() ) 
            {
                
                if(!empty($current_post_id))
                {
                    $arm_is_paid_post = get_post_meta($current_post_id, 'arm_is_paid_post', true);
                    if(!empty($arm_is_paid_post))
                    {
                        $plan_id = $this->arm_get_plan_from_post_id( $current_post_id );
                        if( !empty( $plan_id ) )
                        {
                            
                            $isLoggedIn = is_user_logged_in();
                            if($isLoggedIn)
                            {
                                $current_user_id = get_current_user_id();
                                $arm_user_plan = get_user_meta($current_user_id, 'arm_user_plan_ids', true);
                                $arm_user_plan = !empty($arm_user_plan) ? $arm_user_plan : array();
                                if(!empty($arm_user_plan)){
                                    $suspended_plan_ids = get_user_meta($current_user_id, 'arm_user_suspended_plan_ids', true);
                                    if( ! empty($suspended_plan_ids)) {
                                        foreach ($suspended_plan_ids as $suspended_plan_id) {
                                            if(in_array($suspended_plan_id, $arm_user_plan)) {
                                                unset($arm_user_plan[array_search($suspended_plan_id, $arm_user_plan)]);
                                            }
                                        }
                                    }

                                    if(in_array($plan_id, $arm_user_plan))
                                    {
                                        $arm_post_hasaccess = true;
                                        $content='';
                                    }
                                }
                            }
                            
                        }
                        
                    }
                }
                
            }
            if($arm_post_hasaccess==false)
            {
           
                if(empty($paid_post_shortcode_redirect_url))
                {
                    if($redirct_url != "")
                    {
                        $redirect_to = get_the_permalink($page_settings['paid_post_page_id']);
                    }
                    else
                    {
                        $redirect_to = get_permalink($current_post_id);
                    }
                }
                else
                {
                    $redirect_to = $paid_post_shortcode_redirect_url;
                }


                $all_global_settings = $arm_global_settings;
                $general_settings = isset($all_global_settings->global_settings) ? $all_global_settings->global_settings : array();

                $arm_pay_per_post_buynow_var = (!empty($general_settings['arm_pay_per_post_buynow_var'])) ? $general_settings['arm_pay_per_post_buynow_var'] : 'arm_paid_post';
                $arm_pay_per_post_allow_fancy_url = (!empty($general_settings['arm_pay_per_post_allow_fancy_url'])) ? $general_settings['arm_pay_per_post_allow_fancy_url'] : '';
                $arm_pay_per_post_success_var = 'arm_success_url';

                
                if($redirect_to == "" && empty($current_post_id))
                {
                    $redirect_to = ARM_HOME_URL;
                }

                $redirect_to = apply_filters('arm_modify_redirection_page_external', $redirect_to,0,$current_post_id);

                $arm_success_fancy_url = '';
                $query_arg = array();
                $query_arg[$arm_pay_per_post_buynow_var] = $current_post_id;
                if (!empty($paid_post_shortcode_success_url)) {
                    $arm_success_fancy_url = '/'.$arm_pay_per_post_success_var.'/'.$paid_post_shortcode_success_url;
                    $query_arg[$arm_pay_per_post_success_var] = $paid_post_shortcode_success_url;
                }

                $paid_post_buy_now_url = "";
                if(substr($redirect_to, -1) == '/')
                { 
                    $paid_post_buy_now_url = ($arm_pay_per_post_allow_fancy_url) ? $redirect_to.$arm_pay_per_post_buynow_var.'/'.$current_post_id.$arm_success_fancy_url : add_query_arg($query_arg, $redirect_to);
                }
                else
                {
                    $paid_post_buy_now_url = ($arm_pay_per_post_allow_fancy_url) ? $redirect_to."/".$arm_pay_per_post_buynow_var.'/'.$current_post_id.$arm_success_fancy_url : add_query_arg($query_arg, $redirect_to);
                }

                $paid_post_buy_now_url = wp_nonce_url($paid_post_buy_now_url);
                $paidPostWrapper = arm_generate_random_code();
                $content = apply_filters('arm_before_paid_post_buy_now_shortcode_content', $content, $atts);
                //$content .= '<div class="arm_paid_post_container" id="arm_paid_post_' . $paidPostWrapper . '">';
                $btnStyle = '';
                if (!empty($atts['link_css'])) {
                    $btnStyle .= '.arm_paid_post_buy_now_btn{' . esc_html($atts['link_css']) . '}';
                }
                if (!empty($atts['link_hover_css'])) {
                    $btnStyle .= '.arm_paid_post_buy_now_btn:hover{' . esc_html($atts['link_hover_css']) . '}';
                }
                if (!empty($btnStyle)) {
                    $content .= '<style type="text/css">' . $btnStyle . '</style>';
                }
                
                if ($atts['type'] == 'button') {
                    $content .= '<form method="post" class="arm_paid_post_buy_now" name="arm_paid_post_buy_now" action="' . esc_url($paid_post_buy_now_url) . '" enctype="multipart/form-data">';
                    $content .= '<button type="submit" class="arm_paid_post_buy_now_btn arm_paid_post_buy_now_button">' . $atts['label'] . '</button>';
                    $content .= '</form>';
                } else {
                    $content .= '<a href="' . esc_url($paid_post_buy_now_url) . '" title="' . esc_attr($atts['label']) . '" class="arm_paid_post_buy_now_btn arm_paid_post_buy_now_link">' . $atts['label'] . '</a>';
                }
                    
                $content = apply_filters('arm_after_paid_post_buy_now_shortcode_content', $content, $atts);

                $ARMember->arm_check_font_awesome_icons($content);
            }
            return do_shortcode($content);
        }

    
        function arm_get_paid_post_plans_paging($user_id = 0, $current_page = 1, $per_page = 5){
            
            global $arm_global_settings,$arm_subscription_plans,$is_multiple_membership_feature;

            $arm_paid_post_plans_wrapper = "";
            if (!empty($user_id) && $user_id != 0) {
                $arm_common_date_format = $arm_global_settings->arm_check_common_date_format(get_option('date_format'));
                $planIDs = get_user_meta($user_id, 'arm_user_post_ids', true);
                $futurePlanIDs = get_user_meta($user_id, 'arm_user_future_plan_ids', true);
                $futurePlanIDs = !empty($futurePlanIDs) ? $futurePlanIDs : array();
                if( !empty( $futurePlanIDs ) ){
                    foreach( $futurePlanIDs as $fPlanKey => $fPlanId ){
                        $fPlanData = $this->arm_get_post_from_plan_id( $fPlanId );

                        if( !empty( $fPlanData[0]['arm_subscription_plan_id'] ) && !empty( $fPlanData[0]['arm_subscription_plan_post_id'] ) ){
                            $planIDs[$fPlanData[0]['arm_subscription_plan_id']] = $fPlanData[0]['arm_subscription_plan_post_id'];
                        }
                    }
                }

                $arm_paid_post_plans_wrapper = '';
                if (!empty($planIDs) || !empty($futurePlanIDs)) {
                
                $arm_paid_post_plans_wrapper .= '<div class="arm_add_member_plans_div arm_paid_post_plans_wrapper" data-user_id="'.esc_attr($user_id).'">';

                $arm_paid_post_plans_wrapper.= '<table class="arm_user_plan_table">';
                $arm_paid_post_plans_wrapper.= '<tr class="odd">';
                $arm_paid_post_plans_wrapper.= '<th class="arm_user_plan_text_th arm_user_plan_no">'. esc_html__('No', 'ARMember').'</th>';
                $arm_paid_post_plans_wrapper.= '<th class="arm_user_plan_text_th arm_user_plan_name">'. esc_html__('Post Name', 'ARMember').'</th>';
                $arm_paid_post_plans_wrapper.= '<th class="arm_user_plan_text_th arm_user_plan_type">'. esc_html__('Post Type', 'ARMember').'</th>';
                $arm_paid_post_plans_wrapper.= '<th class="arm_user_plan_text_th arm_user_plan_start">'. esc_html__('Starts On', 'ARMember').'</th>';
                $arm_paid_post_plans_wrapper.= '<th class="arm_user_plan_text_th arm_user_plan_end">'. esc_html__('Expires On', 'ARMember').'</th>';
                $arm_paid_post_plans_wrapper.= '<th class="arm_user_plan_text_th arm_user_plan_cycle_date">'. esc_html__('Cycle Date', 'ARMember').'</th>';
                $arm_paid_post_plans_wrapper.= '<th class="arm_user_plan_text_th arm_user_plan_action">'. esc_html__('Action', 'ARMember').'</th>';
                $arm_paid_post_plans_wrapper.= '</tr>';

                            $offset = (!empty($current_page) && $current_page > 1) ? (($current_page - 1) * $per_page) : 0;
                            $membership_count = count($planIDs);
                            $planIDs_slice = array_slice($planIDs, $offset, $per_page);
                            
                            $date_format = $arm_global_settings->arm_get_wp_date_format();
                            $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                            $suspended_plan_ids = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
                            $suspended_plan_ids = (isset($suspended_plan_ids) && !empty($suspended_plan_ids)) ? $suspended_plan_ids : array();
                            
                            $count_plans = 0;
                            if( $current_page > 1 ){
                                $count_plans = $count_plans + $per_page;
                            }
                            if (!empty($planIDs)) {
                                foreach ($planIDs as $pID => $arm_paid_post_id) {
                                    $uniq_delete_no = uniqid();
                                    if (!empty($pID) && in_array($arm_paid_post_id, $planIDs_slice)) {
                                        $planData = get_user_meta($user_id, 'arm_user_plan_' . $pID, true);
                                        $planData = !empty($planData) ? $planData : array();

                                        if (!empty($planData) && !empty($planData['arm_current_plan_detail']['arm_subscription_plan_post_id'])) {
                                            $planDetail = $planData['arm_current_plan_detail'];
                                            if (!empty($planDetail)) {
                                                $planObj = new ARM_Plan(0);
                                                $planObj->init((object) $planDetail);
                                            } else {
                                                $planObj = new ARM_Plan($pID);
                                            }

                                            $no = $count_plans;
                                            $planName = $planObj->name;
                                            $grace_message = '';
                                            
                                            $starts_date = !empty($planData['arm_start_plan']) ? $planData['arm_start_plan'] : '';
                                            $started_date = !empty($planData['arm_started_plan_date']) ? $planData['arm_started_plan_date'] : '';

                                            $starts_on = !empty($starts_date) ? date_i18n($date_format, $starts_date) : '-';

                                            if($started_date != '' && $started_date <= $starts_date) {
                                                $starts_on = date_i18n($date_format, $started_date);
                                            }

                                            $expires_on = !empty($planData['arm_expire_plan']) ? '<span id="arm_user_expiry_date_' . esc_attr($pID) . '" style="display: flex;"> ' . date_i18n($date_format, $planData['arm_expire_plan']) . ' <img src="' . MEMBERSHIPLITE_IMAGES_URL . '/edit_icon.svg" width="20" style="margin: -4px 0 0 5px; cursor: pointer;" title="' . esc_attr__('Change Expiry Date', 'ARMember') . '" data-plan_id="' . esc_attr($pID) . '" class="arm_edit_user_expiry_date"></span><span id="arm_user_expiry_date_box_' . esc_attr($pID) . '" style="display: none; position: relative; width: 155px;"><input type="text" value="' . esc_attr( date($arm_common_date_format, $planData['arm_expire_plan']) ) . '"  data-date_format="'.esc_attr($arm_common_date_format).'" name="arm_subscription_expiry_date_' . esc_attr($pID) . '" class="arm_member_form_input arm_user_plan_expiry_date_picker arm_width_200 arm_min_width_200" /><img src="' . MEMBERSHIPLITE_IMAGES_URL . '/cancel_date_icon.png" width="11" height="11" title="' . esc_attr__('Cancel', 'ARMember') . '" data-plan_id="' . esc_attr($pID) . '" data-plan-expire-date="' . esc_attr(date($arm_common_date_format, $planData['arm_expire_plan'])) . '" class="arm_cancel_edit_user_expiry_date"></span>' : esc_html__('Never Expires', 'ARMember');
                                            $renewal_on = !empty($planData['arm_next_due_payment']) ? date_i18n($date_format, $planData['arm_next_due_payment']) : '-';
                                            $trial_starts = !empty($planData['arm_trial_start']) ? $planData['arm_trial_start'] : '';
                                            $trial_ends = !empty($planData['arm_trial_end']) ? $planData['arm_trial_end'] : '';
                                            $arm_payment_mode = ( $planData['arm_payment_mode'] == 'auto_debit_subscription') ? '<br/>(' . esc_html__('Auto Debit','ARMember') . ')' : '';
                                            $arm_payment_cycle = !empty($planData['arm_payment_cycle']) ? $planData['arm_payment_cycle'] : '';

                                            if ($planObj->is_recurring()) {
                                                $recurring_plan_options = $planObj->prepare_recurring_data($arm_payment_cycle);
                                                $recurring_time = $recurring_plan_options['rec_time'];
                                                $completed = $planData['arm_completed_recurring'];
                                                if ($recurring_time == 'infinite' || empty($planData['arm_expire_plan'])) {
                                                    $remaining_occurence = esc_html__('Never Expires', 'ARMember');
                                                } else {
                                                    $remaining_occurence = $recurring_time - $completed;
                                                }

                                                if (!empty($planData['arm_expire_plan'])) {
                                                    if ($remaining_occurence == 0) {
                                                        $renewal_on = esc_html__('No cycles due', 'ARMember');
                                                    } else {
                                                        $renewal_on .= "<br/>( " . $remaining_occurence . esc_html__(' cycles due', 'ARMember') . " )";
                                                    }
                                                }

                                                $arm_is_user_in_grace = $planData['arm_is_user_in_grace'];

                                                $arm_grace_period_end = $planData['arm_grace_period_end'];

                                                if ($arm_is_user_in_grace == "1" || $arm_is_user_in_grace == 1) {
                                                    $arm_grace_period_end = date_i18n($date_format, $arm_grace_period_end);
                                                    $grace_message .= "<br/>( " . esc_html__('grace period expires on', 'ARMember') ." ". $arm_grace_period_end . " )";
                                                }
                                            }

                                            $arm_plan_is_suspended = '';

                                            if (!empty($suspended_plan_ids)) {
                                                if (in_array($pID, $suspended_plan_ids)) {
                                                    $arm_plan_is_suspended = '<div class="arm_user_plan_status_div arm_position_relative" ><span class="armhelptip tipso_style" id="arm_user_suspend_plan_' . esc_attr($pID) . '" style="color: red; cursor:pointer;" onclick="arm_show_failed_payment_history(' . $user_id . ',' . $pID . ',\'' . $planName . '\',\'' . $planData['arm_start_plan'] . '\')" title="' . esc_attr__('Click here to Show failed payment history', 'ARMember') . '">(' . esc_html__('Suspended', 'ARMember') . ')</span><img src="' . MEMBERSHIPLITE_IMAGES_URL . '/edit_icon.svg" width="20" style="position: absolute; margin: -4px 0 0 5px; cursor: pointer;" title="' . esc_attr__('Activate Post', 'ARMember') . '" data-plan_id="' . esc_attr($pID) . '" onclick="showConfirmBoxCallback(\'change_user_plan_' . $pID . '\');" class="arm_change_user_plan_img_' . esc_attr($pID) . '">
                
                                                    <div class="arm_confirm_box arm_member_edit_confirm_box" id="arm_confirm_box_change_user_plan_' . esc_attr($pID) . '" style="top:25px; left: 0; ">
                                                            <div class="arm_confirm_box_body">
                                                                <div class="arm_confirm_box_arrow" style="float: right"></div>
                                                                <div class="arm_confirm_box_text_title">'.esc_html__( 'Activate Post', 'ARMember' ).'</div>
                                                                <div class="arm_confirm_box_text arm_padding_top_15" >' .
                                                            esc_html__('Are you sure you want to active this paid post?', 'ARMember') . '
                                                                </div>
                                                                <div class="arm_confirm_box_btn_container arm_display_flex">
                                                                    <button type="button" class="arm_confirm_box_btn armcancel" onclick="hideConfirmBoxCallback();">' . esc_html__('Cancel', 'ARMember') . '</button>
                                                                    <button type="button" class="arm_confirm_box_btn armemailaddbtn arm_margin_left_12" id="arm_change_user_plan_status" data-index="' . esc_attr($pID) . '" >' . esc_html__('Ok', 'ARMember') . '</button>
                                                                    
                                                                </div>
                                                            </div>
                                                        </div>

                                            </div>';
                                                }
                                            }

                                            $trial_active = '';
                                            if (!empty($trial_starts)) {
                                                if ($planData['arm_is_trial_plan'] == 1 || $planData['arm_is_trial_plan'] == '1') {
                                                    if ($trial_starts < $planData['arm_start_plan']) {
                                                        $trial_active = "<div class='arm_user_plan_status_div'><span class='arm_current_membership_trial_active'>(" . esc_html__('trial active', 'ARMember') . ")</span></div>";
                                                    }
                                                }
                                            }
                                            

                                            
                                                
                                            
                                            $count_plans_is_odd_even = ($count_plans % 2 == 0) ? 'even' : 'odd';
                                            $get_last_plan_id_key = array_key_last($planIDs);
                                            if($pID == $get_last_plan_id_key)
                                            {
                                                $count_plans_is_odd_even .= ' arm_no_border';
                                            }                                           
                                            $count_plans_new = $count_plans + 1;    
                                            $arm_paid_post_plans_wrapper.= '<tr class="arm_user_plan_table_tr '.$count_plans_is_odd_even.'" id="arm_user_plan_div_'.esc_attr($uniq_delete_no).'">';
                                            $arm_paid_post_plans_wrapper.= '<td data-label="'.esc_html__('No', 'ARMember').'">'.$count_plans_new.'</td>';
                                            $arm_paid_post_plans_wrapper.= '<td data-label="'.esc_html__('Post Name', 'ARMember').'">'.$planName . $arm_plan_is_suspended.'</td>';
                                            $arm_paid_post_plans_wrapper.= '<td data-label="'.esc_html__('Post Type', 'ARMember').'">'.$planObj->new_user_plan_text(false, $arm_payment_cycle).'</td>';
                                            $arm_paid_post_plans_wrapper.= '<td data-label="'.esc_html__('Starts On', 'ARMember').'">'.$starts_on . $trial_active.'</td>';
                                            $arm_paid_post_plans_wrapper.= '<td data-label="'.esc_html__('Expires On', 'ARMember').'">'.$expires_on.'</td>';
                                            $arm_paid_post_plans_wrapper.= '<td data-label="'.esc_html__('Action', 'ARMember').'">'.$renewal_on . $grace_message . $arm_payment_mode.'</td>';

                                            $arm_paid_post_plans_wrapper.= '<td>';

                                                    
                                            if ($planObj->is_recurring() && $planData['arm_payment_mode'] == 'manual_subscription' && !in_array($pID, $futurePlanIDs)) {

                                                $recurringData = $planObj->prepare_recurring_data($arm_payment_cycle);

                                                $total_recurrence = $recurringData['rec_time'];
                                                $completed_rec = $planData['arm_completed_recurring'];
                                                
                                                $arm_paid_post_plans_wrapper.= '<div class="arm_float_left arm_position_relative">';
                                                   
                                                    if (!in_array($pID, $suspended_plan_ids) && $total_recurrence != $completed_rec) {
                                                        
                                                        $arm_paid_post_plans_wrapper.= '<a href="javascript:void(0)" id="arm_extend_cycle_days" class="arm_user_extend_renewal_date_action_btn" onclick="showConfirmBoxCallback(\'extend_renewal_date_'.$pID.'\');">'.esc_html__('Extend Days', 'ARMember').'</a>';

                                                        $arm_paid_post_plans_wrapper.= '<div class="arm_confirm_box arm_member_edit_confirm_box arm_confirm_box_extend_renewal_date" id="arm_confirm_box_extend_renewal_date_'.esc_attr($pID).'">';
                                                        $arm_paid_post_plans_wrapper.= '<div class="arm_confirm_box_body">';
                                                        $arm_paid_post_plans_wrapper.= '<div class="arm_confirm_box_arrow"></div>';
                                                        $arm_paid_post_plans_wrapper.= '<div class="arm_confirm_box_text_title">'.esc_html__( 'Extend days', 'ARMember' ).'</div>';
                                                        $arm_paid_post_plans_wrapper.= '<div class="arm_confirm_box_text arm_padding_top_0">';
                                                        $arm_paid_post_plans_wrapper.= '<span class="arm_margin_bottom_5 arm_font_size_15">'.esc_html__('Select how many days you want to extend in current cycle?', 'ARMember').'</span><div class="arm_margin_top_10">';
                                                        $arm_paid_post_plans_wrapper.= '<input type="hidden" id="arm_user_grace_plus_'.esc_attr($pID).'" name="arm_user_grace_plus_'.esc_attr($pID).'" value="0" class="arm_user_grace_plus"/>';
                                                        $arm_paid_post_plans_wrapper.= '<dl class="arm_selectbox column_level_dd arm_member_form_dropdown arm_width_83">
                                                                            <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                                            <dd>';
                                                        $arm_paid_post_plans_wrapper.= '<ul data-id="arm_user_grace_plus_'.esc_attr($pID).'">';
                                                                                    
                                                                                    for ($i = 0; $i <= 30; $i++) {
                                                                                        
                                                                                        $arm_paid_post_plans_wrapper.= '<li data-label='.esc_attr($i).' data-value='.esc_attr($i).'>'.esc_html($i).'</li>';
                                                                                        
                                                                                    }
                                                                                    
                                                        $arm_paid_post_plans_wrapper.= '</ul>';
                                                        $arm_paid_post_plans_wrapper.= '</dd>';
                                                        $arm_paid_post_plans_wrapper.= '</dl>&nbsp;&nbsp;'.esc_html__('Days', 'ARMember').'</div>';
                                                        $arm_paid_post_plans_wrapper.= '</div>';
                                                        $arm_paid_post_plans_wrapper.= '<div class="arm_confirm_box_btn_container arm_display_flex">';
                                                        $arm_paid_post_plans_wrapper.= '<button type="button" class="arm_confirm_box_btn armcancel arm_user_extend_renewal_date_cancel_btn" onclick="hideUserExtendRenewalDateBoxCallback('.$pID.');">'.esc_html__('Cancel', 'ARMember').'</button>';
                                                        $arm_paid_post_plans_wrapper.= '<button type="button" class="arm_confirm_box_btn armemailaddbtn arm_margin_left_12"  onclick="hideConfirmBoxCallback();">'.esc_html__('Ok', 'ARMember').'</button>';
                                                        
                                                        $arm_paid_post_plans_wrapper.= '</div>';
                                                            
                                                        $arm_paid_post_plans_wrapper.= '</div>';
                                                        $arm_paid_post_plans_wrapper.= '</div>';
                                                       
                                                    }
                                                    
                                                    
                                                    if ($total_recurrence != $completed_rec) {
                                                         
                                                        $arm_paid_post_plans_wrapper .= '<a href="javascript:void(0)" class="arm_user_renew_next_cycle_action_btn arm_margin_right_5" id="arm_skip_next_cycle" onclick="showConfirmBoxCallback(\'renew_next_cycle_'.$pID.'\');">'.esc_html__('Renew Cycle', 'ARMember').'</a>';
                                                        $arm_paid_post_plans_wrapper .=  '<div class="arm_confirm_box arm_member_edit_confirm_box arm_confirm_box_renew_next_cycle" id="arm_confirm_box_renew_next_cycle_'.esc_attr($pID).'" style=" top:25px; right:45px;">';
                                                        $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_body">';
                                                        $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_arrow" style="float: right"></div>';
                                                        $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_text_title">'.esc_html__( 'Renew plan', 'ARMember' ).'</div>';
                                                        $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_text arm_padding_top_0">';
                                                        $arm_paid_post_plans_wrapper .= '<input type="hidden" id="arm_skip_next_renewal_'.esc_attr($pID).'" name="arm_skip_next_renewal_'.esc_attr($pID).'" value="0" class="arm_skip_next_renewal"/>'.esc_html__('Are you sure you want to renew next cycle?', 'ARMember').'</div>'; 
                                                        $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_btn_container arm_display_flex">';
                                                        $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armcancel arm_user_renew_next_cycle_cancel_btn" onclick="hideUserRenewNextCycleBoxCallback('.$pID.');">'.esc_html__('Cancel', 'ARMember').'</button>';
                                                        $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armemailaddbtn arm_margin_left_12" onclick="RenewNextCycleOkCallback('.$pID.')">'.esc_html__('Ok', 'ARMember').'</button>';
                                                        $arm_paid_post_plans_wrapper .= '</div>';
                                                        $arm_paid_post_plans_wrapper .= '</div>';
                                                        $arm_paid_post_plans_wrapper .= '</div>';
                                                    }
                                                }
                                                else if(isset($planData['arm_current_plan_detail']['arm_subscription_plan_type']) && $planData['arm_current_plan_detail']['arm_subscription_plan_type']=='paid_finite')
                                                {
                                                      
                                                    $arm_paid_post_plans_wrapper .= '<div style="position: relative; float: left;">';
                                                    $arm_paid_post_plans_wrapper .= '<a href="javascript:void(0)" class="arm_user_renew_next_cycle_action_btn arm_margin_right_5" id="arm_skip_next_cycle" onclick="showConfirmBoxCallback(\'renew_next_cycle_'.$pID.'\');">'.esc_html__('Renew', 'ARMember').'</a>';
                                                    $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box arm_member_edit_confirm_box arm_confirm_box_renew_next_cycle" id="arm_confirm_box_renew_next_cycle_'.esc_attr($pID).'" style=" top:25px; right:45px; ">';
                                                    $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_body">';
                                                    $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_arrow" style="float: right"></div>';
                                                    $arm_paid_post_plans_wrapper .= "<div class='arm_confirm_box_text_title'>".esc_html__( 'Renew Plan', 'ARMember' )."</div>";
                                                    $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_text arm_padding_top_0">';
                                                    $arm_paid_post_plans_wrapper .= '<input type="hidden" id="arm_skip_next_renewal_'.esc_attr($pID).'" name="arm_skip_next_renewal_'.esc_attr($pID).'" value="0" class="arm_skip_next_renewal"/>'.esc_html__('Are you sure you want to renew plan?', 'ARMember').'</div>';
                                                    $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_btn_container arm_display_flex">';
                                                    $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armcancel arm_user_renew_next_cycle_cancel_btn" onclick="hideUserRenewNextCycleBoxCallback('.$pID.');">'.esc_html__('Cancel', 'ARMember').'</button>';
                                                    $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armemailaddbtn arm_margin_left_12"  onclick="RenewNextCycleOkCallback('.$pID.')">'.esc_html__('Ok', 'ARMember').'</button>';
                                                    $arm_paid_post_plans_wrapper .= '</div>';
                                                    $arm_paid_post_plans_wrapper .= '</div>';
                                                    $arm_paid_post_plans_wrapper .= '</div>';
                                                       
                                                }

                                                if (in_array($pID, $suspended_plan_ids)) {
                                                    
                                                    $arm_paid_post_plans_wrapper .= '<input type="hidden" name="arm_user_suspended_plan[]" value="'.esc_attr($pID).'" id="arm_user_suspended_plan_'.esc_attr($pID).'"/>';
                                                    
                                                }
                                                    
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_position_relative arm_float_left">';
                                                
                                                $arm_paid_post_plans_wrapper .= '<a class="arm_remove_user_plan_div armhelptip tipso_style" href="javascript:void(0)" title="'.esc_attr__('Remove Post', 'ARMember').'" onclick="showConfirmBoxCallback(\'delete_user_plan_'.$pID.'\');"></a>';
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box arm_member_edit_confirm_box" id="arm_confirm_box_delete_user_plan_'.esc_attr($pID).'" style="top:25px; right: -20px; ">';
                                                    
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_body">';
                                                
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_arrow" style="float: right"></div>';
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_text_title">'.esc_html__( 'Remove Paid post', 'ARMember' ).'</div>';
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_text arm_padding_top_0">'.esc_html__('Are you sure you want to remove this post?', 'ARMember').'</div>'; 
                                                
                                                
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_btn_container arm_display_flex">';
                                                $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armcancel" onclick="hideConfirmBoxCallback();">'.esc_html__('Cancel', 'ARMember').'</button>';
                                                $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armemailaddbtn arm_remove_user_plan_div_box arm_margin_left_12 arm_pro arm_remove_user_paid_post_div_box"  data-index='.esc_attr($uniq_delete_no).'>'.esc_html__('Ok', 'ARMember').'</button>';
                                                $arm_paid_post_plans_wrapper .= '</div>';
                                                $arm_paid_post_plans_wrapper .= '</div>';
                                                $arm_paid_post_plans_wrapper .= '</div></div>';
                                                
                                                $arm_paid_post_plans_wrapper .= '</td>';


                                            
                                            $arm_paid_post_plans_wrapper .= '</tr>';

                                                $count_plans++;
                                        } else {
                                            if (!empty($pID)) {
                                                $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $pID, true);
                                                $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                                                $planData = shortcode_atts($defaultPlanData, $userPlanDatameta);

                                                if (!empty($planData)) {
                                                    $planDetail = $planData['arm_current_plan_detail'];
                                                    if (!empty($planDetail)) {
                                                        $planObj = new ARM_Plan(0);
                                                        $planObj->init((object) $planDetail);
                                                    } else {
                                                        $planObj = new ARM_Plan($pID);
                                                    }
                                                }

                                                $no = $count_plans;
                                                $planName = $planObj->name;
                                                $grace_message = '';
                                                $starts_date = !empty($planData['arm_start_plan']) ? $planData['arm_start_plan'] : '';
                                                $started_date = !empty($planData['arm_started_plan_date']) ? $planData['arm_started_plan_date'] : '';

                                                $starts_on = !empty($starts_date) ? date_i18n($date_format, $starts_date) : '-';

                                                if($started_date != '' && $started_date <= $starts_date) {
                                                    $starts_on = date_i18n($date_format, $started_date);
                                                }
                                                $expires_on = !empty($planData['arm_expire_plan']) ? '<span id="arm_user_expiry_date_' . esc_attr($pID) . '" style="display: flex;">' . date_i18n($date_format, $planData['arm_expire_plan']) . ' <img src="' . MEMBERSHIPLITE_IMAGES_URL . '/edit_icon.svg" width="26" style=" margin: -4px 0 0 5px; cursor: pointer;" title="' . esc_attr__('Change Expiry Date', 'ARMember') . '" data-plan_id="' . esc_attr($pID) . '" class="arm_edit_user_expiry_date"></span><span id="arm_user_expiry_date_box_' . esc_attr($pID) . '" style="display: none; position: relative; width: 155px;"><input type="text" value="' . esc_attr( date('m/d/Y', $planData['arm_expire_plan']) ) . '" data-date_format="'. esc_attr($arm_common_date_format) .'"  name="arm_subscription_expiry_date_' . esc_attr($pID) . '" class="arm_member_form_input arm_user_plan_expiry_date_picker arm_width_120 arm_min_width_120" /><img src="' . MEMBERSHIPLITE_IMAGES_URL . '/cancel_date_icon.png" width="11" height="11" title="' . esc_attr__('Cancel', 'ARMember') . '" data-plan_id="' . esc_attr($pID) . '" data-plan-expire-date="' . esc_attr( date($arm_common_date_format, $planData['arm_expire_plan']) ) . '" class="arm_cancel_edit_user_expiry_date"></span>' : esc_html__('Never Expires', 'ARMember');
                                                $renewal_on = !empty($planData['arm_next_due_payment']) ? date_i18n($date_format, $planData['arm_next_due_payment']) : '-';
                                                $trial_starts = !empty($planData['arm_trial_start']) ? $planData['arm_trial_start'] : '';
                                                $trial_ends = !empty($planData['arm_trial_end']) ? $planData['arm_trial_end'] : '';
                                                $arm_payment_mode = ( $planData['arm_payment_mode'] == 'auto_debit_subscription') ? '<br/>(' . esc_html__('Auto Debit','ARMember') . ')' : '';
                                                $arm_payment_cycle = !empty($planData['arm_payment_cycle']) ? $planData['arm_payment_cycle'] : '';
                                                if ($planObj->is_recurring()) {
                                                    $recurring_plan_options = $planObj->prepare_recurring_data($arm_payment_cycle);
                                                    $recurring_time = $recurring_plan_options['rec_time'];
                                                    $completed = $planData['arm_completed_recurring'];
                                                    if ($recurring_time == 'infinite' || empty($planData['arm_expire_plan'])) {
                                                        $remaining_occurence = esc_html__('Never Expires', 'ARMember');
                                                    } else {
                                                        $remaining_occurence = $recurring_time - $completed;
                                                    }

                                                    if (!empty($planData['arm_expire_plan'])) {
                                                        if ($remaining_occurence == 0) {
                                                            $renewal_on = esc_html__('No cycles due', 'ARMember');
                                                        } else {
                                                            $renewal_on .= "<br/>( " . $remaining_occurence . esc_html__(' cycles due', 'ARMember') . " )";
                                                        }
                                                    }
                                                    $arm_is_user_in_grace = $planData['arm_is_user_in_grace'];

                                                    $arm_grace_period_end = $planData['arm_grace_period_end'];

                                                    if ($arm_is_user_in_grace == "1") {
                                                        $arm_grace_period_end = date_i18n($date_format, $arm_grace_period_end);
                                                        $grace_message .= "<br/>( " . esc_html__('grace period expires on', 'ARMember') ." ". $arm_grace_period_end . " )";
                                                    }
                                                }

                                                $arm_plan_is_suspended = '';

                                                $trial_active = '';
                                                $plans_is_odd_even =($count_plans % 2 == 0) ? 'even' : 'odd';

                                                $get_last_plan_id_key = array_key_last($planIDs);
                                                if($pID == $get_last_plan_id_key)
                                                {
                                                    $plans_is_odd_even .= ' arm_no_border';
                                                }

                                                $arm_paid_post_plans_wrapper .= '<tr class="arm_user_plan_table_tr '.esc_attr($plans_is_odd_even).'" id="arm_user_future_plan_div_'.esc_attr($count_plans).'">';
                                                $count_plans_no = $no + 1;
                                                $arm_paid_post_plans_wrapper .= '<td>'.esc_html($count_plans_no).'</td>';

                                                $arm_paid_post_plans_wrapper .= '<td>'.esc_html($planName) . $arm_plan_is_suspended.'</td>';
                                                $arm_paid_post_plans_wrapper .= '<td>'.$planObj->new_user_plan_text(false, $arm_payment_cycle).'</td>';
                                                $arm_paid_post_plans_wrapper .= '<td>'.$starts_on . $trial_active.'</td>';
                                                $arm_paid_post_plans_wrapper .= '<td>'.$expires_on.'</td>';
                                                $arm_paid_post_plans_wrapper .= '<td>'.$renewal_on . $grace_message . $arm_payment_mode.'</td>';

                                                $arm_paid_post_plans_wrapper .= '<td>';
                                                
                                                
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_position_relative arm_float_left">';
                                                $arm_paid_post_plans_wrapper .= '<a class="arm_remove_user_plan_div armhelptip tipso_style" href="javascript:void(0)" title="'.esc_attr__('Remove Post', 'ARMember').'" onclick="showConfirmBoxCallback(\'delete_user_plan_'.$pID.'\');"></a>';
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box arm_member_edit_confirm_box" id="arm_confirm_box_delete_user_plan_'.esc_attr($pID).'" style="top:25px; right: -20px; ">';

                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_body">';
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_arrow" style="float: right"></div>';
                                                $arm_paid_post_plans_wrapper .= "<div class='arm_confirm_box_text_title'>".esc_html__( 'Delete', 'ARMember' )."</div>";
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_text arm_padding_top_0" >'.esc_html__('Are you sure you want to remove this post?', 'ARMember').'</div>';
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_btn_container">';
                                                $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armemailaddbtn arm_margin_right_5" id="arm_remove_paid_post_user_future_plan_div_'.esc_attr($pID).'" data-index='.esc_attr($count_plans).'>'.esc_html__('Ok', 'ARMember').'</button>';
                                                $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armcancel" onclick="hideConfirmBoxCallback();">'.esc_html__('Cancel', 'ARMember').'</button>';
                                                $arm_paid_post_plans_wrapper .= '</div>';
                                                $arm_paid_post_plans_wrapper .= '</div>';
                                                $arm_paid_post_plans_wrapper .= '</div>';
                                                $arm_paid_post_plans_wrapper .= '</div>';
                                                
                                                $arm_paid_post_plans_wrapper .=  '</td>';
                                                $arm_paid_post_plans_wrapper .= '</tr>';

                                                $count_plans++;
                                            }
                                        }
                                    }

                                    if( in_array( $pID, $futurePlanIDs ) ){
                                        $arm_paid_post_plans_wrapper .= '<input name="arm_user_future_plan[]" value='.esc_attr($pID).' type="hidden" id="arm_user_paid_post_future_plan_'.esc_attr($uniq_delete_no).'">';
                                    } else {
                                        //$arm_paid_post_plans_wrapper .= '<input type="hidden" name="arm_user_plan[]" value="'.$pID.'"/>';
                                        $arm_paid_post_plans_wrapper .= '<input type="hidden" name="arm_user_plan2[]" id="arm_user_paid_post_div_'.esc_attr($uniq_delete_no).'" value="'.esc_attr($pID).'"/>';
                                        $planData['arm_start_plan'] = !empty($planData['arm_start_plan']) ? $planData['arm_start_plan'] : current_time('mysql');
                                        $arm_paid_post_plans_wrapper .= '<input type="hidden" name="arm_subscription_start_date2[]" id="arm_user_paid_post_date_div_'.esc_attr($uniq_delete_no).'" value='.date('m/d/Y', (int)$planData['arm_start_plan']).' />';
                                    }
                                    //$arm_paid_post_plans_wrapper .= '<input type="hidden" name="arm_subscription_start_date[]" value='.date('m/d/Y', $planData['arm_start_plan']).' />';
                                }
                            }

                                 
                    $arm_paid_post_plans_wrapper .= '</table>';
                    

                    if(!empty($planIDs) && $membership_count>5){
                        $member_paid_post_plans_pagging = $arm_global_settings->arm_get_paging_links($current_page, $membership_count, $per_page);
                        $arm_paid_post_plans_wrapper .= '<div class="arm_member_paid_post_plans_pagination_block">';
                        $arm_paid_post_plans_wrapper .= '<div class="arm_member_paid_post_plans_paging_container">'.$member_paid_post_plans_pagging.'</div>';
                        $arm_paid_post_plans_wrapper .= '</div>';
                    }
                     
                $arm_paid_post_plans_wrapper .= '</div>';
                }
            }
            return  $arm_paid_post_plans_wrapper;
        }
	
        function arm_paid_post_plan_paging_action() {
            global $wp, $wpdb, $ARMember, $arm_global_settings,$arm_subscription_plans,$arm_capabilities_global;
            if (isset($_POST['action']) && $_POST['action'] == 'arm_paid_post_plan_paging_action') {//phpcs:ignore
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_members'],1,1);//phpcs:ignore --Reason:Verifying nonce
                
                $user_id = isset($_POST['user_id']) ? intval( $_POST['user_id'] ) : 0;//phpcs:ignore
                $current_page = isset($_POST['page']) ? intval( $_POST['page'] ) : 1;//phpcs:ignore
                $per_page = isset($_POST['per_page']) ? intval( $_POST['per_page'] ) : 5;//phpcs:ignore
                
                echo $this->arm_get_paid_post_plans_paging($user_id, $current_page, $per_page); //phpcs:ignore
            }
            exit;
        }

        function arm_paid_post_content_check_restriction($content)
        {
            // Check if we're inside the main loop in a single Post.
	    $arm_is_allowed_content = 1;
            $arm_is_allowed_content = apply_filters('arm_paid_post_check_content_access_external', $arm_is_allowed_content);
	    
            if (!current_user_can('administrator') && is_singular() && in_the_loop() && is_main_query() && $arm_is_allowed_content) 
            {
                $current_post_id = get_the_ID();
                if(!empty($current_post_id))
                {
                    $arm_is_paid_post = get_post_meta($current_post_id, 'arm_is_paid_post', true);
                    if(!empty($arm_is_paid_post))
                    {
                        $plan_id = $this->arm_get_plan_from_post_id( $current_post_id );
                        if( !empty( $plan_id ) )
                        {
                            $hasaccess = false;
                            $isLoggedIn = is_user_logged_in();
                            if($isLoggedIn)
                            {
                                $current_user_id = get_current_user_id();
                                $arm_user_plan = get_user_meta($current_user_id, 'arm_user_plan_ids', true);
                                $arm_user_plan = !empty($arm_user_plan) ? $arm_user_plan : array();
                                if(!empty($arm_user_plan)){
                                    $suspended_plan_ids = get_user_meta($current_user_id, 'arm_user_suspended_plan_ids', true);
                                    if( ! empty($suspended_plan_ids)) {
                                        foreach ($suspended_plan_ids as $suspended_plan_id) {
                                            if(in_array($suspended_plan_id, $arm_user_plan)) {
                                                unset($arm_user_plan[array_search($suspended_plan_id, $arm_user_plan)]);
                                            }
                                        }
                                    }

                                    if(in_array($plan_id, $arm_user_plan))
                                    {
                                        $hasaccess = true;
                                    }
                                }
                            }

                            if($hasaccess==false)
                            {

                                $arm_enable_paid_post_alternate_content = get_post_meta($current_post_id, 'arm_enable_paid_post_alternate_content', true);
                                if(!empty($arm_enable_paid_post_alternate_content))
                                {
                                    $arm_paid_post_alternative_content = get_post_meta($current_post_id, 'arm_paid_post_alternative_content', true);
                                    
				    $arm_paid_post_alternative_content = apply_filters('arm_modified_paid_post_alternative_content_externally', $arm_paid_post_alternative_content,$current_post_id);
                                    
                                    return do_shortcode( $arm_paid_post_alternative_content );
                                }
                                else {
                                    global $arm_global_settings;
                                    $arm_global_settings_general_settings = !empty($arm_global_settings->global_settings['arm_pay_per_post_default_content']) ? stripslashes($arm_global_settings->global_settings['arm_pay_per_post_default_content']) : esc_html__('Content is Restricted. Buy this post to get access to full content.', 'ARMember');
                                    
				    $arm_global_settings_general_settings = apply_filters('arm_modified_paid_post_settings_alternative_content_externally', $arm_global_settings_general_settings);

                                    return do_shortcode( $arm_global_settings_general_settings );
                                    
                                }
                            }

                            
                        }
                        
                    }
                }
                
            }
 
            return $content;
        }
	
	function arm_paid_post_plan_modal_paging_action()
        {
            global $wp, $wpdb, $ARMember, $arm_global_settings,$arm_subscription_plans,$arm_capabilities_global,$arm_ajax_pattern_start,$arm_ajax_pattern_end;
            if (isset($_POST['action']) && $_POST['action'] == 'arm_paid_post_plan_modal_paging_action') {//phpcs:ignore
                $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_members'],1,1);//phpcs:ignore --Reason:Verifying nonce
                $user_id = isset($_POST['user_id']) ? intval( $_POST['user_id'] ) : 0;//phpcs:ignore
                $current_page = isset($_POST['page']) ? intval( $_POST['page'] ) : 1;//phpcs:ignore
                $per_page = isset($_POST['per_page']) ? intval( $_POST['per_page'] ) : 5;//phpcs:ignore
                $arm_get_paid_post_model_plan = $this->arm_get_paid_post_modal_plans($user_id, $current_page, $per_page); //phpcs:ignore
                echo $arm_ajax_pattern_start .''.$arm_get_paid_post_model_plan.''. $arm_ajax_pattern_end;
            }
            exit;   
        }


        function arm_get_paid_post_modal_plans($user_id = 0, $current_page = 1, $per_page = 5)
        {
            global $arm_global_settings,$arm_subscription_plans,$is_multiple_membership_feature, $ARMember, $arm_capabilities_global,$arm_common_lite;

            $arm_paid_post_plans_wrapper = "";
            if (!empty($user_id) && $user_id != 0) {
                $arm_common_date_format = $arm_global_settings->arm_check_common_date_format(get_option('date_format'));

                $planIDs = get_user_meta($user_id, 'arm_user_post_ids', true);
                $planIDs = !empty($planIDs) ? $planIDs : array();

                /*$postIDs = get_user_meta($user_id, 'arm_user_post_ids', true);
                $postIDs = !empty($postIDs) ? $postIDs : array();*/

                $futurePlanIDs = get_user_meta($user_id, 'arm_user_future_plan_ids', true);
                $futurePlanIDs = !empty($futurePlanIDs) ? $futurePlanIDs : array();

                if( !empty( $futurePlanIDs ) ){
                    foreach( $futurePlanIDs as $fPlanKey => $fPlanId ){
                        $fPlanData = $this->arm_get_post_from_plan_id( $fPlanId );

                        if( !empty( $fPlanData[0]['arm_subscription_plan_id'] ) && !empty($fPlanData[0]['arm_subscription_plan_post_id']) ){
                            $planIDs[$fPlanData[0]['arm_subscription_plan_id']] = $fPlanData[0]['arm_subscription_plan_post_id'];
                        }
                    }
                }

                $date_format = $arm_global_settings->arm_get_wp_date_format();

                $user_name = '';
                $arm_user_info = get_userdata($user_id);
                $user_name = $arm_user_info->user_login;
                $u_roles = $arm_user_info->roles;
                
                

                $all_subscription_plans = $arm_subscription_plans->arm_get_paid_post_data();

                /*foreach($planIDs as $plan_key => $plan_value)
                {
                    if(!array_key_exists($plan_value, $postIDs))
                    {
                        unset($plan_key);
                    }
                }*/

                /*$all_plan_ids = array();
                if (!empty($all_subscription_plans)) {
                    foreach ($all_subscription_plans as $p) {
                        $all_plan_ids[] = $p['arm_subscription_plan_id'];
                    }
                }*/



                $plansLists = '<li data-label="' . esc_attr__('Select Post', 'ARMember') . '" data-value="">' . esc_html__('Select Post', 'ARMember') . '</li>';
                if (!empty($all_subscription_plans)) {
                    foreach ($all_subscription_plans as $p) {
                        if($p['arm_subscription_plan_post_id'] != 0 && (!in_array($p['arm_subscription_plan_id'], $planIDs)))
                        {
                            $p_id = $p['arm_subscription_plan_id'];
                            $plansLists .= '<li data-label="' . stripslashes(esc_attr($p['arm_subscription_plan_name'])) . '" data-value="' . $p_id . '">' . stripslashes(esc_attr($p['arm_subscription_plan_name'])) . '</li>';
                        }
                    }
                }


                $arm_paid_post_plans_wrapper .= '<div class="arm_add_new_item_box arm_add_new_plan arm_margin_top_0 arm_margin_right_32"><a id="arm_add_plan_to_user" class="greensavebtn arm_save_btn arm_margin_right_10" href="javascript:void(0)" ><img align="absmiddle" src="' . MEMBERSHIPLITE_IMAGES_URL . '/add_new_icon.svg"><span> ' . esc_html__('Add Post', 'ARMember') . '</span></a></div>';

                $arm_paid_post_plans_wrapper .= '<div class="popup_content_text arm_add_plan arm_text_align_center arm_padding_32" style="display:none;">';
                $arm_paid_post_plans_wrapper .= '<div class="arm_edit_plan_wrapper arm_position_relative arm_display_contents" >';
                $arm_paid_post_plans_wrapper .= '<span class="arm_edit_plan_lbl arm_margin_bottom_12">' . esc_html__('Select Post', 'ARMember') . '*</span> ';
                $arm_paid_post_plans_wrapper .= '<div class="arm_edit_field">';
                
                    $arm_paid_post_plans_wrapper .= '<input type="text" class="arm-selectpicker-input-control arm_user_plan_change_input arm_paid_post_user_plan_cycle arm_user_plan_change_input_get_cycle" name="arm_user_plan[]" id="arm_user_pp_plan" value="" data-manage-plan-grid="1"/>';
                
                $arm_paid_post_plans_wrapper .= '<dl class="arm_selectbox column_level_dd arm_member_form_dropdown arm_float_left arm_width_100_pct" >';
                $arm_paid_post_plans_wrapper .= '<dt class="arm_width_100_pct arm_max_width_100_pct"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';
                $arm_paid_post_plans_wrapper .= '<dd><ul data-id="arm_user_pp_plan">' . $plansLists . '</ul></dd>';
                $arm_paid_post_plans_wrapper .= '</dl>';
                $arm_paid_post_plans_wrapper .= '<br/><span class="arm_error_select_plan error arm_invalid arm_text_align_left" style="display:none; ">' . esc_html__('Please select Post.', 'ARMember') . '</span>';
                $arm_paid_post_plans_wrapper .= '</div>';
                $arm_paid_post_plans_wrapper .= '</div>';

                $arm_paid_post_plans_wrapper .= '<div class="arm_selected_plan_cycle arm_position_relative arm_display_contents">';
                $arm_paid_post_plans_wrapper .= '</div>';

                $arm_paid_post_plans_wrapper .= '<div  class="arm_position_relative arm_display_contents">';
                $arm_paid_post_plans_wrapper .= '<span class="arm_edit_plan_lbl arm_margin_top_28 arm_margin_bottom_12">' . esc_html__('Post Start Date', 'ARMember') . '</span>';
                $arm_paid_post_plans_wrapper .= '<div class="arm_edit_field arm_position_relative">';
                
                $arm_paid_post_plans_wrapper .= '<input type="text" value="' . esc_attr( date($arm_common_date_format, strtotime(date('Y-m-d'))) ) . '"  data-date_format="'. esc_attr($arm_common_date_format) .'" name="arm_subscription_start_date[]" class="arm_datepicker arm_member_form_input arm_user_add_plan_date_picker arm_width_100_pct arm_max_width_100_pct"  />';
                
                $arm_paid_post_plans_wrapper .= '</div>';
                $arm_paid_post_plans_wrapper .= '</div>';

                $arm_paid_post_plans_wrapper .= '<div class="arm_position_relative arm_margin_top_28">';
                $arm_paid_post_plans_wrapper .= '<span class="arm_edit_plan_lbl">&nbsp;</span>';
                $arm_paid_post_plans_wrapper .= '<div class="arm_edit_field arm_float_right arm_text_align_right">';
                $arm_paid_post_plans_wrapper .= '<img src="' . MEMBERSHIPLITE_IMAGES_URL . '/arm_loader.gif" class="arm_loader_img_user_add_plan arm_submit_btn_loader" style="display:none;position:absolute;left:66%;" width="24" height="24" />';
                $arm_paid_post_plans_wrapper .= '<button class="arm_add_plan_cancel_btn arm_cancel_btn" type="button">' . esc_html__('Close', 'ARMember') . '</button>';
                $arm_paid_post_plans_wrapper .= '<button class="arm_member_add_paid_plan_save_btn arm_save_btn arm_margin_right_0">' . esc_html__('Save', 'ARMember') . '</button>';
                $arm_paid_post_plans_wrapper .= '</div>';
                $arm_paid_post_plans_wrapper .= '</div>';

                $arm_paid_post_plans_wrapper .= '</div>';

                $user_plans = $planIDs;
                //$user_plans = $postIDs;

                if (!empty($u_roles)) {
                    foreach ($u_roles as $ur) {
                        $arm_paid_post_plans_wrapper .= '<input type="hidden" name="roles[]" value="' . esc_attr($ur) . '"/>';
                    }
                }
                
                $arm_paid_post_plans_wrapper .= '<div class="arm_loading_grid arm_plan_loading_grid" style="display: none;">'.$arm_common_lite->arm_loader_img_func().'</div>';

                $arm_paid_post_plans_wrapper .= '<div class="arm_paid_post_plans_wrapper" data-user_id="'.esc_attr($user_id).'">';

                $arm_paid_post_plans_wrapper.= '<table class="arm_user_edit_plan_table arm_text_align_center" cellspacing="1">';
                $arm_paid_post_plans_wrapper.= '<tr class="arm_user_plan_row arm_user_plan_head odd">';
                //$arm_paid_post_plans_wrapper.= '<th class="arm_user_plan_text_th arm_user_plan_no">'. esc_html__('No', 'ARMember').'</th>';
                $arm_paid_post_plans_wrapper.= '<th class="arm_edit_plan_name">'. esc_html__('Post Name', 'ARMember').'</th>';
                $arm_paid_post_plans_wrapper.= '<th class="arm_edit_plan_type">'. esc_html__('Post Type', 'ARMember').'</th>';
                $arm_paid_post_plans_wrapper.= '<th class="arm_edit_plan_start">'. esc_html__('Starts On', 'ARMember').'</th>';
                $arm_paid_post_plans_wrapper.= '<th class="arm_edit_plan_expire">'. esc_html__('Expires On', 'ARMember').'</th>';
                $arm_paid_post_plans_wrapper.= '<th class="arm_edit_plan_cycle_date">'. esc_html__('Cycle Date', 'ARMember').'</th>';
                $arm_paid_post_plans_wrapper.= '<th class="arm_edit_plan_action"></th>';
                $arm_paid_post_plans_wrapper.= '</tr>';


                $offset = (!empty($current_page) && $current_page > 1) ? (($current_page - 1) * $per_page) : 0;
                $membership_count = count($planIDs);
                $planIDs_slice = array_slice($planIDs, $offset, $per_page);


                $date_format = $arm_global_settings->arm_get_wp_date_format();
                $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                $suspended_plan_ids = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
                $suspended_plan_ids = (isset($suspended_plan_ids) && !empty($suspended_plan_ids)) ? $suspended_plan_ids : array();
                
                $count_plans = 0;
                $plan_row_count = 0;
                if( $current_page > 1 ){
                    $count_plans = $count_plans + $per_page;
                }
                $arm_check_data = 1;
                
                $arm_member_view_paid_plan_detail_action = !empty($_REQUEST['action']) ? sanitize_text_field( $_REQUEST['action'] ) : '';

                $arm_paid_post_supended_tooltip_class = $arm_paid_post_supended_tooltip_txt = "";
                $arm_paid_post_suspended_txt_func = "javascript:void(0)";
                if(empty($arm_member_view_paid_plan_detail_action))
                {
                    $arm_paid_post_supended_tooltip_class = "armhelptip tipso_style";
                    $arm_paid_post_supended_tooltip_txt = esc_html__('Click here to Show failed payment history', 'ARMember');
                    $arm_paid_post_suspended_txt_func = 'arm_show_failed_payment_history(' . $user_id . ',' . $pID . ',\'' . $planName . '\',\'' . $planData['arm_start_plan'] . '\')';
                }

                if (!empty($planIDs)) {
                    foreach ($planIDs as $pID => $arm_paid_post_id) {
                        if (!empty($pID) && in_array($arm_paid_post_id, $planIDs_slice)) {
                            $planData = get_user_meta($user_id, 'arm_user_plan_' . $pID, true);
                            $planData = !empty($planData) ? $planData : array();

                            $uniq_delete_no = uniqid();
                            if (!empty($planData) && !empty($planData['arm_current_plan_detail']['arm_subscription_plan_post_id'])) {
                                $planDetail = $planData['arm_current_plan_detail'];
                                if (!empty($planDetail)) {
                                    $planObj = new ARM_Plan(0);
                                    $planObj->init((object) $planDetail);
                                } else {
                                    $planObj = new ARM_Plan($pID);
                                }

                                $no = $count_plans;
                                $planName = $planObj->name;
                                $grace_message = '';
                                
                                $starts_date = !empty($planData['arm_start_plan']) ? $planData['arm_start_plan'] : '';
                                $started_date = !empty($planData['arm_started_plan_date']) ? $planData['arm_started_plan_date'] : '';

                                $starts_on = !empty($starts_date) ? date_i18n($date_format, $starts_date) : '-';

                                if($started_date != '' && $started_date <= $starts_date) {
                                    $starts_on = date_i18n($date_format, $started_date);
                                }

                                $expires_on = !empty($planData['arm_expire_plan']) ? '<span id="arm_user_expiry_date_' . $pID . '" style="display: flex;align-items: center;"> ' . date_i18n($date_format, $planData['arm_expire_plan']) . ' <a href="javascript:void(0)" title="' . esc_attr__('Change Expiry Date', 'ARMember') . '" data-plan_id="' . esc_attr($pID) . '" class="arm_member_edit_plan arm_edit_user_expiry_date armhelptip tipso_style" id="arm_user_expiry_date_'.$pID.'"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.2594 3.60022L5.04936 12.2902C4.73936 12.6202 4.43936 13.2702 4.37936 13.7202L4.00936 16.9602C3.87936 18.1302 4.71936 18.9302 5.87936 18.7302L9.09936 18.1802C9.54936 18.1002 10.1794 17.7702 10.4894 17.4302L18.6994 8.74022C20.1194 7.24022 20.7594 5.53022 18.5494 3.44022C16.3494 1.37022 14.6794 2.10022 13.2594 3.60022Z" stroke="#617191" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.8906 5.0498C12.3206 7.8098 14.5606 9.9198 17.3406 10.1998" stroke="#617191" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 22H21" stroke="#617191" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/></svg></a></span><span id="arm_user_expiry_date_box_' . esc_attr($pID) . '" style="display: none;" class="arm_position_relative"><input type="text" value="' . esc_attr( date($arm_common_date_format, $planData['arm_expire_plan']) ) . '"  data-date_format="'. esc_attr($arm_common_date_format) .'" name="arm_subscription_expiry_date_' . esc_attr($pID) . '"  id="arm_subscription_expiry_date_'.esc_attr($pID).'_'.esc_attr($user_id).'" class="arm_member_form_input arm_user_plan_expiry_date_picker arm_width_170 arm_min_width_170" />
                                <a href="javascript:void(0)" title="' . esc_attr__('Save Expiry Date', 'ARMember') . '" class="arm_edit_post_plan_action_button arm_member_save_post arm_vertical_align_middle armhelptip tipso_style" id="arm_member_save_post_'.esc_attr($pID).'" data-plan_id="' . esc_attr($pID) . '" data-user_id="' . esc_attr($user_id) . '" ><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><script xmlns=""></script><script xmlns=""></script><path d="M3 7.5V5C3 3.89543 3.89543 3 5 3H16.1716C16.702 3 17.2107 3.21071 17.5858 3.58579L20.4142 6.41421C20.7893 6.78929 21 7.29799 21 7.82843V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V16.5" stroke="#617191" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M6 21V17" stroke="#617191" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M18 21V13.6C18 13.2686 17.7314 13 17.4 13H15" stroke="#617191" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M16 3V8.4C16 8.73137 15.7314 9 15.4 9H13.5" stroke="#617191" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M8 3V6" stroke="#617191" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M1 12H12M12 12L9 9M12 12L9 15" stroke="#617191" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path></svg></a>
                                <a href="javascript:void(0)" title="' . esc_attr__('Cancel', 'ARMember') . '" data-plan_id="' . esc_attr($pID) . '" data-plan-expire-date="' . esc_attr(date($arm_common_date_format, $planData['arm_expire_plan']) ) . '" class="arm_cancel_edit_user_expiry_date armhelptip"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M6.75827 17.2426L12.0009 12M17.2435 6.75736L12.0009 12M12.0009 12L6.75827 6.75736M12.0009 12L17.2435 17.2426" stroke="#617191" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a></span>' : esc_html__('Never Expires', 'ARMember');
                                $renewal_on = !empty($planData['arm_next_due_payment']) ? date_i18n($date_format, $planData['arm_next_due_payment']) : '-';
                                $trial_starts = !empty($planData['arm_trial_start']) ? $planData['arm_trial_start'] : '';
                                $trial_ends = !empty($planData['arm_trial_end']) ? $planData['arm_trial_end'] : '';
                                $arm_payment_mode = ( $planData['arm_payment_mode'] == 'auto_debit_subscription') ? '<br/>(' . esc_html__('Auto Debit','ARMember') . ')' : '';
                                $arm_payment_cycle = !empty($planData['arm_payment_cycle']) ? $planData['arm_payment_cycle'] : '';

                                if ($planObj->is_recurring()) {
                                    $recurring_plan_options = $planObj->prepare_recurring_data($arm_payment_cycle);
                                    $recurring_time = $recurring_plan_options['rec_time'];
                                    $completed = $planData['arm_completed_recurring'];
                                    if ($recurring_time == 'infinite' || empty($planData['arm_expire_plan'])) {
                                        $remaining_occurence = esc_html__('Never Expires', 'ARMember');
                                    } else {
                                        $remaining_occurence = $recurring_time - $completed;
                                    }

                                    if (!empty($planData['arm_expire_plan'])) {
                                        if ($remaining_occurence == 0) {
                                            $renewal_on = esc_html__('No cycles due', 'ARMember');
                                        } else {
                                            $renewal_on .= "<br/>( " . $remaining_occurence . esc_html__(' cycles due', 'ARMember') . " )";
                                        }
                                    }

                                    $arm_is_user_in_grace = $planData['arm_is_user_in_grace'];

                                    $arm_grace_period_end = $planData['arm_grace_period_end'];

                                    if ($arm_is_user_in_grace == "1" || $arm_is_user_in_grace == 1) {
                                        $arm_grace_period_end = date_i18n($date_format, $arm_grace_period_end);
                                        $grace_message .= "<br/>( " . esc_html__('grace period expires on', 'ARMember') ." ". $arm_grace_period_end . " )";
                                    }
                                }

                                $arm_plan_is_suspended = '';

                                if (!empty($suspended_plan_ids)) {
                                    if (in_array($pID, $suspended_plan_ids)) {
                                        $arm_plan_is_suspended = '<div class="arm_user_plan_status_div arm_position_relative" ><span class="'. esc_attr($arm_paid_post_supended_tooltip_class) .'" id="arm_user_suspend_plan_' . esc_attr($pID) . '" style="color: red;" onclick="'. $arm_paid_post_suspended_txt_func.'" title="' . $arm_paid_post_supended_tooltip_txt . '">(' . esc_html__('Suspended', 'ARMember') . ')</span><img src="' . MEMBERSHIPLITE_IMAGES_URL . '/edit_icon.svg" width="20" style="position: absolute; margin: -4px 0 0 5px; cursor: pointer;" title="' . esc_attr__('Activate Post', 'ARMember') . '" data-plan_id="' . esc_attr($pID) . '" onclick="showConfirmBoxCallback(\'change_user_plan_' . $pID . '\');" class="arm_change_user_plan_img_' . esc_attr($pID) . '">
    
                                        <div class="arm_confirm_box arm_member_edit_confirm_box" id="arm_confirm_box_change_user_plan_' . esc_attr($pID) . '" style="top:25px; right: -20px; ">
                                                <div class="arm_confirm_box_body">
                                                    <div class="arm_confirm_box_arrow" style="float: right"></div>
                                                    <div class="arm_confirm_box_text_title">'.esc_html__( 'Activate Post', 'ARMember' ).'</div>
                                                    <div class="arm_confirm_box_text arm_padding_top_15" >' .
                                                esc_html__('Are you sure you want to active this paid post?', 'ARMember') . '
                                                    </div>
                                                    <div class="arm_confirm_box_btn_container">
                                                        <button type="button" class="arm_confirm_box_btn armemailaddbtn arm_post_status_change arm_margin_right_5"  data-index="' . esc_attr($pID) . '" data-item_id="'.esc_attr($pID).'" >' . esc_html__('Ok', 'ARMember') . '</button>
                                                        <button type="button" class="arm_confirm_box_btn armcancel" onclick="hideConfirmBoxCallback();">' . esc_html__('Cancel', 'ARMember') . '</button>
                                                    </div>
                                                </div>
                                            </div>

                                </div>';
                                    }
                                }

                                $trial_active = '';
                                if (!empty($trial_starts)) {
                                    if ($planData['arm_is_trial_plan'] == 1 || $planData['arm_is_trial_plan'] == '1') {
                                        if ($trial_starts < $planData['arm_start_plan']) {
                                            $trial_active = "<div class='arm_user_plan_status_div'><span class='arm_current_membership_trial_active'>(" . esc_html__('trial active', 'ARMember') . ")</span></div>";
                                        }
                                    }
                                }
                                
                                $count_plans_is_odd_even = ($count_plans % 2 == 0) ? 'even' : 'odd';
                                $count_plans_new = $count_plans + 1;    
                                $arm_last_child_cls = '';
                                $get_last_plan_id_key = array_key_last($planIDs);
                                if($pID == $get_last_plan_id_key)
                                {
                                    $count_plans_is_odd_even .= ' arm_no_border';
                                }                  
                                $arm_paid_post_plans_wrapper.= '<tr class="arm_user_plan_row '.esc_attr($count_plans_is_odd_even).'" id="arm_user_plan_div_'.esc_attr($uniq_delete_no).'">';
                                //$arm_paid_post_plans_wrapper.= '<td>'.$count_plans_new.'</td>';
                                $arm_paid_post_plans_wrapper.= '<td class="arm_edit_plan_name" data-label="'.esc_html('Post Name','ARMember').'">'.esc_html($planName) . $arm_plan_is_suspended.'</td>';
                                $arm_paid_post_plans_wrapper.= '<td class="arm_edit_plan_type" data-label="'.esc_html('Post Type','ARMember').'">'.$planObj->new_user_plan_text(false, $arm_payment_cycle).'</td>';
                                $arm_paid_post_plans_wrapper.= '<td class="arm_edit_plan_start" data-label="'.esc_html('Start On','ARMember').'">'.esc_html($starts_on) . $trial_active.'</td>';
                                $arm_paid_post_plans_wrapper.= '<td class="arm_edit_plan_expiry" data-label="'.esc_html('Expires On','ARMember').'">'.$expires_on.'</td>';
                                $arm_paid_post_plans_wrapper.= '<td class="arm_edit_plan_cycle_date" data-label="'.esc_html('Cycle Date','ARMember').'">'.$renewal_on . $grace_message . $arm_payment_mode.'</td>';

                                $arm_paid_post_plans_wrapper.= '<td data-label="'.esc_html('Action','ARMember').'">';

                                        
                                        if ($planObj->is_recurring() && $planData['arm_payment_mode'] == 'manual_subscription' && !in_array($pID, $futurePlanIDs)) {

                                            $recurringData = $planObj->prepare_recurring_data($arm_payment_cycle);

                                            $total_recurrence = $recurringData['rec_time'];
                                            $completed_rec = $planData['arm_completed_recurring'];
                                            
                                            $arm_paid_post_plans_wrapper.= '<div class="arm_position_relative arm_float_left" >';
                                               
                                                if (!in_array($pID, $suspended_plan_ids) && $total_recurrence != $completed_rec) {
                                                    
                                                    //$arm_paid_post_plans_wrapper.= '<a href="javascript:void(0)" id="arm_extend_cycle_days" class="arm_user_extend_renewal_date_action_btn" onclick="showConfirmBoxCallback(\'extend_renewal_date_'.$pID.'\');">'.esc_html__('Extend Days', 'ARMember').'</a>';

                                                    $arm_paid_post_plans_wrapper.= '<div class="arm_confirm_box arm_member_edit_confirm_box arm_confirm_box_extend_renewal_date" id="arm_confirm_box_extend_renewal_date_'.esc_attr($pID).'">';
                                                    $arm_paid_post_plans_wrapper.= '<div class="arm_confirm_box_body">';
                                                    $arm_paid_post_plans_wrapper.= '<div class="arm_confirm_box_arrow"></div>';
                                                    $arm_paid_post_plans_wrapper.= '<div class="arm_confirm_box_text arm_padding_top_0" >';
                                                    $arm_paid_post_plans_wrapper.= '<span class="arm_margin_bottom_5 arm_font_size_15>'.esc_html__('Select how many days you want to extend in current cycle?', 'ARMember').'</span><div class="arm_margin_top_10">';
                                                    $arm_paid_post_plans_wrapper.= '<input type="hidden" id="arm_user_grace_plus_'.esc_attr($pID).'" name="arm_user_grace_plus_'.esc_attr($pID).'" value="0" class="arm_user_grace_plus"/>';
                                                    $arm_paid_post_plans_wrapper.= '<dl class="arm_selectbox column_level_dd arm_member_form_dropdown">
                                                                        <dt style="min-width:45px; width:45px; text-align: center;"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                                        <dd>';
                                                    $arm_paid_post_plans_wrapper.= '<ul data-id="arm_user_grace_plus_'.$pID.'">';
                                                                                
                                                                                for ($i = 0; $i <= 30; $i++) {
                                                                                    
                                                                                    $arm_paid_post_plans_wrapper.= '<li data-label='.esc_attr($i).' data-value='.esc_attr($i).'>'.esc_html($i).'</li>';
                                                                                    
                                                                                }
                                                                                
                                                    $arm_paid_post_plans_wrapper.= '</ul>';
                                                    $arm_paid_post_plans_wrapper.= '</dd>';
                                                    $arm_paid_post_plans_wrapper.= '</dl>'.esc_html__('Days', 'ARMember').'</div>';
                                                    $arm_paid_post_plans_wrapper.= '</div>';
                                                    $arm_paid_post_plans_wrapper.= '<div class="arm_confirm_box_btn_container">';
                                                    $arm_paid_post_plans_wrapper.= '<button type="button" class="arm_confirm_box_btn armemailaddbtn arm_margin_right_5"  onclick="hideConfirmBoxCallback();">'.esc_html__('Ok', 'ARMember').'</button>';
                                                    $arm_paid_post_plans_wrapper.= '<button type="button" class="arm_confirm_box_btn armcancel arm_user_extend_renewal_date_cancel_btn" onclick="hideUserExtendRenewalDateBoxCallback('.$pID.');">'.esc_html__('Cancel', 'ARMember').'</button>';
                                                    
                                                    $arm_paid_post_plans_wrapper.= '</div>';
                                                        
                                                    $arm_paid_post_plans_wrapper.= '</div>';
                                                    $arm_paid_post_plans_wrapper.= '</div>';
                                                   
                                                }
                                                
                                                
                                                if ($total_recurrence != $completed_rec) {
                                                     
                                                    //$arm_paid_post_plans_wrapper .= '<a href="javascript:void(0)" class="arm_user_renew_next_cycle_action_btn" id="arm_skip_next_cycle" onclick="showConfirmBoxCallback(\'renew_next_cycle_'.$pID.'\');">'.esc_html__('Renew Cycle', 'ARMember').'</a>';
                                                    $arm_paid_post_plans_wrapper .=  '<div class="arm_confirm_box arm_member_edit_confirm_box arm_confirm_box_renew_next_cycle arm_width_280" id="arm_confirm_box_renew_next_cycle_'.esc_attr($pID).'" style="top:25px; right:45px;">';
                                                    $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_body">';
                                                    $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_arrow" style="float: right"></div>';
                                                    $arm_paid_post_plans_wrapper .= "<div class='arm_confirm_box_text_title'>".esc_html__( 'Renew post', 'ARMember' )."</div>";
                                                    $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_text arm_padding_top_0" >';
                                                    $arm_paid_post_plans_wrapper .= '<input type="hidden" id="arm_skip_next_renewal_'.esc_attr($pID).'" name="arm_skip_next_renewal_'.esc_attr($pID).'" value="0" class="arm_skip_next_renewal"/>'.esc_html__('Are you sure you want to renew next cycle?', 'ARMember').'</div>'; 
                                                    $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_btn_container">';
                                                    $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armemailaddbtn arm_margin_right_5"  onclick="RenewNextCycleOkCallback('.$pID.')">'.esc_html__('Ok', 'ARMember').'</button>';
                                                    $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armcancel arm_user_renew_next_cycle_cancel_btn" onclick="hideUserRenewNextCycleBoxCallback('.$pID.');">'.esc_html__('Cancel', 'ARMember').'</button>';
                                                    $arm_paid_post_plans_wrapper .= '</div>';
                                                    $arm_paid_post_plans_wrapper .= '</div>';
                                                    $arm_paid_post_plans_wrapper .= '</div>';
                                                }
                                            }
                                            else if(isset($planData['arm_current_plan_detail']['arm_subscription_plan_type']) && $planData['arm_current_plan_detail']['arm_subscription_plan_type']=='paid_finite')
                                            {
                                                  
                                                $arm_paid_post_plans_wrapper .= '<div style="position: relative; float: left;">';
                                                //$arm_paid_post_plans_wrapper .= '<a href="javascript:void(0)" class="arm_user_renew_next_cycle_action_btn" id="arm_skip_next_cycle" onclick="showConfirmBoxCallback(\'renew_next_cycle_'.$pID.'\');">'.esc_html__('Renew', 'ARMember').'</a>';
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box arm_member_edit_confirm_box arm_confirm_box_renew_next_cycle arm_width_280" id="arm_confirm_box_renew_next_cycle_'.esc_attr($pID).'" style="top:25px; right:45px; ">';
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_body">';
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_arrow" style="float: right"></div>';
                                                $arm_paid_post_plans_wrapper .= "<div class='arm_confirm_box_text_title'>".esc_html__( 'Renew post', 'ARMember' )."</div>";
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_text arm_padding_top_0" >';
                                                $arm_paid_post_plans_wrapper .= '<input type="hidden" id="arm_skip_next_renewal_'.esc_attr($pID).'" name="arm_skip_next_renewal_'.esc_attr($pID).'" value="0" class="arm_skip_next_renewal"/>'.esc_html__('Are you sure you want to renew plan?', 'ARMember').'</div>';
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_btn_container">';
                                                $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armemailaddbtn arm_margin_right_5"  onclick="RenewNextCycleOkCallback('.$pID.')" >'.esc_html__('Ok', 'ARMember').'</button>';
                                                $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armcancel arm_user_renew_next_cycle_cancel_btn" onclick="hideUserRenewNextCycleBoxCallback('.$pID.');">'.esc_html__('Cancel', 'ARMember').'</button>';
                                                $arm_paid_post_plans_wrapper .= '</div>';
                                                $arm_paid_post_plans_wrapper .= '</div>';
                                                $arm_paid_post_plans_wrapper .= '</div>';
                                                   
                                            }

                                            if (in_array($pID, $suspended_plan_ids)) {
                                                
                                                $arm_paid_post_plans_wrapper .= '<input type="hidden" name="arm_user_suspended_plan[]" value="'.esc_attr($pID).'" id="arm_user_suspended_plan_'.esc_attr($pID).'"/>';
                                                
                                            }

                                            //if ($is_multiple_membership_feature->isMultipleMembershipFeature) {
                                                
                                                

                                                $arm_paid_post_plans_wrapper .= '<div class="arm_plan_action_btns arm_position_relative">';
                                                
                                                $arm_paid_post_plans_wrapper .= '<a href="javascript:void(0)" class="arm_delete_plan" title="'.esc_attr__('Delete Post', 'ARMember').'" onclick="showConfirmBoxCallback_post_plan(\''.$pID.'\');"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 5.33333H21M16.5 5.33333L16.1956 4.43119C15.9005 3.55694 15.7529 3.11982 15.4793 2.79664C15.2376 2.51126 14.9274 2.29036 14.5768 2.1542C14.1798 2 13.7134 2 12.7803 2H11.2197C10.2866 2 9.8202 2 9.4232 2.1542C9.07266 2.29036 8.76234 2.51126 8.5207 2.79664C8.24706 3.11982 8.09954 3.55694 7.80447 4.43119L7.5 5.33333M18.75 5.33333V16.6667C18.75 18.5336 18.75 19.4669 18.3821 20.18C18.0586 20.8072 17.5423 21.3171 16.9072 21.6367C16.1852 22 15.2402 22 13.35 22H10.65C8.75982 22 7.81473 22 7.09278 21.6367C6.45773 21.3171 5.94143 20.8072 5.61785 20.18C5.25 19.4669 5.25 18.5336 5.25 16.6667V5.33333M14.25 9.77778V17.5556M9.75 9.77778V17.5556" stroke="#617191" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>';
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box" id="arm_confirm_box_post_plan_'.$pID.'" style="right: -15px;top: 1.4rem;">';
                                                    
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_body">';
                                                
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_arrow"></div>';
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_text_title">'.esc_html__('Delete', 'ARMember').'</div>';
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_text">'.esc_html__('Are you sure you want to delete this post from user?', 'ARMember').'</div>'; 
                                               
                                                $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_btn_container">';
                                                
                                                $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armcancel" onclick="hideConfirmBoxCallback();">'.esc_html__('Cancel', 'ARMember').'</button>';

                                                $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armok arm_member_paid_plan_delete_btn arm_margin_right_0" data-item_id='.$pID.' >'.esc_html__('Delete', 'ARMember').'</button>';
                                                $arm_paid_post_plans_wrapper .= '</div>';
                                                $arm_paid_post_plans_wrapper .= '</div>';
                                                $arm_paid_post_plans_wrapper .= '</div></div>';
                                                
                                            //}
                                            

                                    $arm_paid_post_plans_wrapper .= '</td>';
                                $arm_paid_post_plans_wrapper .= '</tr>';


                                $count_plans++;
                            }
                            else{
                                if (!empty($pID)) {
                                    $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $pID, true);
                                    $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                                    $planData = shortcode_atts($defaultPlanData, $userPlanDatameta);



                                    if (!empty($planData)) {
                                        $planDetail = $planData['arm_current_plan_detail'];
                                        if (!empty($planDetail)) {
                                            $planObj = new ARM_Plan(0);
                                            $planObj->init((object) $planDetail);
                                        } else {
                                            $planObj = new ARM_Plan($pID);
                                        }
                                    }

                                    $no = $count_plans;
                                    $planName = $planObj->name;
                                    $grace_message = '';
                                    $starts_date = !empty($planData['arm_start_plan']) ? $planData['arm_start_plan'] : '';
                                    $started_date = !empty($planData['arm_started_plan_date']) ? $planData['arm_started_plan_date'] : '';

                                    $starts_on = !empty($starts_date) ? date_i18n($date_format, $starts_date) : '-';

                                    if($started_date != '' && $started_date <= $starts_date) {
                                        $starts_on = date_i18n($date_format, $started_date);
                                    }
                                    $expires_on = !empty($planData['arm_expire_plan']) ? '<span id="arm_user_expiry_date_' . esc_attr($pID) . '" style="display: flex;"> ' . date_i18n($date_format, $planData['arm_expire_plan']) . ' <img src="' . MEMBERSHIPLITE_IMAGES_URL . '/edit_icon.svg" width="26" style="margin: -4px 0 0 5px; cursor: pointer;" title="' . esc_attr__('Change Expiry Date', 'ARMember') . '" data-plan_id="' . esc_attr($pID) . '" class="arm_edit_user_expiry_date armhelptip tipso_style"></span><span id="arm_user_expiry_date_box_' . esc_attr($pID) . '" style="display: none;" class="arm_position_relative"><input type="text" value="' . date($arm_common_date_format, $planData['arm_expire_plan']) . '"  data-date_format="'.esc_attr($arm_common_date_format).'" name="arm_subscription_expiry_date_' . esc_attr($pID) . '"  id="arm_subscription_expiry_date_'. esc_attr($pID).'_'.esc_attr($user_id).'" class="arm_member_form_input arm_user_plan_expiry_date_picker arm_width_120 arm_min_width_120" /><img src="' . MEMBERSHIPLITE_IMAGES_URL . '/arm_save_icon.png" style="display:none;" width="14" height="16" title="' . esc_attr__('Save Expiry Date', 'ARMember') . '" class="arm_edit_plan_action_button arm_member_save_post armhelptip tipso_style arm_vertical_align_middle" id="arm_member_save_post_'. esc_attr($pID).'" data-plan_id="' . esc_attr($pID) . '" data-user_id="' . esc_attr($user_id) . '" /><img src="' . MEMBERSHIPLITE_IMAGES_URL . '/cancel_date_icon.png" width="11" height="11" title="' . esc_attr__('Cancel', 'ARMember') . '" data-plan_id="' . esc_attr($pID) . '" data-plan-expire-date="' . esc_html( date($arm_common_date_format, $planData['arm_expire_plan']) ) . '" class="arm_cancel_edit_user_expiry_date"></span>' : esc_html__('Never Expires', 'ARMember');

                                    $renewal_on = !empty($planData['arm_next_due_payment']) ? date_i18n($date_format, $planData['arm_next_due_payment']) : '-';
                                    $trial_starts = !empty($planData['arm_trial_start']) ? $planData['arm_trial_start'] : '';
                                    $trial_ends = !empty($planData['arm_trial_end']) ? $planData['arm_trial_end'] : '';
                                    $arm_payment_mode = ( $planData['arm_payment_mode'] == 'auto_debit_subscription') ? '<br/>(' . esc_html__('Auto Debit','ARMember') . ')' : '';
                                    $arm_payment_cycle = !empty($planData['arm_payment_cycle']) ? $planData['arm_payment_cycle'] : '';
                                    if ($planObj->is_recurring()) {
                                        $recurring_plan_options = $planObj->prepare_recurring_data($arm_payment_cycle);
                                        $recurring_time = $recurring_plan_options['rec_time'];
                                        $completed = $planData['arm_completed_recurring'];
                                        if ($recurring_time == 'infinite' || empty($planData['arm_expire_plan'])) {
                                            $remaining_occurence = esc_html__('Never Expires', 'ARMember');
                                        } else {
                                            $remaining_occurence = $recurring_time - $completed;
                                        }

                                        if (!empty($planData['arm_expire_plan'])) {
                                            if ($remaining_occurence == 0) {
                                                $renewal_on = esc_html__('No cycles due', 'ARMember');
                                            } else {
                                                $renewal_on .= "<br/>( " . $remaining_occurence . esc_html__(' cycles due', 'ARMember') . " )";
                                            }
                                        }
                                        $arm_is_user_in_grace = $planData['arm_is_user_in_grace'];

                                        $arm_grace_period_end = $planData['arm_grace_period_end'];

                                        if ($arm_is_user_in_grace == "1") {
                                            $arm_grace_period_end = date_i18n($date_format, $arm_grace_period_end);
                                            $grace_message .= "<br/>( " . esc_html__('grace period expires on', 'ARMember') ." ". $arm_grace_period_end . " )";
                                        }
                                    }

                                    $arm_plan_is_suspended = '';

                                    $trial_active = '';
                                    $plans_is_odd_even =($count_plans % 2 == 0) ? 'even' : 'odd';

                                    $arm_paid_post_plans_wrapper .= '<tr class="arm_user_plan_row '. esc_attr($plans_is_odd_even).'" id="arm_user_future_plan_div_'.esc_attr($count_plans).'">';
                                    $count_plans_no = $no + 1;
                                    //$arm_paid_post_plans_wrapper .= '<td>'.$count_plans_no.'</td>';

                                    $arm_paid_post_plans_wrapper .= '<td class="arm_edit_plan_name">'. esc_html($planName) . $arm_plan_is_suspended.'</td>';
                                    $arm_paid_post_plans_wrapper .= '<td class="arm_edit_plan_type">'.$planObj->new_user_plan_text(false, $arm_payment_cycle).'</td>';
                                    $arm_paid_post_plans_wrapper .= '<td class="arm_edit_plan_start">'.esc_html($starts_on) . $trial_active.'</td>';
                                    $arm_paid_post_plans_wrapper .= '<td class="arm_edit_plan_expiry">'.$expires_on.'</td>';
                                    $arm_paid_post_plans_wrapper .= '<td class="arm_edit_plan_cycle_date">'.$renewal_on . $grace_message . $arm_payment_mode.'</td>';

                                    $arm_paid_post_plans_wrapper .= '<td>';
                                    
                                    $arm_paid_post_plans_wrapper .= '<input name="arm_user_future_plan[]" value='.esc_attr($pID).' type="hidden" id="arm_user_paid_post_future_plan_'.esc_attr($pID).'">';

                                            $arm_paid_post_plans_wrapper .= '<div class="arm_position_relative">';
                                                
                                            $arm_paid_post_plans_wrapper .= '<a href="javascript:void(0)" class="arm_edit_post_plan_action_button armhelptip tipso_style" href="javascript:void(0)" title="'.esc_attr__('Delete Post', 'ARMember').'" onclick="showConfirmBoxCallback_plan(\''.$pID.'\');"><img src="' . MEMBERSHIPLITE_IMAGES_URL . '/grid_delete_icon_trans.svg"></a>';
                                            $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box" id="arm_confirm_box_plan_'.esc_attr($pID).'" style="right: -15px;top: 1.4rem;">';
                                                
                                            $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_body">';
                                            
                                            $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_arrow"></div>';
                                            $arm_paid_post_plans_wrapper .= "<div class='arm_confirm_box_text_title'>".esc_html__( 'Delete', 'ARMember' )."</div>";

                                            $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_text">'.esc_html__('Are you sure you want to delete this post from user?', 'ARMember').'</div>'; 
                                            
                                            
                                            $arm_paid_post_plans_wrapper .= '<div class="arm_confirm_box_btn_container">';
                                            $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armcancel" onclick="hideConfirmBoxCallback();">'.esc_html__('Cancel', 'ARMember').'</button>';
                                            $arm_paid_post_plans_wrapper .= '<button type="button" class="arm_confirm_box_btn armok arm_member_paid_plan_delete_btn" data-item_id='.esc_attr($pID).' >'.esc_html__('Delete', 'ARMember').'</button>';

                                            $arm_paid_post_plans_wrapper .= '</div>';
                                            $arm_paid_post_plans_wrapper .= '</div>';
                                            $arm_paid_post_plans_wrapper .= '</div></div>';
                                               
                                           
                                            

                                        $arm_paid_post_plans_wrapper .=  '</td>';





                                    $arm_paid_post_plans_wrapper .= '</tr>';


                                    $count_plans++;
                                }
                            }
                            if( in_array( $pID, $futurePlanIDs ) ){
                                $arm_paid_post_plans_wrapper .= '<input name="arm_user_future_plan[]" value='.esc_attr($pID).' type="hidden" id="arm_user_paid_post_future_plan_'.esc_attr($uniq_delete_no).'">';
                            } else {
                                $arm_paid_post_plans_wrapper .= '<input type="hidden" name="arm_user_plan2[]" id="arm_user_paid_post_div_'.esc_attr($uniq_delete_no).'" value="'.esc_attr($pID).'"/>';
                                
                                $arm_paid_post_plans_wrapper .= '<input type="hidden" name="arm_subscription_start_date2[]" id="arm_user_paid_post_date_div_'.esc_attr($uniq_delete_no).'" value='. esc_attr( date('m/d/Y', (int)$planData['arm_start_plan']) ).' />';
                            }
                            /*$arm_paid_post_plans_wrapper .= '<input type="hidden" name="arm_user_plan2[]" value="'.$pID.'"/>';
                            $arm_paid_post_plans_wrapper .= '<input type="hidden" name="arm_subscription_start_date2[]" value='.date('m/d/Y', $planData['arm_start_plan']).' />';*/
                        }
                        $plan_row_count++;
                    }
                } else{
                    $arm_paid_post_plans_wrapper .= '<tr class="arm_user_edit_plan_table" ><td colspan="6" class="arm_text_align_center">'. esc_html__("This user don't have any paid post.", 'ARMember'). '</td></tr>';
                }


                $arm_paid_post_plans_wrapper .= '</table>';
                

                if(!empty($planIDs) && $membership_count>5){
                    $member_paid_post_plans_pagging = $arm_global_settings->arm_get_paging_links($current_page, $membership_count, $per_page);
                    $arm_paid_post_plans_wrapper .= '<div class="arm_member_paid_post_plans_pagination_block">';
                    $arm_paid_post_plans_wrapper .= '<div class="arm_member_paid_post_plans_modal_paging_container" style="float: right;">'.$member_paid_post_plans_pagging.'</div>';
                    $arm_paid_post_plans_wrapper .= '</div>';
                }
                 
                $arm_paid_post_plans_wrapper .= '</div>';
                $arm_paid_post_plans_wrapper .= '<input type="hidden" id="arm_paid_post_counter_value" value="'.esc_attr( count($planIDs) ).'">';
            }   
            return  $arm_paid_post_plans_wrapper;
        }
	
	    function arm_add_paid_post_message_types( $message_types ){

            $pp_message_types = array(
                'on_new_subscription_post' => esc_html__('On new paid post purchase', 'ARMember'),
                'on_renew_subscription_post' => esc_html__('On renew paid post purchase', 'ARMember'),
                'on_recurring_subscription_post' => esc_html__('On recurring paid post purchase', 'ARMember'),
                'on_cancel_subscription_post' => esc_html__( 'On cancel paid post', 'ARMember' ),
                'before_expire_post' => esc_html__('Before paid post expire', 'ARMember'),
                'on_expire_post' => esc_html__('On Expire paid post', 'ARMember')
            );

            return array_merge( $message_types, $pp_message_types );
        }

        function arm_update_paid_post_access_rules( $posted_data ){

            $form_data = isset( $posted_data['form_data'] ) ? json_decode( stripslashes_deep( $posted_data['form_data'] ), true ) : array();

            if( !empty( $form_data ) ){
                global $ARMember;
                foreach( $form_data as $rule_id => $rule_data ){
                    if( !empty( $rule_data['protection'] ) && 1 == $rule_data['protection'] ) {
                        $isEnablePaidPost = get_post_meta( $rule_id, 'arm_is_paid_post', true );
                        if( 1 == $isEnablePaidPost ){
                            $plan_id = $this->arm_get_plan_from_post_id( $rule_id );
                            if( !empty( $plan_id ) ){
                                $getRules = get_post_meta( $rule_id, 'arm_access_plan', false );
                                if( !empty( $getRules ) && !in_array( $plan_id, $getRules ) ){
                                    add_post_meta( $rule_id, 'arm_access_plan', $plan_id );
                                }
                            }
                        }
                    }
                }

            }

        }

        function arm_update_access_plan_for_drip_rules_callback( $post_id ){

            if( !empty( $post_id ) ){

                $plan_id = $this->arm_get_plan_from_post_id( $post_id );

                if( !empty( $plan_id ) ){
                    $getRules = get_post_meta( $post_id, 'arm_access_plan', false );
                    if( !empty( $getRules ) && !in_array( $plan_id, $getRules ) ){
                        add_post_meta( $post_id, 'arm_access_plan', $plan_id );
                    }
                }

            }
        }
	
	   function arm_ajax_display_paid_post_cycle() {
	        global $arm_payment_gateways, $ARMember, $arm_capabilities_global,$wpdb,$arm_ajax_pattern_start,$arm_ajax_pattern_end;
        
	        $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_pay_per_post'], '1',1);
	        $arm_currency = $arm_payment_gateways->arm_get_global_currency();
	        $type = 'failed';
	        $plan_name = '';
	        $content = '';
	        if( isset($_POST['paid_post_id']) && !empty($_POST['paid_post_id']) ) {//phpcs:ignore
	            $count_cycle = '';
            
	            $paid_post_id = intval( $_POST['paid_post_id'] );//phpcs:ignore
           
	            $paid_post_plan_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_post_id` = %d", $paid_post_id) ); //phpcs:ignore --Reason $ARMember->tbl_arm_membership_setup is a table name
            
	            $plan_name = esc_html(stripslashes($paid_post_plan_data->arm_subscription_plan_name));
	            $plan_options = isset( $paid_post_plan_data->arm_subscription_plan_options ) ? maybe_unserialize( $paid_post_plan_data->arm_subscription_plan_options ) : array();

	            if( isset( $plan_options['payment_cycles'] ) && !empty( $plan_options['payment_cycles'] ) ){
	                $payment_cycles_data = $plan_options['payment_cycles'];
            
	                if($payment_cycles_data > 0) {
	                    $type = 'success';
	                    $typeArrayMany = array(
	                        'D' => esc_html__("days", 'ARMember'),
	                        'W' => esc_html__("weeks", 'ARMember'),
	                        'M' => esc_html__("months", 'ARMember'),
	                        'Y' => esc_html__("years", 'ARMember'),
	                    );
	                    $typeArray = array(
	                        'D' => esc_html__("day", 'ARMember'),
	                        'W' => esc_html__("week", 'ARMember'),
	                        'M' => esc_html__("month", 'ARMember'),
	                        'Y' => esc_html__("year", 'ARMember'),
	                    );

	                    $content .= '<div class="popup_content_text arm_plan_cycle_text arm_text_align_center arm_padding_32 arm_padding_top_0" cellspacing="1">';
	                    $content .= '<table class="arm_user_edit_plan_table arm_text_align_center arm_plan_cycle_text" cellspacing="1">';
	                    $content .= '<tr class="arm_user_plan_row arm_user_plan_head odd">';
	                    $content .= '<th class="arm_edit_plan_name">' . esc_html__('Label', 'ARMember') . '</th>';
	                    $content .= '<th class="arm_edit_plan_type">' . esc_html__('Amount', 'ARMember') . '</th>';
	                    $content .= '<th class="arm_edit_plan_start">' . esc_html__('Billing Cycle', 'ARMember') . '</th>';
	                    $content .= '<th class="arm_edit_plan_expire">' . esc_html__('Recurring Time', 'ARMember') . '</th>';
	                    $content .= '</tr>';

	                    foreach ($payment_cycles_data as $arm_cycle) {
	                        $count_cycle++;
	                        $row_class = ($count_cycle % 2 == 0) ? 'odd' : 'even';
	                        $arm_label = $arm_cycle['cycle_label'];
	                        $arm_amount = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $arm_cycle['cycle_amount']) . ' ' . $arm_currency;
	                        $arm_billing_cycle = $arm_cycle['billing_cycle'];
	                        $arm_billing_type = $arm_cycle['billing_type'];
	                        $arm_recurring_time = $arm_cycle['recurring_time'];

	                        $arm_billing_text = '';
	                        if($arm_billing_cycle > 1) {
	                            $arm_billing_text = $arm_billing_cycle . ' ' . $typeArrayMany[$arm_billing_type];
	                        } else {
	                            $arm_billing_text = $arm_billing_cycle . ' ' . $typeArray[$arm_billing_type];
	                        }

	                        $content .= '<tr class="arm_user_plan_row arm_plan_cycle ' . esc_attr($row_class) . '">';
	                            $content .= '<td class="arm_edit_plan_name" data-label="'.esc_html__('Paid Post','ARMember').'">' . esc_html($arm_label) . '</td>';
	                            $content .= '<td class="arm_edit_plan_type" data-label="'.esc_html__('Paid Amount','ARMember').'">' . esc_html($arm_amount) . '</td>';
	                            $content .= '<td class="arm_edit_plan_start" data-label="'.esc_html__('Start on','ARMember').'">' . esc_html($arm_billing_text) . '</td>';
	                            $content .= '<td class="arm_edit_plan_expire" data-label="'.esc_html__('Expires on','ARMember').'">' . esc_html($arm_recurring_time) . '</td>';
	                        $content .= '</tr>';

	                    }

	                    $content .= '</table>';
	                    $content .= '</div>';
	                }

	            } else {
	                $content = '<center>'.esc_html__('Plan does not have any cycle.', 'ARMember').'</center>';
	            }
	        } else {
	            $content = '<center>'.esc_html__('Plan does not have any cycle.', 'ARMember').'</center>';
	        }
	        echo $arm_ajax_pattern_start.''.$plan_name . '^|^' . $content.''.$arm_ajax_pattern_end; //phpcs:ignore
	        die;
	   }

        function arm_get_total_members_in_paid_post($post_id = 0) {

           global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_payment_gateways;
            $res = 0;
            if (!empty($post_id)) {
                $user_arg = array(
                    'meta_key' => 'arm_user_post_ids',
                    'meta_value' => $post_id,
                    'meta_compare' => 'like',
                    'role__not_in' => 'administrator'
                );
                $users = get_users($user_arg);
                
                $res = 0;
                foreach ($users as $user) {
                    $post_ids = get_user_meta($user->ID, 'arm_user_post_ids', true);
                    if (!empty($post_ids) && is_array($post_ids)) {
                        if (in_array($post_id, $post_ids)) {
                            $res++;
                        }
                    }
                }
            }
            return $res;
        }

    }
}

global $arm_pay_per_post_feature;
$arm_pay_per_post_feature = new ARM_pay_per_post_feature();