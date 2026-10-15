/**
 * CS: editor controls and preview for the taxonomy filter's button and dropdown displays.
 *
 * Used by edit.js, so upstream's editor stays as upstream ships it apart from a few marked
 * lines.
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';

/**
 * The fork's displays, offered in upstream's Display Type control.
 */
export const csDisplayOptions = [
	{ label: __( 'Buttons', 'query-filter' ), value: 'buttons' },
	{ label: __( 'Custom Dropdown', 'query-filter' ), value: 'dropdown' },
];

/**
 * The Display Type value for a block, over upstream's displayType and the fork's booleans.
 *
 * customDropdown only renders when displayAsButtons is also on, so a block saved with
 * customDropdown alone shows, and renders, as upstream's display.
 *
 * @param {Object} attributes Block attributes.
 * @return {string} Display Type value.
 */
export const getCsDisplay = ( attributes ) => {
	if ( attributes.displayAsButtons ) {
		return attributes.customDropdown ? 'dropdown' : 'buttons';
	}

	return attributes.displayType;
};

/**
 * Apply a Display Type choice to the fork's attributes.
 *
 * @param {string}   display       Chosen Display Type value.
 * @param {Function} setAttributes Block setAttributes.
 * @return {boolean} True when the choice is one of the fork's displays and is fully applied.
 */
export const setCsDisplay = ( display, setAttributes ) => {
	const isCsDisplay = display === 'buttons' || display === 'dropdown';

	setAttributes( {
		displayAsButtons: isCsDisplay,
		customDropdown: display === 'dropdown',
		// Upstream's controls key off displayType, so the fork's displays leave it at the
		// default and its select-only options stay hidden.
		...( isCsDisplay && {
			displayType: 'select',
			layoutDirection: undefined,
		} ),
	} );

	return isCsDisplay;
};

/**
 * Settings for the fork's displays.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Block setAttributes.
 * @return {Element|null} Controls, or null for upstream's displays.
 */
export function CsDisplayControls( { attributes, setAttributes } ) {
	const { displayAsButtons, singleSelect } = attributes;

	if ( ! displayAsButtons ) {
		return null;
	}

	return (
		<ToggleControl
			label={ __( 'Single-Select Mode', 'query-filter' ) }
			checked={ singleSelect }
			onChange={ ( value ) => setAttributes( { singleSelect: value } ) }
		/>
	);
}

/**
 * Editor preview of the fork's displays.
 *
 * @param {Object} props            Component props.
 * @param {Object} props.attributes Block attributes.
 * @param {Array}  props.terms      Terms the block would offer.
 * @return {Element|null} Preview, or null for upstream's displays.
 */
export function CsDisplayPreview( { attributes, terms } ) {
	const { displayAsButtons, customDropdown, emptyLabel } = attributes;

	if ( ! displayAsButtons ) {
		return null;
	}

	if ( customDropdown ) {
		return (
			<div className="wp-block-query-filter-taxonomy__buttons is-dropdown-style">
				<button
					type="button"
					className="wp-block-query-filter-taxonomy__button empty-choice"
					inert="true"
				>
					{ emptyLabel || __( 'All', 'query-filter' ) }
				</button>
			</div>
		);
	}

	return (
		<div className="wp-block-query-filter-taxonomy__links">
			<button type="button" inert="true">
				{ emptyLabel || __( 'All', 'query-filter' ) }
			</button>
			{ terms.map( ( term ) => (
				<button
					type="button"
					key={ term.slug }
					data-term-slug={ term.slug }
					inert="true"
				>
					{ term.name }
				</button>
			) ) }
		</div>
	);
}
