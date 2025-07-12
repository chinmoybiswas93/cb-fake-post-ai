<?php
namespace CBFakePostAi;

use CBFakePostAi\Hooks\Handlers\AdminMenuHandlers;
use CBFakePostAi\Hooks\Handlers\RestApiHandlers;

class App {

    public function __construct() {
        add_action('init', [$this, 'init']);
    }

    public function init() {

        define( 'CB_FAKE_POST_AI_PATH', untrailingslashit( plugin_dir_path( __DIR__ ) ) );
        define( 'CB_FAKE_POST_AI_URL', untrailingslashit( plugin_dir_url( __DIR__ ) ) );
        define( 'CB_FAKE_POST_AI_BUILD_PATH', CB_FAKE_POST_AI_PATH . '/public/assets' );
        define( 'CB_FAKE_POST_AI_BUILD_URL', CB_FAKE_POST_AI_URL . '/public/assets' );
        define( 'CB_FAKE_POST_AI_VERSION', '1.0.0' );


       new AdminMenuHandlers();
       new RestApiHandlers();
    }
}
