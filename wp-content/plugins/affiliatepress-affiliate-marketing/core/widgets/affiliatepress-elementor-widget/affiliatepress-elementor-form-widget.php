<?php

if( !defined( 'ABSPATH' ) ) { exit; }

use \Elementor\Controls_Manager;

class affiliatepress_Elementor_Form_Widget extends \Elementor\Widget_Base{

    public function get_name(){
        return 'affiliatepress_Elementor_Form_Widget';
    }

    public function get_title(){
        return esc_html__('Affiliate Signup - AffiliatePress', 'affiliatepress-affiliate-marketing') . '<style>
        .affiliatepress_element_icon{
            display: inline-block;
            width: 35px;
            height: 24px;
            background-image: url(' . AFFILIATEPRESS_IMAGES_URL . '/affiliatepress_logo_icon.png);
            background-repeat: no-repeat;
            background-size: contain;
        }
        </style>';
    }

    public function get_icon(){
        return 'affiliatepress_element_icon';
    }

    public function get_categories(){
        return [ 'general' ];
    }

    public function get_keywords(){
        return [ 'Affiliate', 'Commission tracking' , ];
    }

    protected function render() {
        echo '[affiliatepress_affiliate_registration]';
    }

}