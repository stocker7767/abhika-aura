<?php
if (!defined('ABSPATH')) {
    exit;
}
function aa_page_definitions()
{
    return array(
        'home' => array('title' => 'Home', 'slug' => 'home'),
        'services' => array('title' => 'Services', 'slug' => 'services'),
        'health-safety' => array('title' => 'Digital Health & Safety', 'slug' => 'health-safety'),
        'about' => array('title' => 'About Abhika Aura', 'slug' => 'about'),
        'how-we-work' => array('title' => 'How We Work', 'slug' => 'how-we-work'),
        'contact' => array('title' => 'Let’s Talk', 'slug' => 'contact'),
        'refund' => array('title' => 'Refund & Cancellation Policy', 'slug' => 'refund'),
        'terms' => array('title' => 'Terms & Conditions', 'slug' => 'terms'),
        'privacy' => array('title' => 'Privacy Policy', 'slug' => 'privacy'),
        'cookies' => array('title' => 'Cookie Policy', 'slug' => 'cookies'),
    );
}
function aa_page_id($key, $include_unpublished = false)
{
    $map = get_option('aa_page_map', array());
    $id = is_array($map) && isset($map[$key]) ? absint($map[$key]) : 0;
    if (!$id || 'page' !== get_post_type($id) || in_array(get_post_status($id), array('trash', 'auto-draft'), true)) {
        return 0;
    }
    if (!$include_unpublished && ('publish' !== get_post_status($id) || get_post_field('post_password', $id))) {
        return 0;
    }
    return $id;
}
function aa_page_url($key)
{
    $id = aa_page_id($key);
    if ($id) {
        return get_permalink($id);
    }
    // Do not expose unavailable page permalinks in public calls to action.
    return 'contact' === $key && is_email(aa_setting('email')) ? 'mailto:' . aa_setting('email') : home_url('/');
}
function aa_page_key($id = 0)
{
    $id = $id ? absint($id) : get_queried_object_id();
    $key = get_post_meta($id, '_aa_starter_key', true);
    return is_string($key) && isset(aa_page_definitions()[$key]) ? $key : '';
}
function aa_logo_url()
{
    $id = defined('ABHIKA_AURA_VERSION') ? absint(get_theme_mod('custom_logo')) : 0;
    $id = $id ?: absint(get_option('aa_company_logo_id'));
    $url = $id ? wp_get_attachment_image_url(absint($id), 'full') : '';
    return $url ? $url : AA_TOOLS_URL . '/assets/logo.png';
}
function aa_telephone_uri()
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', aa_setting('phone'));
}
function aa_instagram_url()
{
    return 'https://www.instagram.com/' . aa_setting('instagram') . '/';
}
function aa_resolve_content_tokens($content)
{
    $replacements = array(
        '{{aa_email}}' => esc_html(aa_setting('email')),
        '{{aa_phone}}' => esc_html(aa_setting('phone')),
        '{{aa_tel}}' => esc_attr(preg_replace('/[^0-9+]/', '', aa_setting('phone'))),
        '{{aa_location}}' => esc_html(aa_setting('location')),
        '{{aa_company_number}}' => esc_html(aa_setting('company_number')),
        '{{aa_instagram}}' => esc_html(aa_setting('instagram')),
        '{{aa_instagram_url}}' => esc_url(aa_instagram_url()),
        '{{aa_logo}}' => esc_url(aa_logo_url()),
    );
    $content = strtr($content, $replacements);
    return preg_replace_callback('/\{\{aa_url_([a-z-]+)([^}]*)\}\}/', function ($match) {
        if (!isset(aa_page_definitions()[$match[1]])) {
            return '';
        }
        $url = aa_page_url($match[1]);
        if (0 === strpos($url, 'mailto:')) {
            return esc_url($url);
        }
        $parts = wp_parse_url('https://placeholder.test/' . $match[2]);
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $args);
            $url = add_query_arg($args, $url);
        }
        if (!empty($parts['fragment'])) {
            $url .= '#' . rawurlencode($parts['fragment']);
        }
        return esc_url($url);
    }, $content);
}
add_filter('the_content', 'aa_resolve_content_tokens', 8);
function aa_default_content($key)
{
    if (!isset(aa_page_definitions()[$key])) {
        return '';
    }
    $file = AA_TOOLS_PATH . '/starter-content/' . $key . '.html';
    return is_readable($file) ? file_get_contents($file) : '';
}
function aa_contact_no_cache()
{
    $post = get_queried_object();
    if ('contact' === aa_page_key() || ($post instanceof WP_Post && has_shortcode($post->post_content, 'abhika_aura_enquiry_form'))) {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        nocache_headers();
    }
}
add_action('template_redirect', 'aa_contact_no_cache');
