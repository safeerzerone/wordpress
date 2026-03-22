<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ARM_VCExtend {

    protected static $instance = null;
    var $is_membership_vdextend = 0;
    var $isWPBakryBuilderRestrictionFeature;

    public function __construct() {
        $is_wpbakery_builder_restriction_feature = get_option('arm_is_wpbakery_page_builder_restriction_feature');
        $this->isWPBakryBuilderRestrictionFeature = ($is_wpbakery_builder_restriction_feature == '1') ? true : false;
        if ($this->isWPBakryBuilderRestrictionFeature) {
            add_action('init', array($this, 'ARM_arm_pro_edit_profile'));
            add_action('init', array($this, 'ARM_arm_conditional_redirection'));
            add_action('init', array($this, 'ARM_arm_conditional_redirection_role'));
            add_action('init', array($this, 'ARM_arm_user_badge'));
            add_action('vc_before_init', array($this, 'ARM_init_all_shortcode'));
        }
    }

    public function ARM_init_all_shortcode() {

        add_shortcode('arm_pro_profile_detail_vc', array($this, 'arm_pro_edit_profile_vc_func'));
        add_shortcode('arm_conditional_redirection_vc', array($this, 'arm_conditional_redirection_vc_func'));
        add_shortcode('arm_conditional_redirection_role_vc', array($this, 'arm_conditional_redirection_role_vc_func'));
            
        add_shortcode('arm_user_badge_vc', array($this, 'arm_user_badge_vc_func'));
    }
    public function ARM_arm_pro_edit_profile() {
        global $arm_version, $ARMember, $arm_member_forms;

        if (!$this->isWPBakryBuilderRestrictionFeature) {
            return;
        }

        $arm_forms = $arm_member_forms->arm_get_all_member_forms('arm_form_id, arm_form_label, arm_form_type');
        $armFormList = array();
        $armFormList = array(
            esc_html__('Select Form', 'ARMember') => '',
        );

        if (!empty($arm_forms)) {
            foreach ($arm_forms as $_form) {
                if ($_form['arm_form_type'] == 'edit_profile') {
                    $armFormList[ strip_tags(stripslashes($_form['arm_form_label'])) . ' (ID: ' . $_form['arm_form_id'] . ')' ] =  $_form['arm_form_id'];
                }
            }
        }

        $arm_form_position = array(
            esc_html__('Center','ARMember') => 'center',
            esc_html__('Left','ARMember') => 'left',
            esc_html__('Right','ARMember') => 'right',
        );

        if (function_exists('vc_map')) {
            vc_map(array(
                'name' => esc_html__('ARMember Edit Profile', 'ARMember'),
                'base' => 'arm_pro_profile_detail_vc',
                'category' => esc_html__('ARMember', 'ARMember'),
                'class' => '',
                'controls' => 'full',
                'icon' => 'arm_vc_icon',
                'admin_enqueue_css' => array(MEMBERSHIP_URL . '/core/vc/arm_vc.css'),
                'front_enqueue_css' => MEMBERSHIP_URL . '/core/vc/arm_vc.css',
                'params' => array(
                    array(
                        'type' => 'dropdown',
                        'class' => 'arm_element_dropdown',
                        'heading' => esc_html__('Select Form', 'ARMember'),
                        'param_name' => 'form_id',
                        'value' => $armFormList, 
                        'group' => esc_html__( 'ARMember Edit profile', 'ARMember' ),
                    ),
                    array(
                        'type' => 'dropdown',
                        'class' => 'arm_element_dropdown',
                        'heading' => esc_html__('Form Position', 'ARMember'),
                        'param_name' => 'form_position',
                        'value' => $arm_form_position,
                        'group' => esc_html__( 'ARMember Edit profile', 'ARMember' ),
                    )
                )
            ));
        }
    }
    public function arm_pro_edit_profile_vc_func( $atts, $content, $tag ) {

        $hasaccess = $this->chack_shortcode_hasaccess( $atts );

        $atts = shortcode_atts(
            array(
                'id' => '',
                'form_id' => '',
                'form_position' => 'center',
            ),
            $atts,
            $tag
        );
        
        if($hasaccess) {
            $form_id = isset($atts['form_id']) && !empty($atts['form_id']) ? intval( $atts['form_id'] ) : '105' ;
            $form_position = isset($atts['form_position']) && !empty($atts['form_position']) ? esc_attr( $atts['form_position'] ) : 'center' ;
        
            return do_shortcode('[arm_profile_detail form_id="'.$form_id.'" form_position="'.$form_position.'"]');
        } else {
            return '';
        }
    }
    public function ARM_arm_conditional_redirection(){
        global $arm_version,$ARMember, $arm_subscription_plans;

        if (!$this->isWPBakryBuilderRestrictionFeature && !$this->arm_vc_pro_is_active) {
            return;
        }

        $armFormCondition = array(
            esc_html__('Select Condition', 'ARMember') => '',
            esc_html__('Having','ARMember') => 'having',
            esc_html__('Not Having','ARMember') => 'nothaving',
        );

        $arm_planlist = array();
        $arm_planlist = array(
            esc_html__('Select Plan', 'ARMember') => '',
            esc_html__('Non Logged in Users','ARMember') => 'not_logged_in',
        );
        $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('arm_subscription_plan_id, arm_subscription_plan_name');
        if(!empty($all_plans)){
            foreach($all_plans as $plan){
                $arm_planlist[ stripslashes($plan['arm_subscription_plan_name']) ] = $plan['arm_subscription_plan_id'];
            }
        }
            

        if (function_exists('vc_map') && defined('MEMBERSHIPLITE_URL')) {
            vc_map(array(
                'name' => esc_html__('ARMember Conditional Redirect', 'ARMember'),
                'base' => 'arm_conditional_redirection_vc',
                'category' => esc_html__('ARMember', 'ARMember'),
                'class' => '',
                'controls' => 'full',
                'icon' => 'arm_vc_icon',
                'admin_enqueue_css' => array(MEMBERSHIPLITE_URL . '/core/vc/arm_vc.css'),
                'front_enqueue_css' => MEMBERSHIPLITE_URL . '/core/vc/arm_vc.css',
                'params' => array(
                    array(
                        'type' => 'dropdown',
                        'class' => 'arm_element_dropdown',
                        'heading' => esc_html__('Condition','ARMember'),
                        'param_name' => 'condition',
                        'value' => $armFormCondition,
                        'group' => esc_html__( 'ARMember Conditional Redirect', 'ARMember' )
                    ),
                    array(
                        'type' => 'dropdown',
                        'class' => 'arm_element_dropdown',
                        'heading' => esc_html__('Select Plan', 'ARMember'),
                        'param_name' => 'plans',
                        'value' => $arm_planlist,
                        'group' => esc_html__( 'ARMember Conditional Redirect', 'ARMember' )
                    ),
                    array(
                        'type' => 'textfield',
                        'class' => '',
                        'heading' => esc_html__('Redirect URL','ARMember'),
                        'description' => '&nbsp;Please Enter URL with http:// or https://',
                        'param_name' => 'redirect_to',
                        'value' => '',
                        'group' => esc_html__( 'ARMember Conditional Redirect', 'ARMember' )
                    ),
                )
            ));
        }
    }
    public function arm_conditional_redirection_vc_func( $atts, $content, $tag ){
        $hasaccess = $this->chack_shortcode_hasaccess( $atts );

        if ($hasaccess) {
            $condition = isset($atts['condition']) && !empty($atts['condition']) ? esc_attr( $atts['condition'] ) : esc_html__('equals', 'ARMember') ;
            $plans = isset($atts['plans']) && !empty($atts['plans']) ? esc_attr( $atts['plans'] ) : '' ;
            $redirect_to = isset($atts['redirect_to']) && !empty($atts['redirect_to']) ? esc_url( $atts['redirect_to'] ) : ARM_HOME_URL ;

            return do_shortcode('[arm_conditional_redirection condition="'.$condition.'" plans="'.$plans.'" redirect_to="'.$redirect_to.'"]');
        } else {
            return '';
        }

    }

    public function ARM_arm_conditional_redirection_role(){
        global $arm_version,$ARMemberLite,$arm_global_settings;

        if (!$this->isWPBakryBuilderRestrictionFeature && !$this->arm_vc_pro_is_active) {
            return;
        }

        $armFormCondition = array(
            esc_html__('Select Condition', 'ARMember') => '',
            esc_html__('Having','ARMember') => 'having',
            esc_html__('Not Having','ARMember') => 'nothaving',
        );

        $armRolesList = array();
        $all_roles = $arm_global_settings->arm_get_all_roles();
        if (!empty($all_roles)){
            foreach ($all_roles as $role_key => $role_name){
                $armRolesList[ esc_html(stripslashes($role_name)) ] = esc_attr($role_key);
            }
        }

        if (function_exists('vc_map')) {
            vc_map(array(
                'name' => esc_html__('ARMember Conditional Redirect (User Role)', 'ARMember'),
                'base' => 'arm_conditional_redirection_role_vc',
                'category' => esc_html__('ARMember', 'ARMember'),
                'class' => '',
                'controls' => 'full',
                'icon' => 'arm_vc_icon',
                'admin_enqueue_css' => array(MEMBERSHIPLITE_URL . '/core/vc/arm_vc.css'),
                'front_enqueue_css' => MEMBERSHIPLITE_URL . '/core/vc/arm_vc.css',
                'params' => array(
                    array(
                        'type' => 'dropdown',
                        'class' => 'arm_element_dropdown',
                        'heading' => esc_html__('Condition','ARMember'),
                        'param_name' => 'condition',
                        'value' => $armFormCondition,
                        'group' => esc_html__( 'ARMember Conditional Redirect (User Role)', 'ARMember' )
                    ),
                    array(
                        'type' => 'dropdown',
                        'class' => 'arm_element_dropdown',
                        'heading' => esc_html__('Roles','ARMember'),
                        'param_name' => 'roles',
                        'value' => $armRolesList,
                        'group' => esc_html__( 'ARMember Conditional Redirect (User Role)', 'ARMember' )
                    ),
                    array(
                        'type' => 'textfield',
                        'class' => '',
                        'heading' => esc_html__('Redirect URL','ARMember'),
                        'description' => '&nbsp;Please Enter URL with http:// or https://',
                        'param_name' => 'redirect_to',
                        'value' => '',
                        'group' => esc_html__( 'ARMember Conditional Redirect (User Role)', 'ARMember' )
                    ),
                )
            ));
        }
    }
    public function arm_conditional_redirection_role_vc_func( $atts, $content, $tag ){
        $hasaccess = $this->chack_shortcode_hasaccess( $atts );
        
        if ($hasaccess) {
            $condition = isset($atts['condition']) && !empty($atts['condition']) ? esc_attr( $atts['condition'] ) : esc_html__('having', 'ARMember') ;
            $roles = isset($atts['roles']) && !empty($atts['roles']) ? esc_attr( $atts['roles'] ) : '' ;
            $redirect_to = isset($atts['redirect_to']) && !empty($atts['redirect_to']) ? esc_url( $atts['redirect_to'] ) : ARM_HOME_URL ;

            return do_shortcode('[arm_conditional_redirection_role condition="'.$condition.'" roles="'.$roles.'" redirect_to="'.$redirect_to.'"]');
        } else {
            return '';
        }
    }

    public function ARM_arm_user_badge(){
        global $arm_version, $ARMemberLite;

        if (!$this->isWPBakryBuilderRestrictionFeature && !$this->arm_vc_pro_is_active) {
            return;
        }

        if (function_exists('vc_map')) {
            vc_map(array(
                'name' => esc_html__('ARMember User Badge', 'ARMember'),
                'base' => 'arm_user_badge_vc',
                'category' => esc_html__('ARMember', 'ARMember'),
                'class' => '',
                'controls' => 'full',
                'icon' => 'arm_vc_icon',
                'admin_enqueue_css' => array(MEMBERSHIPLITE_URL . '/core/vc/arm_vc.css'),
                'front_enqueue_css' => MEMBERSHIPLITE_URL . '/core/vc/arm_vc.css',
                'params' => array(
                    array(
                        'type' => 'textfield',
                        'class' => '',
                        'heading' => esc_html__('Enter User Id', 'ARMember'),
                        'param_name' => 'user_id',
                        'value' => '',
                        'group' => esc_html__( 'ARMember User Badge', 'ARMember' )
                    ),
                )
            ));
        }
    }
    public function arm_user_badge_vc_func( $atts, $content, $tag ){
        $hasaccess = $this->chack_shortcode_hasaccess( $atts );
        
        if($hasaccess){
            $user_id = isset($atts['user_id']) && !empty($atts['user_id']) ? intval( $atts['user_id'] ) : '' ;

            return do_shortcode('[arm_user_badge user_id="'.$user_id.'"]');
        } else {
            return '';
        }
    }

    public function get_shortcode_default_field( $atts, $tag) {
        $default_arm_member_transaction_vc_fields = array(
            'transaction_id' => 'Transaction ID', 
            'invoice_id' => 'Invoice ID', 
            'plan' => 'Plan', 
            'payment_gateway' => 'Payment Gateway', 
            'payment_type' => 'Payment Type', 
            'transaction_status' => 'Transaction Status', 
            'amount' => 'Amount', 
            'used_coupon_code' => 'Used Coupon Code', 
            'used_coupon_discount' => 'Used Coupon Discount', 
            'payment_date' => 'Payment Date', 
        );
        global $arm_members_directory;
        $allDefaultLabelArray = array();
        $dbProfileFields = $arm_members_directory->arm_template_profile_fields();
        if (!empty($dbProfileFields)) {
            foreach ($dbProfileFields as $db_form) {
                $arm_meta_key = (!empty($db_form['meta_key']) ? $db_form['meta_key'] : '');
                if($arm_meta_key != '' ){
                    $default_arm_account_detail_vc_fields[$arm_meta_key] = $db_form['label'];
                }
            }            
        }
        $default_arm_account_detail_vc_fields['arm_membership_plan'] = esc_html__('Membership Plan', 'ARMember');
        $default_arm_account_detail_vc_fields['arm_membership_plan_renew_date'] = esc_html__( 'Membership Plan Renewal Date', 'ARMember');
        $default_arm_account_detail_vc_fields['arm_membership_plan_expiry_date'] = esc_html__( 'Membership Plan Expiry Date', 'ARMember');        
                
        $default_arm_membership_vc_fields = array(
            'current_membership_no' => esc_html__('No.', 'ARMember'),
            'current_membership_is' => esc_html__('Membership Plan', 'ARMember'),
            'current_membership_recurring_profile' => esc_html__('Plan Type', 'ARMember'),
            'current_membership_started_on' => esc_html__( 'Starts On', 'ARMember'),
            'current_membership_expired_on' => esc_html__( 'Expires On', 'ARMember'),
            'current_membership_next_billing_date' => esc_html__( 'Cycle Date', 'ARMember'),
            'action_button' => esc_html__( 'Action', 'ARMember'),
        );

        $return_shortcode_fields = array();
        $return_shortcode_fields['default_label_field'] = array();
        $return_shortcode_fields['default_value_field'] = array();
        foreach (${'default_'.$tag.'_fields'} as $f_key => $f_value) {
            if (isset($atts[$f_key.'_label']) && !empty($atts[$f_key.'_label'])) {
                $return_shortcode_fields['default_label_field'][] = isset($atts[$f_key.'_label']) && !empty($atts[$f_key.'_label']) ? $atts[$f_key.'_label'] : $f_key;
                $field_value = isset($atts[$f_key.'_value']) && !empty($atts[$f_key.'_value']) ? $atts[$f_key.'_value'] : $f_value ;
                $return_shortcode_fields['default_value_field'][] = $field_value;
            }
        }

        return $return_shortcode_fields;
    }
    public function chack_shortcode_hasaccess( $atts) {
        if (current_user_can('administrator')) {
            return true;
        }

        if(isset($armember_restriction_access) && !$armember_restriction_access) {
            return true;
        }

        if(isset($atts['armember_restriction_access']) && $atts['armember_restriction_access'] == 'no') {
            return true;
        }

        $arm_membership_plans = isset($atts['armember_membership_plans']) && !empty($atts['armember_membership_plans']) ? explode(",", $atts['armember_membership_plans']) : array();
        $arm_restriction_type = isset($atts['armember_access_type']) && !empty($atts['armember_access_type']) ? $atts['armember_access_type'] : '';

        global $arm_restriction;
        $hasaccess = $arm_restriction->arm_check_content_hasaccess( $arm_membership_plans, $arm_restriction_type );

        return $hasaccess;
    }
}?>
