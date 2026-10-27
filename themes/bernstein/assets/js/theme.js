/**
 * Dezente Einblend-Animation beim Scrollen (ersetzt Framer Motion).
 * Ohne JavaScript oder mit „Bewegung reduzieren“ bleibt alles sofort sichtbar.
 */
(function () {
	if (!('IntersectionObserver' in window)) return;
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

	var selector = '.bm-heading, .bm-split > .wp-block-column, .bm-more, [data-bm-reveal]';
	var elements = Array.prototype.slice.call(document.querySelectorAll(selector));
	var viewportHeight = window.innerHeight;

	var observer = new IntersectionObserver(
		function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					observer.unobserve(entry.target);
				}
			});
		},
		{ rootMargin: '0px 0px -60px 0px' }
	);

	elements.forEach(function (el) {
		// Bereits sichtbare Elemente nicht verstecken (kein Aufblitzen beim Laden).
		if (el.getBoundingClientRect().top < viewportHeight - 60) return;
		var siblings = el.parentElement ? Array.prototype.indexOf.call(el.parentElement.children, el) : 0;
		el.style.transitionDelay = Math.min(siblings * 0.06, 0.3) + 's';
		el.classList.add('bm-reveal');
		observer.observe(el);
	});
})();
