<?php
    if ( ! defined( 'ABSPATH' ) ) { exit; }
    global $AffiliatePress;
?>
<el-main class="ap-main-listing-card-container ap-default-card ap--is-page-non-scrollable-mob ap---manage-visits-page" id="ap-all-page-main-container">
    <el-row :gutter="12" type="flex" class="ap-head-wrap">
        <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="ap-head-left">
            <h1 class="ap-page-heading"><?php esc_html_e('Manage Visits', 'affiliatepress-affiliate-marketing'); ?></h1>
        </el-col>        
    </el-row>
    <div class="ap-back-loader-container" v-if="ap_first_page_loaded == '1'" id="ap-page-loading-loader">
        <div class="ap-back-loader"></div>
    </div>    
    <div v-if="ap_first_page_loaded == '0'" id="ap-main-container">        
        <div class="ap-table-filter">
            <el-row class="ap-table-filter-row"  type="flex" :gutter="24">
                <el-col :xs="24" :sm="24" :md="24" :lg="18" :xl="13">
                    <el-row type="flex" :gutter="16">
                        <el-col :xs="24" :sm="24" :md="24" :lg="7" :xl="7">
                            <div class="ap-combine-field">
                                <el-select ref="selectAffUserRef" size="large" class="ap-form-control ap-remove-fields-close" v-model="visits_search.ap_affiliates_user" filterable placeholder="<?php esc_html_e( 'Enter Affiliate Name', 'affiliatepress-affiliate-marketing'); ?>" @change="affiliatepress_get_existing_affiliate_details($event)" remote reserve-keyword	 :remote-method="get_affiliate_users" :loading="affiliatepress_user_loading" clearable >                                                               
                                    <el-option-group v-for="wp_user_list_cat in AffiliateUsersList" :key="wp_user_list_cat.category" :label="wp_user_list_cat.category">
                                        <el-option v-for="item in wp_user_list_cat.wp_user_data" :key="item.value" :label="item.label" :value="item.value" ></el-option>                                    
                                    </el-option-group>
                                </el-select>
                            </div>
                        </el-col>
                        <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="11" :xl="11">    
                            <div class="ap-combine-field">
                                <el-date-picker popper-class="ap-date-range-picker-widget-wrapper" value-format="YYYY-MM-DD" :format="ap_common_date_format" v-model="visits_search.ap_visit_date" class="ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" type="daterange" size="large" :start-placeholder="affiliatepress_start_date" :end-placeholder="affiliatepress_end_date" :default-time="defaultTime"/>
                            </div>                    
                        </el-col>
                        <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="6" :xl="6">    
                            <div class="ap-combine-field">   
                                <el-select class="ap-form-control" size="large" v-model="visits_search.visit_type" placeholder="<?php esc_html_e('Conversion Status', 'affiliatepress-affiliate-marketing'); ?>" :popper-append-to-body="false" popper-class="ap-el-select--is-with-navbar">                            
                                    <el-option label="<?php esc_html_e('Converted', 'affiliatepress-affiliate-marketing'); ?>" value="converted"></el-option>
                                    <el-option label="<?php esc_html_e('Not converted', 'affiliatepress-affiliate-marketing'); ?>" value="not_converted"></el-option>
                                </el-select>
                            </div>                    
                        </el-col>             
                    </el-row>
                </el-col>   
                <el-col :xs="24" :sm="24" :md="24" :lg="6" :xl="6">
                        <el-button @click="applyFilter()" class="ap-btn--primary" plain type="primary" :disabled="is_apply_disabled">
                            <span class="ap-btn__label"><?php esc_html_e('Apply', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>
                        <el-button @click="resetFilter" class="ap-btn--second" v-if="visits_search.ap_affiliates_user || (visits_search.ap_visit_date && visits_search.ap_visit_date.length > 0) || visits_search.visit_type != ''">
                            <span class="ap-btn__label"><?php esc_html_e('Reset', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>
                </el-col>
            </el-row>
        </div>
        <el-row>
            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                <el-container class="ap-table-container ap-listing-multi-without" :class="(is_display_loader == '1')?'ap-loader_table_container':''">                
                    <div class="ap-back-loader-container" v-if="is_display_loader == '1'">
                        <div class="ap-back-loader"></div>
                    </div>                
                    <div v-if="current_grid_screen_size == 'desktop'" class="ap-tc__wrapper">
                            <el-table ref="multipleTable" @sort-change="handleSortChange" :class="(is_display_loader == '1')?'ap-hidden-table':''" class="ap-manage-appointment-items " :data="items"> 
                                <template #empty>
                                    <div class="ap-data-empty-view">
                                        <div class="ap-ev-left-vector">
                                            <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                                            <div class="no-data-found-text"> <?php esc_html_e('No Data Found!', 'affiliatepress-affiliate-marketing'); ?></div>
                                        </div>
                                    </div>
                                </template>
                                <el-table-column align="center" header-align="center" width="100" prop="ap_visit_id" label="<?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="ap_visit_id">
                                    <template #default="scope">
                                        <span>#{{ scope.row.ap_visit_id }}</span>
                                    </template>
                                </el-table-column>
                                <el-table-column  prop="ap_visit_created_date" min-width="100" label="<?php esc_html_e('Date', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by='ap_visit_created_date'>
                                    <template #default="scope">
                                        <span>{{ scope.row.visit_created_date_formated }}</span>
                                    </template>                                
                                </el-table-column>
                                <el-table-column  prop="full_name"  min-width="200" label="<?php esc_html_e('Affiliate User', 'affiliatepress-affiliate-marketing'); ?>" sortable="true">
                                    <template #default="scope">
                                        <el-popover trigger="click" width="350" popper-class="ap-affiliate-user-details-popover" :placement="(is_rtl == 'is_rtl') ? 'left-start' : 'right-start'" :visible="userPopoverVisible">
                                            <div class="ap-affiliate-user-details-container">
                                                <div class="ap-status-loader-wrapper" v-if="is_get_user_data_loader == 1">
                                                    <el-image class="ap-status-loader" src="<?php echo esc_url(AFFILIATEPRESS_IMAGES_URL . '/status-loader.gif'); ?>" alt="<?php esc_attr_e('Loader', 'affiliatepress-affiliate-marketing'); ?>"></el-image>
                                                </div>
                                                <div v-else>
                                                    <div class="ap-user-details-heading">
                                                        <div><?php esc_html_e('User Details', 'affiliatepress-affiliate-marketing'); ?></div>
                                                        <div @click="editUserclosePopover()" class="ap-close-popup"><?php do_action('affiliatepress_common_svg_code','popup_close'); ?></div>
                                                    </div>
                                                    <div class="ap-user-details-content" v-if="show_user_details == '1'">
                                                        <div class="ap-user-details-row">
                                                            <div class="ap-user-details-label"><?php esc_html_e('Username', 'affiliatepress-affiliate-marketing'); ?></div>
                                                            <div class="ap-user-details-separtor">:</div>
                                                            <div class="ap-user-details-value">{{affiliate_user_details.affiliate_user_name}}</div>
                                                        </div>
                                                        <div class="ap-user-details-row">
                                                            <div class="ap-user-details-label"><?php esc_html_e('Email Address', 'affiliatepress-affiliate-marketing'); ?></div>
                                                            <div class="ap-user-details-separtor">:</div>
                                                            <div class="ap-user-details-value">{{affiliate_user_details.affiliate_user_email}}</div>
                                                        </div>
                                                        <div class="ap-user-details-row">
                                                            <div class="ap-user-details-label"><?php esc_html_e('Name', 'affiliatepress-affiliate-marketing'); ?></div>
                                                            <div class="ap-user-details-separtor">:</div>
                                                            <div class="ap-user-details-value">{{affiliate_user_details.affiliate_user_full_name}}</div>
                                                        </div>
                                                        <?php
                                                            $affiliatepress_user_extra_details = "";
                                                            $affiliatepress_user_extra_details = apply_filters('affiliatepress_user_extra_details_add',$affiliatepress_user_extra_details);
                                                            echo $affiliatepress_user_extra_details;//phpcs:ignore
                                                        ?>
                                                        <a class="ap-affiliate-edit-user" :href="affiliate_user_details.affiliate_user_edit_link" target="_blank">
                                                            <div><?php esc_html_e('Edit User', 'affiliatepress-affiliate-marketing');?></div>
                                                        </a>
                                                    </div>
                                                    <div class="ap-user-details-content" v-else>
                                                        <div class="ap-not-wp-user">{{affiliatepress_wordpress_user_delete}}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <template #reference>
                                                <span class="ap-user_details" @click="affiliatepress_get_affiliate_user_details(scope.row.affiliatepress_affiliate_id,scope.row.affiliatepress_affiliate_user_id)">{{ scope.row.full_name }}</span>
                                            </template>    
                                        </el-popover>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="ap_visit_ip_address" min-width="90" label="<?php esc_html_e('IP Address', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <div class="ap-visit-ip-wrapper">
                                            <el-tooltip  v-if="scope.row.ap_visit_country_img_url" popper-class="ap--popover-tool-tip"  show-after="300"  effect="dark" :content="scope.row.ap_visit_country"  placement="top" >
                                                <el-image  :src="scope.row.ap_visit_country_img_url" class="ap-visit-country-flag"
                                                ></el-image>
                                            </el-tooltip>
                                            <span class="ap-visit-ip-address">
                                                {{ scope.row.ap_visit_ip_address }}
                                            </span>
                                        </div>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="ap_visit_landing_url" min-width="250" label="<?php esc_html_e('Landing URL', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <div v-if="scope.row.ap_visit_landing_url"  class="ap-url-wrapper"   :class="{ clickable: scope.row._hasOverflow }" @click="scope.row._hasOverflow ? (scope.row._expanded = true) : null" >
                                            <div class="ap-url-text" :class="{ expanded: scope.row._expanded }"  :ref="el => checkOverflow(el, scope.row)">{{ scope.row.ap_visit_landing_url }}</div>
                                            <a  v-if="scope.row._hasOverflow && !scope.row._expanded"  class="ap-more-inline ap-refrance-link"   @click.stop="scope.row._expanded = true">...</a>
                                        </div>
                                        <span v-else>-</span>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="ap_referrer_url" min-width="150" label="<?php esc_html_e('Referrer URL', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <div   v-if="scope.row.ap_referrer_url"  class="ap-url-wrapper" :class="{ clickable: scope.row._ref_hasOverflow }" @click="scope.row._ref_hasOverflow ? (scope.row._ref_expanded = true) : null" >
                                            <div   class="ap-url-text" :class="{ expanded: scope.row._ref_expanded }" :ref="el => checkOverflow(el, scope.row, 'ref')">{{ scope.row.ap_referrer_url }}</div>
                                            <a v-if="scope.row._ref_hasOverflow && !scope.row._ref_expanded" class="ap-more-inline ap-refrance-link" @click.stop="scope.row._ref_expanded = true" >...</a>
                                        </div>
                                        <span v-else>-</span>
                                    </template>
                                </el-table-column>
                                <el-table-column align="center" prop="ap_commission_id" min-width="120" label="<?php esc_html_e('Converted', 'affiliatepress-affiliate-marketing'); ?>" sort-by="ap_commission_id" sortable>
                                    <template #default="scope">
                                        <span v-if="scope.row.ap_commission_id == 0 || scope.row.ap_commission_id == ''">
                                            -                                       
                                        </span>
                                        <span v-else>
                                            <?php do_action('affiliatepress_common_svg_code','right_icon'); ?>                                        
                                        </span>                                    
                                    </template>                        
                                </el-table-column>
                            </el-table>
                    </div>
                    <div v-if="current_grid_screen_size != 'desktop'" class="ap-tc__wrapper ap-small-screen-table">
                        <el-table ref="multipleTable" @selection-change="handleSelectionChange" @sort-change="handleSortChange" class="ap-manage-appointment-items" :data="items" :class="(is_display_loader == '1')?'ap-hidden-table':''" @row-click="affiliatepress_full_row_clickable">
                            <template #empty>
                                    <div class="ap-data-empty-view">
                                        <div class="ap-ev-left-vector">
                                            <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                                            <div class="no-data-found-text"> <?php esc_html_e('No Data Found!', 'affiliatepress-affiliate-marketing'); ?></div>
                                        </div>
                                    </div>
                                </template>
                            <el-table-column type="expand" width="72">
                                <template slot-scope="scope" #default="scope">
                                <div class="ap-table-expand-view-wapper">
                                    <div class="ap-table-expand-view ap-tablet-view-visit">
                                        <div class="ap-table-expand-view-inner ap-table-expand-view-inner-full">
                                            <div class="ap-table-expand-label"><?php esc_html_e('Landing URL', 'affiliatepress-affiliate-marketing'); ?></div>
                                            <div class="ap-table-expand-seprater">:</div>
                                            <div class="ap-table-expand-value">
                                                <span v-if="scope.row.ap_visit_landing_url == ''">-</span>
                                                <span v-else v-html="scope.row.ap_visit_landing_url"></span>                                            
                                            </div>
                                        </div> 
                                        <div class="ap-table-expand-view-inner ap-table-expand-view-inner-full">
                                            <div class="ap-table-expand-label"><?php esc_html_e('Referrer URL', 'affiliatepress-affiliate-marketing'); ?></div>
                                            <div class="ap-table-expand-seprater">:</div>
                                            <div class="ap-table-expand-value">                                        
                                                <span v-if="scope.row.ap_referrer_url">{{ scope.row.ap_referrer_url }}</span>
                                                <span v-else>-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                </template>
                            </el-table-column>                                                
                            <el-table-column min-width="55" prop="ap_visit_id" label="<?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="ap_visit_id">
                                <template #default="scope">
                                    <span>#{{ scope.row.ap_visit_id }}</span>
                                </template>
                            </el-table-column>
                            <el-table-column  prop="ap_visit_created_date" min-width="100" label="<?php esc_html_e('Date', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by='ap_visit_created_date'>
                                    <template #default="scope">
                                        <span>{{ scope.row.visit_created_date_formated }}</span>
                                    </template>                                
                            </el-table-column> 
                            <el-table-column  prop="full_name"  min-width="120" label="<?php esc_html_e('Affiliate User', 'affiliatepress-affiliate-marketing'); ?>" sortable="true">
                                    <template #default="scope">
                                        <el-popover trigger="click" width="350" popper-class="ap-affiliate-user-details-popover" placement="bottom"  :visible="userPopoverVisible" ref="fields_useredit_popover">
                                            <div class="ap-affiliate-user-details-container">
                                                <div class="ap-status-loader-wrapper" v-if="is_get_user_data_loader == 1">
                                                    <el-image class="ap-status-loader" src="<?php echo esc_url(AFFILIATEPRESS_IMAGES_URL . '/status-loader.gif'); ?>" alt="<?php esc_attr_e('Loader', 'affiliatepress-affiliate-marketing'); ?>"></el-image>
                                                </div>
                                                <div v-else>
                                                    <div class="ap-user-details-heading">
                                                        <div><?php esc_html_e('User Details', 'affiliatepress-affiliate-marketing'); ?></div>
                                                        <div @click="editUserclosePopover()" class="ap-close-popup"><?php do_action('affiliatepress_common_svg_code','popup_close'); ?></div>
                                                    </div>
                                                    <div class="ap-user-details-content" v-if="show_user_details == '1'">
                                                        <div class="ap-user-details-row">
                                                            <div class="ap-user-details-label"><?php esc_html_e('Username', 'affiliatepress-affiliate-marketing'); ?></div>
                                                            <div class="ap-user-details-separtor">:</div>
                                                            <div class="ap-user-details-value">{{affiliate_user_details.affiliate_user_name}}</div>
                                                        </div>
                                                        <div class="ap-user-details-row">
                                                            <div class="ap-user-details-label"><?php esc_html_e('Email Address', 'affiliatepress-affiliate-marketing'); ?></div>
                                                            <div class="ap-user-details-separtor">:</div>
                                                            <div class="ap-user-details-value">{{affiliate_user_details.affiliate_user_email}}</div>
                                                        </div>
                                                        <div class="ap-user-details-row">
                                                            <div class="ap-user-details-label"><?php esc_html_e('Name', 'affiliatepress-affiliate-marketing'); ?></div>
                                                            <div class="ap-user-details-separtor">:</div>
                                                            <div class="ap-user-details-value">{{affiliate_user_details.affiliate_user_full_name}}</div>
                                                        </div>
                                                        <?php
                                                            $affiliatepress_user_extra_details = "";
                                                            $affiliatepress_user_extra_details = apply_filters('affiliatepress_user_extra_details_add',$affiliatepress_user_extra_details);
                                                            echo $affiliatepress_user_extra_details;//phpcs:ignore
                                                        ?>
                                                        <a class="ap-affiliate-edit-user" :href="affiliate_user_details.affiliate_user_edit_link" target="_blank">
                                                            <div><?php esc_html_e('Edit User', 'affiliatepress-affiliate-marketing');?></div>
                                                        </a>
                                                    </div>
                                                    <div class="ap-user-details-content" v-else>
                                                        <div class="ap-not-wp-user">{{affiliatepress_wordpress_user_delete}}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <template #reference>
                                                <span class="ap-user_details" @click="affiliatepress_get_affiliate_user_details(scope.row.affiliatepress_affiliate_id,scope.row.affiliatepress_affiliate_user_id)">{{ scope.row.full_name }}</span>
                                            </template>    
                                        </el-popover>
                                    </template>
                                </el-table-column>
                            <el-table-column min-width="55" prop="ap_visit_ip_address"  label="<?php esc_html_e('IP Address', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <div class="ap-visit-ip-wrapper">
                                            <el-tooltip  v-if="scope.row.ap_visit_country_img_url" popper-class="ap--popover-tool-tip ap-tab-view-tooltip"  show-after="300"  effect="dark" :content="scope.row.ap_visit_country"  placement="top" >
                                                <el-image  :src="scope.row.ap_visit_country_img_url" class="ap-visit-country-flag"
                                                ></el-image>
                                            </el-tooltip>
                                            <span v-else class="ap-visit-country-flag ap-empty-image"></span>
                                            <span class="ap-visit-ip-address">
                                                {{ scope.row.ap_visit_ip_address }}
                                            </span>
                                        </div>
                                    </template>
                                </el-table-column>
                            <el-table-column align="center" prop="ap_commission_id" width="100" label="<?php esc_html_e('Converted', 'affiliatepress-affiliate-marketing'); ?>" sort-by="ap_commission_id" sortable>
                                <template #default="scope">
                                    <span v-if="scope.row.ap_commission_id == 0 || scope.row.ap_commission_id == ''">
                                        -
                                    </span>
                                    <span v-else>
                                        <?php do_action('affiliatepress_common_svg_code','right_icon'); ?>                                        
                                    </span>                                    
                                </template>                        
                            </el-table-column>                         

                        </el-table>                    
                    </div>                 
                </el-container>
            </el-col>
        </el-row>

        <el-row class="ap-pagination" type="flex" v-if="items.length > 0"> 
            <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" v-if="pagination_count != 1 && pagination_count != 0">
                <div class="ap-pagination-left">
                    <p><?php esc_html_e('Showing', 'affiliatepress-affiliate-marketing'); ?> {{ items.length }}&nbsp; <?php esc_html_e('out of', 'affiliatepress-affiliate-marketing'); ?> &nbsp;{{ totalItems }}</p>
                    <div class="ap-pagination-per-page">
                        <p><?php esc_html_e('Per Page', 'affiliatepress-affiliate-marketing'); ?></p>
						<el-select v-model="pagination_length_val" placeholder="Select" @change="changePaginationSize($event)" size="large" class="ap-form-control" popper-class="ap-pagination-dropdown">
							<el-option v-for="item in pagination_val" :key="item.text" :label="item.text" :value="item.value"></el-option>
						</el-select>
					</div>
                </div>
            </el-col>
            <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="ap-pagination-nav" v-if="pagination_count != 1 && pagination_count != 0">
                <el-pagination @size-change="handleSizeChange" @current-change="handleCurrentChange" v-model:current-page="currentPage" background layout="prev, pager, next" :total="totalItems" :page-size="perPage"></el-pagination>
            </el-col>
        </el-row>
    </div>
</el-main>
<?php
    $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/affiliatepress_footer.php';
    $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_footer_content', $affiliatepress_load_file_name,1);
    require $affiliatepress_load_file_name;
?>


