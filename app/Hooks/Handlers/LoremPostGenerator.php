<?php

namespace CBFakePostAi\Hooks\Handlers;

class LoremPostGenerator
{
    /**
     * Generate posts with Lorem Ipsum content
     */
    public function generate_posts(\WP_REST_Request $request)
    {
        $posts_count = intval($request->get_param('posts_count')) ?: 1;
        $title_words = intval($request->get_param('title_words')) ?: 5;
        $content_paragraphs = intval($request->get_param('content_paragraphs')) ?: 3;
        $selected_categories = $request->get_param('selected_categories') ?: [];

        // Get credit setting from saved settings
        $settings = get_option('cb_fake_post_ai_settings', []);
        $show_credit = isset($settings['credit']) && $settings['credit'] === 'yes';

        $created_posts = [];
        $errors = [];

        for ($i = 0; $i < $posts_count; $i++) {
            try {
                $title = $this->generate_lorem_title($title_words);
                $content = $this->generate_lorem_content($content_paragraphs, $show_credit);

                $post_data = [
                    'post_title' => $title,
                    'post_content' => $content,
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
                    'title' => $title,
                    'url' => get_permalink($post_id)
                ];

            } catch (\Exception $e) {
                $errors[] = "Post " . ($i + 1) . ": " . $e->getMessage();
            }
        }

        // ...existing code for response handling...
        $success_count = count($created_posts);
        $error_count = count($errors);

        // Get category names for the message
        $category_names = [];
        if (!empty($selected_categories)) {
            foreach ($selected_categories as $cat_id) {
                $category = get_category($cat_id);
                if ($category) {
                    $category_names[] = $category->name;
                }
            }
        }

        if ($success_count > 0) {
            $message = sprintf(
                'Successfully created %d post%s',
                $success_count,
                $success_count > 1 ? 's' : ''
            );

            if (!empty($category_names)) {
                $message .= ' in categories: ' . implode(', ', $category_names);
            }

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
                    'errors' => $errors,
                    'categories' => $category_names
                ]
            ], 200);
        } else {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Failed to create any posts. ' . implode('; ', $errors)
            ], 500);
        }
    }

    /**
     * Generate Lorem Ipsum title with random words
     */
    private function generate_lorem_title($word_count)
    {
        $lorem_words = [
            'lorem',
            'ipsum',
            'dolor',
            'sit',
            'amet',
            'consectetur',
            'adipiscing',
            'elit',
            'sed',
            'do',
            'eiusmod',
            'tempor',
            'incididunt',
            'ut',
            'labore',
            'et',
            'dolore',
            'magna',
            'aliqua',
            'enim',
            'ad',
            'minim',
            'veniam',
            'quis',
            'nostrud',
            'exercitation',
            'ullamco',
            'laboris',
            'nisi',
            'aliquip',
            'ex',
            'ea',
            'commodo',
            'consequat',
            'duis',
            'aute',
            'irure',
            'in',
            'reprehenderit',
            'voluptate',
            'velit',
            'esse',
            'cillum',
            'fugiat',
            'nulla',
            'pariatur',
            'excepteur',
            'sint',
            'occaecat',
            'cupidatat',
            'non',
            'proident',
            'sunt',
            'culpa',
            'qui',
            'officia',
            'deserunt',
            'mollit',
            'anim',
            'id',
            'est',
            'laborum'
        ];

        // Randomly shuffle the words and pick the required count
        $shuffled_words = $lorem_words;
        shuffle($shuffled_words);
        $title_words = array_slice($shuffled_words, 0, $word_count);
        
        $title = implode(' ', $title_words);
        return ucwords($title);
    }

    /**
     * Generate Lorem Ipsum content
     */
    private function generate_lorem_content($paragraph_count, $show_credit = false)
    {
        $lorem_paragraphs = [
            "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.",
            "Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
            "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.",
            "Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.",
            "Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.",
            "At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.",
            "Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur?",
            "Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?",
            "But I must explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you a complete account of the system.",
            "On the other hand, we denounce with righteous indignation and dislike men who are so beguiled and demoralized by the charms of pleasure of the moment, so blinded by desire, that they cannot foresee the pain and trouble that are bound to ensue.",
            "These cases are perfectly simple and easy to distinguish. In a free hour, when our power of choice is untrammelled and when nothing prevents our being able to do what we like best, every pleasure is to be welcomed and every pain avoided.",
            "But in certain circumstances and owing to the claims of duty or the obligations of business it will frequently occur that pleasures have to be repudiated and annoyances accepted."
        ];

        $content_paragraphs = [];
        
        // Randomly select paragraphs without repetition if possible
        if ($paragraph_count <= count($lorem_paragraphs)) {
            $shuffled_paragraphs = $lorem_paragraphs;
            shuffle($shuffled_paragraphs);
            $content_paragraphs = array_slice($shuffled_paragraphs, 0, $paragraph_count);
        } else {
            // If more paragraphs needed than available, repeat some randomly
            for ($i = 0; $i < $paragraph_count; $i++) {
                $content_paragraphs[] = $lorem_paragraphs[array_rand($lorem_paragraphs)];
            }
        }

        // Create Gutenberg blocks
        $blocks = [];
        foreach ($content_paragraphs as $paragraph) {
            $blocks[] = [
                'blockName' => 'core/paragraph',
                'attrs' => [],
                'innerBlocks' => [],
                'innerHTML' => '<p>' . $paragraph . '</p>',
                'innerContent' => ['<p>' . $paragraph . '</p>']
            ];
        }

        // Add credit link if enabled
        if ($show_credit) {
            $credit_text = 'Generated by <a href="https://github.com/chinmoybiswas93/cb-fake-post-ai" target="_blank" rel="noopener">CB Fake Post AI</a>';
            $blocks[] = [
                'blockName' => 'core/paragraph',
                'attrs' => [
                    'className' => 'cb-fake-post-credit'
                ],
                'innerBlocks' => [],
                'innerHTML' => '<p class="cb-fake-post-credit"><small><em>' . $credit_text . '</em></small></p>',
                'innerContent' => ['<p class="cb-fake-post-credit"><small><em>' . $credit_text . '</em></small></p>']
            ];
        }

        return serialize_blocks($blocks);
    }
}
