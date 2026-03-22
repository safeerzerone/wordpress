<?php 
if( !defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap arfforms_page">
	<div class="top_bar" style="margin-bottom: 10px;">
	<span class="h2"> <?php echo esc_html__( 'ARForms Add-Ons', 'arforms-form-builder' ); ?></span>
	</div>
	<div id="poststuff" class="">
		<div id="post-body" >
			<div class="addon_content">
				<input type="hidden" name="arf_validation_nonce" id="arf_validation_nonce" value="<?php echo esc_attr( wp_create_nonce( 'arf_wp_nonce' ) ); ?>" />
				<div class="addon_page_content">
					<?php
						global $arflitesettingcontroller;
						$arflitesettingcontroller->addons_page();
					?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php do_action( 'arforms_quick_help_links' ); ?>