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

$options = getopt( '', array( 'apply', 'path::' ) );
$apply   = isset( $options['apply'] );

$loaded = false;

foreach ( array( 'wp-load.php', '../wp-load.php', '../../wp-load.php' ) as $candidate ) {
	if ( file_exists( __DIR__ . '/' . $candidate ) ) {
		require_once __DIR__ . '/' . $candidate;
		$loaded = true;
		break;
	}
}

if ( ! $loaded ) {
	exit( "wp-load.php non trovato: eseguire lo script dentro l'installazione WordPress.\n" );
}

$target = isset( $options['path'] ) ? $options['path'] : ABSPATH . 'robots.txt';

// do_robots() stampa direttamente: si cattura l'output.
ob_start();
do_robots();
$content = ob_get_clean();

// do_robots() manda anche gli header: qui non servono, l'output e' un file.
$content = trim( $content ) . "\n";

$agents = substr_count( $content, 'User-agent:' );

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
