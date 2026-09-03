<?php
/**
 * Genera le varianti WebP e AVIF della media library.
 *
 * Il tema serve automaticamente questi file tramite <picture> quando li trova
 * accanto all'originale: non modifica ne' cancella nulla di esistente, quindi
 * si puo' interrompere e riprendere in qualsiasi momento.
 *
 * Uso, dalla cartella che contiene wp-load.php:
 *
 *   php bin/convert-images.php                    prova a vuoto, non scrive
 *   php bin/convert-images.php --apply            converte
 *   php bin/convert-images.php --apply --avif     converte anche in AVIF
 *   php bin/convert-images.php --apply --limit=500
 *
 * AVIF comprime meglio di WebP ma richiede molta CPU: sulle librerie grandi
 * conviene lanciarlo di notte, o in piu' passaggi con --limit.
 *
 * @package GPMI
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( "Eseguire da riga di comando.\n" );
}

$options = getopt( '', array( 'apply', 'avif', 'limit::', 'quality::', 'path::' ) );

$apply   = isset( $options['apply'] );
$do_avif = isset( $options['avif'] );
$limit   = isset( $options['limit'] ) ? (int) $options['limit'] : 0;
$quality = isset( $options['quality'] ) ? (int) $options['quality'] : 80;

// Individua la cartella uploads: da WordPress se disponibile, altrimenti --path.
$base = null;

foreach ( array( 'wp-load.php', '../wp-load.php', '../../wp-load.php', '../../../../wp-load.php' ) as $candidate ) {
	if ( file_exists( __DIR__ . '/' . $candidate ) ) {
		require_once __DIR__ . '/' . $candidate;
		$dir  = wp_get_upload_dir();
		$base = $dir['basedir'];
		break;
	}
}

if ( isset( $options['path'] ) ) {
	$base = rtrim( $options['path'], "/\\" );
}

if ( ! $base || ! is_dir( $base ) ) {
	exit( "Cartella uploads non trovata. Passare --path=/percorso/wp-content/uploads\n" );
}

$info = gd_info();
if ( empty( $info['WebP Support'] ) ) {
	exit( "GD e' compilato senza supporto WebP: installare php-gd con WebP oppure usare cwebp.\n" );
}
if ( $do_avif && empty( $info['AVIF Support'] ) ) {
	echo "Attenzione: GD non supporta AVIF, verranno generati solo i WebP.\n";
	$do_avif = false;
}

echo "Cartella: {$base}\n";
echo $apply ? "Modalita': scrittura\n" : "Modalita': prova a vuoto (aggiungere --apply per scrivere)\n";

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS )
);

$stats = array(
	'esaminati' => 0,
	'convertiti' => 0,
	'saltati'   => 0,
	'errori'    => 0,
	'byte_originali' => 0,
	'byte_nuovi'     => 0,
);

foreach ( $iterator as $file ) {
	if ( ! $file->isFile() ) {
		continue;
	}

	$path = $file->getPathname();
	$ext  = strtolower( $file->getExtension() );

	if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png' ), true ) ) {
		continue;
	}

	$stats['esaminati']++;

	if ( $limit && $stats['convertiti'] >= $limit ) {
		break;
	}

	$targets = array( 'webp' => $quality );
	if ( $do_avif ) {
		// AVIF regge una qualita' piu' bassa a parita' di resa percepita.
		$targets['avif'] = max( 30, $quality - 20 );
	}

	foreach ( $targets as $format => $q ) {
		$out = preg_replace( '/\.(jpe?g|png)$/i', '.' . $format, $path );

		if ( file_exists( $out ) ) {
			$stats['saltati']++;
			continue;
		}

		if ( ! $apply ) {
			$stats['convertiti']++;
			continue;
		}

		$image = ( 'png' === $ext ) ? @imagecreatefrompng( $path ) : @imagecreatefromjpeg( $path );

		if ( ! $image ) {
			$stats['errori']++;
			echo "  errore in lettura: {$path}\n";
			continue;
		}

		if ( 'png' === $ext ) {
			imagepalettetotruecolor( $image );
			imagealphablending( $image, true );
			imagesavealpha( $image, true );
		}

		$ok = ( 'avif' === $format )
			? @imageavif( $image, $out, $q )
			: @imagewebp( $image, $out, $q );

		imagedestroy( $image );

		if ( ! $ok ) {
			$stats['errori']++;
			echo "  errore in scrittura: {$out}\n";
			continue;
		}

		// Se la variante non e' piu' leggera dell'originale non ha senso tenerla.
		if ( filesize( $out ) >= filesize( $path ) ) {
			unlink( $out );
			$stats['saltati']++;
			continue;
		}

		$stats['convertiti']++;
		$stats['byte_originali'] += filesize( $path );
		$stats['byte_nuovi']     += filesize( $out );
	}

	if ( 0 === $stats['esaminati'] % 200 ) {
		echo "  ... {$stats['esaminati']} file esaminati\n";
	}
}

$risparmio = $stats['byte_originali'] - $stats['byte_nuovi'];

echo "\nFile esaminati:  {$stats['esaminati']}\n";
echo "Varianti create: {$stats['convertiti']}\n";
echo "Gia' presenti:   {$stats['saltati']}\n";
echo "Errori:          {$stats['errori']}\n";

if ( $stats['byte_originali'] > 0 ) {
	printf(
		"Risparmio:       %.1f MB su %.1f MB (%.0f%%)\n",
		$risparmio / 1048576,
		$stats['byte_originali'] / 1048576,
		100 * $risparmio / $stats['byte_originali']
	);
}

if ( ! $apply ) {
	echo "\nNessun file scritto. Rilanciare con --apply per convertire davvero.\n";
}
