<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<el-main class="ap-main-listing-card-container ap-default-card ap--is-page-scrollable-tablet ap-addon-list-card" id="all-page-main-container">	
	<div class="ap-back-loader-container" id="ap-page-loading-loader">
		<div class="ap-back-loader" v-if="is_display_loader == 1"></div>
	</div>	
	<div id="ap-main-container">		
		<el-container class="ap-addons-container">
			<div class="ap-addon-sub-list-wrapper ap-addon-feature-list-wrapper" >
				<el-row v-for="(addonsList, category) in ap_lite_addons" style="display : block;">
					<el-row type="flex" class="ap-mlc-head-wrap" :class="category+'-ap-mlc-head-wrap'">
						<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="">
							<h1 class="ap-page-heading" v-if="category == 'features'"><?php esc_html_e( 'Feature Add-ons', 'affiliatepress-affiliate-marketing' ); ?></h1>
							<h1 class="ap-page-heading" v-if="category == 'payment_gateways'"><?php esc_html_e( 'Payment Gateways', 'affiliatepress-affiliate-marketing' ); ?></h1>
							<h1 class="ap-page-heading" v-if="category == 'integrations'"><?php esc_html_e( 'Integrations', 'affiliatepress-affiliate-marketing' ); ?></h1>
						</el-col>
					</el-row>
					<el-row :gutter="32" class="">
						<el-col :xs="12" :sm="12" :md="12" :lg="8" :xl="6" v-for="addons in addonsList" class="ap-addons-items-col">
							<div class="ap-addon-item" :id="addons.addon_key+'_activate_addon'">
								<div class="ap-addon-details">
									<span class="ap-ai-icon" :class="addons.addon_icon_slug"></span>
									<div class="">
										<h3>{{ addons.addon_name }}</h3>
									</div>
								</div>
								<div class="ap-ai-desc">
									<div>{{ addons.addon_description }}</div>							
								</div>
								<div class="ap-ai-btns">
									<el-row type="flex">
										<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">	
											<el-button @click="open_premium_modal()" class="ap-btn--full-width ap-btn--addon-primary ap-upgrade-btn">
												<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
													<path class="ap-hover-upgrade-color" d="M16.2067 10.1712L16.3786 8.34472C16.5136 6.91027 16.5811 6.19304 16.3357 5.89655C16.203 5.73617 16.0225 5.6379 15.8295 5.62095C15.4727 5.58961 15.0247 6.09967 14.1286 7.1198C13.6651 7.64737 13.4335 7.91115 13.1749 7.95203C13.0318 7.9746 12.8858 7.95135 12.7535 7.88482C12.5149 7.76467 12.3557 7.43859 12.0374 6.78638L10.3598 3.34864C9.7584 2.11621 9.45765 1.5 9 1.5C8.54235 1.5 8.2416 2.11621 7.64017 3.34864L5.96255 6.78639C5.64427 7.43859 5.48513 7.76467 5.24644 7.88482C5.11419 7.95135 4.96825 7.9746 4.82503 7.95203C4.56654 7.91115 4.33483 7.64737 3.8714 7.1198C2.97531 6.09967 2.52726 5.58961 2.17049 5.62095C1.97749 5.6379 1.79698 5.73617 1.66424 5.89655C1.41885 6.19304 1.48635 6.91027 1.62136 8.34472L1.79325 10.1712C2.07649 13.1806 2.21811 14.6854 3.10507 15.5926C3.99203 16.5 5.32138 16.5 7.98007 16.5H10.0199C12.6786 16.5 14.008 16.5 14.8949 15.5926C15.7819 14.6854 15.9235 13.1806 16.2067 10.1712Z" fill="#1CC6C9"/>
												</svg>
												<span class="ap-btn__label"><?php esc_html_e('Upgrade to Pro', 'affiliatepress-affiliate-marketing'); ?></span>
											</el-button>
										</el-col>
									</el-row>
								</div>
								<div class="ap-ai-doc-link">
									<el-link :href="addons.addon_documentation" target="_blank">
										<?php esc_html_e( 'More info', 'affiliatepress-affiliate-marketing' ); ?><?php do_action('affiliatepress_common_svg_code','more_info'); ?>
									</el-link>
								</div>
							</div>
						</el-col>
					</el-row>
				</el-row>
			</div>
		</el-container>
	</div>
</el-main>
<?php
    $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/affiliatepress_footer.php';
    $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_footer_content', $affiliatepress_load_file_name,1);
    require $affiliatepress_load_file_name;
?>
