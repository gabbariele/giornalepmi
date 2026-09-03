<?php
/**
 * Indicizzazione e visibilita' nei motori generativi.
 *
 * Tre livelli, dal piu' efficace al meno:
 *
 * 1. robots.txt con i token dei crawler realmente documentati. E' l'unico
 *    meccanismo che i gestori di LLM dichiarano di rispettare, ed e' quello
 *    che decide se il giornale puo' comparire nelle risposte generative.
 * 2. Dati strutturati NewsArticle completi, da cui i motori estraggono
 *    titolo, data, autore e testata.
 * 3. I meta tag ai-* gia' presenti sul sito e llms.txt: nessun crawler
 *    importante li consuma oggi, ma costano poco e sono la dichiarazione
 *    di intenti dell'editore.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Crawler di IA e ricerca generativa, con il consenso dichiarato dall'editore.
 *
 * true  = consentito           false = bloccato
 *
 * I nomi sono quelli pubblicati dai rispettivi gestori. Per revocare il
 * consenso a un singolo operatore basta filtrare questo elenco.
 *
 * @return array<string, bool>
 */
function gpmi_ai_crawlers() {
	return apply_filters( 'gpmi_ai_crawlers', array(
		// OpenAI: addestramento, ricerca, recupero su richiesta dell'utente.
		'GPTBot'             => true,
		'OAI-SearchBot'      => true,
		'ChatGPT-User'       => true,

		// Anthropic.
		'ClaudeBot'          => true,
		'Claude-User'        => true,
		'Claude-SearchBot'   => true,

		// Google: Gemini e AI Overviews. Distinto da Googlebot, che resta
		// sempre consentito per la ricerca tradizionale.
		'Google-Extended'    => true,

		// Perplexity.
		'PerplexityBot'      => true,
		'Perplexity-User'    => true,

		// Apple Intelligence.
		'Applebot-Extended'  => true,

		// Meta AI.
		'meta-externalagent' => true,

		// Common Crawl: alimenta molti dataset di terze parti.
		'CCBot'              => true,

		// Altri assistenti e indici.
		'Amazonbot'          => true,
		'DuckAssistBot'      => true,
		'cohere-ai'          => true,
		'YouBot'             => true,
		'Bytespider'         => true,
	) );
}

/**
 * Estende il robots.txt virtuale di WordPress.
 *
 * @param string $output robots.txt generato dal core.
 * @param bool   $public Se il sito e' visibile ai motori.
 * @return string
 */
function gpmi_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}

	$lines = array( trim( $output ), '' );

	$allowed = array();
	$blocked = array();

	foreach ( gpmi_ai_crawlers() as $agent => $consent ) {
		if ( $consent ) {
			$allowed[] = $agent;
		} else {
			$blocked[] = $agent;
		}
	}

	if ( $allowed ) {
		$lines[] = '# Crawler di IA e ricerca generativa autorizzati a indicizzare,';
		$lines[] = '# riassumere e citare i contenuti di questa testata.';
		foreach ( $allowed as $agent ) {
			$lines[] = 'User-agent: ' . $agent;
			$lines[] = 'Allow: /';
			$lines[] = 'Disallow: /wp-admin/';
			$lines[] = '';
		}
	}

	if ( $blocked ) {
		$lines[] = '# Crawler ai quali l\'editore non concede l\'uso dei contenuti.';
		foreach ( $blocked as $agent ) {
			$lines[] = 'User-agent: ' . $agent;
			$lines[] = 'Disallow: /';
			$lines[] = '';
		}
	}

	/*
	 * La sitemap e' il punto di ingresso preferito da ogni crawler, ma
	 * dichiararne una che risponde 404 e' peggio che non dichiararne nessuna.
	 * Quella di Yoast si aggiunge solo se la funzione e' davvero attiva, e solo
	 * se il core non l'ha gia' scritta: una direttiva duplicata e' un errore.
	 */
	if ( gpmi_yoast_sitemap_enabled() && false === strpos( $output, 'sitemap_index.xml' ) ) {
		$lines[] = 'Sitemap: ' . esc_url( home_url( '/sitemap_index.xml' ) );
	}

	// llms.txt non e' una sitemap: si segnala come commento, non come direttiva.
	$lines[] = '# Indice per modelli linguistici: ' . esc_url( home_url( '/llms.txt' ) );

	return implode( "\n", $lines ) . "\n";
}
add_filter( 'robots_txt', 'gpmi_robots_txt', 10, 2 );

/**
 * Indica se Yoast sta generando la propria sitemap XML.
 *
 * L'opzione puo' essere spenta, e in quel caso /sitemap_index.xml risponde 404.
 *
 * @return bool
 */
function gpmi_yoast_sitemap_enabled() {
	if ( ! class_exists( 'WPSEO_Options' ) ) {
		return false;
	}

	return (bool) WPSEO_Options::get( 'enable_xml_sitemap', false );
}

/**
 * Meta tag di dichiarazione d'uso per le IA.
 *
 * Non sono uno standard riconosciuto e nessun crawler noto li interpreta:
 * restano perche' erano gia' sul sito e perche' documentano in chiaro la
 * posizione dell'editore. Il consenso effettivo passa dal robots.txt.
 */
function gpmi_ai_meta() {
	/*
	 * Se gli stessi meta arrivano gia' da un plugin che inietta codice nel
	 * <head>, vanno tolti da una delle due parti: duplicarli non aggiunge
	 * nulla e sporca il sorgente. Con un array vuoto il tema non ne stampa
	 * nessuno e lascia fare al plugin.
	 */
	$tags = apply_filters( 'gpmi_ai_meta_tags', array(
		'ai-train'        => 'allow',
		'ai-access'       => 'index, summarize, reference',
		'ai-summary'      => 'allow',
		'ai-metadata'     => 'rich',
		'ai-purpose'      => 'knowledge',
		'ai-distribution' => 'open',
	) );

	foreach ( $tags as $name => $content ) {
		printf(
			'<meta name="%s" content="%s">' . "\n",
			esc_attr( $name ),
			esc_attr( $content )
		);
	}
}
add_action( 'wp_head', 'gpmi_ai_meta', 3 );

/**
 * Porta lo schema Yoast da Article a NewsArticle e lo completa.
 *
 * NewsArticle e' il tipo che i motori generativi usano per riconoscere una
 * fonte giornalistica: distingue una notizia da un post di blog e porta con
 * se' sezione, lingua e accessibilita' del contenuto.
 *
 * @param array $data Grafo dell'articolo costruito da Yoast.
 * @return array
 */
function gpmi_schema_news_article( $data ) {
	if ( ! is_singular( 'post' ) ) {
		return $data;
	}

	$data['@type'] = 'NewsArticle';

	$post_id = get_the_ID();

	$sections = wp_get_post_categories( $post_id, array( 'fields' => 'names' ) );
	if ( $sections ) {
		$data['articleSection'] = array_values( $sections );
	}

	$data['inLanguage']          = get_bloginfo( 'language' );
	$data['isAccessibleForFree'] = true;
	$data['wordCount']           = gpmi_word_count( $post_id );

	if ( ! empty( $data['headline'] ) ) {
		$data['headline'] = gpmi_plain_text( $data['headline'], 110 );
	}

	// Il direttore responsabile e' un dato di affidabilita' della fonte.
	$editor = gpmi_option( 'editor_name' );
	if ( $editor ) {
		$data['editor'] = array( '@type' => 'Person', 'name' => $editor );
	}

	// Indica quale parte della pagina rappresenta la notizia in forma parlata.
	$data['speakable'] = array(
		'@type'       => 'SpeakableSpecification',
		'cssSelector' => array( '.entry-title', '.entry-content > p:first-of-type' ),
	);

	return $data;
}
add_filter( 'wpseo_schema_article', 'gpmi_schema_news_article' );

/**
 * Conteggio parole di un articolo.
 *
 * @param int $post_id ID articolo.
 * @return int
 */
function gpmi_word_count( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return 0;
	}

	$text = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );

	return count( preg_split( '/[\s\p{P}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY ) );
}

/**
 * Normalizza una stringa per i dati strutturati.
 *
 * I valori JSON-LD devono essere testo semplice: le entita' HTML che
 * WordPress inserisce nei titoli (apostrofi tipografici, puntini di
 * sospensione) vanno decodificate, non lasciate come &#8217;.
 *
 * @param string $text  Testo di partenza.
 * @param int    $limit Lunghezza massima in caratteri, 0 per nessun limite.
 * @return string
 */
function gpmi_plain_text( $text, $limit = 0 ) {
	$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = trim( preg_replace( '/\s+/u', ' ', $text ) );

	if ( $limit && mb_strlen( $text ) > $limit ) {
		$text = rtrim( mb_substr( $text, 0, $limit - 1 ) ) . '...';
	}

	return $text;
}


/**
 * Registra la rotta /llms.txt.
 *
 * Convenzione emergente: un indice in Markdown pensato per essere letto da un
 * modello linguistico. Nessun crawler importante lo consuma ancora, ma e' un
 * file statico da qualche kilobyte e definisce in chiaro cosa e' il giornale.
 */
function gpmi_llms_rewrite() {
	add_rewrite_rule( '^llms\.txt$', 'index.php?gpmi_llms=1', 'top' );
}
add_action( 'init', 'gpmi_llms_rewrite' );

/**
 * Registra la query var di llms.txt.
 *
 * @param array $vars Query var registrate.
 * @return array
 */
function gpmi_llms_query_var( $vars ) {
	$vars[] = 'gpmi_llms';
	return $vars;
}
add_filter( 'query_vars', 'gpmi_llms_query_var' );

/**
 * Genera e serve llms.txt.
 */
function gpmi_llms_output() {
	if ( ! get_query_var( 'gpmi_llms' ) ) {
		return;
	}

	$cached = get_transient( 'gpmi_llms_txt' );

	if ( false === $cached ) {
		$lines = array();

		$lines[] = '# ' . get_bloginfo( 'name' );
		$lines[] = '';
		$lines[] = '> ' . get_bloginfo( 'description' );
		$lines[] = '';
		$lines[] = 'Testata giornalistica italiana dedicata alle piccole e medie imprese: '
			. 'fisco, finanziamenti, bandi, export, lavoro, tecnologia e innovazione. '
			. 'I contenuti possono essere indicizzati, riassunti e citati indicando la fonte.';
		$lines[] = '';

		$lines[] = '## Sezioni';
		$lines[] = '';

		$categories = get_categories( array(
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 20,
			'hide_empty' => true,
		) );

		foreach ( $categories as $cat ) {
			$lines[] = sprintf(
				'- [%s](%s): %d articoli%s',
				$cat->name,
				get_category_link( $cat ),
				$cat->count,
				$cat->description ? ' — ' . wp_strip_all_tags( $cat->description ) : ''
			);
		}

		$lines[] = '';
		$lines[] = '## Articoli recenti';
		$lines[] = '';

		$recent = get_posts( array(
			'numberposts'            => 40,
			'post_status'            => 'publish',
			'update_post_meta_cache' => false,
		) );

		foreach ( $recent as $post ) {
			$lines[] = sprintf(
				'- [%s](%s) — %s',
				wp_strip_all_tags( get_the_title( $post ) ),
				get_permalink( $post ),
				get_the_date( 'Y-m-d', $post )
			);
		}

		$lines[] = '';
		$lines[] = '## Archivio completo';
		$lines[] = '';
		$lines[] = '- [Sitemap XML](' . home_url( function_exists( 'YoastSEO' ) ? '/sitemap_index.xml' : '/wp-sitemap.xml' ) . ')';
		$lines[] = '- [Feed RSS](' . get_feed_link() . ')';
		$lines[] = '';

		$cached = implode( "\n", $lines );
		set_transient( 'gpmi_llms_txt', $cached, HOUR_IN_SECONDS );
	}

	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Cache-Control: public, max-age=3600' );
	echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- testo semplice gia' ripulito.
	exit;
}
add_action( 'template_redirect', 'gpmi_llms_output', 0 );

/**
 * Impedisce a WordPress di redirigere /llms.txt su /llms.txt/.
 *
 * @param string|false $redirect_url URL di destinazione calcolato dal core.
 * @return string|false
 */
function gpmi_no_canonical_for_llms( $redirect_url ) {
	return get_query_var( 'gpmi_llms' ) ? false : $redirect_url;
}
add_filter( 'redirect_canonical', 'gpmi_no_canonical_for_llms' );

/**
 * Svuota la cache di llms.txt quando cambia un contenuto.
 */
function gpmi_flush_llms_cache() {
	delete_transient( 'gpmi_llms_txt' );
}
add_action( 'save_post_post', 'gpmi_flush_llms_cache' );
add_action( 'deleted_post', 'gpmi_flush_llms_cache' );

/**
 * Aggiunge le regole di rewrite al primo caricamento del tema.
 */
function gpmi_flush_rewrites() {
	gpmi_llms_rewrite();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'gpmi_flush_rewrites' );

/**
 * Dati dell'editore per i dati strutturati.
 *
 * NewsMediaOrganization con editore e sede reali: e' il blocco da cui i motori
 * di ricerca e i sistemi generativi ricavano di chi e' la testata.
 *
 * @return array
 */
function gpmi_publisher_schema() {
	$publisher = array(
		'@type' => 'NewsMediaOrganization',
		'name'  => gpmi_option( 'publisher_name' ) ? gpmi_option( 'publisher_name' ) : get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);

	$city = gpmi_option( 'publisher_city' );
	if ( $city ) {
		$publisher['address'] = array(
			'@type'           => 'PostalAddress',
			'addressLocality' => $city,
			'addressCountry'  => 'IT',
		);
	}

	if ( has_custom_logo() ) {
		$logo = wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' );
		if ( $logo ) {
			$publisher['logo'] = array( '@type' => 'ImageObject', 'url' => $logo );
		}
	}

	return $publisher;
}
