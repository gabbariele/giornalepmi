<?php
/**
 * Ticker "FLASH" con le ultime notizie.
 *
 * Lo scorrimento e' una animazione CSS su un contenitore con scroll-snap:
 * sostituisce jquery.marquee e slick.js del tema precedente. Si ferma al
 * passaggio del mouse, al focus da tastiera e con prefers-reduced-motion.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

if ( ! gpmi_option( 'ticker_enabled' ) ) {
	return;
}

$ticker = gpmi_ticker_query( 8 );

if ( ! $ticker->have_posts() ) {
	return;
}
?>
<section class="ticker" aria-label="<?php esc_attr_e( 'Ultime notizie', 'gpmi' ); ?>">
	<div class="container ticker-inner">

		<p class="ticker-label">
			<?php echo gpmi_icon( 'bolt', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php echo esc_html( gpmi_option( 'ticker_label' ) ); ?></span>
		</p>

		<div class="ticker-track" data-ticker>
			<ul class="ticker-list">
				<?php
				while ( $ticker->have_posts() ) :
					$ticker->the_post();
					?>
					<li class="ticker-item">
						<a href="<?php the_permalink(); ?>">
							<?php if ( has_post_thumbnail() ) : ?>
								<span class="ticker-thumb">
									<?php
									the_post_thumbnail( 'gpmi-thumb', array(
										'loading'  => 'lazy',
										'decoding' => 'async',
										'sizes'    => '48px',
										'alt'      => '',
									) );
									?>
								</span>
							<?php endif; ?>
							<span class="ticker-text">
								<span class="ticker-title"><?php the_title(); ?></span>
								<span class="ticker-date"><?php echo esc_html( gpmi_relative_date() ); ?></span>
							</span>
						</a>
					</li>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</ul>
		</div>

		<div class="ticker-nav">
			<button type="button" class="ticker-prev" data-ticker-prev>
				<?php echo gpmi_icon( 'chevron-left', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Notizia precedente', 'gpmi' ); ?></span>
			</button>
			<button type="button" class="ticker-next" data-ticker-next>
				<?php echo gpmi_icon( 'chevron-right', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Notizia successiva', 'gpmi' ); ?></span>
			</button>
		</div>

	</div>
</section>
