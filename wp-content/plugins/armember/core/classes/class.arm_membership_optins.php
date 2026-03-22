<?php 
if (!class_exists('ARM_membership_optins'))
{
	class ARM_membership_optins
	{
        var $arm_optins_settings = array();
        function __construct()
		{
            global $arm_email_settings;
            if( !empty( $arm_email_settings->isOptInsFeature ) )
            {
                $this->arm_optins_settings = get_option('arm_email_settings');

                add_action('arm_on_expire_cancel_subscription', array($this,'arm_member_remove_optins'), 10, 4);
                add_action('arm_after_cancel_subscription', array( $this,'arm_member_remove_optins'), 100, 4 );
                
                add_action('arm_cancel_subscription_gateway_action', array($this,'arm_member_cancel_optins'), 10, 2);
                add_action('arm_user_plan_status_action_failed_payment', array($this,'arm_member_cancel_optins'), 10, 2);
                add_action('arm_user_plan_status_action_eot', array($this,'arm_member_cancel_optins'), 10, 2);
                add_action('arm_cancel_except_recurring_subscription_action', array($this,'arm_cancel_except_recurring_subscription_action_optins'), 10, 2);

                add_action('arm_after_user_plan_change', array($this,'arm_member_remove_and_add_optin'), 10, 2);
                add_action('arm_after_user_plan_change_by_admin', array($this,'arm_member_remove_and_add_optin'), 10, 2);
                add_action('arm_after_recurring_payment_success_outside', array($this,'arm_member_remove_and_add_optin'), 10, 5);
                add_action('arm_action_outside_after_assign_import_user_plan', array($this,'arm_member_remove_and_add_optin'), 10, 3);

                add_action('wp_ajax_arm_optins_sync_progress', array($this, 'arm_optins_sync_progress'));       
                add_action('wp_ajax_arm_optins_sync', array($this, 'arm_optins_sync_func')); 
            }
        }
        /* for get optins details */
        function arm_member_optins_details_func($optins_name){
     
            $email_settings_unser = $this->arm_optins_settings;

			$all_email_settings = maybe_unserialize($email_settings_unser);
			$all_email_settings = apply_filters('arm_get_all_email_settings', $all_email_settings);

            $arm_optins_data = (!empty($all_email_settings['arm_email_tools'][$optins_name]) ? $all_email_settings['arm_email_tools'][$optins_name] : '');
            return $arm_optins_data;

        }
        /* for get user's meta details */
        function arm_optins_user_metadata_func($user_id,$optins_name){
            global $ARMember,$wpdb,$arm_social_feature,$arm_is_social_signup;
            $user_metadata = array();
            $user_meta_data = get_user_meta($user_id);
            if(!empty($user_meta_data))
            {
	            foreach($user_meta_data as $key => $value){
	                if (isset($value[0])) {
	                    $user_metadata[$key] = $value[0];
	                }
	            }
            }
            else {
                $user_meta_data = array();
            }
            $user_info = get_user_by('id', $user_id);
            if(!empty($user_info) && !empty($user_info->user_email))
            {
                $user_metadata['user_email'] = $user_info->user_email;
            }

            if($arm_is_social_signup){
                $social_settings = $arm_social_feature->arm_get_social_settings();
                $user_metadata['social_login'] = $social_settings['options']['optins_name'];
                $user_metadata['optins_data']['status'] = !empty($social_settings['options']['arm_one_click_social_signup']) ? $social_settings['options']['arm_one_click_social_signup'] : 0 ;
                $user_metadata['optins_data']['list_id'] = !empty($social_settings['options'][$optins_name]['list_id']) ? $social_settings['options'][$optins_name]['list_id'] : 0;
            }
            
            $form_id = (isset($user_metadata['arm_form_id'])) ? $user_metadata['arm_form_id'] : '';

            if(!empty($form_id)){
                $form_settings = $wpdb->get_var("SELECT `arm_form_settings` FROM `" . $ARMember->tbl_arm_forms . "` WHERE `arm_form_id`='" . $form_id . "'");
                $form_settings = (!empty($form_settings)) ? maybe_unserialize($form_settings) : array();
                $user_metadata['optins_data'] = (!empty($form_settings['email'][$optins_name]) ? $form_settings['email'][$optins_name] : '');
            }
            return $user_metadata;
        }
        function arm_optins_user_meta_data($user_id,$meta_key_arr){

            $user_data = array();
            foreach($meta_key_arr as $meta_key)
            {       
                if(!empty($meta_key)){
                    
                    $user_meta_data = do_shortcode('[arm_usermeta id="'.$user_id.'" meta="'.$meta_key.'"]');  
       
                    $user_data[$meta_key] = !empty($user_meta_data) ? $user_meta_data : '';
                    if($meta_key == 'user_email' || $meta_key == 'user_url'){
                        preg_match('/<a[^>]*>(.*?)<\/a>/', $user_meta_data, $matches1);
                        $user_data[$meta_key] = !empty($user_meta_data) ? (!empty($matches1[1]) ? $matches1[1] : $user_meta_data) : '';    
                    }
                }
            }
            $user_data = apply_filters('arm_optins_modify_user_custom_field_value', $user_data, $user_id);
            return $user_data;
        }
      
        function arm_optins_sync_func(){

            global $wpdb, $ARMember, $arm_capabilities_global, $arm_global_settings;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:verifying nonce
            @set_time_limit(0);
            $ARMember->arm_session_start();
            $response = array('type' => 'error', 'msg' => esc_html__('Something went wrong.', 'ARMember'));
            $optins_name = (!empty($_POST['optins_action'])) ? sanitize_text_field( $_POST['optins_action'] ) : '';
            $args = array(
                'role__not_in' => array( 'administrator' ),
                'orderby' => 'ID',
                'order' => 'ASC',
                'fields' => 'ID',
            );

            $users = get_users($args);
            $totalMember = count($users);
            
            if( empty( $_REQUEST['arm_membership_optins_continue_flag'] ) )
            {
                $_SESSION['arm_optins_sync_users'] = 0;
                $_SESSION['arm_optins_total_users'] = $totalMember;
            }
            if(!empty($optins_name)){
                $etoolName = $optins_name;
                $etool = apply_filters('arm_opt_ins_display_name', $optins_name, $etoolName);
                if($totalMember > 50){

                    $chunked_user_data = array_chunk($users, 50, false);
    
                    $total_chunked_data = count($chunked_user_data); 
    
                    for($ch_data = 0; $ch_data < $total_chunked_data; $ch_data++) {
                        $chunked_data = null;
                        $chunked_data = $chunked_user_data[$ch_data];
                        foreach($chunked_data as $user_id)
                        {
                            $plan_id = get_user_meta($user_id, 'arm_user_plan_ids', true);
                            do_action('arm_optins_sync_members_'.$optins_name, $user_id, $plan_id);
                            
                            $_SESSION['arm_optins_sync_users'] ++;
                            @session_write_close();
                            $ARMember->arm_session_start(true);   
                        }
                    }

                    $response = array('type' => 'success', 'msg' => esc_html__('Members are successfully synced to', 'ARMember') . ' ' . $etool , 'arm_optins_continue_flag' => '1');
                }else{
                    foreach($users as $user_id)
                    {
                        $plan_id = get_user_meta($user_id, 'arm_user_plan_ids', true);
                        do_action('arm_optins_sync_members_'.$optins_name, $user_id, $plan_id); 
                        $_SESSION['arm_optins_sync_users'] ++;
                        @session_write_close();
                        $ARMember->arm_session_start(true);  
                    }
                    $response = array('type' => 'success', 'msg' =>  esc_html__('Members are successfully synced to', 'ARMember') . ' ' . $etool ,'arm_optins_continue_flag' => '1');
                }
            }
            
            echo json_encode($response);
            die();
        }
        function arm_optins_sync_progress() {
            global $ARMember, $arm_capabilities_global;
            $ARMember->arm_session_start();
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $arm_synced_users = isset($_SESSION['arm_optins_sync_users']) ? (int) $_SESSION['arm_optins_sync_users'] : 0;
            $arm_total_users = isset($_SESSION['arm_optins_total_users']) ? (int) $_SESSION['arm_optins_total_users'] : 0;

            $response = array();
            $response['total_users'] = $arm_total_users;
            $response['currently_synced'] = $arm_synced_users;
            if ($response['currently_synced'] > 0) {
                if ($response['currently_synced'] >= $response['total_users'] && $response['total_users'] != 0) {
                    $percentage = 100;
                    $response['continue'] = false;
                    unset($_SESSION['arm_optins_sync_users']);
                    unset($_SESSION['arm_optins_total_users']);
                } else {
			if(empty($response['total_users']))
			{
				$percentage = 100;
			}
			else {
				$percentage = (100 * $response['currently_synced']) / $response['total_users'];
			}
                    $percentage = round($percentage);
                    $response['continue'] = true;
                }
                $response['percentage'] = $percentage;
            }else {
                $response['percentage'] = 0;
                $response['continue'] = true;
            }
            $response['error'] = false;
            @session_write_close();
            $ARMember->arm_session_start(true);
            echo json_encode(stripslashes_deep($response));
            die();
        }

        function arm_member_remove_and_add_optin( $user_id, $plan_id, $payment_gateway = '', $payment_mode = '', $user_subsdata = '' )
        {
            do_action( 'arm_add_member_from_opt_ins_external', $user_id, $plan_id, $payment_gateway, $payment_mode, $user_subsdata );
        }
        
        function arm_member_remove_optins( $user_id, $plan, $cancel_plan_action, $planData )
        {
            do_action( 'arm_remove_member_from_opt_ins_external', $user_id, $plan, $cancel_plan_action, $planData );
        }

        function arm_member_cancel_optins( $user_id, $plan_id ) //arm_member_remove_optins function 2nd parameter id object. So created this function
        {
            do_action( 'arm_remove_member_from_opt_ins_external', $user_id, $plan_id );
        }
        function arm_cancel_except_recurring_subscription_action_optins( $user_id,$plan_id )
        {
            do_action('arm_remove_member_from_opt_ins_external', $user_id, $plan_id );
        }
    }
}

global $arm_membership_optins;
$arm_membership_optins = new ARM_membership_optins();