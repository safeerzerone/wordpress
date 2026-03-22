<?php
$filter_search = (!empty($_POST['search'])) ? sanitize_text_field($_POST['search']) : '';//phpcs:ignore

if(!wp_style_is( 'arm_lite_post_metabox_css', 'enqueued' ) && defined('MEMBERSHIPLITE_URL')){
	wp_enqueue_style('arm_lite_post_metabox_css', MEMBERSHIPLITE_URL . '/css/arm_post_metaboxes.css', array(), MEMBERSHIP_VERSION);
}
wp_enqueue_style('arm_post_metaboxes_css', MEMBERSHIP_URL . '/css/arm_post_metaboxes.css', array(), MEMBERSHIP_VERSION);
wp_enqueue_script('arm_tinymce', MEMBERSHIP_URL . '/js/arm_tinymce_member.js', array(), MEMBERSHIP_VERSION);

global $arm_members_activity;
$setact = 0;
global $check_sorting;
$setact = $arm_members_activity->$check_sorting();

global $wpdb, $ARMember, $arm_global_settings,$arm_payment_gateways;
$user_table = $ARMember->tbl_arm_members;
$user_meta_table = $wpdb->usermeta;

$PaidPostContentTypes = array('page' => esc_html__('Page', 'ARMember'), 'post' => esc_html__('Post', 'ARMember'));
$custom_post_types = get_post_types(array('public' => true, '_builtin' => false, 'show_ui' => true), 'objects');
if (!empty($custom_post_types)) {
	foreach ($custom_post_types as $cpt) {
		$PaidPostContentTypes[$cpt->name] = $cpt->label;
	}
}

$action = isset( $_REQUEST['arm_action'] ) ? sanitize_text_field($_REQUEST['arm_action']) : 'add_paid_post';
$get_page = !empty($_REQUEST['page']) ? sanitize_text_field( $_REQUEST['page'] ) : '';

$post_id = '';
$post_type = '';
$edit_paid_post = false;
if( 'edit_paid_post' == $action ){
	$post_id = isset( $_REQUEST['post_id'] ) ? intval($_REQUEST['post_id']) : '';
	$post_type = get_post_type( $post_id );
}
$get_msg = !empty($_REQUEST['msg']) ? esc_html( sanitize_text_field($_REQUEST['msg'] )) : '';
if( isset( $_REQUEST['status'] ) && 'success' == $_REQUEST['status'] ){
	echo "<script type='text/javascript'>";
		echo "jQuery(document).ready(function(){";
			echo "armToast('" . $get_msg . "','success');"; //phpcs:ignore
			echo "var pageurl = ArmRemoveVariableFromURL( document.URL, 'status' );";  
			echo "pageurl = ArmRemoveVariableFromURL( pageurl, 'msg' );";  
			echo "window.history.pushState( { path: pageurl }, '', pageurl );";
		echo "});";
	echo "</script>";
}

$global_currency = $arm_payment_gateways->arm_get_global_currency();
$all_currencies = $arm_payment_gateways->arm_get_all_currencies();
$global_currency_sym = isset($all_currencies) ? $all_currencies[strtoupper($global_currency)] : '';
$global_currency_sym = apply_filters('arm_admin_membership_plan_currency_format', $global_currency_sym, strtoupper($global_currency)); //phpcs:ignore
$global_currency_sym_pos = $arm_payment_gateways->arm_currency_symbol_position($global_currency);
$global_currency_sym_pos_pre = (!empty($global_currency_sym_pos) && $global_currency_sym_pos == 'prefix' ? '' : 'hidden_section');
$global_currency_sym_pos_suf = (!empty($global_currency_sym_pos) && $global_currency_sym_pos == 'suffix' ? '' : 'hidden_section');
$arm_currency_pos_class = ($global_currency_sym_pos == 'suffix') ? 'arm_curr_sym_suff' : 'arm_curr_sym_pref';

$script_data  = 'var CYCLEAMOUNT = "'.esc_html__('Amount', 'ARMember').'";
var ARMCURRENCYLABEL = "'.strtoupper($global_currency).'";
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
var CURRENCYLABEL ="'.strtoupper($global_currency).'";
var CURRENCYINPUTCLASS = "'.$arm_currency_pos_class.'";       
var CURRENCYPREF = "'.$global_currency_sym_pos_pre.'";
var CURRENCYSUF = "'.$global_currency_sym_pos_suf.'";
var CURRENCYSYM = "'.$global_currency_sym.'";
var ARM_RR_CLOSE_IMG = "'.MEMBERSHIP_IMAGES_URL.'/arm_close_icon.png";
var ARM_RR_CLOSE_IMG_HOVER = "'.MEMBERSHIP_IMAGES_URL.'/arm_close_icon_hover.png";
var ADDCYCLE = "'.esc_html__('Add Payment Cycle', 'ARMember').'";
var REMOVECYCLE = "'.esc_html__('Remove Payment Cycle', 'ARMember').'";
var INVALIDAMOUNTERROR = "'.esc_html__('Please enter valid amount','ARMember').'";
var ARMEDITORNOTICELABEL = "'.esc_html__('ARMember settings','ARMember').': ";';

echo "<script>".$script_data."</script>";
?>

<div class="wrap arm_page arm_paid_posts_add_edit_main_wrapper popup_wrapper">
	<div class="content_wrapper arm_paid_posts_wrapper arm_position_relative" id="content_wrapper" >
		<div class="popup_header page_title">
			<span class="arm_paid_post_page_title arm_add_paid_post_section">
			<?php
				esc_html_e('Add Paid Post','ARMember');?>
			</span>
			<span class="arm_paid_post_page_title arm_edit_paid_post_section hidden_section">
			<?php
				esc_html_e('Edit Paid Posts','ARMember');?>
			</span>
			<span class="arm_popup_close_btn arm_close_paid_post_btn"></span>
			
			<?php
			$after_title = "";
			$after_title = apply_filters('arm_filter_after_paid_post_page_title',$after_title);
			echo $after_title; //phpcs:ignore
			?>
		</div>
		<div class="armclear"></div>
		<?php
			global $arm_pay_per_post_feature;
			$total_paid_post_setups = $arm_pay_per_post_feature->arm_get_paid_post_setup();
			
			if( $total_paid_post_setups < 1 ){

				$arm_setup_link = admin_url( 'admin.php?page=arm_membership_setup&action=new_setup' );
		?>
			<div class="armember_notice_warning">
				<?php echo sprintf( esc_html__( 'You don\'t have created paid post type membership setup. Please create at least one membership setup for paid post from %s and then reload this page.', 'ARMember' ), '<a href="'.esc_url($arm_setup_link).'">here</a>' ); //phpcs:ignore?>
			</div>
		<?php
			} else {
		?>
			<form method="post" id="arm_add_edit_paid_post_form" class="arm_add_edit_paid_post_form arm_admin_form" novalidate="novalidate">
				<?php
					
					echo '<input type="hidden" name="edit_paid_post_id" value="0" />';
					echo '<input type="hidden" name="edit_paid_post_type" value="page" />';
					echo '<input type="hidden" name="arm_enable_paid_post" value="1"/>';
					echo '<input type="hidden" name="arm_enable_paid_post_hidden" value="1"/>';
					echo '<input type="hidden" name="arm_action" value="arm_add_update_paid_post_plan" />';
					echo '<input type="hidden" name="arm_post_action" value="add_paid_post" />';
				?>
				<div class="arm_admin_form_content postbox" id="arm_paid_post_metabox_wrapper">
					<table class="form-table">
						<tbody>
							<tr class="form-field form-required arm_paid_post_form_section">
								<?php
									global $arm_pay_per_post_feature;
									
									$postBlankObj = new stdClass();
									$content = "";
									echo apply_filters('arm_add_paid_post_metabox_html',$content,$postBlankObj,true,true);
								?>
								
							</tr>
						</tbody>
					</table>
					
				</div>
				<div class="arm_submit_btn_container">
					<img src="<?php echo MEMBERSHIP_IMAGES_URL.'/arm_loader.gif' //phpcs:ignore?>" id="arm_loader_img_add_member_paid_post_loader" class="arm_loader_img arm_submit_btn_loader"  style=" display: none;" width="20" height="20" />
					<button class="arm_cancel_btn arm_close_paid_post_btn" type="button"><?php esc_html_e('Close','ARMember'); ?></button>
					<button class="arm_save_btn arm_paid_post_save_btn" type="submit"><?php esc_html_e('Save','ARMember'); ?></button>
				</div>
				<?php
				
				$after_content = "";
				$after_content = apply_filters('arm_content_filter_after_paid_post_form_content',$after_content);
				echo $after_content; //phpcs:ignore
				
				?>
			</form>
		<?php
			}
		?>
		<?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
		<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
		<div class="armclear"></div>
	</div>
</div>

<script type="text/javascript">

	jQuery(document).on('click', '.arm_remove_selected_itembox', function () {
		jQuery(this).parents('.arm_paid_post_itembox').remove();
		if(jQuery('#arm_paid_post_items .arm_paid_post_itembox').length == 0) {
			jQuery('#arm_paid_post_items_input').attr('required', 'required');
			jQuery('#arm_paid_post_items').hide();
		}
		return false;
	});	
</script>
<?php
	if (file_exists(MEMBERSHIP_VIEWS_DIR . '/arm_tinymce_options_shortcodes.php')) {
		require ( MEMBERSHIP_VIEWS_DIR . '/arm_tinymce_options_shortcodes.php');
	}
	echo $ARMember->arm_get_need_help_html_content('paid-posts-list-add'); //phpcs:ignore
?>