<?php
if ( !defined( 'ABSPATH' ) || !class_exists('NF_Abstracts_Action')) { exit; }
    
    final class affiliatepress_ninja_form_action extends NF_Abstracts_Action{

        protected $_name  = 'affiliatepress_add_commission';
        
        protected $_tags = array( 'affiliate', 'affiliatepress', 'referral','commission','AffiliatePress' );
        
        protected $_timing = 'late';
        
        protected $_priority = '10';

                
        /**
         * Function for construct
         *
         * @return void
        */
        public function __construct() {
            parent::__construct();

            global $affiliatepress_global_options;

            $affiliatepress_commission_all_options = array();

            
            $affiliatepress_global_options_data = $affiliatepress_global_options->affiliatepress_global_options();
            $affiliatepress_all_commissions_type = $affiliatepress_global_options_data['commissions_type'];

            foreach( $affiliatepress_all_commissions_type as $affiliatepress_commissions_type ) {                
                if($affiliatepress_commissions_type['value'] != 'subscription'){
                    $affiliatepress_commission_all_options[] = array(
                        'label' => $affiliatepress_commissions_type['text'],
                        'value' => $affiliatepress_commissions_type['value']
                    );    
                }
            }
            
            $this->_nicename = esc_html__( 'AffiliatePress Commission', 'affiliatepress-affiliate-marketing');
            $this->_settings[ 'affiliatepress_total' ] = array(
                'name'           => 'affiliatepress_total',
                'label'          => esc_html__( 'Total Amount Field', 'affiliatepress-affiliate-marketing'),
                'type'           => 'textbox',
                'width'          => 'full',
                'value'          => '',
                'group'          => 'primary',
                'use_merge_tags' => array(
                    'exclude' => array( // phpcs:ignore
                        'post',
                        'user',
                        'system'
                    )
                )
            );

            $this->_settings[ 'affiliatepress_email' ] = array(
                'name'           => 'affiliatepress_email',
                'label'          => esc_html__( 'Customer Email', 'affiliatepress-affiliate-marketing'),
                'type'           => 'textbox',
                'width'          => 'full',
                'value'          => '',
                'group'          => 'primary',
                'use_merge_tags' => array(
                    'exclude' => array(// phpcs:ignore
                        'user'
                    )
                )
            );

            $this->_settings[ 'affiliatepress_description' ] = array(
                'name'           => 'affiliatepress_description',
                'label'          => esc_html__( 'Description', 'affiliatepress-affiliate-marketing'),
                'type'           => 'textbox',
                'width'          => 'full',
                'value'          => '',
                'group'          => 'primary',
                'use_merge_tags' => array(
                    'exclude' => array(// phpcs:ignore
                        'post',
                        'user',
                        'system'
                    )
                )
            );

            $this->_settings[ 'affiliatepress_commission_type' ] = array(
                'name'           => 'affiliatepress_commission_type',
                'label'          => esc_html__( 'Commission Type', 'affiliatepress-affiliate-marketing'),
                'type'           => 'select',
                'options'        => $affiliatepress_commission_all_options,
                'width'          => 'full',
                'group'          => 'primary',
            );

            $this->_settings = apply_filters('affiliatepress_ninjaforms_add_product_settings', $this->_settings);
        }

           
        
        /**
         * Save Action
         *
         * @param  array $affiliatepress_act_settings
         * @return void
        */
        public function save($affiliatepress_act_settings){
            
        }        
        
        /**
         * process the affiliatepress commission
         *
         * @param  array $affiliatepress_act_settings
         * @param  int $affiliatepress_form_id
         * @param  array $affiliatepress_data
         * @return array
        */
        public function process( $affiliatepress_act_settings, $affiliatepress_form_id, $affiliatepress_data ) {

            if( isset( $affiliatepress_data['settings']['is_preview'] ) && $affiliatepress_data['settings']['is_preview'] ){
                return $affiliatepress_data;
            }
    
            if (!isset($affiliatepress_data[ 'actions' ][ 'save' ][ 'sub_id' ] ) ){

                $affiliatepress_sub = Ninja_Forms()->form( $affiliatepress_form_id )->sub()->get();              
                $affiliatepress_hidden_field_types = array();
                
                foreach( $affiliatepress_data['fields'] as $affiliatepress_field ){

                    if( in_array( $affiliatepress_field[ 'type' ], array_values( $affiliatepress_hidden_field_types ) ) ) {
                        $affiliatepress_data['actions']['save']['hidden'][] = $affiliatepress_field['type'];
                        continue;
                    }    
                    $affiliatepress_sub->update_field_value( $affiliatepress_field['id'], $affiliatepress_field['value'] );

                }
    
                if( isset( $affiliatepress_data[ 'extra' ] ) ) {
                    $affiliatepress_sub->update_extra_values( $affiliatepress_data['extra'] );
                }

                $affiliatepress_sub->save();    
                $affiliatepress_data[ 'actions' ][ 'save' ][ 'sub_id' ] = $affiliatepress_sub->get_id();
    
            }
    
            $affiliatepress_total_amount      = $this->affiliatepress_get_field_value('affiliatepress_total', $affiliatepress_act_settings);
            $affiliatepress_sub_id            = (isset($affiliatepress_data['actions']['save']['sub_id']))?intval($affiliatepress_data['actions']['save']['sub_id']):'';
            $affiliatepress_name              = $this->affiliatepress_get_product_or_form_name( $affiliatepress_act_settings, $affiliatepress_data );
            $affiliatepress_customer_email    = $this->affiliatepress_get_field_value( 'affiliatepress_email',$affiliatepress_act_settings );
            $affiliatepress_commission_type   = isset($affiliatepress_act_settings['affiliatepress_commission_type'])?$affiliatepress_act_settings[ 'affiliatepress_commission_type' ]:'';
            

            $affiliatepress_data_new = array(
                'total_amount'    => $affiliatepress_total_amount,
                'sub_id'          => $affiliatepress_sub_id,
                'name'            => $affiliatepress_name,
                'customer_email'  => $affiliatepress_customer_email,
                'commission_type' => (!empty($affiliatepress_commission_type))?$affiliatepress_commission_type:'sale',
                'form_id'         => $affiliatepress_form_id
            );

            $affiliatepress_send_data = $affiliatepress_data[ 'extra' ][ 'affiliatepress' ] = $affiliatepress_data_new;
            do_action( 'affiliatepress_add_ninja_form_commission', $affiliatepress_data_new );
    
            return $affiliatepress_data;
        }

                
        /**
         * Function for get product or form name
         *
         * @param  array $affiliatepress_act_settings
         * @param  array $affiliatepress_data
         * @return array
        */
        private function affiliatepress_get_product_or_form_name( $affiliatepress_act_settings, $affiliatepress_data ){

            $affiliatepress_description = '';
            $affiliatepress_products    = $this->affiliatepress_get_product_data( $affiliatepress_data );
            if( !empty( $affiliatepress_products ) ) {
                $affiliatepress_description = $affiliatepress_products;
            }elseif( ! empty( $affiliatepress_act_settings[ 'affiliatepress_description' ] ) ) {
                $affiliatepress_description = $affiliatepress_act_settings[ 'affiliatepress_description' ];
            } elseif( ! empty( $affiliatepress_data[ 'settings' ][ 'title' ] ) ) {
                $affiliatepress_description = $affiliatepress_data[ 'settings' ][ 'title' ];
            }

            return $affiliatepress_description;
        }
        
        
        /**
         * Function for get field value
         *
         * @param  string $affiliatepress_key
         * @param  array $affiliatepress_act_settings
         * @return array
        */
        private function affiliatepress_get_field_value($affiliatepress_key,$affiliatepress_act_settings ) {
            $affiliatepress_field_value = '';
            if( isset($affiliatepress_act_settings[$affiliatepress_key])){
                $affiliatepress_field_value = $affiliatepress_act_settings[$affiliatepress_key];
            }
            return $affiliatepress_field_value;
        }       
        
        /**
         * Function for get product data 
         *
         * @param  array $affiliatepress_data
         * @return void
        */
        private function affiliatepress_get_product_data( $affiliatepress_data ) {
            $affiliatepress_product_name = '';    
            if( ! empty( $affiliatepress_data['extra']['product_fields'] ) ) {    
                $affiliatepress_product_labels = array();
                foreach( $affiliatepress_data['fields'] as $affiliatepress_field ) {
                    if( 'product' == $affiliatepress_field[ 'type' ] ) {
                        $affiliatepress_product_labels[] = $affiliatepress_field['label'];
                        continue;
                    }    
                }
                $affiliatepress_product_name = implode( ', ', $affiliatepress_product_labels );
            }
            return $affiliatepress_product_name;
        }        


    }
