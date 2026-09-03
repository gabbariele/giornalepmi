<?php
/**
 * Pagina 404.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="container layout">

	<main id="primary" class="site-main error-404">

		<h1 class="archive-title"><?php esc_html_e( 'Pagina non trovata', 'gpmi' ); ?></h1>
		<p><?php esc_html_e( 'La pagina che cercavi non esiste piu oppure ha cambiato indirizzo.', 'gpmi' ); ?></p>

		<?php get_search_form(); ?>

		<?php
		$recent = gpmi_ticker_query( 6 );
		if ( $recent->have_posts() ) :
			?>
			<h2 class="section-title"><?php esc_html_e( 'Le ultime dal giornale', 'gpmi' ); ?></h2>
			<div class="post-grid" style="--cols:3">
				<?php
				while ( $recent->have_posts() ) :
					$recent->the_post();
					get_template_part( 'template-parts/card', null, array( 'variant' => 'grid' ) );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		<?php endif; ?>

	</main>

</div>

<?php
get_footer();
