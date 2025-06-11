<?php
/*
Plugin Name: TNT Product Video for WooCommerce
Plugin URI: https://github.com/lamprian/tnt-product-video
Description: Αντικαθιστά τη βασική εικόνα προϊόντος στο WooCommerce με YouTube βίντεο, αν υπάρχει URL. Υποστηρίζει όλες τις μορφές YouTube URL (watch, embed, short).
Version: 1.1
Author: Lamprian, Fene, Nikolakith
License: MIT
License URI: https://opensource.org/licenses/MIT
Text Domain: tnt-product-video
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.2
*/

if (!defined('ABSPATH')) exit;

// 1. Δημιουργία Meta Box για το πεδίο video στο admin προϊόντος
add_action('add_meta_boxes', function () {
    add_meta_box(
        'tnt_product_video_box',
        '🎥 Video URL (YouTube)',
        'tnt_product_video_field_callback',
        'product',
        'side'
    );
});

function tnt_product_video_field_callback($post) {
    $value = get_post_meta($post->ID, '_tnt_product_video_url', true);
    echo '<label for="tnt_product_video_url">Δώσε YouTube URL (οποιοδήποτε):</label>';
    echo '<input type="url" style="width:100%;" id="tnt_product_video_url" name="tnt_product_video_url" value="' . esc_attr($value) . '" />';
    echo '<p style="font-size:11px;color:#666;">Υποστηρίζονται όλες οι μορφές YouTube URL</p>';
}

// 2. Αποθήκευση του πεδίου video URL κατά την αποθήκευση προϊόντος
add_action('save_post_product', function ($post_id) {
    if (isset($_POST['tnt_product_video_url'])) {
        update_post_meta($post_id, '_tnt_product_video_url', esc_url_raw($_POST['tnt_product_video_url']));
    }
});

// 3. Εμφάνιση του βίντεο στη σελίδα προϊόντος και απόκρυψη της εικόνας
add_action('woocommerce_before_single_product_summary', function () {
    global $post;
    $raw_url = get_post_meta($post->ID, '_tnt_product_video_url', true);
    if ($raw_url) {
        $embed_url = tnt_convert_to_embed_url($raw_url);

        // Απόκρυψη της γκαλερί εικόνων του WooCommerce
        echo '<style>.woocommerce-product-gallery { display: none !important; }</style>';

        // Εμφάνιση του iframe βίντεο
        echo '<div class="woocommerce-product-video" style="margin-bottom:20px;">
            <iframe width="100%" height="360" src="' . esc_url($embed_url) . '" 
            frameborder="0" allowfullscreen loading="lazy"></iframe>
        </div>';
    }
}, 5);

// 4. Μετατροπή όλων των τύπων YouTube URL σε embed μορφή
function tnt_convert_to_embed_url($url) {
    // Αν είναι URL τύπου "watch?v=..."
    if (preg_match('/youtube\.com\/watch\?v=([^\&]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    // Αν είναι URL τύπου "youtu.be/..."
    if (preg_match('/youtu\.be\/([^\?]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    // Αν είναι ήδη embed URL
    if (strpos($url, 'embed') !== false) {
        return $url;
    }
    // Αν δεν ταιριάζει τίποτα, επιστρέφει κενό
    return '';
}

// 5. Προαιρετικό στυλ για το iframe βίντεο στη σελίδα προϊόντος
add_action('wp_head', function () {
    echo '<style>
    .woocommerce-product-video iframe {
        max-width: 100%;
        aspect-ratio: 16/9;
        display: block;
        margin: 0 auto;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    </style>';
});
