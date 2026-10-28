<?php
if (!defined('ABSPATH')) {
    exit;
}
function aa_services()
{
    return array('Not sure yet — let’s discuss', 'IT consulting & architecture', 'Web & application development', 'Cloud & cybersecurity', 'AI & workflow automation', 'CRM & ERP solutions', 'Data & business growth', 'Digital Health and Safety');
}
function aa_request_scalar($input, $key)
{
    return isset($input[$key]) && is_string($input[$key]) ? wp_unslash($input[$key]) : '';
}
function aa_normalise_service($value)
{
    return strtolower(trim(preg_replace('/\s+/', ' ', str_replace('&', 'and', $value))));
}
function aa_form_stamp($time)
{
    return $time . ':' . hash_hmac('sha256', $time . '|aa-enquiry', wp_salt('nonce'));
}
function aa_stamp_is_valid($stamp)
{
    $parts = explode(':', $stamp);
    if (2 !== count($parts) || !ctype_digit($parts[0])) {
        return false;
    }
    $time = (int) $parts[0];
    return $time <= time() && $time >= time() - DAY_IN_SECONDS && hash_equals(aa_form_stamp($time), $stamp);
}
function aa_render_enquiry_form($values = array(), $errors = array())
{
    $values = is_array($values) ? $values : array();
    $errors = is_array($errors) ? $errors : array();
    $values = wp_parse_args($values, array('name' => '', 'company' => '', 'email' => '', 'phone' => '', 'service' => aa_services()[0], 'message' => '', 'submission_token' => ''));
    static $form_index = 0;
    $form_index++;
    $form_id = 1 === $form_index ? 'enquiry' : 'enquiry-' . $form_index;
    $field_prefix = 'aa-' . $form_index . '-';
    $submission_token = aa_submission_token_is_valid($values['submission_token']) ? $values['submission_token'] : aa_submission_token();
    $confirmation = aa_take_confirmation();
    if (!$values['service'] || !in_array($values['service'], aa_services(), true)) {
        $values['service'] = aa_services()[0];
    }
    if (!$errors && aa_request_scalar($_GET, 'service')) {
        $requested = aa_normalise_service(sanitize_text_field(aa_request_scalar($_GET, 'service')));
        foreach (aa_services() as $service) {
            if (aa_normalise_service($service) === $requested) {
                $values['service'] = $service;
                break;
            }
        }
    }
    ob_start(); ?>
    <form id="<?php echo esc_attr($form_id); ?>" class="aa-enquiry-form" method="post"
        action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <h2>Tell us about your requirement.</h2>
        <?php if ($confirmation && !$errors): ?>
            <div class="aa-success" role="status">
                <p>Thank you. Your enquiry has been received. Reference: <?php echo esc_html($confirmation); ?>. We aim to
                    respond within 24 hours.</p>
            </div><?php endif; ?>
        <?php if ($errors): ?>
            <div class="aa-enquiry-errors" role="alert" tabindex="-1">
                <p>Please check the following:</p>
                <ul><?php foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                } ?></ul>
            </div><?php endif; ?>
        <input type="hidden" name="action" value="aa_submit_enquiry">
        <input type="hidden" name="aa_enquiry_nonce" value="<?php echo esc_attr(wp_create_nonce('aa_submit_enquiry')); ?>">
        <input type="hidden" name="aa_submission_token" value="<?php echo esc_attr($submission_token); ?>">
        <input type="hidden" name="aa_form_stamp" value="<?php echo esc_attr(aa_form_stamp(time())); ?>">
        <div class="aa-form-trap" aria-hidden="true"><label>Leave this field empty<input type="text" name="aa_website"
                    value="" autocomplete="off" tabindex="-1"></label></div>
        <div class="form-grid">
            <label for="<?php echo esc_attr($field_prefix . 'name'); ?>">Full name *<input
                    id="<?php echo esc_attr($field_prefix . 'name'); ?>" name="aa_name" autocomplete="name" required
                    maxlength="100" value="<?php echo esc_attr($values['name']); ?>"></label>
            <label for="<?php echo esc_attr($field_prefix . 'company'); ?>">Company name<input
                    id="<?php echo esc_attr($field_prefix . 'company'); ?>" name="aa_company" autocomplete="organization"
                    maxlength="150" value="<?php echo esc_attr($values['company']); ?>"></label>
            <label for="<?php echo esc_attr($field_prefix . 'email'); ?>">Email address *<input
                    id="<?php echo esc_attr($field_prefix . 'email'); ?>" name="aa_email" type="email" autocomplete="email"
                    required maxlength="254" value="<?php echo esc_attr($values['email']); ?>"></label>
            <label for="<?php echo esc_attr($field_prefix . 'phone'); ?>">Phone / WhatsApp *<input
                    id="<?php echo esc_attr($field_prefix . 'phone'); ?>" name="aa_phone" type="tel" autocomplete="tel"
                    required minlength="6" maxlength="40" value="<?php echo esc_attr($values['phone']); ?>"></label>
        </div>
        <label for="<?php echo esc_attr($field_prefix . 'service'); ?>">Service of interest<select
                id="<?php echo esc_attr($field_prefix . 'service'); ?>"
                name="aa_service"><?php foreach (aa_services() as $service): ?>
                    <option value="<?php echo esc_attr($service); ?>" <?php selected($service, $values['service']); ?>>
                        <?php echo esc_html($service); ?>
                    </option><?php endforeach; ?>
            </select></label>
        <label for="<?php echo esc_attr($field_prefix . 'message'); ?>">What do you need help with? *<textarea
                id="<?php echo esc_attr($field_prefix . 'message'); ?>" name="aa_message" rows="5" required maxlength="5000"
                placeholder="Your goals, current challenges and what you would like to achieve…"><?php echo esc_textarea($values['message']); ?></textarea></label>
        <p class="form-note">Your enquiry is saved privately so we can respond, and an email notification is sent to our
            team. Please read our <a href="<?php echo esc_url(aa_page_url('privacy')); ?>">Privacy Policy</a>. Do not
            include passwords or
            sensitive personal information.</p>
        <button class="button" type="submit">Send enquiry ↗</button>
        <p class="form-note">Prefer email? <a
                href="<?php echo esc_url('mailto:' . aa_setting('email')); ?>"><?php echo esc_html(aa_setting('email')); ?></a>
        </p>
    </form>
    <?php return ob_get_clean();
}
add_shortcode('abhika_aura_enquiry_form', 'aa_render_enquiry_form');
function aa_validate_enquiry($input)
{
    $raw = array();
    foreach (array('name', 'company', 'email', 'phone', 'service', 'message') as $key) {
        $raw[$key] = aa_request_scalar($input, 'aa_' . $key);
    }
    $values = array();
    foreach ($raw as $key => $value) {
        $values[$key] = 'message' === $key ? sanitize_textarea_field($value) : sanitize_text_field($value);
    }
    $errors = array();
    foreach (array('name' => 100, 'company' => 150, 'email' => 254, 'phone' => 40, 'message' => 5000) as $key => $limit) {
        $length = function_exists('mb_strlen') ? mb_strlen($raw[$key], 'UTF-8') : strlen($raw[$key]);
        if ($length > $limit) {
            $errors[] = ucfirst($key) . ' must be no longer than ' . $limit . ' characters.';
        }
    }
    if (!$values['name']) {
        $errors[] = 'Enter your name.';
    }
    if (!$values['message']) {
        $errors[] = 'Describe what you need help with.';
    }
    if (!is_email($raw['email']) || preg_match('/[\r\n]/', $raw['email'])) {
        $errors[] = 'Enter a valid email address.';
    }
    $phone_digits = preg_replace('/\D/', '', $values['phone']);
    if (!preg_match('/^\+?[0-9().\s-]{6,40}$/D', $values['phone']) || strlen($phone_digits) < 6 || strlen($phone_digits) > 15) {
        $errors[] = 'Enter a valid telephone number.';
    }
    if (!in_array($values['service'], aa_services(), true)) {
        $errors[] = 'Choose one of the listed services.';
    }
    $values['email'] = sanitize_email($values['email']);
    return array('values' => $values, 'errors' => $errors);
}
function aa_rate_key()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return '';
    }
    return 'aa_enquiry_rate_' . hash_hmac('sha256', $ip, wp_salt('auth'));
}
function aa_enquiry_error_response($values, $errors, $status = 400)
{
    status_header($status);
    nocache_headers();
    aa_prepare_public_form_response();
    get_header();
    echo '<section class="wrap section aa-regular-page"><h1>Let’s check<br>your enquiry.</h1>';
    echo aa_render_enquiry_form($values, $errors); // Renderer escapes all field values and messages.
    echo '</section>';
    get_footer();
    exit;
}
function aa_prepare_public_form_response()
{
    // admin-post.php is an admin endpoint, but these responses are public pages.
    // A front screen lets WordPress enqueue front-end assets without admin-bar callbacks.
    if (is_admin() && function_exists('set_current_screen')) {
        set_current_screen('front');
    }
    show_admin_bar(false);
}
function aa_submit_enquiry()
{
    if (!isset($_SERVER['REQUEST_METHOD']) || 'POST' !== $_SERVER['REQUEST_METHOD']) {
        aa_enquiry_error_response(array(), array('Use the enquiry form to send your message.'), 405);
    }
    $validated = aa_validate_enquiry($_POST);
    $values = $validated['values'];
    $values['submission_token'] = aa_request_scalar($_POST, 'aa_submission_token');
    $errors = $validated['errors'];
    $nonce = aa_request_scalar($_POST, 'aa_enquiry_nonce');
    if (!wp_verify_nonce($nonce, 'aa_submit_enquiry') || !aa_stamp_is_valid(aa_request_scalar($_POST, 'aa_form_stamp'))) {
        aa_enquiry_error_response($values, array('The form expired or could not be verified. Review the form below and submit it again.'), 403);
    }
    if (aa_request_scalar($_POST, 'aa_website')) {
        aa_enquiry_error_response(array(), array('The submission could not be accepted. Please contact us by email.'), 400);
    }
    if ($errors) {
        aa_enquiry_error_response($values, $errors);
    }
    if (!aa_submission_token_is_valid($values['submission_token'])) {
        aa_enquiry_error_response($values, array('The form could not be verified. Please review it and submit again.'), 403);
    }
    $lock_key = 'aa_submission_' . hash('sha256', $values['submission_token']);
    $previous = get_option($lock_key);
    if (is_array($previous) && !empty($previous['id']) && 'aa_enquiry' === get_post_type($previous['id'])) {
        if ('pending' === get_post_meta($previous['id'], '_aa_delivery_status', true)) {
            aa_deliver_enquiry(absint($previous['id']));
        }
        aa_enquiry_success(absint($previous['id']));
    }
    if (is_array($previous) && empty($previous['id']) && !empty($previous['created']) && $previous['created'] < time() - 10 * MINUTE_IN_SECONDS) {
        $saved = get_posts(array('post_type' => 'aa_enquiry', 'post_status' => array('private', 'trash'), 'meta_key' => '_aa_submission_lock', 'meta_value' => $lock_key, 'numberposts' => 1, 'fields' => 'ids'));
        if ($saved) {
            update_option($lock_key, array('created' => time(), 'id' => absint($saved[0])), false);
            if ('pending' === get_post_meta($saved[0], '_aa_delivery_status', true)) {
                aa_deliver_enquiry(absint($saved[0]));
            }
            aa_enquiry_success(absint($saved[0]));
        }
        delete_option($lock_key);
    }
    $rate_key = aa_rate_key();
    $rate = $rate_key ? get_transient($rate_key) : false;
    $rate = is_array($rate) ? $rate : array('count' => 0, 'reset' => time() + HOUR_IN_SECONDS);
    if (time() >= $rate['reset']) {
        $rate = array('count' => 0, 'reset' => time() + HOUR_IN_SECONDS);
    }
    if ($rate['count'] >= 10) {
        aa_enquiry_error_response($values, array('Too many enquiries have been submitted from this connection. Please try again later or contact us by email.'), 429);
    }
    $recipient = sanitize_email(aa_setting('email'));
    if (!is_email($recipient)) {
        aa_enquiry_error_response($values, array('The enquiry service is unavailable. Please contact us by telephone.'), 503);
    }
    if (!add_option($lock_key, array('created' => time(), 'id' => 0), '', false)) {
        aa_enquiry_error_response($values, array('This enquiry is already being processed. Please wait a moment before submitting again.'), 409);
    }
    $record = aa_save_enquiry($values, $lock_key);
    if (is_wp_error($record)) {
        delete_option($lock_key);
        aa_enquiry_error_response($values, array('We could not save your enquiry. Please try again or contact us directly.'), 503);
    }
    update_option($lock_key, array('created' => time(), 'id' => $record), false);
    if ($rate_key) {
        $rate['count']++;
        set_transient($rate_key, $rate, max(1, $rate['reset'] - time()));
    }
    aa_deliver_enquiry($record);
    aa_enquiry_success($record);
}
add_action('admin_post_aa_submit_enquiry', 'aa_submit_enquiry');
add_action('admin_post_nopriv_aa_submit_enquiry', 'aa_submit_enquiry');
function aa_submission_token()
{
    $uuid = wp_generate_uuid4();
    return $uuid . ':' . hash_hmac('sha256', $uuid, wp_salt('nonce'));
}
function aa_submission_token_is_valid($token)
{
    if (!is_string($token) || !preg_match('/^([a-f0-9-]{36}):([a-f0-9]{64})$/D', $token, $parts)) {
        return false;
    }
    return hash_equals(hash_hmac('sha256', $parts[1], wp_salt('nonce')), $parts[2]);
}
function aa_enquiry_success($id)
{
    $token = bin2hex(random_bytes(24));
    set_transient('aa_confirmation_' . hash('sha256', $token), 'AA-' . absint($id), 15 * MINUTE_IN_SECONDS);
    $url = aa_page_id('contact') ? aa_page_url('contact') : home_url('/');
    if (!aa_page_id('contact')) {
        status_header(200);
        nocache_headers();
        aa_prepare_public_form_response();
        get_header();
        echo '<section class="wrap section"><h1>Thank you.</h1><p>Your enquiry has been received. Reference: ' . esc_html('AA-' . absint($id)) . '. We aim to respond within 24 hours.</p></section>';
        get_footer();
        exit;
    }
    wp_safe_redirect(add_query_arg('aa_enquiry', $token, $url) . '#enquiry', 303);
    exit;
}
function aa_take_confirmation()
{
    $token = aa_request_scalar($_GET, 'aa_enquiry');
    if (!preg_match('/^[a-f0-9]{48}$/D', $token)) {
        return '';
    }
    $key = 'aa_confirmation_' . hash('sha256', $token);
    $value = get_transient($key);
    delete_transient($key);
    return is_string($value) ? $value : '';
}
function aa_enquiry_assets()
{
    wp_enqueue_style('aa-enquiry', AA_TOOLS_URL . '/assets/enquiry.css', array(), AA_WEBSITE_TOOLS_VERSION);
    wp_enqueue_script('aa-enquiry', AA_TOOLS_URL . '/assets/enquiry.js', array(), AA_WEBSITE_TOOLS_VERSION, true);
}
add_action('wp_enqueue_scripts', 'aa_enquiry_assets');
