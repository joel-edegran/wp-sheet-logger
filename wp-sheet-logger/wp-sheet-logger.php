<?php
/**
 * Plugin Name: WP Sheet Logger
 * Description: Logs updates to Google Sheets.
 */

define('WP_SHEET_LOGGER_DEPLOYMENT_ID', 'YOUR_DEPLOYMENT_ID'); // Paste your Google Apps Script Deployment ID here
define('WP_SHEET_LOGGER_WEB_APP_URL', 'https://script.google.com/macros/s/' . WP_SHEET_LOGGER_DEPLOYMENT_ID . '/exec');

add_action('upgrader_process_complete', 'log_update_to_google_sheet', 10, 2);

function log_update_to_google_sheet($upgrader_object, $options) {
    $webhook_url = WP_SHEET_LOGGER_WEB_APP_URL;
    $site_name = wp_parse_url(home_url(), PHP_URL_HOST);
    
    if (($options['action'] ?? '') !== 'update') return;

    $type = $options['type'] ?? '';
    
    // Log Core
    if ($type === 'core') {
        send_to_sheet($webhook_url, [
            'site_name' => $site_name,
            'date'      => current_time('Y-m-d'),
            'platform'  => 'WP',
            'action'    => 'Update',
            'type'      => 'CMS',
            'name'      => 'WordPress',
            'note'      => '',
            'from_value'=> '',
            'to_value'  => $upgrader_object->result ?? ''
        ]);
    }
    
    // Log Plugins
    if ($type === 'plugin' && !empty($options['plugins'])) {
        foreach ($options['plugins'] as $plugin_file) {
            $data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
            send_to_sheet($webhook_url, [
                'site_name' => $site_name,
                'date'      => current_time('Y-m-d'),
                'platform'  => 'WP',
                'action'    => 'Update',
                'type'      => 'Plugin',
                'name'      => $data['Name'] ?? $plugin_file,
                'note'      => '',
                'from_value'=> '',
                'to_value'  => $data['Version'] ?? ''
            ]);
        }
    }
    
    // Log Themes
    if ($type === 'theme' && !empty($options['themes'])) {
        foreach ($options['themes'] as $theme_slug) {
            $theme = wp_get_theme($theme_slug);
            send_to_sheet($webhook_url, [
                'site_name' => $site_name,
                'date'      => current_time('Y-m-d'),
                'platform'  => 'WP',
                'action'    => 'Update',
                'type'      => 'Theme',
                'name'      => $theme->get('Name') ?: $theme_slug,
                'note'      => '',
                'from_value'=> '',
                'to_value'  => $theme->get('Version') ?? ''
            ]);
        }
    }
    
    // Log Translation
    if ($type === 'translation') {
        foreach (($upgrader_object->result ?? []) as $translation) {
            send_to_sheet($webhook_url, [
                'site_name' => $site_name,
                'date'      => current_time('Y-m-d'),
                'platform'  => 'WP',
                'action'    => 'Update',
                'type'      => 'Translation',
                'name'      => $translation['name'] ?? 'Unknown',
                'note'      => $translation['language'] ?? 'sv_SE',
                'from_value'=> '',
                'to_value'  => ''
            ]);
        }
    }
}

function send_to_sheet($url, $data) {
    wp_remote_post($url, [
        'blocking' => false,
        'headers'  => ['Content-Type' => 'application/json'],
        'body'     => json_encode($data)
    ]);
}