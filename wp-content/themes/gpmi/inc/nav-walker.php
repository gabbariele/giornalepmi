<?php
/**
 * Walker del menu principale.
 *
 * Aggiunge un pulsante di apertura ai sottomenu, cosi' che funzionino da
 * tastiera e su touch senza dipendere da :hover.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Walker con toggle accessibili per i sottomenu.
 */
class GPMI_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Apre un livello di sottomenu.
	 *
	 * @param string   $output Markup accumulato.
	 * @param int      $depth  Profondita' corrente.
	 * @param stdClass $args   Argomenti del menu.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$indent  = str_repeat( "\t", $depth );
		$output .= "\n{$indent}<ul class=\"sub-menu depth-{$depth}\">\n";
	}

	/**
	 * Apre una voce di menu.
	 *
	 * @param string   $output Markup accumulato.
	 * @param WP_Post  $item   Voce di menu.
	 * @param int      $depth  Profondita' corrente.
	 * @param stdClass $args   Argomenti del menu.
	 * @param int      $id     ID elemento.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$has_children = in_array( 'menu-item-has-children', $classes, true );
		if ( $has_children ) {
			$classes[] = 'has-dropdown';
		}

		$class_names = implode( ' ', array_filter( array_map( 'sanitize_html_class', $classes ) ) );

		$atts = array(
			'href'   => $item->url ? $item->url : '',
			'target' => $item->target ? $item->target : '',
			'rel'    => $item->xfn ? $item->xfn : '',
		);

		$attributes = '';
		foreach ( array_filter( $atts ) as $key => $value ) {
			$attributes .= sprintf( ' %s="%s"', $key, 'href' === $key ? esc_url( $value ) : esc_attr( $value ) );
		}

		/*
		 * La descrizione della voce di menu non viene stampata dentro il link:
		 * sul tema precedente finiva concatenata al titolo, rendendo il nome
		 * accessibile della voce una frase intera ("INFOIMPRESAInformazioni
		 * sulle Imprese in Italia, Finanziamenti per le Imprese").
		 */
		$title = apply_filters( 'the_title', $item->title, $item->ID );

		$link = sprintf( '<a%s>%s</a>', $attributes, esc_html( $title ) );

		if ( $has_children && 0 === $depth ) {
			$link .= sprintf(
				'<button class="submenu-toggle" type="button" aria-expanded="false"><span class="screen-reader-text">%s</span>%s</button>',
				/* translators: %s: nome della voce di menu. */
				esc_html( sprintf( __( 'Apri il sottomenu di %s', 'gpmi' ), $title ) ),
				gpmi_icon( 'chevron-right', 14 )
			);
		}

		$output .= sprintf(
			'<li id="menu-item-%d" class="%s">%s',
			(int) $item->ID,
			esc_attr( $class_names ),
			$link
		);
	}

	/**
	 * Chiude una voce di menu.
	 *
	 * @param string   $output Markup accumulato.
	 * @param WP_Post  $item   Voce di menu.
	 * @param int      $depth  Profondita'.
	 * @param stdClass $args   Argomenti.
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= "</li>\n";
	}
}

/**
 * Menu di ripiego quando nessun menu e' assegnato alla posizione principale.
 *
 * Nell'ordine: si riusa il menu del footer, che sul giornale contiene gia' le
 * sezioni; se manca anche quello, si elencano le categorie con piu' articoli.
 * Cosi' la barra di navigazione non resta mai vuota, nemmeno subito dopo un
 * cambio di tema, quando WordPress azzera le posizioni dei menu.
 */
function gpmi_menu_fallback() {
	if ( has_nav_menu( 'footer' ) ) {
		wp_nav_menu( array(
			'theme_location' => 'footer',
			'menu_id'        => 'primary-menu',
			'menu_class'     => 'primary-menu',
			'container'      => false,
			'walker'         => new GPMI_Nav_Walker(),
			'fallback_cb'    => 'gpmi_category_menu',
		) );
		return;
	}

	gpmi_category_menu();
}

/**
 * Elenco delle categorie con piu' articoli, ultimo ripiego per la navigazione.
 */
function gpmi_category_menu() {
	$categories = get_categories( array(
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => (int) apply_filters( 'gpmi_fallback_menu_count', 8 ),
		'hide_empty' => true,
	) );

	if ( ! $categories ) {
		return;
	}

	$current = is_category() ? (int) get_queried_object_id() : 0;

	echo '<ul id="primary-menu" class="primary-menu">';

	foreach ( $categories as $cat ) {
		printf(
			'<li class="menu-item%1$s"><a href="%2$s">%3$s</a></li>',
			$cat->term_id === $current ? ' current-menu-item' : '',
			esc_url( get_category_link( $cat ) ),
			esc_html( $cat->name )
		);
	}

	echo '</ul>';
}
