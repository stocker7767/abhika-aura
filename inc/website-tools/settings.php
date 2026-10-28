<?php
if (!defined('ABSPATH')) {
    exit;
}
function aa_setting_defaults()
{
    return array('brand' => 'ABHIKA AURA', 'tagline' => 'BUILT FOR WHAT’S NEXT', 'email' => 'insights@abhikaaura.com', 'phone' => '+44 7442 302822', 'location' => 'Leeds, LS9 8FE', 'company_number' => '17186771', 'instagram' => 'abhikaaura.solutions', 'registered_office' => '', 'jurisdiction' => '');
}
function aa_clean_instagram($value)
{
    return substr(preg_replace('/[^a-zA-Z0-9_.]/', '', ltrim(trim(is_string($value) ? $value : ''), '@')), 0, 30);
}
function aa_setting($key)
{
    $defaults = aa_setting_defaults();
    if (!isset($defaults[$key])) {
        return '';
    }
    $settings = get_option('aa_company_settings', array());
    $value = is_array($settings) && isset($settings[$key]) ? $settings[$key] : get_theme_mod('aa_' . $key, $defaults[$key]);
    return is_scalar($value) ? (string) $value : $defaults[$key];
}
function aa_migrate_company_settings()
{
    if (!defined('ABHIKA_AURA_VERSION')) {
        return;
    }
    $settings = get_option('aa_company_settings', array());
    $settings = is_array($settings) ? $settings : array();
    $changed = false;
    foreach (aa_setting_defaults() as $key => $default) {
        if (!array_key_exists($key, $settings)) {
            $settings[$key] = get_theme_mod('aa_' . $key, $default);
            $changed = true;
        }
    }
    if ($changed) {
        update_option('aa_company_settings', $settings, false);
    }
    if (!get_option('aa_company_logo_id') && get_theme_mod('custom_logo')) {
        update_option('aa_company_logo_id', absint(get_theme_mod('custom_logo')), false);
    }
}
add_action('init', 'aa_migrate_company_settings', 5);
function aa_save_company_logo()
{
    if (defined('ABHIKA_AURA_VERSION')) {
        update_option('aa_company_logo_id', absint(get_theme_mod('custom_logo')), false);
    }
}
add_action('customize_save_after', 'aa_save_company_logo');
function aa_customize_register($manager)
{
    $manager->add_section('aa_company', array('title' => __('Abhika Aura company details', 'abhika-aura'), 'priority' => 30));
    $labels = array('brand' => 'Brand name', 'tagline' => 'Tagline', 'email' => 'Contact and enquiry recipient email', 'phone' => 'Telephone', 'location' => 'Displayed city and postcode', 'company_number' => 'Company number', 'instagram' => 'Instagram handle (without @)', 'registered_office' => 'Registered office disclosure', 'jurisdiction' => 'Registration jurisdiction');
    foreach (aa_setting_defaults() as $key => $default) {
        $setting = 'aa_company_settings[' . $key . ']';
        $sanitize = 'email' === $key ? 'sanitize_email' : ('instagram' === $key ? 'aa_clean_instagram' : 'sanitize_text_field');
        $manager->add_setting($setting, array('type' => 'option', 'default' => aa_setting($key), 'capability' => 'manage_options', 'sanitize_callback' => $sanitize, 'transport' => 'refresh'));
        $args = array('label' => $labels[$key], 'section' => 'aa_company', 'type' => 'email' === $key ? 'email' : 'text');
        if ('registered_office' === $key) {
            $args['description'] = 'Use the registered office address approved by the company for publication.';
        }
        if ('jurisdiction' === $key) {
            $args['description'] = 'Enter the verified place of registration, for example England and Wales.';
        }
        $manager->add_control($setting, $args);
    }
}
add_action('customize_register', 'aa_customize_register');
