<?php
/**
 * Plugin Name: WP Sheet Logger
 * Description: Logs updates and lifecycle events to Google Sheets.
 */

define('WP_SHEET_LOGGER_DEPLOYMENT_ID', 'YOUR_DEPLOYMENT_ID'); // Paste your Google Apps Script Deployment ID here
define('WP_SHEET_LOGGER_WEB_APP_URL', 'https://script.google.com/macros/s/' . WP_SHEET_LOGGER_DEPLOYMENT_ID . '/exec');

add_action('upgrader_process_complete', 'log_update_to_google_sheet', 10, 2);

// Log plugin activation
add_action('activated_plugin', function($plugin, $network_wide) {
    $webhook_url = WP_SHEET_LOGGER_WEB_APP_URL;
    $site_name = wp_parse_url(home_url(), PHP_URL_HOST);
    $data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);

    send_to_sheet($webhook_url, [
        'site_name' => $site_name,
        'date'      => current_time('Y-m-d'),
        'platform'  => 'WP',
        'action'    => 'Activate',
        'type'      => 'Plugin',
        'name'      => $data['Name'] ?? $plugin,
        'note'      => '',
        'from_value'=> '',
        'to_value'  => $data['Version'] ?? ''
    ]);
}, 10, 2);

// Log plugin deactivation
add_action('deactivated_plugin', function($plugin, $network_wide) {
    $webhook_url = WP_SHEET_LOGGER_WEB_APP_URL;
    $site_name = wp_parse_url(home_url(), PHP_URL_HOST);
    $data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);

    send_to_sheet($webhook_url, [
        'site_name' => $site_name,
        'date'      => current_time('Y-m-d'),
        'platform'  => 'WP',
        'action'    => 'Deactivate',
        'type'      => 'Plugin',
        'name'      => $data['Name'] ?? $plugin,
        'note'      => '',
        'from_value'=> '',
        'to_value'  => $data['Version'] ?? ''
    ]);
}, 10, 2);

// Log theme activation (switch theme)
add_action('switch_theme', function($new_name, $new_theme, $old_theme) {
    $webhook_url = WP_SHEET_LOGGER_WEB_APP_URL;
    $site_name = wp_parse_url(home_url(), PHP_URL_HOST);

    send_to_sheet($webhook_url, [
        'site_name' => $site_name,
        'date'      => current_time('Y-m-d'),
        'platform'  => 'WP',
        'action'    => 'Activate',
        'type'      => 'Theme',
        'name'      => $new_theme->get('Name'),
        'note'      => '',
        'from_value'=> '',
        'to_value'  => $new_theme->get('Version')
    ]);
}, 10, 3);

function log_update_to_google_sheet($upgrader_object, $options) {
    $webhook_url = WP_SHEET_LOGGER_WEB_APP_URL;
    $site_name = wp_parse_url(home_url(), PHP_URL_HOST);
    
    $action = $options['action'] ?? '';
    $type = $options['type'] ?? '';

    // Handle new installations via upgrader
    if ($action === 'install') {
        if ($type === 'plugin' && !empty($options['plugin'])) {
            $plugin_file = $options['plugin'];
            $data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
            send_to_sheet($webhook_url, [
                'site_name' => $site_name,
                'date'      => current_time('Y-m-d'),
                'platform'  => 'WP',
                'action'    => 'Install',
                'type'      => 'Plugin',
                'name'      => $data['Name'] ?? $plugin_file,
                'note'      => '',
                'from_value'=> '',
                'to_value'  => $data['Version'] ?? ''
            ]);
        } elseif ($type === 'theme' && !empty($options['theme'])) {
            $theme_slug = $options['theme'];
            $theme = wp_get_theme($theme_slug);
            send_to_sheet($webhook_url, [
                'site_name' => $site_name,
                'date'      => current_time('Y-m-d'),
                'platform'  => 'WP',
                'action'    => 'Install',
                'type'      => 'Theme',
                'name'      => $theme->get('Name') ?: $theme_slug,
                'note'      => '',
                'from_value'=> '',
                'to_value'  => $theme->get('Version') ?? ''
            ]);
        }
        return;
    }

    // Handle deletions / uninstalls via upgrader
    if ($action === 'delete') {
        if ($type === 'plugin' && !empty($options['plugins'])) {
            foreach ($options['plugins'] as $plugin_file) {
                $full_path = WP_PLUGIN_DIR . '/' . $plugin_file;
                $data = file_exists($full_path) ? get_plugin_data($full_path) : [];
                $name = $data['Name'] ?? ucwords(str_replace(['-', '_', '/'], [' ', ' ', ' '], dirname($plugin_file)));

                send_to_sheet($webhook_url, [
                    'site_name' => $site_name,
                    'date'      => current_time('Y-m-d'),
                    'platform'  => 'WP',
                    'action'    => 'Uninstall',
                    'type'      => 'Plugin',
                    'name'      => $name,
                    'note'      => '',
                    'from_value'=> '',
                    'to_value'  => $data['Version'] ?? ''
                ]);
            }
        } elseif ($type === 'theme' && !empty($options['themes'])) {
            foreach ($options['themes'] as $theme_slug) {
                $theme = wp_get_theme($theme_slug);
                send_to_sheet($webhook_url, [
                    'site_name' => $site_name,
                    'date'      => current_time('Y-m-d'),
                    'platform'  => 'WP',
                    'action'    => 'Uninstall',
                    'type'      => 'Theme',
                    'name'      => $theme->exists() ? $theme->get('Name') : $theme_slug,
                    'note'      => '',
                    'from_value'=> '',
                    'to_value'  => $theme->get('Version') ?? ''
                ]);
            }
        }
        return;
    }

    // Handle updates
    if ($action !== 'update') {
        return;
    }

    // Log Core
    if ($type === 'core') {
        // Extract version safely if result is an object/array
        $core_version = '';
        if (is_string($upgrader_object->result)) {
            $core_version = $upgrader_object->result;
        } elseif (is_object($upgrader_object->result) && isset($upgrader_object->result->version)) {
            $core_version = $upgrader_object->result->version;
        } elseif (is_array($upgrader_object->result) && isset($upgrader_object->result['version'])) {
            $core_version = $upgrader_object->result['version'];
        }

        send_to_sheet($webhook_url, [
            'site_name' => $site_name,
            'date'      => current_time('Y-m-d'),
            'platform'  => 'WP',
            'action'    => 'Update',
            'type'      => 'CMS',
            'name'      => 'WordPress',
            'note'      => '',
            'from_value'=> '',
            'to_value'  => $core_version
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
    if ($type === 'translation' && !empty($options['translations'])) {
        foreach ($options['translations'] as $translation) {
            // Cast to array to safely access keys, as WP sometimes passes objects here
            $trans = (array) $translation;
            
            $trans_type = $trans['type'] ?? 'Unknown';
            $slug       = $trans['slug'] ?? 'Unknown';
            $language   = $trans['language'] ?? 'sv_SE';
            $version    = $trans['version'] ?? '';

            // Determine a readable name
            $name = $slug;
            if ($trans_type === 'core') {
                $name = 'WordPress';
            } elseif ($trans_type === 'theme') {
                $theme = wp_get_theme($slug);
                $name = $theme->exists() ? $theme->get('Name') : $slug;
            } elseif ($trans_type === 'plugin' && $slug !== 'Unknown') {
                // Convert slugs like 'yoast-seo' to 'Yoast Seo'
                $name = ucwords(str_replace('-', ' ', $slug));
            }

            send_to_sheet($webhook_url, [
                'site_name' => $site_name,
                'date'      => current_time('Y-m-d'),
                'platform'  => 'WP',
                'action'    => 'Update',
                'type'      => 'Translation',
                'name'      => $name,
                'note'      => $language,
                'from_value'=> '',
                'to_value'  => $version
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