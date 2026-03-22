<?php
/**
 * WP Fusion - FluentCommunity Admin Interface
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2024, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.44.20
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * FluentCommunity Admin Interface.
 *
 * @since 3.44.20
 */
class WPF_FluentCommunity_Admin {

	/**
	 * The main integration instance.
	 *
	 * @since 3.44.20
	 * @var   WPF_FluentCommunity
	 */
	private $integration;

	/**
	 * Constructor.
	 *
	 * @since 3.44.20
	 *
	 * @param WPF_FluentCommunity $integration The main integration instance.
	 */
	public function __construct( $integration ) {
		$this->integration = $integration;

		// Add hooks directly in constructor.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 40 );
	}

	/**
	 * Add admin menu item.
	 *
	 * @since 3.44.20
	 */
	public function admin_menu() {

		$id = add_submenu_page(
			'fluent-community',
			// translators: %s is the CRM name.
			sprintf( __( '%s Integration', 'wp-fusion' ), wp_fusion()->crm->name ),
			__( 'WP Fusion', 'wp-fusion' ),
			'manage_options',
			'fluent-community-wpf-settings',
			array( $this, 'render_admin_menu' )
		);

		add_action( 'load-' . $id, array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 3.44.20
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'options-css', WPF_DIR_URL . 'includes/admin/options/css/options.css' );
		wp_enqueue_style( 'wpf-options', WPF_DIR_URL . 'assets/css/wpf-options.css' );
	}

	/**
	 * Render admin menu.
	 *
	 * @since 3.44.20
	 */
	public function render_admin_menu() {

		if ( ! empty( $_POST ) ) {
			$this->integration->save_settings();
		}

		?>
		<div class="wrap">

			<form id="wpf-fluent-settings" action="" method="post">

				<h1><?php echo wp_kses_post( wpf_logo_svg() ); ?> 
				<?php
				// translators: %s is the CRM name.
				printf( esc_html__( '%s Integration', 'wp-fusion' ), esc_html( wp_fusion()->crm->name ) );
				?>
				</h1>

				<?php wp_nonce_field( 'wpf_fluent_settings', 'wpf_fluent_settings_nonce' ); ?>

				<input type="hidden" name="action" value="update">

				<?php if ( isset( $_POST['wpf_fluent_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpf_fluent_settings_nonce'] ) ), 'wpf_fluent_settings' ) ) : ?>
					<div id="message" class="updated fade">
						<p><strong><?php esc_html_e( 'Settings saved', 'wp-fusion' ); ?></strong></p>
					</div>
				<?php endif; ?>

				<h3><?php esc_html_e( 'Access Control', 'wp-fusion' ); ?></h3>
				<p class="description">
				<?php
				// translators: %s is the CRM name.
				printf( esc_html__( 'Users will only be able to access the community portal if they have any of the specified tags in %s.', 'wp-fusion' ), esc_html( wp_fusion()->crm->name ) );
				?>
				</p>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="fc_access_tags"><?php esc_html_e( 'Required Tags', 'wp-fusion' ); ?></label>
							</th>
							<td>
								<?php
								$args = array(
									'setting'   => $this->integration->get_setting( 'fc_access_tags', array() ),
									'meta_name' => 'wpf-settings',
									'field_id'  => 'fc_access_tags',
								);

								wpf_render_tag_multiselect( $args );
								?>
								<p class="description"><?php esc_html_e( 'If no tags are selected, all logged in users will be able to access the community portal.', 'wp-fusion' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wpf-redirect"><?php esc_html_e( 'Redirect If Denied', 'wp-fusion' ); ?></label>
							</th>
							<td>
								<?php

								$settings = array(
									'redirect' => $this->integration->get_setting( 'redirect' ),
								);

								wp_fusion()->admin_interfaces->page_redirect_select( null, $settings );

								?>
								<p class="description">
									<?php
									$base_url = \FluentCommunity\App\Services\Helper::baseUrl();
									printf(
										// translators: %1$s and %2$s are link tags.
										esc_html__( 'Select a page or enter a URL to redirect to if access is denied. Leave blank to show the %1$srestricted content message%2$s.', 'wp-fusion' ),
										'<a href="' . esc_url( $base_url . 'admin/settings' ) . '" target="_blank">',
										'</a>'
									);
									?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit"><input name="Submit" type="submit" class="button-primary" value="<?php esc_html_e( 'Save Changes', 'wp-fusion' ); ?>"/></p>

			</form>
		</div>
		<?php
	}
}
