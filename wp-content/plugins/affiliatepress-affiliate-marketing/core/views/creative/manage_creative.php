<?php
    if ( ! defined( 'ABSPATH' ) ) { exit; }
    global $AffiliatePress;
?>
<el-main class="ap-main-listing-card-container ap-default-card ap--is-page-non-scrollable-mob ap-creative-listing-wrapper" id="ap-all-page-main-container">
    <el-row :gutter="12" type="flex" class="ap-head-wrap">
        <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="ap-head-left">
            <h1 class="ap-page-heading"><?php esc_html_e('Manage Creatives', 'affiliatepress-affiliate-marketing'); ?></h1>
        </el-col>
        <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="ap-head-right">
            <div>
                <div class="ap-hw-right-btn-group">              
                    <el-button type="primary" @click="open_modal = true" class="ap-btn--primary" :disabled="affiliate_add_disable">
                        <span class="ap-btn__icon"><?php do_action('affiliatepress_common_svg_code','add_icon'); ?></span>
                        <span class="ap-btn__label"><?php esc_html_e('Add New', 'affiliatepress-affiliate-marketing'); ?></span>
                    </el-button> 		
                </div>
                <div @click="open_premium_modal" class="ap-premium-text" v-if="is_pro_active != '1'"><?php echo $AffiliatePress->affiliatepress_get_premium_content(); //phpcs:ignore ?></div>
            </div>
        </el-col>                
    </el-row>
    <div class="ap-back-loader-container" v-if="ap_first_page_loaded == '1'" id="ap-page-loading-loader">
        <div class="ap-back-loader"></div>
    </div>    
    <div v-if="ap_first_page_loaded == '0'" id="ap-main-container">       
        <div class="ap-table-filter">
            <el-row class="ap-table-filter-row" type="flex" :gutter="24">
                <el-col :xs="24" :sm="24" :md="24" :lg="6" :xl="4">
                    <div class="ap-combine-field">
                        <el-input class="ap-form-control" v-model="creatives_search.ap_creative_name" size="large" placeholder="<?php esc_html_e('Enter Creative Name', 'affiliatepress-affiliate-marketing'); ?>" @keyup.enter="applyFilter()"/>
                    </div>    
                </el-col> 
                <el-col :xs="24" :sm="24" :md="24" :lg="6" :xl="6">
                        <el-button @click="applyFilter()" class="ap-btn--primary" plain type="primary" :disabled="is_apply_disabled">
                            <span class="ap-btn__label"><?php esc_html_e('Apply', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>
                        <el-button @click="resetFilter" class="ap-btn--second" v-if="creatives_search.ap_creative_name != ''">
                            <span class="ap-btn__label"><?php esc_html_e('Reset', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>
                </el-col>
            </el-row>
        </div>
        <el-row >
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
                                <el-table-column  width="80" prop="ap_creative_id" label="<?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="ap_creative_id">
                                    <template #default="scope">
                                        <span>{{ scope.row.ap_creative_id }}</span>
                                    </template>
                                </el-table-column>
                                <el-table-column  prop="ap_creative_text" width="250" label="<?php esc_html_e('Creative', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <div v-if="scope.row.ap_creative_type == 'text_link'"><a href="javascript:void(0);">{{scope.row.ap_creative_text}}</a></div>
                                        <div v-else>
                                            <div>
                                                <el-image class="ap-table-creatie-avatar" :src="scope.row.image_url"></el-image>    
                                            </div>
                                        </div>
                                    </template>                                
                                </el-table-column>
                                <el-table-column prop="ap_creative_name" width="400" label="<?php esc_html_e('Name', 'affiliatepress-affiliate-marketing'); ?>" sortable="true"></el-table-column>
                                <el-table-column class-name="ap-padding-left-cls ap-grid-status-align-center"  prop="ap_creative_status" align="center" header-align="center" width="190" label="<?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <div class="ap-table-status-dropdown-wrapper" :class="(scope.row.change_status_loader == 1) ? '__ap-is-loader-active' : ''">
                                            <div class="ap-status-loader-wrapper" v-if="scope.row.change_status_loader == 1" :class="(scope.row.ap_creative_status == '1' ? 'ap-status--active' : '') || (scope.row.ap_creative_status == '0' ? 'ap-status--rejected' : '')">
                                                <el-image class="ap-status-loader" src="<?php echo esc_url(AFFILIATEPRESS_IMAGES_URL . '/status-loader.gif'); ?>" alt="<?php esc_attr_e('Loader', 'affiliatepress-affiliate-marketing'); ?>"></el-image>
                                                </div>
                                            <el-select v-else class="ap-form-control ap-status-control" :class="(scope.row.ap_creative_status == '1' ? 'ap-status--active' : '') || (scope.row.ap_creative_status == '0' ? 'ap-status--rejected' : '')" v-model="scope.row.ap_creative_status" placeholder="Select Status" @change="affiliatepress_change_status(scope.row.ap_creative_id,scope.$index,$event,scope.row.ap_creative_status)" popper-class="ap-status-dropdown-popper">
                                                <el-option-group label="<?php esc_html_e('Change status', 'affiliatepress-affiliate-marketing'); ?>">
                                                    <el-option  v-for="item in all_creatives_status" :key="item.value" :label="item.text" :value="item.value"></el-option>
                                                </el-option-group>
                                            </el-select>
                                        </div>
                                    </template>
                                </el-table-column>
                                <el-table-column  prop="ap_creative_type" width="100" label="<?php esc_html_e('Type', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <div v-if="scope.row.ap_creative_type == 'text_link'"><?php esc_html_e('Text Link', 'affiliatepress-affiliate-marketing'); ?></div>
                                        <div v-else><?php esc_html_e('Image', 'affiliatepress-affiliate-marketing'); ?></div>
                                    </template>                            
                                </el-table-column>
                                <el-table-column class-name="ap-action-column" prop="ap_creative_landing_url" min-width="150" label="<?php esc_html_e('Landing URL', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <span>{{ scope.row.ap_creative_landing_url }}</span>
                                        <div class="ap-table-actions-wrap">
                                            <div class="ap-table-actions">
                                                <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Edit', 'affiliatepress-affiliate-marketing'); ?>" placement="top">
                                                    <el-button @click="editCreative(scope.row.ap_creative_id,scope.$index,scope.row)" class="ap-btn--icon-without-box">
                                                        <span class="ap-small-btn-icon ap-edit-icon">
                                                            <?php do_action('affiliatepress_common_svg_code','edit_icon'); ?>                                                        
                                                        </span>
                                                    </el-button>
                                                </el-tooltip>
                                                <?php 
                                                    $affiliatepress_add_creative_action = "";
                                                    $affiliatepress_add_creative_action = apply_filters('affiliatepress_add_creative_action',$affiliatepress_add_creative_action);
                                                    echo $affiliatepress_add_creative_action; //phpcs:ignore
                                                ?>
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
                                        <div class="ap-table-expand-view-inner ap-table-expand-view-inner-full">
                                            <div class="ap-table-expand-label"><?php esc_html_e('Creative', 'affiliatepress-affiliate-marketing'); ?></div>
                                            <div class="ap-table-expand-seprater">:</div>
                                            <div class="ap-table-expand-value">
                                                <div v-if="scope.row.ap_creative_type == 'text_link'"><a href="javascript:void(0);">{{scope.row.ap_creative_text}}</a></div>
                                                <div v-else>
                                                    <div>
                                                        <el-image class="ap-table-creatie-avatar" :src="scope.row.image_url"></el-image>    
                                                    </div>
                                                </div>                                            
                                            </div>
                                        </div>
                                        <div class="ap-table-expand-view-inner ap-table-expand-view-inner-full">
                                            <div class="ap-table-expand-label"><?php esc_html_e('Landing URL', 'affiliatepress-affiliate-marketing'); ?></div>
                                            <div class="ap-table-expand-seprater">:</div>
                                            <div class="ap-table-expand-value">
                                                <span v-if="scope.row.ap_creative_landing_url == ''">-</span>
                                                <span v-else v-html="scope.row.ap_creative_landing_url" class="ap-creative-landing-url"></span>                                            
                                            </div>
                                        </div>                                                                      
                                    </div>
                                </div>
                                </template>
                            </el-table-column>                        
                            <el-table-column width="40" type="selection"></el-table-column>
                            <el-table-column min-width="55" prop="ap_creative_id" label="<?php esc_html_e('ID', 'affiliatepress-affiliate-marketing'); ?>" sortable sort-by="ap_creative_id">
                                <template #default="scope">
                                    <span>#{{ scope.row.ap_creative_id }}</span>
                                </template>
                            </el-table-column>
                            <el-table-column prop="ap_creative_name" min-width="180" label="<?php esc_html_e('Name', 'affiliatepress-affiliate-marketing'); ?>" sortable="true"></el-table-column>
                            <el-table-column class-name="ap-padding-left-cls ap-grid-status-align-center" prop="ap_creative_status" align="center" header-align="center" width="190" label="<?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?>">
                                <template #default="scope">
                                    <div class="ap-table-status-dropdown-wrapper" :class="(scope.row.change_status_loader == 1 ? '__ap-is-loader-active ' : '') + (current_screen_size != 'desktop' ? 'ap-small-screen-status-dropdown' : '')">
                                        <div class="ap-status-loader-wrapper" v-if="scope.row.change_status_loader == 1" :class="(scope.row.ap_creative_status == '1' ? 'ap-status--active' : '') || (scope.row.ap_creative_status == '0' ? 'ap-status--rejected' : '')">
                                        <el-image class="ap-status-loader" src="<?php echo esc_url(AFFILIATEPRESS_IMAGES_URL . '/status-loader.gif'); ?>" alt="<?php esc_attr_e('Loader', 'affiliatepress-affiliate-marketing'); ?>"></el-image>
                                                </div>
                                        <el-select v-else class="ap-form-control ap-status-control" :class="(scope.row.ap_creative_status == '1' ? 'ap-status--active' : '') || (scope.row.ap_creative_status == '0' ? 'ap-status--rejected' : '')" v-model="scope.row.ap_creative_status" placeholder="<?php esc_html_e('Select Status', 'affiliatepress-affiliate-marketing'); ?>" @change="affiliatepress_change_status(scope.row.ap_creative_id,scope.$index,$event,scope.row.ap_creative_status)" popper-class="ap-status-dropdown-popper">
                                            <el-option-group label="<?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?>">
                                                <el-option  v-for="item in all_creatives_status" :key="item.value" :label="item.text" :value="item.value"></el-option>
                                            </el-option-group>
                                        </el-select>
                                    </div>
                                </template>
                            </el-table-column>
                            <el-table-column class-name="ap-action-column" prop="ap_creative_type" min-width="100" label="<?php esc_html_e('Type', 'affiliatepress-affiliate-marketing'); ?>">
                                    <template #default="scope">
                                        <span v-if="scope.row.ap_creative_type == 'text_link'"><?php esc_html_e('Text Link', 'affiliatepress-affiliate-marketing'); ?></span>
                                        <span v-else><?php esc_html_e('Image', 'affiliatepress-affiliate-marketing'); ?></span>
                                        <div class="ap-table-actions-wrap">
                                            <div class="ap-table-actions">
                                                <el-tooltip popper-class="ap--popover-tool-tip" show-after="300" effect="dark" content="<?php esc_html_e('Edit', 'affiliatepress-affiliate-marketing'); ?>" placement="top">
                                                    <el-button @click="editCreative(scope.row.ap_creative_id,scope.$index,scope.row)" class="ap-btn--icon-without-box">
                                                        <span class="ap-small-btn-icon ap-edit-icon">
                                                            <?php do_action('affiliatepress_common_svg_code','edit_icon'); ?>                                                        
                                                        </span>
                                                    </el-button>
                                                </el-tooltip>
                                                <?php 
                                                    $affiliatepress_add_creative_action = "";
                                                    $affiliatepress_add_creative_action = apply_filters('affiliatepress_add_creative_action',$affiliatepress_add_creative_action);
                                                    echo $affiliatepress_add_creative_action; //phpcs:ignore
                                                ?>
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
        <el-drawer modal-class="ap-add__drawer-main" :direction="drawer_direction" :withHeader="false" @close="resetModal('creatives_form_data')" v-model="open_modal">    
            <div class="ap-add__drawer">
                <div class="ap-dlt__header">
                    <div class="ap-dlt__heading" v-if="creatives.ap_creative_id == ''"><?php esc_html_e('Add Creative', 'affiliatepress-affiliate-marketing'); ?></div>
                    <div class="ap-dlt__heading" v-else><?php esc_html_e('Edit Creative', 'affiliatepress-affiliate-marketing'); ?></div>
                </div>
                <div id="ap-drawer-body" class="ap-dlt__body">
                    <div class="ap-dlt__form_body">
                        <div class="ap-dlt__form_title"><?php esc_html_e('Creative Details', 'affiliatepress-affiliate-marketing'); ?></div>
                        <el-form ref="creatives_form_data" :rules="rules" require-asterisk-position="right" :model="creatives" label-position="top">
                            <div class="ap-single-field__form">
                                <el-form-item class="ap-combine-field" prop="ap_creative_name">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Name', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-input class="ap-form-control" maxlength="250" type="text" v-model="creatives.ap_creative_name" size="large" placeholder="<?php esc_html_e('Enter Creative Name', 'affiliatepress-affiliate-marketing'); ?>" />
                                </el-form-item>                     
                            </div>
                            <div class="ap-single-field__form">
                                <el-form-item class="ap-combine-field" prop="ap_creative_description">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Description', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-input class="ap-form-control" maxlength="600" type="textarea" :rows="4" v-model="creatives.ap_creative_description" size="large" placeholder="<?php esc_html_e('Enter description', 'affiliatepress-affiliate-marketing'); ?>" />
                                </el-form-item>                     
                            </div>
                            <div class="ap-single-field__form">
                                <el-form-item class="ap-combine-field" prop="ap_creative_type">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Creative Type', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>
                                    <el-select class="ap-form-control" v-model="creatives.ap_creative_type" placeholder="Select" size="large">
                                        <el-option label="<?php esc_html_e('Text Link', 'affiliatepress-affiliate-marketing'); ?>" value="text_link"/></el-option>
                                        <el-option label="<?php esc_html_e('Image', 'affiliatepress-affiliate-marketing'); ?>" value="image"/></el-option>
                                    </el-select>                        
                                </el-form-item>                     
                            </div>                 
                            <div v-if="creatives.ap_creative_type == 'image'" class="ap-single-field__form">
                                <el-form-item  class="ap-combine-field-upload" prop="ap_creative_image_url">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Creative Image', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-upload class="ap-simple-upload-form-control ap-combine-field-upload" ref="avatarRef" multiple="false" limit="1" action="<?php echo wp_nonce_url(admin_url('admin-ajax.php') . '?action=affiliatepress_upload_creative_image', 'affiliatepress_upload_creative_image'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - esc_html is already used by wp_nonce_url function and it's false positive ?>" 
                                    :file-list="creatives.image_list"
                                    :on-success="affiliatepress_upload_creative_image_func"
                                    :on-exceed="affiliatepress_replace_image"
                                    :on-remove="affiliatepress_remove_affiliate_avatar"
                                    :before-upload="checkUploadedFile">
                                        <label class="ap-simple--file-upload">
                                            <span class="ap-fu__placeholder"><span class="ap-fu__icon"><?php do_action('affiliatepress_common_svg_code','file-upload-icon'); ?></span><span class="ap-fu__text"><?php esc_html_e('Browse file to upload', 'affiliatepress-affiliate-marketing'); ?></span></span>                                    
                                        </label> 
                                    </el-upload> 
                                </el-form-item>                     
                            </div>
                            <div  v-if="creatives.ap_creative_type == 'image'" class="ap-single-field__form">
                                <el-form-item class="ap-combine-field" prop="ap_creative_alt_text">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Alternative Text', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-input class="ap-form-control" maxlength="250" type="text" v-model="creatives.ap_creative_alt_text" size="large" placeholder="<?php esc_html_e('Enter Alternative Text', 'affiliatepress-affiliate-marketing'); ?>" />
                                </el-form-item>                     
                            </div>
                            <div v-if="creatives.ap_creative_type == 'text_link'" class="ap-single-field__form">
                                <el-form-item class="ap-combine-field" prop="ap_creative_text">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Text', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-input class="ap-form-control" type="text" maxlength="254" v-model="creatives.ap_creative_text" size="large" placeholder="<?php esc_html_e('Enter Text', 'affiliatepress-affiliate-marketing'); ?>" />
                                </el-form-item>                     
                            </div>                
                            <div class="ap-single-field__form">
                                <el-form-item class="ap-combine-field" prop="ap_creative_landing_url">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Landing URL', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-input class="ap-form-control" type="text" maxlength="300" v-model="creatives.ap_creative_landing_url" size="large" placeholder="<?php esc_html_e('Enter Website Link', 'affiliatepress-affiliate-marketing'); ?>" />
                                </el-form-item>                     
                            </div>
                            <div class="ap-single-field__form">
                                <el-form-item class="ap-combine-field" prop="ap_creative_status">
                                    <template #label>
                                        <span class="ap-form-label"><?php esc_html_e('Status', 'affiliatepress-affiliate-marketing'); ?></span>
                                    </template>                
                                    <el-select class="ap-form-control" v-model="creatives.ap_creative_status" placeholder="Select" size="large">
                                        <el-option v-for="item in all_creatives_status" :key="item.value" :label="item.text" :value="item.value"/>
                                    </el-select>
                                </el-form-item>                     
                            </div>                    
                            <div v-if="creatives.ap_creative_id != ''" class="ap-top-border-seperator"></div>
                            <div v-if="creatives.ap_creative_id != ''" class="ap-single-field__form">
                                <span class="ap-form-label"><?php esc_html_e('Creative Shortcode', 'affiliatepress-affiliate-marketing'); ?></span>
                                <div class="ap-front-copy-field">                                    
                                    <el-button @click="copy_affiliate_data(creatives.creative_shortcode)" class="ap-btn--primary ap-copy-button" type="primary">
                                        <span class="ap-btn__icon"><?php do_action('affiliatepress_common_affiliate_panel_svg_code','copy_icon'); ?></span>                                
                                    </el-button>                                    
                                    <el-input readonly="true" class="ap-form-control" type="text" v-model="creatives.creative_shortcode" size="large" />
                                </div>                        
                            </div>

                        </el-form>
                    </div>
                </div>
                <div class="ap-dlt__footer">
                    <div class="ap-dlt__footer-btn">
                        <el-button @click="closeModal('creatives_form_data')" class="ap-btn--second ap-btn--big ap-margin-right--sec-btn">
                            <span class="ap-btn__label"><?php esc_html_e('Cancel', 'affiliatepress-affiliate-marketing'); ?></span>
                        </el-button>
                        <el-button :class="(savebtnloading) ? 'ap-btn--is-loader' : ''" @click="saveCreative('creatives_form_data')" :disabled="is_disabled" class="ap-btn--primary ap-btn--big"  type="primary">
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


