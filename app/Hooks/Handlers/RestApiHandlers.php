<?php

namespace CBFakePostAi\Hooks\Handlers;

class RestApiHandlers
{
    private $settings_handler;
    private $api_key_handler;
    private $lorem_generator;
    private $ai_generator;

    public function __construct()
    {
        $this->settings_handler = new SettingsHandler();
        $this->api_key_handler = new ApiKeyHandler();
        $this->lorem_generator = new LoremPostGenerator();
        $this->ai_generator = new AiPostGenerator();
        
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        // Settings route
        register_rest_route('cb-fake-post-ai/v1', '/settings', [
            'methods' => ['GET', 'POST'],
            'callback' => [$this->settings_handler, 'handle_settings'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);

        // Categories route
        register_rest_route('cb-fake-post-ai/v1', '/categories', [
            'methods' => 'GET',
            'callback' => [$this->settings_handler, 'get_categories'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);

        // Lorem Ipsum posts generation route
        register_rest_route('cb-fake-post-ai/v1', '/generate-posts', [
            'methods' => 'POST',
            'callback' => [$this->lorem_generator, 'generate_posts'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);

        // API key management route
        register_rest_route('cb-fake-post-ai/v1', '/api-key', [
            'methods' => ['GET', 'POST'],
            'callback' => [$this->api_key_handler, 'handle_api_key'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);

        // API key test route
        register_rest_route('cb-fake-post-ai/v1', '/test-api-key', [
            'methods' => 'POST',
            'callback' => [$this->api_key_handler, 'test_api_key'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);

        // Available models route
        register_rest_route('cb-fake-post-ai/v1', '/available-models', [
            'methods' => 'GET',
            'callback' => [$this->api_key_handler, 'get_available_models'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);

        // AI posts generation route
        register_rest_route('cb-fake-post-ai/v1', '/generate-ai-posts', [
            'methods' => 'POST',
            'callback' => [$this->ai_generator, 'generate_ai_posts'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);
    }
}
