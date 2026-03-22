<?php

/**
 * WP Fusion Download Section for EDD tabbed interface.
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2024, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.46.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP Fusion Download Section for EDD tabbed interface.
 *
 * @since 3.46.7
 */
class WPF_EDD_Download_Section extends \EDD\Admin\Downloads\Editor\Section {

	/**
	 * Section ID.
	 *
	 * @since 3.46.7
	 * @var string
	 */
	protected $id = 'wp_fusion';

	/**
	 * Section priority.
	 *
	 * @since 3.46.7
	 * @var int
	 */
	protected $priority = 20;

	/**
	 * Get the section label.
	 *
	 * @since 3.46.7
	 * @return string
	 */
	public function get_label() {
		return wpf_logo_svg( 20 ) . ' ' . __( 'WP Fusion', 'wp-fusion' );
	}

	/**
	 * Render the section.
	 *
	 * @since 3.46.7
	 * @return void
	 */
	public function render() {

		if ( ! $this->item ) {
			return;
		}

		$post_id = $this->item->ID;

		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		// Get existing settings.
		$settings = array(
			'apply_tags'          => array(),
			'apply_tags_refunded' => array(),
		);

		if ( get_post_meta( $post_id, 'wpf-settings-edd', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post_id, 'wpf-settings-edd', true ) );
		}

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'wpf_meta_box_edd', 'wpf_meta_box_edd_nonce' );

		?>
		<div class="edd-form-group">
			<h3>
				<?php esc_html_e( 'WP Fusion Download Settings', 'wp-fusion' ); ?>
			</h3>
			<div class="edd-form-group__control">
				<table class="form-table wpf-edd-settings">
					<tbody>
						<tr>
							<th scope="row">
								<label for="apply_tags"><?php esc_html_e( 'Apply Tags', 'wp-fusion' ); ?>:</label>
							</th>
							<td>
								<?php
								wpf_render_tag_multiselect(
									array(
										'setting'   => $settings['apply_tags'],
										'meta_name' => 'wpf-settings-edd',
										'field_id'  => 'apply_tags',
									)
								);
								?>
								<p class="description">
									<?php
									printf(
										/* translators: %s: CRM name */
										esc_html__( 'Apply these tags in %s when purchased.', 'wp-fusion' ),
										esc_html( wp_fusion()->crm->name )
									);
									?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="apply_tags_refunded"><?php esc_html_e( 'Refund Tags', 'wp-fusion' ); ?>:</label>
							</th>
							<td>
								<?php
								wpf_render_tag_multiselect(
									array(
										'setting'   => $settings['apply_tags_refunded'],
										'meta_name' => 'wpf-settings-edd',
										'field_id'  => 'apply_tags_refunded',
									)
								);
								?>
								<p class="description">
									<?php
									printf(
										/* translators: %s: CRM name */
										esc_html__( 'Apply these tags in %s when refunded.', 'wp-fusion' ),
										esc_html( wp_fusion()->crm->name )
									);
									?>
								</p>
							</td>
						</tr>

						<?php
						/**
						 * Allows other plugins to add additional fields to the WP Fusion tab
						 *
						 * @since 3.46.7
						 *
						 * @param \WP_Post $post The post object.
						 * @param array    $settings The current settings.
						 */
						do_action( 'wpf_edd_meta_box_inner', $this->item, $settings );
						?>
					</tbody>
				</table>

				<?php
				/**
				 * Allows other plugins to add additional content to the WP Fusion tab
				 *
				 * @since 3.46.7
				 *
				 * @param \WP_Post $post The post object.
				 * @param array    $settings The current settings.
				 */
				do_action( 'wpf_edd_meta_box', $this->item, $settings );
				?>
			</div>
		</div>
		<?php
	}
}
