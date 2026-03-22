<?php
    if ( ! defined( 'ABSPATH' ) ) { exit; }
    global $AffiliatePress;
?>
<el-main class="ap-main-listing-card-container ap-default-card ap--is-page-non-scrollable-mob ap---manage-affiliate-page" id="ap-all-page-main-container">
    <el-row :gutter="12" type="flex" class="ap-head-wrap">
        <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="ap-head-left">
            <h1 class="ap-page-heading"><?php esc_html_e('Manage Affiliates', 'affiliatepress-affiliate-marketing'); ?></h1>
        </el-col>
        <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="ap-head-right">
            <div class="ap-hw-right-btn-group">              
                <el-button type="primary" @click="openAddAffiliate()" class="ap-btn--primary">
                    <span class="ap-btn__icon"><?php do_action('affiliatepress_common_svg_code','add_icon'); ?></span>
                    <span class="ap-btn__label"><?php esc_html_e('Add New', 'affiliatepress-affiliate-marketing'); ?></span>
                </el-button> 		
            </div>
        </el-col>
    </el-row>
    <div v-if="ap_first_page_loaded == '1'" class="ap-back-loader-container" id="ap-page-loading-loader">
        <div class="ap-back-loader"></div>
    </div>    
    <div v-if="ap_first_page_loaded == '0'" id="ap-main-container">        
        <div class="ap-table-filter">
            <el-row class="ap-table-filter-row" type="flex" :gutter="24">
                <el-col :xs="24" :sm="24" :md="24" :lg="11" :xl="7">
                    <el-row type="flex" :gutter="16">
                        <el-col :xs="24" :sm="24" :md="24" :lg="14" :xl="14">
                            <div class="ap-combine-field">
                                <el-select ref="selectAffUserRef" size="large" class="ap-form-control ap-remove-fields-close" v-model="affiliates_search.ap_affiliates_user"  filterable placeholder="<?php esc_html_e( 'Enter Affiliate Name', 'affiliatepress-affiliate-marketing'); ?>" @change="affiliatepress_get_existing_affiliate_details($event)" remote reserve-keyword :remote-method="get_affiliate_users" :loading="affiliatepress_user_loading" clearable >                                                               
                                    <el-option-group v-for="wp_user_list_cat in AffiliateUsersList" :key="wp_user_list_cat.category" :label="wp_user_list_cat.category">
                                        <el-option v-for="item in wp_user_list_cat.wp_user_data" :key="item.value" :label="item.label" :value="item.value" ></el-option>                                    
                                    </el-option-group>
                                </el-select>
                            </div>
                        </el-col>
                        <el-col :xs="24" :sm="24" :md="24" :lg="10" :xl="10">
                            <div class="ap-combine-field">
                            <el-select class="ap-form-control" size="large" v-model="affiliates_search.ap_affiliates_status" placeholder="<?php esc_html_e('Select Status', 'affiliatepress-affiliate-marketing'); ?>" :popper-append-to-body="false" popper-class="ap-el-select--is-with-navbar">
                                <el-option v-for="item in all_status" :key="item.value" :label="item.label" :value="item.value"></el-option>                        
                            </el-select>
                            </div>
                        </el-col>
                    </el-row>
                </el-col>
                <el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="12">
                    <el-button @click="applyFilter()" class="ap-btn--primary" plain type="primary" :disabled="is_apply_disabled">
                        <span class="ap-btn__label"><?php esc_html_e('Apply', 'affiliatepress-affiliate-marketing'); ?></span>
                    </el-button>   
                    <el-button @click="resetFilter" class="ap-btn--second" v-if="affiliates_search.ap_affiliates_user || affiliates_search.ap_affiliates_status != ''">
                        <span class="ap-btn__label"><?php esc_html_e('Reset', 'affiliatepress-affiliate-marketing'); ?></span>
                    </el-button>
                </el-col>
                <el-col :xs="24" :sm="24" :md="24" :lg="5" :xl="5">
                    <div class="ap-tf-btn-group import-export-tool">
                        <el-button @click="importAffiliateOpenDrawer()" class="ap-btn--primary" plain type="primary">
                            <span class="ap-btn__icon"><?php do_action('affiliatepress_common_svg_code','import_icon'); ?></span>
                            <span class="ap-btn__label"><?php esc_html_e('Import', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>   
                        <el-button @click="exportAffiliate()" class="ap-btn--primary" plain type="primary">
                            <span class="ap-btn__icon"><?php do_action('affiliatepress_common_svg_code','export_icon'); ?></span>
                            <span class="ap-btn__label"><?php esc_html_e('Export', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>   
                    </div>
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
                        <el-table ref="multipleTable" @selection-change="handleSelectionChange" @sort-change="handleSortChange" class="ap-manage-appointment-items" :data="items" :class="(is_display_loader == '1')?'ap-hidden-table':''">
                            <template #empty>
                                <div class="ap-data-empty-view">
                                    <div class="ap-ev-left-vector">
                                        <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                                        <div class="no-data-found-text"> <?php esc_html_e('No Data Found!', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </div>
                                </div>
                            </template>
                            <el-table-column type="selection"></el-table-column>
                            <el-table-column width="80" prop="ap_affiliates_id" label="<?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="ap_affiliates_id"> 
                                <template #default="scope">
                                    <span>#{{ scope.row.ap_affiliates_id }}</span>
                                </template>
                            </el-table-column>
                            <el-table-column prop="full_name" width="400" class-name="ap-affiliate-user-col-large"  label="<?php esc_html_e('Affiliate User', 'affiliatepress-affiliate-marketing'); ?>" sortable="true">
                                <template #default="scope">
                                    <div class="ap-table-column-avatar-with-detail">
                                    <el-image class="ap-table-column-avatar" :src="scope.row.affiliates_avatar"></el-image>
                                    <div class="ap-item__avatar-detail-col">
                                        <div class="ap-item_detail-label ap-affiliate-name-col">{{scope.row.full_name}} <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Affiliate Link', 'affiliatepress-affiliate-marketing'); ?>" placement="top">
                                                <el-button @click="copy_affiliate_link(scope.row.affiliates_link)" class="ap-btn--icon-only-box">
                                                    <span class="ap-small-btn-icon ap-edit-icon">
                                                        <?php do_action('affiliatepress_common_svg_code','link_icon'); ?>
                                                    </span>
                                                </el-button>
                                            </el-tooltip></div>
                                        <div class="ap-item_detail-data ap-affiliate-list-email">{{scope.row.user_email}}</div>
                                    </div>                                    
                                    </div>

                                </template>
                            </el-table-column>
                            <el-table-column class-name="ap-padding-left-cls ap-grid-status-align-center"  align="center" header-align="center" prop="status"  width="190" label="<?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?>">
                                <template #default="scope">                                        
                                    <div class="ap-table-status-dropdown-wrapper" :class="(scope.row.change_status_loader == 1) ? '__ap-is-loader-active' : ''">
                                        <div class="ap-status-loader-wrapper" v-if="scope.row.change_status_loader == 1" :class="((scope.row.ap_affiliates_status == '2') ? 'ap-status--pending' : '') || (scope.row.ap_affiliates_status == '1' ? 'ap-status--active' : '') || (scope.row.ap_affiliates_status == '3' ? 'ap-status--rejected' : '')">
                                            <el-image class="ap-status-loader" src="<?php echo esc_url(AFFILIATEPRESS_IMAGES_URL . '/status-loader.gif'); ?>" alt="<?php esc_attr_e('Loader', 'affiliatepress-affiliate-marketing'); ?>"></el-image>
                                        </div>
                                        <el-select  v-else class="ap-form-control ap-status-control" :class="((scope.row.ap_affiliates_status == '2') ? 'ap-status--pending' : '') || (scope.row.ap_affiliates_status == '1' ? 'ap-status--active' : '') || (scope.row.ap_affiliates_status == '3' ? 'ap-status--rejected' : '')" v-model="scope.row.ap_affiliates_status" placeholder="Select Status" @change="affiliatepress_change_status(scope.row.ap_affiliates_id,scope.$index,$event,scope.row.ap_affiliates_status)" popper-class="ap-status-dropdown-popper">
                                            <el-option-group label="<?php esc_html_e('Change status', 'affiliatepress-affiliate-marketing'); ?>">
                                                <el-option  v-for="item in all_status" :key="item.value" :label="item.label" :value="item.value"></el-option>
                                            </el-option-group>
                                        </el-select>
                                    </div>                                  
                                </template>
                            </el-table-column>         
                            <el-table-column align="right" header-align="right" prop="paid_earning" width="130" label="<?php esc_html_e('Paid Earnings', 'affiliatepress-affiliate-marketing'); ?>"></el-table-column>
                            <el-table-column class-name="ap-padding-left-col-20" align="right" header-align="right" width="150" prop="current_commission_rate" label="<?php esc_html_e('Commission Rate', 'affiliatepress-affiliate-marketing'); ?>">
                                <template #default="scope">
                                    <span>{{ scope.row.current_commission_rate }}</span>
                                </template>
                            </el-table-column>                        
                            <el-table-column class-name="ap-padding-right-col" align="right" header-align="right" prop="unpaid_earning" width="150" label="<?php esc_html_e('Unpaid Earnings', 'affiliatepress-affiliate-marketing'); ?>"></el-table-column>
                            <el-table-column class-name="ap-padding-left-col-20"  prop="total_visit"   min-width="130"  label="<?php esc_html_e('Total Visit', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="total_visit">
                                <template #default="scope">
                                    <span>
                                        <template v-if="scope.row.total_visit > 0">
                                            <a  class="ap-refrance-link"   @click="affiliatepress_affiliate_to_all_visit_show(scope.row.ap_affiliates_id)"> {{ scope.row.total_visit }}</a>
                                        </template>
                                        <template v-else>
                                            {{ scope.row.total_visit }}
                                        </template>
                                    </span>
                                </template>
                            </el-table-column>
                            <el-table-column prop="converted_user" width="150" label="<?php esc_html_e('Converted', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="converted_user"></el-table-column>
                            <el-table-column class-name="ap-action-column" prop="ap_affiliates_created_at" min-width="150" label="<?php esc_html_e('Date', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="ap_affiliates_created_at">
                                <template #default="scope">
                                    <span>{{ scope.row.affiliate_created_date_formated }}</span>
                                    <div class="ap-table-actions-wrap">
                                        <div class="ap-table-actions">
                                            <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Edit', 'affiliatepress-affiliate-marketing'); ?>" placement="top">
                                                <el-button @click="editAffiliate(scope.row.ap_affiliates_id,scope.$index,scope.row)" class="ap-btn--icon-without-box">
                                                    <span class="ap-small-btn-icon ap-edit-icon">
                                                        <?php do_action('affiliatepress_common_svg_code','edit_icon'); ?>                                                   
                                                    </span>
                                                </el-button>
                                            </el-tooltip>
                                            <el-popconfirm 
                                                    confirm-button-text="<?php esc_html_e('Yes', 'affiliatepress-affiliate-marketing'); ?>"
                                                    cancel-button-text="<?php esc_html_e('No', 'affiliatepress-affiliate-marketing'); ?>"
                                                    confirm-button-type="danger"
                                                    cancel-button-type="plain"
                                                    :hide-icon="true"
                                                    :placement="bottom"  
                                                    popper-class="el-popover ap-popconfirm-delete"                                                                                                      
                                                    title="<?php esc_html_e('Are you sure you want to delete this affiliate?', 'affiliatepress-affiliate-marketing'); ?>"
                                                    @confirm="deleteAffiliate(scope.row.ap_affiliates_id,scope.$index)"
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
                        <el-table ref="multipleTable" @selection-change="handleSelectionChange" @sort-change="handleSortChange" class="ap-manage-appointment-items" :data="items" :class="(is_display_loader == '1')?'ap-hidden-table':''" @row-click="affiliatepress_full_row_clickable">
                            <template #empty>
                                <div class="ap-data-empty-view">
                                    <div class="ap-ev-left-vector">
                                        <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                                        <div class="no-data-found-text"> <?php esc_html_e('No Data Found!', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </div>
                                </div>
                            </template>
                            <el-table-column type="expand" width="58">
                                <template slot-scope="scope" #default="scope">
                                <div class="ap-table-expand-view-wapper">
                                    <div class="ap-table-expand-view">
                                        <div class="ap-table-expand-view-inner">
                                            <div class="ap-table-expand-label"><?php esc_html_e('Total Visit', 'affiliatepress-affiliate-marketing'); ?></div>
                                            <div class="ap-table-expand-seprater">:</div>
                                            <div class="ap-table-expand-value">
                                                <template v-if="scope.row.total_visit > 0">
                                                    <a  class="ap-refrance-link"  @click="affiliatepress_affiliate_to_all_visit_show(scope.row.ap_affiliates_id)">{{ scope.row.total_visit }}</a>
                                                </template>
                                                <template v-else>
                                                    {{ scope.row.total_visit }}
                                                </template>
                                            </div>
                                        </div>
                                        <div class="ap-table-expand-view-inner">
                                            <div class="ap-table-expand-label"><?php esc_html_e('Converted', 'affiliatepress-affiliate-marketing'); ?></div>
                                            <div class="ap-table-expand-seprater">:</div>
                                            <div class="ap-table-expand-value">{{scope.row.converted_user}}</div>
                                        </div>  
                                        <div class="ap-table-expand-view-inner">
                                            <div class="ap-table-expand-label"><?php esc_html_e('Commission Rate', 'affiliatepress-affiliate-marketing'); ?></div>
                                            <div class="ap-table-expand-seprater">:</div>
                                            <div class="ap-table-expand-value">{{ scope.row.current_commission_rate }}</div>
                                        </div>
                                        <div class="ap-table-expand-view-inner">
                                            <div class="ap-table-expand-label"><?php esc_html_e('Date', 'affiliatepress-affiliate-marketing'); ?></div>
                                            <div class="ap-table-expand-seprater">:</div>
                                            <div class="ap-table-expand-value">{{ scope.row.affiliate_created_date_formated }}</div>
                                        </div>                                     
                                    </div>
                                </div>
                                </template>
                            </el-table-column>                        
                            <el-table-column width="40" type="selection"></el-table-column>
                            <el-table-column width="50" prop="ap_affiliates_id" label="<?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="ap_affiliates_id">
                                <template #default="scope">
                                    <span>#{{ scope.row.ap_affiliates_id }}</span>
                                </template>
                            </el-table-column>
                            <el-table-column prop="full_name" width="250" class-name="ap-affiliate-user-col-large"  label="<?php esc_html_e('Affiliate User', 'affiliatepress-affiliate-marketing'); ?>" sortable="true">
                                <template #default="scope">
                                    <div class="ap-table-column-avatar-with-detail">
                                    <el-image class="ap-table-column-avatar" :src="scope.row.affiliates_avatar"></el-image>
                                    <div class="ap-item__avatar-detail-col">
                                        <div class="ap-item_detail-label ap-affiliate-name-col">{{scope.row.full_name}} <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Affiliate Link', 'affiliatepress-affiliate-marketing'); ?>" placement="top">
                                                <el-button @click="copy_affiliate_link(scope.row.affiliates_link)" class="ap-btn--icon-only-box">
                                                    <span class="ap-small-btn-icon ap-edit-icon">
                                                        <?php do_action('affiliatepress_common_svg_code','link_icon'); ?>
                                                    </span>
                                                </el-button>
                                            </el-tooltip></div>
                                        <div class="ap-item_detail-data ap-affiliate-list-email">{{scope.row.user_email}}</div>
                                    </div>                                    
                                    </div>

                                </template>
                            </el-table-column>
                            <el-table-column class-name="ap-padding-left-cls ap-grid-status-align-center" align="center" header-align="center" prop="status" width="100" label="<?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?>">
                                <template #default="scope">                                        
                                    <div class="ap-table-status-dropdown-wrapper" :class="(scope.row.change_status_loader == 1 ? '__ap-is-loader-active ' : '') + (current_screen_size != 'desktop' ? 'ap-small-screen-status-dropdown' : '')">
                                        <div class="ap-status-loader-wrapper" v-if="scope.row.change_status_loader == 1" :class="((scope.row.ap_affiliates_status == '2') ? 'ap-status--pending' : '') || (scope.row.ap_affiliates_status == '1' ? 'ap-status--active' : '') || (scope.row.ap_affiliates_status == '3' ? 'ap-status--rejected' : '')">
                                            <el-image class="ap-status-loader" src="<?php echo esc_url(AFFILIATEPRESS_IMAGES_URL . '/status-loader.gif'); ?>" alt="<?php esc_attr_e('Loader', 'affiliatepress-affiliate-marketing'); ?>"></el-image>
                                        </div>
                                        <el-select v-else class="ap-form-control ap-status-control" :class="((scope.row.ap_affiliates_status == '2') ? 'ap-status--pending' : '') || (scope.row.ap_affiliates_status == '1' ? 'ap-status--active' : '') || (scope.row.ap_affiliates_status == '3' ? 'ap-status--rejected' : '')" v-model="scope.row.ap_affiliates_status" placeholder="Select Status" @change="affiliatepress_change_status(scope.row.ap_affiliates_id,scope.$index,$event,scope.row.ap_affiliates_status)" popper-class="ap-status-dropdown-popper">
                                            <el-option-group label="<?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?>">
                                                <el-option  v-for="item in all_status" :key="item.value" :label="item.label" :value="item.value"></el-option>
                                            </el-option-group>
                                        </el-select>
                                    </div>                                  
                                </template>
                            </el-table-column>     
                            <el-table-column align="center" prop="paid_earning" width="70" label="<?php esc_html_e('Paid', 'affiliatepress-affiliate-marketing'); ?>"></el-table-column>
                            <el-table-column class-name="ap-action-column" prop="unpaid_earning" min-width="150" width="150" label="<?php esc_html_e('Unpaid', 'affiliatepress-affiliate-marketing'); ?>">
                                <template #default="scope">
                                    <span>{{ scope.row.unpaid_earning }}</span>
                                    <div class="ap-table-actions-wrap">
                                        <div class="ap-table-actions">
                                            <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Edit', 'affiliatepress-affiliate-marketing'); ?>" placement="top">
                                                <el-button @click="editAffiliate(scope.row.ap_affiliates_id,scope.$index,scope.row)" class="ap-btn--icon-without-box">
                                                    <span class="ap-small-btn-icon ap-edit-icon">
                                                        <?php do_action('affiliatepress_common_svg_code','edit_icon'); ?>                                                   
                                                    </span>
                                                </el-button>
                                            </el-tooltip>
                                            <el-popconfirm 
                                                    confirm-button-text="<?php esc_html_e('Yes', 'affiliatepress-affiliate-marketing'); ?>"
                                                    cancel-button-text="<?php esc_html_e('No', 'affiliatepress-affiliate-marketing'); ?>"
                                                    confirm-button-type="danger"
                                                    cancel-button-type="plain"
                                                    :hide-icon="true"
                                                    :placement="bottom"  
                                                    popper-class="el-popover ap-popconfirm-delete"                                                                                                      
                                                    title="<?php esc_html_e('Are you sure you want to delete this affiliate?', 'affiliatepress-affiliate-marketing'); ?>"
                                                    @confirm="deleteAffiliate(scope.row.ap_affiliates_id,scope.$index)"
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
                <el-container v-if="multipleSelection.length > 0" class="ap-default-card ap-bulk-actions-card" >                    
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
            <el-container v-if="multipleSelection.length > 0 && current_screen_size == 'desktop'" class="ap-default-card ap-bulk-actions-card" >
                    
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
        <el-drawer modal-class="ap-add__drawer-main" :direction="drawer_direction" :withHeader="false" @close="resetModal('affiliates_form_data')" v-model="open_modal">    
            <div v-if="open_import_modal == false" class="ap-add__drawer">
                <div class="ap-dlt__header">
                    <div class="ap-dlt__heading" v-if="affiliates.ap_affiliates_id == ''"><?php esc_html_e('Add Affiliates', 'affiliatepress-affiliate-marketing'); ?></div>
                    <div class="ap-dlt__heading" v-else><?php esc_html_e('Edit Affiliates', 'affiliatepress-affiliate-marketing'); ?></div>
                </div>
                <div id="ap-drawer-body" class="ap-dlt__body">
                    <div class="ap-dlt__form_body">
                        <div class="ap-dlt__form_title"><?php esc_html_e('Affiliate Details', 'affiliatepress-affiliate-marketing'); ?></div>
                        <el-form ref="affiliates_form_data" :rules="rules" require-asterisk-position="right" :model="affiliates" label-position="top">
                            <div class="ap-single-field__form">
                                <el-form-item class="ap-combine-field ap-combine-field-search">
                                    <template #label>
                                        <label><span class="ap-form-label"><?php esc_html_e('Avatar', 'affiliatepress-affiliate-marketing'); ?></span></label>
                                    </template>
                                    <div class="ap-upload-component">
                                        <div class="ap-upload-control-upper">
                                            <el-upload :show-file-list="false" action="<?php echo wp_nonce_url(admin_url('admin-ajax.php') . '?action=affiliatepress_upload_affiliate_avatar', 'affiliatepress_upload_affiliate_avatar'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - esc_html is already used by wp_nonce_url function and it's false positive ?>" 
                                            class="ap-upload-control" ref="avatarRef" multiple="false" 
                                            :file-list="affiliate_image_list"
                                            :on-success="affiliatepress_upload_affiliate_avatar_func"
                                            :on-exceed="affiliatepress_image_upload_limit"
                                            :on-remove="affiliatepress_remove_affiliate_avatar"
                                            limit="1" :before-upload="checkUploadedFile">
                                                <span class="material-icons-round ap-upload-component__icon">cloud_upload</span>                                           
                                            </el-upload>
                                            <div class="ap-uploaded-avatar__preview" v-if="affiliates.avatar_url != ''">
                                                <span class="ap-avatar-close-icon" @click="affiliatepress_remove_affiliate_avatar">
                                                    <span class="material-icons-round">close</span>
                                                </span>
                                                <el-avatar shape="square" :src="affiliates.avatar_url" class="ap-uploaded-avatar__picture"></el-avatar>
                                            </div>                                    
                                        </div>
                                        <div class="ap-upload-component__text"><?php esc_html_e('Select avatar image (Max size: 1MB)', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </div>
                                </el-form-item>
                            </div>
                            <div v-if="affiliates.ap_affiliates_id == '' || affiliates.affiliate_user_name == ''" class="ap-single-field__form">
                                <el-form-item class="ap-combine-field ap-combine-field-search" prop="ap_affiliates_user_id">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('WordPress User', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>
                                    <el-select ref="selectRef" size="large" class="ap-form-control ap-remove-fields-close" v-model="affiliates.ap_affiliates_user_id" filterable placeholder="<?php esc_html_e( 'Start typing to fetch user.', 'affiliatepress-affiliate-marketing'); ?>" @change="affiliatepress_get_existing_user_details($event)" remote reserve-keyword	 :remote-method="get_wordpress_users" :loading="affiliatepress_user_loading" clearable>                                
                                        <el-option-group label="<?php esc_html_e( 'Create New User', 'affiliatepress-affiliate-marketing'); ?>">
                                            <el-option value="add_new" label="<?php esc_html_e( 'Create New', 'affiliatepress-affiliate-marketing'); ?>">
                                            </el-option>
                                        </el-option-group>                                
                                        <el-option-group v-for="wp_user_list_cat in wpUsersList" :key="wp_user_list_cat.category" :label="wp_user_list_cat.category">
                                            <el-option v-for="item in wp_user_list_cat.wp_user_data" :key="item.value" :label="item.label" :value="item.value" ></el-option>                                    
                                        </el-option-group>
                                    </el-select>
                                </el-form-item>                     
                            </div>
                            <div v-else class="ap-single-field__form">
                                <el-form-item  class="ap-combine-field ap-combine-field-disable" prop="affiliate_user_name">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('WordPress User', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>
                                    <el-input class="ap-form-control" type="text" :disabled="true" v-model="affiliates.affiliate_user_name" size="large" placeholder="<?php esc_html_e('Wordpress Username', 'affiliatepress-affiliate-marketing'); ?>" />
                                </el-form-item>                     
                            </div>
                            <div v-for="affiliate_field in affiliate_fields" class="ap-dynamic-fields">
                                
                                <div v-if="affiliate_field.ap_form_field_name == 'username' && affiliates.ap_affiliates_user_id == 'add_new'" class="ap-single-field__form">
                                    <el-form-item class="ap-combine-field" prop="username">
                                        <template #label>
                                            <span class="ap-form-label">{{affiliate_field.ap_field_label}}</span>
                                        </template>                
                                        <el-input class="ap-form-control" type="text" v-model="affiliates.username" size="large" :placeholder="affiliate_field.ap_field_placeholder" />
                                    </el-form-item>                     
                                </div>
                                <div v-if="affiliate_field.ap_form_field_name == 'firstname' && affiliates.ap_affiliates_user_id == 'add_new'" class="ap-single-field__form">
                                    <el-form-item class="ap-combine-field" prop="firstname">
                                        <template #label>
                                            <span class="ap-form-label">{{affiliate_field.ap_field_label}}</span>
                                        </template>                
                                        <el-input class="ap-form-control" type="text" v-model="affiliates.firstname" size="large" :placeholder="affiliate_field.ap_field_placeholder" />
                                    </el-form-item>                     
                                </div>
                                <div v-if="affiliate_field.ap_form_field_name == 'lastname' && affiliates.ap_affiliates_user_id == 'add_new'" class="ap-single-field__form">
                                    <el-form-item class="ap-combine-field" prop="lastname">
                                        <template #label>
                                            <span class="ap-form-label">{{affiliate_field.ap_field_label}}</span>
                                        </template>                
                                        <el-input class="ap-form-control" type="text" v-model="affiliates.lastname" size="large" :placeholder="affiliate_field.ap_field_placeholder" />
                                    </el-form-item>                     
                                </div>                                                 
                                <div v-if="affiliate_field.ap_form_field_name == 'email' && affiliates.ap_affiliates_user_id == 'add_new'" class="ap-single-field__form">
                                    <el-form-item class="ap-combine-field" prop="email">
                                        <template #label>
                                            <span class="ap-form-label">{{affiliate_field.ap_field_label}}</span>
                                        </template>                
                                        <el-input class="ap-form-control" type="text" v-model="affiliates.email" size="large" :placeholder="affiliate_field.ap_field_placeholder" />
                                    </el-form-item>                     
                                </div>
                                <div v-if="affiliate_field.ap_form_field_name == 'password' && affiliates.ap_affiliates_user_id == 'add_new'" class="ap-single-field__form">
                                    <el-form-item class="ap-combine-field" prop="password">
                                        <template #label>
                                            <span class="ap-form-label">{{affiliate_field.ap_field_label}}</span>
                                        </template>                
                                        <el-input class="ap-form-control" type="password" :show-password="true" v-model="affiliates.password" size="large" :placeholder="affiliate_field.ap_field_placeholder" />
                                    </el-form-item>                     
                                </div>
                                <div v-if="affiliate_field.ap_form_field_name == 'password' && affiliates.ap_affiliates_user_id == 'add_new' && confirm_password_field.is_display_confirm_password == 'true'" class="ap-single-field__form">
                                    <el-form-item class="ap-combine-field" prop="confirm_password">
                                        <template #label>
                                            <span class="ap-form-label">{{confirm_password_field.confirm_password_label}}</span>
                                        </template>                
                                        <el-input class="ap-form-control" type="password" :show-password="true" v-model="affiliates.confirm_password" size="large" :placeholder="confirm_password_field.confirm_password_placeholder" />
                                    </el-form-item>                     
                                </div>
                                <div v-if="affiliate_field.ap_form_field_name == 'ap_affiliates_payment_email' && affiliates.ap_affiliates_id == ''" class="ap-single-field__form">
                                    <el-form-item class="ap-combine-field" prop="ap_affiliates_payment_email_add">
                                        <template #label>
                                            <span class="ap-form-label">{{affiliate_field.ap_field_label}}</span>
                                        </template>                
                                        <el-input class="ap-form-control" type="text" v-model="affiliates.ap_affiliates_payment_email_add" size="large" :placeholder="affiliate_field.ap_field_placeholder" />
                                    </el-form-item>                     
                                </div>
                                <div v-if="affiliate_field.ap_form_field_name == 'ap_affiliates_payment_email' && affiliates.ap_affiliates_id != ''" class="ap-single-field__form">
                                    <el-form-item class="ap-combine-field" prop="ap_affiliates_payment_email_edit">
                                        <template #label>
                                            <span class="ap-form-label">{{affiliate_field.ap_field_label}}</span>
                                        </template>                
                                        <el-input class="ap-form-control" type="text" v-model="affiliates.ap_affiliates_payment_email_edit" size="large" :placeholder="affiliate_field.ap_field_placeholder" />
                                    </el-form-item>                     
                                </div>
                                <div v-if="affiliate_field.ap_form_field_name == 'ap_affiliates_website'" class="ap-single-field__form">
                                    <el-form-item class="ap-combine-field" prop="ap_affiliates_website">
                                        <template #label>
                                            <span class="ap-form-label">{{affiliate_field.ap_field_label}}</span>
                                        </template>                
                                        <el-input class="ap-form-control" type="text" v-model="affiliates.ap_affiliates_website" size="large" :placeholder="affiliate_field.ap_field_placeholder" />
                                    </el-form-item>                     
                                </div>
                                <div v-if="affiliate_field.ap_form_field_name == 'ap_affiliates_promote_us'" class="ap-single-field__form">
                                    <el-form-item class="ap-combine-field" prop="ap_affiliates_promote_us">
                                        <template #label>
                                            <span class="ap-form-label">{{affiliate_field.ap_field_label}}</span>
                                        </template>                
                                        <el-input class="ap-form-control" type="textarea" :rows="2" v-model="affiliates.ap_affiliates_promote_us" size="large" :placeholder="affiliate_field.ap_field_placeholder" />
                                    </el-form-item>                     
                                </div>                                                

                            </div>
                            <div class="ap-single-field__form">
                                <el-form-item class="ap-combine-field" prop="ap_affiliates_status">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-select class="ap-form-control" v-model="affiliates.ap_affiliates_status" placeholder="Select" size="large">
                                        <el-option v-for="item in all_status" :key="item.value" :label="item.label" :value="item.value"/>
                                    </el-select>
                                </el-form-item>                     
                            </div> 
                            <?php  
                                do_action('affiliatepress_backend_affiliate_extra_fields');
                            ?>
                            <div class="ap-single-field__form">
                                <el-form-item class="ap-combine-field" prop="ap_affiliates_note">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Note', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-input class="ap-form-control" maxlength="600" type="textarea" :rows="4" v-model="affiliates.ap_affiliates_note" size="large" placeholder="<?php esc_html_e('Add Affiliate Note Here', 'affiliatepress-affiliate-marketing'); ?>" />
                                </el-form-item>                     
                            </div>     
                            <div v-if="affiliates.ap_affiliates_id == ''" class="ap-single-field__form ap-single-switch ap-top-padding-top-10">
                                <el-form-item prop="ap_send_email" class="ap-combine-field">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Send Welcome Email', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-switch v-model="affiliates.ap_send_email"/>
                                </el-form-item>                     
                            </div>                    
                        </el-form>
                    </div>
                </div>
                <div class="ap-dlt__footer">
                    <div class="ap-dlt__footer-btn">
                        <el-button @click="closeModal('affiliates_form_data')" class="ap-btn--second ap-btn--big ap-margin-right--sec-btn">
                            <span class="ap-btn__label"><?php esc_html_e('Cancel', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>
                        <el-button :class="(savebtnloading) ? 'ap-btn--is-loader' : ''" @click="saveAffiliate('affiliates_form_data')" :disabled="is_disabled" class="ap-btn--primary ap-btn--big"  type="primary">
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
            <div v-else class="ap-add__drawer">
                <div class="ap-dlt__header">
                    <div class="ap-dlt__heading"><?php esc_html_e('Import Affiliates', 'affiliatepress-affiliate-marketing'); ?></div>            
                </div> 
                <div id="ap-drawer-body" class="ap-dlt__body">
                    <div v-if="import_loading == '1'" class="ap-generate-payout-loader-container">
                        <div class="ap-generate-payout-loader">
                            <el-progress type="dashboard" :percentage="complete_percentage" :color="(complete_percentage < 100)?'#7362f9':'#1CC985'"></el-progress>
                            <div v-if="complete_percentage < 100" class="ap-loader-progress-txt"><?php esc_html_e('Progress...', 'affiliatepress-affiliate-marketing'); ?></div>
                            <div v-else class="ap-loader-complete-txt ap-flex-center">
                                <div class="ap-loader-progress-txt"><?php esc_html_e('Total Affiliate :', 'affiliatepress-affiliate-marketing'); ?> <span v-html="total_count"></span></div>
                                <div class="ap-loader-progress-txt"><?php esc_html_e('Imported Affiliate :', 'affiliatepress-affiliate-marketing'); ?> <span v-html="import_count"></span></div>
                                <div class="ap-loader-progress-txt"><?php esc_html_e('Duplicate Affiliate :', 'affiliatepress-affiliate-marketing'); ?> <span v-html="duplicate_count"></span></div>
                                <div class="ap-loader-progress-txt">
                                    <el-button @click="closeModal('affiliates_form_data')" class="ap-btn--second ap-btn--big ap-margin-right--sec-btn">
                                        <span class="ap-btn__label"><?php esc_html_e('Cancel', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </el-button>
                                </div>
                            </div>
                        </div>
                    </div>             
                    <div class="ap-dlt__form_body" v-if="import_loading != '1'">               
                        <div class="ap-dlt__form_title"><?php esc_html_e('Upload csv file', 'affiliatepress-affiliate-marketing'); ?></div>
                        <div class="ap-single-field__form">
                            <el-form-item prop="ap_creative_name">               
                                <el-upload class="ap-simple-upload-form-control ap-combine-field-upload" ref="importFileRef" multiple="false" limit="1"  action="<?php echo wp_nonce_url(admin_url('admin-ajax.php') . '?action=affiliatepress_upload_affiliate_import_file', 'affiliatepress_upload_affiliate_import_file'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - esc_html is already used by wp_nonce_url function and it's false positive ?>"  
                                :file-list="import_file_list"
                                :on-success="affiliatepress_upload_import_file_func"
                                :on-exceed="affiliatepress_image_upload_limit"
                                :on-remove="affiliatepress_remove_import_file"
                                :before-upload="checkImportUploadedFile">
                                    <label class="ap-simple--file-upload">
                                        <span class="ap-fu__placeholder">
                                            <span class="ap-fu__icon"><?php do_action('affiliatepress_common_svg_code','file-upload-icon'); ?></span>
                                            <span class="ap-fu__text"><?php esc_html_e('Browse file to upload', 'affiliatepress-affiliate-marketing'); ?></span>
                                        </span>                                    
                                    </label> 
                                </el-upload> 
                            </el-form-item>  
                        </div>
                        <el-form v-if="import_file_name != ''" ref="affiliates_form_import" :rules="affiliatepress_affiliate_import_rules" require-asterisk-position="right" :model="affiliatepress_import_fields" label-position="top">
                            <div v-if="affiliatepress_import_field_data.length != 0" class="ap-import-field-data">
                                <el-row type="flex" class="ap-import-field-head">
                                    <el-col class="ap-import-field__left-area" :xs="24" :sm="12" :md="12" :lg="12" :xl="12">                                
                                        <div class="ap-head-fields"><?php esc_html_e('Affiliate Field', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </el-col>
                                    <el-col class="ap-import-field__right-area" :xs="24" :sm="12" :md="12" :lg="12" :xl="12">
                                        <div class="ap-head-fields ap-csv-column"><?php esc_html_e('CSV Column', 'affiliatepress-affiliate-marketing'); ?></div>                              
                                    </el-col>
                                </el-row>
                                <el-row v-for="import_field in affiliatepress_import_field_data" type="flex" class="ap-import-field-body">
                                    <el-col class="ap-import-field__left-area" :xs="24" :sm="12" :md="12" :lg="12" :xl="12">                                
                                        <div :class="(import_field.is_required == '1')?'ap-req-imp-fld':''" class="ap-import-fields-label">{{import_field.field_label}}</div>
                                    </el-col>
                                    <el-col class="ap-import-field__right-area" :xs="24" :sm="12" :md="12" :lg="12" :xl="12">
                                        <div class="ap-import-fields-select">
                                            <el-form-item :prop="import_field.field_key">                                        
                                                <el-select size="large" class="ap-form-control" v-model="affiliatepress_import_fields[import_field.field_key]" placeholder="Select" filterable>
                                                    <el-option label="<?php esc_html_e('- Ignore this field -', 'affiliatepress-affiliate-marketing'); ?>" value=""></el-option>
                                                    <el-option v-for="item in import_file_fields" :key="item.key" :label="item.value" :value="item.key"></el-option>
                                                </el-select>
                                            </el-form-item>
                                        </div>                              
                                    </el-col>
                                </el-row>
                                <el-row  type="flex" class="ap-import-field-body">
                                    <el-col class="ap-import-field__left-area" :xs="24" :sm="12" :md="12" :lg="12" :xl="12">                                
                                        <div class="ap-import-fields-label ap-req-imp-fld">Affiliate Default Status</div>
                                    </el-col>
                                    <el-col class="ap-import-field__right-area" :xs="24" :sm="12" :md="12" :lg="12" :xl="12">
                                        <div class="ap-import-fields-select">
                                            <el-form-item :prop="ap_affiliates_status">                                        
                                                <el-select class="ap-form-control" v-model="affiliatepress_import_fields['ap_affiliates_status']" placeholder="Select" size="large">
                                                    <el-option v-for="item in all_status" :key="item.value" :label="item.label" :value="item.value"/>
                                                </el-select>
                                            </el-form-item>
                                        </div>                              
                                    </el-col>
                                </el-row>                        
                            </div>
                        </el-form>
                    </div>
                </div>  
                <div class="ap-dlt__footer" v-if="import_loading != '1'">
                    <div class="ap-dlt__footer-btn">
                        <el-button @click="closeModal('affiliates_form_data')" class="ap-btn--second ap-btn--big ap-margin-right--sec-btn">
                            <span class="ap-btn__label"><?php esc_html_e('Cancel', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>
                        <el-button v-if="import_file_name != '' && import_loading == '0'" :class="(import_loading == '1') ? 'ap-btn--is-loader' : ''" @click="importAffiliate('affiliates_form_import')" :disabled="is_disabled" class="ap-btn--primary ap-btn--big"  type="primary">
                            <span class="ap-btn__label"><?php esc_html_e('Proceed', 'affiliatepress-affiliate-marketing'); ?></span>
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

