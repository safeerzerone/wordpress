<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if (! defined('WPINC') ) {
    die;
}
class AffiliatePress_VCExtend
{
    protected static $affiliatepress_instance    = null;
    var $affiliatepress_is_affiliatepress_vdextend = 0;

    public function __construct()
    {
        /**Add SignUp Form SHortcode */
        add_action('init', array( $this, 'affiliatepress_signupForm' ));

        /**Add Affiliate Panle SHortcode */
        add_action('init', array( $this, 'affiliatepress_affiliate_panel' ));

        /**Add SHortcode  Load*/
        add_action('init', array( $this, 'affiliatepress_init_all_shortcode' ));
    }    
    /**
     * Function For Add visual composer in add page
     *
     * @return void
     */
    public function affiliatepress_init_all_shortcode()
    {
        if (function_exists('vc_add_shortcode_param') ) {
            vc_add_shortcode_param('affiliatepress_signupform_shortcode', array( $this, 'affiliatepress_signupform_shortcode_html' ));
            vc_add_shortcode_param('affiliatepress_affiliate_panel_shortcode', array( $this, 'affiliatepress_affiliate_panel_shortcode_html' ));
        }
    }    
    /**
     * Function For visual composer in add signup form attributes
     *
     * @return void
     */
    public function affiliatepress_signupForm()
    {
        if (function_exists('vc_map') ) {
            vc_map(
                array(
                'name'        => __('Affiliate Signup - AffiliatePress', 'affiliatepress-affiliate-marketing'),
                'description' => '',
                'base'        => 'affiliatepress_affiliate_registration',
                'category'    => __('AffiliatePress', 'affiliatepress-affiliate-marketing'),
                'class'       => '',
                'controls'    => 'full',
                'icon'        => AFFILIATEPRESS_IMAGES_URL . '/affiliatepress_logo_icon.png',
                'params'      => array(
                                    array(
                                    'type'        => 'affiliatepress_signupform_shortcode',
                                    'heading'     => false,
                                    'param_name'  => 'affiliatepress_signupForm',
                                    'value'       => '',
                                    'description' => '&nbsp;',
                                    'admin_label' => true,
                                    ),
                                ),
                )
            );
        }
    }    
    /**
     * Function For Add Signup form
     *
     * @param  array $affiliatepress_settings
     * @param  mixed $affiliatepress_value
     * @return void
     */
    public function affiliatepress_signupform_shortcode_html( $affiliatepress_settings, $affiliatepress_value )
    {
        echo '<input id="' . esc_attr($affiliatepress_settings['param_name']) . '" name="' . esc_attr($affiliatepress_settings['param_name']) . '" class=" ' . esc_attr($affiliatepress_settings['param_name']) . ' ' . esc_attr($affiliatepress_settings['type']) . '_armfield" type="hidden" value="' . esc_attr($affiliatepress_value) . '" />';
        ?>
        <?php
        if ($this->is_affiliatepress_vdextend == 0 ) {
            $this->is_affiliatepress_vdextend = 1;
            ?>
            <div><?php esc_html_e('Affiliate Signup - AffiliatePress', 'affiliatepress-affiliate-marketing'); ?></div>
            </div>
            <?php
        }
    }    

    /**
     * Function For visual composer in add Affiliate panel attributes
     *
     * @return void
     */
    public function affiliatepress_affiliate_panel()
    {
        if (function_exists('vc_map') ) {
            vc_map(
                array(
                'name'        => __('Affiliate Panel - AffiliatePress', 'affiliatepress-affiliate-marketing'),
                'description' => '',
                'base'        => 'affiliatepress_affiliate_panel',
                'category'    => __('AffiliatePress', 'affiliatepress-affiliate-marketing'),
                'class'       => '',
                'controls'    => 'full',
                'icon'        => AFFILIATEPRESS_IMAGES_URL . '/affiliatepress_logo_icon.png',
                'params'      => array(
                array(
                'type'        => 'affiliatepress_affiliate_panel_shortcode',
                'heading'     => false,
                'param_name'  => 'affiliatepress_affiliate_panel',
                'value'       => '',
                'description' => '&nbsp;',
                'admin_label' => true,
                ),
                ),
                )
            );
        }
    }
        
    /**
     * Function For Affiliate panel 
     *
     * @param  array $affiliatepress_settings
     * @param  mixed $affiliatepress_value
     * @return void
     */
    public function affiliatepress_affiliate_panel_shortcode_html( $affiliatepress_settings, $affiliatepress_value )
    {
        echo '<input id="' . esc_attr($affiliatepress_settings['param_name']) . '" name="' . esc_attr($affiliatepress_settings['param_name']) . '" class=" ' . esc_attr($affiliatepress_settings['param_name']) . ' ' . esc_attr($affiliatepress_settings['type']) . '_armfield" type="hidden" value="' . esc_attr($affiliatepress_value) . '" />';
        if ($this->is_affiliatepress_vdextend == 0 ) {
            $this->is_affiliatepress_vdextend = 1;
            ?>
            <div><?php esc_html_e('Affiliate Panel - AffiliatePress', 'affiliatepress-affiliate-marketing'); ?></div>
            </div>
            <?php
        }

    }
}
?>
