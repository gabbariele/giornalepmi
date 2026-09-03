<?php
/**
 * Query della homepage, con cache a oggetti.
 *
 * Con 11.500 articoli le query della home sono la parte piu' costosa della
 * pagina. Qui vengono eseguite una sola volta e messe in cache finche' non
 * viene pubblicato o modificato un contenuto.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Durata della cache delle query di homepage, in secondi.
 *
 * @return int
 */
function gpmi_query_cache_ttl() {
	return (int) apply_filters( 'gpmi_query_cache_ttl', 10 * MINUTE_IN_SECONDS );
}

/**
 * Restituisce una lista di ID articolo, con cache.
 *
 * Si mettono in cache solo gli ID: gli oggetti post completi vengono poi
 * caricati dal core in un'unica query con la cache dei post gia' calda.
 *
 * @param string $key  Chiave identificativa della lista.
 * @param array  $args Argomenti per WP_Query.
 * @return int[]
 */
function gpmi_get_post_ids( $key, array $args ) {
	$cache_key = 'gpmi_ids_' . $key . '_' . substr( md5( wp_json_encode( $args ) ), 0, 8 );
	$ids       = get_transient( $cache_key );

	if ( false !== $ids ) {
		return $ids;
	}

	$defaults = array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'fields'                 => 'ids',
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);

	$query = new WP_Query( wp_parse_args( $args, $defaults ) );
	$ids   = $query->posts;

	set_transient( $cache_key, $ids, gpmi_query_cache_ttl() );
	gpmi_register_cache_key( $cache_key );

	return $ids;
}

/**
 * Tiene un registro delle chiavi di cache create, per poterle invalidare.
 *
 * @param string $cache_key Chiave da registrare.
 */
function gpmi_register_cache_key( $cache_key ) {
	$keys = get_option( 'gpmi_cache_keys', array() );
	if ( in_array( $cache_key, $keys, true ) ) {
		return;
	}
	$keys[] = $cache_key;
	update_option( 'gpmi_cache_keys', array_slice( $keys, -60 ), false );
}

/**
 * Svuota la cache delle query quando cambia un contenuto.
 */
function gpmi_flush_query_cache() {
	foreach ( get_option( 'gpmi_cache_keys', array() ) as $cache_key ) {
		delete_transient( $cache_key );
	}
	update_option( 'gpmi_cache_keys', array(), false );
}
add_action( 'save_post_post', 'gpmi_flush_query_cache' );
// Mettere o togliere il pin non passa sempre da un salvataggio dell'articolo.
add_action( 'update_option_sticky_posts', 'gpmi_flush_query_cache' );
add_action( 'deleted_post', 'gpmi_flush_query_cache' );
add_action( 'switch_theme', 'gpmi_flush_query_cache' );

/**
 * Costruisce una WP_Query a partire da ID gia' in cache.
 *
 * @param int[] $ids ID degli articoli, nell'ordine desiderato.
 * @return WP_Query
 */
function gpmi_query_from_ids( array $ids ) {
	if ( empty( $ids ) ) {
		return new WP_Query( array( 'post__in' => array( 0 ) ) );
	}

	return new WP_Query( array(
		'post_type'           => 'post',
		'post__in'            => $ids,
		'orderby'             => 'post__in',
		'posts_per_page'      => count( $ids ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
}

/**
 * Articoli in evidenza per il blocco principale della homepage.
 *
 * @param int $limit Numero di articoli.
 * @return WP_Query
 */
function gpmi_featured_query( $limit = 5 ) {
	/*
	 * Gli articoli che la redazione mette "in cima alla pagina principale"
	 * vengono prima di tutto il resto: e' il modo con cui WordPress esprime
	 * una scelta editoriale, e va rispettato.
	 */
	$ids = gpmi_sticky_ids( $limit );

	// Poi la categoria in evidenza, se ne e' stata scelta una.
	$featured_cat = gpmi_option( 'featured_category', '' );

	if ( $featured_cat && count( $ids ) < $limit ) {
		$ids = array_merge( $ids, gpmi_get_post_ids( 'featured', array(
			'posts_per_page' => $limit - count( $ids ),
			'category_name'  => $featured_cat,
			'post__not_in'   => $ids,
		) ) );
	}

	// Infine i piu' recenti, per completare la griglia.
	if ( count( $ids ) < $limit ) {
		$ids = array_merge( $ids, gpmi_get_post_ids( 'latest_fill', array(
			'posts_per_page' => $limit - count( $ids ),
			'post__not_in'   => $ids,
		) ) );
	}

	return gpmi_query_from_ids( array_slice( array_values( array_unique( $ids ) ), 0, $limit ) );
}

/**
 * ID degli articoli in evidenza, dal piu' recente.
 *
 * get_option( 'sticky_posts' ) conserva anche gli ID di articoli poi cancellati
 * o rimessi in bozza: passarli da WP_Query li filtra, altrimenti la griglia
 * della homepage resterebbe con dei buchi.
 *
 * @param int $limit Numero massimo di articoli.
 * @return int[]
 */
function gpmi_sticky_ids( $limit = 5 ) {
	$sticky = get_option( 'sticky_posts' );

	if ( empty( $sticky ) || ! is_array( $sticky ) ) {
		return array();
	}

	return gpmi_get_post_ids( 'sticky', array(
		'post__in'       => array_map( 'absint', $sticky ),
		'posts_per_page' => $limit,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
}

/**
 * Articoli per il ticker "FLASH".
 *
 * @param int $limit Numero di articoli.
 * @return WP_Query
 */
function gpmi_ticker_query( $limit = 6 ) {
	$ids = gpmi_get_post_ids( 'ticker', array( 'posts_per_page' => $limit ) );
	return gpmi_query_from_ids( $ids );
}

/**
 * Ultimi articoli esclusi quelli gia' mostrati in evidenza.
 *
 * @param int[] $exclude ID da escludere.
 * @param int   $limit   Numero di articoli.
 * @param int   $paged   Pagina corrente.
 * @return WP_Query
 */
function gpmi_latest_query( array $exclude = array(), $limit = 10, $paged = 1 ) {
	// La paginazione ha bisogno del conteggio totale: qui la cache non si applica.
	return new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'paged'               => max( 1, (int) $paged ),
		'post__not_in'        => $exclude,
		'ignore_sticky_posts' => true,
	) );
}

/**
 * Articoli correlati per categoria, mostrati in fondo all'articolo.
 *
 * @param int $post_id ID dell'articolo corrente.
 * @param int $limit   Numero di correlati.
 * @return WP_Query
 */
function gpmi_related_query( $post_id, $limit = 4 ) {
	$cats = wp_get_post_categories( $post_id );

	$ids = gpmi_get_post_ids( 'related_' . $post_id, array(
		'posts_per_page' => $limit,
		'post__not_in'   => array( $post_id ),
		'category__in'   => $cats ? $cats : array(),
	) );

	return gpmi_query_from_ids( $ids );
}
