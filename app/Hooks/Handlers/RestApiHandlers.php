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
                ],
                'categories' => [
                    'required' => false,
                    'type' => 'array',
                    'items' => [
                        'type' => 'integer'
                    ]
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

        register_rest_route('cb-fake-post-ai/v1', '/generate-posts', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_posts'],
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
                ]
            ]
        ]);

        register_rest_route('cb-fake-post-ai/v1', '/categories', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categories'],
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
            'credit' => sanitize_text_field($params['credit']),
            'categories' => isset($params['categories']) ? array_map('intval', $params['categories']) : []
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
            'credit' => 'yes',
            'categories' => []
        ];

        $settings = get_option('cb_fake_post_ai_settings', $default_settings);

        return [
            'success' => true,
            'data' => $settings
        ];
    }

    public function generate_posts($request) {
        $params = $request->get_params();
        
        // Validate ranges
        if ($params['numPostsMax'] < $params['numPostsMin']) {
            return new \WP_Error('invalid_range', 'Max posts must be greater than or equal to min posts', ['status' => 400]);
        }
        
        if ($params['titleMax'] < $params['titleMin']) {
            return new \WP_Error('invalid_range', 'Max title size must be greater than or equal to min title size', ['status' => 400]);
        }
        
        if ($params['contentMax'] < $params['contentMin']) {
            return new \WP_Error('invalid_range', 'Max content size must be greater than or equal to min content size', ['status' => 400]);
        }

        // Get credit setting from saved options
        $saved_settings = get_option('cb_fake_post_ai_settings', []);
        $show_credit = isset($saved_settings['credit']) ? $saved_settings['credit'] === 'yes' : true;
        $selected_categories = isset($saved_settings['categories']) ? $saved_settings['categories'] : [];

        // Lorem ipsum text for generating content
        $lorem_words = explode(' ', 'Lorem ipsum dolor sit amet consectetur adipiscing elit Quisque faucibus ex sapien vitae pellentesque sem placerat In id cursus mi pretium tellus duis convallis Tempus leo eu aenean sed diam urna tempor Pulvinar vivamus fringilla lacus nec metus bibendum egestas Iaculis massa nisl malesuada lacinia integer nunc posuere Ut hendrerit semper vel class aptent taciti sociosqu Ad litora torquent per conubia nostra inceptos himenaeos');

        // Determine number of posts to generate
        $num_posts = ($params['numPostsMin'] === $params['numPostsMax']) 
            ? $params['numPostsMin'] 
            : rand($params['numPostsMin'], $params['numPostsMax']);

        $generated_posts = [];
        $current_user_id = get_current_user_id();

        for ($i = 0; $i < $num_posts; $i++) {
            // Generate title
            $title_length = ($params['titleMin'] === $params['titleMax']) 
                ? $params['titleMin'] 
                : rand($params['titleMin'], $params['titleMax']);
            
            $title_words = array_slice($lorem_words, 0, $title_length);
            shuffle($title_words);
            $title = ucfirst(implode(' ', array_slice($title_words, 0, $title_length)));

            // Generate content
            $content_length = ($params['contentMin'] === $params['contentMax']) 
                ? $params['contentMin'] 
                : rand($params['contentMin'], $params['contentMax']);
            
            // Create multiple paragraphs for longer content
            $paragraphs = [];
            $words_per_paragraph = max(20, intval($content_length / max(1, intval($content_length / 30)))); // Aim for ~30 words per paragraph
            $remaining_words = $content_length;
            
            while ($remaining_words > 0) {
                $paragraph_words = min($words_per_paragraph, $remaining_words);
                $paragraph_content = [];
                
                for ($j = 0; $j < $paragraph_words; $j++) {
                    $paragraph_content[] = $lorem_words[array_rand($lorem_words)];
                }
                
                $paragraph_text = ucfirst(implode(' ', $paragraph_content)) . '.';
                $paragraphs[] = $paragraph_text;
                $remaining_words -= $paragraph_words;
            }

            // Create Gutenberg blocks for each paragraph
            $content = '';
            foreach ($paragraphs as $paragraph) {
                $content .= '<!-- wp:paragraph -->' . "\n";
                $content .= '<p>' . $paragraph . '</p>' . "\n";
                $content .= '<!-- /wp:paragraph -->';
                if (count($paragraphs) > 1) {
                    $content .= "\n\n";
                }
            }

            // Add credit link as a separate Gutenberg block if enabled
            if ($show_credit) {
                $content .= "\n\n" . '<!-- wp:paragraph -->' . "\n";
                $content .= '<p><em>Generated with <a href="#">CB Fake Post AI</a></em></p>' . "\n";
                $content .= '<!-- /wp:paragraph -->';
            }

            // Create post
            $post_data = [
                'post_title' => $title,
                'post_content' => $content,
                'post_status' => 'publish',
                'post_author' => $current_user_id,
                'post_type' => 'post'
            ];

            // Add categories to post data if selected
            if (!empty($selected_categories)) {
                $post_data['post_category'] = $selected_categories;
                
                // Temporarily disable default category assignment
                add_filter('wp_insert_post_empty_content', '__return_false');
            }

            $post_id = wp_insert_post($post_data);

            if (!is_wp_error($post_id)) {
                // Double-check category assignment with wp_set_object_terms for reliability
                if (!empty($selected_categories)) {
                    wp_set_object_terms($post_id, $selected_categories, 'category');
                    
                    // Remove default category if it was auto-assigned and we have specific categories
                    $default_category = get_option('default_category');
                    if ($default_category && !in_array($default_category, $selected_categories)) {
                        wp_remove_object_terms($post_id, $default_category, 'category');
                    }
                }

                $generated_posts[] = [
                    'id' => $post_id,
                    'title' => $title,
                    'content_length' => $content_length,
                    'categories' => $selected_categories
                ];
            }
            
            // Re-enable default category assignment
            remove_filter('wp_insert_post_empty_content', '__return_false');
        }

        if (count($generated_posts) > 0) {
            $category_names = [];
            $category_info = '';
            
            if (!empty($selected_categories)) {
                foreach ($selected_categories as $cat_id) {
                    $category = get_category($cat_id);
                    if ($category) {
                        $category_names[] = $category->name;
                    }
                }
                $category_info = !empty($category_names) 
                    ? ' in categories: ' . implode(', ', $category_names)
                    : '';
            } else {
                $category_info = ' (uncategorized)';
            }
                
            return [
                'success' => true,
                'message' => sprintf('Successfully generated %d post(s)%s', count($generated_posts), $category_info),
                'data' => [
                    'generated_count' => count($generated_posts),
                    'posts' => $generated_posts,
                    'applied_categories' => $selected_categories
                ]
            ];
        } else {
            return new \WP_Error('generation_failed', 'Failed to generate posts', ['status' => 500]);
        }
    }

    public function get_categories() {
        $categories = get_categories([
            'taxonomy' => 'category',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);

        $category_list = [];
        foreach ($categories as $category) {
            $category_list[] = [
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count
            ];
        }

        return [
            'success' => true,
            'data' => $category_list
        ];
    }
}
