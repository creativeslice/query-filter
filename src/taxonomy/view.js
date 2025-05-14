/**
 * Taxonomy filter interactivity script.
 *
 * Enables multi-select filtering of taxonomy terms via URL parameters.
 * Works with the WordPress Interactivity API for client-side interactions.
 */
import { store, getElement, getContext } from '@wordpress/interactivity';

const updateURL = async ( action, value, name ) => {
    const url = new URL( action );
    if ( value || name === 's' ) {
        url.searchParams.set( name, value );
    } else {
        url.searchParams.delete( name );
    }
    const { actions } = await import( '@wordpress/interactivity-router' );
    await actions.navigate( url.toString() );
};

const { state } = store( 'query-filter', {
    state: {
        searchValue: '',
    },
    actions: {
        *navigate( e ) {
            e.preventDefault();
            const { actions } = yield import(
                '@wordpress/interactivity-router'
            );
            yield actions.navigate( e.target.value );
        },
        *search( e ) {
            e.preventDefault();
            const { ref } = getElement();
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
            if ( value === state.searchValue ) return;

            state.searchValue = value;

            yield updateURL( action, value, name );
        },
        *toggleTerm(e) {
            e.preventDefault();

            // Get term slug from clicked button
            const termSlug = e.target.dataset.termSlug;
            const context = getContext('query-filter');

            if (!context.queryVar) {
                console.error('Missing query parameter in context');
                return;
            }

            const url = new URL(window.location.href);
            const params = url.searchParams;

            // Get existing value and handle toggle
            const currentValue = params.get(context.queryVar) || '';
            let terms = currentValue ? currentValue.split(',') : [];

            if (terms.includes(termSlug)) {
                // Remove term
                terms = terms.filter(t => t !== termSlug);
            } else {
                // Add term
                terms.push(termSlug);
            }

            // Update URL
            if (terms.length > 0) {
                params.set(context.queryVar, terms.join(','));
            } else {
                params.delete(context.queryVar);
            }

            // Navigate
            const { actions } = yield import('@wordpress/interactivity-router');
            yield actions.navigate(url.toString());
        },
        *clearSelections(e) {
            e.preventDefault();
            const context = getContext('query-filter');

            if (!context.queryVar) {
                console.error('Missing query parameter in context');
                return;
            }

            const url = new URL(window.location.href);
            url.searchParams.delete(context.queryVar);

            const { actions } = yield import('@wordpress/interactivity-router');
            yield actions.navigate(url.toString());
        }
    },
} );
