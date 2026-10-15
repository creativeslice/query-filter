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
		"inherit":<?php echo empty( $block->context['query']['inherit'] ) ? 'false' : 'true'; ?>,
		"singleSelect":<?php echo $attributes['singleSelect'] ? 'true' : 'false'; ?>
	}'>

	<?php
	$label_text = ( $attributes['label'] ?? '' ) ?: $taxonomy->label;
	$has_visible_label = ! empty( $attributes['label'] ) && $attributes['showLabel'];

	// The buttons are a group, named by the visible label when there is one. A label's `for`
	// can only point at a single control, so it names the group through aria-labelledby.
	$group_attrs = ' role="group"' . ( $has_visible_label
		? ' aria-labelledby="' . esc_attr( $id . '-label' ) . '"'
		: ' aria-label="' . esc_attr( $label_text ) . '"' );
	?>

	<?php if ( $has_visible_label ) : ?>
		<label class="wp-block-query-filter-post-type__label wp-block-query-filter__label" id="<?php echo esc_attr( $id . '-label' ); ?>">
			<?php echo esc_html( $label_text ); ?>
		</label>
	<?php endif; ?>

	<?php
	$button_classes = 'wp-block-query-filter-taxonomy__buttons';
	$is_dropdown = ! empty( $attributes['customDropdown'] );
	$empty_label = $attributes['emptyLabel'] ?: __( 'All', 'query-filter' );

	if ( $is_dropdown ) {
		$button_classes .= ' is-dropdown-style';

		// The open state is store state bound through directives, so it survives the router
		// re-rendering the query region after each filter change.
		$container_attrs = ' data-wp-class--is-open="state.isDropdownOpen"'
			. ' data-wp-on--keydown="actions.closeDropdownOnEscape"'
			. ' data-wp-on-document--click="actions.closeDropdownOnOutsideClick"';
		$trigger_attrs = ' data-wp-on--click="actions.toggleDropdown"'
			. ' aria-expanded="false" data-wp-bind--aria-expanded="state.isDropdownOpen"'
			. ' aria-controls="' . esc_attr( $id . '-items' ) . '"';
	} else {
		$container_attrs = '';
		$trigger_attrs = ' data-wp-on--click="actions.clearSelections"'
			. ' aria-pressed="' . ( empty( $selected_terms ) ? 'true' : 'false' ) . '"';
	}

	if ( ! empty( $attributes['singleSelect'] ) ) {
		$button_classes .= ' is-single-select';
	}
	?>

	<?php // phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped -- Literal attribute strings and esc_attr()'d values assembled above. ?>
	<div class="<?php echo esc_attr( $button_classes ); ?>"<?php echo $group_attrs . $container_attrs; ?>>
		<?php // phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped -- Literal attribute strings and esc_attr()'d values assembled above. ?>
		<button type="button"<?php echo $trigger_attrs; ?>
			<?php echo empty( $selected_terms ) ? ' aria-current="true"' : ''; ?>
			class="wp-block-query-filter-taxonomy__button empty-choice">
			<?php
			// The dropdown trigger can show the filter's label followed by the selected term
			// names, or by the empty choice label when nothing is selected.
			if ( $is_dropdown && ! empty( $attributes['selectionInTrigger'] ) ) :
				$selected_names = wp_list_pluck( array_filter( $terms, $is_selected ), 'name' );
				?>
				<span class="wp-block-query-filter-taxonomy__trigger-label"><?php echo esc_html( $label_text ); ?></span>
				<span class="wp-block-query-filter-taxonomy__trigger-value"><?php echo esc_html( $selected_names ? implode( ', ', $selected_names ) : $empty_label ); ?></span>
			<?php else : ?>
				<?php echo esc_html( $empty_label ); ?>
			<?php endif; ?>
		</button>

		<?php if ( $is_dropdown ) : ?>
			<div class="wp-block-query-filter-taxonomy__dropdown-items" id="<?php echo esc_attr( $id . '-items' ); ?>">
		<?php endif; ?>

		<?php foreach ( $terms as $term ) : ?>
			<button type="button"
				data-term-slug="<?php echo esc_attr( $term->slug ); ?>"
				data-wp-on--click="actions.toggleTerm"
				aria-pressed="<?php echo $is_selected( $term ) ? 'true' : 'false'; ?>"
				<?php echo $is_selected( $term ) ? ' aria-current="true"' : ''; ?>
				class="wp-block-query-filter-taxonomy__button">
				<?php echo esc_html( $term->name ); ?>
			</button>
		<?php endforeach; ?>

		<?php if ( $is_dropdown ) : ?>
				<button type="button"
					data-wp-on--click="actions.clearSelections"
					class="wp-block-query-filter-taxonomy__clear-filters">
					<?php echo esc_html( __( 'Clear all', 'query-filter' ) ); ?>
				</button>
			</div>
		<?php endif; ?>

	</div>
</div>
