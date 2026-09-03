<?php
/**
 * Modulo di ricerca.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

$gpmi_search_id = wp_unique_id( 'search-field-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $gpmi_search_id ); ?>">
		<?php esc_html_e( 'Cerca nel giornale', 'gpmi' ); ?>
	</label>
	<input
		type="search"
		id="<?php echo esc_attr( $gpmi_search_id ); ?>"
		class="search-field"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'Cerca fra 11.000 articoli...', 'gpmi' ); ?>"
		autocomplete="off"
	>
	<button type="submit" class="search-submit">
		<?php echo gpmi_icon( 'search', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Cerca', 'gpmi' ); ?></span>
	</button>
</form>
