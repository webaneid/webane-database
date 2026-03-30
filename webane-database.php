<?php
/**
 * Plugin Name: Webane Database
 * Plugin URI: https://webane.com
 * Description: Database pesantren dan alumni.
 * Version: 0.0.4
 * Author: Webane Indonesia
 * Author URI: https://webane.com
 * Update URI: https://github.com/webaneid/webane-database
 * Text Domain: webane-database
 * Domain Path: /languages
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WDB_PLUGIN_VERSION', '0.0.4');
define('WDB_PLUGIN_FILE', __FILE__);
define('WDB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WDB_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WDB_PLUGIN_PATH . 'includes/Database/Installer.php';
require_once WDB_PLUGIN_PATH . 'includes/Core/Plugin.php';
require_once WDB_PLUGIN_PATH . 'includes/Core/Updater.php';
require_once WDB_PLUGIN_PATH . 'includes/Admin/CrudPage.php';
require_once WDB_PLUGIN_PATH . 'includes/Admin/Menu.php';
require_once WDB_PLUGIN_PATH . 'includes/Admin/RegionImporter.php';
require_once WDB_PLUGIN_PATH . 'includes/Frontend/Auth.php';
require_once WDB_PLUGIN_PATH . 'includes/Frontend/Forms.php';

register_activation_hook(WDB_PLUGIN_FILE, ['WDB\\Database\\Installer', 'activate']);

WDB\Database\Installer::maybe_upgrade();
WDB\Core\Plugin::boot();
