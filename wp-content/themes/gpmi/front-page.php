<?php
/**
 * Homepage.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

get_header();

$featured    = gpmi_featured_query( 5 );
$featured_ids = wp_list_pluck( $featured->posts, 'ID' );
$paged       = max( 1, (int) get_query_var( 'paged' ) );

// Il blocco in evidenza si mostra solo in prima pagina della paginazione.
$show_featured = ( 1 === $paged ) && $featured->have_posts();
?>

<?php if ( $show_featured ) : ?>
	<section class="featured" aria-label="<?php esc_attr_e( 'In evidenza', 'gpmi' ); ?>">
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

		<?php
		$latest = gpmi_latest_query(
			$show_featured ? $featured_ids : array(),
			(int) get_option( 'posts_per_page', 10 ),
			$paged
		);

		if ( $latest->have_posts() ) :
			$columns = (int) gpmi_option( 'posts_columns' );
			?>
			<div class="post-grid" style="--cols:<?php echo esc_attr( $columns ); ?>">
				<?php
				while ( $latest->have_posts() ) :
					$latest->the_post();
					get_template_part( 'template-parts/card', null, array( 'variant' => 'grid' ) );
				endwhile;
				?>
			</div>

			<?php
			gpmi_pagination( $latest );
			wp_reset_postdata();
			?>
		<?php else : ?>
			<p class="no-results"><?php esc_html_e( 'Nessun articolo pubblicato.', 'gpmi' ); ?></p>
		<?php endif; ?>

	</main>

	<?php get_sidebar(); ?>

</div>

<?php
get_footer();
