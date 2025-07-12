<?php
/**
 * WordPress - CB Fake Post Ai
 *
 * Plugin Name:         CB Fake Post Ai
 * Plugin URI:          https://wordpress.org/plugins/cb-fake-post-ai
 * Description:         Plugins short description
 * Version:             1.1.1
 * Requires at least:   5.2
 * Requires PHP:        7.2
 * Contributor:         Contributor according to the WordPress.org
 * Author:              Chinmoy Biswas
 * Author URI:          https://github.com/chinmoybiswas93
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         cb-fake-post-ai
 * Domain Path:         /languages
 */

if ( ! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use CBFakePostAi\App;

if ( class_exists( 'CBFakePostAi\App' ) ) {
    $app = new App();
}
