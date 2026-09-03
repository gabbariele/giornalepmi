<?php
/**
 * Chiusura dei commenti a livello di sito.
 *
 * Due interruttori distinti, perche' sono due decisioni diverse:
 *
 *  - chiudere i commenti impedisce di scriverne di nuovi, ma quelli gia'
 *    pubblicati restano leggibili;
 *  - nasconderli toglie dalla vista anche l'archivio esistente.
 *
 * Sul giornale ci sono commenti gia' pubblicati: cancellarli o nasconderli
 * senza volerlo significherebbe perdere contenuti scritti dai lettori, quindi
 * il secondo interruttore e' separato e spento di default.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chiude i commenti ovunque, per qualsiasi tipo di contenuto.
 *
 * @param bool $open Se i commenti sono aperti.
 * @return bool
 */
function gpmi_close_comments( $open ) {
	return gpmi_option( 'disable_comments' ) ? false : $open;
}
add_filter( 'comments_open', 'gpmi_close_comments', 20 );
add_filter( 'pings_open', 'gpmi_close_comments', 20 );

/**
 * Nasconde i commenti gia' pubblicati, se richiesto.
 *
 * @param array $comments Commenti recuperati.
 * @return array
 */
function gpmi_hide_existing_comments( $comments ) {
	if ( gpmi_option( 'disable_comments' ) && gpmi_option( 'hide_existing_comments' ) ) {
		return array();
	}
	return $comments;
}
add_filter( 'comments_array', 'gpmi_hide_existing_comments', 20 );

/**
 * Azzera il conteggio mostrato nei metadati quando i commenti sono nascosti.
 *
 * @param int $count Numero di commenti.
 * @return int
 */
function gpmi_comments_number( $count ) {
	if ( gpmi_option( 'disable_comments' ) && gpmi_option( 'hide_existing_comments' ) ) {
		return 0;
	}
	return $count;
}
add_filter( 'get_comments_number', 'gpmi_comments_number', 20 );

/**
 * Toglie il feed dei commenti dall'head e i relativi link.
 */
function gpmi_remove_comment_feeds() {
	if ( ! gpmi_option( 'disable_comments' ) ) {
		return;
	}

	remove_action( 'wp_head', 'feed_links', 2 );
	add_action( 'wp_head', 'gpmi_feed_links_without_comments', 2 );
}
add_action( 'init', 'gpmi_remove_comment_feeds' );

/**
 * Ristampa il solo feed dei contenuti, senza quello dei commenti.
 */
function gpmi_feed_links_without_comments() {
	printf(
		'<link rel="alternate" type="%s" title="%s" href="%s">' . "\n",
		esc_attr( feed_content_type() ),
		esc_attr( sprintf(
			/* translators: %s: nome del sito. */
			__( '%s &raquo; Feed', 'gpmi' ),
			get_bloginfo( 'name' )
		) ),
		esc_url( get_feed_link() )
	);
}

/**
 * Blocca sul nascere l'invio di un commento, anche via richiesta diretta.
 *
 * Chiudere i commenti nasconde il modulo, ma wp-comments-post.php resta
 * raggiungibile: gli spambot ci arrivano lo stesso.
 */
function gpmi_block_comment_posting() {
	if ( gpmi_option( 'disable_comments' ) ) {
		wp_die(
			esc_html__( 'I commenti sono chiusi.', 'gpmi' ),
			esc_html__( 'Commenti chiusi', 'gpmi' ),
			array( 'response' => 403 )
		);
	}
}
add_action( 'pre_comment_on_post', 'gpmi_block_comment_posting' );

/**
 * Nasconde nell'area di amministrazione le voci legate ai commenti.
 *
 * Non tocca i dati: i commenti restano nel database e tornano visibili
 * riaccendendo l'opzione.
 */
function gpmi_hide_admin_comments() {
	if ( ! gpmi_option( 'disable_comments' ) ) {
		return;
	}

	remove_menu_page( 'edit-comments.php' );

	// Toglie anche la voce nella barra di amministrazione.
	add_action( 'wp_before_admin_bar_render', function () {
		global $wp_admin_bar;
		$wp_admin_bar->remove_node( 'comments' );
	} );
}
add_action( 'admin_menu', 'gpmi_hide_admin_comments', 999 );

/**
 * Toglie il supporto ai commenti dai tipi di contenuto.
 *
 * Serve a far sparire il pannello dall'editor, altrimenti la redazione
 * continuerebbe a vedere un'opzione che non ha piu' effetto.
 */
function gpmi_remove_comment_support() {
	if ( ! gpmi_option( 'disable_comments' ) ) {
		return;
	}

	foreach ( get_post_types( array( 'public' => true ) ) as $type ) {
		remove_post_type_support( $type, 'comments' );
		remove_post_type_support( $type, 'trackbacks' );
	}
}
add_action( 'init', 'gpmi_remove_comment_support', 100 );
