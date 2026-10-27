/** Galerie: Filter nach Kategorie und Lightbox (natives <dialog>). */
(function () {
	document.querySelectorAll('[data-bm-gallery]').forEach(function (gallery) {
		var chips = gallery.querySelectorAll('[data-bm-filter]');
		var items = Array.prototype.slice.call(gallery.querySelectorAll('[data-bm-category]'));
		var dialog = gallery.querySelector('.bm-lightbox');
		var img = dialog.querySelector('img');
		var caption = dialog.querySelector('figcaption');
		var visible = items;
		var index = 0;

		chips.forEach(function (chip) {
			chip.addEventListener('click', function () {
				var filter = chip.getAttribute('data-bm-filter');
				chips.forEach(function (c) {
					var active = c === chip;
					c.classList.toggle('is-active', active);
					c.setAttribute('aria-pressed', String(active));
				});
				items.forEach(function (item) {
					item.hidden = filter !== 'alle' && item.getAttribute('data-bm-category') !== filter;
				});
				visible = items.filter(function (item) {
					return !item.hidden;
				});
			});
		});

		function show(i) {
			index = (i + visible.length) % visible.length;
			var button = visible[index].querySelector('button');
			var alt = button.getAttribute('data-bm-alt');
			img.src = button.getAttribute('data-bm-full');
			img.alt = alt;
			caption.textContent = alt + ' · ' + (index + 1) + ' / ' + visible.length;
			dialog.setAttribute('aria-label', alt);
		}

		items.forEach(function (item) {
			item.querySelector('button').addEventListener('click', function () {
				show(visible.indexOf(item));
				dialog.showModal();
				document.body.classList.add('bm-menu-open');
			});
		});

		dialog.addEventListener('close', function () {
			document.body.classList.remove('bm-menu-open');
			var button = visible[index] && visible[index].querySelector('button');
			if (button) button.focus();
		});
		dialog.querySelector('[data-bm-close]').addEventListener('click', function () {
			dialog.close();
		});
		dialog.querySelector('[data-bm-prev]').addEventListener('click', function () {
			show(index - 1);
		});
		dialog.querySelector('[data-bm-next]').addEventListener('click', function () {
			show(index + 1);
		});
		dialog.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowLeft') show(index - 1);
			if (event.key === 'ArrowRight') show(index + 1);
		});
		// Klick auf den dunklen Hintergrund schließt.
		dialog.addEventListener('click', function (event) {
			if (event.target === dialog) dialog.close();
		});
	});
})();
