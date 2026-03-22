import WpfSelect from '@verygoodplugins/wpfselect';
import { useEffect, useState } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { link } from '@wordpress/icons';

const getConfig = () => window?.wpf_suremembers || {};

const normalizeTags = ( tags, single = false ) => {
	if ( ! tags ) {
		return [];
	}

	let list = tags;

	if ( 'string' === typeof tags ) {
		list = tags.split( ',' ).filter( Boolean );
	}

	if ( ! Array.isArray( list ) ) {
		list = [ list ];
	}

	const normalized = list
		.map( ( tag ) => {
			if ( ! tag ) {
				return null;
			}

			if ( 'object' === typeof tag ) {
				const value = String( tag.value ?? '' ).trim();

				if ( ! value ) {
					return null;
				}

				return {
					value,
					label: tag.label || value,
				};
			}

			const value = String( tag ).trim();

			if ( ! value ) {
				return null;
			}

			return {
				value,
				label: value,
			};
		} )
		.filter( Boolean );

	return single ? normalized.slice( 0, 1 ) : normalized;
};

const normalizeState = ( sectionData = {} ) => {
	const config = getConfig();
	const applySource = sectionData.apply_tags ?? config.apply_tags;
	const linkSource = sectionData.tag_link ?? config.tag_link;

	return {
		applyTags: normalizeTags( applySource ),
		linkTags: normalizeTags( linkSource, true ),
	};
};

/**
 * WP Fusion settings component for the SureMembers membership editor.
 *
 * SureMembers passes `sectionData` (saved values) and `updateData`
 * (callback to update the save payload) via the third-party API.
 *
 * @param {Object}   props
 * @param {Object}   props.sectionData Saved values from suremembers_get_membership_data.
 * @param {Function} props.updateData  Callback to update third-party save payload.
 */
const SureMembersSettings = ( {
	sectionData = {},
	updateData = () => {},
} ) => {
	const config = getConfig();
	const [ state, setState ] = useState( normalizeState( sectionData ) );

	useEffect( () => {
		setState( normalizeState( sectionData ) );
	}, [ sectionData ] );

	const { applyTags, linkTags } = state;

	const setApplyState = ( nextTags ) => {
		const nextApplyTags = normalizeTags( nextTags );

		setState( ( prev ) => ( {
			...prev,
			applyTags: nextApplyTags,
		} ) );

		updateData( {
			apply_tags: nextApplyTags,
			tag_link: linkTags,
		} );
	};

	const setLinkState = ( nextTag ) => {
		const nextLinkTags = normalizeTags( nextTag, true );

		setState( ( prev ) => ( {
			...prev,
			linkTags: nextLinkTags,
		} ) );

		updateData( {
			apply_tags: applyTags,
			tag_link: nextLinkTags,
		} );
	};

	return (
		<div
			className="bg-white rounded-sm divide-y-[1px] w-full"
			id="wpf_meta_box_suremembers"
		>
			<style>{ `
				#wpf_meta_box_suremembers [class*="-multiValue"],
				#wpf_meta_box_suremembers .wpf-media-multi-select-value {
					max-width: none !important;
				}
				#wpf_meta_box_suremembers [class*="-MultiValueGeneric"],
				#wpf_meta_box_suremembers .wpf-media-multi-select-value > div:first-child {
					white-space: nowrap !important;
					overflow: visible !important;
					text-overflow: unset !important;
					max-width: none !important;
				}
				#wpf_meta_box_suremembers [class*="-ValueContainer"] {
					overflow: visible !important;
					flex-wrap: wrap !important;
				}
				#wpf_meta_box_suremembers [class*="-control"] {
					min-height: auto !important;
					height: auto !important;
				}
			` }</style>
			<div
				className="text-sm space-y-6"
				id="wpf_meta_box_suremembers_content"
			>
				<div>
					<sc-form-control
						className="hydrated"
						id="tag_apply"
						label={
							window?.wpf_admin?.strings?.applyTags ||
							__( 'Apply Tags', 'wp-fusion' )
						}
						size="medium"
					>
						<WpfSelect
							existingTags={ applyTags }
							onChange={ setApplyState }
							elementID="wpf-sure-members-tags"
						/>
					</sc-form-control>
					<p className="mt-1 text-xs text-gray-500">
						{ config.apply_tags_string || '' }
					</p>
				</div>
				<div>
					<sc-form-control
						className="hydrated"
						id="tag_link"
						label={
							window?.wpf_admin?.strings?.linkWithTag ||
							__( 'Link with Tag', 'wp-fusion' )
						}
						size="medium"
					>
						<WpfSelect
							existingTags={ linkTags }
							onChange={ setLinkState }
							elementID="wpf-sure-members-link"
							isMulti={ false }
							isClearable={ true }
							sideIcon={ link }
						/>
					</sc-form-control>
					<p className="mt-1 text-xs text-gray-500">
						{ config.tag_link_string || '' }
					</p>
				</div>
			</div>
		</div>
	);
};

// Register via SureMembers' third-party component API (v2.0.6+).
window.suremembers_third_party_components =
	window.suremembers_third_party_components || {};

const componentName =
	getConfig().component_name || 'WPFusionSureMembersSettings';

window.suremembers_third_party_components[ componentName ] =
	SureMembersSettings;

window.dispatchEvent(
	new CustomEvent( 'suremembers_third_party_component_registered' )
);

// Legacy hook for older SureMembers versions that use wp.hooks filters.
addFilter( 'suremembers_sidebar_metaboxes_after', 'wpfusion', function () {
	return <SureMembersSettings />;
} );
