<?php
/**
 * Template Name: Abhika Aura Full Width
 * Template Post Type: page
 */
if (!defined('ABSPATH')) {
    exit;
}
get_header();
while (have_posts()):
    the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('aa-page-content'); ?>><?php the_content();
        wp_link_pages(); ?>
    </article>
<?php endwhile;
get_footer();
