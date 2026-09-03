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
	/*
	 * Con post__in vuoto WordPress non riconosce piu' la query come specifica,
	 * la tratta come query di home e ci antepone da solo gli articoli pinnati.
	 * Per ottenere davvero zero risultati servono i parametri espliciti,
	 * ignore_sticky_posts compreso.
	 */
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'post__in'            => $ids ? $ids : array( 0 ),
		'orderby'             => 'post__in',
		'posts_per_page'      => $ids ? count( $ids ) : 1,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	return new WP_Query( $args );
}

/**
 * Articoli in evidenza per il blocco principale della homepage.
 *
 * @param int $limit Numero di articoli.
 * @return WP_Query
 */
function gpmi_featured_query( $limit = 5 ) {
	// Apertura del giornale: gli ultimi pubblicati, senza eccezioni.
	return gpmi_query_from_ids( gpmi_featured_ids( $limit ) );
}

/**
 * ID degli articoli del blocco di apertura.
 *
 * @param int $limit Numero di articoli.
 * @return int[]
 */
function gpmi_featured_ids( $limit = 5 ) {
	return gpmi_get_post_ids( 'featured', array( 'posts_per_page' => $limit ) );
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
 * Numero di articoli per pagina della griglia, allineato alle colonne.
 *
 * Con dieci articoli su tre colonne l'ultima riga resta con due celle vuote.
 * Si arrotonda per eccesso al multiplo delle colonne, cosi' la griglia chiude
 * sempre piena.
 *
 * @return int
 */
function gpmi_grid_per_page() {
	$cols = max( 1, (int) gpmi_option( 'posts_columns' ) );
	$base = max( 1, (int) get_option( 'posts_per_page' ) );

	return (int) ceil( $base / $cols ) * $cols;
}

/**
 * ID della griglia principale per la pagina richiesta.
 *
 * In prima pagina apre con gli articoli pinnati, poi prosegue per data. Gli
 * articoli del blocco di apertura sono esclusi da ogni pagina, non solo dalla
 * prima: escluderli solo in testa sfaserebbe la paginazione e li farebbe
 * ricomparire piu' avanti.
 *
 * @param int[] $featured_ids ID gia' usati nel blocco di apertura.
 * @param int   $paged        Pagina corrente.
 * @param int   $max_pages    Valorizzato con il numero totale di pagine.
 * @return int[]
 */
function gpmi_grid_page_ids( array $featured_ids, $paged, &$max_pages ) {
	$per_page = gpmi_grid_per_page();
	$paged    = max( 1, (int) $paged );

	// Un pinnato che sia gia' fra gli ultimi pubblicati resta solo in apertura.
	$sticky = array_values( array_diff( gpmi_sticky_ids( $per_page ), $featured_ids ) );
	$sticky = array_slice( $sticky, 0, $per_page );

	$exclude = array_merge( $featured_ids, $sticky );

	// Quanti articoli "normali" restano da mostrare in prima pagina.
	$first_page_rest = max( 0, $per_page - count( $sticky ) );

	if ( 1 === $paged ) {
		$offset = 0;
		$limit  = $first_page_rest;
	} else {
		$offset = $first_page_rest + ( $paged - 2 ) * $per_page;
		$limit  = $per_page;
	}

	$rest = array();
	$total = 0;

	// Con limit a zero la query si puo' saltare del tutto: servirebbe solo il conteggio.
	$query = new WP_Query( array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'post__not_in'           => $exclude,
		'posts_per_page'         => max( 1, $limit ),
		'offset'                 => $offset,
		'ignore_sticky_posts'    => true,
		'fields'                 => 'ids',
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	) );

	$total = (int) $query->found_posts;
	$rest  = $limit > 0 ? $query->posts : array();

	$max_pages = 1 + (int) ceil( max( 0, $total - $first_page_rest ) / $per_page );

	return 1 === $paged ? array_merge( $sticky, $rest ) : $rest;
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
