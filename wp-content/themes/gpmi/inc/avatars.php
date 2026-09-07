<?php
/**
 * Foto degli autori caricate sul sito.
 *
 * WordPress da solo non permette di caricare una foto profilo: si appoggia a
 * Gravatar, che mostra l'immagine solo se la persona ha un account collegato a
 * quell'indirizzo email. Su una redazione con oltre cento firme significa che
 * quasi nessuno ha la foto.
 *
 * Qui la foto si sceglie dalla libreria media, direttamente nel profilo utente,
 * e viene usata ovunque: riquadro autore, commenti, elenco utenti, dati
 * strutturati. Se non c'e', si torna a Gravatar come prima.
 *
 * Vantaggio secondario: le foto locali non fanno partire una richiesta a
 * gravatar.com per ogni pagina, quindi niente indirizzi IP dei lettori inviati
 * a un servizio terzo.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chiave in cui il tema salva l'ID dell'immagine.
 */
const GPMI_AVATAR_META = 'gpmi_avatar_id';

/**
 * Chiavi usate dai plugin di foto profilo piu' diffusi.
 *
 * Servono a far ricomparire da sole le foto gia' caricate in passato: quando
 * un plugin viene disattivato i suoi dati restano nel database, e sarebbe uno
 * spreco chiedere alla redazione di ricaricare tutto.
 *
 * @return array<string, string> Chiave => forma del valore ('id' o 'array').
 */
function gpmi_legacy_avatar_keys() {
	return apply_filters( 'gpmi_legacy_avatar_keys', array(
		'simple_local_avatar'          => 'array', // Simple Local Avatars.
		'basic_user_avatar'            => 'array', // Basic User Avatars.
		'wp_user_avatar'               => 'id',    // WP User Avatar / ProfilePress.
		'avatar_manager_custom_avatar' => 'id',    // Avatar Manager.
		'sae_local_avatar'             => 'array', // Admin and Site Enhancements.
	) );
}

/**
 * ID dell'immagine profilo di un utente.
 *
 * @param int $user_id Utente.
 * @return int ID allegato, 0 se non impostata.
 */
function gpmi_avatar_id( $user_id ) {
	$user_id = (int) $user_id;

	if ( ! $user_id ) {
		return 0;
	}

	$own = (int) get_user_meta( $user_id, GPMI_AVATAR_META, true );

	if ( $own ) {
		return $own;
	}

	// Nessuna foto scelta nel tema: si cerca fra i lasciti dei vecchi plugin.
	foreach ( gpmi_legacy_avatar_keys() as $key => $shape ) {
		$value = get_user_meta( $user_id, $key, true );

		if ( empty( $value ) ) {
			continue;
		}

		if ( 'id' === $shape ) {
			$id = (int) $value;
		} else {
			// I plugin che salvano un array usano quasi sempre media_id.
			$id = 0;
			if ( is_array( $value ) ) {
				foreach ( array( 'media_id', 'id', 'attachment_id' ) as $field ) {
					if ( ! empty( $value[ $field ] ) ) {
						$id = (int) $value[ $field ];
						break;
					}
				}
			} else {
				$id = (int) $value;
			}
		}

		if ( $id && wp_attachment_is_image( $id ) ) {
			return $id;
		}
	}

	return 0;
}

/**
 * Sostituisce l'avatar Gravatar con la foto caricata sul sito.
 *
 * Agisce su get_avatar_data, quindi vale per ogni punto in cui WordPress
 * mostra un avatar, non solo per i template del tema.
 *
 * @param array $args        Argomenti dell'avatar.
 * @param mixed $id_or_email Utente, email, commento o ID.
 * @return array
 */
function gpmi_local_avatar( $args, $id_or_email ) {
	if ( ! empty( $args['force_default'] ) ) {
		return $args;
	}

	$user_id = gpmi_resolve_user_id( $id_or_email );

	if ( ! $user_id ) {
		return $args;
	}

	$attachment_id = gpmi_avatar_id( $user_id );

	if ( ! $attachment_id ) {
		return $args;
	}

	/*
	 * La dimensione "thumbnail" e' 150x150 ritagliata ed esiste per ogni
	 * immagine caricata: copre anche gli schermi ad alta densita' senza
	 * costringere a rigenerare la libreria media.
	 */
	$url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );

	if ( ! $url ) {
		return $args;
	}

	$args['url']          = $url;
	$args['found_avatar'] = true;

	return $args;
}
add_filter( 'get_avatar_data', 'gpmi_local_avatar', 10, 2 );

/**
 * Ricava l'ID utente dai molti formati accettati da get_avatar().
 *
 * @param mixed $id_or_email Utente, email, ID o commento.
 * @return int
 */
function gpmi_resolve_user_id( $id_or_email ) {
	if ( is_numeric( $id_or_email ) ) {
		return (int) $id_or_email;
	}

	if ( $id_or_email instanceof WP_User ) {
		return (int) $id_or_email->ID;
	}

	if ( $id_or_email instanceof WP_Post ) {
		return (int) $id_or_email->post_author;
	}

	if ( $id_or_email instanceof WP_Comment ) {
		if ( ! empty( $id_or_email->user_id ) ) {
			return (int) $id_or_email->user_id;
		}
		$user = ! empty( $id_or_email->comment_author_email )
			? get_user_by( 'email', $id_or_email->comment_author_email )
			: false;

		return $user ? (int) $user->ID : 0;
	}

	if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
		return $user ? (int) $user->ID : 0;
	}

	return 0;
}

/**
 * Campo "Foto" nelle schermate di profilo.
 *
 * @param WP_User|string $context Utente in modifica, oppure il contesto della
 *                                schermata di creazione.
 */
function gpmi_avatar_field( $context = null ) {
	$user_id = ( $context instanceof WP_User ) ? (int) $context->ID : 0;

	// In creazione non esiste ancora un utente: si mostra il campo vuoto.
	if ( $user_id && ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	if ( ! $user_id && ! current_user_can( 'create_users' ) ) {
		return;
	}

	$attachment_id = $user_id ? (int) get_user_meta( $user_id, GPMI_AVATAR_META, true ) : 0;
	$inherited     = $user_id ? gpmi_avatar_id( $user_id ) : 0;
	$preview       = $inherited ? wp_get_attachment_image_url( $inherited, 'thumbnail' ) : '';
	?>
	<h2><?php esc_html_e( 'Foto', 'gpmi' ); ?></h2>

	<table class="form-table" role="presentation">
		<tr>
			<th><label for="gpmi-avatar"><?php esc_html_e( 'Foto dell\'autore', 'gpmi' ); ?></label></th>
			<td>
				<div class="gpmi-avatar-field">
					<img
						src="<?php echo esc_url( $preview ); ?>"
						alt=""
						id="gpmi-avatar-preview"
						style="width:96px;height:96px;object-fit:cover;border-radius:50%;background:#f0f0f1;<?php echo $preview ? '' : 'display:none;'; ?>"
					>

					<p>
						<input type="hidden" name="gpmi_avatar_id" id="gpmi-avatar" value="<?php echo esc_attr( $attachment_id ); ?>">
						<button type="button" class="button" id="gpmi-avatar-choose"><?php esc_html_e( 'Scegli immagine', 'gpmi' ); ?></button>
						<button type="button" class="button" id="gpmi-avatar-remove"<?php echo $attachment_id ? '' : ' style="display:none;"'; ?>><?php esc_html_e( 'Rimuovi', 'gpmi' ); ?></button>
					</p>

					<p class="description">
						<?php
						if ( $user_id && ! $attachment_id && $inherited ) {
							esc_html_e( 'Foto ereditata da un plugin precedente. Scegline una nuova per sostituirla.', 'gpmi' );
						} else {
							esc_html_e( 'Immagine quadrata, almeno 300x300 pixel. Senza foto si ricade su Gravatar, che mostra qualcosa solo a chi ha un account collegato a quell\'indirizzo email.', 'gpmi' );
						}
						?>
					</p>
				</div>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'gpmi_avatar_field' );
add_action( 'edit_user_profile', 'gpmi_avatar_field' );
// Anche in creazione: aggiungere una firma e poi doverla riaprire per la foto
// e' un passaggio in piu' che su una redazione numerosa si paga ogni volta.
add_action( 'user_new_form', 'gpmi_avatar_field' );

/**
 * Salva la foto scelta, sia modificando un utente sia creandone uno.
 *
 * user_register scatta anche per registrazioni dal front-end e per creazioni da
 * codice: si agisce solo se il campo e' stato inviato da una schermata di
 * amministrazione, con nonce valido e permessi adeguati.
 *
 * @param int $user_id Utente salvato o appena creato.
 */
function gpmi_save_avatar_field( $user_id ) {
	if ( ! isset( $_POST['gpmi_avatar_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- il nonce e' verificato subito sotto.
		return;
	}

	$authorized = false;

	if ( isset( $_POST['_wpnonce_create-user'] ) ) {
		// Schermata "Aggiungi utente".
		$authorized = current_user_can( 'create_users' )
			&& wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce_create-user'] ) ), 'create-user' );
	} elseif ( isset( $_POST['_wpnonce'] ) ) {
		// Schermata di modifica del profilo.
		$authorized = current_user_can( 'edit_user', $user_id )
			&& wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'update-user_' . $user_id );
	}

	if ( ! $authorized ) {
		return;
	}

	$attachment_id = absint( wp_unslash( $_POST['gpmi_avatar_id'] ) );

	// Si accetta solo un allegato che sia davvero un'immagine.
	if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {
		update_user_meta( $user_id, GPMI_AVATAR_META, $attachment_id );
	} else {
		delete_user_meta( $user_id, GPMI_AVATAR_META );
	}
}
add_action( 'personal_options_update', 'gpmi_save_avatar_field' );
add_action( 'edit_user_profile_update', 'gpmi_save_avatar_field' );
add_action( 'user_register', 'gpmi_save_avatar_field' );

/**
 * Carica il selettore della libreria media nelle schermate di profilo.
 *
 * @param string $hook Schermata corrente.
 */
function gpmi_avatar_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'profile.php', 'user-edit.php', 'user-new.php' ), true ) ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script(
		'gpmi-avatar',
		GPMI_URI . '/assets/js/avatar.js',
		array(),
		gpmi_asset_version( 'assets/js/avatar.js' ),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'gpmi_avatar_admin_assets' );
