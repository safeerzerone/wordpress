<?php
/**
 * WP Fusion - LearnDash Quizzes
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2025, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.46.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * LearnDash Quizzes integration.
 *
 * Handles quiz completion tracking, scoring, essay submission, and answer syncing.
 *
 * @since 3.46.0
 */
class WPF_LearnDash_Quizzes {

	/**
	 * Gets things started.
	 *
	 * @since 3.46.0
	 */
	public function __construct() {

		// Quiz completion.
		add_action( 'learndash_quiz_submitted', array( $this, 'quiz_completed' ), 5, 2 ); // all LD quizzes pass through this since 4.12.0.
		add_action( 'learndash_quiz_completed', array( $this, 'quiz_completed' ), 5, 2 ); // needed for compatibility with GrassBlade xAPI Companion and other addons.

		// Essay submission.
		add_action( 'learndash_new_essay_submitted', array( $this, 'essay_submitted' ), 5, 2 );

		// Quiz answers.
		add_action( 'ldadvquiz_answered', array( $this, 'quiz_answered' ), 10, 3 );

		// Settings fields.
		add_filter( 'learndash_quiz_settings_fields_wpf', array( $this, 'quiz_settings_fields' ), 10, 2 );

		// Meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 20, 2 );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ), 20, 2 );
	}

	/**
	 * Quiz Completed.
	 *
	 * Applies tags when a LearnDash quiz is completed.
	 *
	 * @since 3.46.0
	 *
	 * @param array   $data The quiz data.
	 * @param WP_User $user The user.
	 */
	public function quiz_completed( $data, $user ) {

		if ( doing_action( 'learndash_quiz_completed' ) && did_action( 'learndash_quiz_submitted' ) ) {
			// since WPF 3.43.4 and LD 4.12.0, the learndash_quiz_submitted hook is the primary one for
			// tracking quiz completions, since learndash_quiz_completed doesn't run on quizzes that don't
			// have a parent course. However, we're still hooked to learndash_quiz_completed for backwards
			// compatibility with older versions of LD and LD addons.
			return;
		}

		if ( isset( $data['quiz']->ID ) ) {
			$quiz_id = $data['quiz']->ID;
		} else {
			// For grading in the admin.
			$quiz_id = $data['quiz'];
		}

		$update_data = array();

		// Final score.

		// pre 3.41.35, the field mapping was stored in the generic wpf-settings, contact_fields.
		$settings = (array) get_post_meta( $quiz_id, 'wpf-settings', true );

		if ( wpf_is_field_active( "quiz_final_score_{$quiz_id}" ) ) {

			// New 3.41.35+ storage.
			$update_data[ "quiz_final_score_{$quiz_id}" ] = $data['percentage'];

		} elseif ( ! empty( $settings['final_score_field'] ) && ! empty( $settings['final_score_field']['crm_field'] ) ) {

			$update_data[ $settings['final_score_field']['crm_field'] ] = $data['percentage'];

		}

		// Final points.

		if ( wpf_is_field_active( "quiz_final_points_{$quiz_id}" ) ) {

			// New 3.41.35+ storage.
			$update_data[ "quiz_final_points_{$quiz_id}" ] = $data['percentage'];

		} elseif ( ! empty( $settings['final_points_field'] ) && ! empty( $settings['final_points_field']['crm_field'] ) ) {

			$update_data[ $settings['final_points_field']['crm_field'] ] = $data['points'];

		}

		// Add category score to CRM field.

		if ( isset( $_POST['results']['comp'] ) && isset( $_POST['results']['comp']['cats'] ) ) {

			$category_results = array_map( 'intval', $_POST['results']['comp']['cats'] );

			foreach ( $category_results as $id => $category_score ) {
				$update_data[ "quiz_category_score_{$quiz_id}_{$id}" ] = $category_score;
			}
		}

		wp_fusion()->user->push_user_meta( $user->ID, $update_data );

		// Apply tags:

		$settings = (array) get_post_meta( $quiz_id, 'wpf-settings-learndash', true );

		// If the quiz is passed.
		if ( ! empty( $data['pass'] ) && ! empty( $settings['apply_tags_ld'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_ld'], $user->ID );
		}

		// If the quiz is failed.
		if ( empty( $data['pass'] ) && ! empty( $settings['apply_tags_ld_quiz_fail'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_ld_quiz_fail'], $user->ID );
		}
	}

	/**
	 * Essay Submitted.
	 *
	 * Applies tags when a user submits an essay.
	 *
	 * @since 3.46.0
	 *
	 * @param int   $essay_id   The essay ID.
	 * @param array $essay_args The essay args.
	 */
	public function essay_submitted( $essay_id, $essay_args ) {

		$quiz_id  = get_post_meta( $essay_id, 'quiz_post_id', true );
		$user_id  = $essay_args['post_author'];
		$settings = get_post_meta( $quiz_id, 'wpf-settings-learndash', true );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_ld_essay_submitted'] ) ) {

			$message = 'User submitted essay for Learndash Quiz <a href="' . admin_url( 'post.php?post=' . $quiz_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $quiz_id ) . '</a>';

			wpf_log( 'info', $user_id, $message . '. Applying tags.' );

			wp_fusion()->user->apply_tags( $settings['apply_tags_ld_essay_submitted'] );

		}
	}

	/**
	 * Sync quiz question answers to custom fields when quiz answered.
	 *
	 * @since 3.46.0
	 *
	 * @param array $results         The quiz results.
	 * @param mixed $quiz            The quiz object.
	 * @param array $question_models The question models.
	 */
	public function quiz_answered( $results, $quiz, $question_models ) {

		$contact_id = wp_fusion()->user->get_contact_id();

		if ( empty( $contact_id ) ) {
			return;
		}

		$questions_and_answers = array();

		foreach ( $results as $key => $result ) {

			if ( ! empty( $result['e']['r'] ) ) {

				$questions_and_answers[ $key ] = $result['e']['r'];

			} else {

				// Essay questions.
				$questions_and_answers[ $key ] = $_POST['data']['responses'][ $key ]['response'];

			}
		}

		// Map the question IDs into post IDs.
		foreach ( $question_models as $post_id => $model ) {

			$answerData = $model->getAnswerData();

			foreach ( $questions_and_answers as $key => $result ) {

				if ( $key == $model->getId() ) {

					// Convert multiple choice from true / false into the selected option.
					if ( is_array( $result ) ) {

						foreach ( $result as $n => $multiple_choice_answer ) {

							if ( true == $multiple_choice_answer ) {

								$answers = $model->getAnswerData();

								foreach ( $answers as $x => $answer ) {

									if ( $x == $n ) {

										$result = $answer->getAnswer();
										break 2;

									}
								}
							}
						}
					}

					$questions_and_answers[ $post_id ] = $result;
					unset( $questions_and_answers[ $key ] );
				}
			}
		}

		$update_data = array();

		foreach ( $questions_and_answers as $post_id => $answer ) {

			$settings = get_post_meta( $post_id, 'wpf-settings-learndash', true );

			if ( ! empty( $settings ) && ! empty( $settings['crm_field'] ) ) {

				$update_data[ $settings['crm_field'] ] = $answer;

			}
		}

		if ( ! empty( $update_data ) ) {

			wpf_log( 'info', wpf_get_current_user_id(), 'Syncing <a href="' . get_edit_post_link( $quiz->getPostId() ) . '">' . $quiz->getName() . '</a> quiz answers to ' . wp_fusion()->crm->name . ':', array( 'meta_array_nofilter' => $update_data ) );

			wp_fusion()->crm->update_contact( $contact_id, $update_data, false );

		}
	}

	/**
	 * Quiz Settings Fields.
	 *
	 * Registers LD quiz fields in the WP Fusion admin tab.
	 *
	 * @since 3.46.0
	 *
	 * @param array  $fields      Fields.
	 * @param string $metabox_key Metabox key.
	 * @return array Fields.
	 */
	public function quiz_settings_fields( $fields, $metabox_key ) {

		if ( wpf_get_option( 'admin_permissions' ) && ! current_user_can( 'manage_options' ) ) {
			return $fields;
		}

		$settings = array(
			'apply_tags_ld_essay_submitted' => array(),
			'apply_tags_ld_quiz_fail'       => array(),
		);

		$categories = array();

		global $post;

		if ( is_object( $post ) ) {

			$settings = wp_parse_args( get_post_meta( $post->ID, 'wpf-settings-learndash', true ), $settings );

			$category_mapper = new WpProQuiz_Model_CategoryMapper();
			$quiz_id         = (int) learndash_get_setting( $post->ID, 'quiz_pro' );
			$categories      = $category_mapper->fetchByQuiz( $quiz_id );

		}

		$new_options = array(
			'apply_tags_ld_essay_submitted' => array(
				'name'             => 'apply_tags_ld_essay_submitted',
				'label'            => __( 'Apply Tags When Essay Submitted', 'wp-fusion' ),
				'type'             => 'multiselect',
				'multiple'         => 'true',
				'display_callback' => array( wp_fusion()->integrations->learndash, 'display_wpf_tags_select' ),
				'desc'             => sprintf( __( 'These tags will be applied in %s when someone submits an essay.', 'wp-fusion' ), wp_fusion()->crm->name ),
				'help_text'        => sprintf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/learning-management/learndash/#course-specific-settings" target="_blank">', '</a>' ),
			),
			'apply_tags_ld'                 => array(
				'name'             => 'apply_tags_ld',
				'label'            => __( 'Apply Tags When Quiz Passed', 'wp-fusion' ),
				'type'             => 'multiselect',
				'multiple'         => 'true',
				'display_callback' => array( wp_fusion()->integrations->learndash, 'display_wpf_tags_select' ),
				'desc'             => sprintf( __( 'This tag will be applied in %1$s when someone passes this quiz.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name ),
				'help_text'        => sprintf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/learning-management/learndash/#course-specific-settings" target="_blank">', '</a>' ),
			),
			'apply_tags_ld_quiz_fail'       => array(
				'name'             => 'apply_tags_ld_quiz_fail',
				'label'            => __( 'Apply Tags When Quiz Failed', 'wp-fusion' ),
				'type'             => 'multiselect',
				'multiple'         => 'true',
				'display_callback' => array( wp_fusion()->integrations->learndash, 'display_wpf_tags_select' ),
				'desc'             => sprintf( __( 'This tag will be applied in %1$s when someone fails this quiz.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name ),
				'help_text'        => sprintf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/learning-management/learndash/#course-specific-settings" target="_blank">', '</a>' ),
			),
			'final_score_field'             => array(
				'name'             => "quiz_final_score_{$post->ID}",
				'label'            => __( 'Field - Final Score', 'wp-fusion' ),
				'type'             => 'select',
				'display_callback' => array( wp_fusion()->integrations->learndash, 'display_crm_field_dropdown' ),
				'desc'             => sprintf( __( 'Sync the final score for this quiz to a custom field in %s', 'wp-fusion' ), wp_fusion()->crm->name ),
			),
			'final_points_field'            => array(
				'name'             => "quiz_final_points_{$post->ID}",
				'label'            => __( 'Field - Final Points', 'wp-fusion' ),
				'type'             => 'select',
				'display_callback' => array( wp_fusion()->integrations->learndash, 'display_crm_field_dropdown' ),
				'desc'             => sprintf( __( 'Sync the final points for this quiz to a custom field in %s', 'wp-fusion' ), wp_fusion()->crm->name ),
			),
		);

		// Add category score fields options.
		foreach ( $categories as $category ) {

			$category_id = $category->getCategoryId();

			if ( ! empty( $category_id ) ) {
				$new_options[ 'category_score_field_' . $category_id ] = array(
					'name'             => "quiz_category_score_{$post->ID}_{$category_id}",
					'label'            => sprintf( __( 'Field - %s Score', 'wp-fusion' ), $category->getCategoryName() ),
					'type'             => 'select',
					'display_callback' => array( wp_fusion()->integrations->learndash, 'display_crm_field_dropdown' ),
					'desc'             => sprintf( __( 'Sync the final points for the category %1$s to the selected custom field in %2$s.', 'wp-fusion' ), $category->getCategoryName(), wp_fusion()->crm->name ),
				);
			}
		}

		$fields = wp_fusion()->settings->insert_setting_after( 'quiz_access_list', $fields, $new_options );
		return $fields;
	}

	/**
	 * Adds meta boxes.
	 *
	 * @since 3.46.0
	 *
	 * @param int    $post_id The post ID.
	 * @param object $data    The post data.
	 */
	public function add_meta_box( $post_id, $data ) {

		if ( wpf_get_option( 'admin_permissions' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}
		add_meta_box( 'wpf-learndash-meta', __( 'WP Fusion - Question Settings', 'wp-fusion' ), array( $this, 'meta_box_callback_question' ), 'sfwd-question' );
	}

	/**
	 * Displays meta box content (question).
	 *
	 * @since 3.46.0
	 *
	 * @param object $post The post object.
	 */
	public function meta_box_callback_question( $post ) {

		wp_nonce_field( 'wpf_meta_box_learndash', 'wpf_meta_box_learndash_nonce' );

		$settings = array(
			'crm_field' => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf-settings-learndash', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf-settings-learndash', true ) );
		}

		echo '<table class="form-table"><tbody>';

		echo '<tr>';

		echo '<th scope="row"><label for="tag_link">' . __( 'Sync to field', 'wp-fusion' ) . ':</label></th>';
		echo '<td>';

		wpf_render_crm_field_select( $settings['crm_field'], 'wpf-settings-learndash' );

		echo '<span class="description">' . sprintf( __( 'Sync answers to this question the selected custom field in %s.', 'wp-fusion' ), wp_fusion()->crm->name ) . '</span>';
		echo '</td>';

		echo '</tr>';

		echo '</tbody></table>';
	}

	/**
	 * Save Meta Box Data.
	 *
	 * Runs when WPF meta box is saved on a quiz or question.
	 *
	 * @since 3.46.0
	 *
	 * @param int    $post_id Post ID.
	 * @param object $post    Post object.
	 */
	public function save_meta_box_data( $post_id, $post ) {

		if ( ! in_array( $post->post_type, array( 'sfwd-quiz', 'sfwd-question' ) ) ) {
			return;
		}

		// As of LD 3.2.2 this runs on every lesson in the builder when the course is saved, so we'll check for that here to avoid having the lesson settings overwritten by the course.
		if ( isset( $_POST['post_ID'] ) && $_POST['post_ID'] != $post_id ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$data = array();

		if ( ! empty( $_POST['wpf-settings-learndash'] ) ) {
			$data = $_POST['wpf-settings-learndash'];
		}

		if ( ! empty( $data ) ) {

			$data = WPF_Admin_Interfaces::sanitize_tags_settings( $data );
			update_post_meta( $post_id, 'wpf-settings-learndash', $data );

			// Copy custom fields to main contact fields.

			$contact_fields = wpf_get_option( 'contact_fields', array() );

			// Quiz progress fields.

			$fields = array( "quiz_final_score_{$post_id}", "quiz_final_points_{$post_id}" );

			foreach ( $fields as $field ) {

				if ( isset( $data[ $field ] ) && ! empty( $data[ $field ]['crm_field'] ) ) {

					// Also copy to the main settings.
					$contact_fields[ $field ]['crm_field'] = $data[ $field ]['crm_field'];
					$contact_fields[ $field ]['active']    = true;

				} elseif ( isset( $contact_fields[ $field ] ) ) {

					unset( $contact_fields[ $field ] );

				}
			}

			// Quiz category scores. We need to use the Learndash Category Mapper here to get
			// the categories.

			$category_mapper = new WpProQuiz_Model_CategoryMapper();
			$quiz_id         = (int) learndash_get_setting( $post_id, 'quiz_pro' );
			$categories      = $category_mapper->fetchByQuiz( $quiz_id );

			$field = 'category_score_field_';

			foreach ( $categories as $category ) {

				$category_id = $category->getCategoryId();

				// i.e. quiz_category_score_123_1.
				$key = "quiz_category_score_{$post_id}_{$category_id}";

				if ( ! empty( $data[ $key ]['crm_field'] ) ) {

					// Also copy to the main settings.
					$contact_fields[ $key ]['crm_field'] = $data[ $key ]['crm_field'];
					$contact_fields[ $key ]['active']    = true;

				} elseif ( isset( $contact_fields[ $key ] ) ) {

					unset( $contact_fields[ $key ] );

				}
			}

			wp_fusion()->settings->set( 'contact_fields', $contact_fields );

		} elseif ( empty( $data ) && isset( $_POST['action'] ) && 'editpost' === $_POST['action'] ) {
			delete_post_meta( $post_id, 'wpf-settings-learndash' );
		}
	}
}
