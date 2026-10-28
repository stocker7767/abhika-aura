<?php if (!defined('ABSPATH')) {
    exit;
} ?>
</main>
<footer>
    <div class="wrap footer-grid">
        <div>
            <h3><?php echo esc_html(aa_setting('brand')); ?></h3>
            <p><?php echo esc_html(aa_setting('tagline')); ?></p>
            <p>Technology made simpler.<br>Workplaces made safer.</p>
        </div>
        <div><span
                class="eyebrow">EXPLORE</span><?php foreach (array('services' => 'Our services', 'health-safety' => 'Digital Health & Safety', 'about' => 'About us', 'how-we-work' => 'How we work') as $key => $label):
                    if (!aa_page_id($key)) {
                        continue;
                    } ?><a
                    href="<?php echo esc_url(aa_page_url($key)); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?>
        </div>
        <div>
            <a class="footer-talk button" href="<?php echo esc_url(aa_page_url('contact')); ?>">Let’s talk ↗</a>
            <a
                href="<?php echo esc_url('mailto:' . aa_setting('email')); ?>"><?php echo esc_html(aa_setting('email')); ?></a>
            <a href="<?php echo esc_url(aa_telephone_uri()); ?>"><?php echo esc_html(aa_setting('phone')); ?></a>
            <p><?php echo esc_html(aa_setting('location')); ?></p>
            <?php aa_instagram_link(); ?>
        </div>
    </div>
    <div class="wrap footer-bottom">
        <div class="aa-footer-company"><span>© <?php echo esc_html(wp_date('Y')); ?> ABHIKA AURA LTD · Company no.
                <?php echo esc_html(aa_setting('company_number')); ?></span><?php if (aa_setting('registered_office')): ?>
                <p>Registered office: <?php echo esc_html(aa_setting('registered_office')); ?></p>
            <?php endif; ?><?php if (aa_setting('jurisdiction')): ?>
                <p>Registered in <?php echo esc_html(aa_setting('jurisdiction')); ?>.</p><?php endif; ?>
        </div>
        <div class="footer-policies">
            <?php if (has_nav_menu('policies')) {
                wp_nav_menu(array('theme_location' => 'policies', 'container' => false, 'depth' => 1));
            } else {
                foreach (array('refund' => 'Refund & Cancellation', 'terms' => 'Terms & Conditions', 'privacy' => 'Privacy Policy', 'cookies' => 'Cookie Policy') as $key => $label) {
                    if (!aa_page_id($key)) {
                        continue;
                    }
                    echo '<a href="' . esc_url(aa_page_url($key)) . '">' . esc_html($label) . '</a>';
                }
            } ?>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>

</html>