import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

// The option names render.php shows for the loop's own order, by orderBy and order.
const DEFAULT_SORT_LABELS = {
	date: {
		desc: __( 'Most Recent', 'query-filter' ),
		asc: __( 'Oldest', 'query-filter' ),
	},
	title: {
		asc: __( 'Title A–Z', 'query-filter' ),
		desc: __( 'Title Z–A', 'query-filter' ),
	},
};

export default function Edit( { attributes, setAttributes, context } ) {
	const { label } = attributes;
	const { orderBy = 'date', order = 'desc' } = context.query || {};
	const defaultSortLabel = DEFAULT_SORT_LABELS[ orderBy ]?.[ order ];

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Sort Settings', 'query-filter' ) }>
					<TextControl
						label={ __( 'Label', 'query-filter' ) }
						value={ label }
						placeholder={ __( 'Sort', 'query-filter' ) }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'wp-block-query-filter' } ) }>
				<div className="wp-block-query-filter-taxonomy__buttons is-dropdown-style is-single-select">
					<button
						type="button"
						className="wp-block-query-filter-taxonomy__button empty-choice"
						inert="true"
					>
						<span className="wp-block-query-filter-taxonomy__trigger-label">
							{ label || __( 'Sort', 'query-filter' ) }
						</span>
						{ defaultSortLabel && (
							<span className="wp-block-query-filter-taxonomy__trigger-value">
								{ defaultSortLabel }
							</span>
						) }
					</button>
				</div>
			</div>
		</>
	);
}
