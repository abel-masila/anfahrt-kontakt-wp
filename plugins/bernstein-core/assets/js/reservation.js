/**
 * Anfrage-Formular: Prüfung im Browser, Erfolgsmeldung und vorausgefüllte E-Mail (mailto:).
 * Es wird nichts an den Server gesendet.
 */
(function () {
	document.querySelectorAll('[data-bm-reservation]').forEach(function (root) {
		var config = JSON.parse(root.getAttribute('data-config') || '{}');
		var form = root.querySelector('form');
		var success = root.querySelector('.bm-success');
		var today = new Date();
		var todayIso = new Date(today.getTime() - today.getTimezoneOffset() * 60000).toISOString().slice(0, 10);

		var arrival = form.elements.arrival;
		var departure = form.elements.departure;
		arrival.min = todayIso;
		departure.min = todayIso;
		arrival.addEventListener('change', function () {
			departure.min = arrival.value || todayIso;
		});

		// Vorauswahl über ?zimmer=…
		var preselect = new URLSearchParams(window.location.search).get('zimmer');
		if (preselect && form.elements.room.querySelector('option[value="' + CSS.escape(preselect) + '"]')) {
			form.elements.room.value = preselect;
		}

		function formatDate(iso, weekday) {
			if (!iso) return '';
			var options = { day: '2-digit', month: '2-digit', year: 'numeric' };
			if (weekday) options.weekday = 'short';
			return new Date(iso + 'T12:00:00').toLocaleDateString('de-AT', options);
		}

		function setError(name, message) {
			var el = form.elements[name];
			var error = root.querySelector('#bm-err-' + name);
			if (!error) return;
			error.textContent = message || '';
			error.hidden = !message;
			el.setAttribute('aria-invalid', message ? 'true' : 'false');
		}

		['firstName', 'lastName', 'email', 'phone', 'arrival', 'departure', 'privacy'].forEach(function (name) {
			form.elements[name].addEventListener('input', function () {
				setError(name, '');
			});
			form.elements[name].addEventListener('change', function () {
				setError(name, '');
			});
		});

		function validate() {
			var v = form.elements;
			var errors = {};
			if (!v.firstName.value.trim()) errors.firstName = 'Bitte geben Sie Ihren Vornamen an.';
			if (!v.lastName.value.trim()) errors.lastName = 'Bitte geben Sie Ihren Nachnamen an.';
			if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.email.value.trim())) errors.email = 'Bitte geben Sie eine gültige E-Mail-Adresse an.';
			if (v.phone.value.trim().length < 6) errors.phone = 'Bitte geben Sie Ihre Telefonnummer an.';
			if (!v.arrival.value) errors.arrival = 'Bitte wählen Sie ein Anreisedatum.';
			if (!v.departure.value) errors.departure = 'Bitte wählen Sie ein Abreisedatum.';
			if (v.arrival.value && v.departure.value && v.departure.value <= v.arrival.value) errors.departure = 'Die Abreise muss nach der Anreise liegen.';
			if (!v.privacy.checked) errors.privacy = 'Bitte stimmen Sie der Datenschutzerklärung zu.';

			['firstName', 'lastName', 'email', 'phone', 'arrival', 'departure', 'privacy'].forEach(function (name) {
				setError(name, errors[name]);
			});
			var first = Object.keys(errors)[0];
			if (first) form.elements[first].focus();
			return !first;
		}

		function roomName(id) {
			var room = (config.rooms || []).filter(function (r) {
				return r.id === id;
			})[0];
			return room ? room.name : id;
		}

		function mailto(v) {
			var name = v.firstName.value.trim() + ' ' + v.lastName.value.trim();
			var subject = 'Zimmeranfrage: ' + roomName(v.room.value) + ', ' + formatDate(v.arrival.value, true) + ' – ' + formatDate(v.departure.value, true);
			var lines = [
				config.salutation,
				'',
				'ich möchte gerne folgendes Zimmer anfragen:',
				'',
				'Zimmerkategorie: ' + roomName(v.room.value),
				'Anzahl Zimmer: ' + v.roomCount.value,
				'Anreise: ' + formatDate(v.arrival.value, true),
				'Abreise: ' + formatDate(v.departure.value, true),
				'Personen: ' + v.guests.value,
				'',
			];
			if (v.message.value.trim()) lines.push('Nachricht: ' + v.message.value.trim(), '');
			lines.push('Name: ' + name, 'Telefon: ' + v.phone.value.trim(), 'E-Mail: ' + v.email.value.trim(), '', 'Mit freundlichen Grüßen', name);
			return 'mailto:' + config.email + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(lines.join('\n'));
		}

		var submitBtn = form.querySelector('.bm-form__submit');
		var formError = form.querySelector('.bm-form__error');
		var busy = false;

		/** Speichert die Anfrage in WordPress. Liefert true (gespeichert), false (Fehler) oder Feldfehler. */
		function save(v) {
			var payload = {
				firstName: v.firstName.value.trim(),
				lastName: v.lastName.value.trim(),
				email: v.email.value.trim(),
				phone: v.phone.value.trim(),
				arrival: v.arrival.value,
				departure: v.departure.value,
				guests: Number(v.guests.value),
				room: v.room.value,
				roomCount: Number(v.roomCount.value),
				message: v.message.value.trim(),
				privacy: v.privacy.checked,
				website: v.website.value,
			};
			return fetch(config.endpoint, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				credentials: 'same-origin',
				body: JSON.stringify(payload),
			})
				.then(function (res) {
					return res.json().then(function (json) {
						if (res.ok && json.success) return { saved: true };
						if (res.status === 400 && json.errors) return { errors: json.errors };
						return { saved: false };
					});
				})
				.catch(function () {
					return { saved: false };
				});
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			if (busy || !validate()) return;
			var v = form.elements;
			busy = true;
			formError.hidden = true;
			submitBtn.setAttribute('aria-busy', 'true');
			submitBtn.textContent = 'Wird gesendet …';

			save(v).then(function (result) {
				busy = false;
				submitBtn.removeAttribute('aria-busy');
				submitBtn.textContent = 'Unverbindlich anfragen';
				if (result.errors) {
					Object.keys(result.errors).forEach(function (name) {
						setError(name, result.errors[name]);
					});
					formError.textContent = 'Bitte prüfen Sie Ihre Angaben.';
					formError.hidden = false;
					return;
				}
				showSuccess(v, result.saved);
			});
		});

		function showSuccess(v, saved) {
			success.querySelector('.bm-success__title').textContent = 'Vielen Dank, ' + v.firstName.value.trim() + '!';
			var summary = success.querySelector('.bm-success__summary');
			summary.textContent = '';
			var parts = [
				['Ihre Anfrage für ', false],
				[roomName(v.room.value), true],
				[' vom ', false],
				[formatDate(v.arrival.value, true), true],
				[' bis ', false],
				[formatDate(v.departure.value, true), true],
				[saved ? ' ist bei uns eingegangen.' : ' wurde vorbereitet.', false],
			];
			parts.forEach(function (part) {
				var node = part[1] ? document.createElement('strong') : document.createTextNode(part[0]);
				if (part[1]) node.textContent = part[0];
				summary.appendChild(node);
			});

			// Gespeichert: E-Mail nur als optionale Kopie. Nicht gespeichert: E-Mail ist der Hauptweg.
			var mailBtn = success.querySelector('[data-bm-mailto]');
			mailBtn.href = mailto(v);
			mailBtn.classList.toggle('bm-btn--outline', saved);
			success.querySelector('[data-bm-mailto-label]').textContent = saved ? 'Kopie per E-Mail senden' : 'Anfrage per E-Mail senden';
			success.querySelector('.bm-success__fallback').hidden = saved;
			success.setAttribute('data-saved', saved ? 'true' : 'false');

			form.hidden = true;
			success.hidden = false;
			success.focus();
			root.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}

		root.querySelector('[data-bm-reset]').addEventListener('click', function () {
			form.reset();
			if (preselect) form.elements.room.value = preselect;
			success.hidden = true;
			form.hidden = false;
			form.elements.firstName.focus();
		});
	});
})();
