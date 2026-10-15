import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { label } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Clear Filters Settings', 'query-filter' ) }
				>
					<TextControl
						label={ __( 'Label', 'query-filter' ) }
						value={ label }
						placeholder={ __( 'Clear Filters', 'query-filter' ) }
						help={ __(
							'On the site the button shows only while a filter, sort, or search is active.',
							'query-filter'
						) }
						onChange={ ( label ) => setAttributes( { label } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'wp-block-query-filter' } ) }>
				<button
					type="button"
					className="wp-block-query-filter-clear__button"
					inert="true"
				>
					{ label || __( 'Clear Filters', 'query-filter' ) }
				</button>
			</div>
		</>
	);
}
