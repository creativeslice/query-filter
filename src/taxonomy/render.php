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

// Limit terms if Query block has taxonomy filters
if (!empty($block->context['query']['taxQuery'][$attributes['taxonomy']])) {
    $args['include'] = $block->context['query']['taxQuery'][$attributes['taxonomy']];
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
