<?php
/**
 * CS: the sort block. It renders the taxonomy filter's single-select dropdown markup, so the
 * taxonomy view script drives it and the dropdown styles apply unchanged. The URL carries a
 * slug from get_sort_options(), which pre_get_posts_apply_sort() maps to orderby and order.
 *
 * @var array    $attributes Block attributes array.
 * @var WP_Block $block      WP_Block instance being rendered.
 */

$id = 'query-filter-' . wp_generate_uuid4();
$sort_options = \HM\Query_Loop_Filter\Creative_Slice\get_sort_options();
$inherit = ! empty( $block->context['query']['inherit'] );

if ( $inherit ) {
	$query_var = 'query-sort';
	$page_var = 'page';

	// An inherited loop shows the main query, which WP_Query orders by date DESC, except on
	// the search template, where it orders by relevance and so matches no option here.
	$loop_orderby = is_search() ? 'relevance' : 'date';
	$loop_order = 'DESC';
} else {
	$query_id = $block->context['queryId'] ?? 0;
	$query_var = sprintf( 'query-%d-sort', $query_id );
	$page_var = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';

	// The loop's own Order setting, falling back to WP_Query's date DESC as core does
	// when the block leaves it unset (build_query_vars_from_query_block()).
	$loop_orderby = $block->context['query']['orderBy'] ?? 'date';
	$loop_order = strtoupper( $block->context['query']['order'] ?? 'desc' );
}

// The option matching the loop's own order is the default. Choosing it removes the URL
// parameter rather than restating the order, and it carries no data-term-slug.
$default_sort = '';

foreach ( $sort_options as $slug => $option ) {
	if ( $option['orderby'] === $loop_orderby && $option['order'] === $loop_order ) {
		$default_sort = $slug;
		break;
	}
}

$selected_sort = isset( $_GET[ $query_var ] ) && is_scalar( $_GET[ $query_var ] )
	? sanitize_key( wp_unslash( $_GET[ $query_var ] ) )
	: '';

$current_sort = isset( $sort_options[ $selected_sort ] ) ? $selected_sort : $default_sort;
$label = $attributes['label'] ?: __( 'Sort', 'query-filter' );
?>

<div <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?>
	data-wp-interactive="query-filter"
	<?php
	// phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped -- wp_interactivity_data_wp_context() returns an escaped attribute.
	echo wp_interactivity_data_wp_context(
		[
			'queryVar'     => $query_var,
			'pageVar'      => $page_var,
			'inherit'      => $inherit,
			'singleSelect' => true,
		]
	);
	?>
>
	<div class="wp-block-query-filter-taxonomy__buttons is-dropdown-style is-single-select"
		role="group"
		aria-label="<?php echo esc_attr( $label ); ?>"
		data-wp-class--is-open="state.isDropdownOpen"
		data-wp-on--keydown="actions.closeDropdownOnEscape"
		data-wp-on-document--click="actions.closeDropdownOnOutsideClick">
		<button type="button"
			data-wp-on--click="actions.toggleDropdown"
			aria-expanded="false"
			data-wp-bind--aria-expanded="state.isDropdownOpen"
			aria-controls="<?php echo esc_attr( $id . '-items' ); ?>"
			class="wp-block-query-filter-taxonomy__button empty-choice">
			<span class="wp-block-query-filter-taxonomy__trigger-label"><?php echo esc_html( $label ); ?></span>
			<?php if ( $current_sort ) : ?>
				<span class="wp-block-query-filter-taxonomy__trigger-value"><?php echo esc_html( $sort_options[ $current_sort ]['label'] ); ?></span>
			<?php endif; ?>
		</button>

		<div class="wp-block-query-filter-taxonomy__dropdown-items" id="<?php echo esc_attr( $id . '-items' ); ?>">
			<?php foreach ( $sort_options as $slug => $option ) : ?>
				<button type="button"
					<?php if ( $slug === $default_sort ) : ?>
						data-wp-on--click="actions.clearSelections"
					<?php else : ?>
						data-term-slug="<?php echo esc_attr( $slug ); ?>"
						data-wp-on--click="actions.toggleTerm"
					<?php endif; ?>
					aria-pressed="<?php echo $slug === $current_sort ? 'true' : 'false'; ?>"
					<?php echo $slug === $current_sort ? ' aria-current="true"' : ''; ?>
					class="wp-block-query-filter-taxonomy__button">
					<?php echo esc_html( $option['label'] ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	</div>
</div>
