<?php
/**
 * Colonna laterale.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>
<aside id="secondary" class="sidebar widget-area" aria-label="<?php esc_attr_e( 'Colonna laterale', 'gpmi' ); ?>">
	<div class="sidebar-sticky">
		<?php dynamic_sidebar( 'sidebar-1' ); ?>
	</div>
</aside>
