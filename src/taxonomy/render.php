<?php
if ( empty( $attributes['taxonomy'] ) ) {
	return;
}

$id = 'query-filter-' . wp_generate_uuid4();

$taxonomy = get_taxonomy( $attributes['taxonomy'] );

if ( $block->context['query']['inherit'] ) {
	$query_var = sprintf( 'query-%s', $attributes['taxonomy'] );
	$page_var = 'page';
	$base_url = str_replace( '/page/' . get_query_var( 'paged' ), '', remove_query_arg( [ $query_var, $page_var ] ) );
} else {
	$query_id = $block->context['queryId'] ?? 0;
	$query_var = sprintf( 'query-%d-%s', $query_id, $attributes['taxonomy'] );
	$page_var = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
	$base_url = remove_query_arg( [ $query_var, $page_var ] );
}

$args = [
    'hide_empty' => true,
    'taxonomy'   => $attributes['taxonomy'],
    'number'     => 100,
];

/**
 * Limit available terms to those specified in Query block's taxonomy filters.
 */
if ( ! empty( $block->context['query']['taxQuery'][ $attributes['taxonomy'] ] ) ) {
    $args['include'] = $block->context['query']['taxQuery'][ $attributes['taxonomy'] ];
}

$terms = get_terms( $args );

if ( is_wp_error( $terms ) || empty( $terms ) ) {
	return;
}
?>

<div <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?> data-wp-interactive="query-filter" data-wp-context="{}">
    <?php if ( ! empty( $attributes['label'] ) && $attributes['showLabel'] ) : ?>
        <label class="wp-block-query-filter-post-type__label wp-block-query-filter__label<?php echo $attributes['showLabel'] ? '' : ' screen-reader-text' ?>" for="<?php echo esc_attr( $id ); ?>">
            <?php echo esc_html( $attributes['label'] ?? $taxonomy->label ); ?>
        </label>
    <?php endif; ?>

    <?php if ( ! empty( $attributes['displayAsButtons'] ) ) : ?>
        <div class="wp-block-query-filter-taxonomy__buttons">
			<button type="button"
				value="<?php echo esc_attr( $base_url ); ?>"
				data-wp-on--click="actions.navigate"
				<?php echo empty( $_GET[ $query_var ] ) ? 'aria-current="true"' : ''; ?>
				class="wp-block-query-filter-taxonomy__button">
				<?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?>
			</button>
			<?php foreach ( $terms as $term ) : ?>
				<button type="button"
					value="<?php echo esc_attr( add_query_arg( [ $query_var => $term->slug, $page_var => false ], $base_url ) ); ?>"
					data-wp-on--click="actions.navigate"
					<?php echo $term->slug === ( $_GET[ $query_var ] ?? '' ) ? 'aria-current="true"' : ''; ?>
					class="wp-block-query-filter-taxonomy__button">
					<?php echo esc_html( $term->name ); ?>
				</button>
			<?php endforeach; ?>
		</div>
    <?php else : ?>
		<select class="wp-block-query-filter-post-type__select wp-block-query-filter__select" id="<?php echo esc_attr( $id ); ?>" data-wp-on--change="actions.navigate">
			<option value="<?php echo esc_attr( $base_url ) ?>"><?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?></option>
			<?php foreach ( $terms as $term ) : ?>
				<option value="<?php echo esc_attr( add_query_arg( [ $query_var => $term->slug, $page_var => false ], $base_url ) ) ?>" <?php selected( $term->slug, wp_unslash( $_GET[ $query_var ] ?? '' ) ); ?>><?php echo esc_html( $term->name ); ?></option>
			<?php endforeach; ?>
		</select>
	<?php endif; ?>
</div>
