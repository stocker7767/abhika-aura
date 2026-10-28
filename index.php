<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header(); ?>
<section class="wrap section aa-regular-page">
    <h1><?php echo is_home() ? esc_html__('Insights', 'abhika-aura') : esc_html(wp_strip_all_tags(get_the_archive_title())); ?>
    </h1>
    <?php if (have_posts()):
        while (have_posts()):
            the_post(); ?>
            <article <?php post_class('aa-post-card'); ?>>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <p class="aa-post-meta"><?php echo esc_html(get_the_date()); ?></p><?php the_excerpt(); ?>
            </article>
        <?php endwhile;
        the_posts_pagination(array('class' => 'aa-pagination'));
    else: ?>
        <p>No posts are available yet.</p><?php endif; ?>
</section>
<?php get_footer();
