<?php

namespace CBFakePostAi\Hooks\Handlers;

class RestApiHandlers {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('suitepress/v1', '/plugin-stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_plugin_stats'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);

        register_rest_route('cb-fake-post-ai/v1', '/settings', [
            'methods' => 'POST',
            'callback' => [$this, 'save_settings'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'args' => [
                'numPostsMin' => [
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 1
                ],
                'numPostsMax' => [
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 1
                ],
                'titleMin' => [
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 1
                ],
                'titleMax' => [
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 1
                ],
                'contentMin' => [
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 1
                ],
                'contentMax' => [
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 1
                ],
                'credit' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['yes', 'no']
                ]
            ]
        ]);

        register_rest_route('cb-fake-post-ai/v1', '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_settings'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);
    }

    public function get_plugin_stats() {
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);

        return [
            'total' => count($all_plugins),
            'active' => count($active_plugins),
            'inactive' => count($all_plugins) - count($active_plugins),
        ];
    }

    public function save_settings($request) {
        $params = $request->get_params();
        
        // Validate that max values are greater than or equal to min values
        if ($params['numPostsMax'] < $params['numPostsMin']) {
            return new \WP_Error('invalid_range', 'Max posts must be greater than or equal to min posts', ['status' => 400]);
        }
        
        if ($params['titleMax'] < $params['titleMin']) {
            return new \WP_Error('invalid_range', 'Max title size must be greater than or equal to min title size', ['status' => 400]);
        }
        
        if ($params['contentMax'] < $params['contentMin']) {
            return new \WP_Error('invalid_range', 'Max content size must be greater than or equal to min content size', ['status' => 400]);
        }

        // Save settings to WordPress options table
        $settings = [
            'numPostsMin' => intval($params['numPostsMin']),
            'numPostsMax' => intval($params['numPostsMax']),
            'titleMin' => intval($params['titleMin']),
            'titleMax' => intval($params['titleMax']),
            'contentMin' => intval($params['contentMin']),
            'contentMax' => intval($params['contentMax']),
            'credit' => sanitize_text_field($params['credit'])
        ];

        // Get current settings to check if anything changed
        $current_settings = get_option('cb_fake_post_ai_settings', []);
        $settings_changed = $current_settings !== $settings;

        // update_option returns false if the value is the same, so we need to handle this
        $saved = update_option('cb_fake_post_ai_settings', $settings);

        // If update_option returns false, check if it's because the data is the same
        if ($saved || !$settings_changed) {
            $message = $settings_changed ? 'Settings saved successfully' : 'Settings are up to date';
            return [
                'success' => true,
                'message' => $message,
                'data' => $settings,
                'changed' => $settings_changed
            ];
        } else {
            return new \WP_Error('save_failed', 'Failed to save settings', ['status' => 500]);
        }
    }

    public function get_settings() {
        $default_settings = [
            'numPostsMin' => 1,
            'numPostsMax' => 5,
            'titleMin' => 3,
            'titleMax' => 8,
            'contentMin' => 30,
            'contentMax' => 100,
            'credit' => 'yes'
        ];

        $settings = get_option('cb_fake_post_ai_settings', $default_settings);

        return [
            'success' => true,
            'data' => $settings
        ];
    }
}
