<?php
/**
 * Scrive su disco il robots.txt generato da WordPress.
 *
 * Serve sui server WordOps, dove nginx intercetta /robots.txt con un blocco
 * dedicato:
 *
 *     location = /robots.txt {
 *         try_files $uri $uri/ /index.php?$args @robots;
 *     }
 *     location @robots {
 *         return 200 "User-agent: *\nDisallow: /wp-admin/\n...";
 *     }
 *
 * Non trovando un file vero, nginx serve il proprio fallback e WordPress non
 * viene mai chiamato: nessuna direttiva del tema arriva ai crawler. Il primo
 * parametro di try_files e' pero' $uri, quindi basta che il file esista.
 *
 * Questo script prende cio' che WordPress genererebbe (filtri del tema
 * compresi) e lo scrive in un file, cosi' la configurazione resta una sola,
 * dentro il tema. Va rilanciato dopo ogni modifica all'elenco dei crawler.
 *
 * Uso, dalla cartella dell'installazione:
 *
 *   php bin/write-robots.php            mostra il contenuto, non scrive
 *   php bin/write-robots.php --apply    scrive htdocs/robots.txt
 *
 * @package GPMI
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( "Eseguire da riga di comando.\n" );
}

$options = getopt( '', array( 'apply', 'path::', 'wp::' ) );
$apply   = isset( $options['apply'] );

/*
 * Individuazione dell'installazione su cui agire.
 *
 * Su un server con piu' siti, sbagliare cartella significherebbe scrivere il
 * robots.txt del sito sbagliato. Si cerca quindi in ordine dichiarato, si
 * pretende anche wp-config.php (una docroot senza non e' un'installazione), e
 * prima di scrivere si stampa sempre di quale sito si tratta.
 */
$roots = array();

if ( isset( $options['wp'] ) ) {
	$roots[] = rtrim( $options['wp'], "/\\" );
}

// Cartella corrente e livelli superiori: il caso normale e' lanciare lo
// script stando dentro la docroot del sito.
$dir = getcwd();
for ( $i = 0; $i < 4 && $dir; $i++ ) {
	$roots[] = $dir;
	$parent  = dirname( $dir );
	if ( $parent === $dir ) {
		break;
	}
	$dir = $parent;
}

// Infine il caso in cui lo script viva dentro l'installazione stessa.
$dir = __DIR__;
for ( $i = 0; $i < 4 && $dir; $i++ ) {
	$roots[] = $dir;
	$parent  = dirname( $dir );
	if ( $parent === $dir ) {
		break;
	}
	$dir = $parent;
}

$loaded = false;

foreach ( array_unique( $roots ) as $root ) {
	if ( file_exists( $root . '/wp-load.php' ) && file_exists( $root . '/wp-config.php' ) ) {
		require_once $root . '/wp-load.php';
		$loaded = true;
		break;
	}
}

if ( ! $loaded ) {
	echo "Installazione WordPress non trovata.\n";
	echo "Lanciare lo script dalla docroot del sito, oppure indicarla:\n";
	echo "  php write-robots.php --wp=/var/www/esempio.it/htdocs --apply\n";
	exit( 1 );
}

$target = isset( $options['path'] ) ? $options['path'] : ABSPATH . 'robots.txt';

// do_robots() stampa direttamente: si cattura l'output.
ob_start();
do_robots();
$content = ob_get_clean();

// do_robots() manda anche gli header: qui non servono, l'output e' un file.
$content = trim( $content ) . "\n";

$agents = substr_count( $content, 'User-agent:' );

// Si stampa sempre quale sito e' stato caricato: su un server condiviso e' la
// verifica che impedisce di scrivere nella docroot sbagliata.
echo "Sito:         " . get_bloginfo( 'name' ) . ' (' . home_url( '/' ) . ")\n";
echo "Destinazione: {$target}\n";
echo "Direttive User-agent generate: {$agents}\n\n";

if ( ! $apply ) {
	echo $content;
	echo "\n---\nNessun file scritto. Rilanciare con --apply per scrivere davvero.\n";
	exit( 0 );
}

if ( file_exists( $target ) ) {
	$backup = $target . '.' . gmdate( 'Ymd-His' ) . '.bak';
	copy( $target, $backup );
	echo "Copia del file precedente: " . basename( $backup ) . "\n";
}

if ( false === file_put_contents( $target, $content ) ) {
	exit( "Scrittura fallita: controllare i permessi su " . dirname( $target ) . "\n" );
}

chmod( $target, 0644 );

printf( "Scritti %d byte.\n", strlen( $content ) );
echo "\nVerifica (la cache CDN puo' trattenere la versione vecchia per ore):\n";
echo "  curl -s " . home_url( '/robots.txt' ) . " | grep -c '^User-agent:'\n";
echo "Deve rispondere {$agents}.\n";
