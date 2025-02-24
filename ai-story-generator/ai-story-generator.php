<?php
/**
 * Plugin Name: AI Story Generator
 * Plugin URI: https://hahuprime.com/ai-story-generator
 * Description: Integrates Hugging Face AI story generation with WordPress.
 * Version: 1.0.0
 * Author: Tesfa G
 * Author URI: https://hahuprime.com
 * License: GPL-2.0+
 * Text Domain: ai-story-generator
 */

// Add admin menu
add_action('admin_menu', 'aisg_add_admin_menu');
function aisg_add_admin_menu() {
    add_options_page(
        'AI Story Settings',
        'AI Settings',
        'manage_options',
        'ai-story-generator',
        'aisg_options_page'
    );
}

// Initialize settings
add_action('admin_init', 'aisg_settings_init');
function aisg_settings_init() {
    register_setting('aisg_plugin', 'aisg_settings');
    
    add_settings_section(
        'aisg_section',
        'API Settings',
        'aisg_section_cb',
        'aisg_plugin'
    );
    
    add_settings_field(
        'aisg_api_key',
        'Hugging Face API Key',
        'aisg_api_key_cb',
        'aisg_plugin',
        'aisg_section'
    );
}

function aisg_section_cb() {
    echo 'Enter your Hugging Face API settings:';
}

function aisg_api_key_cb() {
    $options = get_option('aisg_settings');
    echo '<input type="password" name="aisg_settings[aisg_api_key]" value="'.esc_attr($options['aisg_api_key'] ?? '').'">';
}

function aisg_options_page() {
    ?>
    <div class="wrap">
        <h2>AI Story Generator Settings</h2>
        <form action="options.php" method="post">
            <?php
            settings_fields('aisg_plugin');
            do_settings_sections('aisg_plugin');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register shortcode
add_shortcode('story_generator', 'aisg_story_generator');
function aisg_story_generator() {
    wp_enqueue_script(
        'aisg-script',
        plugin_dir_url(__FILE__) . 'assets/js/aisg-script.js',
        ['jquery'],
        '1.0',
        true
    );
    
    wp_enqueue_style(
        'aisg-style',
        plugin_dir_url(__FILE__) . 'assets/css/style.css'
    );
    
    wp_localize_script('aisg-script', 'aisg_vars', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('story_nonce')
    ]);
    
    return '
        <div class="story-generator">
            <textarea id="story-prompt" placeholder="Enter your story prompt..."></textarea>
            <button id="generate-story">Generate Story</button>
            <div class="story-output"></div>
        </div>
    ';
}

// Handle AJAX requests
add_action('wp_ajax_generate_story', 'aisg_generate_story');
add_action('wp_ajax_nopriv_generate_story', 'aisg_require_login');

function aisg_require_login() {
    wp_send_json_error('Authentication required', 401);
}

function aisg_generate_story() {
    check_ajax_referer('story_nonce', 'security');
    
    $api_key = get_option('aisg_settings')['aisg_api_key'] ?? '';
    $prompt = sanitize_text_field($_POST['prompt'] ?? '');
    
    if (empty($prompt)) {
        wp_send_json_error('Prompt cannot be empty');
    }
    
    $response = wp_remote_post('https://api-inference.huggingface.co/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'deepseek-ai/DeepSeek-R1-Distill-Qwen-32B',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.5,
            'max_tokens' => 2048,
            'top_p' => 0.7
        ]),
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }
    
    $body = json_decode($response['body'], true);
    
    if (isset($body['choices'][0]['message']['content'])) {
        wp_send_json_success(wp_kses_post($body['choices'][0]['message']['content']));
    }
    
    wp_send_json_error('Failed to generate story');
}