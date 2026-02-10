<?php
/*
Plugin Name: TNT Product Video for WooCommerce
Plugin URI: https://github.com/Lamprian/TNT-Product-Video-for-WooCommerce
Description: Αντικαθιστά τη βασική εικόνα προϊόντος στο WooCommerce με YouTube βίντεο, αν υπάρχει URL. Υποστηρίζει watch, embed, short, shorts και youtube-nocookie μορφές.
Version: 1.3
Author: Lamprian, Fene, Nikolakith
License: MIT
License URI: https://opensource.org/licenses/MIT
Text Domain: tnt-product-video
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
*/

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Δημιουργία Meta Box για το πεδίο video στο admin προϊόντος.
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'tnt_product_video_box',
        '🎥 Video URL (YouTube)',
        'tnt_product_video_field_callback',
        'product',
        'side'
    );
});

/**
 * Απόδοση του πεδίου βίντεο στο προϊόν.
 *
 * @param WP_Post $post Product post.
 */
function tnt_product_video_field_callback($post)
{
    $value = get_post_meta($post->ID, '_tnt_product_video_url', true);
    $embed = tnt_convert_to_embed_url($value);

    wp_nonce_field('tnt_product_video_save', 'tnt_product_video_nonce');

    echo '<label for="tnt_product_video_url">Δώσε YouTube URL (οποιοδήποτε):</label>';
    echo '<input type="url" style="width:100%;" id="tnt_product_video_url" name="tnt_product_video_url" value="' . esc_attr($value) . '" placeholder="https://youtu.be/VIDEO_ID" />';
    echo '<p style="font-size:11px;color:#666;">Υποστηρίζονται μορφές: watch?v=, youtu.be, embed, shorts</p>';

    if (!empty($embed)) {
        echo '<p style="margin:8px 0 4px;"><strong>Preview:</strong></p>';
        echo '<iframe width="100%" height="180" src="' . esc_url($embed) . '" frameborder="0" allowfullscreen loading="lazy"></iframe>';
    }
}

/**
 * 2. Αποθήκευση του πεδίου video URL με ελέγχους ασφαλείας.
 */
add_action('save_post_product', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    if (!isset($_POST['tnt_product_video_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tnt_product_video_nonce'])), 'tnt_product_video_save')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!isset($_POST['tnt_product_video_url'])) {
        delete_post_meta($post_id, '_tnt_product_video_url');
        return;
    }

    $url = esc_url_raw(wp_unslash($_POST['tnt_product_video_url']));

    if (empty($url)) {
        delete_post_meta($post_id, '_tnt_product_video_url');
        return;
    }

    update_post_meta($post_id, '_tnt_product_video_url', $url);
});

/**
 * 3. Αν υπάρχει έγκυρο video URL, αφαιρούμε native gallery output.
 */
add_action('wp', function () {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    $product_id = get_queried_object_id();

    if (!$product_id) {
        return;
    }

    $raw_url = get_post_meta($product_id, '_tnt_product_video_url', true);
    $embed_url = tnt_convert_to_embed_url($raw_url);

    if (!empty($embed_url)) {
        remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
    }
});

/**
 * 4. Εμφάνιση βίντεο αντί για εικόνες, μόνο όταν υπάρχει έγκυρο embed URL.
 */
add_action('woocommerce_before_single_product_summary', function () {
    $product_id = get_queried_object_id();

    if (!$product_id) {
        return;
    }

    $raw_url = get_post_meta($product_id, '_tnt_product_video_url', true);
    $embed_url = tnt_convert_to_embed_url($raw_url);

    if (empty($embed_url)) {
        return;
    }

    echo '<div class="woocommerce-product-video">';
    echo '<iframe width="100%" height="360" src="' . esc_url($embed_url) . '" frameborder="0" allowfullscreen loading="lazy"></iframe>';
    echo '</div>';
}, 20);

/**
 * 5. Μετατροπή τύπων YouTube URL σε embed μορφή.
 *
 * @param string $url YouTube URL.
 * @return string
 */
function tnt_convert_to_embed_url($url)
{
    if (empty($url) || !is_string($url)) {
        return '';
    }

    $url = trim($url);
    $parts = wp_parse_url($url);

    if (empty($parts['host'])) {
        return '';
    }

    $host = strtolower($parts['host']);
    $path = isset($parts['path']) ? trim($parts['path'], '/') : '';
    $query = isset($parts['query']) ? $parts['query'] : '';

    if (false !== strpos($host, 'youtube.com') || false !== strpos($host, 'youtube-nocookie.com')) {
        if (0 === strpos($path, 'embed/')) {
            $video_id = substr($path, 6);
            $video_id = tnt_normalize_youtube_video_id($video_id);
            return $video_id ? 'https://www.youtube.com/embed/' . $video_id : '';
        }

        if (0 === strpos($path, 'shorts/')) {
            $video_id = substr($path, 7);
            $video_id = tnt_normalize_youtube_video_id($video_id);
            return $video_id ? 'https://www.youtube.com/embed/' . $video_id : '';
        }

        if ('watch' === $path && !empty($query)) {
            parse_str($query, $params);
            if (!empty($params['v'])) {
                $video_id = tnt_normalize_youtube_video_id($params['v']);
                return $video_id ? 'https://www.youtube.com/embed/' . $video_id : '';
            }
        }
    }

    if (false !== strpos($host, 'youtu.be') && !empty($path)) {
        $segments = explode('/', $path);
        $video_id = tnt_normalize_youtube_video_id($segments[0]);
        return $video_id ? 'https://www.youtube.com/embed/' . $video_id : '';
    }

    return '';
}

/**
 * Κανονικοποίηση YouTube video ID.
 *
 * @param string $video_id Πιθανό video id.
 * @return string
 */
function tnt_normalize_youtube_video_id($video_id)
{
    $video_id = sanitize_text_field((string) $video_id);
    $video_id = preg_replace('/[^A-Za-z0-9_-]/', '', $video_id);

    if (empty($video_id)) {
        return '';
    }

    return $video_id;
}

/**
 * 6. Frontend styles μόνο στη σελίδα προϊόντος.
 */
add_action('wp_enqueue_scripts', function () {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    $product_id = get_queried_object_id();

    if (!$product_id) {
        return;
    }

    $embed_url = tnt_convert_to_embed_url(get_post_meta($product_id, '_tnt_product_video_url', true));

    if (empty($embed_url)) {
        return;
    }

    wp_enqueue_style(
        'tnt-product-video-frontend',
        plugin_dir_url(__FILE__) . 'assets/css/tnt-product-video.css',
        array(),
        '1.3'
    );
});

/**
 * 7. Νέα στήλη στη λίστα προϊόντων για ένδειξη video.
 */
add_filter('manage_edit-product_columns', function ($columns) {
    $columns['tnt_product_video'] = esc_html__('Product Video', 'tnt-product-video');
    return $columns;
});

add_action('manage_product_posts_custom_column', function ($column, $post_id) {
    if ('tnt_product_video' !== $column) {
        return;
    }

    $embed_url = tnt_convert_to_embed_url(get_post_meta($post_id, '_tnt_product_video_url', true));

    if (empty($embed_url)) {
        echo '—';
        return;
    }

    echo '<span style="color:#2271b1;font-weight:600;">✓ Active</span><br />';
    echo '<a href="' . esc_url($embed_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Preview', 'tnt-product-video') . '</a>';
}, 10, 2);
