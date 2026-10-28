<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();
if (is_page() && have_posts()) {
    while (have_posts()) {
        the_post();
        $is_starter = (bool) aa_page_key(get_the_ID());
        echo '<article id="post-' . esc_attr(get_the_ID()) . '" class="' . ($is_starter ? 'aa-page-content' : 'wrap section aa-regular-page') . '">';
        if (!$is_starter) {
            echo '<h1>' . esc_html(get_the_title()) . '</h1>';
        }
        the_content();
        wp_link_pages();
        echo '</article>';
    }
} else {
    echo '<article class="aa-page-content">';
    echo apply_filters('the_content', aa_default_content('home')); // Packaged trusted starter markup; runtime values are escaped.
    echo '</article>';
}
get_footer();
