<?php

namespace CBFakePostAi\Hooks\Handlers;

class ApiKeyHandler
{
    /**
     * Handle API key management (GET/POST)
     */
    public function handle_api_key(\WP_REST_Request $request)
    {
        if ($request->get_method() === 'GET') {
            return $this->get_api_key_status();
        } else {
            return $this->save_api_key($request);
        }
    }

    /**
     * Get API key status (without revealing the actual key)
     */
    private function get_api_key_status()
    {
        $api_key = get_option('cb_fake_post_ai_gemini_key', '');
        
        return new \WP_REST_Response([
            'success' => true,
            'data' => [
                'has_key' => !empty($api_key)
            ]
        ], 200);
    }

    /**
     * Save API key securely
     */
    private function save_api_key(\WP_REST_Request $request)
    {
        $api_key = sanitize_text_field($request->get_param('api_key'));
        
        // Handle API key disconnection (empty key)
        if (empty($api_key)) {
            $deleted = delete_option('cb_fake_post_ai_gemini_key');
            return new \WP_REST_Response([
                'success' => true,
                'message' => 'API key disconnected successfully'
            ], 200);
        }

        // Basic validation for Gemini API key format
        if (!preg_match('/^AIza[0-9A-Za-z_-]{35}$/', $api_key)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Invalid API key format. Please check your Gemini API key.'
            ], 400);
        }

        // Store the API key securely
        $saved = update_option('cb_fake_post_ai_gemini_key', $api_key);
        
        if ($saved) {
            return new \WP_REST_Response([
                'success' => true,
                'message' => 'API key saved successfully'
            ], 200);
        } else {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Failed to save API key'
            ], 500);
        }
    }

    /**
     * Test API key connection with Gemini
     */
    public function test_api_key(\WP_REST_Request $request)
    {
        $api_key = sanitize_text_field($request->get_param('api_key'));
        
        if (empty($api_key)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'API key is required for testing'
            ], 400);
        }

        // Test the API key with a simple request to Gemini
        $test_result = $this->test_gemini_connection($api_key);
        
        if ($test_result['success']) {
            return new \WP_REST_Response([
                'success' => true,
                'message' => 'API key is valid and working'
            ], 200);
        } else {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $test_result['message']
            ], 400);
        }
    }

    /**
     * Test Gemini API connection
     */
    private function test_gemini_connection($api_key)
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $api_key;
        
        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Say hello']
                    ]
                ]
            ]
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($body),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code === 200 && isset($data['candidates'])) {
            return [
                'success' => true,
                'message' => 'API key is valid'
            ];
        } else {
            $error_message = 'Invalid API key';
            if (isset($data['error']['message'])) {
                $error_message = $data['error']['message'];
            }
            return [
                'success' => false,
                'message' => $error_message
            ];
        }
    }

    /**
     * Get available models for the current API key
     */
    public function get_available_models(\WP_REST_Request $request)
    {
        $api_key = get_option('cb_fake_post_ai_gemini_key', '');
        
        if (empty($api_key)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'No API key configured'
            ], 400);
        }

        $models_result = $this->fetch_available_models($api_key);
        
        if ($models_result['success']) {
            return new \WP_REST_Response([
                'success' => true,
                'data' => $models_result['models']
            ], 200);
        } else {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $models_result['message']
            ], 500);
        }
    }

    /**
     * Fetch available models from Gemini API
     */
    private function fetch_available_models($api_key)
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key;
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Failed to fetch models: ' . $response->get_error_message()
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200 || !isset($data['models'])) {
            $error_message = 'Failed to fetch available models';
            if (isset($data['error']['message'])) {
                $error_message = $data['error']['message'];
            }
            return [
                'success' => false,
                'message' => $error_message
            ];
        }

        // Filter and format models for content generation
        $available_models = [];
        foreach ($data['models'] as $model) {
            $model_name = $model['name'];
            
            // Only include models that support generateContent
            if (isset($model['supportedGenerationMethods']) && 
                in_array('generateContent', $model['supportedGenerationMethods'])) {
                
                // Extract model ID from full name (e.g., "models/gemini-pro" -> "gemini-pro")
                $model_id = str_replace('models/', '', $model_name);
                
                // Get display name and description
                $display_name = $this->format_model_display_name($model_id);
                $description = $this->get_model_description($model_id);
                
                $available_models[] = [
                    'id' => $model_id,
                    'name' => $display_name,
                    'description' => $description,
                    'full_name' => $model_name
                ];
            }
        }

        // Sort models by preference (Pro models first, then Flash, then others)
        usort($available_models, function($a, $b) {
            $priority_a = $this->get_model_priority($a['id']);
            $priority_b = $this->get_model_priority($b['id']);
            return $priority_a - $priority_b;
        });

        return [
            'success' => true,
            'models' => $available_models
        ];
    }

    /**
     * Format model ID to display name
     */
    private function format_model_display_name($model_id)
    {
        $name_map = [
            'gemini-1.5-pro-latest' => 'Gemini 1.5 Pro (Latest)',
            'gemini-1.5-pro' => 'Gemini 1.5 Pro',
            'gemini-1.5-flash-latest' => 'Gemini 1.5 Flash (Latest)',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            'gemini-pro' => 'Gemini Pro',
            'gemini-1.0-pro' => 'Gemini 1.0 Pro'
        ];

        return $name_map[$model_id] ?? ucwords(str_replace('-', ' ', $model_id));
    }

    /**
     * Get model description
     */
    private function get_model_description($model_id)
    {
        $descriptions = [
            'gemini-1.5-pro-latest' => 'Most capable model with latest improvements',
            'gemini-1.5-pro' => 'Most capable model for complex tasks',
            'gemini-1.5-flash-latest' => 'Fast and efficient with latest updates',
            'gemini-1.5-flash' => 'Fast and efficient for most tasks',
            'gemini-pro' => 'Balanced performance and capability',
            'gemini-1.0-pro' => 'Original Gemini Pro model'
        ];

        return $descriptions[$model_id] ?? 'Google Gemini AI model';
    }

    /**
     * Get model priority for sorting (lower number = higher priority)
     */
    private function get_model_priority($model_id)
    {
        $priorities = [
            'gemini-1.5-flash-latest' => 1,
            'gemini-1.5-flash' => 2,
            'gemini-1.5-pro-latest' => 3,
            'gemini-1.5-pro' => 4,
            'gemini-pro' => 5,
            'gemini-1.0-pro' => 6
        ];

        return $priorities[$model_id] ?? 10;
    }
}
