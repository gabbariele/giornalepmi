<?php
/**
 * Registrazione delle funzionalita' del tema, dei menu e delle sidebar.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dichiara il supporto alle funzionalita' core usate dal tema.
 */
function gpmi_setup() {
	load_theme_textdomain( 'gpmi', GPMI_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'custom-logo', array(
		'height'      => 42,
		'width'       => 319,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
		'navigation-widgets',
	) );

	// Dimensioni allineate ai contenitori reali del layout: niente ritagli inutilizzati.
	add_image_size( 'gpmi-hero', 848, 477, true );      // Articolo principale in homepage.
	add_image_size( 'gpmi-card', 420, 236, true );      // Card di griglia e archivi.
	add_image_size( 'gpmi-thumb', 160, 160, true );     // Miniature quadrate di liste e ticker.
	add_image_size( 'gpmi-single', 1120, 630, false );  // Immagine in evidenza dell'articolo.

	register_nav_menus( array(
		'primary' => __( 'Menu principale', 'gpmi' ),
		'topbar'  => __( 'Barra superiore', 'gpmi' ),
		'footer'  => __( 'Footer', 'gpmi' ),
		'social'  => __( 'Social', 'gpmi' ),
	) );
}
add_action( 'after_setup_theme', 'gpmi_setup' );

/**
 * Limita le dimensioni intermedie generate da WordPress a quelle davvero usate.
 *
 * @param array $sizes Dimensioni intermedie registrate.
 * @return array
 */
function gpmi_trim_image_sizes( $sizes ) {
	return array_diff( $sizes, array( 'medium_large', '1536x1536', '2048x2048' ) );
}
add_filter( 'intermediate_image_sizes', 'gpmi_trim_image_sizes' );

/**
 * Registra le aree widget.
 */
function gpmi_widgets_init() {
	$common = array(
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	);

	register_sidebar( array_merge( $common, array(
		'name'        => __( 'Sidebar principale', 'gpmi' ),
		'id'          => 'sidebar-1',
		'description' => __( 'Colonna destra di homepage, archivi e articoli.', 'gpmi' ),
	) ) );

	foreach ( array( 1, 2, 3, 4 ) as $i ) {
		register_sidebar( array_merge( $common, array(
			/* translators: %d: numero della colonna footer. */
			'name'        => sprintf( __( 'Footer %d', 'gpmi' ), $i ),
			'id'          => 'footer-' . $i,
			'description' => __( 'Colonna del footer.', 'gpmi' ),
		) ) );
	}
}
add_action( 'widgets_init', 'gpmi_widgets_init' );

/**
 * Larghezza di riferimento per gli embed.
 */
function gpmi_content_width() {
	$GLOBALS['content_width'] = 848;
}
add_action( 'after_setup_theme', 'gpmi_content_width', 0 );

/**
 * Aggiunge classi di stato utili al body per gli stili condizionali.
 *
 * @param array $classes Classi correnti.
 * @return array
 */
function gpmi_body_classes( $classes ) {
	if ( ! is_active_sidebar( 'sidebar-1' ) || is_page_template( 'templates/full-width.php' ) ) {
		$classes[] = 'no-sidebar';
	}
	if ( is_singular() && has_post_thumbnail() ) {
		$classes[] = 'has-hero-image';
	}
	return $classes;
}
add_filter( 'body_class', 'gpmi_body_classes' );

/**
 * Estratti piu' corti e senza puntini di sospensione con link.
 *
 * @return string
 */
function gpmi_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'gpmi_excerpt_more' );

/**
 * Lunghezza estratto.
 *
 * @return int
 */
function gpmi_excerpt_length() {
	return 28;
}
add_filter( 'excerpt_length', 'gpmi_excerpt_length' );
