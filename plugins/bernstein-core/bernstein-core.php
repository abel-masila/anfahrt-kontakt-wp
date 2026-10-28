<?php
/**
 * Plugin Name:       Bernstein Core
 * Plugin URI:        https://github.com/abel-masila/anfahrt-kontakt-wp
 * Description:       Inhalte und Blöcke für das Bernstein-Motel Marchegg: Zimmer, Aktivitäten, FAQ, Galerie, Anfrage-Formular und Buchungs-Vorschau.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      8.0
 * Author:            Bernstein-Motel Marchegg
 * License:           GPL-2.0-or-later
 * Text Domain:       bernstein-core
 */

defined( 'ABSPATH' ) || exit;

define( 'BM_VERSION', '1.0.0' );
define( 'BM_PATH', plugin_dir_path( __FILE__ ) );
define( 'BM_URL', plugin_dir_url( __FILE__ ) );

require_once BM_PATH . 'includes/helpers.php';
require_once BM_PATH . 'includes/hardening.php';
require_once BM_PATH . 'includes/settings.php';
require_once BM_PATH . 'includes/post-types.php';
require_once BM_PATH . 'includes/blocks.php';
require_once BM_PATH . 'includes/enquiries.php';
require_once BM_PATH . 'includes/seo.php';
require_once BM_PATH . 'includes/seed-admin.php';

register_activation_hook(
	__FILE__,
	function () {
		bm_register_post_types();
		bm_grant_enquiry_caps();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'bm_enquiry_cleanup' );
	}
);
