<?php
/**
 * Query filter main file.
 *
 * @package query-filter
 */

namespace HM\Query_Loop_Filter;

use WP_HTML_Tag_Processor;
use WP_Query;

/**
 * Connect namespace methods to hooks and filters.
 *
 * @return void
 */
function bootstrap() : void {
	// General hooks.
	add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\\filter_query_loop_block_query_vars', 10, 3 );
	add_action( 'pre_get_posts', __NAMESPACE__ . '\\pre_get_posts_transpose_query_vars' );
	add_filter( 'block_type_metadata', __NAMESPACE__ . '\\filter_block_type_metadata', 10 );
	add_action( 'init', __NAMESPACE__ . '\\register_blocks' );
	add_action( 'enqueue_block_assets', __NAMESPACE__ . '\\action_wp_enqueue_scripts' );

	// Search.
	add_filter( 'render_block_core/search', __NAMESPACE__ . '\\render_block_search', 10, 3 );

	// Query.
	add_filter( 'render_block_core/query', __NAMESPACE__ . '\\render_block_query', 10, 3 );
}

/**
 * Fires when scripts and styles are enqueued.
 *
 * @TODO work out why this doesn't work but building interactivity via the blocks does.
 */
function action_wp_enqueue_scripts() : void {
	$asset = include ROOT_DIR . '/build/taxonomy/index.asset.php';
	wp_register_style(
		'query-filter-view',
		plugins_url( '/build/taxonomy/index.css', PLUGIN_FILE ),
		[],
		$asset['version']
	);
}

/**
 * Fires after WordPress has finished loading but before any headers are sent.
 *
 */
function register_blocks() : void {
	register_block_type( ROOT_DIR . '/build/taxonomy' );
	register_block_type( ROOT_DIR . '/build/post-type' );
}

/**
 * Filters the arguments which will be passed to `WP_Query` for the Query Loop Block.
 *
 * @param array     $query Array containing parameters for <code>WP_Query</code> as parsed by the block context.
 * @param \WP_Block $block Block instance.
 * @param int       $page  Current query's page.
 * @return array Array containing parameters for <code>WP_Query</code> as parsed by the block context.
 */
function filter_query_loop_block_query_vars( array $query, \WP_Block $block, int $page ) : array {
	if ( isset( $block->context['queryId'] ) ) {
		$query['query_id'] = $block->context['queryId'];
	}

	return $query;
}

/**
 * Fires after the query variable object is created, but before the actual query is run.
 *
 * @param  WP_Query $query The WP_Query instance (passed by reference).
 */
function pre_get_posts_transpose_query_vars( WP_Query $query ) : void {
	$query_id = $query->get( 'query_id', null );

	if ( ! $query->is_main_query() && is_null( $query_id ) ) {
		return;
	}

	$prefix = $query->is_main_query() ? 'query-' : "query-{$query_id}-";
	$tax_query = [];
	$filtered_taxonomies = [];
	$valid_keys = [
		'post_type' => $query->is_search() ? 'any' : 'post',
		's' => '',
	];

	// Preserve valid params for later retrieval.
	foreach ( $valid_keys as $key => $default ) {
		$query->set(
			"query-filter-$key",
			$query->get( $key, $default )
		);
	}

	// Map get params to this query.
    foreach ( $_GET as $key => $value ) {
        if ( strpos( $key, $prefix ) === 0 ) {
            $key = str_replace( $prefix, '', $key );
            $value = sanitize_text_field( urldecode( wp_unslash( $value ) ) );

            // Handle taxonomies specifically.
            if ( get_taxonomy( $key ) ) {
                $filtered_taxonomies[] = $key;
                $tax_query['relation'] = 'AND';

                // Check if value contains multiple terms (comma-separated)
                if (strpos($value, ',') !== false) {
                    $terms = array_filter(array_map('trim', explode(',', $value)));
                    $tax_query[] = [
                        'taxonomy' => $key,
                        'terms' => $terms,
                        'field' => 'slug',
                        'operator' => 'IN', // Show posts matching ANY of the selected terms
                    ];
                } else {
                    $tax_query[] = [
                        'taxonomy' => $key,
                        'terms' => [ $value ],
                        'field' => 'slug',
                    ];
                }
            } else {
                // Other options should map directly to query vars.
                $key = sanitize_key( $key );

                if ( ! in_array( $key, array_keys( $valid_keys ), true ) ) {
                    continue;
                }

                $query->set(
                    $key,
                    $value
                );
            }
        }
    }

	/*
	 * Keep the archive's own term first among the clauses for its taxonomy.
	 *
	 * parse_tax_query() seeds tax_query from query_vars['tax_query'] and only appends the
	 * archive's own clause afterwards, but get_posts() then back-fills 'cat'/'category_name'
	 * from the FIRST clause for that taxonomy (class-wp-query.php:2357-2369). Left alone, a
	 * filter on the archive's own taxonomy wins that back-fill, so get_queried_object()
	 * returns the filtered term instead of the archive's and the template hierarchy resolves
	 * a different template. Restating the archive's clause first keeps the archive primary
	 * and the filter additive.
	 */
	foreach ( array_unique( $filtered_taxonomies ) as $taxonomy ) {
		$query_var = get_taxonomy( $taxonomy )->query_var;

		if ( ! $query_var ) {
			continue;
		}

		$archive_term = $query->get( $query_var );

		if ( ! is_string( $archive_term ) || '' === $archive_term ) {
			continue;
		}

		/*
		 * Core reads '+' as separate AND clauses and ',' as one IN clause
		 * (class-wp-query.php:1205-1222). Restating those correctly means reimplementing that
		 * parsing, so leave multi-term archives to core instead. The queried object still gets
		 * retargeted in that case, but the query stays right, which beats a clause that matches
		 * a term slugged "a+b" and returns nothing.
		 */
		if ( str_contains( $archive_term, '+' ) || str_contains( $archive_term, ',' ) ) {
			continue;
		}

		// Same clause shape core builds from the taxonomy's query var.
		array_unshift(
			$tax_query,
			[
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => [ wp_basename( $archive_term ) ],
			]
		);

		// Restated above, so leave nothing for core to append as a duplicate clause.
		$query->set( $query_var, '' );
	}

	if ( ! empty( $tax_query ) ) {
		$existing_query = $query->get( 'tax_query', [] );

		if ( ! empty( $existing_query ) ) {
			$tax_query = [
				'relation' => 'AND',
				[ $existing_query ],
				$tax_query,
			];
		}

		$query->set( 'tax_query', $tax_query );
	}
}

/**
 * Get the term IDs a Query Loop block's own taxQuery attribute narrows a taxonomy to.
 *
 * WP 7.0 changed the attribute shape from `{"category":[4]}` to
 * `{"include":{"category":[4]},"exclude":{"post_tag":[5]}}` and kept reading both, detecting
 * the old one by the presence of keys other than include/exclude (blocks.php:2776-2778).
 * Mirror that test so blocks work whichever version saved them.
 *
 * @param \WP_Block $block    Block instance.
 * @param string    $taxonomy Taxonomy to read.
 * @return array{include: int[], exclude: int[]} Term IDs, empty when unrestricted.
 */
function get_block_tax_query_term_ids( \WP_Block $block, string $taxonomy ) : array {
	$empty           = [ 'include' => [], 'exclude' => [] ];
	$tax_query_input = $block->context['query']['taxQuery'] ?? null;

	if ( empty( $tax_query_input ) || ! is_array( $tax_query_input ) ) {
		return $empty;
	}

	$term_ids = static function ( $terms ) : array {
		return array_filter( array_map( 'intval', (array) $terms ) );
	};

	// Any key other than include/exclude means the pre-7.0 shape, same test core uses.
	if ( ! empty( array_diff( array_keys( $tax_query_input ), [ 'include', 'exclude' ] ) ) ) {
		return array_merge( $empty, [ 'include' => $term_ids( $tax_query_input[ $taxonomy ] ?? [] ) ] );
	}

	return [
		'include' => $term_ids( $tax_query_input['include'][ $taxonomy ] ?? [] ),
		'exclude' => $term_ids( $tax_query_input['exclude'][ $taxonomy ] ?? [] ),
	];
}

/**
 * Get the IDs of the terms carried by the posts in the current archive.
 *
 * Only meaningful when the Query Loop inherits the template query: the loop then shows the
 * main query's posts, but `hide_empty` is site-global and cannot see the archive, so the
 * filter offers terms with no posts in it.
 *
 * The scope is read from `WP_Query::$query`, the raw URL-parsed vars, rather than
 * `$query_vars`: `parse_query()` assigns `$query` before `fill_query_vars()` and never
 * writes to it again, so it still holds the archive's own constraint without this plugin's
 * `pre_get_posts` tax_query. Replaying it therefore scopes to the archive rather than to the
 * current selection, which is what keeps a filter's own options from collapsing to whatever
 * is selected.
 *
 * @param string $taxonomy Taxonomy to collect terms for.
 * @return array|null Term IDs, [] when the archive has no posts, null when it cannot be scoped.
 */
function get_archive_scope_term_ids( string $taxonomy ) : ?array {
	if ( ! isset( $GLOBALS['wp_query'] ) || ! $GLOBALS['wp_query'] instanceof WP_Query ) {
		return null;
	}

	$archive_vars = $GLOBALS['wp_query']->query;

	if ( ! is_array( $archive_vars ) ) {
		return null;
	}

	/*
	 * Pagination is not part of the scope. Left in, the posts page would scope on /page/2/,
	 * where 'paged' makes ->query non-empty, but not on page 1, so one archive would offer
	 * different options depending on the page.
	 */
	unset( $archive_vars['paged'], $archive_vars['page'], $archive_vars['offset'] );

	// Nothing constrains the loop, so every term with a post already qualifies and the
	// existing hide_empty gives the same answer without an extra query.
	if ( empty( $archive_vars ) ) {
		return null;
	}

	// One query loop commonly holds several filter blocks sharing a scope.
	// array_key_exists, not isset: a memoised null is a real answer.
	static $cache = [];
	$cache_key = md5( serialize( [ $archive_vars, $taxonomy ] ) );

	if ( array_key_exists( $cache_key, $cache ) ) {
		return $cache[ $cache_key ];
	}

	/**
	 * Filters the maximum number of posts the option scope will query.
	 *
	 * Past this the archive is treated as unscopable and every term is offered, because
	 * truncating would produce a plausible but wrong option list.
	 *
	 * @param int    $limit    Maximum posts to inspect.
	 * @param string $taxonomy Taxonomy being scoped.
	 */
	$limit = (int) apply_filters( 'query_filter_scope_post_limit', 5000, $taxonomy );

	/*
	 * A plain WP_Query is neither the main query nor carries a query_id, so
	 * pre_get_posts_transpose_query_vars() returns early and never re-applies the filters.
	 * Fetching one row past the limit detects an oversized archive without a second query.
	 * 'nopaging' must stay false or it would override posts_per_page.
	 */
	$scope = new WP_Query( array_merge(
		$archive_vars,
		[
			'fields'                 => 'ids',
			'posts_per_page'         => $limit + 1,
			'nopaging'               => false,
			'paged'                  => 0,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		]
	) );

	if ( count( (array) $scope->posts ) > $limit ) {
		$cache[ $cache_key ] = null;
		return $cache[ $cache_key ];
	}

	if ( empty( $scope->posts ) ) {
		$cache[ $cache_key ] = [];
		return $cache[ $cache_key ];
	}

	$term_ids = get_terms( [
		'taxonomy'   => $taxonomy,
		'object_ids' => $scope->posts,
		'fields'     => 'ids',
		'hide_empty' => false,
	] );

	if ( is_wp_error( $term_ids ) || empty( $term_ids ) ) {
		$cache[ $cache_key ] = is_wp_error( $term_ids ) ? null : [];
		return $cache[ $cache_key ];
	}

	$term_ids = array_map( 'intval', $term_ids );

	/*
	 * get_terms( object_ids ) force-sets hide_empty = false (class-wp-term-query.php:608),
	 * which skips core's "show empty categories that have children" pass at :845. A container
	 * parent with count = 0 renders today, so re-add ancestors to keep it.
	 */
	if ( is_taxonomy_hierarchical( $taxonomy ) ) {
		$ancestors = [];

		foreach ( $term_ids as $term_id ) {
			$ancestors = array_merge( $ancestors, get_ancestors( $term_id, $taxonomy, 'taxonomy' ) );
		}

		$term_ids = array_unique( array_merge( $term_ids, array_map( 'intval', $ancestors ) ) );
	}

	$cache[ $cache_key ] = array_values( $term_ids );

	return $cache[ $cache_key ];
}

/**
 * Filters the settings determined from the block type metadata.
 *
 * @param array $metadata Metadata provided for registering a block type.
 * @return array Array of metadata for registering a block type.
 */
function filter_block_type_metadata( array $metadata ) : array {
	// Add query context to search block.
	if ( $metadata['name'] === 'core/search' ) {
		$metadata['usesContext'] = array_merge( $metadata['usesContext'] ?? [], [ 'queryId', 'query' ] );
	}

	return $metadata;
}

/**
 * Filters the content of a single block.
 *
 * @param string    $block_content The block content.
 * @param array     $block         The full block, including name and attributes.
 * @param \WP_Block $instance      The block instance.
 * @return string The block content.
 */
function render_block_search( string $block_content, array $block, \WP_Block $instance ) : string {
	if ( empty( $instance->context['query'] ) ) {
		return $block_content;
	}

	wp_enqueue_script_module( 'query-filter-taxonomy-view-script-module' );

	$query_var = empty( $instance->context['query']['inherit'] )
		? sprintf( 'query-%d-s', $instance->context['queryId'] ?? 0 )
		: 'query-s';

	$action = str_replace( '/page/'. get_query_var( 'paged', 1 ), '', add_query_arg( [ $query_var => '' ] ) );

	// Note sanitize_text_field trims whitespace from start/end of string causing unexpected behaviour.
	$value = wp_unslash( $_GET[ $query_var ] ?? '' );
	$value = urldecode( $value );
	$value = wp_check_invalid_utf8( $value );
	$value = wp_pre_kses_less_than( $value );
	$value = strip_tags( $value );

	$block_content = new WP_HTML_Tag_Processor( $block_content );
	$block_content->next_tag( [ 'tag_name' => 'form' ] );
	$block_content->set_attribute( 'action', $action );
	$block_content->set_attribute( 'data-wp-interactive', 'query-filter' );
	$block_content->set_attribute( 'data-wp-on--submit', 'actions.search' );
	$block_content->set_attribute(
		'data-wp-context',
		wp_json_encode( [ 'searchValue' => $value ] )
	);
	$block_content->next_tag( [ 'tag_name' => 'input', 'class_name' => 'wp-block-search__input' ] );
	$block_content->set_attribute( 'name', $query_var );
	$block_content->set_attribute( 'inputmode', 'search' );
	$block_content->set_attribute( 'value', $value );
	$block_content->set_attribute( 'data-wp-bind--value', 'context.searchValue' );
	$block_content->set_attribute( 'data-wp-on--input', 'actions.search' );

	return (string) $block_content;
}

/**
 * Add data attributes to the query block to describe the block query.
 *
 * @param string    $block_content Default query content.
 * @param array     $block         Parsed block.
 * @return string
 */
function render_block_query( $block_content, $block ) {
	$block_content = new WP_HTML_Tag_Processor( $block_content );
	$block_content->next_tag();

	// Always allow region updates on interactivity, use standard core region naming.
	// Enhanced pagination already sets core/query here, and core's pagination
	// directives resolve their context against that namespace, so only claim the
	// island when nothing else has.
	if ( null === $block_content->get_attribute( 'data-wp-interactive' ) ) {
		$block_content->set_attribute( 'data-wp-interactive', 'query-filter' );
	}
	$block_content->set_attribute( 'data-wp-router-region', 'query-' . ( $block['attrs']['queryId'] ?? 0 ) );

	return (string) $block_content;
}
