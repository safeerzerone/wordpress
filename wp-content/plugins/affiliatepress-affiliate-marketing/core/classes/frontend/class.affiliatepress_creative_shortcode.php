<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_creative_shortcode') ) {
    class affiliatepress_creative_shortcode Extends AffiliatePress_Core{
                
        function __construct(){
            
            add_shortcode('affiliatepress_creative', array($this,'affiliatepress_affiliate_creative_func'));
            add_filter('affiliatepress_affiliate_creative_dynamic_data_fields',array($this,'affiliatepress_affiliate_creative_dynamic_data_fields_func'),10,1);
            add_filter('affiliatepress_affiliate_creative_dynamic_vue_methods',array($this,'affiliatepress_affiliate_creative_dynamic_vue_methods_func'),10,1);
            
        }
        
        /**
         * Function for dynamic vue method
         *
         * @param  mixed $affiliatepress_creative_vue_method
         * @return void
        */
        function affiliatepress_affiliate_creative_dynamic_vue_methods_func($affiliatepress_creative_vue_method){

            $affiliatepress_nonce = esc_html(wp_create_nonce('ap_wp_nonce'));
            global $affiliatepress_notification_duration;

            $affiliatepress_creative_vue_method.='
                download_preview_image(image_url){                    
                    window.open(image_url, "_blank");
                },
                affiliatepress_copy_data(copy_data){
                    const vm = this;	
                    var affiliatepress_dummy_elem = document.createElement("textarea");
                    document.body.appendChild(affiliatepress_dummy_elem);
                    affiliatepress_dummy_elem.value = copy_data;
                    affiliatepress_dummy_elem.select();
                    document.execCommand("copy");
                    document.body.removeChild(affiliatepress_dummy_elem);
                    vm.$notify({ 
                        title: "'.esc_html__('Success', 'affiliatepress-affiliate-marketing').'",
                        message: "'.esc_html__('Link copied successfully.', 'affiliatepress-affiliate-marketing').'",
                        type: "success",
                        customClass: "success_notification",
                        duration:'.intval($affiliatepress_notification_duration).',
                    });
                },                                
            ';
            return $affiliatepress_creative_vue_method;
        }
                
        /**
         * Function for add register fields dynamic data
         *
         * @return void
        */
        function affiliatepress_affiliate_creative_dynamic_data_fields_func($affiliatepress_dynamic_data_fields){
            
            global $AffiliatePress,$wpdb,$affiliatepress_tbl_ap_affiliate_form_fields;            

            return wp_json_encode($affiliatepress_dynamic_data_fields);

        }

        /**
         * Function for set front CSS
         *
         * @return void
        */
        function affiliatepress_affiliatepress_set_front_css($affiliatepress_force_enqueue = 0 ){
            
            global $AffiliatePress;
            
            /* AffiliatePress Front CSS */
            wp_register_style('affiliatepress_front_variables_css', AFFILIATEPRESS_URL . 'css/affiliatepress_front_variables.css', array(), AFFILIATEPRESS_VERSION);

            $affiliatepress_custom_css = $AffiliatePress->affiliatepress_front_dynamic_variable_add();
            wp_add_inline_style('affiliatepress_front_variables_css', $affiliatepress_custom_css,'after');

            wp_register_style('affiliatepress_elements_front_css', AFFILIATEPRESS_URL . 'css/affiliatepress_elements_front.css', array(), AFFILIATEPRESS_VERSION);
            wp_register_style('affiliatepress_component_css', AFFILIATEPRESS_URL . 'css/affiliatepress_component.css', array(), AFFILIATEPRESS_VERSION);            
            wp_register_style('affiliatepress_front_css', AFFILIATEPRESS_URL . 'css/affiliatepress_front.css', array(), AFFILIATEPRESS_VERSION);
            wp_register_style('affiliatepress_front_rtl_css', AFFILIATEPRESS_URL . 'css/affiliatepress_front_rtl.css', array(), AFFILIATEPRESS_VERSION);

            if($affiliatepress_force_enqueue == 1){

                wp_enqueue_style('affiliatepress_front_variables_css');
                wp_enqueue_style('affiliatepress_elements_front_css');               
                wp_enqueue_style('affiliatepress_component_css');
                wp_enqueue_style('affiliatepress_front_css');
                if(is_rtl()){
                    wp_enqueue_style('affiliatepress_front_rtl_css');   
                }
                
            }


        }
        
        /**
         * Function for set front js
         *
         * @param  mixed $affiliatepress_force_enqueue
         * @return void
        */
        function affiliatepress_set_front_js($affiliatepress_force_enqueue = 0 ){

            /* Plugin JS File */
            wp_register_script('affiliatepress_front_js', AFFILIATEPRESS_URL . 'js/affiliatepress_vue.min.js', array(), AFFILIATEPRESS_VERSION,false);
            wp_register_script('affiliatepress_axios_js', AFFILIATEPRESS_URL . 'js/affiliatepress_axios.min.js', array(), AFFILIATEPRESS_VERSION,false); 
            wp_register_script('affiliatepress_wordpress_vue_qs_js', AFFILIATEPRESS_URL . 'js/affiliatepress_wordpress_vue_qs_helper.js', array(), AFFILIATEPRESS_VERSION,false); 
            wp_register_script('affiliatepress_element_js', AFFILIATEPRESS_URL . 'js/affiliatepress_element.min.js', array(), AFFILIATEPRESS_VERSION,true);
            

            if($affiliatepress_force_enqueue == 1){

                $affiliatepress_data = 'var affiliatepress_ajax_obj = '.wp_json_encode( array('ajax_url' => admin_url( 'admin-ajax.php'))).';';
                wp_add_inline_script('affiliatepress_front_js', $affiliatepress_data, 'before');

                wp_enqueue_script('affiliatepress_front_js');
                wp_enqueue_script('affiliatepress_axios_js');                
                wp_enqueue_script('affiliatepress_wordpress_vue_qs_js');
                wp_enqueue_script('affiliatepress_element_js');
                wp_enqueue_script( 'moment' );                            

            }            

        }

                
        /**
         * Function for affiliate registration page shortcode 
         *
         * @return void
        */
        function affiliatepress_affiliate_creative_func($affiliatepress_atts, $affiliatepress_content, $affiliatepress_tag){
            global $wpdb, $affiliatepress_tbl_ap_creative, $affiliatepress_affiliate_panel,$AffiliatePress,$affiliatepress_affiliate_panel;  
            $affiliatepress_site_current_language = get_locale();
            $affiliatepress_defaults = array(
                'id'  => 0,
            );
            $affiliatepress_args = shortcode_atts($affiliatepress_defaults, $affiliatepress_atts, $affiliatepress_tag);
            $affiliatepress_id = (isset($affiliatepress_args['id']))?intval($affiliatepress_args['id']):0;
            $affiliatepress_dynamic_data_fields = array(); 
            $affiliatepress_dynamic_data_fields['id'] = $affiliatepress_id;
            $affiliatepress_dynamic_data_fields['creative'] = '';
            $affiliatepress_affiliate_id = $affiliatepress_affiliate_panel->affiliatepress_check_affiliate_user_allow_to_access_func();
            if(!$affiliatepress_affiliate_id){
                return '';
            }
            $affiliatepress_creative = $this->affiliatepress_select_record( true, '', $affiliatepress_tbl_ap_creative, '*', 'WHERE ap_creative_id = %d', array( $affiliatepress_id ), '', '', '', false, true,ARRAY_A);
            if(empty($affiliatepress_creative)){
                return '';
            }else{
                $affiliatepress_creative['image_url']               = '';
                $affiliatepress_creative['ap_creative_alt_text']    = stripslashes_deep($affiliatepress_creative['ap_creative_alt_text']);
                $affiliatepress_creative['ap_creative_name']        = stripslashes_deep($affiliatepress_creative['ap_creative_name']);
                $affiliatepress_creative['ap_creative_text']        = stripslashes_deep($affiliatepress_creative['ap_creative_text']);
                $affiliatepress_creative['ap_creative_description'] = stripslashes_deep($affiliatepress_creative['ap_creative_description']);
                if(!empty($affiliatepress_creative['ap_creative_image_url'])){
                    $affiliatepress_creative['image_url'] = AFFILIATEPRESS_UPLOAD_URL.'/'.$affiliatepress_creative['ap_creative_image_url'];                         
                }
                $affiliatepress_creative_landing_url = $affiliatepress_creative['ap_creative_landing_url'];
                $affiliatepress_creative_landing_url = $AffiliatePress->affiliatepress_get_affiliate_custom_link($affiliatepress_affiliate_id,$affiliatepress_creative_landing_url);
                $affiliatepress_creative['ap_creative_code'] = '';
                $affiliatepress_creative['ap_creative_code_preview'] = '';
                $affiliatepress_creative['image_data'] = array(
                    'width' => '',
                    'height' => '',
                    'type' => '',
                    'fileSize' => '',
                );                
                if($affiliatepress_creative['ap_creative_type'] == 'image'){
                    $affiliatepress_creative['ap_creative_code'] = '<a href="'.esc_url($affiliatepress_creative_landing_url).'"><img src="'.esc_url($affiliatepress_creative['image_url']).'" alt="'.esc_attr($affiliatepress_creative['ap_creative_alt_text']).'" /></a>';// phpcs:ignore
                    $affiliatepress_creative['ap_creative_code_preview'] = htmlentities($affiliatepress_creative['ap_creative_code']);
                    $affiliatepress_affiliate_upload_dir = AFFILIATEPRESS_UPLOAD_DIR.'/'.$affiliatepress_creative['ap_creative_image_url'];
                    $affiliatepress_creative['image_data'] = $affiliatepress_affiliate_panel->affiliatepress_get_image_info($affiliatepress_affiliate_upload_dir);                    
                }else{
                    $affiliatepress_creative['ap_creative_code'] = '<a href="'.$affiliatepress_creative_landing_url.'">'.$affiliatepress_creative['ap_creative_text'].'</a>';
                    $affiliatepress_creative['ap_creative_code_preview'] = htmlentities($affiliatepress_creative['ap_creative_code']);
                }                
                $affiliatepress_dynamic_data_fields['creative'] = $affiliatepress_creative;
            }

            $affiliatepress_uniq_id = uniqid();
            $this->affiliatepress_affiliatepress_set_front_css(1);
            $this->affiliatepress_set_front_js(1);
            
            $affiliatepress_front_booking_dynamic_helper_vars = '';
            $affiliatepress_front_booking_dynamic_helper_vars = apply_filters('affiliatepress_affiliate_creative_dynamic_helper_vars', $affiliatepress_front_booking_dynamic_helper_vars);

            $affiliatepress_dynamic_directive_data = '';
            $affiliatepress_dynamic_directive_data = apply_filters('affiliatepress_affiliate_creative_dynamic_directives', $affiliatepress_dynamic_directive_data);

                       
            $affiliatepress_dynamic_data_fields = apply_filters('affiliatepress_affiliate_creative_dynamic_data_fields', $affiliatepress_dynamic_data_fields);
            
            $affiliatepress_dynamic_on_load_methods_data = '';
            $affiliatepress_dynamic_on_load_methods_data = apply_filters('affiliatepress_affiliate_creative_dynamic_on_load_methods', $affiliatepress_dynamic_on_load_methods_data);          

            $affiliatepress_vue_methods_data = '';
            $affiliatepress_vue_methods_data = apply_filters('affiliatepress_affiliate_creative_dynamic_vue_methods', $affiliatepress_vue_methods_data);
            
            $affiliatepress_script_return_data = '';
            if (! empty($affiliatepress_front_booking_dynamic_helper_vars) ) {
                $affiliatepress_script_return_data .= $affiliatepress_front_booking_dynamic_helper_vars;
            }
            
            $affiliatepress_script_return_data .= "var affiliatepress_uniq_id_js_var = '" . $affiliatepress_uniq_id . "';";
            $affiliatepress_nonce = esc_html(wp_create_nonce('ap_wp_nonce'));

            $affiliatepress_script_return_data .= "var affiliatepress_uniq_id_js_var = '" . $affiliatepress_uniq_id . "';";
            $affiliatepress_nonce = esc_html(wp_create_nonce('ap_wp_nonce'));

            $affiliatepress_site_date = date('Y-m-d H:i:s', current_time( 'timestamp') ); // phpcs:ignore
            $affiliatepress_vue_root_element_id = '#affiliatepress_creative_form_' . $affiliatepress_uniq_id;
            $affiliatepress_vue_root_element_id_without_hash = 'affiliatepress_creative_form__' . $affiliatepress_uniq_id;
            $affiliatepress_vue_root_element_id_el = 'method_' . $affiliatepress_uniq_id;
            
            ob_start();
            $affiliatepress_shortcode_file_url = AFFILIATEPRESS_VIEWS_DIR.'/front/affiliate_creative_shortcode.php';
            include $affiliatepress_shortcode_file_url;            
            $affiliatepress_content = ob_get_clean();   

            ob_start();
            $affiliatepress_load_file_name = AFFILIATEPRESS_VIEWS_DIR . '/manage_language.php';                            
            include $affiliatepress_load_file_name;            
            $affiliatepress_localization_data_content = ob_get_clean();               
        
            $affiliatepress_script_return_data .= $affiliatepress_localization_data_content;
            $affiliatepress_script_return_data .= '

            var app = "";
            const { ref, createApp, reactive} = Vue;  
            const container = ref(null); 
            app = createApp({ 
				el: "' . $affiliatepress_vue_root_element_id . '",
				components:{  },				
				data(){
                    var affiliatepress_return_data_creative_form = '.$affiliatepress_dynamic_data_fields.';
					return affiliatepress_return_data_creative_form;
				},
				filters: {
					
				},
                beforeCreate(){                      
                    this.is_affiliate_creative_form_loader = "0";                    
				},
				created(){
					this.affiliatepress_load_reg_booking_form();                    
				},
				mounted(){
					'.$affiliatepress_dynamic_on_load_methods_data.'
				},
                computed: {

                },
                methods:{
                    affiliatepress_load_reg_booking_form(){
                        const vm = this;
                        setTimeout(function(){
                            vm.is_affiliate_creative_form_loader = "0";                            
                        }, 1000);
                    },                 
					'.$affiliatepress_vue_methods_data.'
				},
			});               
            app.use(ElementPlus, {
                locale: ElementPlusLocaleData,
            });            
            app.mount("'.$affiliatepress_vue_root_element_id.'");            
            ';            

            $affiliatepress_script_data = " var app;  
			var is_script_loaded_$affiliatepress_vue_root_element_id_el = false;
            affiliatepress_beforeload_data = '';
            if( null != document.getElementById('$affiliatepress_vue_root_element_id_without_hash') ){
                affiliatepress_beforeload_data = document.getElementById('$affiliatepress_vue_root_element_id_without_hash').innerHTML;
            }
            window.addEventListener('DOMContentLoaded', function() {
                if( is_script_loaded_$affiliatepress_vue_root_element_id_el == false) {
                    is_script_loaded_$affiliatepress_vue_root_element_id_el = true;
                    ap_load_vue_shortcode_$affiliatepress_vue_root_element_id_el();
                }
            });
            window.addEventListener( 'elementor/popup/show', (event) => {
                let element = event.detail.instance.\$element[0].querySelector('.ap-review-container');
                if( 'undefined' != typeof element ){
                    document.getElementById('$affiliatepress_vue_root_element_id_without_hash').innerHTML = affiliatepress_beforeload_data;
                    ap_load_vue_shortcode_$affiliatepress_vue_root_element_id_el();
                }
            });
            function ap_load_vue_shortcode_$affiliatepress_vue_root_element_id_el(){
                {$affiliatepress_script_return_data}           
            }";            
            
            wp_add_inline_script('affiliatepress_element_js', $affiliatepress_script_data, 'after');

            return do_shortcode( $affiliatepress_content );                

        }


    }
}
global $affiliatepress_creative_shortcode;
$affiliatepress_creative_shortcode = new affiliatepress_creative_shortcode();
