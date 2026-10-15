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

use WP_Query;

/**
 * Connect the Creative Slice additions to hooks and filters.
 *
 * @return void
 */
function bootstrap() : void {
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
