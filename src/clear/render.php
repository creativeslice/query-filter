<?php
/**
 * CS: the clear filters block. It shows only while the loop has a filter, sort, or search
 * parameter, and its button removes all of them (actions.clearAll). The router re-renders the
 * query region after every change, so the button appears and disappears with the parameters.
 *
 * The block is hidden rather than left out while nothing is active. The router diffs the new
 * region against the old one by position, so an element that appears mid-session shifts every
 * later block one place, and each then inherits the interactive state of the block before it:
 * with this block placed before the sort block, the sort dropdown stopped opening after the
 * first sort.
 *
 * @var array    $attributes Block attributes array.
 * @var WP_Block $block      WP_Block instance being rendered.
 */

$inherit = ! empty( $block->context['query']['inherit'] );

if ( $inherit ) {
	$prefix = 'query-';
	$page_var = 'page';
} else {
	$prefix = sprintf( 'query-%d-', $block->context['queryId'] ?? 0 );
	$page_var = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
}

$is_active = false;

foreach ( $_GET as $key => $value ) {
	if ( $key === $page_var || ! is_scalar( $value ) || '' === $value || ! str_starts_with( $key, $prefix ) ) {
		continue;
	}

	// An inherited loop reads the bare query- prefix, which every non-inherited loop's
	// query-{id}- parameters also start with.
	if ( $inherit && preg_match( '/^query-\d+-/', $key ) ) {
		continue;
	}

	$is_active = true;
	break;
}
?>

<div <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?>
	<?php echo $is_active ? '' : 'hidden'; ?>
	data-wp-interactive="query-filter"
	<?php
	// phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped -- wp_interactivity_data_wp_context() returns an escaped attribute.
	echo wp_interactivity_data_wp_context(
		[
			'prefix'  => $prefix,
			'pageVar' => $page_var,
			'inherit' => $inherit,
		]
	);
	?>
>
	<button type="button" class="wp-block-query-filter-clear__button" data-wp-on--click="actions.clearAll">
		<?php echo esc_html( $attributes['label'] ?: __( 'Clear Filters', 'query-filter' ) ); ?>
	</button>
</div>
