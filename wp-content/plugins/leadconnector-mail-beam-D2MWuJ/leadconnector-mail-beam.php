<?php
/**
 *
 * @link              https://www.leadconnectorhq.com/
 * @since             1.0.0
 * @package           Leadconnector_Mail_Beam
 *
 * @wordpress-plugin
 * Plugin Name:       Mail Beam by LeadConnector
 * Plugin URI:        https://www.leadconnectorhq.com/
 * Description:       Send all your mails through Mail Beam | Plug n Play | No configuration Needed
 * Version:           1.0.1
 * Author:            LeadConnector
 * Author URI:        https://www.leadconnectorhq.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       leadconnector-mail-beam
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
	die('Invalid request.');
}

//ADD MENU FOR ON DASHBOARD
add_action('admin_menu', 'LcEmailMenu');
function LcEmailMenu(){
    add_menu_page( 'LC Email', 'LC Email', 'manage_options', 'lc-email', 'LcEmailShowPage' );
}

//TO SHOW BUTTONS TO USE LC EMAIL
function LcEmailShowPage(){ ?>
<div class="wrap">
    <h1>LC-EMAIL</h1>
    <form action="<?= esc_url(admin_url('admin-post.php')) ?>" method="post">
        <input type="hidden" name="action" value="my_action" />
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="blogname">Manage Settings</label></th>
                    <td>
                        <label style="display: flex; align-items: flex-end;">
                            <input type="checkbox" style="display: flex;" name="lc_email_checkbox" value="1"
                                <?php echo (get_option( 'lc_email_checkBox' )==1 ? 'checked' : '');?> />Check the check
                            box to enable Lc Email.
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php  submit_button( __( 'Save Settings', 'textdomain' ), 'primary', 'lc_email' );?>
    </form>
</div>
<?php
}

//SET LC-EMAIL ENABLE ON PLUGIN ACTIVATION
register_activation_hook( __FILE__, 'LcEmailEnableOnActivation' );
function LcEmailEnableOnActivation(){
    update_option( 'lc_email_checkbox', '1');
}

//UPDATE CHEKBOX VALUE ON SUBMIT
add_action( 'admin_post_my_action', 'LcEmailPostAction' );
function LcEmailPostAction() {
    if( isset($_POST['lc_email']) ) {
        if(isset($_POST['lc_email_checkbox'])){
	        update_option( 'lc_email_checkbox', '1');
	        $redirect = add_query_arg( 'add_settings_message', 'success', 'admin.php?page=lc-email' );
       		wp_redirect( $redirect );
      	}else{
       		update_option( 'lc_email_checkbox', '0');
       		$redirect = add_query_arg( 'remove_settings_message', 'success', 'admin.php?page=lc-email' );
       		wp_redirect( $redirect );
      	}
    }
}

 //SHOW SUCCESS MESSAGE ON ENABLE AND DISABEL
if ( filter_input( INPUT_GET, 'remove_settings_message' ) === 'success' ){
    add_action('admin_notices', 'lcEmailAdminNoticeRemove');
}elseif(filter_input( INPUT_GET, 'add_settings_message' ) === 'success'){
    add_action('admin_notices', 'lcEmailAdminNoticeApplied');
}

//WP MAIL FUNCTION
if(get_option( 'lc_email_checkbox' ) == "1"){
	if ( ! function_exists( 'wp_mail' ) ) {
    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
        $baseUrl = "https://services.msgsndr.com/wp_bridge"; // = "https://d9d9-101-0-45-5.in.ngrok.io/wp_bridge/send_mail";
        $body = array("location"=> DB_NAME, "to"=>$to, 'subject' => $subject, 'body' => $message, 'attachment' => $attachments
        );
        wp_remote_post($baseUrl."/send_mail",array(
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body' => json_encode($body),
        'method' => 'POST',
        'data_format' => 'body',
        ));
        return true;
    }
  }

	//TO SHOW ENABLE NOTICE FUNCTION
	function lcEmailAdminNoticeApplied() {
    global $hook_suffix;
    if ( $hook_suffix == 'toplevel_page_lc-email') { ?>
			<div class="notice notice-success is-dismissible">
			    <p>Email setting is applied.</p>
			</div>
				<?
      }
  }

}

//TO SHOW DISABLE NOTICE FUNCTION
function lcEmailAdminNoticeRemove() {
  global $hook_suffix;
  if ( $hook_suffix == 'toplevel_page_lc-email')  { ?>
		<div class="notice notice-success is-dismissible">
		    <p>Email setting is removed.</p>
		</div>
<?
  }
}

//SET LC-EMAIL ENABLE ON PLUGIN DEACTIVATION
register_deactivation_hook( __FILE__, 'LcEmailDisableOnDeactivation' );
function LcEmailDisableOnDeactivation(){
    delete_option('lc_email_checkbox');
}
?>
