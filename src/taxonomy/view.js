/**
 * Taxonomy filter interactivity script.
 *
 * Enables multi-select filtering of taxonomy terms via URL parameters.
 * Works with the WordPress Interactivity API for client-side interactions.
 */
import {
	store,
	getElement,
	getContext,
	withSyncEvent,
} from '@wordpress/interactivity';

/**
 * How long the router may spend fetching a navigation before giving up, in
 * milliseconds.
 *
 * The router's default is 10s, after which it falls back to a full-page
 * `window.location.assign()`. Slow uncached renders (WP_DEBUG local
 * environments, cold caches) regularly brush against that default, turning
 * in-place filter updates into full reloads. The extended window keeps slow
 * responses on the in-place path; genuinely dead requests still fall back.
 */
const NAVIGATION_TIMEOUT = 30000;

const updateURL = async ( action, value, name ) => {
	const url = new URL( action );
	if ( value || name === 's' ) {
		url.searchParams.set( name, value );
	} else {
		url.searchParams.delete( name );
	}
	const { actions } = await import( '@wordpress/interactivity-router' );
	await actions.navigate( url.toString(), { timeout: NAVIGATION_TIMEOUT } );
};

store( 'query-filter', {
	actions: {
		navigate: withSyncEvent( function* ( e ) {
			e.preventDefault();
			const { actions } = yield import(
				'@wordpress/interactivity-router'
			);
			yield actions.navigate( e.target.value, {
				timeout: NAVIGATION_TIMEOUT,
			} );
		} ),
		search: withSyncEvent( function* ( e ) {
			e.preventDefault();
			const { ref } = getElement();
			const context = getContext( 'query-filter' );
			let action, name, value;
			if ( ref.tagName === 'FORM' ) {
				const input = ref.querySelector( 'input[type="search"]' );
				action = ref.action;
				name = input.name;
				value = input.value;
			} else {
				action = ref.closest( 'form' ).action;
				name = ref.name;
				value = ref.value;
			}

			// Don't navigate if the search didn't really change.
			if ( value === context.searchValue ) return;

			context.searchValue = value;

			yield updateURL( action, value, name );
		} ),
		toggleTerm: withSyncEvent( function* ( e ) {
			e.preventDefault();

			// Get term slug from clicked button
			const termSlug = e.target.dataset.termSlug;
			const context = getContext( 'query-filter' );

			if ( ! context.queryVar ) {
				console.error( 'Missing query parameter in context' );
				return;
			}

			const url = new URL( window.location.href );
			const params = url.searchParams;

			// Get existing value and handle toggle
			const currentValue = params.get( context.queryVar ) || '';
			let terms = currentValue ? currentValue.split( ',' ) : [];

			// Check if singleSelect mode is enabled
			const isSingleSelect = context.singleSelect || false;

			if ( isSingleSelect ) {
				// Single-select mode: Replace all terms with the clicked term
				terms = [ termSlug ];
			} else {
				// Multi-select mode: Add or remove the clicked term
				if ( terms.includes( termSlug ) ) {
					terms = terms.filter( ( t ) => t !== termSlug );
				} else {
					terms.push( termSlug );
				}
			}

			// Update URL
			if ( terms.length > 0 ) {
				params.set( context.queryVar, terms.join( ',' ) );
			} else {
				params.delete( context.queryVar );
			}

			// Reset pagination when filtering
			params.delete( context.pageVar );

			// Navigate
			const { actions } = yield import(
				'@wordpress/interactivity-router'
			);
			yield actions.navigate( url.toString(), {
				timeout: NAVIGATION_TIMEOUT,
			} );
		} ),
		clearSelections: withSyncEvent( function* ( e ) {
			e.preventDefault();
			const context = getContext( 'query-filter' );

			if ( ! context.queryVar ) {
				console.error( 'Missing query parameter in context' );
				return;
			}

			const url = new URL( window.location.href );
			url.searchParams.delete( context.queryVar );

			// Reset pagination when clearing filters
			url.searchParams.delete( context.pageVar );

			const { actions } = yield import(
				'@wordpress/interactivity-router'
			);
			yield actions.navigate( url.toString(), {
				timeout: NAVIGATION_TIMEOUT,
			} );
		} ),
		// 2025-08-13 Toggle custom button dropdown
		toggleDropdown: withSyncEvent( function* ( e ) {
			e.preventDefault();
			const { ref } = getElement();
			if ( ref ) {
				// Only run if this is actually a custom dropdown
				const isCustomDropdown =
					ref.classList.contains( 'is-dropdown-style' );
				if ( ! isCustomDropdown ) {
					return;
				}

				const isOpening = ! ref.classList.contains( 'is-open' );
				ref.classList.toggle( 'is-open' );

				if ( isOpening ) {
					const closeOnClickOutside = ( event ) => {
						if ( ! ref.contains( event.target ) ) {
							ref.classList.remove( 'is-open' );
							document.removeEventListener(
								'click',
								closeOnClickOutside
							);
						}
					};
					setTimeout( () => {
						document.addEventListener(
							'click',
							closeOnClickOutside
						);
					}, 200 );
				}
			}
		} ),
	},
} );
