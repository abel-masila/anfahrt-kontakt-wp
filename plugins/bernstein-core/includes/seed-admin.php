<?php
/**
 * Startinhalte einspielen: per WP-CLI (`wp bernstein seed [--force]`) oder
 * in wp-admin unter Werkzeuge → Bernstein-Inhalte (für Hosts ohne WP-CLI).
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'bernstein seed',
		function ( $args, $assoc_args ) {
			require_once BM_PATH . 'seed/seed.php';
			foreach ( bm_run_seed( ! empty( $assoc_args['force'] ) ) as $line ) {
				WP_CLI::log( $line );
			}
			WP_CLI::success( 'Bernstein-Motel ist eingerichtet.' );
		},
		array(
			'shortdesc' => 'Legt Seiten, Zimmer, Aktivitäten, FAQ, Galerie, Menüs und Einstellungen an.',
			'synopsis'  => array(
				array(
					'type'        => 'flag',
					'name'        => 'force',
					'optional'    => true,
					'description' => 'Bestehende Inhalte mit dem Demo-Stand überschreiben.',
				),
			),
		)
	);
}

add_action(
	'admin_menu',
	function () {
		add_management_page( 'Bernstein-Inhalte', 'Bernstein-Inhalte', 'manage_options', 'bernstein-seed', 'bm_render_seed_page' );
	}
);

function bm_render_seed_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$log = array();
	if ( isset( $_POST['bm_seed'] ) && check_admin_referer( 'bm_seed' ) ) {
		require_once BM_PATH . 'seed/seed.php';
		$log = bm_run_seed( ! empty( $_POST['bm_force'] ) );
	}
	?>
	<div class="wrap">
		<h1>Bernstein-Inhalte</h1>
		<p>Legt alle Seiten, Zimmer, Aktivitäten, Fragen, Galerie-Bilder, Menüs und Einstellungen der Website an. Bereits vorhandene Inhalte bleiben unverändert.</p>
		<?php if ( $log ) : ?>
			<div class="notice notice-success"><p><?php echo wp_kses_post( implode( '<br>', array_map( 'esc_html', $log ) ) ); ?></p></div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'bm_seed' ); ?>
			<p><label><input type="checkbox" name="bm_force" value="1"> Bestehende Inhalte mit dem Demo-Stand <strong>überschreiben</strong> (Änderungen gehen verloren)</label></p>
			<?php submit_button( 'Inhalte einspielen', 'primary', 'bm_seed' ); ?>
		</form>
	</div>
	<?php
}
