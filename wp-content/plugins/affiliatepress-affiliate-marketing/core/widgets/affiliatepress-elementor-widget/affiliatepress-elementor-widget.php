<?php

if( !defined( 'ABSPATH' ) ){
    die;
}

class affiliatepress_elementor_widget{

    function __construct(){
        /**Elementor Add Affiliate page */
        add_action( 'elementor/widgets/register', array( $this, 'affiliatepress_register_elementor_widget' ));
    }
    
    /**
     * Function for add elemnetor in affiliate shortcode
     *
     * @param  mixed $widgets_manager
     * @return void
     */
    function affiliatepress_register_elementor_widget( $widgets_manager ){

        require_once __DIR__ . '/affiliatepress-elementor-form-widget.php';
        require_once __DIR__ . '/affiliatepress-elementor-affiliate-panel-widget.php';

        $widgets_manager->register( new \affiliatepress_Elementor_Form_Widget() );
        $widgets_manager->register( new \affiliatepress_Elementor_affiliate_Panel_Widget() );
    }

}

new affiliatepress_elementor_widget();