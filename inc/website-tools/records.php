<?php
/** Private enquiry records, email notifications and personal-data tools. */
if (!defined('ABSPATH')) {
    exit;
}
function aa_register_enquiries()
{
    $caps = array_fill_keys(array('edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'delete_posts', 'delete_others_posts', 'read_private_posts', 'edit_private_posts', 'edit_published_posts', 'delete_private_posts', 'delete_published_posts', 'read'), 'manage_options');
    $caps['create_posts'] = 'do_not_allow';
    $caps['publish_posts'] = 'do_not_allow';
    register_post_type('aa_enquiry', array(
        'labels' => array('name' => 'Enquiries', 'singular_name' => 'Enquiry', 'edit_item' => 'View enquiry', 'all_items' => 'All enquiries', 'not_found' => 'No enquiries found.'),
        'public' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'show_ui' => true,
        'show_in_rest' => false,
        'show_in_nav_menus' => false,
        'show_in_admin_bar' => false,
        'rewrite' => false,
        'query_var' => false,
        'supports' => false,
        'can_export' => false,
        'menu_icon' => 'dashicons-email-alt',
        'capabilities' => $caps,
        'map_meta_cap' => false,
    ));
    if (!wp_next_scheduled('aa_enquiry_daily_cleanup')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'aa_enquiry_daily_cleanup');
    }
}
add_action('init', 'aa_register_enquiries');
function aa_enquiry_private_status($data)
{
    if ('aa_enquiry' === $data['post_type'] && 'trash' !== $data['post_status']) {
        $data['post_status'] = 'private';
    }
    return $data;
}
add_filter('wp_insert_post_data', 'aa_enquiry_private_status');
function aa_save_enquiry($values, $lock_key)
{
    $fields = array_intersect_key($values, array_flip(array('name', 'company', 'email', 'phone', 'service')));
    return wp_insert_post(wp_slash(array(
        'post_type' => 'aa_enquiry',
        'post_status' => 'private',
        'post_author' => 0,
        'post_title' => $values['name'] . ' — ' . $values['service'],
        'post_content' => $values['message'],
        'comment_status' => 'closed',
        'ping_status' => 'closed',
        'meta_input' => array('_aa_fields' => $fields, '_aa_email' => $values['email'], '_aa_delivery_status' => 'pending', '_aa_attempts' => 0, '_aa_submission_lock' => $lock_key),
    )), true);
}
function aa_deliver_enquiry($id)
{
    $id = absint($id);
    if ('aa_enquiry' !== get_post_type($id) || 'private' !== get_post_status($id) || 'sent' === get_post_meta($id, '_aa_delivery_status', true) || (int) get_post_meta($id, '_aa_attempts', true) >= 3) {
        return;
    }
    $lock = 'aa_sending_' . $id;
    $started = (int) get_option($lock);
    if ($started && $started < time() - 10 * MINUTE_IN_SECONDS) {
        delete_option($lock);
    }
    if (!add_option($lock, time(), '', false)) {
        return;
    }
    try {
        $fields = get_post_meta($id, '_aa_fields', true);
        if (!is_array($fields)) {
            update_post_meta($id, '_aa_delivery_status', 'failed');
            return;
        }
        $attempts = (int) get_post_meta($id, '_aa_attempts', true) + 1;
        update_post_meta($id, '_aa_attempts', $attempts);
        $recipient = sanitize_email(aa_setting('email'));
        $message = 'New website enquiry AA-' . $id . "\n\n";
        foreach (array('name' => 'Name', 'company' => 'Company', 'email' => 'Email', 'phone' => 'Phone', 'service' => 'Service') as $key => $label) {
            $message .= $label . ': ' . (!empty($fields[$key]) ? $fields[$key] : 'Not provided') . "\n";
        }
        $message .= "\nRequirement:\n" . get_post_field('post_content', $id) . "\n\nView saved record: " . admin_url('post.php?post=' . $id . '&action=edit');
        $sent = is_email($recipient) && is_email($fields['email']) && wp_mail($recipient, 'Website enquiry AA-' . $id . ' — ' . $fields['service'], $message, array('Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $fields['email']));
        if ($sent) {
            update_post_meta($id, '_aa_delivery_status', 'sent');
            wp_clear_scheduled_hook('aa_retry_enquiry_email', array($id));
        } elseif ($attempts < 3) {
            $scheduled = wp_schedule_single_event(time() + (1 === $attempts ? 5 : 15) * MINUTE_IN_SECONDS, 'aa_retry_enquiry_email', array($id), true);
            update_post_meta($id, '_aa_delivery_status', true === $scheduled || wp_next_scheduled('aa_retry_enquiry_email', array($id)) ? 'retry' : 'failed');
        } else {
            update_post_meta($id, '_aa_delivery_status', 'failed');
            wp_clear_scheduled_hook('aa_retry_enquiry_email', array($id));
        }
    } finally {
        delete_option($lock);
    }
}
add_action('aa_retry_enquiry_email', 'aa_deliver_enquiry');
function aa_enquiry_columns($columns)
{
    return array('cb' => $columns['cb'], 'title' => 'Enquiry', 'aa_service' => 'Service', 'aa_email_status' => 'Email notification', 'date' => 'Received');
}
add_filter('manage_aa_enquiry_posts_columns', 'aa_enquiry_columns');
function aa_delivery_label($status)
{
    $labels = array('pending' => 'Pending notification', 'retry' => 'Retry scheduled — record saved', 'sent' => 'Accepted by mail service', 'failed' => 'Notification failed — record saved');
    return $labels[$status] ?? 'Awaiting notification';
}
function aa_enquiry_column($column, $id)
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $fields = get_post_meta($id, '_aa_fields', true);
    if ('aa_service' === $column) {
        echo esc_html(is_array($fields) ? ($fields['service'] ?? '') : '');
    } elseif ('aa_email_status' === $column) {
        echo esc_html(aa_delivery_label(get_post_meta($id, '_aa_delivery_status', true)));
    }
}
add_action('manage_aa_enquiry_posts_custom_column', 'aa_enquiry_column', 10, 2);
function aa_enquiry_row_actions($actions, $post)
{
    if ('aa_enquiry' === $post->post_type) {
        unset($actions['inline hide-if-no-js'], $actions['view']);
        if (isset($actions['edit'])) {
            $actions['edit'] = '<a href="' . esc_url(get_edit_post_link($post->ID)) . '">View record</a>';
        }
    }
    return $actions;
}
add_filter('post_row_actions', 'aa_enquiry_row_actions', 10, 2);
function aa_enquiry_meta_boxes()
{
    remove_meta_box('submitdiv', 'aa_enquiry', 'side');
    add_meta_box('aa-enquiry-details', 'Saved enquiry', 'aa_enquiry_details', 'aa_enquiry', 'normal', 'high');
}
add_action('add_meta_boxes_aa_enquiry', 'aa_enquiry_meta_boxes');
function aa_enquiry_details($post)
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $fields = get_post_meta($post->ID, '_aa_fields', true);
    echo '<p><strong>Reference: ' . esc_html('AA-' . $post->ID) . '</strong></p><dl>';
    foreach (array('name' => 'Name', 'company' => 'Company', 'email' => 'Email', 'phone' => 'Phone', 'service' => 'Service') as $key => $label) {
        echo '<dt><strong>' . esc_html($label) . '</strong></dt><dd style="margin:4px 0 16px">' . esc_html(is_array($fields) ? ($fields[$key] ?? '') : '') . '</dd>';
    }
    echo '</dl><h3>Message</h3><div style="white-space:pre-wrap;overflow-wrap:anywhere">' . esc_html($post->post_content) . '</div><hr>';
    echo '<p><strong>Email notification:</strong> ' . esc_html(aa_delivery_label(get_post_meta($post->ID, '_aa_delivery_status', true))) . '</p>';
    echo '<p>“Accepted by mail service” confirms processing by your mail configuration; it does not prove inbox delivery. The saved record remains available here.</p>';
    $resend = wp_nonce_url(admin_url('admin-post.php?action=aa_resend_enquiry&id=' . $post->ID), 'aa_resend_' . $post->ID);
    echo '<p><a class="button" href="' . esc_url($resend) . '">Resend notification</a> <a class="button" href="' . esc_url(get_delete_post_link($post->ID)) . '">Move to trash</a></p>';
}
function aa_resend_enquiry()
{
    $id = isset($_GET['id']) && is_scalar($_GET['id']) ? absint($_GET['id']) : 0;
    if (!current_user_can('manage_options') || 'aa_enquiry' !== get_post_type($id) || 'private' !== get_post_status($id)) {
        wp_die('You cannot access this enquiry.', '', array('response' => 403));
    }
    check_admin_referer('aa_resend_' . $id);
    wp_clear_scheduled_hook('aa_retry_enquiry_email', array($id));
    update_post_meta($id, '_aa_delivery_status', 'pending');
    update_post_meta($id, '_aa_attempts', 0);
    aa_deliver_enquiry($id);
    wp_safe_redirect(get_edit_post_link($id, 'raw'), 303);
    exit;
}
add_action('admin_post_aa_resend_enquiry', 'aa_resend_enquiry');
function aa_enquiry_admin_notice()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $failed = get_posts(array('post_type' => 'aa_enquiry', 'post_status' => 'private', 'meta_key' => '_aa_delivery_status', 'meta_value' => 'failed', 'numberposts' => 1, 'fields' => 'ids'));
    if ($failed) {
        echo '<div class="notice notice-warning"><p>An Abhika Aura email notification failed. The enquiry is saved. <a href="' . esc_url(admin_url('edit.php?post_type=aa_enquiry')) . '">Review Enquiries</a>, check your mail settings and resend from the record.</p></div>';
    }
}
add_action('admin_notices', 'aa_enquiry_admin_notice');
function aa_delete_enquiry_resources($id, $post)
{
    if ('aa_enquiry' !== $post->post_type) {
        return;
    }
    $lock = get_post_meta($id, '_aa_submission_lock', true);
    if (is_string($lock) && 0 === strpos($lock, 'aa_submission_')) {
        delete_option($lock);
    }
    delete_option('aa_sending_' . absint($id));
    wp_clear_scheduled_hook('aa_retry_enquiry_email', array(absint($id)));
}
add_action('before_delete_post', 'aa_delete_enquiry_resources', 10, 2);
function aa_enquiry_cleanup()
{
    $ids = get_posts(array('post_type' => 'aa_enquiry', 'post_status' => array('private', 'trash'), 'numberposts' => 100, 'fields' => 'ids', 'date_query' => array(array('before' => gmdate('Y-m-d H:i:s', time() - 365 * DAY_IN_SECONDS), 'column' => 'post_date_gmt'))));
    foreach ($ids as $id) {
        wp_delete_post($id, true);
    }
    if (count($ids) === 100 && !wp_next_scheduled('aa_enquiry_cleanup_batch')) {
        wp_schedule_single_event(time() + MINUTE_IN_SECONDS, 'aa_enquiry_cleanup_batch');
    }
}
add_action('aa_enquiry_daily_cleanup', 'aa_enquiry_cleanup');
add_action('aa_enquiry_cleanup_batch', 'aa_enquiry_cleanup');
function aa_privacy_enquiry_ids($email, $page = 1)
{
    return get_posts(array('post_type' => 'aa_enquiry', 'post_status' => array('private', 'trash'), 'meta_key' => '_aa_email', 'meta_value' => sanitize_email($email), 'numberposts' => 50, 'offset' => (max(1, (int) $page) - 1) * 50, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids'));
}
function aa_privacy_export_enquiries($email, $page = 1)
{
    $ids = aa_privacy_enquiry_ids($email, $page);
    $data = array();
    foreach ($ids as $id) {
        $fields = get_post_meta($id, '_aa_fields', true);
        $items = array();
        foreach ((is_array($fields) ? $fields : array()) as $name => $value) {
            $items[] = array('name' => ucfirst($name), 'value' => $value);
        }
        $items[] = array('name' => 'Message', 'value' => get_post_field('post_content', $id));
        $data[] = array('group_id' => 'aa-enquiries', 'group_label' => 'Abhika Aura enquiries', 'item_id' => 'aa-enquiry-' . $id, 'data' => $items);
    }
    return array('data' => $data, 'done' => count($ids) < 50);
}
function aa_privacy_erase_enquiries($email, $page = 1)
{
    // Deleted results change offsets, so always erase the first remaining batch.
    $ids = aa_privacy_enquiry_ids($email, 1);
    $removed = false;
    $retained = false;
    foreach ($ids as $id) {
        if (wp_delete_post($id, true)) {
            $removed = true;
        } else {
            $retained = true;
        }
    }
    return array('items_removed' => $removed, 'items_retained' => $retained, 'messages' => $retained ? array('Some enquiry records could not be erased; an administrator should review them.') : array(), 'done' => $retained || count($ids) < 50);
}
function aa_register_privacy_exporter($exporters)
{
    $exporters['aa-enquiries'] = array('exporter_friendly_name' => 'Abhika Aura enquiries', 'callback' => 'aa_privacy_export_enquiries');
    return $exporters;
}
function aa_register_privacy_eraser($erasers)
{
    $erasers['aa-enquiries'] = array('eraser_friendly_name' => 'Abhika Aura enquiries', 'callback' => 'aa_privacy_erase_enquiries');
    return $erasers;
}
add_filter('wp_privacy_personal_data_exporters', 'aa_register_privacy_exporter');
add_filter('wp_privacy_personal_data_erasers', 'aa_register_privacy_eraser');
