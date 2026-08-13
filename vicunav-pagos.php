<?php
/**
 * Plugin Name:       Vicunav Pagos
 * Plugin URI:        https://github.com/vicunav/vicunav-pagos
 * Description:       Independent payment engine for the Vicunav WordPress ecosystem.
 * Version:           0.3.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Requires Plugins:  vicunav-plugin-core
 * Author:            Vicunav
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vicunav-pagos
 *
 * @package Vicunav_Pagos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VICU_PAGOS_VERSION', '0.3.0' );
define( 'VICU_PAGOS_CONTRACT_VERSION', '0.3.0' );
define( 'VICU_PAGOS_DB_VERSION', '2' );
define( 'VICU_PAGOS_PLUGIN_FILE', __FILE__ );
define( 'VICU_PAGOS_PATH', plugin_dir_path( __FILE__ ) );

require_once VICU_PAGOS_PATH . 'src/bootstrap.php';

register_activation_hook( VICU_PAGOS_PLUGIN_FILE, 'Vicu\Pagos\activate' );
register_deactivation_hook( VICU_PAGOS_PLUGIN_FILE, 'Vicu\Pagos\deactivate' );
