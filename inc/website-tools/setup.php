<?php
if (!defined('ABSPATH')) {
    exit;
}
function aa_setup_admin_menu()
{
    add_theme_page('Abhika Aura Setup', 'Abhika Aura Setup', 'manage_options', 'abhika-aura-setup', 'aa_setup_screen');
}
add_action('admin_menu', 'aa_setup_admin_menu');
function aa_activation_notice()
{
    if (!current_user_can('manage_options') || !defined('ABHIKA_AURA_VERSION') || !aa_website_readiness()) {
        return;
    }
    echo '<div class="notice notice-warning"><p>Some Abhika Aura pages need attention. <a href="' . esc_url(admin_url('themes.php?page=abhika-aura-setup')) . '">Review website setup</a> for missing, unpublished or outdated pages. Existing edited content is preserved.</p></div>';
}
add_action('admin_notices', 'aa_activation_notice');
function aa_website_readiness()
{
    $issues = array();
    foreach (aa_page_definitions() as $key => $definition) {
        if (!aa_page_id($key)) {
            $issues[] = $definition['title'] . ': publish this page without a password to make it available to visitors.';
        }
    }
    $privacy = aa_page_id('privacy', true);
    if ($privacy && false !== strpos(get_post_field('post_content', $privacy), 'does not create enquiry records')) {
        $issues[] = 'Review the Privacy Policy: it still describes the previous email-only form. Accepted enquiries are now saved privately in WordPress.';
    }
    return $issues;
}
function aa_setup_screen()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $map = get_option('aa_page_map', array());
    ?>
    <div class="wrap">
        <h1>Abhika Aura Setup</h1>
        <p>Create the website pages and menus. Your edited page content is preserved. Unedited supplied pages can be
            refreshed to the current version.</p>
        <p><strong>Website tools <?php echo esc_html(AA_WEBSITE_TOOLS_VERSION); ?> · Server PHP
                <?php echo esc_html(PHP_VERSION); ?></strong></p>
        <p>Requires WordPress 6.9 or newer and PHP 8.3 or newer. Use a supported, patched PHP version supplied by your host.
            Uploading these files does not change your server’s PHP version.</p>
        <?php $result = get_transient('aa_setup_result_' . get_current_user_id());
        if (is_array($result) && empty($result['errors'])): ?>
            <div class="notice notice-success inline">
                <p>Setup completed. Review the page list below, then visit your website.</p>
            </div>
        <?php endif;
        $errors = is_array($result) && !empty($result['errors']) ? $result['errors'] : array();
        if ($errors): ?>
            <div class="notice notice-error inline">
                <p>Setup needs attention. The website is not fully ready:</p>
                <ul><?php foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                } ?></ul>
            </div>
        <?php endif;
        delete_transient('aa_setup_result_' . get_current_user_id()); ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="aa_create_pages">
            <?php wp_nonce_field('aa_create_pages', 'aa_setup_nonce'); ?>
            <p><label><input type="checkbox" name="aa_front_page" value="1" checked> Use the Abhika Aura Home page as the
                    website homepage.</label></p>
            <p><label><input type="checkbox" name="aa_assign_menus" value="1" checked> Assign Abhika Aura navigation and
                    policy menus to this theme.</label></p>
            <p>These choices change the homepage and menu assignments. Existing posts, pages, menus and edited Abhika Aura
                page content remain intact. If a page slug is already in use, a new Abhika Aura page is created with a
                different slug.</p>
            <?php submit_button('Create website pages and menus'); ?>
        </form>
        <h2>Your website pages</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (aa_page_definitions() as $key => $definition):
                    $id = aa_page_id($key, true); ?>
                    <tr>
                        <td><?php echo esc_html($definition['title']); ?></td>
                        <td><?php echo $id ? esc_html(aa_page_id($key) ? 'Published — available to visitors' : get_post_status($id) . ' — not publicly available') : 'Not created'; ?>
                        </td>
                        <td><?php if ($id): ?><a href="<?php echo esc_url(get_edit_post_link($id)); ?>">Edit</a> · <a
                                    href="<?php echo esc_url(get_permalink($id)); ?>">View</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h2>Company details and logo</h2>
        <p>Use <a href="<?php echo esc_url(admin_url('customize.php')); ?>">Appearance → Customize</a> to update the
            logo, contact details, Instagram account and company information. Use Pages to edit the page text and layouts.
            The supplied layouts are Custom HTML blocks; switch a block to its HTML view to edit it.</p>
        <h2>Enquiry email</h2>
        <p>Accepted enquiries are saved privately under <a
                href="<?php echo esc_url(admin_url('edit.php?post_type=aa_enquiry')); ?>">Enquiries</a> before an email
            notification is attempted. Only administrators can access them. Failed notifications are retried up to three
            total attempts; you can also resend them from the record. Email delivery depends on your host or SMTP settings,
            so check both the dashboard and your inbox.</p>
        <p>Records are automatically removed after 365 days by WordPress scheduled tasks. Move information needed for an
            active client project to your agreed client record system. WordPress Tools → Export Personal Data / Erase
            Personal Data includes the enquiry records.</p>
        <?php if (!defined('AA_TOOLS_PLUGIN_FILE')): ?>
            <p><strong>Install and activate the supplied Abhika Aura Website Tools plugin</strong> to keep your enquiry records
                accessible, links resolved and forms working if you change themes.</p><?php endif; ?>
        <p>Exclude the Let’s Talk page from full-page or CDN caching so its form security token stays current.</p>
        <h2>Before public use</h2>
        <p>Review the policy pages against your real email, hosting, plugin and retention arrangements. The supplied
            location is Leeds, LS9 8FE. Add the verified registered office disclosure and registration jurisdiction in the
            Customizer before public company trading use. No previous street address is included in this theme.</p>
    </div>
    <?php
}
function aa_import_website($use_home = true, $assign_menus = true)
{
    $map = get_option('aa_page_map', array());
    $map = is_array($map) ? $map : array();
    $errors = array();
    foreach (aa_page_definitions() as $key => $definition) {
        $id = aa_page_id($key, true);
        if (!$id) {
            $matches = get_posts(array('post_type' => 'page', 'post_status' => array('publish', 'draft', 'pending', 'private', 'future'), 'meta_key' => '_aa_starter_key', 'meta_value' => $key, 'numberposts' => 1, 'fields' => 'ids'));
            $id = $matches ? absint($matches[0]) : 0;
        }
        if (!$id) {
            $content = aa_default_content($key);
            if (!$content) {
                $errors[] = 'Missing starter content: ' . $definition['title'];
                continue;
            }
            $slug = $definition['slug'];
            if (get_page_by_path($slug, OBJECT, 'page')) {
                $slug = 'abhika-aura-' . $slug;
            }
            $id = wp_insert_post(wp_slash(array('post_type' => 'page', 'post_title' => $definition['title'], 'post_name' => $slug, 'post_status' => 'publish', 'post_content' => $content, 'comment_status' => 'closed', 'ping_status' => 'closed')), true);
            if (is_wp_error($id)) {
                $errors[] = $definition['title'] . ': ' . $id->get_error_message();
                continue;
            }
            update_post_meta($id, '_aa_starter_key', $key);
            if (defined('ABHIKA_AURA_VERSION')) {
                update_post_meta($id, '_wp_page_template', 'page-templates/full-width.php');
            }
            update_post_meta($id, '_aa_content_version', AA_WEBSITE_TOOLS_VERSION);
        } else {
            $old_content = get_post_field('post_content', $id);
            $hash_file = AA_TOOLS_PATH . '/starter-content/legacy-hashes.json';
            $legacy = is_readable($hash_file) ? json_decode(file_get_contents($hash_file), true) : array();
            if (isset($legacy[$key]) && hash_equals($legacy[$key], hash('sha256', $old_content))) {
                $updated = wp_update_post(wp_slash(array('ID' => $id, 'post_content' => aa_default_content($key))), true);
                if (is_wp_error($updated)) {
                    $errors[] = $definition['title'] . ': ' . $updated->get_error_message();
                } else {
                    update_post_meta($id, '_aa_content_version', AA_WEBSITE_TOOLS_VERSION);
                }
            }
        }
        $map[$key] = absint($id);
    }
    update_option('aa_page_map', $map);
    if ($use_home && aa_page_id('home')) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', absint($map['home']));
    }
    if (!get_option('wp_page_for_privacy_policy') && aa_page_id('privacy')) {
        update_option('wp_page_for_privacy_policy', absint($map['privacy']));
    }
    $menu_sets = array(
        'primary' => array('name' => 'Abhika Aura Main Navigation', 'items' => array('home' => 'Home', 'services' => 'Services', 'health-safety' => 'Health & Safety', 'about' => 'About', 'how-we-work' => 'How We Work')),
        'policies' => array('name' => 'Abhika Aura Footer Policies', 'items' => array('refund' => 'Refund & Cancellation', 'terms' => 'Terms & Conditions', 'privacy' => 'Privacy Policy', 'cookies' => 'Cookie Policy')),
    );
    $locations = get_theme_mod('nav_menu_locations', array());
    foreach ($menu_sets as $location => $menu_set) {
        $menu_id = absint(get_option('aa_menu_' . $location));
        $menu = $menu_id ? wp_get_nav_menu_object($menu_id) : false;
        if (!$menu) {
            $name = $menu_set['name'];
            if (wp_get_nav_menu_object($name)) {
                $name .= ' ' . wp_generate_password(6, false, false);
            }
            $menu_id = wp_create_nav_menu($name);
            if (is_wp_error($menu_id)) {
                $errors[] = $menu_id->get_error_message();
                continue;
            }
            update_option('aa_menu_' . $location, absint($menu_id));
        }
        $existing = wp_get_nav_menu_items($menu_id);
        $existing_ids = array();
        foreach ($existing ?: array() as $item) {
            $existing_ids[] = absint($item->object_id);
        }
        foreach ($menu_set['items'] as $key => $label) {
            if (empty($map[$key]) || !aa_page_id($key) || in_array(absint($map[$key]), $existing_ids, true)) {
                continue;
            }
            $result = wp_update_nav_menu_item($menu_id, 0, array('menu-item-title' => $label, 'menu-item-object' => 'page', 'menu-item-object-id' => absint($map[$key]), 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish'));
            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
            }
        }
        if ($assign_menus) {
            $locations[$location] = absint($menu_id);
        }
    }
    if ($assign_menus) {
        set_theme_mod('nav_menu_locations', $locations);
    }
    $errors = array_values(array_unique(array_merge($errors, aa_website_readiness())));
    if (!$errors) {
        update_option('aa_pages_created', AA_WEBSITE_TOOLS_VERSION);
    } else {
        delete_option('aa_pages_created');
    }
    return $errors;
}
function aa_setup_handle_request()
{
    if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) {
        wp_die('Use the website setup form.', '', array('response' => 405));
    }
    if (!current_user_can('manage_options') || !current_user_can('publish_pages')) {
        wp_die('You are not allowed to set up this theme.', '', array('response' => 403));
    }
    check_admin_referer('aa_create_pages', 'aa_setup_nonce');
    $errors = aa_import_website(isset($_POST['aa_front_page']), isset($_POST['aa_assign_menus']));
    set_transient('aa_setup_result_' . get_current_user_id(), array('errors' => $errors), 5 * MINUTE_IN_SECONDS);
    wp_safe_redirect(add_query_arg(array('page' => 'abhika-aura-setup', 'aa_setup' => $errors ? 'incomplete' : 'complete'), admin_url('themes.php')), 303);
    exit;
}
add_action('admin_post_aa_create_pages', 'aa_setup_handle_request');
