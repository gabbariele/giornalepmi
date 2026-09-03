<?php
/**
 * Area commenti.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="comments-area">

	<?php if ( have_comments() ) : ?>
		<h2 class="section-title">
			<?php
			$gpmi_count = (int) get_comments_number();
			printf(
				/* translators: %s: numero di commenti. */
				esc_html( _n( '%s commento', '%s commenti', $gpmi_count, 'gpmi' ) ),
				esc_html( number_format_i18n( $gpmi_count ) )
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments( array(
				'style'       => 'ol',
				'short_ping'  => true,
				'avatar_size' => 48,
			) );
			?>
		</ol>

		<?php
		the_comments_pagination( array(
			'prev_text' => gpmi_icon( 'chevron-left', 16 ),
			'next_text' => gpmi_icon( 'chevron-right', 16 ),
		) );
		?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="no-comments"><?php esc_html_e( 'I commenti sono chiusi.', 'gpmi' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form( array(
		'title_reply'        => __( 'Lascia un commento', 'gpmi' ),
		'class_submit'       => 'btn btn-primary',
		'comment_notes_before' => '',
	) );
	?>

</section>
