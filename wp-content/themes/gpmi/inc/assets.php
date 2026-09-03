<?php
/**
 * Caricamento di CSS, JS e font.
 *
 * Il tema serve un solo foglio di stile e un solo script, entrambi versionati
 * con filemtime cosi' da poter essere messi in cache per un anno.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Versione basata sulla data di modifica del file, per cache immutabile.
 *
 * @param string $rel Percorso relativo alla root del tema.
 * @return string
 */
function gpmi_asset_version( $rel ) {
	$path = GPMI_DIR . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) filemtime( $path ) : GPMI_VERSION;
}

/**
 * Accoda gli asset del front-end.
 */
function gpmi_enqueue_assets() {
	wp_enqueue_style( 'gpmi', get_stylesheet_uri(), array(), gpmi_asset_version( 'style.css' ) );

	wp_enqueue_script( 'gpmi', GPMI_URI . '/assets/js/app.js', array(), gpmi_asset_version( 'assets/js/app.js' ), true );
	wp_script_add_data( 'gpmi', 'defer', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'gpmi_enqueue_assets' );

/**
 * Preconnessioni e preload dei font locali.
 *
 * I font sono serviti dallo stesso dominio: niente preconnect verso Google
 * Fonts, niente richiesta CSS aggiuntiva, nessun problema di privacy.
 *
 * @param array  $urls Risorse gia' registrate.
 * @param string $relation_type Tipo di hint.
 * @return array
 */
function gpmi_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		// Rimuove il preconnect a fonts.gstatic.com aggiunto dal core o dai plugin.
		foreach ( $urls as $i => $url ) {
			$href = is_array( $url ) ? ( $url['href'] ?? '' ) : $url;
			if ( false !== strpos( (string) $href, 'fonts.gstatic.com' ) || false !== strpos( (string) $href, 'fonts.googleapis.com' ) ) {
				unset( $urls[ $i ] );
			}
		}
		$urls = array_values( $urls );
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'gpmi_resource_hints', 10, 2 );

/**
 * Font locali effettivamente presenti sul disco.
 *
 * Si preferisce il file variabile: copre tutti i pesi con un solo download.
 * I file statici restano supportati come ripiego, per chi li avesse gia'.
 *
 * @return array<string, string> Nome file => valore di font-weight.
 */
function gpmi_available_fonts() {
	$dir = GPMI_DIR . '/assets/fonts/';

	if ( file_exists( $dir . 'roboto-latin-var.woff2' ) ) {
		return array( 'roboto-latin-var.woff2' => '100 900' );
	}

	$fonts = array(
		'roboto-latin-400.woff2' => '400',
		'roboto-latin-700.woff2' => '700',
	);

	return array_filter(
		$fonts,
		function ( $file ) use ( $dir ) {
			return file_exists( $dir . $file );
		},
		ARRAY_FILTER_USE_KEY
	);
}

/**
 * Preload dei font locali, il piu' in alto possibile nel <head>.
 */
function gpmi_preload_fonts() {
	foreach ( array_keys( gpmi_available_fonts() ) as $font ) {
		printf(
			'<link rel="preload" as="font" type="font/woff2" href="%s" crossorigin>' . "\n",
			esc_url( GPMI_URI . '/assets/fonts/' . $font )
		);
	}
}
add_action( 'wp_head', 'gpmi_preload_fonts', 1 );

/**
 * Dichiara le @font-face solo per i file realmente presenti.
 *
 * Se i woff2 non ci sono, non viene emessa nessuna regola e il testo usa
 * subito lo stack di sistema: nessuna richiesta sprecata, nessun FOIT.
 */
function gpmi_font_face_css() {
	$fonts = gpmi_available_fonts();

	if ( ! $fonts ) {
		return;
	}

	$css = '';

	foreach ( $fonts as $file => $weight ) {
		$css .= sprintf(
			'@font-face{font-family:"Roboto Local";src:url("%s") format("woff2");font-weight:%s;font-style:normal;font-display:swap;unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+2000-206F,U+20AC,U+2122;}',
			esc_url( GPMI_URI . '/assets/fonts/' . $file ),
			preg_replace( '/[^0-9 ]/', '', (string) $weight )
		);
	}

	wp_add_inline_style( 'gpmi', $css );
}
add_action( 'wp_enqueue_scripts', 'gpmi_font_face_css', 11 );

/**
 * Aggiunge gli attributi defer/async richiesti via wp_script_add_data.
 *
 * @param string $tag    Tag script completo.
 * @param string $handle Handle dello script.
 * @return string
 */
function gpmi_script_loader_tag( $tag, $handle ) {
	foreach ( array( 'defer', 'async' ) as $attr ) {
		if ( ! wp_scripts()->get_data( $handle, $attr ) || false !== strpos( $tag, ' ' . $attr ) ) {
			continue;
		}
		$tag = str_replace( ' src=', ' ' . $attr . ' src=', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'gpmi_script_loader_tag', 10, 2 );
