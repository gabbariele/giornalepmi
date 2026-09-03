<?php
/**
 * Articolo singolo.
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
					<?php gpmi_category_badge(); ?>
					<h1 class="entry-title"><?php the_title(); ?></h1>
					<?php gpmi_entry_meta(); ?>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="entry-media">
						<?php gpmi_post_thumbnail( get_post(), 'gpmi-single', '(max-width: 900px) 100vw, 848px', true ); ?>
						<?php
						$caption = wp_get_attachment_caption( get_post_thumbnail_id() );
						if ( $caption ) :
							?>
							<figcaption><?php echo esc_html( $caption ); ?></figcaption>
						<?php endif; ?>
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

				<?php
				$tags = get_the_tag_list( '<ul class="entry-tags"><li>', '</li><li>', '</li></ul>' );
				if ( $tags ) {
					echo $tags; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup generato dal core.
				}
				?>

				<?php
				$author_bio = get_the_author_meta( 'description' );
				if ( $author_bio ) :
					?>
					<aside class="author-box">
						<?php echo get_avatar( get_the_author_meta( 'ID' ), 72, '', '', array( 'loading' => 'lazy' ) ); ?>
						<div class="author-box-text">
							<h2 class="author-name">
								<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" rel="author"><?php the_author(); ?></a>
							</h2>
							<p><?php echo esc_html( $author_bio ); ?></p>
						</div>
					</aside>
				<?php endif; ?>

			</article>

			<?php gpmi_preferred_source_card(); ?>

			<?php
			$prev = get_previous_post();
			$next = get_next_post();
			if ( $prev || $next ) :
				?>
				<nav class="post-nav" aria-label="<?php esc_attr_e( 'Articoli vicini', 'gpmi' ); ?>">
					<?php if ( $prev ) : ?>
						<a class="post-nav-prev" href="<?php echo esc_url( get_permalink( $prev ) ); ?>">
							<span class="post-nav-label"><?php echo gpmi_icon( 'chevron-left', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Articolo precedente', 'gpmi' ); ?></span>
							<span class="post-nav-title"><?php echo esc_html( get_the_title( $prev ) ); ?></span>
						</a>
					<?php endif; ?>
					<?php if ( $next ) : ?>
						<a class="post-nav-next" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
							<span class="post-nav-label"><?php esc_html_e( 'Articolo successivo', 'gpmi' ); ?><?php echo gpmi_icon( 'chevron-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="post-nav-title"><?php echo esc_html( get_the_title( $next ) ); ?></span>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>

			<?php
			$related = gpmi_related_query( get_the_ID(), 4 );
			if ( $related->have_posts() ) :
				?>
				<section class="related" aria-label="<?php esc_attr_e( 'Articoli correlati', 'gpmi' ); ?>">
					<h2 class="section-title"><?php esc_html_e( 'Leggi anche', 'gpmi' ); ?></h2>
					<div class="post-grid" style="--cols:4">
						<?php
						while ( $related->have_posts() ) :
							$related->the_post();
							get_template_part( 'template-parts/card', null, array( 'variant' => 'tile' ) );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</section>
			<?php endif; ?>

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
