<?php
if( !defined( 'ABSPATH' ) ) exit;
global $arflitesettingcontroller, $arformsmain;

if ( $arf_addons == '' ) {

	echo "<div class='error_message' style='margin-top:100px; padding:20px;'>" . esc_html__( 'Add-On listing is currently unavailable. Please try again later.', 'arforms-form-builder' ) . '</div>';

} else {

	//$arf_addons = maybe_unserialize( base64_decode( $arf_addons ) );
	$arf_addons = base64_decode( $arf_addons );
	if( !empty( $arf_addons )){

		$arf_addons = json_decode( $arf_addons, true );
	}

	$arflite_plugins = get_plugins();
	$installed_plugins = array();
	foreach ( $arflite_plugins as $key => $plugin_val ) {
		$is_active        = is_plugin_active( $key );
		$installed_plugin = array(
			'plugin'    => $key,
			'name'      => $plugin_val['Name'],
			'is_active' => $is_active,
		);

		$installed_plugin['activation_url']   = $is_active ? '' : wp_nonce_url( "plugins.php?action=activate&plugin={$key}", "activate-plugin_{$key}" );
		$installed_plugin['deactivation_url'] = ! $is_active ? '' : wp_nonce_url( "plugins.php?action=deactivate&plugin={$key}", "deactivate-plugin_{$key}" );

		$installed_plugins[] = $installed_plugin;
	}

	$arforms_default_addons_list = array();
	$arforms_default_addons_list['default_module'] = array();
	$arforms_default_addons_list['default_module'][] = array(
		'name'                  => __( 'Google reCaptcha', 'arforms-form-builder' ),
		'key'                   => 'arforms_gcaptcha',
		'description'           => __( 'Add reCaptcha with Google reCaptcha Add-On of ARForms', 'arforms-form-builder' ),
		'is_active'             => $arformsmain->arforms_get_settings( 'arforms_gcaptcha', 'arforms_module' ),
		'documentation_url'     => 'https://www.arformsplugin.com/add-on/google-recaptcha/',
		'arf_class'             => 'arf_google_recaptcha',
	);
	$arforms_default_addons_list['default_module'][] = array(
		'name'                  => __( 'Turnstile Captcha', 'arforms-form-builder' ),
		'key'                   => 'arforms_tcaptcha',
		'description'           => __( 'Add reCaptcha with Google reCaptcha Add-On of ARForms', 'arforms-form-builder' ),
		'is_active'             => $arformsmain->arforms_get_settings( 'arforms_tcaptcha', 'arforms_module' ),
		'documentation_url'     => 'https://www.arformsplugin.com/add-on/google-recaptcha/',
		'arf_class'             => 'arf_turnstile_captcha',
	);
	$arforms_default_addons_list['default_module'][] = array(
		'name'                  => __( 'hCaptcha', 'arforms-form-builder' ),
		'key'                   => 'arforms_hcaptcha',
		'description'           => __( 'Add reCaptcha with Google reCaptcha Add-On of ARForms', 'arforms-form-builder' ),
		'is_active'             => $arformsmain->arforms_get_settings( 'arforms_hcaptcha', 'arforms_module' ),
		'documentation_url'     => 'https://www.arformsplugin.com/add-on/google-recaptcha/',
		'arf_class'             => 'arf_hcaptcha',
	); 
	$arforms_default_addons_list['default_module'][] = array(
		'name'                  => __( 'PayPal', 'arforms-form-builder' ),
		'key'                   => 'arforms_paypal',
		'description'           => __( 'Add reCaptcha with Google reCaptcha Add-On of ARForms', 'arforms-form-builder' ),
		'is_active'             => $arformsmain->arforms_get_settings( 'arforms_paypal', 'arforms_module' ),
		'documentation_url'     => 'https://www.arformsplugin.com/add-on/google-recaptcha/',
		'arf_class'             => 'arf_paypal',
	);

	foreach( $arforms_default_addons_list as $arforms_default_addons_key=>$arforms_default_addons_val ){

		if( $arforms_default_addons_key == 'default_module' ){ ?>
			<div class="arf_addon_heading arf_deafult_addon"> <span> Additional Modules </span> </div>
		<?php } 

		foreach( $arforms_default_addons_val as $arf_default_key=>$arf_default_val ){
			?>
			<div class="addon_container default_module">
				<?php if ( $arf_default_val['is_active'] == 1 ) { echo "<div class='addon_container_default_module_activated'></div>"; } ?>
				<div class="addon_image <?php echo $arf_default_val['arf_class']; ?>">
					<a href="" target="_blank"></a>
				</div>
				<div class="addon_title"> <a href="" target="_blank"><?php echo esc_html( $arf_default_val['name'] ); ?></a> </div>
				<div class="addon_description"><?php echo esc_html( $arf_default_val['description'] ); ?></div>
				<div class="add_more">
				<span class="arf_readmore_cls"></span><a href="" class="addon_readmore" target="_blank"><?php echo esc_html__( 'Read More...', 'arforms-form-builder' ); ?> </a>
				</div>


				<?php 
				
					if( $arf_default_val['is_active'] == 1 ){
						$arf_label = __('Deactivate', 'arforms-form-builder');
						$arf_activate_cls = "addon_processing_tick_deactivation";
					} else {
						$arf_label = __('Activate', 'arforms-form-builder');
						$arf_activate_cls = "addon_processing_tick";
					}
					
					echo '<button class="addon_button no_icon arforms_builtin_module_btn" data-status="'.$arf_default_val['is_active'].'" type="button" data-module="'.$arf_default_val['key'].'"><span class="addon_processing_div '.$arf_activate_cls.'">' . $arf_label . '</span><span class="get_it_a">'. $arf_label.'</span><span class="arf_addon_loader"><svg class="arf_circular" viewBox="0 0 60 60"><circle class="path" cx="25px" cy="23px" r="18" fill="none" stroke-width="4" stroke-miterlimit="7"></circle></svg></span></button>';
				 
				?>
			</div>
			<?php
		}

	}

	if ( is_array( $arf_addons ) && count( $arf_addons ) > 0 ) {

		foreach( $arf_addons as $arf_addon_cat=>$arf_addon_val ){

			if( $arf_addon_cat == 'additional_functionality' ){ ?>
				<div class="arf_addon_heading"> <span> <?php esc_html_e('Additional Functionality Add-ons', 'arforms-form-builder' ); ?> </span> </div>
			<?php } else if (  $arf_addon_cat == 'integrations' ) { ?>
				<div class="arf_addon_heading"> <span> <?php esc_html_e('Third Party Integration', 'arforms-form-builder' ); ?> </span> </div>
			<?php } else if (  $arf_addon_cat == 'payment_gateways' ) { ?>
				<div class="arf_addon_heading"> <span> <?php esc_html_e( 'Payment Gateway Integrations', 'arforms-form-builder' ); ?> </span> </div>
			<?php } 

			foreach( $arf_addon_val as $arf_addon_key=>$arf_addon_val ){

				$is_active_addon = is_plugin_active( $arf_addon_val['plugin_installer'] );
				if ( isset( $arf_addon_val['allow_for_free'] ) && 1 != $arf_addon_val['allow_for_free'] ) {
					$addon_detail_url = 'https://1.envato.market/rdeQD';
				} else {
					$addon_detail_url = $arf_addon_val['detail_url'];
				} ?>
				
				<div class="addon_container">
					<?php if ( $is_active_addon == 1 ) { echo "<div class='addon_container_activated'></div>"; } ?>
					<div class="addon_image <?php echo $arf_addon_val['arf_class']; ?>">
						<a href="<?php echo esc_url( $addon_detail_url ); ?>" target="_blank"></a>
					</div>
					<div class="addon_title"> <a href="<?php echo esc_url( $addon_detail_url ); ?>" target="_blank"><?php echo esc_html( $arf_addon_val['full_name'] ); ?></a> </div>
					<div class="addon_description"><?php echo esc_html( $arf_addon_val['description'] ); ?></div>
					<div class="add_more">
					<span class="arf_readmore_cls"></span><a href="<?php echo esc_url( $addon_detail_url ); ?>" class="addon_readmore" target="_blank"><?php echo esc_html__( 'Read More...', 'arforms-form-builder' ); ?> </a>
					</div>

					<?php 
					global $arformsmain;
					if( $arformsmain->arforms_is_pro_active() ){

						global $arsettingcontroller;

						echo $arsettingcontroller->CheckpluginStatus($installed_plugins, $arf_addon_val['plugin_installer'], 'plugin', $arf_addon_val['short_name'], $arf_addon_val['plugin_type'], $arf_addon_val['install_url']);

					} else {

						echo $arflitesettingcontroller->CheckpluginStatus( $installed_plugins, $arf_addon_val['plugin_installer'], 'plugin', $arf_addon_val['short_name'], $arf_addon_val['plugin_type'], $arf_addon_val['install_url'], $arf_addon_val['allow_for_free'] ); //phpcs:ignore 
					} ?>

				</div>
				<?php 
			}
		}
	}
}

$arf_addons_data = get_transient( 'arflite_addon_installation_page_data' );
if ( false == $arf_addons_data ) {
	set_transient( 'arflite_addon_installation_page_data', $arf_addons, DAY_IN_SECONDS );
}

global $arformsmain;
	if( $arformsmain->arforms_is_pro_active() ){
		$arf_addons_data = get_transient( 'arf_addon_installation_page_data' );
		if( false == $arf_addons_data ){
			set_transient('arf_addon_installation_page_data',$arf_addons,DAY_IN_SECONDS);
		}
	}

?>

<div id="error_message" class="arf_error_message">
	<div class="message_descripiton">
		<div id="arf_plugin_install_error" style="float: left; margin-right: 15px;" id=""><?php echo esc_html__( 'File is not proper.', 'arforms-form-builder' ); ?></div>
		<div class="message_svg_icon">
			<svg style="height: 14px;width: 14px;"><path fill-rule="evenodd" clip-rule="evenodd" fill="#ffffff" d="M10.702,10.909L6.453,6.66l-4.249,4.249L1.143,9.848l4.249-4.249L1.154,1.361l1.062-1.061l4.237,4.237l4.238-4.237l1.061,1.061L7.513,5.599l4.249,4.249L10.702,10.909z"></path></svg>
		</div>
	</div>
</div>
