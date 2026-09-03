<?php
/**
 * SEO: descrizioni, Open Graph, canonical e dati strutturati.
 *
 * Il tema copre da solo cio' che WordPress non fa, senza plugin. Il core
 * fornisce gia' il tag <title>, il meta robots con max-image-preview:large e
 * il canonical sulle pagine singole: qui si aggiunge il resto.
 *
 * Se un plugin SEO e' attivo il modulo si spegne del tutto, per non duplicare
 * nulla. Le meta lasciate da Yoast restano pero' utilizzate come sorgente:
 * sono anni di descrizioni scritte a mano, e buttarle sarebbe uno spreco.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Il modulo lavora solo se non c'e' gia' un plugin SEO a occuparsene.
 *
 * @return bool
 */
function gpmi_seo_enabled() {
	$plugin_active = function_exists( 'YoastSEO' )
		|| class_exists( 'RankMath' )
		|| function_exists( 'aioseo' )
		|| defined( 'SEOPRESS_VERSION' );

	return (bool) apply_filters( 'gpmi_seo_enabled', ! $plugin_active );
}

/**
 * Descrizione della pagina corrente.
 *
 * Ordine delle sorgenti: la descrizione scritta a mano in Yoast (se c'e'
 * ancora nel database), poi l'estratto, poi l'inizio del contenuto, infine il
 * sottotitolo del sito.
 *
 * @return string
 */
function gpmi_meta_description() {
	$text = '';

	if ( is_singular() ) {
		$post_id = get_the_ID();

		$legacy = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		if ( $legacy ) {
			$text = $legacy;
		}

		if ( ! $text ) {
			$post = get_post( $post_id );
			$text = $post->post_excerpt ? $post->post_excerpt : $post->post_content;
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		$text = $term && ! empty( $term->description ) ? $term->description : '';

		if ( ! $text && $term ) {
			/* translators: %s: nome della sezione. */
			$text = sprintf( __( 'Tutte le notizie della sezione %s del Giornale delle PMI.', 'gpmi' ), $term->name );
		}
	} elseif ( is_author() ) {
		$author = get_queried_object();
		$text   = $author ? get_the_author_meta( 'description', $author->ID ) : '';

		if ( ! $text && $author ) {
			/* translators: %s: nome dell'autore. */
			$text = sprintf( __( 'Articoli firmati da %s sul Giornale delle PMI.', 'gpmi' ), $author->display_name );
		}
	}

	if ( ! $text ) {
		$text = get_bloginfo( 'description' );
	}

	// 160 caratteri e' la soglia oltre la quale Google tronca quasi sempre.
	return gpmi_plain_text( strip_shortcodes( $text ), 160 );
}

/**
 * Immagine di condivisione della pagina corrente.
 *
 * @return array{url:string,width:int,height:int}|null
 */
function gpmi_share_image() {
	$id = 0;

	if ( is_singular() && has_post_thumbnail() ) {
		$id = get_post_thumbnail_id();
	} elseif ( has_custom_logo() ) {
		$id = (int) get_theme_mod( 'custom_logo' );
	}

	if ( ! $id ) {
		return null;
	}

	// 1200x630 e' il formato che Facebook, LinkedIn e X ritagliano meglio.
	$src = wp_get_attachment_image_src( $id, 'gpmi-single' );

	if ( ! $src ) {
		return null;
	}

	return array(
		'url'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
	);
}

/**
 * Stampa descrizione, Open Graph e Twitter Card.
 */
function gpmi_seo_meta() {
	if ( ! gpmi_seo_enabled() ) {
		return;
	}

	$description = gpmi_meta_description();
	$title       = gpmi_seo_title();
	$url         = gpmi_current_url();
	$image       = gpmi_share_image();

	if ( $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}

	$tags = array(
		'og:locale'    => str_replace( '-', '_', get_bloginfo( 'language' ) ),
		'og:site_name' => get_bloginfo( 'name' ),
		'og:type'      => is_singular( 'post' ) ? 'article' : 'website',
		'og:title'     => $title,
		'og:url'       => $url,
	);

	if ( $description ) {
		$tags['og:description'] = $description;
	}

	if ( $image ) {
		$tags['og:image']        = $image['url'];
		$tags['og:image:width']  = (string) $image['width'];
		$tags['og:image:height'] = (string) $image['height'];
	}

	if ( is_singular( 'post' ) ) {
		$tags['article:published_time'] = get_the_date( DATE_W3C );
		$tags['article:modified_time']  = get_the_modified_date( DATE_W3C );

		$sections = wp_get_post_categories( get_the_ID(), array( 'fields' => 'names' ) );
		if ( $sections ) {
			$tags['article:section'] = $sections[0];
		}
	}

	foreach ( $tags as $property => $content ) {
		if ( '' === $content ) {
			continue;
		}
		printf(
			'<meta property="%s" content="%s">' . "\n",
			esc_attr( $property ),
			esc_attr( $content )
		);
	}

	// Twitter usa i propri nomi, e senza card l'anteprima resta un link nudo.
	$twitter = array(
		'twitter:card'  => $image ? 'summary_large_image' : 'summary',
		'twitter:title' => $title,
	);

	if ( $description ) {
		$twitter['twitter:description'] = $description;
	}
	if ( $image ) {
		$twitter['twitter:image'] = $image['url'];
	}

	foreach ( $twitter as $name => $content ) {
		printf( '<meta name="%s" content="%s">' . "\n", esc_attr( $name ), esc_attr( $content ) );
	}
}
add_action( 'wp_head', 'gpmi_seo_meta', 4 );

/**
 * Titolo della pagina per la condivisione, senza il nome del sito in coda.
 *
 * @return string
 */
function gpmi_seo_title() {
	if ( is_front_page() ) {
		return gpmi_plain_text( get_bloginfo( 'name' ) );
	}

	if ( is_singular() ) {
		$legacy = get_post_meta( get_the_ID(), '_yoast_wpseo_title', true );

		// I titoli di Yoast possono contenere segnaposto %%...%%: se ci sono si scarta.
		if ( $legacy && false === strpos( $legacy, '%%' ) ) {
			return gpmi_plain_text( $legacy );
		}

		return gpmi_plain_text( get_the_title() );
	}

	if ( is_category() || is_tag() || is_tax() || is_author() || is_post_type_archive() ) {
		return gpmi_plain_text( wp_strip_all_tags( get_the_archive_title() ) );
	}

	if ( is_search() ) {
		/* translators: %s: termine cercato. */
		return gpmi_plain_text( sprintf( __( 'Risultati per: %s', 'gpmi' ), get_search_query() ) );
	}

	return gpmi_plain_text( get_bloginfo( 'name' ) );
}

/**
 * URL canonico della pagina corrente.
 *
 * @return string
 */
function gpmi_current_url() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? home_url( '/' ) : $link;
	}
	if ( is_author() ) {
		return get_author_posts_url( (int) get_queried_object_id() );
	}

	return home_url( add_query_arg( array(), $GLOBALS['wp']->request ? $GLOBALS['wp']->request . '/' : '/' ) );
}

/**
 * Canonical anche fuori dalle pagine singole.
 *
 * Il core lo stampa solo sui contenuti singoli: sugli archivi resterebbe
 * assente, e le pagine 2, 3, 4... di una categoria verrebbero viste come
 * duplicati senza un riferimento esplicito.
 */
function gpmi_archive_canonical() {
	if ( ! gpmi_seo_enabled() || is_singular() || is_404() || is_search() ) {
		return;
	}

	$url   = gpmi_current_url();
	$paged = (int) get_query_var( 'paged' );

	if ( $paged > 1 ) {
		$url = trailingslashit( $url ) . 'page/' . $paged . '/';
	}

	printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
}
add_action( 'wp_head', 'gpmi_archive_canonical', 5 );

/**
 * Grafo completo dei dati strutturati.
 *
 * Un unico blocco JSON-LD con i nodi collegati fra loro: la testata, il sito,
 * la pagina, il percorso di navigazione e, sugli articoli, la notizia con
 * autore e immagine. E' la struttura che i motori di ricerca e i sistemi
 * generativi si aspettano da una fonte giornalistica.
 */
function gpmi_schema_graph() {
	if ( ! gpmi_seo_enabled() ) {
		return;
	}

	$home = home_url( '/' );
	$url  = gpmi_current_url();

	$organization = gpmi_publisher_schema();
	$organization['@id'] = $home . '#organization';

	$website = array(
		'@type'           => 'WebSite',
		'@id'             => $home . '#website',
		'url'             => $home,
		'name'            => get_bloginfo( 'name' ),
		'description'     => get_bloginfo( 'description' ),
		'inLanguage'      => get_bloginfo( 'language' ),
		'publisher'       => array( '@id' => $organization['@id'] ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => $home . '?s={search_term_string}',
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	$webpage = array(
		'@type'      => 'WebPage',
		'@id'        => $url . '#webpage',
		'url'        => $url,
		'name'       => gpmi_seo_title(),
		'isPartOf'   => array( '@id' => $website['@id'] ),
		'inLanguage' => get_bloginfo( 'language' ),
	);

	$description = gpmi_meta_description();
	if ( $description ) {
		$webpage['description'] = $description;
	}

	$graph = array( $organization, $website, $webpage );

	$breadcrumb = gpmi_breadcrumb_schema( $url );
	if ( $breadcrumb ) {
		$webpage['breadcrumb'] = array( '@id' => $breadcrumb['@id'] );
		$graph[2]              = $webpage;
		$graph[]               = $breadcrumb;
	}

	if ( is_singular( 'post' ) ) {
		$graph[] = gpmi_article_schema( $url, $website['@id'], $organization['@id'] );
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => array_values( $graph ),
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		)
	);
}
add_action( 'wp_head', 'gpmi_schema_graph', 20 );

/**
 * Nodo BreadcrumbList del grafo.
 *
 * @param string $url URL corrente.
 * @return array|null
 */
function gpmi_breadcrumb_schema( $url ) {
	if ( is_front_page() ) {
		return null;
	}

	$items = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => __( 'Home', 'gpmi' ),
			'item'     => home_url( '/' ),
		),
	);

	if ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( $cats ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => $cats[0]->name,
				'item'     => get_category_link( $cats[0] ),
			);
		}
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => count( $items ) + 1,
			'name'     => gpmi_plain_text( get_the_title() ),
		);
	} elseif ( is_archive() ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => gpmi_plain_text( wp_strip_all_tags( get_the_archive_title() ) ),
		);
	} elseif ( is_singular() ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => gpmi_plain_text( get_the_title() ),
		);
	} else {
		return null;
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => $url . '#breadcrumb',
		'itemListElement' => $items,
	);
}

/**
 * Nodo NewsArticle del grafo.
 *
 * @param string $url     URL corrente.
 * @param string $site_id Identificatore del nodo WebSite.
 * @param string $org_id  Identificatore del nodo Organization.
 * @return array
 */
function gpmi_article_schema( $url, $site_id, $org_id ) {
	$post_id   = get_the_ID();
	$author_id = (int) get_post_field( 'post_author', $post_id );

	$article = array(
		'@type'               => 'NewsArticle',
		'@id'                 => $url . '#article',
		'isPartOf'            => array( '@id' => $url . '#webpage' ),
		'mainEntityOfPage'    => array( '@id' => $url . '#webpage' ),
		'headline'            => gpmi_plain_text( get_the_title(), 110 ),
		'datePublished'       => get_the_date( DATE_W3C ),
		'dateModified'        => get_the_modified_date( DATE_W3C ),
		'inLanguage'          => get_bloginfo( 'language' ),
		'isAccessibleForFree' => true,
		'wordCount'           => gpmi_word_count( $post_id ),
		'publisher'           => array( '@id' => $org_id ),
		'author'              => array(
			'@type' => 'Person',
			'@id'   => get_author_posts_url( $author_id ) . '#person',
			'name'  => get_the_author_meta( 'display_name', $author_id ),
			'url'   => get_author_posts_url( $author_id ),
		),
		'speakable'           => array(
			'@type'       => 'SpeakableSpecification',
			'cssSelector' => array( '.entry-title', '.entry-content > p:first-of-type' ),
		),
	);

	$description = gpmi_meta_description();
	if ( $description ) {
		$article['description'] = $description;
	}

	$sections = wp_get_post_categories( $post_id, array( 'fields' => 'names' ) );
	if ( $sections ) {
		$article['articleSection'] = array_values( $sections );
	}

	$tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
	if ( $tags ) {
		$article['keywords'] = array_values( $tags );
	}

	$image = gpmi_share_image();
	if ( $image ) {
		$article['image'] = array(
			'@type'  => 'ImageObject',
			'url'    => $image['url'],
			'width'  => $image['width'],
			'height' => $image['height'],
		);
	}

	$editor = gpmi_option( 'editor_name' );
	if ( $editor ) {
		$article['editor'] = array( '@type' => 'Person', 'name' => $editor );
	}

	return $article;
}
