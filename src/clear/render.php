<?php
/**
 * CS: the clear filters block. It renders only while the loop has a filter, sort, or search
 * parameter, and its button removes all of them (actions.clearAll). The router re-renders the
 * query region after every change, so the button appears and disappears with the parameters.
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

if ( ! $is_active ) {
	return;
}
?>

<div <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?>
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
