/**
 * CS: Creative Slice actions for the taxonomy filter's button and dropdown displays.
 *
 * Imported by view.js. A second store() call for the same namespace deep-merges into the
 * existing store, so upstream's view.js stays as upstream ships it apart from the import.
 */
import {
	store,
	getElement,
	getContext,
	withSyncEvent,
} from '@wordpress/interactivity';

/**
 * How long the router may spend fetching a navigation before giving up, in milliseconds.
 * Matches NAVIGATION_TIMEOUT in view.js, which explains the value.
 */
const NAVIGATION_TIMEOUT = 30000;

store( 'query-filter', {
	actions: {
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
