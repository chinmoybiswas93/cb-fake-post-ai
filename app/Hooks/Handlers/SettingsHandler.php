<?php

namespace CBFakePostAi\Hooks\Handlers;

class SettingsHandler
{
    /**
     * Handle settings (GET/POST)
     */
    public function handle_settings(\WP_REST_Request $request)
    {
        if ($request->get_method() === 'GET') {
            return $this->get_settings();
        } else {
            return $this->save_settings($request);
        }
    }

    /**
     * Get current settings
     */
    private function get_settings()
    {
        $settings = get_option('cb_fake_post_ai_settings', [
            'posts_count' => 1,
            'title_words' => 5,
            'content_paragraphs' => 3,
            'selected_categories' => [],
            'credit' => 'no',
            // Range values for frontend
            'numPostsMin' => 1,
            'numPostsMax' => 5,
            'titleMin' => 3,
            'titleMax' => 8,
            'contentMin' => 30,
            'contentMax' => 100
        ]);

        return new \WP_REST_Response([
            'success' => true,
            'data' => $settings
        ], 200);
    }

    /**
     * Save settings
     */
    private function save_settings(\WP_REST_Request $request)
    {
        // Get all parameters from the request
        $all_params = $request->get_params();
        error_log('CB Fake Post AI - Received parameters: ' . json_encode($all_params));

        // Validate and sanitize input with defaults
        $posts_count = max(1, intval($request->get_param('posts_count') ?? 1));
        $title_words = max(1, intval($request->get_param('title_words') ?? 5));
        $content_paragraphs = max(1, intval($request->get_param('content_paragraphs') ?? 3));
        $selected_categories = $request->get_param('selected_categories') ?: [];
        $credit = sanitize_text_field($request->get_param('credit') ?? 'no');

        // Handle range values from frontend with defaults
        $numPostsMin = max(1, intval($request->get_param('numPostsMin') ?? 1));
        $numPostsMax = max($numPostsMin, intval($request->get_param('numPostsMax') ?? 5));
        $titleMin = max(1, intval($request->get_param('titleMin') ?? 3));
        $titleMax = max($titleMin, intval($request->get_param('titleMax') ?? 8));
        $contentMin = max(1, intval($request->get_param('contentMin') ?? 30));
        $contentMax = max($contentMin, intval($request->get_param('contentMax') ?? 100));

        // Ensure selected_categories is an array of integers
        if (is_array($selected_categories)) {
            $selected_categories = array_map('intval', array_filter($selected_categories, 'is_numeric'));
        } else {
            $selected_categories = [];
        }

        $settings = [
            'posts_count' => $posts_count,
            'title_words' => $title_words,
            'content_paragraphs' => $content_paragraphs,
            'selected_categories' => $selected_categories,
            'credit' => $credit,
            // Store range values for frontend
            'numPostsMin' => $numPostsMin,
            'numPostsMax' => $numPostsMax,
            'titleMin' => $titleMin,
            'titleMax' => $titleMax,
            'contentMin' => $contentMin,
            'contentMax' => $contentMax
        ];

        error_log('CB Fake Post AI - Saving settings: ' . json_encode($settings));

        // Always update the option
        $saved = update_option('cb_fake_post_ai_settings', $settings);
        
        // Verify the settings were saved by reading them back
        $stored_settings = get_option('cb_fake_post_ai_settings', []);
        
        // Check key fields to ensure save was successful
        $essential_fields_match = (
            isset($stored_settings['numPostsMin']) && $stored_settings['numPostsMin'] === $settings['numPostsMin'] &&
            isset($stored_settings['numPostsMax']) && $stored_settings['numPostsMax'] === $settings['numPostsMax'] &&
            isset($stored_settings['selected_categories']) && $stored_settings['selected_categories'] === $settings['selected_categories']
        );

        if ($essential_fields_match || $saved !== false) {
            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Settings saved successfully',
                'data' => $settings
            ], 200);
        } else {
            error_log('CB Fake Post AI - Settings save verification failed. Stored: ' . json_encode($stored_settings));
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Failed to save settings'
            ], 500);
        }
    }

    /**
     * Get categories
     */
    public function get_categories()
    {
        $categories = get_categories([
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);

        $category_data = array_map(function($category) {
            return [
                'id' => $category->term_id,
                'name' => $category->name,
                'count' => $category->count
            ];
        }, $categories);

        return new \WP_REST_Response([
            'success' => true,
            'data' => $category_data
        ], 200);
    }
}
