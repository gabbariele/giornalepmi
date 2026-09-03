/**
 * Comportamenti del tema.
 *
 * Vanilla JS, nessuna dipendenza. Sostituisce jQuery, jquery-migrate, slick,
 * jquery.marquee e jquery-cookie del tema precedente.
 */
(function () {
	'use strict';

	var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* --------------------------------------------------------------------
	 * Menu mobile
	 * ----------------------------------------------------------------- */

	var navToggle = document.querySelector('.nav-toggle');
	var nav = document.querySelector('.main-navigation');
	var backdrop = null;

	function closeNav() {
		if (!nav) {
			return;
		}
		nav.classList.remove('is-open');
		navToggle.setAttribute('aria-expanded', 'false');
		document.body.style.removeProperty('overflow');
		if (backdrop) {
			backdrop.remove();
			backdrop = null;
		}
	}

	function openNav() {
		nav.classList.add('is-open');
		navToggle.setAttribute('aria-expanded', 'true');
		document.body.style.overflow = 'hidden';

		backdrop = document.createElement('div');
		backdrop.className = 'nav-backdrop';
		backdrop.addEventListener('click', closeNav);
		document.body.appendChild(backdrop);

		var firstLink = nav.querySelector('a');
		if (firstLink) {
			firstLink.focus();
		}
	}

	if (navToggle && nav) {
		navToggle.addEventListener('click', function () {
			if (nav.classList.contains('is-open')) {
				closeNav();
			} else {
				openNav();
			}
		});
	}

	/* --------------------------------------------------------------------
	 * Sottomenu: apertura da tastiera e su touch
	 * ----------------------------------------------------------------- */

	document.addEventListener('click', function (event) {
		var toggle = event.target.closest('.submenu-toggle');
		if (!toggle) {
			return;
		}

		event.preventDefault();
		var item = toggle.closest('li');
		var isOpen = item.classList.toggle('is-open');
		toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
	});

	/* --------------------------------------------------------------------
	 * Pannello di ricerca
	 * ----------------------------------------------------------------- */

	var searchToggle = document.querySelector('.search-toggle');
	var searchPanel = document.getElementById('search-panel');

	if (searchToggle && searchPanel) {
		searchToggle.addEventListener('click', function () {
			var willOpen = searchPanel.hidden;
			searchPanel.hidden = !willOpen;
			searchToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');

			if (willOpen) {
				var field = searchPanel.querySelector('.search-field');
				if (field) {
					field.focus();
				}
			}
		});
	}

	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Escape') {
			return;
		}
		if (searchPanel && !searchPanel.hidden) {
			searchPanel.hidden = true;
			searchToggle.setAttribute('aria-expanded', 'false');
			searchToggle.focus();
		}
		if (nav && nav.classList.contains('is-open')) {
			closeNav();
			navToggle.focus();
		}
	});

	/* --------------------------------------------------------------------
	 * Barra di navigazione che si aggancia in alto allo scroll.
	 *
	 * Usa IntersectionObserver invece di un listener di scroll: nessun lavoro
	 * sul thread principale a ogni frame, nessun layout thrashing.
	 * ----------------------------------------------------------------- */

	var navbar = document.querySelector('[data-sticky]');

	if (navbar && 'IntersectionObserver' in window) {
		var sentinel = document.createElement('div');
		sentinel.setAttribute('aria-hidden', 'true');
		sentinel.style.cssText = 'position:absolute;height:1px;width:1px;';
		navbar.parentNode.insertBefore(sentinel, navbar);

		// Segnaposto che evita il salto di layout quando la barra diventa fissa.
		var spacer = document.createElement('div');
		spacer.setAttribute('aria-hidden', 'true');

		new IntersectionObserver(function (entries) {
			var stuck = !entries[0].isIntersecting;
			navbar.classList.toggle('is-stuck', stuck);

			if (stuck) {
				spacer.style.height = navbar.offsetHeight + 'px';
				if (!spacer.parentNode) {
					navbar.parentNode.insertBefore(spacer, navbar);
				}
			} else if (spacer.parentNode) {
				spacer.remove();
			}
		}, { threshold: 0 }).observe(sentinel);
	}

	/* --------------------------------------------------------------------
	 * Ticker
	 * ----------------------------------------------------------------- */

	var track = document.querySelector('[data-ticker]');

	if (track) {
		var list = track.querySelector('.ticker-list');
		var prev = document.querySelector('[data-ticker-prev]');
		var next = document.querySelector('[data-ticker-next]');
		var timer = null;

		function step(direction) {
			var item = list.querySelector('.ticker-item');
			var amount = item ? item.offsetWidth + 8 : 300;
			var atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;

			if (direction > 0 && atEnd) {
				track.scrollTo({ left: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
				return;
			}

			track.scrollBy({ left: amount * direction, behavior: reduceMotion ? 'auto' : 'smooth' });
		}

		if (prev) {
			prev.addEventListener('click', function () { step(-1); });
		}
		if (next) {
			next.addEventListener('click', function () { step(1); });
		}

		function start() {
			if (reduceMotion || timer) {
				return;
			}
			timer = setInterval(function () { step(1); }, 5000);
		}

		function stop() {
			clearInterval(timer);
			timer = null;
		}

		track.addEventListener('mouseenter', stop);
		track.addEventListener('mouseleave', start);
		track.addEventListener('focusin', stop);
		track.addEventListener('focusout', start);

		// Non far girare il ticker quando la scheda non e' visibile.
		document.addEventListener('visibilitychange', function () {
			if (document.hidden) {
				stop();
			} else {
				start();
			}
		});

		start();
	}

	/* --------------------------------------------------------------------
	 * Fonte preferita su Google
	 *
	 * Il link funziona da solo: qui si decide soltanto se mostrare la scheda
	 * e quanto metterla in evidenza. Niente cookie e niente chiamate al
	 * server, a differenza del plugin che sostituisce.
	 * ----------------------------------------------------------------- */

	var prefsource = document.querySelector('[data-prefsource]');
	var PREF_KEY = 'gpmi.prefsource.dismissed';

	function readFlag(key) {
		try {
			return window.localStorage.getItem(key);
		} catch (e) {
			// Navigazione privata o cookie di terze parti bloccati.
			return null;
		}
	}

	function writeFlag(key, value) {
		try {
			window.localStorage.setItem(key, value);
		} catch (e) {
			// Se non si puo' scrivere, la scheda ricomparira': meglio che un errore.
		}
	}

	if (prefsource && !readFlag(PREF_KEY)) {
		// Chi arriva da Google e' l'unico per cui la preferenza ha un effetto reale.
		var fromGoogle = /(^|\.)google\./.test(document.referrer ? new URL(document.referrer).hostname : '');

		if (fromGoogle) {
			prefsource.classList.add('is-relevant');
		}

		prefsource.hidden = false;

		var closeBtn = prefsource.querySelector('[data-prefsource-close]');
		if (closeBtn) {
			closeBtn.addEventListener('click', function () {
				prefsource.hidden = true;
				writeFlag(PREF_KEY, '1');
			});
		}
	}

	// Il click resta tracciabile da Tag Manager senza codice dedicato.
	document.addEventListener('click', function (event) {
		if (!event.target.closest('[data-prefsource-action]')) {
			return;
		}
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push({ event: 'preferred_source_click' });
		writeFlag(PREF_KEY, '1');
	});

	/* --------------------------------------------------------------------
	 * Orologio della barra superiore.
	 *
	 * L'ora viene scritta dal browser, non dal server: cosi' l'HTML della
	 * pagina resta identico per tutti e la cache a pagina intera funziona.
	 * ----------------------------------------------------------------- */

	var clock = document.querySelector('[data-clock]');

	if (clock) {
		var formatter = new Intl.DateTimeFormat('it-IT', {
			hour: '2-digit',
			minute: '2-digit',
			second: '2-digit'
		});

		function tick() {
			clock.textContent = formatter.format(new Date());
		}

		clock.hidden = false;
		tick();
		setInterval(tick, 1000);
	}
})();
