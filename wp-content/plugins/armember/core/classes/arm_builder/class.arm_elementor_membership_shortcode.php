<?php 
class arm_membership_elementcontroller{
  
   function __construct() {
         add_action( 'plugins_loaded', array( $this, 'arm_element_widget' ) );
         add_action('elementor/editor/before_enqueue_scripts',function(){
            wp_register_style('arm_admin_elementor', MEMBERSHIPLITE_URL . '/css/arm_elementor_section.css', array(), MEMBERSHIP_VERSION);
            wp_enqueue_style('arm_admin_elementor');
         });
   } 
   function arm_element_widget(){
      if ( ! did_action( 'elementor/loaded' ) ) {
         return;
      }
      
      if (file_exists(MEMBERSHIP_WIDGET_DIR . '/arm_elm_widgets/class.arm_elementor_widget_element.php')) {
         require_once(MEMBERSHIP_WIDGET_DIR . '/arm_elm_widgets/class.arm_elementor_widget_element.php');
      }

      if (file_exists(MEMBERSHIP_WIDGET_DIR . '/arm_elm_widgets/class.arm_elementor_control.php')) {
         require_once( MEMBERSHIP_WIDGET_DIR . '/arm_elm_widgets/class.arm_elementor_control.php');
      }   
   }
}
?>