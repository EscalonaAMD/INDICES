<?php
if (!defined('ABSPATH')) exit;

class Indices_Estar {
  private static bool $should_enqueue_public = false;

  public function init(): void {
    add_action('admin_menu', [$this,'admin_menu']);
    add_action('admin_enqueue_scripts', [$this,'admin_assets']);

    add_shortcode('indices_estar', ['Indices_Estar_Shortcode','render']);
    add_action('wp_enqueue_scripts', [$this,'public_assets']);

    add_action('admin_post_indices_estar_save_group', ['Indices_Estar_Admin','handle_save_group']);
    add_action('admin_post_indices_estar_save_issue', ['Indices_Estar_Admin','handle_save_issue']);

    add_action('admin_post_indices_estar_delete_group', ['Indices_Estar_Admin','handle_delete_group']);
    add_action('admin_post_indices_estar_delete_issue', ['Indices_Estar_Admin','handle_delete_issue']);

    add_action('wp_ajax_indices_estar_get_years', ['Indices_Estar_Ajax','get_years']);
    add_action('wp_ajax_nopriv_indices_estar_get_years', ['Indices_Estar_Ajax','get_years']);
    add_action('wp_ajax_indices_estar_get_numbers', ['Indices_Estar_Ajax','get_numbers']);
    add_action('wp_ajax_nopriv_indices_estar_get_numbers', ['Indices_Estar_Ajax','get_numbers']);
    add_action('wp_ajax_indices_estar_get_index', ['Indices_Estar_Ajax','get_index']);
    add_action('wp_ajax_nopriv_indices_estar_get_index', ['Indices_Estar_Ajax','get_index']);

    add_filter('the_posts', function($posts){
      if (empty($posts)) return $posts;
      foreach ($posts as $p) {
        if (is_a($p,'WP_Post') && has_shortcode($p->post_content,'indices_estar')) { self::$should_enqueue_public=true; break; }
      }
      return $posts;
    });
  }

  public function admin_menu(): void {
    if (!indices_estar_current_user_can_manage()) return;
    Indices_Estar_Admin::register_menu();
  }
  public function admin_assets(string $hook): void {
    if (!indices_estar_current_user_can_manage()) return;
    Indices_Estar_Admin::enqueue_assets($hook);
  }

  public function public_assets(): void {
    if (!self::$should_enqueue_public) return;

    wp_enqueue_style('indices-estar-public', INDICES_ESTAR_URL . 'public/assets/public.css', [], INDICES_ESTAR_VERSION);
    wp_enqueue_script('indices-estar-public', INDICES_ESTAR_URL . 'public/assets/public.js', [], INDICES_ESTAR_VERSION, true);

    wp_localize_script('indices-estar-public', 'IndicesEstar', [
      'ajaxUrl'=>admin_url('admin-ajax.php'),
      'nonce'=>wp_create_nonce('indices_estar_public'),
      'i18n'=>[
        'loading'=>__('Cargando…','indices-estar'),
        'noData'=>__('No hay datos para mostrar.','indices-estar'),
      ],
    ]);
  }
}
