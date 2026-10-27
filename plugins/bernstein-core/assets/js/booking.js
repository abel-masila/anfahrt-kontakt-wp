/**
 * Buchungs-Vorschau: rein visueller Prototyp mit Preisberechnung.
 * Es wird keine Verfügbarkeit geprüft, nichts gebucht und nichts bezahlt.
 */
(function () {
	var eur = new Intl.NumberFormat('de-AT', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2 });

	function formatDate(iso) {
		if (!iso) return '–';
		return new Date(iso + 'T12:00:00').toLocaleDateString('de-AT', { day: '2-digit', month: '2-digit', year: 'numeric' });
	}

	function nightsBetween(a, b) {
		if (!a || !b) return 0;
		return Math.max(0, Math.round((new Date(b + 'T12:00:00') - new Date(a + 'T12:00:00')) / 86400000));
	}

	document.querySelectorAll('[data-bm-booking]').forEach(function (root) {
		var config = JSON.parse(root.getAttribute('data-config') || '{}');
		var rooms = config.rooms || [];
		var arrival = root.querySelector('#bd-arrival');
		var departure = root.querySelector('#bd-departure');
		var guests = root.querySelector('#bd-guests');
		var radios = Array.prototype.slice.call(root.querySelectorAll('input[name="bm-room"]'));
		var out = function (key) {
			return root.querySelector('[data-bd="' + key + '"]');
		};
		var calc = root.querySelector('.bm-booking__calc');
		var empty = root.querySelector('.bm-booking__empty');
		var continueBtn = out('continue');
		var modal = root.querySelector('.bm-modal');

		var today = new Date();
		var todayIso = new Date(today.getTime() - today.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
		arrival.min = todayIso;
		departure.min = todayIso;

		function selectedRoom() {
			var checked = radios.filter(function (r) {
				return r.checked;
			})[0];
			var id = checked ? checked.value : rooms[0] && rooms[0].id;
			return rooms.filter(function (r) {
				return r.id === id;
			})[0] || rooms[0];
		}

		function update() {
			departure.min = arrival.value || todayIso;
			var count = Number(guests.value);

			// Kategorien sperren, die zu klein sind; ggf. Auswahl verschieben.
			radios.forEach(function (radio) {
				var room = rooms.filter(function (r) {
					return r.id === radio.value;
				})[0];
				radio.disabled = room && count > room.capacity;
			});
			var current = radios.filter(function (r) {
				return r.checked;
			})[0];
			if (current && current.disabled) {
				var firstEnabled = radios.filter(function (r) {
					return !r.disabled;
				})[0];
				if (firstEnabled) firstEnabled.checked = true;
			}

			var room = selectedRoom();
			if (!room) return;
			var nights = nightsBetween(arrival.value, departure.value);
			var persons = Math.min(count, room.capacity);
			var perNight = persons === 1 ? config.singlePrice : room.price * persons;
			var total = nights * perNight;
			var tax = nights * persons * config.touristTax;

			out('room').textContent = room.name;
			out('arrival').textContent = formatDate(arrival.value);
			out('departure').textContent = formatDate(departure.value);
			out('guests').textContent = String(persons);
			out('nights').textContent = nights ? String(nights) : '–';

			calc.hidden = nights === 0;
			empty.hidden = nights > 0;
			continueBtn.disabled = nights === 0;
			if (nights > 0) {
				out('line').textContent = nights + ' × ' + eur.format(perNight);
				out('subtotal').textContent = eur.format(total);
				out('tax').textContent = '≈ ' + eur.format(tax);
				out('total').textContent = eur.format(total);
			}
		}

		[arrival, departure, guests].concat(radios).forEach(function (el) {
			el.addEventListener('change', update);
			el.addEventListener('input', update);
		});
		update();

		continueBtn.addEventListener('click', function () {
			modal.showModal();
		});
		modal.querySelectorAll('[data-bm-close]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				modal.close();
			});
		});
		// Klick außerhalb des Dialogs (auf den abgedunkelten Hintergrund) schließt.
		modal.addEventListener('click', function (event) {
			var rect = modal.getBoundingClientRect();
			var inside = event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom;
			if (!inside) modal.close();
		});

		// Vorauswahl über ?zimmer=…
		var preselect = new URLSearchParams(window.location.search).get('zimmer');
		radios.forEach(function (radio) {
			if (radio.value === preselect && !radio.disabled) {
				radio.checked = true;
				update();
			}
		});
	});
})();
