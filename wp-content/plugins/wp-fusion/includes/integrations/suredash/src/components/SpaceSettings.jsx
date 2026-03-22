import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import WpfSelect from '@verygoodplugins/wpfselect';

const SpaceSettingsComponent = ( { context } ) => {
	const { activeSpace, updateActiveSpace } = context;

	const settings = activeSpace.wpf_settings_suredash || {};
	const isCourse = activeSpace.integration === 'course';
	const selectRef = useRef( null );
	const settingsRef = useRef( settings );
	const updateActiveSpaceRef = useRef( updateActiveSpace );

	// Get redirect value and title for display.
	const redirectValue = settings.redirect || '';
	const redirectTitle = settings.redirect_label || redirectValue;

	useEffect( () => {
		settingsRef.current = settings;
	}, [ settings ] );

	useEffect( () => {
		updateActiveSpaceRef.current = updateActiveSpace;
	}, [ updateActiveSpace ] );

	useEffect( () => {
		if ( ! selectRef.current || ! window.jQuery || ! window.jQuery.fn.select2 ) {
			return undefined;
		}

		const $ = window.jQuery;
		const $select = $( selectRef.current );

		const selectPageOptions = {
			allowClear: true,
			minimumInputLength: 3,
			tags: true,
			createTag: function ( params ) {
				const term = params.term.trim();
				if ( term === '' ) {
					return null;
				}
				// Only allow URLs.
				if ( term.indexOf( 'http' ) === 0 ) {
					return {
						id: term,
						text: term,
					};
				}
				return null;
			},
			ajax: {
				url: window.ajaxurl,
				dataType: 'json',
				delay: 250,
				data: function ( params ) {
					return {
						search: params.term,
						action: 'wpf_get_redirect_options',
					};
				},
			},
		};

		if ( ! $select.hasClass( 'select2-hidden-accessible' ) ) {
			$select.select2( selectPageOptions );
		}

		const handleChange = () => {
			const value = $select.val();
			const currentSettings = settingsRef.current || {};

			updateActiveSpaceRef.current( {
				wpf_settings_suredash: {
					...currentSettings,
					redirect: value || '',
				},
			} );
		};

		$select.off( '.wpfSuredash' );
		$select.on( 'change.wpfSuredash', handleChange );
		$select.on( 'select2:select.wpfSuredash', handleChange );
		$select.on( 'select2:clear.wpfSuredash', handleChange );

		return () => {
			$select.off( '.wpfSuredash' );
			if ( $select.hasClass( 'select2-hidden-accessible' ) ) {
				$select.select2( 'destroy' );
			}
		};
	}, [] );

	useEffect( () => {
		if ( ! selectRef.current || ! window.jQuery ) {
			return;
		}

		const $select = window.jQuery( selectRef.current );
		if ( ! $select.hasClass( 'select2-hidden-accessible' ) ) {
			return;
		}

		const normalizedValue = redirectValue || '';
		if ( $select.val() !== normalizedValue ) {
			$select.val( normalizedValue ).trigger( 'change.select2' );
		}
	}, [ redirectValue ] );

	return (
		<div>
			<h3>{ __( 'WP Fusion', 'wp-fusion' ) }</h3>
			
			<div style={{ marginBottom: '20px' }}>
				<p style={{ marginBottom: '5px' }}>
					<strong>{ __( 'Required tags (any)', 'wp-fusion' ) }</strong>
				</p>
				<p style={{ fontSize: '12px', color: '#666', marginTop: '0', marginBottom: '10px' }}>
					{ __( 'Users must have at least one of these tags to access this space.', 'wp-fusion' ) }
				</p>
				<WpfSelect
					existingTags={ settings.required_tags || [] }
					onChange={ ( value ) => {
						updateActiveSpace( {
							wpf_settings_suredash: {
								...settings,
								required_tags: value || [],
							},
						} );
					} }
					elementID="wpf-suredash-required-tags"
					dropdownPosition="top"
				/>
			</div>

			<div style={{ marginBottom: '20px' }}>
				<p style={{ marginBottom: '5px' }}>
					<strong>{ __( 'Redirect if access is denied', 'wp-fusion' ) }</strong>
				</p>
				<p style={{ fontSize: '12px', color: '#666', marginTop: '0', marginBottom: '10px' }}>
					{ __( 'Select a page or enter a URL. Users without required tags will be redirected here.', 'wp-fusion' ) }
				</p>
				<select
					ref={ selectRef }
					className="select4-select-page"
					style={{ width: '100%' }}
					data-placeholder={ __( 'Show restricted content message', 'wp-fusion' ) }
					defaultValue={ redirectValue }
				>
					<option value=""></option>
					{ redirectValue && (
						<option value={ redirectValue }>
							{ redirectTitle }
						</option>
					) }
				</select>
			</div>

			{ isCourse && (
				<div style={{ marginBottom: '20px' }}>
					<p style={{ marginBottom: '5px' }}>
						<strong>{ __( 'Apply tags - Course completed', 'wp-fusion' ) }</strong>
					</p>
					<p style={{ fontSize: '12px', color: '#666', marginTop: '0', marginBottom: '10px' }}>
						{ __( 'Apply these tags when the course is completed.', 'wp-fusion' ) }
					</p>
					<WpfSelect
						existingTags={ settings.apply_tags_complete || [] }
						onChange={ ( value ) => {
							updateActiveSpace( {
								wpf_settings_suredash: {
									...settings,
									apply_tags_complete: value || [],
								},
							} );
						} }
						elementID="wpf-suredash-apply-tags-complete"
						dropdownPosition="top"
					/>
				</div>
			) }
		</div>
	);
};

export const SpaceSettings = ( defaultContent, context ) => (
	<SpaceSettingsComponent context={ context } />
);
