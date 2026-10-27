/**
 * Block-Editor: Vorschau der Bernstein-Blöcke (serverseitig gerendert) und einfache Optionen.
 * Kein Build-Schritt nötig – nutzt die globalen wp.*-Pakete.
 */
(function (wp) {
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;
	var RangeControl = wp.components.RangeControl;
	var ServerSideRender = wp.serverSideRender;

	var options = {
		rooms: { compact: 'Kompakt (ohne Ausstattungsliste)', limit: 'Anzahl (0 = alle)' },
		amenities: { limit: 'Anzahl (0 = alle)' },
		activities: { limit: 'Anzahl (0 = alle)' },
		gallery: { preview: 'Vorschau (ohne Filter und Lightbox)', limit: 'Anzahl (0 = alle)' },
	};

	var blocks = [
		'header', 'footer', 'trust-bar', 'rooms', 'price-table', 'price-conditions', 'amenities',
		'location-highlights', 'activities', 'gallery', 'faq', 'contact-card', 'directions',
		'cta-banner', 'reservation-form', 'booking-demo',
	];

	blocks.forEach(function (slug) {
		var name = 'bernstein/' + slug;
		wp.blocks.registerBlockType(name, {
			edit: function (props) {
				var controls = [];
				var opts = options[slug] || {};
				if (opts.compact) {
					controls.push(el(ToggleControl, {
						key: 'compact', label: opts.compact, checked: !!props.attributes.compact,
						onChange: function (v) { props.setAttributes({ compact: v }); },
					}));
				}
				if (opts.preview) {
					controls.push(el(ToggleControl, {
						key: 'preview', label: opts.preview, checked: !!props.attributes.preview,
						onChange: function (v) { props.setAttributes({ preview: v }); },
					}));
				}
				if (opts.limit) {
					controls.push(el(RangeControl, {
						key: 'limit', label: opts.limit, min: 0, max: 24, value: props.attributes.limit || 0,
						onChange: function (v) { props.setAttributes({ limit: v || 0 }); },
					}));
				}
				return el(
					Fragment,
					null,
					controls.length ? el(InspectorControls, null, el(PanelBody, { title: 'Einstellungen' }, controls)) : null,
					el('div', useBlockProps(), el(ServerSideRender, { block: name, attributes: props.attributes }))
				);
			},
			save: function () {
				return null;
			},
		});
	});
})(window.wp);
