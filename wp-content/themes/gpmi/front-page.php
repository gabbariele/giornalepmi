<?php
/**
 * Homepage.
 *
 * Due blocchi:
 *  - apertura, gli ultimi articoli pubblicati;
 *  - griglia, i pinnati in testa e poi il resto per data, senza ripetere
 *    nulla di quanto gia' mostrato in apertura.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

get_header();

$paged = max( 1, (int) get_query_var( 'paged' ) );

// Il blocco di apertura si mostra solo in prima pagina.
$featured_ids = gpmi_featured_ids( 5 );
$featured     = ( 1 === $paged ) ? gpmi_query_from_ids( $featured_ids ) : null;

$max_pages = 1;
$grid_ids  = gpmi_grid_page_ids( $featured_ids, $paged, $max_pages );
$grid      = gpmi_query_from_ids( $grid_ids );
?>

<?php if ( $featured && $featured->have_posts() ) : ?>
	<section class="featured" aria-label="<?php esc_attr_e( 'In apertura', 'gpmi' ); ?>">
		<div class="container featured-grid">
			<?php
			$i = 0;
			while ( $featured->have_posts() ) :
				$featured->the_post();
				$i++;
				get_template_part( 'template-parts/card', null, array(
					'variant' => 1 === $i ? 'hero' : 'tile',
					'eager'   => 1 === $i,
				) );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</section>
<?php endif; ?>

<div class="container layout <?php echo is_active_sidebar( 'sidebar-1' ) ? 'has-sidebar' : ''; ?>">

	<main id="primary" class="site-main">

		<h2 class="section-title"><?php esc_html_e( 'Ultime notizie', 'gpmi' ); ?></h2>

		<?php if ( $grid->have_posts() ) : ?>
			<div class="post-grid" style="--cols:<?php echo esc_attr( (int) gpmi_option( 'posts_columns' ) ); ?>">
				<?php
				while ( $grid->have_posts() ) :
					$grid->the_post();
					get_template_part( 'template-parts/card', null, array( 'variant' => 'grid' ) );
				endwhile;
				wp_reset_postdata();
				?>
			</div>

			<?php gpmi_pagination( $max_pages ); ?>
		<?php else : ?>
			<p class="no-results"><?php esc_html_e( 'Nessun altro articolo da mostrare.', 'gpmi' ); ?></p>
		<?php endif; ?>

	</main>

	<?php get_sidebar(); ?>

</div>

<?php
get_footer();
