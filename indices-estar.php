<?php
/**
 * Plugin Name:       Índices ESTAR
 * Description:       Gestión de índices (grupos) y sus números con frontend interactivo.
 * Version:           2.0.5
 * Author:            ESTAR
 * Text Domain:       indices-estar
 * Domain Path:       /languages
 */
if (!defined('ABSPATH')) exit;

define('INDICES_ESTAR_VERSION', '2.0.5');
define('INDICES_ESTAR_PATH', plugin_dir_path(__FILE__));
define('INDICES_ESTAR_URL', plugin_dir_url(__FILE__));

require_once INDICES_ESTAR_PATH . 'includes/helpers.php';
require_once INDICES_ESTAR_PATH . 'includes/class-indices-estar-db.php';
require_once INDICES_ESTAR_PATH . 'includes/class-indices-estar-ajax.php';
require_once INDICES_ESTAR_PATH . 'includes/class-indices-estar-shortcode.php';
require_once INDICES_ESTAR_PATH . 'admin/class-indices-estar-admin.php';
require_once INDICES_ESTAR_PATH . 'includes/class-indices-estar.php';


add_filter('plugin_action_links_' . plugin_basename(__FILE__), function (array $links) {
    if (current_user_can('manage_options')) {
        $url = admin_url('admin.php?page=indices-estar');
        array_unshift($links, '<a href="' . esc_url($url) . '">' . esc_html__('Gestión de índices', 'indices-estar') . '</a>');
    }
    return $links;
});


register_activation_hook(__FILE__, function () { Indices_Estar_DB::install(); });

add_action('plugins_loaded', function () {
  load_plugin_textdomain('indices-estar', false, dirname(plugin_basename(__FILE__)) . '/languages');
  (new Indices_Estar())->init();
});
