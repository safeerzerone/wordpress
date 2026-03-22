<?php
    if ( ! defined( 'ABSPATH' ) ) { exit; }
    global $AffiliatePress;
?>
<el-main class="ap-main-listing-card-container ap-default-card ap--is-page-non-scrollable-mob ap---manage-commisiions-page" id="ap-all-page-main-container">
    <el-row :gutter="12" type="flex" class="ap-head-wrap">
        <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="ap-head-left">
            <h1 class="ap-page-heading"><?php esc_html_e('Manage Commissions', 'affiliatepress-affiliate-marketing'); ?></h1>
        </el-col>
        <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="ap-head-right">
            <div class="ap-hw-right-btn-group">              
                <el-button type="primary" @click="open_modal = true" class="ap-btn--primary">
                    <span class="ap-btn__icon"><?php do_action('affiliatepress_common_svg_code','add_icon'); ?></span>
                    <span class="ap-btn__label"><?php esc_html_e('Add New', 'affiliatepress-affiliate-marketing'); ?></span>
                </el-button> 		
            </div>
        </el-col>
    </el-row>
    <div class="ap-back-loader-container" v-if="ap_first_page_loaded == '1'" id="ap-page-loading-loader">
        <div class="ap-back-loader"></div>
    </div>    
    <div v-if="ap_first_page_loaded == '0'" id="ap-main-container">        
        <div class="ap-table-filter">
            <el-row class="ap-table-filter-row" type="flex" :gutter="24">
                <el-col :xs="24" :sm="24" :md="24" :lg="18" :xl="13">
                    <el-row type="flex" :gutter="16">
                        <el-col :xs="24" :sm="24" :md="24" :lg="7" :xl="7">
                            <el-select ref="selectAffUserRef" size="large" class="ap-form-control ap-remove-fields-close" v-model="commissions_search.ap_affiliates_user" filterable placeholder="<?php esc_html_e( 'Enter Affiliate Name', 'affiliatepress-affiliate-marketing'); ?>" @change="affiliatepress_get_existing_affiliate_details($event)" remote reserve-keyword	 :remote-method="get_affiliate_users" :loading="affiliatepress_user_loading" clearable >                                                               
                                <el-option-group v-for="wp_user_list_cat in AffiliateUsersList" :key="wp_user_list_cat.category" :label="wp_user_list_cat.category">
                                    <el-option v-for="item in wp_user_list_cat.wp_user_data" :key="item.value" :label="item.label" :value="item.value" ></el-option>                                    
                                </el-option-group>
                            </el-select>
                        </el-col>
                        <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="12" :xl="12">   
                                <el-date-picker popper-class="ap-date-range-picker-widget-wrapper" value-format="YYYY-MM-DD" :format="ap_common_date_format" v-model="commissions_search.ap_commission_search_date" class="ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" type="daterange" size="large" :start-placeholder="affiliatepress_start_date" :end-placeholder="affiliatepress_end_date" :default-time="defaultTime"/>
                        </el-col> 
                        <el-col class="ap-padding-right-16" :xs="24" :sm="24" :md="24" :lg="5" :xl="5">   
                                <el-select class="ap-form-control" size="large" v-model="commissions_search.commission_status" placeholder="<?php esc_html_e('Select Status', 'affiliatepress-affiliate-marketing'); ?>" :popper-append-to-body="false" popper-class="ap-el-select--is-with-navbar">                            
                                    <el-option v-for="item in all_filter_commissions_status" :key="item.value" :label="item.text" :value="item.value"></el-option>                            
                                </el-select>
                        </el-col>                   
                    </el-row>
                </el-col>
                <el-col :xs="24" :sm="24" :md="24" :lg="6" :xl="6">
                        <el-button @click="applyFilter()" class="ap-btn--primary" plain type="primary" :disabled="is_apply_disabled">
                            <span class="ap-btn__label"><?php esc_html_e('Apply', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>
                        <el-button @click="resetFilter" class="ap-btn--second" v-if="commissions_search.ap_affiliates_user || (commissions_search.ap_commission_search_date && commissions_search.ap_commission_search_date.length > 0) || commissions_search.commission_status != ''">
                            <span class="ap-btn__label"><?php esc_html_e('Reset', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>
                </el-col>
            </el-row>
        </div>
        <el-row>
            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                <el-container class="ap-table-container" :class="(is_display_loader == '1')?'ap-loader_table_container':''">                
                    <div class="ap-back-loader-container" v-if="is_display_loader == '1'">
                        <div class="ap-back-loader"></div>
                    </div>          
                    <div v-if="current_grid_screen_size == 'desktop'" class="ap-tc__wrapper">
                            <el-table ref="multipleTable" @selection-change="handleSelectionChange" @sort-change="handleSortChange" :class="(is_display_loader == '1')?'ap-hidden-table':''" class="ap-manage-appointment-items" :data="items"> 
                                <template #empty>
                                    <div class="ap-data-empty-view">
                                        <div class="ap-ev-left-vector">
                                            <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                                            <div class="no-data-found-text"> <?php esc_html_e('No Data Found!', 'affiliatepress-affiliate-marketing'); ?></div>
                                        </div>
                                    </div>
                                </template>
                                <el-table-column type="selection"></el-table-column>
                                <el-table-column header-align="left" width="80" prop="ap_commission_id" sortable sort-by='ap_commission_id' label="<?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="ap_commission_id">
                                    <template #default="scope">
                                        <span>#{{ scope.row.ap_commission_id }}</span>
                                    </template>
                                </el-table-column>
                                
                                <el-table-column  prop="full_name"  width="250" label="<?php esc_html_e('Affiliate User', 'affiliatepress-affiliate-marketing'); ?>" sortable="true">
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

                                <el-table-column prop="ap_commission_source" width="150" label="<?php esc_html_e('Source', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <span>{{scope.row.source_plugin_name}}</span>
                                    </template>                             
                                </el-table-column>

                                <el-table-column prop="affiliatepress_commission_product" width="230" label="<?php esc_html_e('Product', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <span>{{scope.row.affiliatepress_commission_product}}</span>
                                    </template>                             
                                </el-table-column>                            

                                <el-table-column  prop="ap_commission_reference_id" width="90" label="<?php esc_html_e('Reference', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <span v-if="scope.row.commission_order_link == ''">-</span>
                                        <span v-else v-html="scope.row.commission_order_link"></span>
                                    </template>                            
                                </el-table-column>

                                <el-table-column class-name="ap-is-right" label-class-name="ap-is-right" width="130" prop="ap_commission_reference_amount"  label="<?php esc_html_e('Order Amount', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <span>{{scope.row.ap_formated_commission_reference_amount}}</span>
                                    </template>                                                        
                                </el-table-column>
                                <el-table-column class-name="ap-padding-left-cls ap-grid-status-align-center" prop="ap_referrer_url" align="center" header-align="center" width="190" label="<?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?>">
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
                                <el-table-column class-name="ap-is-right" label-class-name="ap-is-right" prop="ap_commission_amount" width="200" label="<?php esc_html_e('Commission Amount', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <span>{{scope.row.ap_formated_commission_amount}}</span>
                                    </template>                            
                                </el-table-column>
                                <el-table-column class-name="ap-action-column" prop="ap_commission_created_date" min-width="200" label="<?php esc_html_e('Date', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by='ap_commission_created_date'>
                                    <template #default="scope">
                                        <span>{{scope.row.commission_created_date_formated}}</span>
                                        <div class="ap-table-actions-wrap">
                                            <div class="ap-table-actions">
                                                <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('View Details', 'affiliatepress-affiliate-marketing'); ?>" placement="top">        
                                                        <el-button @click="commission_extra_details(scope.row.ap_commission_id,scope.$index,scope.row)" class="ap-btn--icon-without-box">
                                                            <span class="ap-small-btn-icon ap-edit-icon">
                                                                <?php do_action('affiliatepress_common_svg_code','details_action'); ?>                                                        
                                                            </span>
                                                        </el-button>
                                                </el-tooltip>
                                                <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Edit', 'affiliatepress-affiliate-marketing'); ?>" placement="top">        
                                                        <el-button @click="editCommission(scope.row.ap_commission_id,scope.$index,scope.row)" class="ap-btn--icon-without-box">
                                                            <span class="ap-small-btn-icon ap-edit-icon">
                                                                <?php do_action('affiliatepress_common_svg_code','edit_icon'); ?>                                                        
                                                            </span>
                                                        </el-button>
                                                </el-tooltip>
                                                <el-popconfirm 
                                                    confirm-button-text="<?php esc_html_e('Delete', 'affiliatepress-affiliate-marketing'); ?>"
                                                    cancel-button-text="<?php esc_html_e('Cancel', 'affiliatepress-affiliate-marketing'); ?>"
                                                    confirm-button-type="danger"
                                                    cancel-button-type="plain"
                                                    :hide-icon="true"
                                                    popper-class="el-popover ap-popconfirm-delete"
                                                    title="<?php esc_html_e('Are you sure you want to delete this commission?', 'affiliatepress-affiliate-marketing'); ?>"
                                                    @confirm="deleteCommission(scope.row.ap_commission_id,scope.$index)"
                                                    width="300">  
                                                    <template #reference>                   
                                                            <el-button class="ap-btn--icon-without-box ap-delete-icon">
                                                                <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="danger" content="<?php esc_html_e('Delete', 'affiliatepress-affiliate-marketing'); ?>" placement="top">         
                                                                    <span class="ap-small-btn-icon">
                                                                        <?php do_action('affiliatepress_common_svg_code','delete_icon'); ?>
                                                                    </span>
                                                                </el-tooltip>
                                                            </el-button>   
                                                    </template>                                         
                                                </el-popconfirm>
                                            </div>
                                        </div>
                                    </template>                            
                                </el-table-column>  
                            </el-table>
                    </div>
                    <div v-if="current_grid_screen_size != 'desktop'" class="ap-tc__wrapper ap-small-screen-table">
                        <el-table ref="multipleTable" @selection-change="handleSelectionChange" @sort-change="handleSortChange" :class="(is_display_loader == '1')?'ap-hidden-table':''" class="ap-manage-appointment-items" :data="items" @row-click="affiliatepress_full_row_clickable"> 
                            <template #empty>
                                <div class="ap-data-empty-view">
                                    <div class="ap-ev-left-vector">
                                        <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                                        <div class="no-data-found-text"> <?php esc_html_e('No Data Found!', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </div>
                                </div>
                            </template>
                            <el-table-column type="expand" width="42">
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
                            <el-table-column width="20" type="selection"></el-table-column>
                            <el-table-column width="80" prop="ap_commission_id" label="<?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by='ap_commission_id'>
                                <template #default="scope">
                                    <span>#{{ scope.row.ap_commission_id }}</span>
                                </template>
                            </el-table-column>
                            <el-table-column  prop="full_name"  width="180" label="<?php esc_html_e('Affiliate User', 'affiliatepress-affiliate-marketing'); ?>" sortable="true">
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
                            <el-table-column class-name="ap-padding-left-cls ap-grid-status-align-center" prop="status" align="center" header-align="center"  width="90" label="<?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">                                    
                                        <div class="ap-table-status-dropdown-wrapper" :class="(scope.row.change_status_loader == 1 ? '__ap-is-loader-active ' : '') + (current_screen_size != 'desktop' ? 'ap-small-screen-status-dropdown' : '')" >
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
                            <el-table-column class-name="ap-action-column"  align="right" header-align="right" prop="ap_commission_amount" width="110" label="<?php esc_html_e('Commission', 'affiliatepress-affiliate-marketing'); ?>">
                                <template #default="scope">
                                <span>{{scope.row.ap_formated_commission_amount}}</span>
                                </template>                            
                            </el-table-column> 
                            <el-table-column class-name="ap-action-column"  prop="ap_commission_created_date"  min-width="160" width="160" label="<?php esc_html_e('Date', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by='ap_commission_created_date'>
                                    <template #default="scope">
                                        <span>{{scope.row.commission_created_date_formated}}</span>
                                        <div class="ap-table-actions-wrap">
                                            <div class="ap-table-actions">
                                                <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('View Details', 'affiliatepress-affiliate-marketing'); ?>" placement="top">
                                                    <el-button @click="commission_extra_details(scope.row.ap_commission_id,scope.$index,scope.row)" class="ap-btn--icon-without-box">
                                                        <span class="ap-small-btn-icon ap-edit-icon">
                                                            <?php do_action('affiliatepress_common_svg_code','details_action'); ?>                                                        
                                                        </span>
                                                    </el-button>
                                                </el-tooltip>
                                                <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Edit', 'affiliatepress-affiliate-marketing'); ?>" placement="top">
                                                    <el-button @click="editCommission(scope.row.ap_commission_id,scope.$index,scope.row)" class="ap-btn--icon-without-box">
                                                        <span class="ap-small-btn-icon ap-edit-icon">
                                                            <?php do_action('affiliatepress_common_svg_code','edit_icon'); ?>                                                        
                                                        </span>
                                                    </el-button>
                                                </el-tooltip>
                                                <el-popconfirm 
                                                    confirm-button-text="<?php esc_html_e('Delete', 'affiliatepress-affiliate-marketing'); ?>"
                                                    cancel-button-text="<?php esc_html_e('Cancel', 'affiliatepress-affiliate-marketing'); ?>"
                                                    confirm-button-type="danger"
                                                    cancel-button-type="plain"
                                                    :hide-icon="true"
                                                    popper-class="el-popover ap-popconfirm-delete"
                                                    title="<?php esc_html_e('Are you sure you want to delete this commission?', 'affiliatepress-affiliate-marketing'); ?>"
                                                    @confirm="deleteCommission(scope.row.ap_commission_id,scope.$index)"
                                                    width="300">  
                                                    <template #reference>           
                                                        <el-button class="ap-btn--icon-without-box ap-delete-icon">
                                                            <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="danger" content="<?php esc_html_e('Delete', 'affiliatepress-affiliate-marketing'); ?>" placement="top">         
                                                            <span class="ap-small-btn-icon">
                                                                <?php do_action('affiliatepress_common_svg_code','delete_icon'); ?>
                                                            </span>
                                                            </el-tooltip>
                                                        </el-button>     
                                                    </template>                                         
                                                </el-popconfirm>
                                            </div>
                                        </div>
                                    </template>                            
                            </el-table-column>      
                        </el-table>
                    </div>            
                </el-container>
            </el-col>
        </el-row>
        <el-row class="ap-pagination" type="flex" v-if="items.length > 0">
            <el-col class="ap-align-center" v-if="current_screen_size != 'desktop'"  :xs="24" :sm="24" :md="24" :lg="12" :xl="12">
                <el-container v-if="multipleSelection.length > 0 && current_screen_size != 'desktop'" class="ap-default-card ap-bulk-actions-card">
                    <el-button class="ap-btn ap-btn--icon-without-box ap-bac__close-icon" @click="closeBulkAction">
                        <span class="material-icons-round">close</span>
                    </el-button>
                    <el-row type="flex" class="ap-bac__wrapper">
                        <el-col class="ap-bac__left-area" :xs="24" :sm="12" :md="12" :lg="12" :xl="12">
                            <span class="material-icons-round">check_circle</span>
                            <p>{{ multipleSelection.length }} <?php esc_html_e('Items Selected', 'affiliatepress-affiliate-marketing'); ?></p>
                        </el-col>
                        <el-col class="ap-bac__right-area" :xs="24" :sm="12" :md="12" :lg="12" :xl="12">
                            <el-select class="ap-form-control" v-model="bulk_action" placeholder="Select" popper-class="ap-dropdown--bulk-actions">
                                <el-option v-for="item in bulk_options" :key="item.value" :label="item.label" :value="item.value"></el-option>
                            </el-select>
                            <el-button @click="bulk_action_perform()" type="primary" class="ap-btn ap-bulk-btn--primary"><?php esc_html_e('Go', 'affiliatepress-affiliate-marketing'); ?></el-button>
                        </el-col>
                    </el-row>
                </el-container>
            </el-col>        
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
            <el-container v-if="multipleSelection.length > 0 && current_screen_size == 'desktop'" class="ap-default-card ap-bulk-actions-card">
                <el-button class="ap-btn ap-btn--icon-without-box ap-bac__close-icon" @click="closeBulkAction">
                    <span class="material-icons-round">close</span>
                </el-button>
                <el-row type="flex" class="ap-bac__wrapper">
                    <el-col class="ap-bac__left-area" :xs="24" :sm="12" :md="12" :lg="12" :xl="12">
                        <span class="material-icons-round">check_circle</span>
                        <p>{{ multipleSelection.length }} <?php esc_html_e('Items Selected', 'affiliatepress-affiliate-marketing'); ?></p>
                    </el-col>
                    <el-col class="ap-bac__right-area" :xs="24" :sm="12" :md="12" :lg="12" :xl="12">
                        <el-select class="ap-form-control" v-model="bulk_action" placeholder="Select" popper-class="ap-dropdown--bulk-actions">
                            <el-option v-for="item in bulk_options" :key="item.value" :label="item.label" :value="item.value"></el-option>
                        </el-select>
                        <el-button @click="bulk_action_perform()" type="primary" class="ap-btn ap-bulk-btn--primary"><?php esc_html_e('Go', 'affiliatepress-affiliate-marketing'); ?></el-button>
                    </el-col>
                </el-row>
            </el-container>                   
        </el-row>

        <el-drawer modal-class="ap-add__drawer-main" :direction="drawer_direction" :withHeader="false" @close="resetModal('commission_form_data')" v-model="open_modal">    
            <div class="ap-add__drawer">
                <div class="ap-dlt__header" v-if="commission_details_show">
                    <div class="ap-dlt__heading"><?php esc_html_e('View Details ', 'affiliatepress-affiliate-marketing'); ?></div>
                </div>
                <div class="ap-dlt__header" v-else>
                    <div class="ap-dlt__heading" v-if="commissions.ap_commission_id == ''"><?php esc_html_e('Add Commission', 'affiliatepress-affiliate-marketing'); ?></div>
                    <div class="ap-dlt__heading" v-else><?php esc_html_e('Edit Commission', 'affiliatepress-affiliate-marketing'); ?></div>
                </div>
                <div v-if="commission_details_show" id="ap-drawer-body" class="ap-dlt__body ap-from-drawer-details">
                    <div class="ap-back-loader-container" id="ap-page-loading-loader" v-if="is_display_commisison_details_loader == 1">
                        <div class="ap-back-loader"></div>
                    </div>   
                    <div class="ap-dlt__form_body"  v-if="is_display_commisison_details_loader == 0">
                        <div class="ap-dlt__form_title"><?php esc_html_e('Commission Details', 'affiliatepress-affiliate-marketing'); ?></div>
                        <div class="ap-dlt__form_drawer_info">
                            <div class="ap-flex-between ap-drawer-info-box">
                                <div class="ap-drawer-detail-title"><?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-drawer-detail-value">#{{commission_details.ap_commission_id}}</div>
                            </div>   
                            <div class="ap-flex-between ap-drawer-info-box">
                                <div class="ap-drawer-detail-title"><?php esc_html_e('Affiliate User', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-drawer-detail-value">{{commission_details.ap_affiliate_name}}</div>
                            </div>                                                                                                            
                            <div class="ap-flex-between ap-drawer-info-box">
                                <div class="ap-drawer-detail-title"><?php esc_html_e('Date', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-drawer-detail-value">{{commission_details.ap_commission_date}}</div>
                            </div> 
                            <div class="ap-flex-between ap-drawer-info-box">
                                <div class="ap-drawer-detail-title"><?php esc_html_e('Source', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-drawer-detail-value">{{commission_details.ap_commission_source}}</div>
                            </div> 
                            <div class="ap-flex-between ap-drawer-info-box">
                                <div class="ap-drawer-detail-title"><?php esc_html_e('Commission Amount', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-drawer-detail-value">{{commission_details.ap_commission_amount}}</div>
                            </div> 
                        </div>
                        <div class="ap-drawer-new-section" v-if="commission_details.ap_visit_id"></div>    
                        <div class="ap-dlt__form_title" v-if="commission_details.ap_visit_id"><?php esc_html_e('Visit Details', 'affiliatepress-affiliate-marketing'); ?></div>
                        <div class="ap-dlt__form_drawer_info" v-if="commission_details.ap_visit_id">
                            <div class="ap-flex-between ap-drawer-info-box">
                                <div class="ap-drawer-detail-title"><?php esc_html_e('Visit ID', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-drawer-detail-value">#{{commission_details.ap_visit_id}}</div>
                            </div> 
                            <div class="ap-flex-between ap-drawer-info-box">
                                <div class="ap-drawer-detail-title"><?php esc_html_e('Country', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-drawer-detail-value ap-country-wrap">
                                    {{ commission_details.ap_visit_country }}
                                    <el-image 
                                        v-if="commission_details.ap_visit_country_img_url"
                                        :src="commission_details.ap_visit_country_img_url"
                                        class="ap-visit-country-flag">
                                    </el-image>
                                </div>
                            </div> 
                            <div class="ap-flex-between ap-drawer-info-box">
                                <div class="ap-drawer-detail-title"><?php esc_html_e('IP Address', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-drawer-detail-value">{{commission_details.ap_visit_ip_address}}</div>
                            </div> 
                            <div class="ap-flex-between ap-drawer-info-box">
                                <div class="ap-drawer-detail-title"><?php esc_html_e('Landing URL', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-drawer-detail-value">{{commission_details.ap_visit_landing_url}}</div>
                            </div> 
                            <div class="ap-flex-between ap-drawer-info-box">
                                <div class="ap-drawer-detail-title"><?php esc_html_e('Browser', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-drawer-detail-value">{{commission_details.ap_visit_browser}}</div>
                            </div> 
                            <div class="ap-flex-between ap-drawer-info-box">
                                <div class="ap-drawer-detail-title"><?php esc_html_e('Referrer URL', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-drawer-detail-value" v-if="commission_details.ap_referrer_url == ''">-</div>
                                <div class="ap-drawer-detail-value" v-else>{{commission_details.ap_referrer_url}}</div>
                            </div> 
                        </div>
                    </div>
                </div>
                <div v-else id="ap-drawer-body" class="ap-dlt__body">
                    <div class="ap-dlt__form_body">
                        <div class="ap-dlt__form_title"><?php esc_html_e('Commission Details', 'affiliatepress-affiliate-marketing'); ?></div>
                        <el-form ref="commission_form_data" :rules="rules" require-asterisk-position="right" :model="commissions" label-position="top">

                            <div v-if="commissions.ap_commission_id == ''" class="ap-single-field__form">                    
                                <el-form-item class="ap-combine-field" prop="ap_affiliates_id">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Affiliate User', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>
                                    <el-select ref="selectAffUserRef" size="large" class="ap-form-control ap-remove-fields-close" v-model="commissions.ap_affiliates_id" filterable placeholder="<?php esc_html_e( 'Start typing to fetch user.', 'affiliatepress-affiliate-marketing'); ?>" @change="affiliatepress_get_existing_affiliate_details($event)" remote reserve-keyword	 :remote-method="get_affiliate_users" :loading="affiliatepress_user_loading" clearable>                                                               
                                        <el-option-group v-for="wp_user_list_cat in AffiliateUsersList" :key="wp_user_list_cat.category" :label="wp_user_list_cat.category">
                                            <el-option v-for="item in wp_user_list_cat.wp_user_data" :key="item.value" :label="item.label" :value="item.value" ></el-option>                                    
                                        </el-option-group>
                                    </el-select>
                                </el-form-item>                     
                            </div>
                            <div v-else class="ap-single-field__form">
                                <el-form-item class="ap-combine-field ap-combine-field-disable" prop="affiliate_user_name">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Affiliate User', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>
                                    <el-input class="ap-form-control" type="text" :disabled="true" v-model="commissions.affiliate_user_name" size="large" placeholder="<?php esc_html_e('Affiliate Name', 'affiliatepress-affiliate-marketing'); ?>" />
                                </el-form-item>                     
                            </div>
                            <div class="ap-single-field__form">
                                <el-form-item class="ap-combine-field" prop="ap_commission_amount">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Amount', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-input class="ap-form-control"  type="text"  v-model="commissions.ap_commission_amount"  size="large"  :placeholder="`${current_currency_symbol} ${amount_placeholder}`"
                                    :formatter="(value) => {
                                        if (!value && value !== 0) return '';
                                            return `${current_currency_symbol} ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                        }"
                                        :parser="(value) => { let parsed = value.replace(/[^0-9.]/g, ''); const parts = parsed.split('.'); if (parts.length > 2) { parsed = parts[0] + '.' + parts[1]; } return parsed; }"/>
                                </el-form-item>                     
                            </div>
                            <div v-if="commissions.ap_commission_id == ''" class="ap-single-field__form">
                                <el-form-item :class="(commissions.ap_commission_id == '')?'':'ap-combine-field-disable'" class="ap-combine-field" prop="ap_commission_reference_id">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Order ID', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-input :disabled="(commissions.ap_commission_id == '')?false:true" class="ap-form-control" type="text" v-model="commissions.ap_commission_reference_id" size="large" placeholder="<?php esc_html_e('Enter Order ID', 'affiliatepress-affiliate-marketing'); ?>"></el-input>
                                </el-form-item>                     
                            </div>
                            <div class="ap-single-field__form">
                                <el-form-item :class="(commissions.ap_commission_id == '')?'':'ap-combine-field-disable'" class="ap-combine-field" prop="ap_commission_order_amount">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Order Amount', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-input :disabled="(commissions.ap_commission_id == '') ? false : true"  class="ap-form-control"  type="text"  v-model="commissions.ap_commission_reference_amount"  size="large"  :placeholder="`${current_currency_symbol} ${ref_amount_placeholder}`"
                                    :formatter="(value) => {
                                        if (!value && value !== 0) return '';
                                            return `${current_currency_symbol} ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                        }"
                                        :parser="(value) => { let parsed = value.replace(/[^0-9.]/g, ''); const parts = parsed.split('.'); if (parts.length > 2) { parsed = parts[0] + '.' + parts[1]; } return parsed; }"/>
                                </el-form-item>                     
                            </div>                    
                            <div class="ap-single-field__form">
                                <el-form-item :class="(commissions.ap_commission_id == '')?'':'ap-combine-field-disable'" class="ap-combine-field" prop="ap_commission_source">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Source', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>
                                    <el-select :disabled="(commissions.ap_commission_id == '')?false:true" class="ap-form-control" @change="affiliatepress_change_source()" v-model="commissions.ap_commission_source" placeholder="<?php esc_html_e( 'Select Source', 'affiliatepress-affiliate-marketing'); ?>" size="large">
                                        <el-option v-for="item in all_plugin_integration" :key="item.plugin_value" :label="item.plugin_name" :value="item.plugin_value"/>
                                    </el-select>                            
                                </el-form-item>                     
                            </div>
                            <div v-if="commissions.ap_commission_id == ''" class="ap-single-field__form">
                                <el-form-item :class="(commissions.ap_commission_id == '')?'':'ap-combine-field-disable'" class="ap-combine-field" prop="ap_commission_product_ids">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Reference Product/Plan', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                                            
                                    <el-select ref="selectRef" :disabled="(commissions.ap_commission_id == '')?false:true" size="large" class="ap-form-control" v-model="commissions.ap_commission_product_ids" @change="affiliatepress_select_products($event)" filterable placeholder="<?php esc_html_e( 'Type to fetch users', 'affiliatepress-affiliate-marketing'); ?>" remote reserve-keyword :remote-method="get_affiliate_source_product" :loading="affiliatepress_user_loading">                                                               
                                        <el-option-group v-for="wp_user_list_cat in SourceProductsList" :key="wp_user_list_cat.category" :label="wp_user_list_cat.category">
                                            <el-option v-for="item in wp_user_list_cat.product_data" :key="item.value" :label="item.label" :value="item.value" ></el-option>                                    
                                        </el-option-group>
                                    </el-select>                            
                                </el-form-item>                     
                            </div>
                            <div class="ap-single-field__form">
                                <el-form-item class="ap-combine-field" prop="ap_commission_reference_detail">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Reference Detail', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-input class="ap-form-control" maxlength="600" type="textarea" :rows="4" v-model="commissions.ap_commission_reference_detail" size="large" placeholder="<?php esc_html_e('Enter reference details', 'affiliatepress-affiliate-marketing'); ?>" />
                                </el-form-item>                     
                            </div>
                            <div class="ap-single-field__form">
                                <el-form-item :class="(commissions.ap_commission_id == '')?'':'ap-combine-field-disable'" class="ap-combine-field" prop="ap_commission_created_date">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Date', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>
                                    <el-date-picker :disabled="(commissions.ap_commission_id == '')?false:true" v-model="commissions.ap_commission_created_date" class="ap-form-date-picker-control" size="large" value-format="YYYY-MM-DD" :format="ap_common_date_format" placeholder="<?php esc_html_e('Select Date', 'affiliatepress-affiliate-marketing'); ?>"></el-date-picker>
                                </el-form-item>                     
                            </div>
                            
                            <div class="ap-single-field__form">
                                <el-form-item class="ap-combine-field" prop="ap_commission_status">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-select class="ap-form-control" v-model="commissions.ap_commission_status" placeholder="Select" size="large">
                                        <el-option v-for="item in all_filter_commissions_status" :key="item.value" :label="item.text" :value="item.value"/>
                                    </el-select>
                                </el-form-item>                     
                            </div> 

                            <div class="ap-single-field__form">
                                <el-form-item class="ap-combine-field" prop="ap_commission_reference_detail">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Note', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-input class="ap-form-control" maxlength="600" type="textarea" :rows="4" v-model="commissions.ap_commission_note" size="large" placeholder="<?php esc_html_e('Add Commission Note Here', 'affiliatepress-affiliate-marketing'); ?>" />
                                </el-form-item>                     
                            </div>                      
                            
                        </el-form>
                    </div>
                </div>
                <div class="ap-dlt__footer">
                    <div class="ap-dlt__footer-btn">
                        <el-button v-if="commission_details_show"  @click="closedetailsModal('commission_form_data')" class="ap-btn--second ap-margin-right--sec-btn">
                            <span class="ap-btn__label"><?php esc_html_e('Close', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>
                        <el-button v-if="commission_details_show == false" @click="closeModal('commission_form_data')" class="ap-btn--second ap-margin-right--sec-btn">
                            <span class="ap-btn__label"><?php esc_html_e('Cancel', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>
                        <el-button v-if="commission_details_show == false" :class="(savebtnloading) ? 'ap-btn--is-loader' : ''" @click="saveCommission('commission_form_data')" :disabled="is_disabled" class="ap-btn--primary ap-btn--big"  type="primary">
                            <span class="ap-btn__label"><?php esc_html_e('Save', 'affiliatepress-affiliate-marketing'); ?></span>
                            <div class="ap-btn--loader__circles">
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>                    
                        </el-button>     
                    </div>
                </div>
            </div>    
        <el-drawer>

    </div>
</el-main>
<?php
    $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/affiliatepress_footer.php';
    $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_footer_content', $affiliatepress_load_file_name,1);
    require $affiliatepress_load_file_name;
?>
