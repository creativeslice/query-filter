<?php
/**
 * CS: the button and dropdown displays of the taxonomy filter.
 *
 * Required from render.php when `displayAsButtons` is set, after upstream's code has resolved
 * the terms and the current selection, so these displays share upstream's term list
 * (including `includeTerms` and `excludeTerms`) and the fork's loop scoping.
 *
 * @var array    $attributes     Block attributes array.
 * @var WP_Block $block          WP_Block instance being rendered.
 * @var WP_Taxonomy $taxonomy    Taxonomy being filtered.
 * @var WP_Term[] $terms         Terms to offer.
 * @var string[] $selected_terms Selected term slugs, decoded.
 * @var callable $is_selected    Whether a term is part of the current selection.
 * @var string   $id             Unique id for this block instance.
 * @var string   $query_var      URL parameter carrying the selection.
 * @var string   $page_var       URL parameter carrying the loop's page.
 * @var string   $base_url       Current URL without the selection or page.
 */

?>

<div <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?>
	data-wp-interactive="query-filter"
	data-wp-context='{
		"selectedTerms":<?php echo wp_json_encode( $selected_terms ); ?>,
		"queryVar":"<?php echo esc_attr( $query_var ); ?>",
		"baseUrl":"<?php echo esc_attr( $base_url ); ?>",
		"pageVar":"<?php echo esc_attr( $page_var ); ?>",
		"singleSelect":<?php echo $attributes['singleSelect'] ? 'true' : 'false'; ?>
	}'>

	<?php if ( ! empty( $attributes['label'] ) && $attributes['showLabel'] ) : ?>
		<label class="wp-block-query-filter-post-type__label wp-block-query-filter__label<?php echo $attributes['showLabel'] ? '' : ' screen-reader-text'; ?>" for="<?php echo esc_attr( $id ); ?>">
			<?php echo esc_html( $attributes['label'] ?? $taxonomy->label ); ?>
		</label>
	<?php endif; ?>

	<?php
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
	}
	?>

	<?php // phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped -- Literal attribute strings assembled above. ?>
	<div class="<?php echo esc_attr( $button_classes ); ?>"<?php echo $dropdown_attrs; ?>>
		<?php // phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped -- Literal attribute strings assembled above. ?>
		<button type="button"<?php echo isset( $clear_attrs ) ? $clear_attrs : ''; ?>
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
				<?php echo $is_selected( $term ) ? ' aria-current="true"' : ''; ?>
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
</div>
