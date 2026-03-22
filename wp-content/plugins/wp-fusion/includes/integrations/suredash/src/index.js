import domReady from '@wordpress/dom-ready';
import { addFilter } from '@wordpress/hooks';
import { SpaceSettings } from './components/SpaceSettings';

domReady( () => {
	addFilter(
		'suredash.space_settings.additional_fields',
		'wpf_suredash_additional_fields',
		SpaceSettings
	);
} );
