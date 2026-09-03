<?php
/**
 * Scarica i font Roboto in locale, nel sottoinsieme latino.
 *
 * Servire i font dal proprio dominio evita una connessione a fonts.gstatic.com
 * (handshake DNS + TLS prima ancora di iniziare il download) ed elimina il
 * trasferimento dell'indirizzo IP dei lettori verso Google, che in Italia e'
 * un problema di base giuridica del trattamento.
 *
 * Uso:
 *   php bin/fetch-fonts.php
 *
 * Il file finisce in wp-content/themes/gpmi/assets/fonts/. Se la cartella
 * resta vuota il tema usa lo stack di sistema e non emette nessuna @font-face.
 *
 * @package GPMI
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( "Eseguire da riga di comando.\n" );
}

$dest = dirname( __DIR__ ) . '/wp-content/themes/gpmi/assets/fonts';

// Se lo script viene copiato dentro l'installazione WordPress, il tema e' altrove.
if ( ! is_dir( $dest ) ) {
	$alt = dirname( __DIR__ ) . '/assets/fonts';
	if ( is_dir( $alt ) ) {
		$dest = $alt;
	}
}

if ( ! is_dir( $dest ) && ! mkdir( $dest, 0755, true ) && ! is_dir( $dest ) ) {
	exit( "Impossibile creare {$dest}\n" );
}

/*
 * Roboto su Google Fonts e' un variable font: un unico file copre tutti i pesi
 * da 100 a 900. Scaricare "400" e "700" separatamente restituirebbe due volte
 * gli stessi identici byte, e il browser farebbe due richieste per nulla.
 */
$context = stream_context_create( array(
	'http' => array(
		// Un User-Agent moderno fa restituire a Google le sorgenti woff2.
		'header'  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36\r\n",
		'timeout' => 20,
	),
) );

$url = 'https://fonts.googleapis.com/css2?family=Roboto:wght@100..900&display=swap&subset=latin';
$css = @file_get_contents( $url, false, $context );

if ( ! $css ) {
	exit( "Impossibile contattare fonts.googleapis.com.\nScaricare Roboto (variabile, sottoinsieme latino) e salvarlo come roboto-latin-var.woff2 in:\n{$dest}\n" );
}

/*
 * Il CSS contiene un blocco @font-face per ogni sottoinsieme (latin,
 * latin-ext, cyrillic...). Serve solo "latin": gli altri peserebbero senza
 * essere mai usati da un giornale in italiano.
 */
preg_match_all( '/\/\*\s*([a-z-]+)\s*\*\/\s*@font-face\s*\{([^}]+)\}/i', $css, $blocks, PREG_SET_ORDER );

$saved = false;

foreach ( $blocks as $block ) {
	if ( 'latin' !== $block[1] ) {
		continue;
	}

	if ( ! preg_match( '/src:\s*url\(([^)]+)\)\s*format\(\'woff2\'\)/', $block[2], $u ) ) {
		continue;
	}

	$font = @file_get_contents( trim( $u[1], "'\" " ), false, $context );

	if ( ! $font ) {
		echo "  scaricamento fallito\n";
		break;
	}

	$file = $dest . '/roboto-latin-var.woff2';
	file_put_contents( $file, $font );

	printf( "  %s  %.1f KB (pesi 100-900 in un solo file)\n", basename( $file ), strlen( $font ) / 1024 );
	$saved = true;
	break;
}

if ( $saved ) {
	echo "\nFont salvato in {$dest}\n";
	echo "Il tema lo rilevera' da solo al prossimo caricamento.\n";
} else {
	echo "\nNessun font salvato: controllare la connessione o scaricarlo a mano.\n";
}
