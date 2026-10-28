<?php
/** Shared by the standalone companion plugin and the theme fallback. */
if (!defined('ABSPATH') || defined('AA_WEBSITE_TOOLS_LOADED')) {
    return;
}
define('AA_WEBSITE_TOOLS_LOADED', true);
define('AA_WEBSITE_TOOLS_VERSION', '1.2.0');
define('AA_TOOLS_PATH', dirname(__DIR__, 2));
define('AA_TOOLS_URL', defined('AA_TOOLS_PLUGIN_FILE') ? plugins_url('', AA_TOOLS_PLUGIN_FILE) : get_template_directory_uri());
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/setup.php';
require_once __DIR__ . '/enquiry.php';
require_once __DIR__ . '/records.php';
