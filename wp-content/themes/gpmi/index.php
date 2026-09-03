<?php
/**
 * Template generico: blog, archivi di categoria/tag/autore/data e ricerca.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="container layout <?php echo is_active_sidebar( 'sidebar-1' ) ? 'has-sidebar' : ''; ?>">

	<main id="primary" class="site-main">

		<header class="archive-header">
			<?php if ( is_search() ) : ?>
				<h1 class="archive-title">
					<?php
					printf(
						/* translators: %s: termine cercato. */
						esc_html__( 'Risultati per: %s', 'gpmi' ),
						'<span>' . esc_html( get_search_query() ) . '</span>'
					);
					?>
				</h1>
				<p class="archive-count">
					<?php
					printf(
						/* translators: %s: numero di risultati. */
						esc_html( _n( '%s articolo trovato', '%s articoli trovati', (int) $wp_query->found_posts, 'gpmi' ) ),
						esc_html( number_format_i18n( $wp_query->found_posts ) )
					);
					?>
				</p>
			<?php elseif ( is_archive() ) : ?>
				<?php the_archive_title( '<h1 class="archive-title">', '</h1>' ); ?>
				<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
			<?php else : ?>
				<h1 class="archive-title"><?php echo esc_html( get_the_title( (int) get_option( 'page_for_posts' ) ) ?: __( 'Ultime notizie', 'gpmi' ) ); ?></h1>
			<?php endif; ?>
		</header>

		<?php if ( have_posts() ) : ?>

			<div class="post-grid" style="--cols:<?php echo esc_attr( (int) gpmi_option( 'posts_columns' ) ); ?>">
				<?php
				$first = true;
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/card', null, array(
						'variant' => 'grid',
						'eager'   => $first,
					) );
					$first = false;
				endwhile;
				?>
			</div>

			<?php gpmi_pagination( $GLOBALS['wp_query'] ); ?>

		<?php else : ?>

			<div class="no-results">
				<h2><?php esc_html_e( 'Nessun risultato', 'gpmi' ); ?></h2>
				<p><?php esc_html_e( 'Prova con parole diverse o sfoglia le sezioni del giornale.', 'gpmi' ); ?></p>
				<?php get_search_form(); ?>
			</div>

		<?php endif; ?>

	</main>

	<?php get_sidebar(); ?>

</div>

<?php
get_footer();
