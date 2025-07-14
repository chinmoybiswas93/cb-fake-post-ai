<?php
namespace CBFakePostAi\Hooks\Handlers;

class AdminMenuHandlers
{

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'conditionally_enqueue_assets']);
    }

    public function conditionally_enqueue_assets($hook)
    {
        if (
            strpos($hook, 'cb-fake-post-ai-dashboard-menu') === false &&
            strpos($hook, 'cb-fake-post-ai-dashboard-submenu') === false
        ) {
            return;
        }
        $this->suitepress_plugins_names_enqueue_assets();
    }

    public function add_admin_menu()
    {

        add_menu_page(
            __('CB Fake Post Ai', 'cb-fake-post-ai'),
            __('CB Fake Post Ai', 'cb-fake-post-ai'),
            'manage_options',
            'cb-fake-post-ai-dashboard-menu',
            [$this, 'render_dashboard'],
            $this->tranpr_get_menu_icon(),
            26
        );
    }

    public function tranpr_get_menu_icon()
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32">
        <rect width="32" height="32" rx="6" fill="#E0E7FF"/>
        <path d="M16 8v8l6 3" stroke="#6366F1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="16" cy="16" r="12" stroke="#6366F1" stroke-width="2"/>
        <circle cx="16" cy="16" r="2" fill="#6366F1"/>
        </svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function render_dashboard()
    {
        include_once CB_FAKE_POST_AI_PATH . '/app/Views/admin-dashboard.php';
    }

    public function suitepress_plugins_names_enqueue_assets()
    {

        $dev_server = 'http://localhost:5173';
        $hot_file_path = CB_FAKE_POST_AI_PATH . '/.hot';
        $is_dev = file_exists($hot_file_path);

        if ($is_dev) {
            // Enqueue Vite HMR client and main entry
            wp_enqueue_script('vite-client', $dev_server . '/@vite/client', [], null, true);
            wp_enqueue_script('vite-client', "{$dev_server}/@vite/client", [], null, true);
            wp_enqueue_script('cb-fake-post-vite', "{$dev_server}/js/main.js", [], null, true);
            wp_localize_script('cb-fake-post-vite', 'SuitePressSettings', [
                'settingsRestUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/settings')),
                'generatePostsUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/generate-posts')),
                'categoriesUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/categories')),
                'apiKeyUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/api-key')),
                'testApiKeyUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/test-api-key')),
                'availableModelsUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/available-models')),
                'generateAiPostsUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/generate-ai-posts')),
                'nonce' => wp_create_nonce('wp_rest'),
                'ajaxurl' => admin_url('admin-ajax.php'),
            ]);

        } else {

            // Prod: Use filetime for cache busting
            $main_js = CB_FAKE_POST_AI_BUILD_PATH . '/main.js';
            $main_css = CB_FAKE_POST_AI_BUILD_PATH . '/main.css';

            $js_version = file_exists($main_js) ? filemtime($main_js) : '1.0.0';
            $css_version = file_exists($main_css) ? filemtime($main_css) : '1.0.0';

            // Load compiled assets
            wp_enqueue_script('cb-fake-post-main', CB_FAKE_POST_AI_BUILD_URL . '/main.js', [], $js_version, true);
            wp_enqueue_style('cb-fake-post-style', CB_FAKE_POST_AI_BUILD_URL . '/main.css', [], $css_version);

            wp_localize_script('cb-fake-post-main', 'SuitePressSettings', [
                'settingsRestUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/settings')),
                'generatePostsUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/generate-posts')),
                'categoriesUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/categories')),
                'apiKeyUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/api-key')),
                'testApiKeyUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/test-api-key')),
                'availableModelsUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/available-models')),
                'generateAiPostsUrl' => esc_url_raw(rest_url('cb-fake-post-ai/v1/generate-ai-posts')),
                'nonce' => wp_create_nonce('wp_rest'),
                'ajaxurl' => admin_url('admin-ajax.php'),
            ]);
        }

        // Optional: Add type="module" for both dev and prod
        add_filter('script_loader_tag', function ($tag, $handle) {
            if (in_array($handle, ['vite-client', 'cb-fake-post-vite', 'cb-fake-post-main'])) {
                $tag = str_replace('<script ', '<script type="module" ', $tag);
            }
            return $tag;
        }, 10, 2);
    }
}
