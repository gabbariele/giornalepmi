/**
 * Anteprima live delle opzioni del tema nel Customizer.
 */
(function (api) {
	'use strict';

	var vars = {
		gpmi_accent_color: '--gp-accent',
		gpmi_category_color: '--gp-category',
		gpmi_link_color: '--gp-link'
	};

	Object.keys(vars).forEach(function (setting) {
		api(setting, function (value) {
			value.bind(function (color) {
				document.documentElement.style.setProperty(vars[setting], color);
			});
		});
	});

	api('blogname', function (value) {
		value.bind(function (text) {
			document.querySelectorAll('.site-title a, .navbar-logo').forEach(function (el) {
				el.textContent = text;
			});
		});
	});

	api('blogdescription', function (value) {
		value.bind(function (text) {
			var el = document.querySelector('.site-tagline');
			if (el) {
				el.textContent = text;
			}
		});
	});
})(wp.customize);
