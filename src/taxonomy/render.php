<?php // query-filter/src/taxonomy/render.php
if ( empty( $attributes['taxonomy'] ) ) {
	return;
}

$id = 'query-filter-' . wp_generate_uuid4();

$taxonomy = get_taxonomy( $attributes['taxonomy'] );

if ( empty( $block->context['query']['inherit'] ) ) {
	$query_id = $block->context['queryId'] ?? 0;
	$query_var = sprintf( 'query-%d-%s', $query_id, $attributes['taxonomy'] );
	$page_var = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
	$base_url = remove_query_arg( [ $query_var, $page_var ] );
} else {
	$query_var = sprintf( 'query-%s', $attributes['taxonomy'] );
	$page_var = 'page';
	$base_url = str_replace( '/page/' . get_query_var( 'paged' ), '', remove_query_arg( [ $query_var, $page_var ] ) );
}

$args = [
    'hide_empty' => true,
    'taxonomy' => $attributes['taxonomy'],
    'number' => 100,
];

/*
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
 */
if ( empty( $block->context['query']['inherit'] ) ) {
    // Limit terms if Query block has taxonomy filters. Reads both the pre-7.0 and 7.0 shapes.
    $block_tax_query = \HM\Query_Loop_Filter\get_block_tax_query_term_ids( $block, $attributes['taxonomy'] );

    if ( ! empty( $block_tax_query['include'] ) ) {
        $args['include'] = $block_tax_query['include'];
    }

    // get_terms() blanks exclude whenever include is set (class-wp-term-query.php:473-476),
    // which matches the block's own precedence, so only one of these ever applies.
    if ( ! empty( $block_tax_query['exclude'] ) ) {
        $args['exclude'] = $block_tax_query['exclude'];
    }
} else {
    $scope_term_ids = \HM\Query_Loop_Filter\get_archive_scope_term_ids( $attributes['taxonomy'] );

    if ( is_array( $scope_term_ids ) ) {
        /*
         * The archive's own term is already implied by the archive, so offering it narrows
         * nothing and just duplicates the empty choice. Leave the reset to the empty choice,
         * which needs no query arg.
         */
        $queried_object = get_queried_object();

        if ( $queried_object instanceof WP_Term && $queried_object->taxonomy === $attributes['taxonomy'] ) {
            $scope_term_ids = array_values( array_diff( $scope_term_ids, [ $queried_object->term_id ] ) );
        }

        // include => [] means "no constraint", not "no results", so bail rather than fail open.
        if ( empty( $scope_term_ids ) ) {
            return;
        }

        $args['include'] = $scope_term_ids;
    }
}

$terms = get_terms($args);

// Parse current selected terms if any
$selected_terms = !empty($_GET[$query_var]) ?
    array_map('trim', explode(',', sanitize_text_field(wp_unslash($_GET[$query_var])))) :
    [];

if ( is_wp_error( $terms ) || empty( $terms ) ) {
	return;
}
?>

<div <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?>
    data-wp-interactive="query-filter"
    data-wp-context='{
        "selectedTerms":<?php echo wp_json_encode($selected_terms); ?>,
        "queryVar":"<?php echo esc_attr($query_var); ?>",
        "baseUrl":"<?php echo esc_attr($base_url); ?>",
        "pageVar":"<?php echo esc_attr($page_var); ?>",
        "singleSelect":<?php echo $attributes['singleSelect'] ? 'true' : 'false'; ?>
    }'>

	<?php if ( ! empty( $attributes['label'] ) && $attributes['showLabel'] ) : ?>
        <label class="wp-block-query-filter-post-type__label wp-block-query-filter__label<?php echo $attributes['showLabel'] ? '' : ' screen-reader-text' ?>" for="<?php echo esc_attr( $id ); ?>">
            <?php echo esc_html( $attributes['label'] ?? $taxonomy->label ); ?>
        </label>
    <?php endif; ?>

    <?php
    /**
     * Display as buttons and allow multiple selections
     */
    if ( ! empty( $attributes['displayAsButtons'] ) ) :
        $button_classes = 'wp-block-query-filter-taxonomy__buttons';
        $dropdown_attrs = '';

        if ( ! empty( $attributes['customDropdown'] ) ) {
            $button_classes .= ' is-dropdown-style';
            $dropdown_attrs = ' data-wp-on--click="actions.toggleDropdown"';
        } else {
            $clear_attrs = ' data-wp-on--click="actions.clearSelections"';
        }

        if ( ! empty( $attributes['singleSelect'] ) ) {
            $button_classes .= ' is-single-select';
        } ?>

        <div class="<?php echo esc_attr( $button_classes ); ?>"<?php echo $dropdown_attrs; ?>>
            <button type="button"<?php echo isset($clear_attrs) ? $clear_attrs : ''; ?>
                <?php echo empty( $selected_terms ) ? ' aria-current="true"' : ''; ?>
                class="wp-block-query-filter-taxonomy__button empty-choice">
                <?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?>
            </button>

            <?php if ( ! empty( $attributes['customDropdown'] ) ) : ?>
                <div class="wp-block-query-filter-taxonomy__dropdown-items">
            <?php endif; ?>

            <?php foreach ( $terms as $term ) : ?>
                <button type="button"
                    data-term-slug="<?php echo esc_attr( $term->slug ); ?>"
                    data-wp-on--click="actions.toggleTerm"
                    <?php echo in_array( $term->slug, $selected_terms ) ? ' aria-current="true"' : ''; ?>
                    class="wp-block-query-filter-taxonomy__button">
                    <?php echo esc_html( $term->name ); ?>
                </button>
            <?php endforeach; ?>

            <?php if ( ! empty( $attributes['customDropdown'] ) ) : ?>
                    <button type="button"
                        data-wp-on--click="actions.clearSelections"
                        class="wp-block-query-filter-taxonomy__clear-filters">
                        <?php echo esc_html( __( 'Clear all', 'query-filter' ) ); ?>
                    </button>
                </div>
            <?php endif; ?>

        </div>

    <?php else :
    /**
     * Default select display
     */
    ?>
		<select class="wp-block-query-filter-post-type__select wp-block-query-filter__select" id="<?php echo esc_attr( $id ); ?>" data-wp-on--change="actions.navigate">
			<option value="<?php echo esc_attr( $base_url ) ?>"><?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?></option>
			<?php foreach ( $terms as $term ) : ?>
				<option value="<?php echo esc_attr( add_query_arg( [ $query_var => $term->slug, $page_var => false ], $base_url ) ) ?>" <?php selected( $term->slug, wp_unslash( $_GET[ $query_var ] ?? '' ) ); ?>><?php echo esc_html( $term->name ); ?></option>
			<?php endforeach; ?>
		</select>
	<?php endif; ?>
</div>
