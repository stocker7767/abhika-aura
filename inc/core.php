<?php
if (!defined('ABSPATH')) {
    exit;
}
function aa_theme_setup()
{
    load_theme_textdomain('abhika-aura', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
    add_theme_support('custom-logo', array('height' => 240, 'width' => 240, 'flex-height' => true, 'flex-width' => true));
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    add_editor_style(array('assets/editor.css'));
    register_nav_menus(array('primary' => __('Main navigation', 'abhika-aura'), 'policies' => __('Footer policies', 'abhika-aura')));
}
add_action('after_setup_theme', 'aa_theme_setup');
function aa_enqueue_assets()
{
    wp_enqueue_style('aa-site', get_template_directory_uri() . '/assets/site.css', array(), ABHIKA_AURA_VERSION);
    wp_enqueue_style('aa-theme', get_stylesheet_uri(), array('aa-site'), wp_get_theme()->get('Version'));
    wp_enqueue_script('aa-theme', get_template_directory_uri() . '/assets/theme.js', array(), ABHIKA_AURA_VERSION, true);
}
add_action('wp_enqueue_scripts', 'aa_enqueue_assets');
function aa_fallback_navigation()
{
    $items = array('home' => 'Home', 'services' => 'Services', 'health-safety' => 'Health & Safety', 'about' => 'About', 'how-we-work' => 'How We Work');
    echo '<ul class="menu">';
    foreach ($items as $key => $label) {
        if (!aa_page_id($key)) {
            continue;
        }
        $active = aa_page_key() === $key || ('home' === $key && is_front_page());
        echo '<li><a href="' . esc_url(aa_page_url($key)) . '"' . ($active ? ' aria-current="page"' : '') . '>' . esc_html($label) . '</a></li>';
    }
    echo '</ul>';
}
function aa_instagram_link()
{
    if (!aa_setting('instagram')) {
        return;
    }
    echo '<a class="instagram-link" href="' . esc_url(aa_instagram_url()) . '" target="_blank" rel="noopener noreferrer"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" fill="currentColor" stroke="none"/></svg><span>Instagram · @' . esc_html(aa_setting('instagram')) . '</span><span aria-hidden="true">↗</span></a>';
}
