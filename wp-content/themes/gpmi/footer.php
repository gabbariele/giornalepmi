<?php
/**
 * Chiusura del documento e footer.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;
?>
	</div><!-- #content -->

	<footer id="colophon" class="site-footer">

		<?php
		$active_columns = array();
		foreach ( array( 1, 2, 3, 4 ) as $i ) {
			if ( is_active_sidebar( 'footer-' . $i ) ) {
				$active_columns[] = $i;
			}
		}
		?>

		<?php if ( $active_columns ) : ?>
			<div class="footer-widgets">
				<div class="container footer-grid" style="--cols:<?php echo (int) count( $active_columns ); ?>">
					<?php foreach ( $active_columns as $i ) : ?>
						<div class="footer-col">
							<?php dynamic_sidebar( 'footer-' . $i ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="footer-bottom">
			<div class="container footer-bottom-inner">

				<div class="footer-credit">
					<?php
					$footer_text = gpmi_option( 'footer_text' );
					if ( $footer_text ) {
						echo wp_kses_post( wpautop( $footer_text ) );
					} else {
						printf(
							'<p>&copy; %1$s %2$s</p>',
							esc_html( wp_date( 'Y' ) ),
							esc_html( get_bloginfo( 'name' ) )
						);
					}
					?>
				</div>

				<?php
				if ( has_nav_menu( 'footer' ) ) {
					wp_nav_menu( array(
						'theme_location' => 'footer',
						'menu_class'     => 'footer-menu',
						'container'      => 'nav',
						'depth'          => 1,
						'fallback_cb'    => false,
					) );
				}
				?>

				<?php
				if ( has_nav_menu( 'social' ) ) {
					wp_nav_menu( array(
						'theme_location' => 'social',
						'menu_class'     => 'social-menu',
						'container'      => 'nav',
						'depth'          => 1,
						'link_before'    => '<span class="screen-reader-text">',
						'link_after'     => '</span>',
						'fallback_cb'    => false,
					) );
				}
				?>

			</div>
		</div>

	</footer>

</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
