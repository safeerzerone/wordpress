<?php
if (!class_exists('ARM_email_settings'))
{
	class ARM_email_settings
	{
		var $templates;
		var $isOptInsFeature;
		function __construct()
		{
			global $wpdb, $ARMember, $arm_slugs;
			$is_opt_ins_feature = get_option('arm_is_opt_ins_feature', 0);
			$this->isOptInsFeature = ($is_opt_ins_feature == '1') ? true : false;
			
			add_action('wp_ajax_arm_submit_email_template', array($this, 'arm_submit_email_template'));
			add_action('wp_ajax_arm_edit_template_data', array($this, 'arm_edit_template_data'));
			add_action('wp_ajax_arm_update_email_template_status', array($this, 'arm_update_email_template_status'));

			add_action('wp_ajax_arm_update_opt_ins_settings', array($this, 'arm_update_opt_ins_settings'));
			add_action('wp_ajax_arm_get_default_message', array($this, 'arm_automated_email_messages_templates'));
			add_action('wp_ajax_nopriv_arm_get_default_message', array($this, 'arm_automated_email_messages_templates'));
			add_action('wp_ajax_arm_send_test_email',array($this, 'arm_send_test_email_callback'));
			add_action('wp_ajax_arm_reset_email_template_by_id',array($this, 'arm_reset_email_template_by_id_func'));


			$this->templates = new stdClass;
			$this->templates->new_reg_user_admin = 'new-reg-user-admin';
			$this->templates->new_reg_user_with_payment = 'new-reg-user-with-payment';
			$this->templates->new_reg_user_without_payment = 'new-reg-user-without-payment';
			$this->templates->email_verify_user = 'email-verify-user';			
			$this->templates->account_verified_user = 'account-verified-user';
			$this->templates->change_password_user = 'change-password-user';	
			$this->templates->forgot_passowrd_user = 'forgot-passowrd-user';
			$this->templates->profile_updated_user = 'profile-updated-user';
			$this->templates->profile_updated_notification_to_admin = 'profile-updated-notification-admin';
			$this->templates->grace_failed_payment = 'grace-failed-payment';
			$this->templates->grace_eot = 'grace-eot';
			$this->templates->failed_payment_admin = 'failed-payment-admin';
			$this->templates->on_menual_activation = 'on-menual-activation';

			add_filter('arm_pro_email_notification_automated_notification',array($this,'arm_pro_email_notification_automated_notification_func'),10,1);

			add_filter('arm_pro_email_notification_automated_notification_form',array($this,'arm_pro_email_notification_automated_notification_form_func'),10,1);

		}

		function arm_pro_email_notification_automated_notification_form_func($arm_add_new_response_email = ''){
			
			if ( file_exists( MEMBERSHIP_VIEWS_DIR . '/arm_add_edit_template_forms.php' ) ) {
				include MEMBERSHIP_VIEWS_DIR . '/arm_add_edit_template_forms.php';
			}
		}

		function arm_pro_email_notification_automated_notification_func(){
			global $wpdb, $ARMember;
			$messages = $wpdb->get_results("SELECT * FROM `".$ARMember->tbl_arm_auto_message."` ORDER BY `arm_message_id` DESC");
			if (file_exists(MEMBERSHIP_VIEWS_DIR . '/arm_email_templates.php') && !empty($messages)) {
				include( MEMBERSHIP_VIEWS_DIR . '/arm_email_templates.php');
			}
		}

		function arm_get_email_template($temp_slug)
		{
			global $wpdb,$ARMember;
			$res = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `".$ARMember->tbl_arm_email_templates."` WHERE `arm_template_slug`=%s",$temp_slug)); //phpcs:ignore --Reason $ARMember->tbl_arm_email_templates is a table name
			if (!empty($res)) {
				$res->arm_template_subject = isset($res->arm_template_subject) ? stripslashes($res->arm_template_subject) : '';
				$res->arm_template_content = isset($res->arm_template_content) ? stripslashes($res->arm_template_content) : '';

				return $res;
			}
			return false;
		}
		function arm_update_email_settings()
		{
			$arm_email_from_name = isset($_POST['arm_email_from_name']) ? sanitize_text_field($_POST['arm_email_from_name']) : ''; //phpcs:ignore
			$arm_email_from_email = isset($_POST['arm_email_from_email']) ? sanitize_email($_POST['arm_email_from_email']) : ''; //phpcs:ignore
			$arm_email_admin_email = isset($_POST['arm_email_admin_email']) ? sanitize_text_field($_POST['arm_email_admin_email']) : ''; //phpcs:ignore
			$server = isset($_POST['arm_email_server']) ? sanitize_text_field($_POST['arm_email_server']) : 'wordpress_server'; //phpcs:ignore

			$arm_mail_authentication = isset($_POST['arm_mail_authentication']) ? intval($_POST['arm_mail_authentication']) : '0'; //phpcs:ignore
			$smtp_mail_server = isset($_POST['arm_mail_server']) ? sanitize_text_field($_POST['arm_mail_server']) : '';//phpcs:ignore
			$smtp_mail_port = isset($_POST['arm_mail_port']) ? sanitize_text_field($_POST['arm_mail_port']) : '';//phpcs:ignore
			$smtp_mail_login_name = isset($_POST['arm_mail_login_name']) ? sanitize_text_field($_POST['arm_mail_login_name']) : '';//phpcs:ignore
			$smtp_mail_password = isset($_POST['arm_mail_password']) ? $_POST['arm_mail_password'] : ''; //phpcs:ignore
			$smtp_mail_enc = isset($_POST['arm_smtp_enc']) ? sanitize_text_field($_POST['arm_smtp_enc']) : 'none';//phpcs:ignore
			
			/** Google */
			$is_email_verified = isset($_POST['arm_gmail_verified_status']) ? sanitize_text_field( $_POST['arm_gmail_verified_status'] ) : '';//phpcs:ignore
			$old_settings = $this->arm_get_all_email_settings();
			$email_tools = (isset($old_settings['arm_email_tools'])) ? $old_settings['arm_email_tools'] : array();
			$email_tools['aweber']['consumer_key'] = '';
			$email_tools['aweber']['consumer_secret'] = '';
			$email_settings = array(
				'arm_email_from_name' => $arm_email_from_name,
				'arm_email_from_email' => $arm_email_from_email,
                                'arm_email_admin_email' => $arm_email_admin_email,
				'arm_email_server' => $server,
				'arm_mail_server' => $smtp_mail_server,
				'arm_mail_port' => $smtp_mail_port,
				'arm_mail_login_name' => $smtp_mail_login_name,
				'arm_mail_password' => $smtp_mail_password,
				'arm_smtp_enc' => $smtp_mail_enc,
				'arm_email_tools' => $email_tools,
				'arm_mail_authentication' => $arm_mail_authentication,
			);
			if($server == 'google_gmail' && $_POST['arm_gmail_verified_status'] == 1)//phpcs:ignore
			{
				$email_settings['arm_email_server'] = 'google_gmail';
				$email_settings['arm_google_client_id'] = isset($_POST['arm_google_client_id']) ? sanitize_text_field($_POST['arm_google_client_id']) : '';//phpcs:ignore
				$email_settings['arm_google_client_secret'] = isset($_POST['arm_google_client_secret']) ? sanitize_text_field($_POST['arm_google_client_secret']) : '';//phpcs:ignore
				$email_settings['arm_google_auth_url'] = isset($_POST['arm_google_auth_url']) ? sanitize_text_field($_POST['arm_google_auth_url']) : '';//phpcs:ignore
				$email_settings['arm_google_auth_token'] = isset($_POST['arm_google_auth_token']) ? sanitize_text_field($_POST['arm_google_auth_token']) : '';//phpcs:ignore
				$email_settings['arm_gmail_verified_status'] = isset($_POST['arm_gmail_verified_status']) ? intval($_POST['arm_gmail_verified_status']) : 0;//phpcs:ignore
				$email_settings['arm_google_connected_account'] = isset($_POST['arm_google_connected_account']) ? sanitize_text_field($_POST['arm_google_connected_account']) : '';//phpcs:ignore
				$email_settings['arm_google_auth_response'] = isset($_POST['arm_google_auth_response']) ? sanitize_textarea_field($_POST['arm_google_auth_response']) : '';//phpcs:ignore
			}
			update_option('arm_email_settings', $email_settings);
		}
		function arm_update_opt_ins_settings()
		{
			global $ARMember, $arm_capabilities_global;
			$response = array('type' => 'error', 'msg' => esc_html__('There is an error while updating opt-ins settings, please try again.', 'ARMember'));
			$posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST ); //phpcs:ignore
			if (isset($posted_data['action']) && $posted_data['action'] == 'arm_update_opt_ins_settings') {
				$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_general_settings'], '1',1); //phpcs:ignore --Reason:Verifying nonce
				$email_settings = $this->arm_get_all_email_settings();
				$email_tools = (isset($posted_data['arm_email_tools'])) ? $posted_data['arm_email_tools'] : array();
				$old_email_tools = (isset($email_settings['arm_email_tools'])) ? $email_settings['arm_email_tools'] : array();
			
				$email_tools = apply_filters('arm_change_optin_settings_before_save', $email_tools);
				
				$email_settings['arm_email_tools'] = arm_array_map($email_tools);
				update_option('arm_email_settings', $email_settings);
                                
				do_action('arm_update_add_on_opt_in_settings', $_POST);//phpcs:ignore
				$response = array('type' => 'success', 'msg' => esc_html__('Opt-ins Settings Saved Successfully.', 'ARMember'));
			}
			echo arm_pattern_json_encode($response);
			die();
		}	
		function object2array($object) {

		    return @json_decode(@json_encode($object), 1);
		}
		function arm_get_optin_settings()
		{
			global $wpdb, $ARMember;
			$emailTools = array();
			if ($this->isOptInsFeature)
			{
				$email_settings = $this->arm_get_all_email_settings();
				if (isset($email_settings['arm_email_tools']) && !empty($email_settings['arm_email_tools'])) {
					$all_email_tools = $email_settings['arm_email_tools'];
					foreach ($all_email_tools as $tool => $et) {
						if (isset($et['status']) && $et['status'] == '1') {
							$emailTools[$tool] = $et;
						}
					}
				}
				$emailTools = apply_filters('arm_get_optin_settings', $emailTools, $email_settings);
			}
			return $emailTools;
		}
		function arm_get_all_email_settings()
		{
			global $wpdb;
			$email_settings_unser = get_option('arm_email_settings');
			$all_email_settings = maybe_unserialize($email_settings_unser);
			$all_email_settings = apply_filters('arm_get_all_email_settings', $all_email_settings);
			return $all_email_settings;
		}
		function arm_get_single_email_template($template_id, $fields = array())
		{
			global $wpdb, $ARMember;
			if ($template_id == '') {
				return false;
			}
			$select_fields = "*";
			if (is_array($fields) && !empty($fields)) {
				$select_fields = implode(',', $fields);
			}
			$res = $wpdb->get_row( $wpdb->prepare("SELECT $select_fields FROM `".$ARMember->tbl_arm_email_templates."` WHERE  `arm_template_id`=%d",$template_id) );//phpcs:ignore --Reason $ARMember->tbl_arm_email_templates is a table name
			if (!empty($res)) {
				if (!empty($res->arm_template_subject)) {
					$res->arm_template_subject = stripslashes($res->arm_template_subject);
				}
				if (!empty($res->arm_template_content)) {
					$res->arm_template_content = stripslashes($res->arm_template_content);
				}
				return $res;
			}
			return false;
		}
		function arm_get_all_email_template($field = array())
		{
			global $wpdb, $ARMember;
			if (is_array($field) && !empty($field)) {
				$field_name = implode(',', $field);
				$sql = "SELECT " . $field_name . " FROM `".$ARMember->tbl_arm_email_templates."` ORDER BY `arm_template_id` ASC ";
			} else {
				$sql = "SELECT * FROM `".$ARMember->tbl_arm_email_templates."` ORDER BY `arm_template_id` ASC ";
			}
			$results = $wpdb->get_results($sql);//phpcs:ignore --Reason Query is a select without where so no need to prepare
			if (!empty($results->arm_template_subject)) {
				$results->arm_template_subject = stripslashes($results->arm_template_subject);
			}
			if (!empty($results->arm_template_content)) {
				$results->arm_template_content = stripslashes($results->arm_template_content);
			}
			return $results;
		}

		function arm_reset_email_template_by_id_func() {
			global $arm_email_settings, $ARMember, $wpdb,$arm_capabilities_global;
		
			$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_email_notifications'], '1',1); //phpcs:ignore --Reason:Verifying nonce

			$template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;

			if (!$template_id) {
				$response = array('status'=>'error','message' => esc_html__('Template ID missing.','ARMember'));
			}
		
			if (empty($arm_email_settings) || !method_exists($arm_email_settings, 'arm_get_single_email_template')) {
				$response = array('status'=>'error','message' => esc_html__('Email settings unavailable.','ARMember'));
			}
		
			$template = $arm_email_settings->arm_get_single_email_template($template_id);
			if (!$template || empty($template->arm_template_slug)) {
				$response = array('status'=>'error','message' => esc_html__('Template not found.','ARMember'));
			}
		
			$slug = $template->arm_template_slug;
		

			$defaults = $this->arm_default_email_templates();
		
			if (!isset($defaults[$slug])) {
				$response = array('status'=>'error','message' => esc_html__('Default template content not available for this slug.','ARMember'));
			}
			else
			{
				$response = array(
					'status' => 'success',
					'message' => esc_html__('Template reseted to default.','ARMember'),
					'template_subject' => $defaults[$slug]['arm_template_subject'],
					'template_content' => $defaults[$slug]['arm_template_content'],
				);
			}
		
			echo arm_pattern_json_encode($response);
			die;
		}
		

		function arm_edit_template_data()
		{
			global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_global_settings, $arm_email_settings, $arm_manage_coupons, $arm_manage_communication, $arm_capabilities_global;
			$return = array('status' => 'error');
			$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_email_notifications'], '1',1); //phpcs:ignore --Reason:Verifying nonce
			if (isset($_REQUEST['action']) && isset($_REQUEST['temp_id']) && $_REQUEST['temp_id'] != '') {
				$template_id = intval($_REQUEST['temp_id']);
				$temp_detail = $arm_email_settings->arm_get_single_email_template($template_id);
				if (!empty($temp_detail)) {
					$return = array(
						'status' => 'success',
						'id' => $template_id,
						'popup_heading' => esc_html(stripslashes($temp_detail->arm_template_name)),
						'arm_template_slug' => $temp_detail->arm_template_slug,
						'arm_template_subject' => esc_html(stripslashes($temp_detail->arm_template_subject)),
						'arm_template_content' => stripslashes($temp_detail->arm_template_content),
						'arm_template_status' => $temp_detail->arm_template_status,
					);
					$return = apply_filters('arm_email_attachment_file_outside',$return);
				}
			}
			echo arm_pattern_json_encode($return);
			exit;
		}
		function arm_submit_email_template()
		{
			global $wpdb, $ARMember, $arm_capabilities_global;
			$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_email_notifications'], '1',1); //phpcs:ignore --Reason:Verifying nonce
			$response = array('type'=>'error', 'msg'=>esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember'));
			if (!empty($_POST['arm_template_id']) && intval($_POST['arm_template_id']) != 0)//phpcs:ignore
			{
				$template_id = intval($_POST['arm_template_id']);//phpcs:ignore
				$arm_email_template_subject = (!empty($_POST['arm_template_subject'])) ? sanitize_text_field($_POST['arm_template_subject']) : '';//phpcs:ignore
				$arm_email_template_content = (!empty($_POST['arm_template_content'])) ? $_POST['arm_template_content'] : ''; //phpcs:ignore
				$arm_email_template_status = (!empty($_POST['arm_template_status'])) ? intval($_POST['arm_template_status']) : 0;//phpcs:ignore
				$temp_data = array(
					'arm_template_subject' => $arm_email_template_subject,
					'arm_template_content' => $arm_email_template_content,
					'arm_template_status' => $arm_email_template_status
				);
				$temp_data=apply_filters('arm_email_template_save_before',$temp_data,$_POST);//phpcs:ignore
				$update_temp = $wpdb->update($ARMember->tbl_arm_email_templates, $temp_data, array('arm_template_id' => $template_id));
				$response = array('type'=>'success', 'msg'=>esc_html__('Email Template Updated Successfully.', 'ARMember'));
			}
			echo arm_pattern_json_encode($response);
			exit;
		}
		function arm_update_email_template_status($posted_data=array())
		{
			global $wpdb, $ARMember, $arm_capabilities_global;
			$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_email_notifications'], '1',1); //phpcs:ignore --Reason:Verifying nonce
			$response = array('type'=>'error', 'msg'=>esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember'));
			if (!empty($_POST['arm_template_id']) && $_POST['arm_template_id'] != 0  && intval( $_POST['arm_template_id'] ) )//phpcs:ignore
			{
				$template_id = intval($_POST['arm_template_id']);//phpcs:ignore
				$arm_email_template_status = (!empty($_POST['arm_template_status'])) ? intval($_POST['arm_template_status']) : 0;//phpcs:ignore
				$temp_data = array(
					'arm_template_status' => $arm_email_template_status,
				);
				$update_temp = $wpdb->update($ARMember->tbl_arm_email_templates, $temp_data, array('arm_template_id' => $template_id));
				$response = array('type'=>'success', 'msg'=>esc_html__('Email Template Updated Successfully.', 'ARMember'));
			}
			echo arm_pattern_json_encode($response);
			exit;
		}
		function arm_insert_default_email_templates()
		{
			global $wpdb, $ARMember;
			$default_email_template = $this->arm_default_email_templates();
			if (!empty($default_email_template)) {
				foreach ($default_email_template as $slug => $email_template) {
                    $oldTemp = $this->arm_get_email_template($slug);
                    if (!empty($oldTemp)) {
                        continue;
                    } else {
                        $email_template['arm_template_slug'] = $slug;
                        $email_template['arm_template_status'] = '1';
                        $ins = $wpdb->insert($ARMember->tbl_arm_email_templates, $email_template);
                    }
				}
			}
		}
	function arm_default_email_templates()
		{
			$temp_slugs = $this->templates;
			$email_templates = array(
				
$temp_slugs->new_reg_user_admin => array(
    'arm_template_name' => esc_html__('Signup Completed Notification To Admin', 'ARMember'),
    'arm_template_subject' => sprintf('New User Registration on %s- Account Details', '{ARM_BLOGNAME}'),
    'arm_template_content' => sprintf(
            '%sHello Administrator,%sThis message informs you that a new user has successfully registered on %s. Below are the details of the newly registered user:%s Full Name%s Email Address%s First Name%s Last Name%s Username%s To view more detailed information about this user or to take any necessary actions, please click the link below:%s Thank you for being a part of %s',
        '<p>', '</p><p>', '<strong>{ARM_BLOGNAME}</strong>', '</p>
<table>
    <tr>
        <th>','</th>
        <td>{ARM_NAME}</td>
    </tr>
    <tr>
        <th>','</th>
        <td>{ARM_EMAIL}</td>
    </tr>
    <tr>
        <th>','</th>
        <td>{ARM_FIRST_NAME}</td>
    </tr>
    <tr>
        <th>','</th>
        <td>{ARM_LAST_NAME}</td>
    </tr>
    <tr>
        <th>','</th>
        <td>{ARM_USERNAME}</td>
    </tr>
</table>
<p>',
'</p><p>{ARM_PROFILE_LINK}</p> 
<p>', '<strong>{ARM_BLOGNAME}</strong>. </p>' )
),


	$temp_slugs->new_reg_user_with_payment => array(
		'arm_template_name'    => esc_html__('Signup Completed (With Payment) Notification To User', 'ARMember'),
		'arm_template_subject' => sprintf( 'Your Subscription to %s is Complete – Payment Confirmation', '{ARM_BLOGNAME}' ),
		'arm_template_content' => sprintf(  '%s Hello %s Thank you for subscribing to %s. We are pleased to welcome you aboard as a member and appreciate your choice to join us under the %s plan.%s To manage or update your membership information, please visit the following link: %s Below is a summary of your most recent payment details for your reference:%s Date of Payment %s Paid With%s Plan Name%sPayment Mode%s Amount%sTransaction ID%s If you have any questions or require further assistance, please do not hesitate to contact us. We look forward to providing you with an excellent experience at %sBest regards,%sThe %s Team%s',
'<p>',
'{ARM_NAME},</p>
<p>','{ARM_BLOGNAME}','{ARM_BLOGNAME}','</p>
<p>','</p>
<p>{ARM_PROFILE_LINK}</p>
<p>','</p>
<table >
  <tr>
    <th align=”right”><strong>','</strong></th>
    <td>{ARM_PAYMENT_DATE}</td>
  </tr>
  <tr>
    <th align=”right”><strong>','</strong></th>
    <td>{ARM_PAYMENT_GATEWAY}</td>
  </tr>
  <tr>
    <th align=”right”><b>','</b></th>
    <td> {ARM_PLAN}</td>
  </tr>
 <tr>
    <th align=”right”><strong>','</strong></th>
    <td>{ARM_PAYMENT_TYPE}</td>
  </tr> <tr>
    <th align=”right”><strong>','</strong></th>
    <td>{ARM_PLAN_AMOUNT}</td>
  </tr>
<th align=”right”><strong>','</strong></th>
    <td>{ARM_TRANSACTION_ID}</td>
  </tr>
</table>
<p>','{ARM_BLOGNAME}. </p>
<p>','</p>
<p>','{ARM_BLOGNAME}','</p>')
),
		
$temp_slugs->new_reg_user_without_payment => array(
	'arm_template_name'    => esc_html__('Signup Completed (Without Payment) Notification To User', 'ARMember'),
	'arm_template_subject' => sprintf('Welcome to %s – Membership Confirmation', '{ARM_BLOGNAME}'),
	'arm_template_content' => sprintf(
		'%s Hello %s We are happy to welcome you as you are our valued member! Thank you for registering at %s You can review and manage your membership details. Please click the provided link:%s If you have any queries, you can contact us! We look forward to serving you.%s Best Regards,%sThe %s Team %s',
'<p>',
'{ARM_NAME},</p><p>',
'{ARM_BLOGNAME}.</p><p>',
'</p><p>{ARM_PROFILE_LINK}</p>',
'</p><p>',
'</p><p>',
'{ARM_BLOGNAME}',
'</p>')
	),
		
	
$temp_slugs->email_verify_user => array(
'arm_template_name'    => esc_html__('Email Verification', 'ARMember'),
'arm_template_subject' => sprintf('Email Confirmation Required to Activate Your Account'),
'arm_template_content' => sprintf(
	'%sDear %s Thank you for registering with %s. To complete the sign-up process and gain access to your account, we kindly request that you confirm your email address.%s Please click on the link below to validate and activate your account:%s If you did not sign up for this account, please disregard this email.%s If you need any assistance or encounter issues, feel free to reach out to us. We’ll be happy to help!%s Best regards,%s The %s Team%s',
'<p>',
'{ARM_NAME},</p><p>',
'{ARM_BLOGNAME}',
'</p><p>',
'</p><p>{ARM_VALIDATE_URL}</p><p>',
'</p><p>',
'</p><p>',
'<br>',
'{ARM_BLOGNAME}',
'</p>')
),
			
$temp_slugs->account_verified_user => array(
    'arm_template_name'    => esc_html__('Email Verified', 'ARMember'),
    'arm_template_subject' => sprintf('Your Account is Now Verified!'),
    'arm_template_content' => sprintf("%sHi %s We're excited to let you know that your account has been successfully verified at %s You can log in to the site with the:  %s Page: %s  Username: %sPassword: (Set while signing up at the site)%sIf you have any questions or need assistance, feel free to reach out.%sThanks for being part of our community, and have a wonderful day!%sBest regards,%sThe %s Team%s",
'<p>',
'{ARM_NAME},</p><p>',
'{ARM_BLOGNAME}!</p><p>',
'</p>', '{ARM_LOGIN_URL}', '<p>',
'{ARM_USERNAME}<br>',
'</p><p>',
'</p><p>',
'</p><p>',
'</p><p>',
'<br>',
'{ARM_BLOGNAME}',
'</p>')
),

$temp_slugs->change_password_user => array(
	'arm_template_name'    => esc_html__('Change Password', 'ARMember'),
	'arm_template_subject' => sprintf('Your Password Has Been Successfully Changed'),
'arm_template_content' => sprintf("%sHi %s To your request for a password reset through your account, your password has been successfully updated. To access your account, please log in using the following link:%s With your username: %s Password: %s(Newly set password)%s If you did not request this change or believe this was made in error, please contact us immediately for assistance.%sThank you, and have a great day!%sBest regards,%sThe %s Team %s",
'<p>','<strong>{ARM_NAME}</strong>,</p>
<p>','</p>
<p>{ARM_LOGIN_URL}</p>
<p>','<strong>{ARM_USERNAME}</strong></p>
<p>','<strong>','</strong></p>
<p>','</p>
<p>','</p>
<p>','</p>
<p>','{ARM_BLOGNAME}','</p>')
),
$temp_slugs->forgot_passowrd_user => array(
	'arm_template_name'    => esc_html__('Forgot Password', 'ARMember'),
	'arm_template_subject' => sprintf('Password Reset Request for %s', '{ARM_BLOGNAME}'),
	'arm_template_content' => sprintf("%sHi %sWe received a request to reset the password for your account:%sUsername: %sEmail: %sIf you did not make this request, you can safely ignore this email, and no changes will be made to your account.%s To proceed with resetting your password, please click the link below:%sIf you encounter any issues or have questions, feel free to reach out to us at %s Thanks,%sThe %s Team%s",
'<p>',
'<strong>{ARM_NAME}</strong>,</p><p>',
'</p><p>',
'<strong>{ARM_USERNAME}</strong><br>',
'<strong>{ARM_EMAIL}</strong></p>
<p>','</p><p>',
'</p> <p>{ARM_RESET_PASSWORD_LINK}</p>
<p>','{ARM_ADMIN_EMAIL}.</p> 
<p>','</p><p>', '{ARM_BLOGNAME}','</p>')
),
				

$temp_slugs->profile_updated_user => array(
	'arm_template_name'    => esc_html__('Profile Updated', 'ARMember'),
	'arm_template_subject' => sprintf('Your Account Details Have Been Updated Successfully!'),
	'arm_template_content' => sprintf("%sHello %sWe're happy to inform you that your account details have been successfully updated! To review and manage your profile, click the link below:%sIf you have any further queries on the updated information, kindly let us know through %sThank you for being a valued part of our community. Have a fantastic day ahead!%sWarm regards,%sThe %s Team%s", 
'<p>',
'<strong>{ARM_NAME}</strong>,</p>
<p>','</p><p>{ARM_PROFILE_LINK}</p>
<p>','{ARM_ADMIN_EMAIL}.</p>
<p>','</p>
<p>','</p>
<p>','{ARM_BLOGNAME}','</p>')
),
				
 
$temp_slugs->profile_updated_notification_to_admin => array(
    'arm_template_name' => esc_html__('Profile Updated Notification To Admin', 'ARMember'),
    'arm_template_subject' => sprintf( '%s\'s Account Details Updated on %s',  '{ARM_USERNAME}', '{ARM_BLOGNAME}'),
    'arm_template_content' => sprintf(
            '%sDear Administrator,%s We would like to inform you that the account of %s has been successfully updated on %s. Below are a few basic details of the member. Kindly review their account and check out the updated details:%s Username%s Email%s First Name%s Last Name%s Thank you for your attention to this matter.',
        '<p>', '</p><p>', '<strong>{ARM_USERNAME}</strong>', '<strong>{ARM_BLOGNAME}</strong>', '</p>
<table>
    <tr>
        <th>', '</th>
        <td>{ARM_USERNAME}</td>
    </tr>
    <tr>
        <th>', '</th>
        <td>{ARM_EMAIL}</td>
    </tr>
    <tr>
        <th>', '</th>
        <td>{ARM_FIRST_NAME}</td>
    </tr>
    <tr>
        <th>', '</th>
        <td>{ARM_LAST_NAME}</td>
    </tr>
</table>
<p>'),
),

				
$temp_slugs->grace_failed_payment = array(
	'arm_template_name' => esc_html__('Grace Period For Failed Payment', 'ARMember'),
	'arm_template_subject' => sprintf('Action Required for your Failed Payment at %s', '{ARM_BLOGNAME}'),
	'arm_template_content' => sprintf(
			'%sDear %s We wanted to inform you that there was an issue processing your recurring payment for your %s subscription at %s. Here are the details of the payment attempt:%s Payment Method%s Amount%sTransaction ID%sWe recommend reaching out to your payment service provider to resolve the issue. Please be aware that if no action is taken within the next %s days, your current membership may lapse.%s If you have any questions or need assistance, don’t hesitate to contact us at %s. We’re happy to help!%s Thank you, and have a great day!%s Best regards,%sThe %s Team',
		'<p>', '{ARM_NAME},</p><p>',
		'{ARM_PLAN}', '{ARM_BLOGNAME}', '</p>
<table>
	<tr>
		<th>', '</th>
		<td>{ARM_PAYMENT_GATEWAY}</td>
	</tr>
	<tr>
		<th>', '</th>
		<td>{ARM_PLAN_AMOUNT}</td>
	</tr>
	<tr>
		<th>', '</th>
		<td>{ARM_TRANSACTION_ID}</td>
	</tr>
</table>
<p>',
		'{ARM_GRACE_PERIOD_DAYS}', '</p><p>',
		'{ARM_BLOGNAME}', '</p><p>',
		'</p><p>', '</p><p>', '{ARM_BLOGNAME}')
),

$temp_slugs->grace_eot => array(
	'arm_template_name' => esc_html__('User Enters Grace Period Notification', 'ARMember'),
	'arm_template_subject' => sprintf('Reminder for membership expiration at %s', '{ARM_BLOGNAME}'),
	'arm_template_content' => sprintf(
			'%sHi %s We wanted to let you know that your %s membership has just expired at  However, you can still access our website without any issues for %s days.%s If you\'d like to renew or update your membership plan, please click the link below:%s Note: %s If you do not renew or change your membership within %s days, the system will automatically take the necessary action.%s Have a great day!%s Best regards,%s The %s Team',
'<p>', '{ARM_NAME},</p>
<p>','{ARM_PLAN}', '{ARM_BLOGNAME}.</p>
<p>','{ARM_GRACE_PERIOD_DAYS}', '</p>
<p>','</p> 
<p>{ARM_BLOG_URL}</p> 
<p><strong>','</strong> ','{ARM_GRACE_PERIOD_DAYS}', '</p>
<p>','</p>
<p>', '</p>
<p>', '{ARM_BLOGNAME}')
),
$temp_slugs->failed_payment_admin => array(
	'arm_template_name' => esc_html__('Failed Payment Notification To Admin', 'ARMember'),
	'arm_template_subject' => sprintf( esc_html__('Failed Payment for %s Membership - Action Required', 'ARMember'),
				'{ARM_PLAN}'),
	'arm_template_content' => sprintf(
			'%sHello Administrator,%s This is a reminder that the recurring payment for %s membership for the following member named as %s has failed at %s Here are the Member Details:%s Username%s Email%s Payment Method%s Amount%s Please check whether the necessary actions have been taken in the system.%s Best regards,%s Team%s',
'<p>', '</p>
<p>','{ARM_PLAN}', '{ARM_NAME}', '{ARM_BLOGNAME}:</p>
<p>','</p>
<table>
	<tr>
		<th><strong>', '</strong></th>
		<td>{ARM_USERNAME}</td>
	</tr>
	<tr>
		<th><strong>','</strong></th>
		<td>{ARM_EMAIL}</td>
	</tr>
	<tr>
		<th><strong>', '</strong></th>
		<td>{ARM_PAYMENT_GATEWAY}</td>
	</tr> 
	<tr>
		<th><strong>','</strong></th>
		<td>{ARM_PLAN_AMOUNT}</td>
	</tr>
</table>
<p>','</p> 
<p>', '<br>{ARM_BLOGNAME}','</p>')
),

$temp_slugs->on_menual_activation => array(
	'arm_template_name' => esc_html__('Manual User Activation', 'ARMember'),
	'arm_template_subject' => sprintf( 'Your account has been activated at %s', '{ARM_BLOGNAME}'),
	'arm_template_content' => sprintf(
			'%sHello %s We are pleased to inform you that your account has been successfully activated. Welcome aboard! To get started, please click on the following link: %s Review and take a tour at %s, we hope you have a smooth experience with us!%s Have a great day!%s Best regards,%sTeam %s',
'<p>', '{ARM_NAME},</p> 
<p>','<a href="{ARM_LOGIN_URL}">{ARM_LOGIN_URL}</a>.</p>
<p>','{ARM_BLOGNAME}', '</p>
<p>','</p>
<p>','</p>
<p>','<br> {ARM_BLOGNAME}','</p>')
),				
	);
	$email_templates = apply_filters('arm_default_email_templates', $email_templates);
	return $email_templates;
}
				
				function arm_automated_email_messages_templates() {
					global $wpdb, $ARMember, $arm_capabilities_global;
					$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_email_notifications'], '1',1); //phpcs:ignore --Reason:Verifying nonce
					$message_type = sanitize_text_field($_POST['message_type'] ?? '');
					$temp_slugs = $this->templates;
					$temp_slugs = array(
						'on_new_subscription' => 'on_new_subscription',
						'on_menual_activation' => 'on_menual_activation',
						'on_change_subscription' => 'on_change_subscription',
						'on_renew_subscription' => 'on_renew_subscription',
						'on_failed' => 'on_failed',
						'on_next_payment_failed' => 'on_next_payment_failed',
						'trial_finished' => 'trial_finished',
						'on_expire' => 'on_expire',
						'before_expire' => 'before_expire',
						'manual_subscription_reminder' => 'manual_subscription_reminder',
						'automatic_subscription_reminder' => 'automatic_subscription_reminder',
						'on_change_subscription_by_admin' => 'on_change_subscription_by_admin',
						'before_dripped_content_available' => 'before_dripped_content_available',
						'on_cancel_subscription' => 'on_cancel_subscription',
						'on_recurring_subscription' => 'on_recurring_subscription',
						'on_close_account' => 'on_close_account',
						'on_login_account' => 'on_login_account',
						'on_new_subscription_post' => 'on_new_subscription_post',
						'on_recurring_subscription_post' => 'on_recurring_subscription_post',
						'on_renew_subscription_post' => 'on_renew_subscription_post',
						'on_cancel_subscription_post' => 'on_cancel_subscription_post',
						'before_expire_post' => 'before_expire_post',
						'on_expire_post' => 'on_expire_post',
						'on_purchase_subscription_bank_transfer' => 'on_purchase_subscription_bank_transfer',
					);
					
					$message_templates = array(
				
$temp_slugs['on_new_subscription'] => array(
				'arm_template_subject' => sprintf(
					'Welcome Aboard! Here Are Your Subscription Details for %s', 
					'{ARM_MESSAGE_BLOGNAME}'
				),
'arm_template_content' => sprintf(
	"%sHello %sWe're happy to have you on board at %s. Thank you for subscribing to %s. Below we've shared the basic details of your plan and request that you go through all the details:%sPlan Name%sPlan Price%sPlan Next Due Date%sPlan Expiration%sTransaction ID%sPayment Gateway Used%sPrice Payment Date%s Please take a moment to review this information. If you have any further queries, then feel free to reach out to us at - %sThanks,%s", 
	'<p>','{ARM_MESSAGE_USERDISPLAYNAME},</p>
<p>','{ARM_MESSAGE_BLOGNAME}','{ARM_MESSAGE_SUBSCRIPTIONNAME}','</p>
<table>
  <tr>
    <th><strong>','</strong></th>
    <td>{ARM_MESSAGE_SUBSCRIPTIONNAME}</td>
  </tr>
  <tr>
    <th><strong>','</strong></th>
    <td>{ARM_MESSAGE_SUBSCRIPTION_AMOUNT}</td>
  </tr>
  <tr>
    <th><strong>','</strong></th>
    <td>{ARM_MESSAGE_SUBSCRIPTION_NEXT_DUE}</td>
  </tr> 
 <tr>
    <th><strong>','</strong></th>
    <td>{ARM_MESSAGE_SUBSCRIPTION_EXPIRE}</td>
  </tr>
 <tr>
    <th><strong>','</strong></th>
    <td>{ARM_MESSAGE_TRANSACTION_ID}</td>
  </tr> <tr>
    <th><strong>','</strong></th>
    <td> {ARM_MESSAGE_PAYMENT_GATEWAY}</td>
  </tr> <tr>
    <th><strong>','</strong></th>
    <td> {ARM_MESSAGE_PAYMENT_DATE}</td>
  </tr>
 </table>
<p>','{ARM_MESSAGE_ADMIN_EMAIL}.</p>
<p>','<br>{ARM_BLOGNAME} Team</p>'
)
),
							
$temp_slugs['on_menual_activation'] => array(
	'arm_template_subject' => sprintf( 'On Manual User Activation at %s',  '{ARM_BLOGNAME}' ),
	'arm_template_content' => sprintf(
		'Content for On Manual User Activation', 
	)
),
				
$temp_slugs['on_change_subscription'] => array(
	'arm_template_subject' => sprintf( 'Success! Your Plan is Now Changed to %s - Full Details', '{ARM_MESSAGE_SUBSCRIPTIONNAME}'),
	'arm_template_content' => sprintf( '%sHello %s We are pleased to inform you that your membership plan has been successfully changed to %s. Please find the details of your new plan below:%s Plan Name%s Plan Price%s Plan Next Due Date%s Plan Expiration%s Payment Date%s We encourage you to review these details carefully. If you have any questions, please don’t hesitate to reach out to us at %s Thank you for being a valued member of %s Best regards,%s The %s Team%s', 
'<p>', '{ARM_MESSAGE_USERDISPLAYNAME}</p>
<p>', '{ARM_MESSAGE_SUBSCRIPTIONNAME}', '</p>
<table>
	<tr>
		<th><strong>','</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTIONNAME}</td>
	</tr>
	<tr>
		<th><strong>','</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTION_AMOUNT}</td>
	</tr>
	<tr>
		<th><strong>','</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTION_NEXT_DUE}</td>
	</tr>
	<tr>
		<th><strong>','</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTION_EXPIRE}</td>
	</tr>
	<tr>
		<th><strong>','</strong></th>
		<td>{ARM_MESSAGE_PAYMENT_DATE}</td>
	</tr>
</table>
<p>','{ARM_MESSAGE_ADMIN_EMAIL}.</p>
<p>','{ARM_MESSAGE_BLOGNAME}.</p>
<p>','</p>
<p>', '{ARM_MESSAGE_BLOGNAME}', '</p>')
),


						
$temp_slugs['on_renew_subscription'] => array(
	'arm_template_subject' => sprintf( 'Your Subscription is Renewed Successfully! Enjoy Uninterrupted
	Access Today!'),
	'arm_template_content' => sprintf(
		'%sHello %s We are pleased to inform you that your subscription to %s has been successfully renewed. Below are the details of your renewed plan:%s Plan Name%s Plan Price%s Plan Expiration%s Payment Date%s Your continued membership allows us to provide you with the best content and services. We appreciate your ongoing support! If you have any questions regarding your renewal, please don’t hesitate to contact us at %s Thank you for being a valued member of %s Best regards,%s Team %s',
		'<p>', '{ARM_MESSAGE_USERDISPLAYNAME}</p>
<p>', '{ARM_MESSAGE_SUBSCRIPTIONNAME}', '</p>
<table>
	<tr>
		<th><strong>', '</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTIONNAME}</td>
	</tr>
	<tr>
		<th><strong>', '</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTION_AMOUNT}</td>
	</tr>
	<tr>
		<th><strong>', '</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTION_EXPIRE}</td>
	</tr>
	<tr>
		<th><strong>', '</strong></th>
		<td>{ARM_MESSAGE_PAYMENT_DATE}</td>
	</tr>
</table>
<p>', '{ARM_MESSAGE_ADMIN_EMAIL}.</p>
<p>', '{ARM_MESSAGE_BLOGNAME}.</p>
<p>', '</p>
<p>', '{ARM_MESSAGE_BLOGNAME}</p>'
	)
),
				
$temp_slugs['on_failed'] => array(
	'arm_template_subject' => sprintf('Payment Didn’t Go Through - Action Required for Your Membership'),
	'arm_template_content' => sprintf(
		'%sHi %s,We wanted to let you know that we had trouble processing your recent payment for your membership with %s. As a result, your membership hasn’t been renewed yet.%s You can do that easily by logging into your account here: %s and reviewing the details of your plan. %sIf you need help or have any questions, feel free to contact us at %s. We’re happy to assist you! %s Thanks,%s Team%s',
'<p>', '{ARM_MESSAGE_USERDISPLAYNAME}</p>
<p>', '{ARM_MESSAGE_BLOGNAME}', '</p>
<p>' ,'{ARM_MESSAGE_LOGIN_URL}',  '</p>
<p>' , '{ARM_MESSAGE_ADMIN_EMAIL}','</p>
<p>', '<br>{ARM_MESSAGE_BLOGNAME}', '</p>')
),
							
$temp_slugs['on_next_payment_failed'] => array(
	'arm_template_subject' => sprintf(
		'Payment Failed - Action Needed to Restore Your Access to %s',  '{ARM_MESSAGE_BLOGNAME}'),
	'arm_template_content' => sprintf(
		"%sHello %s,This message is to inform you that your payment has failed, as you have not made a payment at %s. To enjoy continuous access to the plan, log in here: %s Make a payment and get instan access to your plan.%s If you have a query, then feel free to contact us at %s. We're here to help!%s Thanks,%s Team%s", 
'<p>', '{ARM_MESSAGE_USERDISPLAYNAME}</p>
<p>', '{ARM_MESSAGE_BLOGNAME}', '{ARM_MESSAGE_LOGIN_URL}</p>
<p>','</p>
<p>','{ARM_MESSAGE_ADMIN_EMAIL}', '</p>
<p>','<br>{ARM_MESSAGE_BLOGNAME}', '</p>')
),
				
$temp_slugs['trial_finished'] => array(
	'arm_template_subject' => sprintf('Your Trial Period at %s Has Ended!', '{ARM_MESSAGE_BLOGNAME}'),
	'arm_template_content' => sprintf(
		'%sDear %s We’re excited to let you know that your trial period at %s has now been completed! We hope you’ve enjoyed exploring all that we offer and would love to have you continue with us.%sTo review your account and explore your options moving forward, please log in at: %s%sIf you have any questions or need assistance, feel free to reach out to us at %s. We’re here to help!%sThank you for being a part of the %s community. We look forward to having you with us!%sBest regards,%s Team%s',
'<p>', '{ARM_MESSAGE_USERDISPLAYNAME}</p>
<p>','{ARM_MESSAGE_BLOGNAME}', '</p>
<p>','{ARM_MESSAGE_LOGIN_URL}', '</p>
<p>','{ARM_MESSAGE_ADMIN_EMAIL}', '</p>
<p>','{ARM_MESSAGE_BLOGNAME}', '</p>
<p>','<br> {ARM_MESSAGE_BLOGNAME}', '</p>')
),

$temp_slugs['on_expire'] => array(
	'arm_template_subject' => sprintf('Your Membership Has Expired – Renew to Continue Enjoying Benefits!'),
	'arm_template_content' => sprintf(
		'%sHello %s We wanted to let you know that your membership with %s has expired as of %s. We hope you’ve enjoyed the benefits of being a member with us!%sTo continue accessing all of our premium features and content, simply renew your membership. You can easily do so by logging into your account here: %s If you have any questions or need assistance with your renewal, feel free to reach out to us at %s. We’re more than happy to assist you!%sThank you for being a valued member of %s. We’d love to have you back with us soon!%sBest regards,%s Team%s',
'<p>', '{ARM_MESSAGE_USERDISPLAYNAME},</p>
<p>','{ARM_MESSAGE_BLOGNAME}', '{ARM_MESSAGE_SUBSCRIPTION_EXPIRE}', '</p>
<p>','{ARM_MESSAGE_LOGIN_URL}</p>
<p>','{ARM_MESSAGE_ADMIN_EMAIL}', '</p>
<p>','{ARM_MESSAGE_BLOGNAME}', '</p>
<p>','<br>{ARM_MESSAGE_BLOGNAME}', '</p>')
),
											
$temp_slugs['before_expire'] => array(
	'arm_template_subject' => sprintf(
		'Your Membership is About to Expire – Renew Now for Uninterrupted Access!'),
	'arm_template_content' => sprintf(
		'%sHello %s We hope you’ve been enjoying your experience with %s! We wanted to remind you that your current subscription plan is set to expire soon.%sPlan Name:%s Expiration Date:%s To continue enjoying all the benefits and features of your subscription without any interruptions, be sure to renew before your plan expires. You can easily renew your subscription by logging into your account here: %s%sIf you have any questions or need assistance with your renewal, feel free to reach out to us at %s. We’re always happy to help!%sThank you for being a valued member of %s. We look forward to continuing to serve you!%sBest regards,%s Team%s',
'<p>', '{ARM_MESSAGE_USERDISPLAYNAME},</p>
<p>','{ARM_MESSAGE_BLOGNAME}', '</p>
	<ul>
		<li><strong>','</strong> {ARM_MESSAGE_SUBSCRIPTIONNAME} </li>
		<li><strong>','</strong> {ARM_MESSAGE_SUBSCRIPTION_EXPIRE} </li>
	</ul>
<p>','{ARM_MESSAGE_LOGIN_URL}', '</p>
<p>','{ARM_MESSAGE_ADMIN_EMAIL}', '</p>
<p>','{ARM_MESSAGE_BLOGNAME}', '</p>
<p>','<br>{ARM_MESSAGE_BLOGNAME}', '</p>')
),
					
$temp_slugs['manual_subscription_reminder'] => array(
	'arm_template_subject' => sprintf(
		'Your Upcoming Semi-Automatic Subscription Payment'
	),
	'arm_template_content' => sprintf(
		'%sHello %s We wanted to give you a quick reminder that your semi-automatic subscription payment for %s is due soon.%sAmount Due:%s Next Payment Due Date:%s As your subscription operates on a semi-automatic renewal basis, please ensure that your payment details are up-to-date to avoid any service disruptions. You can update your payment information or review your subscription status by logging into your account here: %sIf you have any questions or need assistance with your payment, please don’t hesitate to reach out to us at %s. We’re happy to assist you!%sThank you for being a valued member of %s. We appreciate your continued support and look forward to serving you!%sBest regards,%s Team%s',
'<p>', '{ARM_MESSAGE_USERDISPLAYNAME},</p>
<p>','{ARM_MESSAGE_SUBSCRIPTIONNAME}', '</p>
	<ul>
		<li><strong>','</strong> {ARM_MESSAGE_SUBSCRIPTION_AMOUNT}</li>
		<li><strong>','</strong> {ARM_MESSAGE_SUBSCRIPTION_NEXT_DUE}</li>
	</ul>
<p>','{ARM_MESSAGE_LOGIN_URL}.</p>
<p>','{ARM_MESSAGE_ADMIN_EMAIL}', '</p>
<p>','{ARM_MESSAGE_BLOGNAME}', '</p>
<p>','<br>{ARM_MESSAGE_BLOGNAME}', '</p>'
)
),
					
$temp_slugs['automatic_subscription_reminder'] => array(
	'arm_template_subject' => sprintf('Automatic Subscription Payment Due'),
	'arm_template_content' => sprintf(
		'%sHello %s We wanted to remind you that your automatic subscription payment for %s is scheduled soon.%sAmount Due:%s Next Payment Due Date:%s As your payment will be processed automatically, please ensure that your payment details are up-to-date to avoid any interruptions to your subscription. You can review and manage your payment information by logging into your account here: %sIf you have any questions or need assistance with your subscription, feel free to reach out to us at %s. We’re here to help!%sThank you for choosing %s. We appreciate your continued support and look forward to serving you!%sBest regards,%s Team%s', 
'<p>', '{ARM_MESSAGE_USERDISPLAYNAME},</p>
<p>','{ARM_MESSAGE_SUBSCRIPTIONNAME}', '</p>
	<ul>
		<li><strong>','</strong> {ARM_MESSAGE_SUBSCRIPTION_AMOUNT}</li>
		<li><strong>','</strong> {ARM_MESSAGE_SUBSCRIPTION_NEXT_DUE}</li>
	</ul>
<p>','{ARM_MESSAGE_LOGIN_URL}</p>
<p>','{ARM_MESSAGE_ADMIN_EMAIL}', '</p>
<p>','{ARM_MESSAGE_BLOGNAME}', '</p>
<p>','<br>{ARM_MESSAGE_BLOGNAME}', '</p>'
)
),

$temp_slugs['on_change_subscription_by_admin'] => array(
    'arm_template_subject' => sprintf(
        'Notification of Subscription Change - %s',
        '{ARM_MESSAGE_BLOGNAME}'
    ),
    'arm_template_content' => sprintf(
        '%sHello %s We would like to inform you that your subscription has been updated by our team. Kindly log in from here: %s to review the action taken. Below are the details of your updated plan:%s New Plan%s Plan Price%s Next Cycle Date%s Plan Expiration%s Payment Date%s Please take a moment to review these changes. If you did not request this change or if you have any concerns, kindly reach out to us immediately at %s so we can assist you further. Thank you for being a valued member of %s Best regards, The %s Team%s',
'<p>', '{ARM_MESSAGE_USERDISPLAYNAME}</p>
<p>', '{ARM_MESSAGE_LOGIN_URL}', '</p>
<table>
    <tr>
        <th><strong>', '</strong></th>
        <td>{ARM_MESSAGE_SUBSCRIPTIONNAME}</td>
    </tr>
    <tr>
        <th><strong>', '</strong></th>
        <td>{ARM_MESSAGE_SUBSCRIPTION_AMOUNT}</td>
    </tr>
    <tr>
        <th><strong>', '</strong></th>
        <td>{ARM_MESSAGE_SUBSCRIPTION_NEXT_DUE}</td>
    </tr>
    <tr>
        <th><strong>', '</strong></th>
        <td>{ARM_MESSAGE_SUBSCRIPTION_EXPIRE}</td>
    </tr>
    <tr>
        <th><strong>', '</strong></th>
        <td>{ARM_MESSAGE_PAYMENT_DATE}</td>
    </tr>
</table>
<p>', '{ARM_MESSAGE_ADMIN_EMAIL}','</p>
<p>', '{ARM_MESSAGE_BLOGNAME}</p>
<p>', '{ARM_MESSAGE_BLOGNAME}</p>')
	),

					         
$temp_slugs['before_dripped_content_available'] => array(
        'arm_template_subject' => sprintf('Exclusive Content Coming Soon - Don’t Miss Out!'),
        'arm_template_content' => sprintf(
            '%sHi %s We hope this message finds you well! We’re excited to let you know that something exclusive is dropping soon at %s - and we don’t want you to miss it!%sAs one of our valued viewers, you can access this special content. Be sure to check it out using this link: %sThank you for being such an important part of the %s community. We appreciate you!%sBest regards,%s Team%s', 
'<p>', '{ARM_MESSAGE_USERDISPLAYNAME},</p>
<p>','{ARM_MESSAGE_BLOGNAME}','</p>
<p>','{ARM_MESSAGE_DRIP_CONTENT_URL}</p>
<p>','{ARM_MESSAGE_BLOGNAME}','</p>
<p>','<br>{ARM_MESSAGE_BLOGNAME}','</p>'
)
),
$temp_slugs['on_cancel_subscription'] => array(
	'arm_template_subject' => sprintf(
		'Your Membership Has Been Cancelled - %s',
		'{ARM_MESSAGE_BLOGNAME}'
	),
	'arm_template_content' => sprintf(
		'%sHello %s We regret to inform you that your membership with %s has been successfully cancelled.%sIf you believe this cancellation was made in error or want to re-purchase your membership plan, please contact us at %s. We’re happy to assist you and ensure everything is in order.%sWe value your time with us, and if you ever wish to return, we’d be more than happy to welcome you back.%sThank you,%s Team%s', 
		'<p>', '{ARM_MESSAGE_USERDISPLAYNAME},</p>
		<p>', '{ARM_MESSAGE_BLOGNAME}', '</p>
		<p>', '{ARM_MESSAGE_ADMIN_EMAIL}', '</p>
		<p>','</p>
		<p>', '<br>{ARM_MESSAGE_BLOGNAME}', '</p>'
	)
),
					
$temp_slugs['on_recurring_subscription'] => array(
	'arm_template_subject' => sprintf('Your Subscription Payment Confirmation and Details - %s', '{ARM_MESSAGE_BLOGNAME}'),
	'arm_template_content' => sprintf('%sHello %s We’re pleased to inform you that your payment for the subscription plan has been successfully processed. Below are the details of your recent subscription payment:%s Plan Name%s Amount Charged%s Next Payment Due Date%s Plan Expiration Date%s You can review and manage your subscription anytime by logging into your account here: %s If you have any questions or need assistance, feel free to contact us at %s – we’re here to help!%s Thank you for choosing %s. We appreciate your continued support!%s Best regards,%s Team %s',
'<p>','{ARM_MESSAGE_USERDISPLAYNAME},</p>
<p>','</p>
<table>
	<tr>
		<th><strong>', '</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTIONNAME}</td>
	</tr>
	<tr>
		<th><strong>', '</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTION_AMOUNT}</td>
	</tr>
	<tr>
		<th><strong>', '</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTION_NEXT_DUE}</td>
	</tr>
	<tr>
		<th><strong>', '</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTION_EXPIRE}</td>
	</tr>
</table>
<p>', '{ARM_MESSAGE_LOGIN_URL}.</p>
<p>', '{ARM_MESSAGE_ADMIN_EMAIL}','</p>
<p>', '{ARM_MESSAGE_BLOGNAME}','</p>
<p>','<br>','{ARM_MESSAGE_BLOGNAME}</p>')
),
					
					
$temp_slugs['on_close_account'] => array(
	'arm_template_subject' => sprintf('Your Account Has Been Successfully Closed'),
	'arm_template_content' => sprintf(
		'%sHello %sWe wanted to confirm that your account with %s has been successfully closed. We’re sorry to see you go, but we respect your decision.%sHere are the details regarding the closure:%s Active Subscriptions:%s All active subscriptions have been canceled, and no further payments will be processed.Data Deletion:%s As per our policy, your personal information has been securely removed from our system. %sIf you change your mind in the future and would like to reopen your account, or if you need assistance with anything else, feel free to reach out to us at %s. We’d be happy to assist you and welcome you back.%s
Thank you for your time with us, and we wish you all the best in your future!%s
Best regards,%s Team%s', 
'<p>',  '{ARM_MESSAGE_USERDISPLAYNAME},</p>
<p>', '{ARM_MESSAGE_BLOGNAME}', '</p>
<p>', '</p>
<ul>
	<li><strong>', '</strong>', '</li>
	<li><strong>', '</strong>', '</li>
</ul>
<p>', '{ARM_MESSAGE_ADMIN_EMAIL}', '</p>
<p>', '</p>
<p>', '<br>{ARM_MESSAGE_BLOGNAME}','</p>'
	)
),
					
$temp_slugs['on_login_account'] => array(
	'arm_template_subject' => sprintf( 'Welcome Back,%s!', '{ARM_MESSAGE_USERDISPLAYNAME}' ),
	'arm_template_content' => sprintf(
		'%sHello %sWelcome back to {ARM_MESSAGE_BLOGNAME}! We’re glad to see you’ve successfully logged into your account. Here’s a quick summary of what you can do next:%sView Your Subscriptions: Check your active subscriptions, payment details, and billing history.%sUpdate Your Profile: Update your personal information, preferences, and settings to ensure a smooth experience.%sExplore New Features: We’ve introduced new features/products. Feel free to explore what’s new!%sIf you have any questions or need assistance with anything, don’t hesitate to contact us at %s. We’re always here to help!%sThanks for being a part of %sBest regards,%s Team', 		
'<p>','{ARM_MESSAGE_USERDISPLAYNAME},</p>
<p>','</p>
<ul>
<li>','</li>
<li>','</li>
<li>','</li>
</ul>
<p>','{ARM_MESSAGE_ADMIN_EMAIL}','</p>
<p>','{ARM_MESSAGE_BLOGNAME}!</p>
<p>','<br>
{ARM_MESSAGE_BLOGNAME}','</p>'
	)
),

$temp_slugs['on_new_subscription_post'] => array(
	'arm_template_subject' => sprintf('Your Paid Post Purchase with %s - Confirmation and Details Inside!', '{ARM_MESSAGE_BLOGNAME}'),
	'arm_template_content' => sprintf('%sHello %s We’re excited to confirm your recent purchase of a paid post with %s! Thank you for purchasing from us.%s Here’s a quick overview of your purchase details:%s Post Title%s Purchase Date%s Payment Type%s Total Cost%s We want to ensure you have a great time at %s! If you have any additional information or special requests, please don’t hesitate to reach out at %s Best regards,%s Team %s',
'<p>','{ARM_MESSAGE_USERDISPLAYNAME},</p>
<p>', '{ARM_MESSAGE_BLOGNAME}','</p>
<p>','</p>
<table>
	<tr>
		<th align="left"><strong>', '</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTIONNAME}</td>
	</tr>
	<tr>
		<th align="left"><strong>', '</strong></th>
		<td>{ARM_MESSAGE_PAYMENT_DATE}</td>
	</tr>
	<tr>
		<th align="left"><strong>', '</strong></th>
		<td>{ARM_MESSAGE_PAYMENT_TYPE}</td>
	</tr>
	<tr>
		<th align="left"><strong>', '</strong></th>
		<td>{ARM_MESSAGE_PAYABLE_AMOUNT}</td>
	</tr>
</table>
<p>', '{ARM_MESSAGE_BLOGNAME}', 
'{ARM_MESSAGE_ADMIN_EMAIL}</p>
<p>', '<br>', '{ARM_MESSAGE_BLOGNAME}</p>')
),

$temp_slugs['on_recurring_subscription_post'] => array(
	'arm_template_subject' => sprintf('Confirmation of Your Recurring Paid Post Purchase with %s!','{ARM_MESSAGE_BLOGNAME}'),
	'arm_template_content' => sprintf( '%sHello %s Thank you for subscribing to our recurring paid post service with %s! We’re excited to continue collaborating with you.%s Here’s a quick overview of your recurring purchase details:%s Post Title%s Payment Date%s Payment Type%s Recurring Payment Amount%s Next Payment Due%s We’ll continue to publish your posts according to the schedule. If you have any updates or special requests for the future, please don’t hesitate to reach out at %s Thank you again for choosing %s. We look forward to working with you on an ongoing basis!%s Best regards,%s Team %s',
		'<p>','{ARM_MESSAGE_USERDISPLAYNAME},</p>
<p>','{ARM_MESSAGE_BLOGNAME}','</p>
<p>','</p>
<table>
	<tr>
		<th align="left"><strong>', '</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTIONNAME}</td>
	</tr>
	<tr>
		<th align="left"><strong>', '</strong></th>
		<td>{ARM_MESSAGE_PAYMENT_DATE}</td>
	</tr>
	<tr>
		<th align="left"><strong>', '</strong></th>
		<td>{ARM_MESSAGE_PAYMENT_TYPE}</td>
	</tr>
	<tr>
		<th align="left"><strong>', '</strong></th>
		<td>{ARM_MESSAGE_PAYABLE_AMOUNT}</td>
	</tr>
	<tr>
		<th align="left"><strong>', '</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTION_NEXT_DUE}</td>
	</tr>
</table>
<p>','{ARM_MESSAGE_ADMIN_EMAIL}.</p>
<p>','{ARM_MESSAGE_BLOGNAME}','</p>
<p>','</p>
<p>','{ARM_MESSAGE_BLOGNAME}</p>')
),
					

$temp_slugs['on_renew_subscription_post'] => array(
	'arm_template_subject' => sprintf('Your Paid Post Renewal Confirmation with %s','{ARM_MESSAGE_BLOGNAME}'
	),
	'arm_template_content' => sprintf(
		'%sHello %s Thank you for renewing your paid post with %s! We’re thrilled to continue working with you.%s Here’s a quick recap of your renewal details:%s Post Title%s Renewal Date%s Payment Type%s Total Cost%s We’re excited to keep your content live on %s and will ensure everything runs smoothly. If you have any further requests or updates to your post, feel free to get in touch with us at %sThank you again for choosing %s Best regards,%s Team %s',
'<p>','{ARM_MESSAGE_USERDISPLAYNAME},</p>
<p>','{ARM_MESSAGE_BLOGNAME}','</p>
<p>','</p>
<table>
<tr>
	<th align="left"><strong>', '</strong></th>
	<td>{ARM_MESSAGE_SUBSCRIPTIONNAME}</td>
</tr>
<tr>
	<th align="left"><strong>', '</strong></th>
	<td>{ARM_MESSAGE_PAYMENT_DATE}</td>
</tr>
<tr>
	<th align="left"><strong>', '</strong></th>
	<td>{ARM_MESSAGE_PAYMENT_TYPE}</td>
</tr>
<tr>
	<th align="left"><strong>', '</strong></th>
	<td>{ARM_MESSAGE_PAYABLE_AMOUNT}</td>
</tr>
</table>
<p>','{ARM_MESSAGE_BLOGNAME}</p>
<p>','{ARM_MESSAGE_ADMIN_EMAIL}</p>
<p>','{ARM_MESSAGE_BLOGNAME}!</p>,
<p>','<br>','{ARM_MESSAGE_BLOGNAME}</p>')
),
					
$temp_slugs['on_cancel_subscription_post'] => array(
	'arm_template_subject' => sprintf('Confirmation of Paid Post Cancellation with %s', 'ARMember', '{ARM_MESSAGE_BLOGNAME}'),
	'arm_template_content' => sprintf( '%sHello %s We’ve received your request to cancel your paid post with %s, and we want to confirm that your post %s has been successfully canceled.%s We’re sorry to see you go, but if you ever decide to work with us again in the future, we’d be more than happy to help! If you have any further questions or need assistance, feel free to contact us at %s Thank you for your past partnership, and we hope to work with you again soon!%s Best regards,%s Team%s',
'<p>', '{ARM_MESSAGE_USERDISPLAYNAME},</p>
<p>', '{ARM_MESSAGE_BLOGNAME}', '{ARM_MESSAGE_SUBSCRIPTIONNAME}', '</p>
<p>', '{ARM_MESSAGE_ADMIN_EMAIL}.</p>
<p?>','</p><p>' ,'<br>{ARM_MESSAGE_BLOGNAME}', '</p>'
	)
),
	
$temp_slugs['before_expire_post'] => array(
	'arm_template_subject' => sprintf('Reminder: Your Paid Post with %s is About to Expire','{ARM_MESSAGE_BLOGNAME}'),
	'arm_template_content' => sprintf('%sHello %s We wanted to give you a quick heads-up that your paid post on %s will be expiring soon. We hope you’ve been satisfied with the results!%s Here are the details:%s Post Title%s Expiration Date%s If you’d like to extend your post or discuss any updates before it expires, we’re here to assist. Feel free to reach out to %s to explore renewal options or any other requests you may have.%s Thank you for choosing %s. We hope to have your continuous support!%s Best regards,%s Team %s',
'<p>', '{ARM_MESSAGE_USERDISPLAYNAME}</p>
<p>', '{ARM_MESSAGE_BLOGNAME}', '</p>
<p>', '</p>
<table>
	<tr>
		<th><strong>', '</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTIONNAME}</td>
	</tr>
	<tr>
		<th><strong>', '</strong></th>
		<td>{ARM_MESSAGE_SUBSCRIPTION_EXPIRE}</td>
	</tr>
</table>
<p>', '{ARM_MESSAGE_ADMIN_EMAIL}','</p>
<p>', '{ARM_MESSAGE_BLOGNAME}','</p>
<p>', '<br>', '{ARM_MESSAGE_BLOGNAME}</p>'
)
),
					
$temp_slugs['on_expire_post'] = array(
    'arm_template_subject' => sprintf('Your Paid Post on %s Has Expired', '{ARM_MESSAGE_BLOGNAME}'),
    'arm_template_content' => sprintf(
        '%sHello %s We wanted to inform you that your paid post at %s has officially expired on %s Here are the details of your expired post:%s Post Title%s Expiration Date%s Total Paid Amount%s If you’re interested in extending the post, renewing it, or creating a new campaign, we’d be happy to help! Please reach out to us at %s, and we’ll assist you with your next steps.%s Best regards,%s Team %s',
        '<p>',
        '{ARM_MESSAGE_USERDISPLAYNAME}</p>
<p>','{ARM_MESSAGE_BLOGNAME}',
'{ARM_MESSAGE_SUBSCRIPTION_EXPIRE} </p>
<p>','</p>
<table>
    <tr>
        <th><strong>', '</strong></th>
        <td>{ARM_MESSAGE_SUBSCRIPTIONNAME}</td>
    </tr>
    <tr>
        <th><strong>', '</strong></th>
        <td>{ARM_MESSAGE_SUBSCRIPTION_EXPIRE}</td>
    </tr>
    <tr>
        <th><strong>', '</strong></th>
        <td>{ARM_MESSAGE_PAYABLE_AMOUNT}</td>
    </tr>
</table>
<p>',
        '{ARM_MESSAGE_ADMIN_EMAIL}</p>
<p>','</p>
<p>','<br>','{ARM_MESSAGE_BLOGNAME}</p>'
    )
	),

$temp_slugs['on_purchase_subscription_bank_transfer'] = array(
    'arm_template_subject' => sprintf('Your %s Membership Plan Purchase Confirmation', '{ARM_MESSAGE_BLOGNAME}'),
    'arm_template_content' => sprintf(
        '%sHey %s Thank you for purchasing the %s membership plan via bank transfer! We are excited to welcome you aboard.%s We are in the process of verifying the transaction. Please note that it may take a short time for us to confirm the payment and activate your membership.%s If you have any questions or need assistance during this process, please don’t hesitate to reach out to us at %s. We’re happy to assist you!%s Thank you again for choosing %s. We look forward to providing you with a premium experience!%s Best regards,%s Team%s',
        '<p>',
        '{ARM_MESSAGE_USERDISPLAYNAME}!</p>
<p>','{ARM_MESSAGE_BLOGNAME}','</p>
<p>','</p>
<p>','{ARM_MESSAGE_ADMIN_EMAIL}','</p>
<p>','{ARM_MESSAGE_BLOGNAME}','</p>
<p>','<br>{ARM_MESSAGE_BLOGNAME}','</p>'
)
	),
);

					foreach ($message_templates as &$template) {
					if (empty($template['arm_admin_template_content'])) {
						$template['arm_admin_template_content'] = $template['arm_template_content'];
					}
				}
				$message_templates = apply_filters('arm_automated_email_messages_templates', $message_templates);
				wp_send_json_success($message_templates);
				wp_die();
				}
					
				function arm_send_test_email_callback() {
					global $wpdb, $arm_email_settings, $ARMember, $arm_global_settings, $arm_capabilities_global,$all_email_settings;
				
					$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_email_notifications'], '1',1);
				
					$template_id = isset($_POST['temp_id']) ? intval($_POST['temp_id']) : 0;
					if ($template_id > 0) {
						$id = $template_id;
						$use_template = true;
					} else {
						$message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;
						if ($message_id <= 0) {
							wp_send_json_error(['msg' => 'Invalid template ID and message ID.']);
						}
						$id = $message_id;
						$use_template = false;
					}
				
					if ($use_template) {
						$temp_detail = null;
						if (isset($arm_email_settings) && method_exists($arm_email_settings, 'arm_get_single_email_template')) {
							$temp_detail = $arm_email_settings->arm_get_single_email_template($id);
						}
					} else {
						$table_name = $wpdb->prefix . 'arm_auto_message';
						$temp_detail = $wpdb->get_row(
							$wpdb->prepare("SELECT * FROM {$table_name} WHERE arm_message_id = %d", $id)
						);
					}
				
					if (empty($temp_detail)) {
						wp_send_json_error(['msg' => 'Email template not found.']);
					}
				
					if ($use_template) {
						$subject = !empty($temp_detail->arm_template_subject) ? stripslashes($temp_detail->arm_template_subject) : '';
						$content = !empty($temp_detail->arm_template_content) ? stripslashes($temp_detail->arm_template_content) : '';
						$admin_content = !empty($temp_detail->arm_admin_template_content) ? stripslashes($temp_detail->arm_admin_template_content) : $content;
					} else {
						$subject = !empty($temp_detail->arm_message_subject) ? stripslashes($temp_detail->arm_message_subject) : '';
						$content = !empty($temp_detail->arm_message_content) ? stripslashes($temp_detail->arm_message_content) : '';
						$admin_content = !empty($temp_detail->arm_message_admin_message) ? stripslashes($temp_detail->arm_message_admin_message) : $content;
					}
				
					if (empty($subject) || empty($content)) {
						wp_send_json_error(['msg' => 'Email subject or content is missing.']);
					}
				
					$subject = str_replace('{ARM_BLOGNAME}', get_bloginfo('name'), $subject);
					$content = str_replace('{ARM_BLOGNAME}', get_bloginfo('name'), $content);
					$admin_content = str_replace('{ARM_BLOGNAME}', get_bloginfo('name'), $admin_content);
				
					
					$all_email_settings = $arm_email_settings->arm_get_all_email_settings();
					$admin_email        = ( ! empty( $all_email_settings['arm_email_admin_email'] ) ) ? $all_email_settings['arm_email_admin_email'] : get_option( 'admin_email' );

				
					if (empty($admin_email)) {
						wp_send_json_error(['msg' => 'Admin email address is missing.']);
					}
					
					if (is_array($admin_email)) {
						$admin_email = implode(',', $admin_email);
					}
				
					$email_addresses = array_filter(array_map('trim', explode(',', $admin_email)), function ($email) {
						return is_email($email);
					});
				
					if (empty($email_addresses)) {
						wp_send_json_error(['msg' => 'No valid email found to send test email.']);
					}
				
					if (!isset($arm_global_settings) || !method_exists($arm_global_settings, 'arm_wp_mail')) {
						wp_send_json_error(['msg' => 'Mail function not available.']);
					}
				
					$sent = false;

					$from = ''; //set empty from so it will send as per settings automatically
					
					if ($arm_global_settings->arm_send_message_to_armember_admin_users($from, $subject, $content)) {
						$sent = true;
					}
					
					if (
						(!empty($temp_detail->arm_message_send_copy_to_admin) && intval($temp_detail->arm_message_send_copy_to_admin) === 1)
						|| (!empty($temp_detail->arm_message_send_diff_msg_to_admin) && intval($temp_detail->arm_message_send_diff_msg_to_admin) === 1)
					) {
						$arm_email_content = 'Different Content For Admin : {ARMNL}' . $admin_content . '{ARMNL}';
						do_action('arm_general_log_entry', 'email', 'send email detail', 'armember', $arm_email_content);
					}
				
					$response = [
						'status' => $sent ? 'success' : 'error',
						'msg' => $sent ? 'Test email sent to: ' . esc_html(implode(', ', $email_addresses)) : 'Failed to send test email.',
						'id' => $id,
						'popup_heading' => esc_html(stripslashes($temp_detail->arm_template_name ?? $temp_detail->arm_message_type ?? '')),
						'arm_template_slug' => $temp_detail->arm_template_slug ?? $temp_detail->arm_message_type ?? '',
						'arm_template_subject' => esc_html($subject),
						'arm_admin_template_content' => esc_html($admin_content),
						'arm_template_content' => $content,
						'arm_template_status' => $temp_detail->arm_template_status ?? $temp_detail->arm_message_status ?? '',
					];
				
					$response = apply_filters('arm_email_attachment_file_outside', $response);
				
					if ($sent) {
						do_action('arm_debug_log', 'Test email sent via arm_wp_mail to : ' . implode(', ', $email_addresses));
						wp_send_json_success($response);
					} else {
						do_action('arm_debug_log', 'Failed to send test email via arm_wp_mail to: ' . implode(', ', $email_addresses));
						wp_send_json_error($response);
					}
				}
				
			}
				}
global $arm_email_settings;
$arm_email_settings = new ARM_email_settings();