<?php
/**
 * Opzioni del tema nel Customizer.
 *
 * Poche opzioni, tutte con un valore predefinito sensato: le impostazioni del
 * tema non devono diventare il posto in cui si costruisce il sito.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Valori predefiniti delle opzioni del tema.
 *
 * @return array
 */
function gpmi_option_defaults() {
	return array(
		'accent_color'      => '#ef4444',
		'category_color'    => '#1b8415',
		'link_color'        => '#4169e1',
		'tagline_visible'   => true,
		'topbar_date'       => true,
		'ticker_label'      => __( 'FLASH', 'gpmi' ),
		'ticker_enabled'    => true,
		'footer_text'       => '',
		'publisher_name'    => 'Associazione Il volo del Calabrone',
		'publisher_city'    => 'Pavia',
		'editor_name'       => 'Dario Vascellaro',
		'registration'      => '',
		'posts_columns'     => 3,
		'author_box'        => true,
		'disable_comments'      => false,
		'hide_existing_comments' => false,
	);
}

/**
 * Legge un'opzione del tema.
 *
 * @param string $key     Chiave.
 * @param mixed  $default Valore di ripiego.
 * @return mixed
 */
function gpmi_option( $key, $default = null ) {
	$defaults = gpmi_option_defaults();
	$fallback = array_key_exists( $key, $defaults ) ? $defaults[ $key ] : $default;

	return get_theme_mod( 'gpmi_' . $key, $fallback );
}

/**
 * Registra i controlli del Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Manager del Customizer.
 */
function gpmi_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'gpmi_identity', array(
		'title'    => __( 'Aspetto del giornale', 'gpmi' ),
		'priority' => 25,
	) );

	$colors = array(
		'accent_color'   => __( 'Colore principale (barre, pulsanti, FLASH)', 'gpmi' ),
		'category_color' => __( 'Colore dei badge di categoria', 'gpmi' ),
		'link_color'     => __( 'Colore dei link nel testo', 'gpmi' ),
	);

	foreach ( $colors as $key => $label ) {
		$wp_customize->add_setting( 'gpmi_' . $key, array(
			'default'           => gpmi_option_defaults()[ $key ],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'gpmi_' . $key, array(
			'label'   => $label,
			'section' => 'gpmi_identity',
		) ) );
	}

	$wp_customize->add_setting( 'gpmi_tagline_visible', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'gpmi_tagline_visible', array(
		'label'   => __( 'Mostra il sottotitolo sotto la testata', 'gpmi' ),
		'section' => 'gpmi_identity',
		'type'    => 'checkbox',
	) );

	$wp_customize->add_setting( 'gpmi_topbar_date', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'gpmi_topbar_date', array(
		'label'   => __( 'Mostra la data nella barra superiore', 'gpmi' ),
		'section' => 'gpmi_identity',
		'type'    => 'checkbox',
	) );

	// Sezione ticker.
	$wp_customize->add_section( 'gpmi_ticker', array(
		'title'    => __( 'Ticker delle ultime notizie', 'gpmi' ),
		'priority' => 26,
	) );

	$wp_customize->add_setting( 'gpmi_ticker_enabled', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'gpmi_ticker_enabled', array(
		'label'   => __( 'Attiva il ticker', 'gpmi' ),
		'section' => 'gpmi_ticker',
		'type'    => 'checkbox',
	) );

	$wp_customize->add_setting( 'gpmi_ticker_label', array(
		'default'           => 'FLASH',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'gpmi_ticker_label', array(
		'label'   => __( 'Etichetta del ticker', 'gpmi' ),
		'section' => 'gpmi_ticker',
		'type'    => 'text',
	) );

	// Sezione homepage.
	$wp_customize->add_section( 'gpmi_home', array(
		'title'    => __( 'Homepage', 'gpmi' ),
		'priority' => 27,
	) );

	$wp_customize->add_setting( 'gpmi_posts_columns', array(
		'default'           => 3,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'gpmi_posts_columns', array(
		'label'   => __( 'Colonne della griglia articoli', 'gpmi' ),
		'section' => 'gpmi_home',
		'type'    => 'select',
		'choices' => array( 2 => '2', 3 => '3', 4 => '4' ),
	) );

	$wp_customize->add_setting( 'gpmi_author_box', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'gpmi_author_box', array(
		'label'       => __( 'Mostra il riquadro autore in fondo agli articoli', 'gpmi' ),
		'description' => __( 'Disattivalo se un plugin ne aggiunge gia uno, per non vederli doppi.', 'gpmi' ),
		'section'     => 'gpmi_home',
		'type'        => 'checkbox',
	) );

	$wp_customize->add_setting( 'gpmi_disable_comments', array(
		'default'           => false,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'gpmi_disable_comments', array(
		'label'       => __( 'Chiudi i commenti su tutto il sito', 'gpmi' ),
		'description' => __( 'Impedisce nuovi commenti ovunque. Quelli gia pubblicati restano leggibili.', 'gpmi' ),
		'section'     => 'gpmi_home',
		'type'        => 'checkbox',
	) );

	$wp_customize->add_setting( 'gpmi_hide_existing_comments', array(
		'default'           => false,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'gpmi_hide_existing_comments', array(
		'label'       => __( 'Nascondi anche i commenti gia pubblicati', 'gpmi' ),
		'description' => __( 'Solo se i commenti sono chiusi. I dati restano nel database e ricompaiono togliendo la spunta.', 'gpmi' ),
		'section'     => 'gpmi_home',
		'type'        => 'checkbox',
	) );

	// Dati legali della testata: compaiono nel footer e alimentano lo schema editore.
	$wp_customize->add_section( 'gpmi_masthead', array(
		'title'       => __( 'Dati legali della testata', 'gpmi' ),
		'description' => __( 'Compaiono nel footer sotto il nome del giornale e nei dati strutturati letti dai motori di ricerca.', 'gpmi' ),
		'priority'    => 28,
	) );

	$legal = array(
		'publisher_name' => __( 'Editore', 'gpmi' ),
		'publisher_city' => __( 'Sede', 'gpmi' ),
		'editor_name'    => __( 'Direttore responsabile', 'gpmi' ),
		'registration'   => __( 'Registrazione al Tribunale', 'gpmi' ),
	);

	foreach ( $legal as $key => $label ) {
		$wp_customize->add_setting( 'gpmi_' . $key, array(
			'default'           => gpmi_option_defaults()[ $key ],
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( 'gpmi_' . $key, array(
			'label'   => $label,
			'section' => 'gpmi_masthead',
			'type'    => 'text',
		) );
	}

	$wp_customize->add_setting( 'gpmi_footer_text', array(
		'default'           => '',
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'gpmi_footer_text', array(
		'label'   => __( 'Testo del copyright nel footer', 'gpmi' ),
		'section' => 'gpmi_home',
		'type'    => 'textarea',
	) );

	// Anteprima live dei colori.
	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
		$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';
	}
}
add_action( 'customize_register', 'gpmi_customize_register' );

/**
 * Stampa le variabili CSS derivate dalle opzioni.
 *
 * Sono poche righe inline: evitano un secondo foglio di stile e permettono di
 * cambiare i colori senza rigenerare il CSS.
 */
function gpmi_custom_properties() {
	$vars = array(
		'--gp-accent'   => gpmi_option( 'accent_color' ),
		'--gp-category' => gpmi_option( 'category_color' ),
		'--gp-link'     => gpmi_option( 'link_color' ),
	);

	$css = '';
	foreach ( $vars as $name => $value ) {
		if ( ! $value ) {
			continue;
		}
		$css .= $name . ':' . esc_attr( $value ) . ';';
	}

	if ( ! $css ) {
		return;
	}

	printf( '<style id="gpmi-vars">:root{%s}</style>' . "\n", $css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- valori gia' validati come colori esadecimali.
}
add_action( 'wp_head', 'gpmi_custom_properties', 5 );

/**
 * Script di anteprima live del Customizer.
 */
function gpmi_customize_preview_js() {
	wp_enqueue_script(
		'gpmi-customizer',
		GPMI_URI . '/assets/js/customizer.js',
		array( 'customize-preview' ),
		gpmi_asset_version( 'assets/js/customizer.js' ),
		true
	);
}
add_action( 'customize_preview_init', 'gpmi_customize_preview_js' );
