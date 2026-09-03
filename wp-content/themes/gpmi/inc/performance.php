<?php
/**
 * Rimozione del peso inutile che WordPress aggiunge di default.
 *
 * Ogni rimozione passa da un filtro dedicato: se un plugin dovesse averne
 * bisogno, si riattiva senza toccare il tema.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ripulisce il <head> dai tag che nessuno consuma piu'.
 */
function gpmi_clean_head() {
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );

	if ( apply_filters( 'gpmi_disable_emoji', true ) ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
		add_filter( 'tiny_mce_plugins', 'gpmi_remove_emoji_tinymce' );
	}
}
add_action( 'init', 'gpmi_clean_head' );

/**
 * Toglie il plugin emoji dall'editor classico.
 *
 * @param array $plugins Plugin TinyMCE.
 * @return array
 */
function gpmi_remove_emoji_tinymce( $plugins ) {
	return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
}

/**
 * Rimuove gli script e gli stili non necessari sul front-end.
 */
function gpmi_dequeue_bloat() {
	if ( is_admin() ) {
		return;
	}

	if ( apply_filters( 'gpmi_remove_jquery_migrate', true ) ) {
		$jquery = wp_scripts()->registered['jquery'] ?? null;
		if ( $jquery && ! empty( $jquery->deps ) ) {
			$jquery->deps = array_diff( $jquery->deps, array( 'jquery-migrate' ) );
		}
	}

	if ( apply_filters( 'gpmi_remove_wp_embed', true ) ) {
		wp_deregister_script( 'wp-embed' );
	}

	/*
	 * classic-theme-styles serve solo ai temi senza theme.json.
	 *
	 * global-styles invece resta: i blocchi nel corpo degli articoli usano le
	 * variabili --wp--preset--*, e toglierle ne romperebbe i colori. Il peso
	 * si riduce dal theme.json del tema, che disattiva i preset inutilizzati
	 * (gradienti, duotone, dimensioni di spaziatura): da 9,3 KB a poco piu'
	 * di 1 KB, senza effetti collaterali.
	 */
	wp_dequeue_style( 'classic-theme-styles' );

	if ( apply_filters( 'gpmi_remove_global_styles', false ) ) {
		wp_dequeue_style( 'global-styles' );
	}

	/*
	 * wp-block-library resta caricato: la sidebar e i contenuti degli articoli
	 * usano blocchi Gutenberg e senza quel CSS si romperebbero.
	 * Filtro disponibile per chi volesse escluderlo su template specifici.
	 */
	if ( apply_filters( 'gpmi_remove_block_library', false ) ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
	}
}
add_action( 'wp_enqueue_scripts', 'gpmi_dequeue_bloat', 100 );

/**
 * Riduce la frequenza dell'heartbeat sul front-end.
 *
 * @param array $settings Impostazioni heartbeat.
 * @return array
 */
function gpmi_heartbeat_settings( $settings ) {
	if ( ! is_admin() ) {
		$settings['interval'] = 120;
	}
	return $settings;
}
add_filter( 'heartbeat_settings', 'gpmi_heartbeat_settings' );

/**
 * Speculation Rules: il browser precarica in background il link sotto il cursore.
 *
 * E' una API nativa del browser, zero JavaScript. Sulle testate giornalistiche
 * e' la singola modifica che incide di piu' sulla navigazione percepita: la
 * seconda pagina appare istantanea.
 */
function gpmi_speculation_rules() {
	if ( is_user_logged_in() || ! apply_filters( 'gpmi_enable_speculation_rules', true ) ) {
		return;
	}

	$rules = array(
		'prerender' => array(
			array(
				'source'    => 'document',
				'where'     => array(
					'and' => array(
						array( 'href_matches' => '/*' ),
						array( 'not' => array( 'href_matches' => array( '/wp-admin/*', '/wp-login.php', '/*\?*(^|&)_wpnonce=*' ) ) ),
						array( 'not' => array( 'selector_matches' => '.no-prerender, [download], [rel~="nofollow"]' ) ),
					),
				),
				'eagerness' => 'moderate',
			),
		),
	);

	printf(
		'<script type="speculationrules">%s</script>' . "\n",
		wp_json_encode( $rules )
	);
}
add_action( 'wp_footer', 'gpmi_speculation_rules' );

/**
 * Header di cache espliciti per le pagine pubbliche.
 *
 * Nginx/FastCGI e Cloudflare davanti al sito rispettano questi header; per gli
 * utenti loggati e le pagine dinamiche la cache resta disattivata.
 */
function gpmi_cache_headers() {
	if ( is_user_logged_in() || is_admin() || is_preview() || is_404() || is_search() ) {
		return;
	}
	if ( ! apply_filters( 'gpmi_send_cache_headers', true ) ) {
		return;
	}

	$max_age  = is_front_page() ? 300 : 600;
	$stale    = 86400;
	$is_https = is_ssl();

	header( sprintf(
		'Cache-Control: public, max-age=%d, s-maxage=%d, stale-while-revalidate=%d, stale-if-error=%d',
		$max_age,
		$max_age,
		$stale,
		$stale
	) );

	if ( $is_https ) {
		header( 'Vary: Accept-Encoding' );
	}
}
add_action( 'template_redirect', 'gpmi_cache_headers' );
