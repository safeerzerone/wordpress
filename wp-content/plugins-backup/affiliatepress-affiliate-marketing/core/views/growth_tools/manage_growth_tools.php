<?php if ( ! defined( 'ABSPATH' ) ) { exit; }
    
    global $affiliatepress_slugs, $AffiliatePress;
    $request_module = ( ! empty($_REQUEST['page']) && ( $_REQUEST['page'] != 'affiliatepress' ) ) ? sanitize_text_field(str_replace('affiliatepress_', '', $_REQUEST['page'])) : 'dashboard'; // phpcs:ignore
    $request_action = ( ! empty($_REQUEST['action']) ) ? sanitize_text_field($_REQUEST['action']) : 'forms'; // phpcs:ignore

    $affiliatepress_logoimage = AFFILIATEPRESS_IMAGES_URL . '/affiliatepress-logo.svg';

if(!empty($request_module) && $request_module != 'lite_wizard'){
    echo $AffiliatePress->affiliatepress_lifetime_deal_header_belt();//phpcs:ignore
}
?>
<nav class="ap-header-navbar ">
    <div class="ap-header-navbar-wrap ap-growth-tools">
        <div class="">
            <a href="<?php echo esc_url( admin_url() . 'admin.php?page=affiliatepress' ); ?>" class="navbar-logo">
            <svg width="48" height="50" viewBox="0 0 48 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17.5054 23.0797L17.5213 23.0638C17.8636 22.7255 18.2297 22.4072 18.6396 22.1564C25.0113 18.2562 31.7331 27.7321 25.4013 32.3367C20.2037 36.1175 13.8559 30.3429 16.1324 25.0378C16.4507 24.2976 16.9363 23.6449 17.5054 23.0797Z" fill="#1CC6C9"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M13.703 43.5004C12.4334 45.1282 12.4851 47.6235 14.1049 48.9607L14.1009 48.9567C17.3325 51.6272 21.4397 48.7736 20.5562 44.8814C20.4905 44.5908 20.2428 44.1865 19.9736 43.7473C19.4429 42.8814 18.8289 41.8794 19.3622 41.3434C19.7298 40.973 21.0486 41.0017 22.1337 41.0253H22.1337H22.1338C22.6228 41.0359 23.0644 41.0455 23.35 41.017C32.1135 40.1415 38.0354 31.6804 36.3838 23.1159C36.2454 22.4003 35.885 21.5373 35.505 20.6272C34.6169 18.5005 33.6212 16.1161 35.0983 14.7504C36.3639 13.5817 37.6361 13.6935 38.9803 13.8116C39.4578 13.8536 39.9445 13.8964 40.4431 13.8828C48.6415 13.6679 49.9787 2.43694 42.4052 0.259998C37.9996 -1.00558 33.5303 2.5245 33.5382 7.04554C33.5399 7.47129 33.5985 7.87828 33.6581 8.29177C33.7411 8.86777 33.826 9.45638 33.7611 10.1259C33.3387 14.5119 29.8493 13.3004 26.839 12.2553C25.9351 11.9414 25.0744 11.6426 24.3529 11.5148C21.2287 10.9617 18.2558 11.4512 15.3864 12.7247C15.0636 12.8677 14.7251 13.0744 14.3785 13.286C13.2296 13.9876 11.9909 14.7441 10.933 13.4172C10.222 12.5226 10.3391 11.4836 10.4593 10.4168C10.5241 9.84157 10.5898 9.25826 10.5271 8.68521C10.2843 6.48439 8.31035 4.37112 6.08963 4.08458C-0.795397 3.19708 -2.78529 13.5246 5.09867 14.7265C5.37325 14.7672 5.6551 14.7538 5.93414 14.7406C6.2011 14.7279 6.46548 14.7154 6.71844 14.7504C10.5094 15.2643 9.61969 17.1235 8.61484 19.2234C8.35907 19.7579 8.09584 20.308 7.90043 20.8554C5.65584 27.1554 7.39898 34.1758 12.9269 38.1277C13.1633 38.2975 13.5323 38.5083 13.9444 38.7436C15.0097 39.352 16.3634 40.1251 16.461 40.7822C16.5467 41.3596 15.6517 41.9944 14.8355 42.5734C14.3633 42.9084 13.9174 43.2247 13.703 43.5004ZM11.7529 24.9068C12.0713 20.931 15.9635 16.7761 19.9353 16.2389C27.6999 15.1882 33.9362 21.9618 31.3931 29.5314C27.7118 40.5036 10.734 37.6382 11.7529 24.9068ZM7.95688 9.37884C7.95688 10.8361 6.77554 12.0174 5.31829 12.0174C3.86103 12.0174 2.67969 10.8361 2.67969 9.37884C2.67969 7.92158 3.86103 6.74023 5.31829 6.74023C6.77554 6.74023 7.95688 7.92158 7.95688 9.37884ZM16.7881 48.0332C17.9266 48.0332 18.8496 47.1102 18.8496 45.9717C18.8496 44.8331 17.9266 43.9102 16.7881 43.9102C15.6495 43.9102 14.7266 44.8331 14.7266 45.9717C14.7266 47.1102 15.6495 48.0332 16.7881 48.0332ZM44.3023 6.78984C44.3023 8.73944 42.7219 10.3199 40.7723 10.3199C38.8227 10.3199 37.2422 8.73944 37.2422 6.78984C37.2422 4.84023 38.8227 3.25977 40.7723 3.25977C42.7219 3.25977 44.3023 4.84023 44.3023 6.78984Z" fill="#6858E0"/>
                <path d="M18.9423 20.1471L18.9502 20.1392C19.1771 19.9123 19.4238 19.7054 19.6945 19.5382C23.9329 16.9434 28.3982 23.2434 24.1916 26.3079C20.7372 28.8231 16.5146 24.9826 18.0269 21.4525C18.2379 20.963 18.5642 20.5252 18.9423 20.1511V20.1471Z" fill="#6858E0"/>
            </svg>
            </a>
        </div>
        <span class="ap_growth_tools_heading"> Growth Plugins </span> 
	</div>
</nav>
<el-main class="ap-main-listing-card-container ap-default-card ap--is-page-scrollable-tablet ap-growth-tools" id="all-page-main-container">	
	<div class="ap-back-loader-container" id="ap-page-loading-loader">
	</div>	
	<div id="ap-main-container">		
		<el-container class="ap-growth-tools-container">
			<div class="ap-growth-tools-sub-list-wrapper">
            <?php  
                ?>
                <el-row type="flex" class="ap-mlc-head-wrap">
					<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="">
                        <div class="ap-gt-page-inner-block-heading"> Our <span class="ap-page-heading-highlight"> Family WordPress Plugins </span> </div>
					</el-col>
				</el-row>
                <el-row type="flex" class="ap-mlc-head-wrap">
					<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="">
                        <div class="ap-gt-heading-main-inner-cls">
						    <div class="ap-gt-page-inner-contain">
                            You will get the same user-friendly experience throughout all of our plugins. Enjoy single-window 24/7 support for all our plugins. All of our plugins are compatible with each other.
                            </div>
                        </div>
					</el-col>
				</el-row>
                <div class="ap-sec-space"> </div>
                <el-row type="flex" class="ap-mlc-head-wrap">
					<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="">
                        <div class="ap-plugin-details-cls">
                            <div class="ap-gt-plugin-icon">
                                <span class="ap-plugin-icon ap-bpa-icon"></span>
                            </div>
                            <div class="ap-gt-plugin-dec">
                                <div class="ap-plugin-heading bpa-heading"> BookingPress <span class="ap-plugin-heading-cls">- WordPress Booking Plugin </span> </div>
                                <div class="ap-plugin-heading-desc"> Imagine a WordPress BookingPress Plugin that's remarkably user-friendly, equipped with an extensive feature set, excelling in performance, and featuring a sleek modern interface. It distinguishes itself as a superior option, surpassing even the most popular Appointment Booking plugins available. </div>
                                <div class="ap-plugin-key-feature"> Key Features: </div>
                                <ul class="ap-feature-list-cls-plugin-dec">
                                    <li class="ap-feature-list-li-plugin"> Great UI And UX </li>
                                    <li class="ap-feature-list-li-plugin"> Online Payment Gateways </li>
                                    <li class="ap-feature-list-li-plugin"> Built-in Spam Facility </li>
                                    <li class="ap-feature-list-li-plugin"> Interactive booking wizard </li>
                                    <li class="ap-feature-list-li-plugin"> Offline Payment </li>
                                    <li class="ap-feature-list-li-plugin"> Custom Email Notifications </li>
                                </ul>
                                <div style="margin-top:40px;">
                                    <el-button class="el-button ap-btn ap-learnmore-btn bpa-plugin" @click="goToLearnMore('bookingpress')" >
                                        <span class="ap-btn__label"> Learn More </span>
                                    </el-button>
                                    <?php
                                        $affiliatepress_plugin_file = 'bookingpress-appointment-booking/bookingpress-appointment-booking.php';
                                        $affiliatepress_plugin_path = WP_PLUGIN_DIR . '/' . $affiliatepress_plugin_file;

                                        if ( file_exists( $affiliatepress_plugin_path ) ) {
                                            if ( is_plugin_active( $affiliatepress_plugin_file ) ) {
                                                ?>
                                                <el-button class="el-button ap-btn ap-install-btn bpa-plugin" disabled>
                                                    <span class="ap-btn__label">Activated</span>
                                                </el-button>
                                                <?php
                                            } else {
                                                ?>
                                                <el-button class="el-button ap-btn ap-install-btn bpa-plugin" @click="affiliatepress_activate_plugins('bookingpress')" :class="(is_display_bookingpress_save_loader == '1') ? 'ap-btn--is-loader' : ''" :disabled="is_disabled">
                                                    <span class="ap-btn__label"> Activate </span> 
                                                    <div class="ap-btn--loader__circles">                    
                                                        <div></div>
                                                        <div></div>
                                                        <div></div>
                                                    </div>          
                                                </el-button>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <el-button class="el-button ap-btn ap-install-btn bpa-plugin" @click="affiliatepress_download_plugins('bookingpress')" :class="(is_display_bookingpress_save_loader == '1') ? 'ap-btn--is-loader' : ''" :disabled="is_disabled">
                                                <span class="ap-btn__label"> Install </span> 
                                                <div class="ap-btn--loader__circles">                    
                                                    <div></div>
                                                    <div></div>
                                                    <div></div>
                                                </div>              
                                            </el-button>
                                            </el-button>
                                            <?php
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
					</el-col>
				</el-row>
                <hr class="ap-section-line"> 
                <el-row type="flex" class="ap-mlc-head-wrap">
					<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="">
                        <div class="ap-plugin-details-cls">
                            <div class="ap-gt-plugin-icon">
                                <span class="ap-plugin-icon ap-arm-icon"></span>
                            </div>
                            <div class="ap-gt-plugin-dec">
                                <div class="ap-plugin-heading"> ARMember <span class="ap-plugin-heading-cls">- WordPress Membership Plugin </span> </div>
                                <div class="ap-plugin-heading-desc"> Can you imagine a WordPress Membership Plugin that is ridiculously easy to operate, offers a wide range of features, excels in performance, and boasts a modern user interface? It's very different and much better than even the most popular membership plugins available here. </div>
                                <div class="ap-plugin-key-feature"> Key Features: </div>
                                <ul class="ap-feature-list-cls-plugin-dec">
                                    <li class="ap-feature-list-li-plugin"> Membership Setup Wizard </li>
                                    <li class="ap-feature-list-li-plugin"> Email Notification Templates </li>
                                    <li class="ap-feature-list-li-plugin"> Unlimited Membership Levels </li>
                                    <li class="ap-feature-list-li-plugin"> Live form Editor </li>
                                    <li class="ap-feature-list-li-plugin"> Create Free & Paid Memberships </li>
                                    <li class="ap-feature-list-li-plugin"> Captcha Free Anti-spam Facility </li>
                                </ul>
                                <div style="margin-top:40px;">
                                    <el-button class="el-button ap-btn ap-learnmore-btn" @click="goToLearnMore('armember')" >
                                        <span class="ap-btn__label"> Learn More </span>
                                    </el-button>
                                    <?php
                                        $affiliatepress_plugin_file = 'armember-membership/armember-membership.php';
                                        $affiliatepress_plugin_path = WP_PLUGIN_DIR . '/' . $affiliatepress_plugin_file;

                                        if ( file_exists( $affiliatepress_plugin_path ) ) {
                                            if ( is_plugin_active( $affiliatepress_plugin_file ) ) {
                                                ?>
                                                <el-button class="el-button ap-btn ap-install-btn arm-plugin" disabled>
                                                    <span class="ap-btn__label">Activated</span>
                                                </el-button>
                                                <?php
                                            } else {
                                                ?>
                                                <el-button class="el-button ap-btn ap-install-btn arm-plugin" @click="affiliatepress_activate_plugins('armember')" :class="(is_display_save_loader == '1') ? 'ap-btn--is-loader' : ''" :disabled="is_disabled">
                                                    <span class="ap-btn__label"> Activate </span> 
                                                    <div class="ap-btn--loader__circles">                    
                                                        <div></div>
                                                        <div></div>
                                                        <div></div>
                                                    </div>         
                                                </el-button>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <el-button class="el-button ap-btn ap-install-btn arm-plugin" @click="affiliatepress_download_plugins('armember')" :class="(is_display_save_loader == '1') ? 'ap-btn--is-loader' : ''" :disabled="is_disabled">
                                                <span class="ap-btn__label"> Install </span> 
                                                <div class="ap-btn--loader__circles">                    
                                                        <div></div>
                                                        <div></div>
                                                        <div></div>
                                                    </div>         
                                            </el-button>
                                            <?php
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
					</el-col>
				</el-row>
                <hr class="ap-section-line"> 
                <el-row type="flex" class="ap-mlc-head-wrap">
					<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="">
                        <div class="ap-plugin-details-cls">
                            <div class="ap-gt-plugin-icon">
                                <span class="ap-plugin-icon ap-arf-icon"></span>
                            </div>
                            <div class="ap-gt-plugin-dec">
                                <div class="ap-plugin-heading arf-heading"> ARForms <span class="ap-plugin-heading-cls">- WordPress Form Builder Plugin </span> </div>
                                <div class="ap-plugin-heading-desc"> ARForms is an all-in-one WordPress form builder plugin. It not only allows you to create contact forms for your site but also empowers you to build WordPress forms such as feedback forms, survey forms, and various other types of forms with responsive designs. </div>
                                <div class="ap-plugin-key-feature"> Key Features: </div>
                                <ul class="ap-feature-list-cls-plugin-dec">
                                    <li class="ap-feature-list-li-plugin"> Real-Time Form Editor </li>
                                    <li class="ap-feature-list-li-plugin"> Multi-Column Option </li>
                                    <li class="ap-feature-list-li-plugin"> Styling & Unlimited Color Option </li>
                                    <li class="ap-feature-list-li-plugin"> Built-In Anti Spam Protection </li>
                                    <li class="ap-feature-list-li-plugin"> Material & Rounded Style Forms </li>
                                    <li class="ap-feature-list-li-plugin"> Popular Page Builders Support </li>
                                </ul>
                                <div style="margin-top:40px;">
                                    <el-button class="el-button ap-btn ap-learnmore-btn arf-plugin" @click="goToLearnMore('arforms')" >
                                        <span class="ap-btn__label"> Learn More </span>
                                    </el-button>
                                    <?php
                                        $affiliatepress_plugin_file = 'arforms-form-builder/arforms-form-builder.php';
                                        $affiliatepress_plugin_path = WP_PLUGIN_DIR . '/' . $affiliatepress_plugin_file;

                                        if ( file_exists( $affiliatepress_plugin_path ) ) {
                                            if ( is_plugin_active( $affiliatepress_plugin_file ) ) {
                                                ?>
                                                <el-button class="el-button ap-btn ap-install-btn arf-plugin" disabled>
                                                    <span class="ap-btn__label">Activated</span>
                                                </el-button>
                                                <?php
                                            } else {
                                                ?>
                                                <el-button class="el-button ap-btn ap-install-btn arf-plugin" @click="affiliatepress_activate_plugins('arforms')" :class="(is_display_arforms_save_loader == '1') ? 'ap-btn--is-loader' : ''" :disabled="is_disabled">
                                                    <span class="ap-btn__label"> Activate </span> 
                                                    <div class="ap-btn--loader__circles">                    
                                                        <div></div>
                                                        <div></div>
                                                        <div></div>
                                                    </div>         
                                                </el-button>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <el-button class="el-button ap-btn ap-install-btn arf-plugin" @click="affiliatepress_download_plugins('arforms')":class="(is_display_arforms_save_loader == '1') ? 'ap-btn--is-loader' : ''" :disabled="is_disabled">
                                                <span class="ap-btn__label"> Install </span> 
                                                <div class="ap-btn--loader__circles">                    
                                                        <div></div>
                                                        <div></div>
                                                        <div></div>
                                                    </div>         
                                            </el-button>
                                            <?php
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
					</el-col>
				</el-row>
                <hr class="ap-section-line">
                <el-row type="flex" class="ap-mlc-head-wrap ap-plugin-section">
					<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="">
                        <div class="ap-plugin-details-cls">
                            <div class="ap-gt-plugin-icon">
                                <span class="ap-plugin-icon ap-arp-icon"></span>
                            </div>
                            <div class="ap-gt-plugin-dec">
                                <div class="ap-plugin-heading arp-heading"> ARPrice <span class="ap-plugin-heading-cls">- WordPress Pricing Table Plugin </span> </div>
                                <div class="ap-plugin-heading-desc">ARPrice is a WordPress pricing table plugin that enables you to effortlessly craft responsive pricing tables and plan comparison tables. With its powerful and flexible real-time editor, you can swiftly design pricing tables, using multiple templates, to suit various WordPress themes. </div>
                                <div class="ap-plugin-key-feature"> Key Features: </div>
                                <ul class="ap-feature-list-cls-plugin-dec">
                                    <li class="ap-feature-list-li-plugin"> Real-time Pricing Table Editor </li>
                                    <li class="ap-feature-list-li-plugin"> Unlimited Color Options </li>
                                    <li class="ap-feature-list-li-plugin"> Create Team Showcases </li>
                                    <li class="ap-feature-list-li-plugin"> Translation Ready </li>
                                    <li class="ap-feature-list-li-plugin"> Responsive Pricing Tables </li>
                                    <li class="ap-feature-list-li-plugin"> Multi-Site Compatible </li>
                                </ul>
                                <div style="margin-top:40px;">
                                    <el-button class="el-button ap-btn ap-learnmore-btn arp-plugin" @click="goToLearnMore('arprice')" >
                                        <span class="ap-btn__label"> Learn More </span>
                                    </el-button>
                                    <?php
                                        $affiliatepress_plugin_file = 'arprice-responsive-pricing-table/arprice-responsive-pricing-table.php';
                                        $affiliatepress_plugin_path = WP_PLUGIN_DIR . '/' . $affiliatepress_plugin_file;

                                        if ( file_exists( $affiliatepress_plugin_path ) ) {
                                            if ( is_plugin_active( $affiliatepress_plugin_file ) ) {
                                                ?>
                                                <el-button class="el-button ap-btn ap-install-btn arp-plugin" disabled>
                                                    <span class="ap-btn__label">Activated</span>
                                                </el-button>
                                                <?php
                                            } else {
                                                ?>
                                                <el-button class="el-button ap-btn ap-install-btn arp-plugin" @click="affiliatepress_activate_plugins('arprice')" :class="(is_display_arprice_save_loader == '1') ? 'ap-btn--is-loader' : ''" :disabled="is_disabled">
                                                    <span class="ap-btn__label"> Activate </span> 
                                                    <div class="ap-btn--loader__circles">                    
                                                        <div></div>
                                                        <div></div>
                                                        <div></div>
                                                    </div>         
                                                </el-button>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <el-button class="el-button ap-btn ap-install-btn arp-plugin" @click="affiliatepress_download_plugins('arprice')" :class="(is_display_arprice_save_loader == '1') ? 'ap-btn--is-loader' : ''" :disabled="is_disabled">
                                                <span class="ap-btn__label"> Install </span> 
                                                <div class="ap-btn--loader__circles">                    
                                                        <div></div>
                                                        <div></div>
                                                        <div></div>
                                                    </div>         
                                            </el-button>
                                            <?php
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
					</el-col>
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