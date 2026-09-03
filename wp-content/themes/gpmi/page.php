<?php
/**
 * Pagina statica.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="container layout <?php echo is_active_sidebar( 'sidebar-1' ) ? 'has-sidebar' : ''; ?>">

	<main id="primary" class="site-main">

		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'single-post' ); ?>>

				<header class="entry-header">
					<h1 class="entry-title"><?php the_title(); ?></h1>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="entry-media">
						<?php gpmi_post_thumbnail( get_post(), 'gpmi-single', '(max-width: 900px) 100vw, 848px', true ); ?>
					</figure>
				<?php endif; ?>

				<div class="entry-content">
					<?php
					the_content();
					wp_link_pages( array(
						'before' => '<nav class="page-links">' . esc_html__( 'Pagine:', 'gpmi' ) . ' ',
						'after'  => '</nav>',
					) );
					?>
				</div>

			</article>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
			<?php
		endwhile;
		?>

	</main>

	<?php get_sidebar(); ?>

</div>

<?php
get_footer();
