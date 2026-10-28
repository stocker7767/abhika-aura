<?php if (!defined('ABSPATH')) {
    exit;
} ?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050505">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <a class="skip" href="#main"><?php esc_html_e('Skip to content', 'abhika-aura'); ?></a>
    <header class="aa-header">
        <a class="wordmark" href="<?php echo esc_url(home_url('/')); ?>"><img class="brand-icon"
                src="<?php echo esc_url(aa_logo_url()); ?>"
                alt=""><span><?php echo esc_html(aa_setting('brand')); ?><small><?php echo esc_html(aa_setting('tagline')); ?></small></span></a>
        <nav id="site-navigation" aria-label="<?php esc_attr_e('Main navigation', 'abhika-aura'); ?>">
            <?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'menu_class' => 'menu', 'fallback_cb' => 'aa_fallback_navigation', 'depth' => 2)); ?>
        </nav>
        <a class="header-cta" href="<?php echo esc_url(aa_page_url('contact')); ?>" <?php if ('contact' === aa_page_key()) {
               echo 'aria-current="page"';
           } ?>><?php esc_html_e('Let’s talk ↗', 'abhika-aura'); ?></a>
        <button type="button" class="menu-toggle" aria-label="<?php esc_attr_e('Open navigation', 'abhika-aura'); ?>"
            aria-controls="site-navigation" aria-expanded="false"><?php esc_html_e('Menu ☰', 'abhika-aura'); ?></button>
    </header>
    <main id="main" tabindex="-1">