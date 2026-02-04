<?php
if (!defined('ABSPATH')) exit;

class Indices_Estar_Admin {

  public static function register_menu(): void {
    add_menu_page(__('Índices','indices-estar'), __('Índices','indices-estar'), 'manage_options', 'indices-estar', [__CLASS__,'render_groups'], 'dashicons-index-card', 56);
    add_submenu_page('indices-estar', __('Índices','indices-estar'), __('Índices','indices-estar'), 'manage_options', 'indices-estar', [__CLASS__,'render_groups']);

    add_submenu_page(null, __('Editar índice','indices-estar'), __('Editar índice','indices-estar'), 'manage_options', 'indices-estar-group-edit', [__CLASS__,'render_group_edit']);
    add_submenu_page(null, __('Números','indices-estar'), __('Números','indices-estar'), 'manage_options', 'indices-estar-issues', [__CLASS__,'render_issues']);
    add_submenu_page(null, __('Editar número','indices-estar'), __('Editar número','indices-estar'), 'manage_options', 'indices-estar-issue-edit', [__CLASS__,'render_issue_edit']);
  }

  public static function enqueue_assets(string $hook): void {
    if (strpos($hook,'indices-estar') === false) return;
    wp_enqueue_media();
    wp_enqueue_style('indices-estar-admin', INDICES_ESTAR_URL . 'admin/assets/admin.css', [], INDICES_ESTAR_VERSION);
    wp_enqueue_script('indices-estar-admin', INDICES_ESTAR_URL . 'admin/assets/admin.js', ['jquery'], INDICES_ESTAR_VERSION, true);
  }

  public static function handle_save_group(): void {
    if (!indices_estar_current_user_can_manage()) wp_die(__('No autorizado.','indices-estar'));
    check_admin_referer('indices_estar_save_group');

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $new_id = Indices_Estar_DB::upsert_group([
      'id'=>$id,
      'name'=>$_POST['name'] ?? '',
      'slug'=>$_POST['slug'] ?? '',
    ]);

    wp_safe_redirect(admin_url('admin.php?page=indices-estar&updated=1&focus='.$new_id));
    exit;
  }

  public static function handle_save_issue(): void {
    if (!indices_estar_current_user_can_manage()) wp_die(__('No autorizado.','indices-estar'));
    check_admin_referer('indices_estar_save_issue');

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

    $data = [
      'id'=>$id,
      'group_id'=>$group_id,
      'year'=>$_POST['year'] ?? 0,
      'number'=>$_POST['number'] ?? 0,
      'index_date'=>$_POST['index_date'] ?? '',
      'image_id'=>$_POST['image_id'] ?? 0,
      'url'=>$_POST['url'] ?? '',
      'url_blank'=>isset($_POST['url_blank']) ? 1 : 0,
    ];

    $items=[];
    $raw=$_POST['items'] ?? [];
    if (is_array($raw)) {
      foreach ($raw as $r) {
        $items[] = [
          'section'=>$r['section'] ?? '',
          'title'=>$r['title'] ?? '',
          'item_url'=>$r['item_url'] ?? '',
          'author'=>$r['author'] ?? '',
        ];
      }
    }

    $new_id = Indices_Estar_DB::upsert_issue($data, $items);
    wp_safe_redirect(admin_url('admin.php?page=indices-estar-issue-edit&id='.$new_id.'&group_id='.$group_id.'&updated=1'));
    exit;
  }

  public static function render_groups(): void {
    if (!indices_estar_current_user_can_manage()) wp_die(__('No autorizado.','indices-estar'));
    $groups = Indices_Estar_DB::get_groups();
    require INDICES_ESTAR_PATH . 'admin/views/page-groups.php';
  }

  public static function render_group_edit(): void {
    if (!indices_estar_current_user_can_manage()) wp_die(__('No autorizado.','indices-estar'));
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $group = $id ? Indices_Estar_DB::get_group($id) : null;
    require INDICES_ESTAR_PATH . 'admin/views/page-group-edit.php';
  }

  public static function render_issues(): void {
    if (!indices_estar_current_user_can_manage()) wp_die(__('No autorizado.','indices-estar'));
    $group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
    if ($group_id<=0) {
      $gs = Indices_Estar_DB::get_groups();
      $group_id = !empty($gs[0]['id']) ? (int)$gs[0]['id'] : 0;
    }
    if ($group_id<=0) wp_die(__('No hay índices.','indices-estar'));
    $group = Indices_Estar_DB::get_group($group_id);
    $issues = Indices_Estar_DB::get_issues_overview($group_id);
    require INDICES_ESTAR_PATH . 'admin/views/page-issues.php';
  }

  public static function render_issue_edit(): void {
    if (!indices_estar_current_user_can_manage()) wp_die(__('No autorizado.','indices-estar'));
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

    $issue = $id ? Indices_Estar_DB::get_issue($id) : null;
    if ($issue) $group_id = (int)$issue['group_id'];

    if ($group_id<=0) {
      $gs = Indices_Estar_DB::get_groups();
      $group_id = !empty($gs[0]['id']) ? (int)$gs[0]['id'] : 0;
    }

    $group = Indices_Estar_DB::get_group($group_id);
    $items = $id ? Indices_Estar_DB::get_items($id) : [];
    require INDICES_ESTAR_PATH . 'admin/views/page-issue-edit.php';
  }


public static function handle_delete_group(): void {
  if (!indices_estar_current_user_can_manage()) wp_die(__('No autorizado.','indices-estar'));
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  check_admin_referer('indices_estar_delete_group');
  if ($id > 0) Indices_Estar_DB::delete_group($id);
  wp_safe_redirect(admin_url('admin.php?page=indices-estar&deleted=1'));
  exit;
}

public static function handle_delete_issue(): void {
  if (!indices_estar_current_user_can_manage()) wp_die(__('No autorizado.','indices-estar'));
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
  check_admin_referer('indices_estar_delete_issue');
  if ($id > 0) Indices_Estar_DB::delete_issue($id);
  $redirect = admin_url('admin.php?page=indices-estar-issues' . ($group_id ? '&group_id=' . $group_id : '') . '&deleted=1');
  wp_safe_redirect($redirect);
  exit;
}

}
