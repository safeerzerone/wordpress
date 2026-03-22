<?php
    if ( ! defined( 'ABSPATH' ) ) { exit; }
    global $AffiliatePress;
?>
<el-main class="ap-main-listing-card-container ap-default-card ap--is-page-non-scrollable-mob ap---manage-payout-page" id="ap-all-page-main-container">
    <el-row :gutter="12" type="flex" class="ap-head-wrap">
        <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="ap-head-left">
            <h1 class="ap-page-heading"><?php esc_html_e('Manage Payout', 'affiliatepress-affiliate-marketing'); ?></h1>
        </el-col>
        <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="ap-head-right">
            <div class="ap-hw-right-btn-group">              
                <el-button type="primary" @click="open_modal = true" class="ap-btn--primary">
                    <span class="ap-btn__icon"><?php do_action('affiliatepress_common_svg_code','add_icon'); ?></span>
                    <span class="ap-btn__label"><?php esc_html_e('Generate New', 'affiliatepress-affiliate-marketing'); ?></span>
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
                <el-col :xs="24" :sm="24" :md="24" :lg="13" :xl="9">
                    <el-row type="flex" :gutter="16">
                        <el-col :xs="24" :sm="24" :md="24" :lg="16" :xl="16">
                            <div class="ap-combine-field">
                                <el-date-picker popper-class="ap-date-range-picker-widget-wrapper" value-format="YYYY-MM-DD" :format="ap_common_date_format" v-model="payout_search.ap_payout_date" class="ap-form-date-range-control ap-form-full-width-control ap-padding-right-16" type="daterange" size="large" :start-placeholder="affiliatepress_start_date" :end-placeholder="affiliatepress_end_date" :default-time="defaultTime" placement="bottom-start"/>                        
                            </div>
                        </el-col>
                        <el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
                            <div class="ap-combine-field">
                                <el-select class="ap-form-control" size="large" v-model="payout_search.ap_payout_type" placeholder="<?php esc_html_e('Select Status', 'affiliatepress-affiliate-marketing'); ?>" :popper-append-to-body="false" popper-class="ap-el-select--is-with-navbar">
                                    <el-option v-for="item in payout_types" :key="item.value" :label="item.text" :value="item.value"></el-option>                        
                                </el-select>
                            </div>
                        </el-col>
                    </el-row>
                </el-col>
                <el-col :xs="24" :sm="24" :md="24" :lg="9" :xl="6">
                    <el-button @click="loadPayouts()" class="ap-btn--primary" plain type="primary" :disabled="is_apply_disabled">
                        <span class="ap-btn__label"><?php esc_html_e('Apply', 'affiliatepress-affiliate-marketing'); ?></span>
                    </el-button>
                    <el-button @click="resetFilter" class="ap-btn--second" v-if="(payout_search.ap_payout_date && payout_search.ap_payout_date.length > 0) || payout_search.ap_payout_type!=''">
                        <span class="ap-btn__label"><?php esc_html_e('Reset', 'affiliatepress-affiliate-marketing'); ?></span>
                    </el-button>
                </el-col>
            </el-row>
        </div>
        <el-row>
            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                <el-container class="ap-table-container ap-payout-table ap-listing-multi-without" :class="(is_display_loader == '1')?'ap-loader_table_container':''">                
                    <div class="ap-back-loader-container" v-if="is_display_loader == '1'">
                        <div class="ap-back-loader"></div>
                    </div>                
                    <div v-if="current_grid_screen_size == 'desktop'" class="ap-tc__wrapper">
                            <el-table ref="multipleTable" @sort-change="handleSortChange" class="ap-manage-appointment-items" :class="(is_display_loader == '1')?'ap-hidden-table':''" :data="items">         
                                <template #empty>
                                    <div class="ap-data-empty-view">
                                        <div class="ap-ev-left-vector">
                                            <?php do_action('affiliatepress_common_svg_code','empty_view'); ?>
                                            <div class="no-data-found-text"> <?php esc_html_e('No Data Found!', 'affiliatepress-affiliate-marketing'); ?></div>
                                        </div>
                                    </div>
                                </template>                   
                                <el-table-column align="center" header-align="center" width="80" prop="ap_payout_id" label="<?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="ap_payout_id">
                                    <template #default="scope">
                                        <span>#{{ scope.row.ap_payout_id }}</span>
                                    </template>
                                </el-table-column>

                                <el-table-column prop="ap_payout_upto_date_formated" width="210" label="<?php esc_html_e('Payout Date Upto', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="ap_payout_upto_date">
                                    <template #default="scope">
                                    <div>{{ scope.row.ap_payout_upto_date_formated }}</div>
                                    </template>
                                </el-table-column>   

                                <el-table-column align="right" header-align="right" prop="ap_formated_payout_amount" width="250" label="<?php esc_html_e('Payment', 'affiliatepress-affiliate-marketing'); ?>" sortable :sort-method="(a, b) => a.ap_payout_amount - b.ap_payout_amount"></el-table-column>

                                <el-table-column width="250" prop="ap_payout_total_affiliate" align="center" header-align="center" label="<?php esc_html_e('Total Affiliates', 'affiliatepress-affiliate-marketing'); ?>"></el-table-column>

                                <el-table-column width="250" prop="paid_earning" align="center" header-align="center" label="<?php esc_html_e('Paid/Unpaid Affiliates', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                    <div>{{scope.row.paid_affiliate_count}}/{{scope.row.unpaid_affiliate_count}}</div>
                                    </template>                            
                                </el-table-column> 

                                <el-table-column  prop="complete_percentage" width="350" label="<?php esc_html_e('Progress', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                    <div>
                                            <el-progress :percentage="scope.row.complete_percentage" color="#7362F9" />
                                    </div>
                                    </template>
                                </el-table-column>

                                <el-table-column class-name="ap-action-column" prop="payout_type" min-width="200" label="<?php esc_html_e('Payout Type', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                    <div>{{scope.row.payout_type}}</div>
                                    <div class="ap-table-actions-wrap">
                                            <div class="ap-table-actions">
                                                <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Edit', 'affiliatepress-affiliate-marketing'); ?>" placement="top">
                                                    <el-button @click="editPayoutCall(scope.row.ap_payout_id,scope.$index,scope.row)" class="ap-btn--icon-without-box">
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
                                                        title="<?php esc_html_e('Are you sure you want to delete this payout?', 'affiliatepress-affiliate-marketing'); ?>"
                                                        @confirm="deletePayout(scope.row.ap_payout_id,scope.$index)"
                                                        width="280">  
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
                            <el-table-column type="expand" width="72">
                                <template slot-scope="scope" #default="scope">
                                <div class="ap-table-expand-view-wapper">
                                    <div class="ap-table-expand-view">
                                        <div class="ap-table-expand-view-inner">
                                            <div class="ap-table-expand-label"><?php esc_html_e('Total Affiliates', 'affiliatepress-affiliate-marketing'); ?></div>
                                            <div class="ap-table-expand-seprater">:</div>
                                            <div class="ap-table-expand-value">{{scope.row.ap_payout_total_affiliate}}</div>
                                        </div>
                                        <div class="ap-table-expand-view-inner">
                                            <div class="ap-table-expand-label"><?php esc_html_e('Payout Type', 'affiliatepress-affiliate-marketing'); ?></div>
                                            <div class="ap-table-expand-seprater">:</div>
                                            <div class="ap-table-expand-value">
                                                <span v-if="scope.row.payout_type == ''">-</span>
                                                <span v-else v-html="scope.row.payout_type"></span>                                            
                                            </div>
                                        </div> 
                                        <div class="ap-table-expand-view-inner ap-table-expand-view-inner-full">
                                            <div class="ap-table-expand-label"><?php esc_html_e('Progress', 'affiliatepress-affiliate-marketing'); ?></div>
                                            <div class="ap-table-expand-seprater">:</div>
                                            <div class="ap-table-expand-value">
                                                <el-progress :percentage="scope.row.complete_percentage" color="#7362F9" />
                                            </div>
                                        </div>                                                                       
                                    </div>
                                </div>
                                </template>
                            </el-table-column>                        
                            <el-table-column width="35" type="selection"></el-table-column>
                            <el-table-column min-width="60" prop="ap_payout_id" label="<?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="ap_payout_id">
                                <template #default="scope">
                                    <span>#{{ scope.row.ap_payout_id }}</span>
                                </template>
                            </el-table-column>
                            <el-table-column prop="ap_payout_upto_date_formated" width="120" label="<?php esc_html_e('Payout Date', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="ap_payout_upto_date">
                                <template #default="scope">
                                    <div>{{ scope.row.ap_payout_upto_date_formated }}</div>
                                </template>
                            </el-table-column> 

                            <el-table-column  align="right" header-align="right" prop="ap_formated_payout_amount" min-width="70" label="<?php esc_html_e('Payment', 'affiliatepress-affiliate-marketing'); ?>" sortable :sort-method="(a, b) => a.ap_payout_amount - b.ap_payout_amount"></el-table-column>

                            <el-table-column class-name="ap-action-column" prop="paid_earning" align="center" header-align="center" min-width="70" label="<?php esc_html_e('Paid/Unpaid', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                    <div>{{scope.row.paid_affiliate_count}}/{{scope.row.unpaid_affiliate_count}}</div>
                                    <div class="ap-table-actions-wrap">
                                            <div class="ap-table-actions">
                                                <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Edit', 'affiliatepress-affiliate-marketing'); ?>" placement="top">
                                                    <el-button @click="editPayoutCall(scope.row.ap_payout_id,scope.$index,scope.row)" class="ap-btn--icon-without-box">
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
                                                        title="<?php esc_html_e('Are you sure you want to delete this payout?', 'affiliatepress-affiliate-marketing'); ?>"
                                                        @confirm="deletePayout(scope.row.ap_payout_id,scope.$index)"
                                                        width="280">  
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
            <el-col class="ap-align-center" v-if="current_screen_size != 'desktop' && is_display_loader == '0'"  :xs="24" :sm="24" :md="24" :lg="12" :xl="12">
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
        <el-drawer :direction="drawer_direction" modal-class="ap-add__drawer-main" :withHeader="false" @close="resetModal('affiliates_form_data')" v-model="open_modal">    
            <div v-if="open_edit_modal == false" class="ap-add__drawer">
                <div class="ap-dlt__header">
                    <div class="ap-dlt__heading"><?php esc_html_e('Generate Payout', 'affiliatepress-affiliate-marketing'); ?></div>            
                </div>
                <div id="ap-drawer-body" class="ap-dlt__body">
                    <div class="ap-dlt__form_body">
                        <div v-if="payout_generate_loading == '1'" class="ap-generate-payout-loader-container">
                            <div class="ap-generate-payout-loader">
                                <el-progress type="dashboard" :percentage="complete_percentage" :color="(complete_percentage < 100)?'#7362f9':'#1CC985'"></el-progress>
                                <div v-if="complete_percentage < 100" class="ap-loader-progress-txt"><?php esc_html_e('Progress...', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div v-else class="ap-loader-progress-txt"><?php esc_html_e('Completed', 'affiliatepress-affiliate-marketing'); ?></div>
                            </div>
                            <div class="ap-payout-buttons">
                                <el-row type="flex">
                                    <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="ap-gs__cb-item-left">
                                        <el-button v-if="payout_generate_loading != '0' && complete_percentage == '100'" :class="(payout_export_loading == '1') ? 'ap-btn--is-loader' : ''" @click="export_payout(payout_generate_id)" :disabled="(payout_export_loading == '1')?true:false" class="ap-btn--primary ap-btn--big ap-payout-loader-button"  type="primary">
                                            <span class="ap-btn__icon"><?php do_action('affiliatepress_common_svg_code','export_icon'); ?></span>
                                            <span class="ap-btn__label"><?php esc_html_e('Export Payout Data',  'affiliatepress-affiliate-marketing'); ?></span>
                                            <div class="ap-btn--loader__circles">
                                                <div></div>
                                                <div></div>
                                                <div></div>
                                            </div>                    
                                        </el-button>  
                                    </el-col>
                                    <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="ap-gs__cb-item-left" v-if="payout_generate_loading != '0'  && complete_percentage == '100'" >
                                        <el-button @click="closeModal('affiliates_form_data')" class="ap-btn--second ap-btn--big ap-payout-loader-close-button">
                                            <span class="ap-btn__label"><?php esc_html_e('Close',  'affiliatepress-affiliate-marketing'); ?></span>
                                        </el-button>
                                    </el-col>
                                </el-row>
                            </div>
                        </div>
                        <div v-if="payout_generate_loading != '1'">
                            <div class="ap-flex-between">
                                <div class="ap-dlt__form_title"><?php esc_html_e('Payout Details', 'affiliatepress-affiliate-marketing'); ?></div>
                                <div class="ap-dlt__form_payout_info"><?php esc_html_e('Minimum Amount :', 'affiliatepress-affiliate-marketing'); ?> <span class="ap-payout-min-amt">{{minimum_payment_amount}}</span></div>
                            </div>
                            <el-form ref="affiliates_form_data" :rules="rules" require-asterisk-position="right" :model="payout" label-position="top">
                                <div class="ap-single-field__form">                      
                                    <el-form-item class="ap-combine-field" prop="payout_upto">
                                        <template #label>
                                            <span class="ap-form-label"><?php esc_html_e('Unpaid Commissions Up to', 'affiliatepress-affiliate-marketing'); ?></span>
                                        </template>                                            
                                        <el-date-picker v-model="payout.payout_upto" class="ap-form-date-picker-control" size="large" value-format="YYYY-MM-DD" :format="ap_common_date_format" placeholder="<?php esc_html_e('Select Date', 'affiliatepress-affiliate-marketing'); ?>" :disabled-date="disabled_after_grace_period_date"></el-date-picker>
                                    </el-form-item>
                                </div>
                                <div class="ap-single-field__form">                                                                                                 
                                    <el-button :class="(payout_preview_loading == '1') ? 'ap-btn--is-loader' : ''" @click="previewPayout('affiliates_form_data')" :disabled="(payout_preview_loading == '1')?true:false" class="ap-btn--primary ap-btn--big"  type="primary">
                                        <span class="ap-btn__label"><?php esc_html_e('Preview', 'affiliatepress-affiliate-marketing'); ?></span>
                                        <div class="ap-btn--loader__circles">
                                            <div></div>
                                            <div></div>
                                            <div></div>
                                        </div>                    
                                    </el-button>  
                                </div>
                            </el-form>
                            <div v-if="preview_affiliates.length > 0" class="ap-payout-preview">
                                <div class="ap-flex-between">
                                    <div class="ap-dlt__form_title"><?php esc_html_e('Select Affiliates', 'affiliatepress-affiliate-marketing'); ?></div>
                                    <div class="ap-dlt__form_payout_info ap-dlt__payout-preview_info"><?php esc_html_e('Payout Amount :', 'affiliatepress-affiliate-marketing'); ?> <span class="ap-payout-min-amt">{{preview_total_amount}}</span> <span class="ap-border-left ap-pl-5"><?php esc_html_e('Total Affiliates :', 'affiliatepress-affiliate-marketing'); ?></span> <span class="ap-payout-min-amt">{{preview_total_affiliate}}</span> </div>
                                </div>  
                                <div class="ap-flex-between ap-manual-approve-checkbox" v-if="payout.payment_method == 'manual'">
                                    <el-checkbox v-model="auto_approved_payouts" class="ap-form-label ap-custom-checkbox--is-label ap-csf-custom-checkbox" label="<?php esc_html_e('Set Payout as Paid', 'affiliatepress-affiliate-marketing'); ?>"><?php esc_html_e('Set Payout as Paid', 'affiliatepress-affiliate-marketing'); ?></el-checkbox>
                                </div>  
                                <div class="ap-payout-table-data">
                                    <el-table @selection-change="handleSelectionPreview" ref="payout_preview_table" class="ap-manage-payout-items" :data="preview_affiliates">  
                                        <el-table-column type="expand" width="40">
                                            <template slot-scope="scope" #default="scope">
                                            <div class="ap-table-expand-view-wapper ap-desktop-expand">
                                                <div class="ap-table-expand-view">
                                                    <div class="ap-table-expand-view-inner">
                                                        <div class="ap-table-expand-label"><?php esc_html_e('Username', 'affiliatepress-affiliate-marketing'); ?></div>
                                                        <div class="ap-table-expand-seprater"></div>
                                                        <div class="ap-table-expand-value"><div>{{ scope.row.affiiate_user_name }}</div></div>
                                                    </div>    
                                                    <div class="ap-table-expand-view-inner">
                                                        <div class="ap-table-expand-label"><?php esc_html_e('Pay ID', 'affiliatepress-affiliate-marketing'); ?></div>
                                                        <div class="ap-table-expand-seprater"></div>
                                                        <div class="ap-table-expand-value">
                                                            <span v-if="scope.row.affiiate_payment_email != ''">{{ scope.row.affiiate_payment_email }}</span>
                                                            <span v-else>-</span>
                                                        </div>
                                                    </div>   
                                                    <div class="ap-table-expand-view-inner">
                                                        <div class="ap-table-expand-label"><?php esc_html_e('Commissions', 'affiliatepress-affiliate-marketing'); ?></div>
                                                        <div class="ap-table-expand-seprater"></div>
                                                        <div class="ap-table-expand-value">{{ scope.row.total_commission }}</div>
                                                    </div>   
                                                    <div class="ap-table-expand-view-inner">
                                                        <div class="ap-table-expand-label"><?php esc_html_e('Visitors', 'affiliatepress-affiliate-marketing'); ?></div>
                                                        <div class="ap-table-expand-seprater"></div>
                                                        <div class="ap-table-expand-value" v-if ="scope.row.ap_payout_visit_count == 0">-</div>
                                                        <div class="ap-table-expand-value" v-else>{{ scope.row.ap_payout_visit_count }}  (<?php esc_html_e('Conversion Rate', 'affiliatepress-affiliate-marketing'); ?>: {{ scope.row.ap_payout_visit_conversion_rate }}%)</div>
                                                    </div>   
                                                </div>
                                            </div>
                                            </template>
                                        </el-table-column>      
                                        <el-table-column type="selection" :selectable="selected_paymnet_affiliate_row"></el-table-column>
                                        <el-table-column min-width="50" label="<?php esc_html_e('Affiliate User', 'affiliatepress-affiliate-marketing'); ?>">
                                            <template #default="scope">
                                                <span>{{ scope.row.affiiate_name }}</span>
                                            </template>
                                        </el-table-column>
                                        <el-table-column min-width="30" align="right" header-align="right" label="<?php esc_html_e('Amount', 'affiliatepress-affiliate-marketing'); ?>">
                                            <template #default="scope">
                                                <span>{{ scope.row.total_amount_formted }}</span>
                                            </template>
                                        </el-table-column> 
                                        <el-table-column min-width="60" align="center" header-align="center" label="<?php esc_html_e('Payment Method', 'affiliatepress-affiliate-marketing'); ?>">
                                            <template #default="scope">
                                                <span>{{ scope.row.payment_method_label }}</span>
                                            </template>
                                        </el-table-column> 
                                    </el-table>    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ap-dlt__footer" v-if="payout_generate_loading != '1'">
                    <div class="ap-dlt__footer-btn">
                        <el-button @click="closeModal('affiliates_form_data')" class="ap-btn--second ap-btn--big">
                            <span class="ap-btn__label"><?php esc_html_e('Cancel',  'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>
                        <el-button v-if="preview_affiliates.length > 0 && payout_preview_loading == '0' && payout.allow_affiliates.length != 0 && payout_generate_loading != '1'" :class="(payout_generate_loading_btn == '1') ? 'ap-btn--is-loader' : ''" @click="generate_payout_request('affiliates_form_data')" :disabled="(payout_generate_loading_btn == '1')?true:false" class="ap-btn--primary ap-btn--big"  type="primary">
                            <span class="ap-btn__label"><?php esc_html_e('Proceed to Payout', 'affiliatepress-affiliate-marketing'); ?></span>
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
                    <div class="ap-dlt__heading"><?php esc_html_e('Edit Payout', 'affiliatepress-affiliate-marketing'); ?></div>            
                </div>
                <div id="ap-drawer-body" class="ap-dlt__body">
                    <div class="ap-back-loader-container" v-if="edit_payout_data == ''" id="ap-page-loading-loader">
                        <div class="ap-back-loader"></div>
                    </div>   
                    <div v-if="edit_payout_data != ''" class="ap-dlt__form_body">
                        <div class="ap-payout-detail-box">
                            <div class="ap-dlt__form_title"><?php esc_html_e('Payout Details', 'affiliatepress-affiliate-marketing'); ?></div>
                            <div class="ap-dlt__form_payout_info">
                                <div class="ap-flex-between ap-payout-box">
                                    <div class="ap-payout-detail-title"><?php esc_html_e('Commissions Up to :', 'affiliatepress-affiliate-marketing'); ?></div>
                                    <div class="ap-payout-detail-value">{{edit_payout_data.ap_payout_upto_date_formated}}</div>
                                </div> 
                                <div class="ap-flex-between ap-payout-box">
                                    <div class="ap-payout-detail-title"><?php esc_html_e('Payment Minimum Amount :', 'affiliatepress-affiliate-marketing'); ?></div>
                                    <div class="ap-payout-detail-value">{{edit_payout_data.ap_payment_min_amount_formated}}</div>
                                </div> 
                                <div class="ap-flex-between ap-payout-box">
                                    <div class="ap-payout-detail-title"><?php esc_html_e('Payout Amount :', 'affiliatepress-affiliate-marketing'); ?></div>
                                    <div class="ap-payout-detail-value">{{edit_payout_data.ap_formated_payout_amount}}</div>
                                </div> 
                                <div class="ap-flex-between ap-payout-box" v-if="edit_payout_data.affiliatepress_common_payment_method != ''">
                                    <div class="ap-payout-detail-title"><?php esc_html_e('Payout Method :',  'affiliatepress-affiliate-marketing');// phpcs:ignore ?></div>
                                    <div class="ap-payout-detail-value">{{edit_payout_data.affiliatepress_common_payment_method}}</div>
                                </div> 
                                <div class="ap-flex-between ap-payout-box">
                                    <div class="ap-payout-detail-title"><?php esc_html_e('Total Affiliates :', 'affiliatepress-affiliate-marketing'); ?></div>
                                    <div class="ap-payout-detail-value">{{edit_payout_data.ap_payout_total_affiliate}}</div>
                                </div>
                                <div class="ap-flex-between ap-payout-box">
                                    <div class="ap-payout-detail-title"><?php esc_html_e('Paid/Unpaid Affiliates :', 'affiliatepress-affiliate-marketing'); ?></div>
                                    <div class="ap-payout-detail-value">{{edit_payout_data.paid_affiliate_count}}/{{edit_payout_data.unpaid_affiliate_count}}</div>
                                </div>                                                                                                                        
                            </div>
                        </div>
                        <div v-if="edit_payout_payments != '' && edit_payout_payments.length != 0" class="ap-payout-table-data ap-payment-table-data">
                            <div class="ap-dlt__form_title"><?php esc_html_e('Payout Payments', 'affiliatepress-affiliate-marketing'); ?></div>
                            <el-table ref="payout_payment_table" class="ap-manage-payout-items" :data="edit_payout_payments" @row-click="affiliatepress_payout_full_row_clickable">   
                                <el-table-column type="expand" width="60">
                                    <template slot-scope="scope" #default="scope">
                                    <div class="ap-table-expand-view-wapper ap-desktop-expand">
                                        <div class="ap-table-expand-view">
                                            <div class="ap-table-expand-view-inner">
                                                <div class="ap-table-expand-label"><?php esc_html_e('Username', 'affiliatepress-affiliate-marketing'); ?></div>
                                                <div class="ap-table-expand-seprater"></div>
                                                <div class="ap-table-expand-value"><div>{{ scope.row.ap_affiliates_user_name }}</div></div>
                                            </div>    
                                            <div class="ap-table-expand-view-inner">
                                                <div class="ap-table-expand-label"><?php esc_html_e('Pay ID', 'affiliatepress-affiliate-marketing'); ?></div>
                                                <div class="ap-table-expand-seprater"></div>
                                                <div class="ap-table-expand-value">
                                                    <span v-if="scope.row.ap_affiliates_payment_email != ''">{{ scope.row.ap_affiliates_payment_email }}</span>
                                                    <span v-else>-</span>
                                                </div>
                                            </div>   
                                            <div class="ap-table-expand-view-inner">
                                                <div class="ap-table-expand-label"><?php esc_html_e('Commissions', 'affiliatepress-affiliate-marketing'); ?></div>
                                                <div class="ap-table-expand-seprater"></div>
                                                <div class="ap-table-expand-value">{{ scope.row.ap_affiliate_commission_count }}</div>
                                            </div>   
                                            <div class="ap-table-expand-view-inner">
                                                <div class="ap-table-expand-label"><?php esc_html_e('Visitors', 'affiliatepress-affiliate-marketing'); ?></div>
                                                <div class="ap-table-expand-seprater"></div>
                                                <div class="ap-table-expand-value" v-if="scope.row.ap_affiliate_visit_count == 0">-</div>
                                                <div class="ap-table-expand-value" v-else>{{ scope.row.ap_affiliate_visit_count }} (<?php esc_html_e('Conversion Rate', 'affiliatepress-affiliate-marketing'); ?>: {{ scope.row.ap_payout_visit_conversion_rate }}%)</div>
                                            </div>   
                                            <div class="ap-table-expand-view-inner">
                                                <div class="ap-table-expand-label"><?php esc_html_e('Payment Method', 'affiliatepress-affiliate-marketing'); ?></div>
                                                <div class="ap-table-expand-seprater"></div>
                                                <div class="ap-table-expand-value">{{ scope.row.ap_payment_method }}</div>
                                            </div>  
                                        </div>
                                    </div>
                                    </template>
                                </el-table-column>                                                
                                <el-table-column  label="<?php esc_html_e('Affiliate User', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <span>{{ scope.row.affiliate_name }}</span>
                                    </template>
                                </el-table-column> 
                                <el-table-column align="right" header-align="right"  label="<?php esc_html_e('Amount', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <span>{{ scope.row.ap_payment_amount_formated }}</span>
                                    </template>
                                </el-table-column>
                                <el-table-column align="center" header-align="center" class-name="ap-action-column" label="<?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <span class="ap-status-display" :class="[(scope.row.ap_payment_status == '1'?'ap-status-orange-color':''),(scope.row.ap_payment_status == '4'?'ap-status-green':''),((scope.row.ap_payment_status == '5' || scope.row.ap_payment_status == '2')?'ap-status-orange-color':''),(scope.row.ap_payment_status == '3'?'ap-status-reject-color':'')]">{{ scope.row.payment_status_name }}</span>
                                        <div :class="(scope.row.payment_status_change_loader == '1')?'ap-table-actions-wrap-disp':''" class="ap-table-actions-wrap">
                                            <div v-if="scope.row.ap_payment_status != '5'" class="ap-table-actions ap-payment-table-actions">
                                                <div v-if="scope.row.payment_status_change_loader == '1'" class="ap-btn--is-loader">                                        
                                                    <div class="ap-only--loader__circles">
                                                        <div></div>
                                                        <div></div>
                                                        <div></div>
                                                    </div>                    
                                                </div>                                            
                                                <el-tooltip v-if="(scope.row.ap_payment_status == '1' || scope.row.ap_payment_status == '3') && scope.row.payment_status_change_loader == '0'" popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Mark as Paid', 'affiliatepress-affiliate-marketing'); ?>" placement="top-start">
                                                    <el-button plain @click="paymentPayoutStatusChange(scope.row.ap_payment_id,'4',scope.$index)" class="ap-btn--icon-without-box">
                                                        <span class="ap-small-btn-icon ap-edit-icon ap-not-fill-icon">
                                                            <?php do_action('affiliatepress_common_svg_code','mark_as_paid_icon'); ?>
                                                        </span>
                                                    </el-button>
                                                </el-tooltip>                                            
                                                <el-tooltip v-if="scope.row.ap_payment_status == '4' && scope.row.payment_status_change_loader == '0' && scope.row.ap_payment_method_key == 'manual'" popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Mark as Unpaid', 'affiliatepress-affiliate-marketing'); ?>" placement="top-start">
                                                    <el-button @click="paymentPayoutStatusChange(scope.row.ap_payment_id,'1',scope.$index)" class="ap-btn--icon-without-box">
                                                        <span class="ap-small-btn-icon ap-edit-icon ap-not-fill-icon">
                                                            <?php do_action('affiliatepress_common_svg_code','mark_as_unpaid_icon'); ?>                                                   
                                                        </span>
                                                    </el-button>
                                                </el-tooltip>                                            
                                                <el-tooltip v-if="scope.row.payment_status_change_loader == '0'" popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Add Note', 'affiliatepress-affiliate-marketing'); ?>" placement="top-start">
                                                    <el-button plain @click="paymentPayoutmessage_add(scope.row.ap_payment_id,scope.$index,scope.row)" class="ap-btn--icon-without-box">
                                                        <span class="ap-small-btn-icon ap-edit-icon">
                                                            <?php do_action('affiliatepress_common_svg_code','payout_note_icon'); ?>                                                   
                                                        </span>
                                                    </el-button>
                                                </el-tooltip>
                                            </div>
                                        </div>                                     
                                    </template>
                                </el-table-column>
                            </el-table>    
                        </div>                    
                    </div>                
                </div>
                <div class="ap-dlt__footer">
                    <div class="ap-dlt__footer-btn">
                        <el-button @click="closeModal('affiliates_form_data')" class="ap-btn--second ap-btn--big ap-margin-right--sec-btn">                        
                            <span class="ap-btn__label"><?php esc_html_e('Close', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>  
                        <el-button  :class="(payout_export_loading == '1') ? 'ap-btn--is-loader' : ''" @click="export_payout(edit_payout_data.ap_payout_id)" :disabled="(payout_export_loading == '1')?true:false" class="ap-btn--primary ap-btn--big"  type="primary">
                            <span class="ap-btn__label"><?php esc_html_e('Export',  'affiliatepress-affiliate-marketing'); ?></span>
                            <div class="ap-btn--loader__circles">
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>                    
                        </el-button>                        
                    </div>
                </div>            
            </div>     
            <el-drawer :direction="drawer_direction" modal-class="ap-add__drawer-main ap-add__drawer-msg" :withHeader="false" @close="closePaymentModal('payout_message_frm')" v-model="open_payment_message_modal">  
                <div  class="ap-add__drawer">
                    <div class="ap-dlt__header">
                        <div class="ap-dlt__heading"><?php esc_html_e('Add Note', 'affiliatepress-affiliate-marketing'); ?></div>            
                    </div>
                    <div id="ap-drawer-body" class="ap-dlt__body">
                        <div class="ap-dlt__form_body">
                            <div class="ap-payout-detail-box ap-payment-msg">
                                <div class="ap-dlt__form_title"><?php esc_html_e('Note Details', 'affiliatepress-affiliate-marketing'); ?></div>
                                <el-form ref="payout_message_frm" :rules="payout_msg_rules" require-asterisk-position="right" :model="payout_message" label-position="top">
                                <div  class="ap-single-field__form">
                                    <el-form-item prop="payout_payment_message">
                                        <template #label>
                                            <span class="ap-form-label"><?php esc_html_e('Add Note', 'affiliatepress-affiliate-marketing'); ?></span>
                                        </template>                
                                        <el-input class="ap-form-control" type="textarea" :rows="6" v-model="payout_message.payout_payment_message" size="large" placeholder="<?php esc_html_e('Write note here', 'affiliatepress-affiliate-marketing'); ?>" />
                                    </el-form-item>                     
                                </div> 
                                </el-form>
                            </div>
                        </div>                   
                    </div>    
                    <div class="ap-dlt__footer">
                        <div class="ap-dlt__footer-btn">
                            <el-button @click="open_payment_message_modal = false" class="ap-btn--second ap-btn--big ap-margin-right--sec-btn">
                                <span class="ap-btn__label"><?php esc_html_e('Cancel', 'affiliatepress-affiliate-marketing'); ?></span>
                            </el-button>
                            <el-button :class="(payout_payment_note_loader == '1') ? 'ap-btn--is-loader' : ''" @click="savePaymentMessage('payout_message_frm',edit_payout_data.ap_payout_id)" :disabled="(payout_payment_note_loader == '1')?true:false" class="ap-btn--primary ap-btn--big"  type="primary">
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
        <el-drawer>
    </div>
</el-main>
<?php
    $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/affiliatepress_footer.php';
    $affiliatepress_load_file_name = apply_filters('affiliatepress_modify_footer_content', $affiliatepress_load_file_name,1);
    require $affiliatepress_load_file_name;
?>

