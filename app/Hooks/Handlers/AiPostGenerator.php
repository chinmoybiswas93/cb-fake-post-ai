<?php

namespace CBFakePostAi\Hooks\Handlers;

class AiPostGenerator
{
    /**
     * Generate AI-powered posts using Gemini
     */
    public function generate_ai_posts(\WP_REST_Request $request)
    {
        $topic = sanitize_text_field($request->get_param('topic'));
        $style = sanitize_text_field($request->get_param('style'));
        $model = sanitize_text_field($request->get_param('model')) ?: 'gemini-1.5-flash-latest';
        $settings = $request->get_param('settings') ?: [];
        
        if (empty($topic)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Topic is required for AI post generation'
            ], 400);
        }

        // Get stored API key
        $api_key = get_option('cb_fake_post_ai_gemini_key', '');
        if (empty($api_key)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Gemini API key not configured. Please add your API key in the Tools tab.'
            ], 400);
        }

        // Use settings from the request or defaults
        $post_count = isset($settings['posts_count']) ? intval($settings['posts_count']) : 1;
        $title_words = isset($settings['title_words']) ? intval($settings['title_words']) : 5;
        $content_paragraphs = isset($settings['content_paragraphs']) ? intval($settings['content_paragraphs']) : 3;
        $selected_categories = isset($settings['selected_categories']) ? $settings['selected_categories'] : [];
        
        // Get credit setting from saved settings
        $saved_settings = get_option('cb_fake_post_ai_settings', []);
        $show_credit = isset($saved_settings['credit']) && $saved_settings['credit'] === 'yes';

        $created_posts = [];
        $errors = [];

        for ($i = 0; $i < $post_count; $i++) {
            try {
                // Generate AI content with post index for uniqueness
                $ai_content = $this->generate_ai_content($api_key, $topic, $style, $title_words, $content_paragraphs, $model, $i + 1, $post_count);
                
                if (!$ai_content['success']) {
                    $errors[] = "Post " . ($i + 1) . ": " . $ai_content['message'];
                    continue;
                }

                // Create the post
                $post_data = [
                    'post_title' => $ai_content['title'],
                    'post_content' => $this->add_credit_if_enabled($ai_content['content'], $show_credit),
                    'post_status' => 'publish',
                    'post_type' => 'post',
                    'post_author' => get_current_user_id()
                ];

                // Add categories if selected
                if (!empty($selected_categories)) {
                    $post_data['post_category'] = array_map('intval', $selected_categories);
                }

                $post_id = wp_insert_post($post_data);

                if (is_wp_error($post_id)) {
                    $errors[] = "Post " . ($i + 1) . ": " . $post_id->get_error_message();
                    continue;
                }

                // Ensure categories are properly assigned
                if (!empty($selected_categories)) {
                    wp_set_object_terms($post_id, array_map('intval', $selected_categories), 'category');
                    
                    // Remove default category if other categories are assigned
                    $default_category = get_option('default_category');
                    if (count($selected_categories) > 0 && in_array($default_category, $selected_categories) === false) {
                        wp_remove_object_terms($post_id, $default_category, 'category');
                    }
                }

                $created_posts[] = [
                    'id' => $post_id,
                    'title' => $ai_content['title'],
                    'url' => get_permalink($post_id)
                ];

            } catch (\Exception $e) {
                $errors[] = "Post " . ($i + 1) . ": " . $e->getMessage();
            }
        }

        $success_count = count($created_posts);
        $error_count = count($errors);

        if ($success_count > 0) {
            $message = sprintf(
                'Successfully generated %d AI-powered post%s on topic "%s"',
                $success_count,
                $success_count > 1 ? 's' : '',
                $topic
            );
            
            if ($error_count > 0) {
                $message .= sprintf(' (%d failed)', $error_count);
            }

            return new \WP_REST_Response([
                'success' => true,
                'message' => $message,
                'data' => [
                    'created_posts' => $created_posts,
                    'success_count' => $success_count,
                    'error_count' => $error_count,
                    'errors' => $errors
                ]
            ], 200);
        } else {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Failed to generate any posts. ' . implode('; ', $errors)
            ], 500);
        }
    }

    /**
     * Generate AI content using Gemini
     */
    private function generate_ai_content($api_key, $topic, $style, $title_words, $content_paragraphs, $model = 'gemini-1.5-flash-latest', $post_index = 1, $total_posts = 1)
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;
        
        // Create a detailed prompt based on the parameters
        $prompt = $this->create_ai_prompt($topic, $style, $title_words, $content_paragraphs, $post_index, $total_posts);
        
        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048
            ]
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($body),
            'timeout' => 45
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'AI request failed: ' . $response->get_error_message()
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200 || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $error_message = 'AI generation failed';
            if (isset($data['error']['message'])) {
                $error_message = $data['error']['message'];
            }
            return [
                'success' => false,
                'message' => $error_message
            ];
        }

        $ai_text = $data['candidates'][0]['content']['parts'][0]['text'];
        $parsed_content = $this->parse_ai_response($ai_text);
        
        return [
            'success' => true,
            'title' => $parsed_content['title'],
            'content' => $parsed_content['content']
        ];
    }

    /**
     * Create AI prompt based on parameters
     */
    private function create_ai_prompt($topic, $style, $title_words, $content_paragraphs, $post_index = 1, $total_posts = 1)
    {
        $style_descriptions = [
            'informative' => 'educational and fact-based',
            'casual' => 'conversational and friendly',
            'professional' => 'formal and business-oriented',
            'creative' => 'imaginative and engaging',
            'technical' => 'detailed and technical'
        ];

        $style_description = $style_descriptions[$style] ?? 'informative';
        
        // Add uniqueness instruction if multiple posts are being generated
        $uniqueness_instruction = '';
        if ($total_posts > 1) {
            $uniqueness_instruction = sprintf(
                "\n\nIMPORTANT: This is post %d of %d posts about the same topic. Please ensure the title and content are unique and approach the topic from a different angle, perspective, or focus area to avoid duplication.",
                $post_index,
                $total_posts
            );
        }

        return sprintf(
            'Create a blog post about "%s" in a %s style.%s

Please format your response EXACTLY like this:

TITLE: [Write a compelling title of approximately %d words]

CONTENT:
[Write %d well-structured paragraphs that are engaging and informative. Each paragraph should be substantial and flow naturally into the next. Use proper WordPress formatting with paragraph breaks.]

Guidelines:
- Make the title catchy and SEO-friendly
- Ensure content is original and engaging
- Use a %s tone throughout
- Structure the content with clear paragraphs
- Make it suitable for a WordPress blog post
- Do not use markdown formatting, just plain text with paragraph breaks',
            $topic,
            $style_description,
            $uniqueness_instruction,
            $title_words,
            $content_paragraphs,
            $style_description
        );
    }

    /**
     * Parse AI response to extract title and content
     */
    private function parse_ai_response($ai_text)
    {
        // Look for TITLE: and CONTENT: markers
        $title = '';
        $content = '';

        if (preg_match('/TITLE:\s*(.+?)(?=\n\n|CONTENT:|$)/s', $ai_text, $title_matches)) {
            $title = trim($title_matches[1]);
        }

        if (preg_match('/CONTENT:\s*(.+)$/s', $ai_text, $content_matches)) {
            $content = trim($content_matches[1]);
        }

        // Fallback: if no markers found, try to split by first line break
        if (empty($title) || empty($content)) {
            $lines = explode("\n", trim($ai_text), 2);
            $title = trim($lines[0] ?? 'AI Generated Post');
            $content = trim($lines[1] ?? $ai_text);
        }

        // Clean up title (remove any "TITLE:" prefix if it wasn't caught)
        $title = preg_replace('/^(TITLE:\s*)/i', '', $title);
        
        // Clean up content (remove any "CONTENT:" prefix if it wasn't caught)
        $content = preg_replace('/^(CONTENT:\s*)/i', '', $content);

        // Convert content to WordPress paragraphs
        $content = $this->format_content_for_wordpress($content);

        return [
            'title' => $title ?: 'AI Generated Post',
            'content' => $content ?: 'AI generated content.'
        ];
    }

    /**
     * Format content for WordPress with proper paragraph structure
     */
    private function format_content_for_wordpress($content)
    {
        // Split content into paragraphs and clean up
        $paragraphs = array_filter(array_map('trim', explode("\n\n", $content)));
        
        // If no double line breaks, try single line breaks
        if (count($paragraphs) <= 1) {
            $paragraphs = array_filter(array_map('trim', explode("\n", $content)));
        }

        // Wrap each paragraph in WordPress paragraph tags
        $formatted_paragraphs = array_map(function($paragraph) {
            return '<p>' . wp_kses_post($paragraph) . '</p>';
        }, $paragraphs);

        return implode("\n\n", $formatted_paragraphs);
    }

    /**
     * Add credit block to content if enabled
     */
    private function add_credit_if_enabled($content, $show_credit = false)
    {
        if (!$show_credit) {
            return $content;
        }

        // Add credit as a paragraph block with AI model information
        $credit_block = '<p><em>Generated by <a href="https://github.com/ChinmoyBarik/cb-fake-post-ai" target="_blank">CB Fake Post AI</a> using Google Gemini AI</em></p>';
        
        return $content . "\n\n" . $credit_block;
    }
}
