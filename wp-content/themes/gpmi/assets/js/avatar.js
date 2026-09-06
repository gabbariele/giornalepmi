/**
 * Selettore della foto autore nel profilo utente.
 *
 * Usa la libreria media di WordPress: nessuna dipendenza aggiuntiva, e la
 * redazione ritrova la finestra che gia' conosce.
 */
(function () {
	'use strict';

	var field = document.getElementById('gpmi-avatar');
	var preview = document.getElementById('gpmi-avatar-preview');
	var choose = document.getElementById('gpmi-avatar-choose');
	var remove = document.getElementById('gpmi-avatar-remove');

	if (!field || !choose || !window.wp || !window.wp.media) {
		return;
	}

	var frame = null;

	choose.addEventListener('click', function (event) {
		event.preventDefault();

		if (frame) {
			frame.open();
			return;
		}

		frame = window.wp.media({
			title: 'Scegli la foto dell\u2019autore',
			button: { text: 'Usa questa immagine' },
			library: { type: 'image' },
			multiple: false
		});

		frame.on('select', function () {
			var image = frame.state().get('selection').first().toJSON();

			field.value = image.id;

			// La miniatura quadrata e' quella che verra' mostrata sul sito.
			var url = (image.sizes && image.sizes.thumbnail) ? image.sizes.thumbnail.url : image.url;
			preview.src = url;
			preview.style.display = '';
			remove.style.display = '';
		});

		frame.open();
	});

	remove.addEventListener('click', function (event) {
		event.preventDefault();
		field.value = '';
		preview.src = '';
		preview.style.display = 'none';
		remove.style.display = 'none';
	});
})();
