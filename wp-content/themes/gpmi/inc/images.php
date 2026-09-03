<?php
/**
 * Gestione delle immagini: formati moderni, priorita' di caricamento, sizes.
 *
 * Sul sito attuale le immagini pesano 2,3 MB su 2,8 MB totali. Qui si agisce
 * su tre fronti: servire AVIF/WebP quando esistono, dichiarare un attributo
 * sizes corretto per non scaricare varianti troppo grandi, e dare priorita'
 * esplicita alla sola immagine LCP.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Qualita' JPEG delle dimensioni generate.
 *
 * @return int
 */
function gpmi_jpeg_quality() {
	return 82;
}
add_filter( 'jpeg_quality', 'gpmi_jpeg_quality' );
add_filter( 'wp_editor_set_quality', 'gpmi_jpeg_quality' );

/**
 * Tiene traccia della prima immagine renderizzata, che e' quasi sempre l'LCP.
 *
 * @param bool $reset Azzera il contatore.
 * @return int Indice dell'immagine corrente.
 */
function gpmi_image_counter( $reset = false ) {
	static $count = 0;
	if ( $reset ) {
		$count = 0;
		return 0;
	}
	return ++$count;
}

/**
 * Stampa un'immagine in evidenza ottimizzata.
 *
 * La prima immagine della pagina viene caricata con fetchpriority alta e senza
 * lazy loading; tutte le altre sono lazy. Se accanto al file originale esiste
 * una versione .avif o .webp, viene servita tramite <picture>.
 *
 * @param int|WP_Post $post  Post di riferimento.
 * @param string      $size  Dimensione registrata.
 * @param string      $sizes Attributo sizes.
 * @param bool        $eager Forza il caricamento prioritario.
 */
function gpmi_post_thumbnail( $post = null, $size = 'gpmi-card', $sizes = '', $eager = null ) {
	$post = get_post( $post );
	if ( ! $post || ! has_post_thumbnail( $post ) ) {
		return;
	}

	$index = gpmi_image_counter();
	if ( null === $eager ) {
		$eager = ( 1 === $index );
	}

	$attr = array(
		'decoding' => 'async',
		'loading'  => $eager ? 'eager' : 'lazy',
		'class'    => 'gpmi-img',
	);

	if ( $eager ) {
		$attr['fetchpriority'] = 'high';
	}
	if ( $sizes ) {
		$attr['sizes'] = $sizes;
	}

	$html = get_the_post_thumbnail( $post, $size, $attr );
	if ( ! $html ) {
		return;
	}

	echo gpmi_wrap_in_picture( $html, get_post_thumbnail_id( $post ), $size ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup generato dal core e gia' escapato.
}

/**
 * Avvolge un tag <img> in un <picture> con sorgenti AVIF/WebP, se presenti.
 *
 * Non converte nulla al volo: cerca semplicemente un file gemello sul disco.
 * La conversione della media library si fa una volta con bin/convert-images.sh.
 *
 * @param string $html Markup <img> originale.
 * @param int    $id   ID dell'allegato.
 * @param string $size Dimensione richiesta.
 * @return string
 */
function gpmi_wrap_in_picture( $html, $id, $size ) {
	if ( ! apply_filters( 'gpmi_use_picture_element', true ) || ! $html ) {
		return $html;
	}

	$src = wp_get_attachment_image_src( $id, $size );
	if ( ! $src ) {
		return $html;
	}

	$uploads = wp_get_upload_dir();
	$sources = '';

	foreach ( array( 'avif' => 'image/avif', 'webp' => 'image/webp' ) as $ext => $mime ) {
		$candidate = preg_replace( '/\.(jpe?g|png)$/i', '.' . $ext, $src[0] );
		if ( $candidate === $src[0] ) {
			continue;
		}

		$path = str_replace( $uploads['baseurl'], $uploads['basedir'], $candidate );
		if ( ! file_exists( $path ) ) {
			continue;
		}

		$sources .= sprintf(
			'<source srcset="%s" type="%s" width="%d" height="%d">',
			esc_url( $candidate ),
			esc_attr( $mime ),
			(int) $src[1],
			(int) $src[2]
		);
	}

	return $sources ? '<picture>' . $sources . $html . '</picture>' : $html;
}

/**
 * Azzera il contatore immagini a ogni nuova richiesta di template.
 */
function gpmi_reset_image_counter() {
	gpmi_image_counter( true );
}
add_action( 'template_redirect', 'gpmi_reset_image_counter' );

/**
 * Impedisce a WordPress di applicare lazy loading alla prima immagine.
 *
 * @param string|bool $value   Valore di loading calcolato dal core.
 * @param string      $image   Markup immagine.
 * @param string      $context Contesto.
 * @return string|bool
 */
function gpmi_no_lazy_first_image( $value, $image, $context ) {
	if ( 'the_content' === $context && false !== strpos( $image, 'fetchpriority="high"' ) ) {
		return false;
	}
	return $value;
}
add_filter( 'wp_img_tag_add_loading_attr', 'gpmi_no_lazy_first_image', 10, 3 );

/**
 * Attributo sizes realistico per le immagini nel corpo dell'articolo.
 *
 * Il valore di default del core (100vw) fa scaricare al browser la variante
 * piu' grande anche quando la colonna e' larga 848px.
 *
 * @param string $sizes Attributo calcolato.
 * @return string
 */
function gpmi_content_image_sizes( $sizes ) {
	if ( is_singular() ) {
		return '(max-width: 900px) 100vw, 848px';
	}
	return $sizes;
}
add_filter( 'wp_calculate_image_sizes', 'gpmi_content_image_sizes' );

/**
 * Preload dell'immagine in evidenza dell'articolo: anticipa l'LCP.
 */
function gpmi_preload_hero_image() {
	if ( ! is_singular() || ! has_post_thumbnail() ) {
		return;
	}

	$id  = get_post_thumbnail_id();
	$src = wp_get_attachment_image_src( $id, 'gpmi-single' );
	if ( ! $src ) {
		return;
	}

	$srcset = wp_get_attachment_image_srcset( $id, 'gpmi-single' );

	printf(
		'<link rel="preload" as="image" href="%s"%s imagesizes="(max-width: 900px) 100vw, 848px" fetchpriority="high">' . "\n",
		esc_url( $src[0] ),
		$srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '"' : ''
	);
}
add_action( 'wp_head', 'gpmi_preload_hero_image', 2 );
