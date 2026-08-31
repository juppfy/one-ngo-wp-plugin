<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$one_ngo_list_keys = get_option('one_ngo_list_transient_keys', []);
if (is_array($one_ngo_list_keys)) {
    foreach ($one_ngo_list_keys as $one_ngo_list_key) {
        if (is_string($one_ngo_list_key) && str_starts_with($one_ngo_list_key, 'one_ngo_list_')) {
            delete_transient($one_ngo_list_key);
        }
    }
}

$one_ngo_options = [
    'one_ngo_organization_id',
    'one_ngo_api_token',
    'one_ngo_api_base',
    'one_ngo_routes',
    'one_ngo_page_ids',
    'one_ngo_list_transient_keys',
];

foreach ($one_ngo_options as $one_ngo_option) {
    delete_option($one_ngo_option);
}

$one_ngo_transients = [
    'one_ngo_heartbeat',
    'one_ngo_me',
    'one_ngo_brand',
    'one_ngo_campaigns',
    'one_ngo_events',
    'one_ngo_stories',
];

foreach ($one_ngo_transients as $one_ngo_transient) {
    delete_transient($one_ngo_transient);
}
