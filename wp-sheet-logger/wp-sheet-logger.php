<?php
/**
 * Plugin Name: WP Sheet Logger
 * Description: Logs updates to Google Sheets.
 */

add_action('upgrader_process_complete', 'log_update_to_google_sheet', 10, 2);

function log_update_to_google_sheet($upgrader_object, $options) {
    $webhook_url = 'YOUR_GOOGLE_APPS_SCRIPT_WEB_APP_URL'; // Paste your Web App URL here
    $site_name   = 'dinsajt.se'; // Name of the site (must match the tab name exactly)
    
    if (($options['action'] ?? '') !== 'update') return;

    $type = $options['type'] ?? '';
    
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
    
    // Log Translation
    if ($type === 'translation') {
        foreach (($upgrader_object->result ?? []) as $translation) {
            send_to_sheet($webhook_url, [
                'site_name' => $site_name,
                'date'      => current_time('Y-m-d'),
                'platform'  => 'WP',
                'action'    => 'Update',
                'type'      => 'Translations',
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