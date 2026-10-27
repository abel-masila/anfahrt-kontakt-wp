/** Kopfbereich: Schatten beim Scrollen und mobiles Menü. */
(function () {
	var header = document.querySelector('[data-bm-header]');
	if (!header) return;

	var toggle = header.querySelector('[data-bm-menu-toggle]');
	var menu = header.querySelector('#bm-mobile-menu');

	function onScroll() {
		header.classList.toggle('is-scrolled', window.scrollY > 8);
	}
	onScroll();
	window.addEventListener('scroll', onScroll, { passive: true });

	if (!toggle || !menu) return;

	function setOpen(open) {
		menu.hidden = !open;
		header.classList.toggle('is-open', open);
		document.body.classList.toggle('bm-menu-open', open);
		toggle.setAttribute('aria-expanded', String(open));
		toggle.setAttribute('aria-label', open ? 'Menü schließen' : 'Menü öffnen');
	}

	toggle.addEventListener('click', function () {
		setOpen(menu.hidden);
	});

	// Schließen bei Klick auf einen Link, mit Escape oder beim Wechsel auf Desktop-Breite.
	menu.addEventListener('click', function (event) {
		if (event.target.closest('a')) setOpen(false);
	});
	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && !menu.hidden) {
			setOpen(false);
			toggle.focus();
		}
	});
	window.matchMedia('(min-width: 1024px)').addEventListener('change', function (mq) {
		if (mq.matches) setOpen(false);
	});
	window.addEventListener('pageshow', function () {
		setOpen(false);
	});
})();
