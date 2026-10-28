<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();
while (have_posts()):
    the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('wrap section aa-regular-page'); ?>>
        <h1><?php the_title(); ?></h1><?php the_content();
          wp_link_pages(); ?>
    </article>
<?php endwhile;
get_footer();
