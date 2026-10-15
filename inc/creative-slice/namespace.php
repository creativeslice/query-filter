<?php
/**
 * CS: Creative Slice additions to the query filter plugin.
 *
 * Upstream's own files stay as upstream ships them, apart from a few hook points marked `CS:`,
 * so each upstream release merges with `git merge` instead of a hand port.
 *
 * @package query-filter
 */

namespace HM\Query_Loop_Filter\Creative_Slice;

use WP_Block;
use WP_Query;
use WP_Term;

/**
 * Connect the Creative Slice additions to hooks and filters.
 *
 * @return void
 */
function bootstrap() : void {
	add_filter( 'query_filter_get_terms_args', __NAMESPACE__ . '\\filter_get_terms_args', 10, 3 );
	add_action( 'init', __NAMESPACE__ . '\\register_style_alias', 11 );
}

/**
 * Keep the `query-filter-view` style handle the fork registered before the upstream realignment.
 *
 * Both filter blocks now load their stylesheet through block.json, as upstream does, under
 * `query-filter-taxonomy-style`. A theme that enqueues or depends on `query-filter-view` still
 * gets that stylesheet, once. Runs after upstream's register_blocks() registers the handle.
 *
 * @return void
 */
function register_style_alias() : void {
	wp_register_style( 'query-filter-view', false, [ 'query-filter-taxonomy-style' ] );
}

/**
 * Keep the archive's own term first among the clauses for its taxonomy.
 *
 * parse_tax_query() seeds tax_query from query_vars['tax_query'] and only appends the
 * archive's own clause afterwards, but get_posts() then back-fills 'cat'/'category_name'
 * from the FIRST clause for that taxonomy (class-wp-query.php:2357-2369). Left alone, a
 * filter on the archive's own taxonomy wins that back-fill, so get_queried_object()
 * returns the filtered term instead of the archive's and the template hierarchy resolves
 * a different template. Restating the archive's clause first keeps the archive primary
 * and the filter additive.
 *
 * @param WP_Query $query     Query the filter parameters are being applied to.
 * @param array    $tax_query Clauses pre_get_posts_transpose_query_vars() built from the URL.
 * @return array The clauses, with each filtered archive's own term restated first.
 */
function restate_archive_terms( WP_Query $query, array $tax_query ) : array {
	$taxonomies = array_unique( array_column( array_filter( $tax_query, 'is_array' ), 'taxonomy' ) );

	foreach ( $taxonomies as $taxonomy ) {
		$taxonomy_object = get_taxonomy( $taxonomy );

		// query_var is a string or false by this point: WP_Taxonomy::set_props() resolves a
		// true into the taxonomy name at registration (class-wp-taxonomy.php:374-382).
		if ( ! $taxonomy_object || ! $taxonomy_object->query_var ) {
			continue;
		}

		$query_var = $taxonomy_object->query_var;

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

	return $tax_query;
}

/**
 * Scope a taxonomy filter's options to the query loop it sits in.
 *
 * The two modes are mutually exclusive, because core builds the loop differently in each
 * (blocks/post-template.php:54-73).
 *
 * Non-inherit: core builds the loop via build_query_vars_from_query_block(), which is the only
 * thing that reads taxQuery, so the block's own taxQuery decides the posts and the options must
 * mirror it.
 *
 * Inherit: core clones $wp_query and never calls that function, so taxQuery has NO effect on the
 * posts shown. Narrowing the options by it would hide terms that are actually in the results.
 * The loop shows the archive's posts, but hide_empty is site-global and cannot see the archive,
 * so scope to the terms those posts carry instead.
 *
 * A curated list (upstream's `includeTerms`) is left alone: naming a term explicitly keeps it.
 *
 * @param array|false   $args       get_terms() arguments, or false to offer no options.
 * @param array         $attributes Taxonomy filter block attributes.
 * @param WP_Block|null $block      The filter block, when rendering one.
 * @return array|false The arguments, scoped to the loop, or false to offer no options.
 */
function filter_get_terms_args( $args, array $attributes, ?WP_Block $block ) {
	if ( ! is_array( $args ) || ! $block instanceof WP_Block || ! empty( array_filter( (array) ( $attributes['includeTerms'] ?? [] ) ) ) ) {
		return $args;
	}

	if ( empty( $block->context['query']['inherit'] ) ) {
		// Limit terms if Query block has taxonomy filters. Reads both the pre-7.0 and 7.0 shapes.
		$block_tax_query = get_block_tax_query_term_ids( $block, $attributes['taxonomy'] );

		if ( ! empty( $block_tax_query['include'] ) ) {
			$args['include'] = $block_tax_query['include'];
		}

		// get_terms() blanks exclude whenever include is set (class-wp-term-query.php:473-476),
		// which matches the block's own precedence, so only one of these ever applies.
		if ( ! empty( $block_tax_query['exclude'] ) ) {
			$args['exclude'] = $block_tax_query['exclude'];
		}

		return $args;
	}

	$scope_term_ids = get_archive_scope_term_ids( $attributes['taxonomy'] );

	if ( ! is_array( $scope_term_ids ) ) {
		return $args;
	}

	/*
	 * The archive's own term is already implied by the archive, so offering it narrows
	 * nothing and just duplicates the empty choice. Leave the reset to the empty choice,
	 * which needs no query arg.
	 */
	$queried_object = get_queried_object();

	if ( $queried_object instanceof WP_Term && $queried_object->taxonomy === $attributes['taxonomy'] ) {
		$scope_term_ids = array_values( array_diff( $scope_term_ids, [ $queried_object->term_id ] ) );
	}

	// include => [] means "no constraint", not "no results", so offer nothing rather than fail open.
	if ( empty( $scope_term_ids ) ) {
		return false;
	}

	$args['include'] = $scope_term_ids;

	return $args;
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
