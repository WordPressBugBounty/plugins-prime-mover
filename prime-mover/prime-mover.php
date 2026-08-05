<?php

/*
Plugin Name: Prime Mover - Backup and Migration
Plugin URI: https://codexonics.com/
Description: The simplest all-around plugin to migrate WordPress and do backups. These support multisite backup/migration or cloning a WP site/multisite subsite.
Version: 2.2.0
Author: Codexonics
Author URI: https://codexonics.com/
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: prime-mover
Network: True
*/
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( !defined( 'PRIME_MOVER_MAINPLUGIN_FILE' ) ) {
    define( 'PRIME_MOVER_MAINPLUGIN_FILE', __FILE__ );
}
if ( !defined( 'PRIME_MOVER_MAINDIR' ) ) {
    define( 'PRIME_MOVER_MAINDIR', dirname( PRIME_MOVER_MAINPLUGIN_FILE ) );
}
if ( !defined( 'PRIME_MOVER_OPENSSL_CIPHER' ) ) {
    define( 'PRIME_MOVER_OPENSSL_CIPHER', 'AES-256-CBC' );
}
if ( function_exists( 'prime_mover_fs' ) ) {
    prime_mover_fs()->set_basename( false, __FILE__ );
} else {
    if ( !function_exists( 'prime_mover_fs' ) ) {
        // Create a helper function for easy SDK access.
        function prime_mover_fs() {
            global $prime_mover_fs;
            if ( !isset( $prime_mover_fs ) ) {
                // Activate multisite network integration.
                if ( !defined( 'WP_FS__PRODUCT_3826_MULTISITE' ) ) {
                    define( 'WP_FS__PRODUCT_3826_MULTISITE', true );
                }
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
                $prime_mover_fs = fs_dynamic_init( array(
                    'id'               => '3826',
                    'slug'             => 'prime-mover',
                    'premium_slug'     => 'prime-mover-pro',
                    'type'             => 'plugin',
                    'public_key'       => 'pk_a69fd5401be20bf46608b1c38165b',
                    'is_premium'       => false,
                    'premium_suffix'   => 'PRO',
                    'has_addons'       => false,
                    'has_paid_plans'   => true,
                    'is_org_compliant' => true,
                    'trial'            => array(
                        'days'               => 14,
                        'is_require_payment' => true,
                    ),
                    'menu'             => array(
                        'slug'    => 'migration-panel-settings',
                        'network' => true,
                    ),
                    'is_live'          => true,
                ) );
            }
            return $prime_mover_fs;
        }

        // Init Freemius.
        prime_mover_fs();
        // Signal that SDK was initiated.
        do_action( 'prime_mover_fs_loaded' );
    }
    require_once PRIME_MOVER_MAINDIR . '/global/PrimeMoverBridgeIO.php';
    require_once PRIME_MOVER_MAINDIR . '/global/PrimeMoverGlobalFunctions.php';
    if ( defined( 'PRIME_MOVER_PLUGIN_PATH' ) || defined( 'PRIME_MOVER_PLUGIN_UTILITIES_PATH' ) || defined( 'PRIME_MOVER_PLUGIN_CORE_PATH' ) || defined( 'PRIME_MOVER_THEME_CORE_PATH' ) ) {
        return;
    }
    include PRIME_MOVER_MAINDIR . '/global/PrimeMoverGlobalConstants.php';
    include PRIME_MOVER_MAINDIR . '/dependency-checks/PrimeMoverPHPVersionDependencies.php';
    include PRIME_MOVER_MAINDIR . '/dependency-checks/PrimeMoverWPCoreDependencies.php';
    include PRIME_MOVER_MAINDIR . '/dependency-checks/PrimeMoverRequirementsCheck.php';
    include PRIME_MOVER_MAINDIR . '/dependency-checks/PrimeMoverPHPCoreFunctionDependencies.php';
    include PRIME_MOVER_MAINDIR . '/dependency-checks/PrimeMoverFileSystemDependencies.php';
    include PRIME_MOVER_MAINDIR . '/dependency-checks/PrimeMoverPluginSlugDependencies.php';
    include PRIME_MOVER_MAINDIR . '/dependency-checks/PrimeMoverCoreSaltDependencies.php';
    include PRIME_MOVER_MAINDIR . '/global/PrimeMoverGlobalDependencies.php';
    $prime_mover_global_dependencies = new PrimeMoverGlobalDependencies();
    $prime_mover_requisitecheck = $prime_mover_global_dependencies->primeMoverGetRequisiteCheck();
    if ( is_object( $prime_mover_requisitecheck ) && !$prime_mover_requisitecheck->passes() ) {
        return;
    }
    include PRIME_MOVER_MAINDIR . '/PrimeMoverLoader.php';
    if ( file_exists( PRIME_MOVER_PLUGIN_PATH . '/vendor/autoload.php' ) ) {
        require_once PRIME_MOVER_PLUGIN_PATH . '/vendor/autoload.php';
    }
    include PRIME_MOVER_MAINDIR . '/PrimeMoverFactory.php';
    include PRIME_MOVER_MAINDIR . '/engines/prime-mover-panel/prime-mover-panel.php';
}