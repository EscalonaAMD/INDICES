<?php
/**
 * Uninstall handler for Índices ESTAR.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// Only wipe data if the user explicitly enabled it in plugin settings.
if (!get_option('indices_estar_delete_on_uninstall')) {
    return;
}


global $wpdb;

$tables = [
    $wpdb->prefix . 'estar_index_items',
    $wpdb->prefix . 'estar_indices',
    $wpdb->prefix . 'estar_index_groups',
];

foreach ($tables as $t) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query("DROP TABLE IF EXISTS {$t}");
}

delete_transient('indices_estar_cache_ver');


delete_option('indices_estar_delete_on_uninstall');
