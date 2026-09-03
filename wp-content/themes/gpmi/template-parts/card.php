<?php
/**
 * Card di un articolo.
 *
 * Variante passata in $args['variant']:
 *  - 'hero'  card grande con titolo sovrapposto (blocco in evidenza)
 *  - 'tile'  card media con titolo sovrapposto
 *  - 'grid'  card standard con immagine sopra e testo sotto (default)
 *  - 'list'  riga orizzontale con miniatura a sinistra
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

$variant = $args['variant'] ?? 'grid';
$eager   = $args['eager'] ?? null;

$sizes = array(
	'hero' => '(max-width: 900px) 100vw, 848px',
	'tile' => '(max-width: 900px) 50vw, 420px',
	'grid' => '(max-width: 640px) 100vw, (max-width: 1100px) 50vw, 420px',
	'list' => '160px',
);

$image_size = array(
	'hero' => 'gpmi-hero',
	'tile' => 'gpmi-card',
	'grid' => 'gpmi-card',
	'list' => 'gpmi-thumb',
);

$overlay = in_array( $variant, array( 'hero', 'tile' ), true );
?>
<article <?php post_class( 'card card--' . $variant ); ?>>

	<?php if ( has_post_thumbnail() ) : ?>
		<a class="card-media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php
			gpmi_post_thumbnail(
				get_post(),
				$image_size[ $variant ],
				$sizes[ $variant ],
				$eager
			);
			?>
		</a>
	<?php endif; ?>

	<div class="card-body">

		<?php gpmi_category_badge(); ?>

		<h3 class="card-title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>

		<?php if ( ! $overlay ) : ?>
			<?php
			gpmi_entry_meta(
				'list' === $variant
					? array( 'date' )
					: array( 'author', 'date', 'reading' )
			);
			?>

			<?php if ( 'grid' === $variant ) : ?>
				<p class="card-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		<?php else : ?>
			<div class="entry-meta">
				<span class="meta-item meta-date">
					<?php echo gpmi_icon( 'clock', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( gpmi_relative_date() ); ?></time>
				</span>
			</div>
		<?php endif; ?>

	</div>

</article>
