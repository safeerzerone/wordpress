<?php
    if ( ! defined( 'ABSPATH' ) ) { exit; }
    global $AffiliatePress;
?>
<el-main class="ap-main-listing-card-container ap--is-page-non-scrollable-mob ap---dashboard-page" id="ap-all-page-main-container">
    <div class="ap-back-loader-container ap-dashboard-loader" v-if="ap_first_page_loaded == '1'" id="ap-page-loading-loader">
        <div class="ap-back-loader"></div>
    </div>    
    <div v-if="ap_first_page_loaded == '0'" id="ap-main-container" class="ap-dashboard-main">

        <div class="ap-back-loader-container ap-dashboard-loader" v-if="dashboard_change_date_loader == '1'" id="ap-page-loading-loader">
            <div class="ap-back-loader"></div>
        </div>   

        <div class="ap-default-card ap-dashboard-count">
            <el-row :gutter="12" type="flex" class="ap-head-wrap1 ap-dashboard-heading-row" v-if="affiliatepress_any_interation_active !=true">
                <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="ap-head-left">
                    <div class="ap-not-any-ntegration">
                       <div>
                            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 6H16.8486C16.3511 6 16 5.49751 16 5C16 3.34315 14.6569 2 13 2C11.3431 2 10 3.34315 10 5C10 5.49751 9.6488 6 9.1513 6H7C6.44771 6 6 6.44772 6 7V9.1513C6 9.6488 5.49751 10 5 10C3.34315 10 2 11.3431 2 13C2 14.6569 3.34315 16 5 16C5.49751 16 6 16.3511 6 16.8486V19C6 19.5523 6.44771 20 7 20H19C19.5523 20 20 19.5523 20 19V16.8486C20 16.3511 19.4975 16 19 16C17.3431 16 16 14.6569 16 13C16 11.3431 17.3431 10 19 10C19.4975 10 20 9.6488 20 9.1513V7C20 6.44772 19.5523 6 19 6Z" stroke="#6858E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                       </div>
                        <div>
                            <?php
                            echo sprintf( __('No integrations are currently enabled. Please visit %1$s to configure one.','affiliatepress-affiliate-marketing'),'<span class="ap-bold-font" @click="affiliatepress_redirect_integration_settings">' .'<span class="ap-content-decoration">' . esc_html__( 'Settings', 'affiliatepress-affiliate-marketing' ) .'</span> → ' .'<span class="ap-content-decoration">' . esc_html__( 'Integrations', 'affiliatepress-affiliate-marketing' ) .'</span>' . '</span>');//phpcs:ignore
                            ?>
                        </div>
                    </div>
                </el-col>           
            </el-row>
            <el-row :gutter="12" type="flex" class="ap-head-wrap1 ap-dashboard-heading-row">
                <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="ap-head-left">
                    <h1 class="ap-page-heading"><?php esc_html_e('Dashboard', 'affiliatepress-affiliate-marketing'); ?></h1>
                </el-col>  
                <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="ap-head-right">
                    <div class="dashboard-head-filter">
                        <el-date-picker :teleported="false" @change="affiliatepress_get_dashboard_detail()" popper-class="ap-date-range-picker-widget-wrapper" value-format="YYYY-MM-DD" :format="ap_common_date_format" v-model="dashboard_date_range" class="ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" type="daterange" size="large" :start-placeholder="affiliatepress_start_date" :end-placeholder="affiliatepress_end_date" :shortcuts="shortcuts" :default-time="defaultTime"/>
                    </div>
                </el-col>                      
            </el-row>
            <div class="ap-dashboard-count-box" v-if="dashboard_change_date_loader != '1'">
                <el-row :gutter="32" type="flex">
                    <el-col :xs="24" :sm="12" :md="8" :lg="8" :xl="8">
                        <div class="ap-dashboard-box">
                            <div class="ap-dashboard-box-icon">
                                <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="72" height="72" rx="36" fill="#A49AFF" fill-opacity="0.06"/>
                                    <g clip-path="url(#clip0_137_3777)">
                                    <path d="M44.1459 23.5581C40.51 19.2106 36.9211 16.3849 36.7703 16.267C35.951 15.6261 34.7501 16.2117 34.7501 17.2513V22.748C34.7501 25.5863 30.9939 26.5852 29.5862 24.1183C29.1485 23.3531 28.0841 23.2725 27.5331 23.9456C23.7504 28.562 20.3755 34.3827 20.3755 40.3755C20.3755 48.9909 27.3847 56 36 56C44.6154 56 51.6245 48.9909 51.6245 40.3755C51.6245 34.2276 48.0747 28.2566 44.1459 23.5581ZM29.1253 36.0007C29.1253 34.6218 30.2471 33.5008 31.6252 33.5008C33.0033 33.5008 34.1251 34.6218 34.1251 36.0007C34.1251 37.3795 33.0033 38.5006 31.6252 38.5006C30.2471 38.5006 29.1253 37.3795 29.1253 36.0007ZM32.6119 46.7675C32.1903 47.3109 31.4047 47.412 30.858 46.987C30.3127 46.5628 30.2151 45.7777 30.6385 45.2332L39.3882 33.9836C39.8124 33.4383 40.5975 33.3406 41.142 33.764C41.6873 34.1882 41.785 34.9734 41.3616 35.5179L32.6119 46.7675ZM40.3749 47.2503C38.9968 47.2503 37.875 46.1292 37.875 44.7504C37.875 43.3715 38.9968 42.2505 40.3749 42.2505C41.753 42.2505 42.8748 43.3715 42.8748 44.7504C42.8748 46.1292 41.753 47.2503 40.3749 47.2503Z" fill="#A49AFF"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_137_3777">
                                    <rect width="40" height="40" fill="white" transform="translate(16 16)"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                            </div>
                            <div>
                                <div class="ap-dashboard-box-title"><?php esc_html_e('Total Commission', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-dashboard-box-value" v-html="dashboard_total_commission"></div>
                            </div>
                        </div>
                    </el-col>
                   <el-col :xs="24" :sm="12" :md="8" :lg="8" :xl="8">
                        <div class="ap-dashboard-box">
                            <div class="ap-dashboard-box-icon">
                                <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="72" height="72" rx="36" fill="#FFA066" fill-opacity="0.06"/>
                                    <path d="M51 36C51 27.7157 44.2843 21 36 21C27.7157 21 21 27.7157 21 36C21 44.2843 27.7157 51 36 51V54.75C25.6447 54.75 17.25 46.3553 17.25 36C17.25 25.6447 25.6447 17.25 36 17.25C46.3553 17.25 54.75 25.6447 54.75 36C54.75 46.3553 46.3553 54.75 36 54.75V51C44.2843 51 51 44.2843 51 36Z" fill="#FFA066"/>
                                    <path d="M49.75 24.7498L24.7498 49.75L22.25 47.2502L47.2502 22.25L49.75 24.7498Z" fill="#FFA066"/>
                                    <circle cx="41.625" cy="41.625" r="3.125" fill="#FFA066"/>
                                    <circle cx="30.375" cy="30.375" r="3.125" fill="#FFA066"/>
                                </svg>
                            </div>  
                            <div>                          
                                <div class="ap-dashboard-box-title"><?php esc_html_e('Unpaid Commission', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-dashboard-box-value" v-html="dashboard_unpaid_commission"></div>
                            </div>
                        </div>
                    </el-col>
                   <el-col :xs="24" :sm="12" :md="8" :lg="8" :xl="8">
                        <div class="ap-dashboard-box">
                            <div class="ap-dashboard-box-icon">
                                <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="72" height="72" rx="36" fill="#3BC3A0" fill-opacity="0.06"/>
                                    <path d="M32.5 17C27.8056 17 24 20.8056 24 25.5C24 30.1945 27.8056 34 32.5 34C37.1944 34 41 30.1945 41 25.5C41 20.8056 37.1944 17 32.5 17Z" fill="#3BC3A0"/>
                                    <path d="M33.6601 37.8694C34.1661 37.1292 33.6795 36.0002 32.7875 36H30.3923C22.9959 36 17 42.0412 17 49.4934C17 52.5346 19.4469 55 22.4653 55H32.9198C33.8227 55 34.3083 53.8426 33.7843 53.1019C32.246 50.9289 31.3412 48.269 31.3412 45.3965C31.3412 42.6021 32.1975 40.009 33.6601 37.8694Z" fill="#3BC3A0"/>
                                    <path d="M45 35C50.5228 35 55 39.4772 55 45C55 50.5228 50.5228 55 45 55C39.4772 55 35 50.5228 35 45C35 39.4772 39.4772 35 45 35ZM45 41C42.4909 41 40.7829 42.4063 39.7646 43.6465C39.2549 44.2674 39 44.578 39 45.5C39 46.422 39.2549 46.7326 39.7646 47.3535C40.7829 48.5937 42.4909 50 45 50C47.5091 50 49.2171 48.5937 50.2354 47.3535C50.7451 46.7326 51 46.422 51 45.5C51 44.578 50.7451 44.2674 50.2354 43.6465C49.2171 42.4063 47.5091 41 45 41ZM45 43.3906C46.2427 43.3906 47.25 44.335 47.25 45.5C47.25 46.665 46.2427 47.6094 45 47.6094C43.7574 47.6094 42.75 46.665 42.75 45.5C42.75 44.335 43.7574 43.3906 45 43.3906Z" fill="#3BC3A0"/>
                                </svg>
                            </div> 
                            <div>                            
                                <div class="ap-dashboard-box-title"><?php esc_html_e('Visits', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-dashboard-box-value" v-html="dashboard_total_visits_count"></div>
                            </div>
                        </div>
                    </el-col>
                   <el-col :xs="24" :sm="12" :md="8" :lg="8" :xl="8">
                        <div class="ap-dashboard-box">
                            <div class="ap-dashboard-box-icon">
                                <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="72" height="72" rx="36" fill="#63BDF8" fill-opacity="0.06"/>
                                    <g clip-path="url(#clip0_137_3785)">
                                    <path d="M36.0002 35.9961C40.1423 35.9961 43.5002 32.6382 43.5002 28.4961C43.5002 24.354 40.1423 20.9961 36.0002 20.9961C31.858 20.9961 28.5002 24.354 28.5002 28.4961C28.5002 32.6382 31.858 35.9961 36.0002 35.9961Z" fill="#63BDF8"/>
                                    <path d="M20.5543 18.8339C20.0288 18.3195 19.2406 18.4052 18.7902 19.0053C17.0136 21.4203 16.0002 24.8783 16.0002 28.4935C16.0002 32.1087 17.0136 35.5668 18.7902 37.9817C19.0404 38.3246 19.3907 38.4961 19.7536 38.4961C20.0413 38.4961 20.3166 38.3818 20.5543 38.1674C21.0798 37.653 21.1548 36.7528 20.7044 36.1526C19.3032 34.2379 18.4899 31.4514 18.4899 28.4935C18.4899 25.5356 19.2907 22.7492 20.7044 20.8344C21.1423 20.2342 21.0798 19.334 20.5543 18.8196V18.8339Z" fill="#63BDF8"/>
                                    <path d="M24.5645 35.9661C24.9224 35.9661 25.2659 35.8311 25.5522 35.5612C26.1248 34.9914 26.1534 34.0467 25.6095 33.4468C24.493 32.2022 23.8488 30.4027 23.8488 28.4833C23.8488 26.5639 24.493 24.7494 25.6095 23.5198C26.1534 22.9199 26.1248 21.9602 25.5522 21.4054C24.9796 20.8355 24.0778 20.8655 23.5339 21.4654C21.9163 23.2498 21.0002 25.8141 21.0002 28.4983C21.0002 31.1825 21.9306 33.7318 23.5339 35.5312C23.8202 35.8461 24.1923 35.9961 24.5788 35.9961L24.5645 35.9661Z" fill="#63BDF8"/>
                                    <path d="M53.2102 19.005C52.7723 18.4053 51.9841 18.3196 51.4461 18.8336C50.9206 19.3477 50.8455 20.2473 51.2959 20.847C52.6972 22.7604 53.5104 25.5449 53.5104 28.5007C53.5104 31.4564 52.7097 34.2409 51.2959 36.1543C50.858 36.754 50.9206 37.6536 51.4461 38.1677C51.6838 38.3961 51.9715 38.4961 52.2468 38.4961C52.6096 38.4961 52.9599 38.3247 53.2102 37.982C54.9868 35.5689 56.0002 32.1133 56.0002 28.5007C56.0002 24.888 54.9868 21.4324 53.2102 19.0193V19.005Z" fill="#63BDF8"/>
                                    <path d="M46.4469 21.4354C45.8759 22.0052 45.8474 22.9499 46.3898 23.5497C47.5031 24.7944 48.1454 26.5939 48.1454 28.5133C48.1454 30.4327 47.5031 32.2472 46.3898 33.4768C45.8474 34.0767 45.8759 35.0364 46.4469 35.5912C46.7181 35.8611 47.0749 35.9961 47.4317 35.9961C47.8171 35.9961 48.1883 35.8311 48.4737 35.5312C50.0867 33.7468 51.0002 31.1825 51.0002 28.4983C51.0002 25.8141 50.0724 23.2648 48.4737 21.4654C47.9313 20.8655 47.0321 20.8355 46.4611 21.4054L46.4469 21.4354Z" fill="#63BDF8"/>
                                    <path d="M38.778 38.4961H33.2224C27.8613 38.4961 23.5002 43.2061 23.5002 48.9961C23.5002 51.4711 25.3752 53.4961 27.6668 53.4961H44.3335C46.6252 53.4961 48.5002 51.4711 48.5002 48.9961C48.5002 43.2061 44.1391 38.4961 38.778 38.4961Z" fill="#63BDF8"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_137_3785">
                                    <rect width="40" height="40" fill="white" transform="translate(16 16)"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                            </div>                             
                            <div>
                                <div class="ap-dashboard-box-title"><?php esc_html_e('Affiliates', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-dashboard-box-value" v-html="dashboard_total_affiliate_count"></div>
                            </div>
                        </div>
                    </el-col>
                   <el-col :xs="24" :sm="12" :md="8" :lg="8" :xl="8">
                        <div class="ap-dashboard-box">
                            <div class="ap-dashboard-box-icon">
                                <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="72" height="72" rx="36" fill="#DE85FF" fill-opacity="0.06"/>
                                    <path d="M28.4605 48.8373C28.3863 48.7676 28.343 48.6694 28.3406 48.5658C28.3383 48.4622 28.3772 48.3621 28.4482 48.2888L30.5231 46.1473L23 46L23.1429 53.7589L25.2192 51.6189C25.2882 51.5479 25.3817 51.5079 25.4792 51.5079H25.4807C25.5787 51.5083 25.6725 51.549 25.7413 51.6211C28.5121 54.5221 31.9978 55.6278 35.3046 54.6546C37.8442 53.9071 39.9439 52.0321 41 49.6719C39.6576 50.9045 38.0024 51.6564 36.204 51.8285C33.5336 52.0836 30.7835 51.0217 28.4605 48.8373L28.4605 48.8373Z" fill="#DE85FF"/>
                                    <path d="M50.4593 29.8053C50.4593 27.0177 48.1782 24.75 45.3744 24.75C42.5706 24.75 40.2871 27.0177 40.2871 29.8053C40.2871 32.5929 42.5692 34.8605 45.3744 34.8605C48.1795 34.8605 50.4593 32.5928 50.4593 29.8053Z" fill="#DE85FF"/>
                                    <path d="M45.376 35.8871C40.0033 35.8871 35.4453 39.8761 34.7737 45.1661C34.7028 45.7175 34.8101 45.8877 34.8448 45.9267C34.8879 45.9754 34.9768 46 35.109 46H55.6407C55.7723 46 55.861 45.9753 55.9043 45.9266C55.9386 45.888 56.0454 45.7205 55.9783 45.1872C55.3158 39.8851 50.7578 35.8871 45.376 35.8871Z" fill="#DE85FF"/>
                                    <path d="M30.957 22.0079C30.957 19.3844 28.7851 17.25 26.1152 17.25C23.4454 17.25 21.2734 19.3844 21.2734 22.0079C21.2734 24.6314 23.4454 26.768 26.1152 26.768C28.7851 26.768 30.957 24.6327 30.957 22.0079Z" fill="#DE85FF"/>
                                    <path d="M35.9999 35.3411C34.8936 30.9178 30.7809 27.7344 26.118 27.7344C21.0022 27.7344 16.6621 31.4878 16.0226 36.4652C15.9551 36.9841 16.0573 37.1443 16.0902 37.181C16.1313 37.2269 16.216 37.25 16.3419 37.25H34.4252C34.8772 36.5555 35.4064 35.9157 36 35.3411L35.9999 35.3411Z" fill="#DE85FF"/>
                                </svg>
                            </div>                             
                            <div>
                                <div class="ap-dashboard-box-title"><?php esc_html_e('Commission Count', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-dashboard-box-value" v-html="dashboard_total_commission_count"></div>
                            </div>
                        </div>
                    </el-col>
                   <el-col :xs="24" :sm="12" :md="8" :lg="8" :xl="8">
                        <div class="ap-dashboard-box">
                            <div class="ap-dashboard-box-icon">
                                <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="72" height="72" rx="36" fill="#FF7F68" fill-opacity="0.06"/>
                                    <g clip-path="url(#clip0_137_3833)">
                                    <path d="M43.1094 30.2188C36.0015 30.2188 30.2188 36.0015 30.2188 43.1094C30.2188 50.2173 36.0015 56 43.1094 56C50.2173 56 56 50.2173 56 43.1094C56 36.0015 50.2173 30.2188 43.1094 30.2188ZM43.1094 41.9375C45.048 41.9375 46.625 43.5145 46.625 45.4531C46.625 46.9789 45.6423 48.2675 44.2812 48.7529V51.3125H41.9375V48.7529C40.5764 48.2675 39.5938 46.9789 39.5938 45.4531H41.9375C41.9375 46.0997 42.4628 46.625 43.1094 46.625C43.7559 46.625 44.2812 46.0997 44.2812 45.4531C44.2812 44.8066 43.7559 44.2812 43.1094 44.2812C41.1708 44.2812 39.5938 42.7042 39.5938 40.7656C39.5938 39.2398 40.5764 37.9513 41.9375 37.4659V34.9062H44.2812V37.4659C45.6423 37.9513 46.625 39.2398 46.625 40.7656H44.2812C44.2812 40.1191 43.7559 39.5938 43.1094 39.5938C42.4628 39.5938 41.9375 40.1191 41.9375 40.7656C41.9375 41.4122 42.4628 41.9375 43.1094 41.9375Z" fill="#FF7F68"/>
                                    <path d="M29.0469 30.2188C36.275 30.2188 41.9375 27.13 41.9375 23.1875C41.9375 19.245 36.275 16 29.0469 16C21.8188 16 16 19.245 16 23.1875C16 27.13 21.8188 30.2188 29.0469 30.2188Z" fill="#FF7F68"/>
                                    <path d="M16 42.3718V44.2813C16 48.2238 21.8188 51.3125 29.0469 51.3125C29.4569 51.3125 29.8541 51.2748 30.2578 51.255C29.3589 49.8417 28.6885 48.2734 28.2937 46.5968C23.0668 46.4525 18.5283 44.8464 16 42.3718Z" fill="#FF7F68"/>
                                    <path d="M27.9313 44.2216C27.9045 43.853 27.875 43.4848 27.875 43.1094C27.875 41.8878 28.0351 40.7054 28.308 39.5662C23.0746 39.4244 18.5305 37.8174 16 35.3406V37.25C16 40.9834 21.2548 43.9174 27.9313 44.2216Z" fill="#FF7F68"/>
                                    <path d="M29.0469 37.25C29.0481 37.25 29.0491 37.2499 29.0505 37.2499C29.8234 35.4027 30.9469 33.7375 32.3421 32.3424C31.2838 32.4761 30.19 32.5625 29.0469 32.5625C23.483 32.5625 18.6505 30.9034 16 28.3093V30.2188C16 34.1613 21.8188 37.25 29.0469 37.25Z" fill="#FF7F68"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_137_3833">
                                    <rect width="40" height="40" fill="white" transform="translate(16 16)"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                            </div>                            
                            <div>
                                <div class="ap-dashboard-box-title"><?php esc_html_e('Total Revenue', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-dashboard-box-value" v-html="dashboard_paid_commission"></div>
                            </div>
                        </div>
                    </el-col>


                </el-row>
            </div>
        </div>
        <div v-if="dashboard_change_date_loader != '1'">
            <div class="ap-dashboard-chart-data">
                <el-row :gutter="32" type="flex">                
                    <el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
                        <div class="ap-default-card ap-dashboard-chart-up ap-dashboard-revenue-up">
                            <div class="ap-dashboard-chart-title"><?php esc_html_e('Commission Revenue', 'affiliatepress-affiliate-marketing'); ?></div>
                            <div class="ap-dashboard-chart">                            
                                <canvas class="ap-canvas-chart-data" id="revenue_chart" width="600" height="400"></canvas>                    
                            </div>
                        </div>
                    </el-col>
                    <el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
                        <div class="ap-default-card ap-dashboard-chart-up ap-dashboard-visits-up">
                            <div class="ap-dashboard-chart-title"><?php esc_html_e('Visits', 'affiliatepress-affiliate-marketing'); ?></div>
                            <div class="ap-dashboard-chart">                            
                                <canvas class="ap-canvas-chart-data" id="visit_chart" width="600" height="400"></canvas>                     
                            </div>
                        </div>
                    </el-col>  
                    <el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
                        <div class="ap-default-card ap-dashboard-chart-up">
                            <div class="ap-dashboard-chart-title"><?php esc_html_e('Affiliates', 'affiliatepress-affiliate-marketing'); ?></div>
                            <div class="ap-dashboard-chart">                            
                                <canvas class="ap-canvas-chart-data" id="affiliate_chart" width="600" height="400"></canvas>                     
                            </div>
                        </div>
                    </el-col>                                                
                </el-row>
            </div>
            <div v-if="current_grid_screen_size == 'desktop'" class="ap-default-card ap-dashboard-listing-data">
                <div class="ap-dash-listing-wrapper">
                    <div class="ap-dash-listing-title ap-dash-valuable-title">
                        <?php esc_html_e('Recent Commissions', 'affiliatepress-affiliate-marketing'); ?>
                    </div>
                    <div class="ap-table-container ap-listing-multi-without">
                        <el-table ref="multipleTable" class="ap-manage-appointment-items" :data="commissions"> 
                            <template #empty>
                                <div class="ap-data-empty-view">
                                    <div class="ap-ev-left-vector">
                                        <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                                        <div class="no-data-found-text"> <?php esc_html_e('No Data Found!', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </div>
                                </div>
                            </template>                   
                            <el-table-column header-align="center" align="center" min-width="90" prop="ap_commission_id" label="ID">
                                <template #default="scope">
                                    <span>#{{ scope.row.ap_commission_id }}</span>
                                </template>
                            </el-table-column>
                            <el-table-column  prop="full_name" min-width="180" label="<?php esc_html_e('Affiliate User', 'affiliatepress-affiliate-marketing'); ?>"></el-table-column>
                            <el-table-column prop="ap_commission_source" min-width="180" label="<?php esc_html_e('Source', 'affiliatepress-affiliate-marketing'); ?>">
                                <template #default="scope">
                                    <span>{{scope.row.source_plugin_name}}</span>
                                </template>                             
                            </el-table-column>
                            <el-table-column prop="ap_commission_source" width="180" label="<?php esc_html_e('Product', 'affiliatepress-affiliate-marketing'); ?>">
                                <template #default="scope">
                                    <span>{{scope.row.affiliatepress_commission_product}}</span>
                                </template>                             
                            </el-table-column>  
                            <el-table-column  prop="ap_commission_reference_id" width="90" label="<?php esc_html_e('Reference', 'affiliatepress-affiliate-marketing'); ?>">
                                <template #default="scope">
                                    <span v-html="scope.row.commission_order_link"></span>
                                </template>                            
                            </el-table-column>
                            <el-table-column align="right" header-align="right" prop="ap_commission_reference_amount" min-width="90" label="<?php esc_html_e('Order Amount', 'affiliatepress-affiliate-marketing'); ?>">
                                <template #default="scope">
                                    <span>{{scope.row.ap_formated_commission_reference_amount}}</span>
                                </template>                                                        
                            </el-table-column>
                            <el-table-column class-name="ap-padding-left-cls ap-grid-status-align-center" align="center" header-align="center" prop="ap_referrer_url" min-width="220" label="<?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?>">
                                <template #default="scope">                                    
                                    <div class="ap-table-status-dropdown-wrapper" :class="(scope.row.change_status_loader == 1) ? '__ap-is-loader-active' : ''">
                                        <div class="ap-status-loader-wrapper" v-if="scope.row.change_status_loader == 1" :class="(scope.row.ap_commission_status == '1' ? 'ap-status--unpaid' : '') || (scope.row.ap_commission_status == '4' ? 'ap-status--active' : '') || (scope.row.ap_commission_status == '2' ? 'ap-status--pending' : '') || (scope.row.ap_commission_status == '3' ? 'ap-status--rejected' : '')">
                                        <el-image class="ap-status-loader" src="<?php echo esc_url(AFFILIATEPRESS_IMAGES_URL . '/status-loader.gif'); ?>" alt="<?php esc_attr_e('Loader', 'affiliatepress-affiliate-marketing'); ?>"></el-image>
                                        </div>
                                        <div v-else>
                                            <div v-if="scope.row.ap_commission_status == '4'" class="ap-commission-status-active ap-status--active">
                                                <span>{{commission_status_paid}}</span>
                                            </div>
                                            <el-select  v-else class="ap-form-control ap-status-control" :class="(scope.row.ap_commission_status == '1' ? 'ap-status--unpaid' : '') || (scope.row.ap_commission_status == '4' ? 'ap-status--active' : '') || (scope.row.ap_commission_status == '2' ? 'ap-status--pending' : '') || (scope.row.ap_commission_status == '3' ? 'ap-status--rejected' : '')" v-model="scope.row.ap_commission_status" placeholder="Select Status" @change="affiliatepress_change_status(scope.row.ap_commission_id,scope.$index,$event,scope.row.ap_commission_status_org)" popper-class="ap-status-dropdown-popper">
                                                <el-option-group label="<?php esc_html_e('Change status', 'affiliatepress-affiliate-marketing'); ?>">
                                                    <el-option  v-for="item in all_commissions_status" :key="item.value" :label="item.text" :value="item.value"></el-option>
                                                </el-option-group>
                                            </el-select>
                                        </div>
                                    </div>                                    
                                </template>                            
                            </el-table-column>
                            <el-table-column align="right" header-align="right" prop="ap_commission_amount" min-width="120" label="<?php esc_html_e('Commission Amount', 'affiliatepress-affiliate-marketing'); ?>">
                                <template #default="scope">
                                    <span>{{scope.row.ap_formated_commission_amount}}</span>
                                </template>                            
                            </el-table-column>
                            <el-table-column class-name="ap-action-column" min-width="120" prop="ap_commission_created_date" min-width="110" label="<?php esc_html_e('Date', 'affiliatepress-affiliate-marketing'); ?>">
                                <template #default="scope">
                                    <span>{{scope.row.commission_created_date_formated}}</span>                                
                                </template>                            
                            </el-table-column>        
                        </el-table>
                    </div>
                </div>
            </div>
            <div v-if="current_grid_screen_size != 'desktop'" class="ap-default-card ap-dashboard-listing-data ap-dashboard-listing-data-mobile">
                <div class="ap-dash-listing-wrapper">
                    <div class="ap-dash-listing-title"><?php esc_html_e('Recent Commissions', 'affiliatepress-affiliate-marketing'); ?></div>
                    <div class="ap-table-container">
                        <div class="ap-tc__wrapper ap-small-screen-table">
                            <el-table ref="multipleTable" class="ap-manage-appointment-items" :data="commissions" @row-click="affiliatepress_full_row_clickable">
                                <template #empty>
                                    <div class="ap-data-empty-view">
                                        <div class="ap-ev-left-vector">
                                            <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                                            <div class="no-data-found-text"> <?php esc_html_e('No Data Found!', 'affiliatepress-affiliate-marketing'); ?></div>
                                        </div>
                                    </div>
                                </template>          
                                <el-table-column type="expand">
                                    <template slot-scope="scope" #default="scope">
                                    <div class="ap-table-expand-view-wapper">
                                        <div class="ap-table-expand-view">
                                            <div class="ap-table-expand-view-inner">
                                                <div class="ap-table-expand-label"><?php esc_html_e('Source', 'affiliatepress-affiliate-marketing'); ?></div>
                                                <div class="ap-table-expand-seprater">:</div>
                                                <div class="ap-table-expand-value">{{scope.row.source_plugin_name}}</div>
                                            </div>                          
                                            <div class="ap-table-expand-view-inner">
                                                <div class="ap-table-expand-label"><?php esc_html_e('Reference', 'affiliatepress-affiliate-marketing'); ?></div>
                                                <div class="ap-table-expand-seprater">:</div>
                                                <div class="ap-table-expand-value">
                                                    <span v-if="scope.row.commission_order_link == ''">-</span>
                                                    <span v-else v-html="scope.row.commission_order_link"></span>                                            
                                                </div>
                                            </div> 
                                            <div class="ap-table-expand-view-inner">
                                                <div class="ap-table-expand-label"><?php esc_html_e('Order Amount', 'affiliatepress-affiliate-marketing'); ?></div>
                                                <div class="ap-table-expand-seprater">:</div>
                                                <div class="ap-table-expand-value">{{scope.row.ap_formated_commission_reference_amount}}</div>
                                            </div> 
                                            <div class="ap-table-expand-view-inner">
                                                <div class="ap-table-expand-label"><?php esc_html_e('Product', 'affiliatepress-affiliate-marketing'); ?></div>
                                                <div class="ap-table-expand-seprater">:</div>
                                                <div class="ap-table-expand-value">{{scope.row.affiliatepress_commission_product}}</div>
                                            </div>                                                                                                               
                                        </div>
                                    </div>
                                    </template>
                                </el-table-column>                                                    
                                <el-table-column min-width="55" prop="ap_commission_id" label="<?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <span>#{{ scope.row.ap_commission_id }}</span>
                                    </template>
                                </el-table-column>
                                <el-table-column class-name="ap-action-column" prop="ap_commission_created_date" min-width="100" label="<?php esc_html_e('Date', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <span>{{scope.row.commission_created_date_formated}}</span>

                                    </template>                            
                                </el-table-column>                         
                                <el-table-column prop="full_name" width="200" label="<?php esc_html_e('Affiliate User', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <span>{{ scope.row.full_name }}</span>
                                    </template>
                                </el-table-column>
                                <el-table-column class-name="ap-padding-left-cls ap-grid-status-align-center" align="center" header-align="center" prop="status"  min-width="120" label="<?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?>">
                                        <template #default="scope">                                    
                                            <div class="ap-table-status-dropdown-wrapper" :class="(scope.row.change_status_loader == 1 ? '__ap-is-loader-active ' : '') + (current_screen_size != 'desktop' ? 'ap-small-screen-status-dropdown' : '')">
                                                <div class="ap-status-loader-wrapper" v-if="scope.row.change_status_loader == 1" :class="(scope.row.ap_commission_status == '1' ? 'ap-status--unpaid' : '') || (scope.row.ap_commission_status == '4' ? 'ap-status--active' : '') || (scope.row.ap_commission_status == '2' ? 'ap-status--pending' : '') || (scope.row.ap_commission_status == '3' ? 'ap-status--rejected' : '')">
                                                    <el-image class="ap-status-loader" src="<?php echo esc_url(AFFILIATEPRESS_IMAGES_URL . '/status-loader.gif'); ?>" alt="<?php esc_attr_e('Loader', 'affiliatepress-affiliate-marketing'); ?>"></el-image>
                                                </div>
                                                <div v-else>
                                                    <div v-if="scope.row.ap_commission_status == '4'" class="ap-commission-status-active ap-status--active">
                                                        <span>{{commission_status_paid}}</span>
                                                    </div>
                                                    <el-select v-else class="ap-form-control ap-status-control" :class="(scope.row.ap_commission_status == '1' ? 'ap-status--unpaid' : '') || (scope.row.ap_commission_status == '4' ? 'ap-status--active' : '') || (scope.row.ap_commission_status == '2' ? 'ap-status--pending' : '') || (scope.row.ap_commission_status == '3' ? 'ap-status--rejected' : '')" v-model="scope.row.ap_commission_status" placeholder="Select Status" @change="affiliatepress_change_status(scope.row.ap_commission_id,scope.$index,$event,scope.row.ap_commission_status_org)" popper-class="ap-status-dropdown-popper">
                                                        <el-option-group label="<?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?>">
                                                            <el-option  v-for="item in all_commissions_status" :key="item.value" :label="item.text" :value="item.value"></el-option>
                                                        </el-option-group>
                                                    </el-select>
                                                </div>
                                            </div>                                    
                                        </template> 
                                </el-table-column>                            
                                <el-table-column align="right" header-align="right" prop="ap_commission_amount" min-width="100" label="<?php esc_html_e('Commission', 'affiliatepress-affiliate-marketing'); ?>">
                                        <template #default="scope">
                                            <span>{{scope.row.ap_formated_commission_amount}}</span>
                                        </template>                            
                                </el-table-column>               
                            </el-table>    
                            
                        </div>                     
                    </div>
                </div>
            </div>
        </div>
    </div>    
</el-main>
<?php
    $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/affiliatepress_footer.php';
    $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_footer_content', $affiliatepress_load_file_name,1);
    require $affiliatepress_load_file_name;
?>


