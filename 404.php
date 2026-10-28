<?php if (!defined('ABSPATH')) {
    exit;
}
get_header(); ?>
<section class="wrap section aa-regular-page"><span class="eyebrow">PAGE NOT FOUND</span>
    <h1>Let’s get you<br>back on track.</h1>
    <p>The page may have moved or the link may be incorrect.</p><a class="button"
        href="<?php echo esc_url(home_url('/')); ?>">Return home →</a> <a class="textlink"
        href="<?php echo esc_url(aa_page_url('contact')); ?>">Let’s talk ↗</a>
</section>
<?php get_footer(); ?>