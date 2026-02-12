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

// ✅ CORREGIDO: Definir explícitamente las tablas válidas
$valid_tables = [
    $wpdb->prefix . 'estar_index_items',
    $wpdb->prefix . 'estar_indices',
    $wpdb->prefix . 'estar_index_groups',
];

$tables = [
    $wpdb->prefix . 'estar_index_items',
    $wpdb->prefix . 'estar_indices',
    $wpdb->prefix . 'estar_index_groups',
];

foreach ($tables as $t) {
    // ✅ CORREGIDO: Validar que la tabla está en la lista de tablas válidas
    if (!in_array($t, $valid_tables, true)) {
        continue;
    }
    
    // ✅ CORREGIDO: Escapar el nombre de tabla usando backticks y esc_sql
    $escaped_table = esc_sql($t);
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query("DROP TABLE IF EXISTS `{$escaped_table}`");
}

delete_transient('indices_estar_cache_ver');


delete_option('indices_estar_delete_on_uninstall');
