<?php
/**
 * Funzioni di output riutilizzate dai template.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icone SVG inline.
 *
 * Sostituiscono le due librerie FontAwesome caricate dal tema precedente
 * (circa 180 KB fra CSS e webfont) con qualche centinaio di byte per pagina.
 *
 * @param string $name  Nome dell'icona.
 * @param int    $size  Lato in pixel.
 * @param array  $attrs Attributi extra.
 * @return string
 */
function gpmi_icon( $name, $size = 16, $attrs = array() ) {
	$paths = array(
		'search'        => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'menu'          => '<path d="M3 6h18M3 12h18M3 18h18"/>',
		'close'         => '<path d="M18 6 6 18M6 6l12 12"/>',
		'user'          => '<path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="7" r="4"/>',
		'clock'         => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'comment'       => '<path d="M21 12a8 8 0 0 1-11.5 7.2L3 21l1.8-6.5A8 8 0 1 1 21 12Z"/>',
		'timer'         => '<path d="M4 4v6h6M20 20v-6h-6"/><path d="M20 10a8 8 0 0 0-14.9-3M4 14a8 8 0 0 0 14.9 3"/>',
		'bolt'          => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>',
		'chevron-left'  => '<path d="m15 18-6-6 6-6"/>',
		'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
		'arrow-right'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'facebook'      => '<path d="M14 9V7c0-1 .4-1.5 1.5-1.5H17V2.5h-2.5C11.8 2.5 11 4.2 11 6.3V9H8.5v3H11v9.5h3V12h2.3l.4-3H14Z"/>',
		'x'             => '<path d="M4 4l7.5 9.5L4.5 21h2l6-6.4L17 21h4l-7.8-9.9L20 4h-2l-5.5 5.9L8 4H4Z"/>',
		'linkedin'      => '<path d="M5 9v10M5 5.5v.01M10 19v-6a3 3 0 0 1 6 0v6"/>',
		'rss'           => '<path d="M5 19a1 1 0 1 0 0-.01M5 12a7 7 0 0 1 7 7M5 5a14 14 0 0 1 14 14"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	$extra = '';
	foreach ( $attrs as $key => $value ) {
		$extra .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
	}

	// Le icone sono decorative: il testo accanto porta gia' il significato.
	return sprintf(
		'<svg class="icon icon-%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"%3$s>%4$s</svg>',
		esc_attr( $name ),
		(int) $size,
		$extra,
		$paths[ $name ]
	);
}

/**
 * Badge della categoria principale di un articolo.
 *
 * @param int|WP_Post $post Articolo.
 * @param bool        $echo Stampa o restituisce.
 * @return string
 */
function gpmi_category_badge( $post = null, $echo = true ) {
	$post = get_post( $post );
	$cats = $post ? get_the_category( $post->ID ) : array();

	if ( empty( $cats ) ) {
		return '';
	}

	// Yoast segnala la categoria primaria: se c'e', ha la precedenza.
	$primary_id = (int) get_post_meta( $post->ID, '_yoast_wpseo_primary_category', true );
	$primary    = $cats[0];

	if ( $primary_id ) {
		foreach ( $cats as $cat ) {
			if ( $cat->term_id === $primary_id ) {
				$primary = $cat;
				break;
			}
		}
	}

	$html = sprintf(
		'<a class="cat-badge" href="%s">%s</a>',
		esc_url( get_category_link( $primary ) ),
		esc_html( $primary->name )
	);

	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- gia' escapato sopra.
	}
	return $html;
}

/**
 * Tempo di lettura stimato, in minuti.
 *
 * @param int|WP_Post $post Articolo.
 * @return int
 */
function gpmi_reading_time( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 1;
	}

	$text  = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	$words = count( preg_split( '/[\s\p{P}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY ) );

	// 200 parole al minuto e' la media per la prosa giornalistica italiana.
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Riga di metadati sotto al titolo: autore, data, commenti, tempo di lettura.
 *
 * @param array $show Elementi da mostrare.
 */
function gpmi_entry_meta( $show = array( 'author', 'date', 'comments', 'reading' ) ) {
	echo '<div class="entry-meta">';

	if ( in_array( 'author', $show, true ) ) {
		printf(
			'<span class="meta-item meta-author">%s<a href="%s" rel="author">%s</a></span>',
			gpmi_icon( 'user', 14 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
			esc_html( get_the_author() )
		);
	}

	if ( in_array( 'date', $show, true ) ) {
		printf(
			'<span class="meta-item meta-date">%s<time datetime="%s">%s</time></span>',
			gpmi_icon( 'clock', 14 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_attr( get_the_date( DATE_W3C ) ),
			esc_html( gpmi_relative_date() )
		);
	}

	if ( in_array( 'comments', $show, true ) && ( comments_open() || get_comments_number() ) ) {
		printf(
			'<span class="meta-item meta-comments">%s<a href="%s">%s</a></span>',
			gpmi_icon( 'comment', 14 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_url( get_comments_link() ),
			esc_html( number_format_i18n( get_comments_number() ) )
		);
	}

	if ( in_array( 'reading', $show, true ) ) {
		$minutes = gpmi_reading_time();
		printf(
			'<span class="meta-item meta-reading">%s%s</span>',
			gpmi_icon( 'timer', 14 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			/* translators: %d: minuti di lettura. */
			esc_html( sprintf( _n( '%d min', '%d min', $minutes, 'gpmi' ), $minutes ) )
		);
	}

	echo '</div>';
}

/**
 * Data relativa ("3 ore fa") per i contenuti recenti, assoluta per i piu' vecchi.
 *
 * @param int|WP_Post $post Articolo.
 * @return string
 */
function gpmi_relative_date( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}

	$published = (int) get_post_time( 'U', true, $post );
	$diff      = time() - $published;

	if ( $diff < 0 || $diff > WEEK_IN_SECONDS ) {
		return get_the_date( '', $post );
	}

	/* translators: %s: intervallo di tempo trascorso, es. "3 ore". */
	return sprintf( __( '%s fa', 'gpmi' ), human_time_diff( $published, time() ) );
}

/**
 * Breadcrumb: usa quello di Yoast se disponibile, altrimenti ne genera uno.
 */
function gpmi_breadcrumbs() {
	if ( function_exists( 'yoast_breadcrumb' ) ) {
		yoast_breadcrumb(
			'<nav class="breadcrumbs" aria-label="' . esc_attr__( 'Percorso', 'gpmi' ) . '"><div class="container">',
			'</div></nav>'
		);
		return;
	}

	if ( is_front_page() ) {
		return;
	}

	echo '<nav class="breadcrumbs" aria-label="' . esc_attr__( 'Percorso', 'gpmi' ) . '"><div class="container">';
	printf( '<a href="%s">%s</a>', esc_url( home_url( '/' ) ), esc_html__( 'Home', 'gpmi' ) );

	$sep = ' <span class="sep">&rsaquo;</span> ';

	if ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( $cats ) {
			printf(
				'%s<a href="%s">%s</a>',
				$sep, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_url( get_category_link( $cats[0] ) ),
				esc_html( $cats[0]->name )
			);
		}
		printf( '%s<span aria-current="page">%s</span>', $sep, esc_html( get_the_title() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} elseif ( is_archive() ) {
		printf( '%s<span aria-current="page">%s</span>', $sep, esc_html( wp_strip_all_tags( get_the_archive_title() ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} elseif ( is_search() ) {
		printf( '%s<span aria-current="page">%s</span>', $sep, esc_html( get_search_query() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} elseif ( is_singular() ) {
		printf( '%s<span aria-current="page">%s</span>', $sep, esc_html( get_the_title() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</div></nav>';
}

/**
 * Paginazione degli archivi.
 *
 * @param WP_Query|int|null $query Query da paginare, o direttamente il numero
 *                                 di pagine quando il conteggio e' calcolato
 *                                 a mano (griglia della homepage).
 */
function gpmi_pagination( $query = null ) {
	if ( is_numeric( $query ) ) {
		$total = (int) $query;
	} else {
		$total = $query instanceof WP_Query ? (int) $query->max_num_pages : 0;
	}

	$links = paginate_links(
		array(
			'total'     => $total,
			'current'   => max( 1, get_query_var( 'paged' ) ),
			'mid_size'  => 1,
			'end_size'  => 1,
			'type'      => 'array',
			'prev_text' => gpmi_icon( 'chevron-left', 16 ) . '<span>' . esc_html__( 'Precedenti', 'gpmi' ) . '</span>',
			'next_text' => '<span>' . esc_html__( 'Successivi', 'gpmi' ) . '</span>' . gpmi_icon( 'chevron-right', 16 ),
		)
	);

	if ( empty( $links ) ) {
		return;
	}

	echo '<nav class="pagination" aria-label="' . esc_attr__( 'Paginazione', 'gpmi' ) . '"><ul>';
	foreach ( $links as $link ) {
		echo '<li>' . $link . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup generato dal core.
	}
	echo '</ul></nav>';
}

/**
 * Logo del sito, con fallback al nome testuale.
 */
function gpmi_site_branding() {
	if ( has_custom_logo() ) {
		$id  = (int) get_theme_mod( 'custom_logo' );
		$img = wp_get_attachment_image(
			$id,
			'full',
			false,
			array(
				'class'         => 'site-logo',
				'fetchpriority' => 'high',
				'decoding'      => 'sync',
				'loading'       => 'eager',
			)
		);

		printf(
			'<a class="custom-logo-link" href="%s" rel="home" aria-label="%s">%s</a>',
			esc_url( home_url( '/' ) ),
			esc_attr( get_bloginfo( 'name' ) ),
			$img // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		return;
	}

	printf(
		'<p class="site-title"><a href="%s" rel="home">%s</a></p>',
		esc_url( home_url( '/' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}

/**
 * Dati legali della testata, per il footer.
 *
 * Una testata giornalistica registrata deve indicare editore e direttore
 * responsabile in modo raggiungibile da ogni pagina: qui stanno accanto al
 * nome del giornale, non nascosti in una pagina di servizio.
 */
function gpmi_masthead_legal() {
	$publisher = gpmi_option( 'publisher_name' );
	$city      = gpmi_option( 'publisher_city' );
	$editor    = gpmi_option( 'editor_name' );
	$reg       = gpmi_option( 'registration' );

	if ( ! $publisher && ! $editor && ! $reg ) {
		return;
	}

	echo '<div class="masthead-legal">';

	if ( $publisher ) {
		printf(
			'<p><span class="legal-label">%s</span> %s%s</p>',
			esc_html__( 'Editore', 'gpmi' ),
			esc_html( $publisher ),
			$city ? ' &ndash; ' . esc_html( $city ) : ''
		);
	}

	if ( $editor ) {
		printf(
			'<p><span class="legal-label">%s</span> %s</p>',
			esc_html__( 'Direttore responsabile', 'gpmi' ),
			esc_html( $editor )
		);
	}

	if ( $reg ) {
		printf( '<p class="legal-registration">%s</p>', esc_html( $reg ) );
	}

	echo '</div>';
}
