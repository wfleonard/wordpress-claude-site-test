/**
 * Saxon primary navigation.
 * Author: William Leonard, CTI Global, bill.leonard@cticorp.com
 *
 * Toggles the small screen menu, keeps aria-expanded in sync, closes on
 * Escape, outside click, or when the viewport grows to the desktop layout,
 * and returns focus to the toggle when closed from the keyboard.
 * Vanilla JavaScript, no dependencies.
 */
(function () {
	'use strict';

	var nav = document.querySelector('.primary-nav');
	if (!nav) {
		return;
	}

	var toggle = nav.querySelector('.menu-toggle');
	var menu = nav.querySelector('.menu');
	if (!toggle || !menu) {
		return;
	}

	var desktop = window.matchMedia('(min-width: 56em)');

	function isOpen() {
		return toggle.getAttribute('aria-expanded') === 'true';
	}

	function setOpen(open) {
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		nav.classList.toggle('is-open', open);
	}

	toggle.addEventListener('click', function () {
		setOpen(!isOpen());
		if (isOpen()) {
			var firstLink = menu.querySelector('a');
			if (firstLink) {
				firstLink.focus();
			}
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && isOpen()) {
			setOpen(false);
			toggle.focus();
		}
	});

	document.addEventListener('click', function (event) {
		if (isOpen() && !nav.contains(event.target)) {
			setOpen(false);
		}
	});

	// Close the menu when keyboard focus leaves it on small screens.
	menu.addEventListener('focusout', function (event) {
		if (!desktop.matches && isOpen() && event.relatedTarget && !nav.contains(event.relatedTarget)) {
			setOpen(false);
		}
	});

	desktop.addEventListener('change', function (event) {
		if (event.matches) {
			setOpen(false);
		}
	});
})();
