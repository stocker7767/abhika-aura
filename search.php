<?php if (!defined('ABSPATH')) {
    exit;
}
get_header(); ?>
<section class="wrap section aa-regular-page">
    <h1><?php printf(esc_html__('Search results for: %s', 'abhika-aura'), esc_html(get_search_query())); ?></h1>
    <?php get_search_form(); ?><?php if (have_posts()):
          while (have_posts()):
              the_post(); ?>
            <article class="aa-post-card">
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?>
            </article><?php endwhile;
          the_posts_pagination();
      else: ?>
        <p>No matching pages were found. Try a different search or contact us.</p><?php endif; ?>
</section>
<?php get_footer(); ?>