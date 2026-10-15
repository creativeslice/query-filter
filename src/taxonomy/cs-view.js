/**
 * CS: Creative Slice actions for the taxonomy filter's button and dropdown displays, and for
 * the sort and clear filters blocks, which render the same markup and share this module.
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

/**
 * The current URL with the loop's pagination removed, as the base for a filter change.
 *
 * A non-inherited loop pages with its own query parameter. An inherited loop pages the main
 * query, which pretty permalinks put in the path as /page/N/, so deleting the parameter alone
 * would open the new results on the old page number.
 *
 * @param {Object} context Block context with `pageVar` and `inherit`.
 * @return {URL} URL to modify and navigate to.
 */
const getFilterUrl = ( context ) => {
	const url = new URL( window.location.href );
	url.searchParams.delete( context.pageVar );

	if ( context.inherit ) {
		url.pathname = url.pathname.replace( /\/page\/\d+\/?$/, '/' );
	}

	return url;
};

const navigateTo = function* ( url ) {
	const { actions } = yield import( '@wordpress/interactivity-router' );
	yield actions.navigate( url.toString(), {
		timeout: NAVIGATION_TIMEOUT,
	} );
};

const { state } = store( 'query-filter', {
	state: {
		/*
		 * The queryVar of the open dropdown, or ''. It lives in store state because the router
		 * keeps an element's existing client context across navigations, while store state is
		 * read fresh by every directive, so a multi-select dropdown stays open while terms are
		 * checked. Keying it by queryVar also means opening one dropdown closes any other.
		 */
		openDropdown: '',
		get isDropdownOpen() {
			return state.openDropdown === getContext( 'query-filter' ).queryVar;
		},
	},
	actions: {
		toggleTerm: withSyncEvent( function* ( e ) {
			e.preventDefault();

			// Get term slug from clicked button
			const termSlug = e.target.dataset.termSlug;
			const context = getContext( 'query-filter' );

			if ( ! context.queryVar ) {
				// eslint-disable-next-line no-console
				console.error( 'Missing query parameter in context' );
				return;
			}

			// Reset pagination when filtering
			const url = getFilterUrl( context );
			const params = url.searchParams;

			// Get existing value and handle toggle
			const currentValue = params.get( context.queryVar ) || '';
			let terms = currentValue ? currentValue.split( ',' ) : [];

			if ( context.singleSelect ) {
				// Single-select mode: Replace all terms with the clicked term
				terms = [ termSlug ];

				// A single choice is complete, so close its dropdown.
				state.openDropdown = '';
			} else if ( terms.includes( termSlug ) ) {
				// Multi-select mode: remove the clicked term
				terms = terms.filter( ( t ) => t !== termSlug );
			} else {
				// Multi-select mode: add the clicked term
				terms.push( termSlug );
			}

			// Update URL
			if ( terms.length > 0 ) {
				params.set( context.queryVar, terms.join( ',' ) );
			} else {
				params.delete( context.queryVar );
			}

			yield* navigateTo( url );
		} ),
		clearSelections: withSyncEvent( function* ( e ) {
			e.preventDefault();
			const context = getContext( 'query-filter' );

			if ( ! context.queryVar ) {
				// eslint-disable-next-line no-console
				console.error( 'Missing query parameter in context' );
				return;
			}

			// Reset pagination when clearing filters
			const url = getFilterUrl( context );
			url.searchParams.delete( context.queryVar );

			state.openDropdown = '';

			yield* navigateTo( url );
		} ),
		/*
		 * The clear filters block. Removes every parameter the loop reads, read from the URL at
		 * click time rather than from context, because the router keeps an element's existing
		 * client context across navigations and would hand back a stale list.
		 */
		clearAll: withSyncEvent( function* ( e ) {
			e.preventDefault();
			const context = getContext( 'query-filter' );
			const url = getFilterUrl( context );
			const searchVar = `${ context.prefix }s`;
			let clearsSearch = false;

			// Copy the keys first: deleting while iterating searchParams skips entries.
			[ ...url.searchParams.keys() ].forEach( ( key ) => {
				// An inherited loop reads the bare query- prefix, which every
				// non-inherited loop's query-{id}- parameters also start with.
				const isLoopKey =
					key.startsWith( context.prefix ) &&
					! ( context.inherit && /^query-\d+-/.test( key ) );

				if ( isLoopKey ) {
					clearsSearch = clearsSearch || key === searchVar;
					url.searchParams.delete( key );
				}
			} );

			state.openDropdown = '';

			// The search field binds its value to client context, which the router keeps
			// across navigations, so an in-place clear would leave the old text in the field.
			if ( clearsSearch ) {
				window.location.assign( url.toString() );
				return;
			}

			yield* navigateTo( url );
		} ),
		toggleDropdown: () => {
			const { queryVar } = getContext( 'query-filter' );
			state.openDropdown =
				state.openDropdown === queryVar ? '' : queryVar;
		},
		closeDropdownOnEscape: withSyncEvent( ( e ) => {
			if ( e.key !== 'Escape' || ! state.isDropdownOpen ) {
				return;
			}

			e.preventDefault();
			state.openDropdown = '';
			getElement().ref.querySelector( '.empty-choice' )?.focus();
		} ),
		closeDropdownOnOutsideClick: withSyncEvent( ( e ) => {
			if (
				state.isDropdownOpen &&
				! getElement().ref.contains( e.target )
			) {
				state.openDropdown = '';
			}
		} ),
	},
} );
