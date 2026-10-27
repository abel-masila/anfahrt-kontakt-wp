<?php
/**
 * Bernstein – Block-Theme für das Bernstein-Motel Marchegg.
 * Inhalte, Blöcke und Einstellungen kommen aus dem Plugin „Bernstein Core“.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/theme.css' );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$dir = get_template_directory();
		$uri = get_template_directory_uri();
		wp_enqueue_style( 'bernstein-theme', $uri . '/assets/css/theme.css', array(), (string) filemtime( $dir . '/assets/css/theme.css' ) );
		wp_enqueue_script(
			'bernstein-theme',
			$uri . '/assets/js/theme.js',
			array(),
			(string) filemtime( $dir . '/assets/js/theme.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
);

/** Schriften vorladen (liegen im Theme – keine Verbindung zu Google). */
add_action(
	'wp_head',
	function () {
		$uri = get_template_directory_uri() . '/assets/fonts/';
		foreach ( array( 'fraunces-latin-variable.woff2', 'inter-latin-variable.woff2' ) as $font ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( $uri . $font ) );
		}
	},
	1
);

/** Button-Stile für Redakteure. */
add_action(
	'init',
	function () {
		register_block_style( 'core/button', array( 'name' => 'amber', 'label' => 'Bernstein' ) );
		register_block_style( 'core/button', array( 'name' => 'ghost', 'label' => 'Hell (auf Foto)' ) );
		register_block_style( 'core/button', array( 'name' => 'outline-forest', 'label' => 'Umrandet' ) );
	}
);
