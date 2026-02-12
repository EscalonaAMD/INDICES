<?php
if (!defined('ABSPATH')) exit;

class Indices_Estar_Ajax {
  private const TTL_ISSUE_PAYLOAD = 30 * MINUTE_IN_SECONDS;

  private static function verify_nonce_public(): void {
    $nonce = $_REQUEST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'indices_estar_public')) {
      wp_send_json_error(['message'=>'Invalid nonce'], 403);
    }
  }

  public static function get_years(): void {
    self::verify_nonce_public();
    $group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
    if ($group_id <= 0) wp_send_json_error(['message'=>__('Falta group_id','indices-estar')], 400);

    wp_send_json_success([
      'years' => Indices_Estar_DB::get_years($group_id),
      'latestId' => Indices_Estar_DB::get_latest_issue_id($group_id),
    ]);
  }

  public static function get_numbers(): void {
    self::verify_nonce_public();
    $group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
    $year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
    if ($group_id <= 0 || $year <= 0) wp_send_json_error(['message'=>__('Parámetros inválidos','indices-estar')], 400);

    wp_send_json_success(['year'=>$year,'numbers'=>Indices_Estar_DB::get_numbers_by_year($group_id, $year)]);
  }

  public static function get_index(): void {
    self::verify_nonce_public();
    $group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($group_id <= 0 || $id <= 0) wp_send_json_error(['message'=>__('Parámetros inválidos','indices-estar')], 400);

    $cache_key = Indices_Estar_DB::cache_key('issue_' . $group_id . '_' . $id);
    $cached = get_transient($cache_key);
    if (is_array($cached)) wp_send_json_success($cached);

    $issue = Indices_Estar_DB::get_issue($id);
    if (!$issue || (int)$issue['group_id'] !== $group_id) wp_send_json_error(['message'=>__('No encontrado','indices-estar')], 404);

    $items = Indices_Estar_DB::get_items($id);

    $image_url=''; $image_title='';
    if (!empty($issue['image_id'])) {
      $image_url = wp_get_attachment_url((int)$issue['image_id']) ?: '';
      $image_title = get_the_title((int)$issue['image_id']) ?: '';
    }

    $year=(int)$issue['year']; $num=(int)$issue['number'];
    $adjIds = Indices_Estar_DB::get_adjacent_ids_by_value($group_id, $year, $num, (int)$issue['id']);
    $adjYears = Indices_Estar_DB::get_adjacent_years($group_id, $year);

    $payload = [
      'index' => [
        'id'=>(int)$issue['id'],
        'groupId'=>$group_id,
        'year'=>$year,
        'number'=>$num,
        'date'=>$issue['index_date'] ? date_i18n(get_option('date_format'), strtotime($issue['index_date'])) : '',
        'dateRaw'=>$issue['index_date'] ?: '',
        'url'=>$issue['url'] ? esc_url($issue['url']) : '',
        'urlBlank'=>!empty($issue['url_blank']),
        'imageUrl'=>$image_url,
        'imageTitle'=>esc_html($image_title), // ✅ CORREGIDO: Escapar antes de enviar
      ],
      'items' => array_map(function($it){
        return [
          'section'=>esc_html($it['section'] ?? ''),     // ✅ CORREGIDO: Escapar antes de enviar
          'title'=>esc_html($it['title'] ?? ''),         // ✅ CORREGIDO: Escapar antes de enviar
          'url'=>$it['item_url'] ? esc_url($it['item_url']) : '',
          'author'=>esc_html($it['author'] ?? ''),       // ✅ CORREGIDO: Escapar antes de enviar
        ];
      }, $items),
      'nav' => [
        'prevId'=>(int)$adjIds['prev'],
        'nextId'=>(int)$adjIds['next'],
        'hasPrev'=>!empty($adjIds['prev']),
        'hasNext'=>!empty($adjIds['next']),
      ],
      'yearsNav' => [
        'yearPrev'=>(int)$adjYears['prevYear'],
        'yearNext'=>(int)$adjYears['nextYear'],
        'hasYearPrev'=>!empty($adjYears['prevYear']),
        'hasYearNext'=>!empty($adjYears['nextYear']),
      ],
    ];

    set_transient($cache_key, $payload, self::TTL_ISSUE_PAYLOAD);
    wp_send_json_success($payload);
  }
}
