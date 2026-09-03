<?php
/**
 * "Aggiungi come fonte preferita su Google", integrato nel tema.
 *
 * Sostituisce il plugin add-as-preferred-source, che per aprire un singolo
 * link caricava un CSS, 2,7 KB di JavaScript, un banner a posizione fissa e
 * una chiamata AJAX di tracciamento a ogni impression.
 *
 * Qui la funzione e' un normale <a href>: funziona anche senza JavaScript,
 * non sposta il layout e compare dove l'intenzione del lettore e' piu' alta,
 * cioe' in fondo a un articolo appena letto.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL della preferenza di fonte su Google.
 *
 * @return string
 */
function gpmi_preferred_source_url() {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	$host = preg_replace( '/^www\./', '', (string) $host );

	return 'https://www.google.com/preferences/source?q=' . rawurlencode( $host );
}

/**
 * Logo Google in SVG, nei colori ufficiali.
 *
 * @param int $size Lato in pixel.
 * @return string
 */
function gpmi_google_mark( $size = 18 ) {
	return sprintf(
		'<svg class="g-mark" width="%1$d" height="%1$d" viewBox="0 0 48 48" aria-hidden="true" focusable="false">'
		. '<path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.6l6.7-6.7C35.6 2.6 30.2 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.8 6.1C12.3 13.3 17.6 9.5 24 9.5z"/>'
		. '<path fill="#4285F4" d="M46.1 24.6c0-1.6-.1-3.1-.4-4.6H24v9.1h12.4c-.5 2.9-2.2 5.3-4.7 7l7.6 5.9c4.4-4.1 6.8-10.1 6.8-17.4z"/>'
		. '<path fill="#FBBC05" d="M10.4 28.7c-.5-1.5-.8-3-.8-4.7s.3-3.2.8-4.7l-7.8-6.1C.9 16.3 0 20 0 24s.9 7.7 2.6 10.8l7.8-6.1z"/>'
		. '<path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.6-5.9c-2.1 1.4-4.9 2.3-8.3 2.3-6.4 0-11.7-3.8-13.6-9.8l-7.8 6.1C6.5 42.6 14.6 48 24 48z"/>'
		. '</svg>',
		(int) $size
	);
}

/**
 * Scheda di invito, mostrata in fondo agli articoli.
 *
 * Il markup e' completo lato server; il JavaScript si limita a nasconderla se
 * il lettore l'ha gia' chiusa e a metterla in evidenza per chi arriva da
 * Google, cioe' le uniche persone per cui la funzione ha davvero effetto.
 */
function gpmi_preferred_source_card() {
	if ( ! apply_filters( 'gpmi_show_preferred_source', true ) ) {
		return;
	}
	?>
	<aside class="prefsource" data-prefsource hidden>
		<div class="prefsource-body">
			<p class="prefsource-title">
				<?php esc_html_e( 'Ti fidi di quello che hai appena letto?', 'gpmi' ); ?>
			</p>
			<p class="prefsource-text">
				<?php
				printf(
					/* translators: %s: nome della testata. */
					esc_html__( 'Imposta %s come fonte preferita: le nostre notizie compariranno piu\' in alto nei tuoi risultati su Google.', 'gpmi' ),
					'<strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong>'
				);
				?>
			</p>
		</div>

		<a
			class="prefsource-btn"
			href="<?php echo esc_url( gpmi_preferred_source_url() ); ?>"
			target="_blank"
			rel="noopener nofollow"
			data-prefsource-action
		>
			<?php echo gpmi_google_mark( 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'Aggiungi a Google', 'gpmi' ); ?></span>
		</a>

		<button type="button" class="prefsource-close" data-prefsource-close>
			<?php echo gpmi_icon( 'close', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Non mostrare piu\' questo invito', 'gpmi' ); ?></span>
		</button>
	</aside>
	<?php
}

/**
 * Link compatto sempre disponibile nella barra superiore.
 *
 * Chi ha chiuso la scheda continua a poter attivare la preferenza da qui,
 * senza che nessuno glielo riproponga.
 */
function gpmi_preferred_source_link() {
	if ( ! apply_filters( 'gpmi_show_preferred_source', true ) ) {
		return;
	}
	?>
	<a
		class="topbar-prefsource"
		href="<?php echo esc_url( gpmi_preferred_source_url() ); ?>"
		target="_blank"
		rel="noopener nofollow"
		data-prefsource-action
	>
		<?php echo gpmi_google_mark( 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span><?php esc_html_e( 'Segui su Google', 'gpmi' ); ?></span>
	</a>
	<?php
}
