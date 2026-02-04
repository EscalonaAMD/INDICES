<?php
if (!defined('ABSPATH')) exit;

class Indices_Estar_DB {
  private const TTL_YEARS  = 6 * HOUR_IN_SECONDS;
  private const TTL_NUMS   = 6 * HOUR_IN_SECONDS;
  private const TTL_LATEST = 6 * HOUR_IN_SECONDS;

  public static function table_groups(): string { global $wpdb; return $wpdb->prefix . 'estar_index_groups'; }
  public static function table_issues(): string { global $wpdb; return $wpdb->prefix . 'estar_indices'; }
  public static function table_items(): string  { global $wpdb; return $wpdb->prefix . 'estar_index_items'; }

  public static function cache_key(string $suffix): string {
    $ver = (int) get_transient('indices_estar_cache_ver');
    if ($ver <= 0) $ver = 1;
    return 'indices_estar_v' . $ver . '_' . $suffix;
  }
  public static function flush_cache(): void {
    $ver = (int) get_transient('indices_estar_cache_ver');
    if ($ver <= 0) $ver = 1;
    set_transient('indices_estar_cache_ver', $ver + 1, 4 * WEEK_IN_SECONDS);
  }

  public static function install(): void {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset = $wpdb->get_charset_collate();

    $tg = self::table_groups();
    $ti = self::table_issues();
    $tt = self::table_items();

    $sql_g = "CREATE TABLE $tg (
      id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(190) NOT NULL,
      slug VARCHAR(190) NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY slug (slug)
    ) $charset;";

    $sql_i = "CREATE TABLE $ti (
      id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      group_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
      year INT(11) NOT NULL,
      number INT(11) NOT NULL,
      index_date DATE NULL,
      image_id BIGINT(20) UNSIGNED NULL,
      url TEXT NULL,
      url_blank TINYINT(1) NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY group_year_num (group_id, year, number),
      KEY year_num (year, number)
    ) $charset;";

    $sql_t = "CREATE TABLE $tt (
      id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      index_id BIGINT(20) UNSIGNED NOT NULL,
      section VARCHAR(255) NULL,
      title VARCHAR(255) NULL,
      item_url TEXT NULL,
      author VARCHAR(255) NULL,
      sort_order INT(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (id),
      KEY idx_index (index_id)
    ) $charset;";

    dbDelta($sql_g);
    dbDelta($sql_i);
    dbDelta($sql_t);

    // Ensure group_id for upgrades from v1
    $col = $wpdb->get_var($wpdb->prepare(
      "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'group_id'",
      $ti
    ));
    if (!$col) {
      $wpdb->query("ALTER TABLE $ti ADD COLUMN group_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0");
      $wpdb->query("ALTER TABLE $ti ADD KEY group_year_num (group_id, year, number)");
    }

    $default = self::ensure_default_group();
    $wpdb->query($wpdb->prepare("UPDATE $ti SET group_id=%d WHERE group_id=0", $default));

    self::flush_cache();
  }

  public static function ensure_default_group(): int {
    global $wpdb; $tg=self::table_groups();
    $existing = (int)$wpdb->get_var("SELECT id FROM $tg ORDER BY id ASC LIMIT 1");
    if ($existing) return $existing;
    $wpdb->insert($tg, ['name'=>'Índices','slug'=>'indices']);
    return (int)$wpdb->insert_id;
  }

  // Groups
  public static function get_groups(): array {
    global $wpdb; $tg=self::table_groups();
    return $wpdb->get_results("SELECT id,name,slug FROM $tg ORDER BY name ASC", ARRAY_A) ?: [];
  }
  public static function get_group(int $id): ?array {
    global $wpdb; $tg=self::table_groups();
    $row = $wpdb->get_row($wpdb->prepare("SELECT id,name,slug FROM $tg WHERE id=%d LIMIT 1", $id), ARRAY_A);
    return $row ?: null;
  }
  public static function get_group_by_slug(string $slug): ?array {
    global $wpdb; $tg=self::table_groups();
    $row = $wpdb->get_row($wpdb->prepare("SELECT id,name,slug FROM $tg WHERE slug=%s LIMIT 1", $slug), ARRAY_A);
    return $row ?: null;
  }
  public static function upsert_group(array $data): int {
    global $wpdb; $tg=self::table_groups();
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    $name = sanitize_text_field($data['name'] ?? '');
    $slug = sanitize_title($data['slug'] ?? $name);
    if ($name==='') $name='Índices';
    if ($slug==='') $slug='indices';

    // Ensure unique slug
    $base=$slug; $i=2;
    while (true) {
      $exists = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $tg WHERE slug=%s AND id<>%d LIMIT 1", $slug, $id));
      if (!$exists) break;
      $slug = $base . '-' . $i; $i++;
    }

    if ($id>0) $wpdb->update($tg, ['name'=>$name,'slug'=>$slug], ['id'=>$id]);
    else { $wpdb->insert($tg, ['name'=>$name,'slug'=>$slug]); $id=(int)$wpdb->insert_id; }

    self::flush_cache();
    return $id;
  }
  public static function delete_group(int $id): bool {
    global $wpdb; $tg=self::table_groups(); $ti=self::table_issues();
    $count=(int)$wpdb->get_var("SELECT COUNT(*) FROM $tg");
    if ($count<=1) return false;
    $default=self::ensure_default_group();
    $wpdb->query($wpdb->prepare("UPDATE $ti SET group_id=%d WHERE group_id=%d", $default, $id));
    $ok=(bool)$wpdb->delete($tg, ['id'=>$id], ['%d']);
    self::flush_cache();
    return $ok;
  }

  // Issues
  public static function get_issues_overview(int $group_id): array {
    global $wpdb; $ti=self::table_issues();
    return $wpdb->get_results($wpdb->prepare(
      "SELECT id,group_id,year,number,index_date,image_id,url,url_blank
       FROM $ti WHERE group_id=%d ORDER BY year DESC, number DESC", $group_id
    ), ARRAY_A) ?: [];
  }
  public static function get_issue(int $id): ?array {
    global $wpdb; $ti=self::table_issues();
    $row = $wpdb->get_row($wpdb->prepare(
      "SELECT id,group_id,year,number,index_date,image_id,url,url_blank FROM $ti WHERE id=%d LIMIT 1", $id
    ), ARRAY_A);
    return $row ?: null;
  }
  public static function get_items(int $issue_id): array {
    global $wpdb; $tt=self::table_items();
    return $wpdb->get_results($wpdb->prepare(
      "SELECT id,section,title,item_url,author,sort_order FROM $tt WHERE index_id=%d ORDER BY sort_order ASC, id ASC", $issue_id
    ), ARRAY_A) ?: [];
  }
  public static function upsert_issue(array $data, array $items): int {
    global $wpdb; $ti=self::table_issues(); $tt=self::table_items();
    $id = isset($data['id']) ? (int)$data['id'] : 0;

    $payload = [
      'group_id'   => max(1, (int)($data['group_id'] ?? 0)),
      'year'       => indices_estar_sanitize_year($data['year'] ?? 0),
      'number'     => indices_estar_sanitize_number($data['number'] ?? 0),
      'index_date' => indices_estar_sanitize_date($data['index_date'] ?? ''),
      'image_id'   => isset($data['image_id']) ? (int)$data['image_id'] : null,
      'url'        => isset($data['url']) ? esc_url_raw($data['url']) : '',
      'url_blank'  => indices_estar_bool_to_int($data['url_blank'] ?? 0),
    ];
    if ($payload['index_date']==='') $payload['index_date']=null;

    if ($id>0) $wpdb->update($ti, $payload, ['id'=>$id]);
    else { $wpdb->insert($ti, $payload); $id=(int)$wpdb->insert_id; }

    $wpdb->delete($tt, ['index_id'=>$id]);

    $order=0;
    foreach ($items as $it) {
      $section=sanitize_text_field($it['section'] ?? '');
      $title=sanitize_text_field($it['title'] ?? '');
      $url=esc_url_raw($it['item_url'] ?? '');
      $author=sanitize_text_field($it['author'] ?? '');
      if ($section==='' && $title==='' && $url==='' && $author==='') continue;

      $wpdb->insert($tt, [
        'index_id'=>$id,'section'=>$section,'title'=>$title,'item_url'=>$url,'author'=>$author,'sort_order'=>$order
      ]);
      $order++;
    }

    self::flush_cache();
    return $id;
  }
  public static function delete_issue(int $id): bool {
    global $wpdb; $ti=self::table_issues(); $tt=self::table_items();
    $wpdb->delete($tt, ['index_id'=>$id]);
    $ok=(bool)$wpdb->delete($ti, ['id'=>$id], ['%d']);
    self::flush_cache();
    return $ok;
  }

  // Frontend queries (by group)
  public static function get_years(int $group_id): array {
    $key=self::cache_key('years_'.$group_id);
    $cached=get_transient($key);
    if (is_array($cached)) return $cached;

    global $wpdb; $ti=self::table_issues();
    $years=$wpdb->get_col($wpdb->prepare("SELECT DISTINCT year FROM $ti WHERE group_id=%d ORDER BY year DESC", $group_id)) ?: [];
    $years=array_map('intval', $years);
    set_transient($key, $years, self::TTL_YEARS);
    return $years;
  }
  public static function get_numbers_by_year(int $group_id, int $year): array {
    $key=self::cache_key('nums_'.$group_id.'_'.$year);
    $cached=get_transient($key);
    if (is_array($cached)) return $cached;

    global $wpdb; $ti=self::table_issues();
    $rows=$wpdb->get_results($wpdb->prepare(
      "SELECT id, number FROM $ti WHERE group_id=%d AND year=%d ORDER BY number DESC", $group_id, $year
    ), ARRAY_A) ?: [];
    $rows=array_map(fn($r)=>['id'=>(int)$r['id'],'number'=>(int)$r['number']], $rows);
    set_transient($key, $rows, self::TTL_NUMS);
    return $rows;
  }
  public static function get_latest_issue_id(int $group_id): int {
    $key=self::cache_key('latest_'.$group_id);
    $cached=get_transient($key);
    if ($cached!==false) return (int)$cached;

    global $wpdb; $ti=self::table_issues();
    $id=$wpdb->get_var($wpdb->prepare("SELECT id FROM $ti WHERE group_id=%d ORDER BY year DESC, number DESC LIMIT 1", $group_id));
    $id=$id ? (int)$id : 0;
    set_transient($key, $id, self::TTL_LATEST);
    return $id;
  }
  public static function get_adjacent_ids_by_value(int $group_id, int $year, int $num): array {
    global $wpdb; $ti=self::table_issues();
    $prev=(int)($wpdb->get_var($wpdb->prepare(
      "SELECT id FROM $ti WHERE group_id=%d AND ((year>%d) OR (year=%d AND number>%d)) ORDER BY year ASC, number ASC LIMIT 1",
      $group_id,$year,$year,$num
    )) ?: 0);
    $next=(int)($wpdb->get_var($wpdb->prepare(
      "SELECT id FROM $ti WHERE group_id=%d AND ((year<%d) OR (year=%d AND number<%d)) ORDER BY year DESC, number DESC LIMIT 1",
      $group_id,$year,$year,$num
    )) ?: 0);
    return ['prev'=>$prev,'next'=>$next];
  }
  public static function get_adjacent_years(int $group_id, int $year): array {
    global $wpdb; $ti=self::table_issues();
    $prevYear=(int)($wpdb->get_var($wpdb->prepare(
      "SELECT year FROM $ti WHERE group_id=%d AND year>%d ORDER BY year ASC LIMIT 1", $group_id, $year
    )) ?: 0);
    $nextYear=(int)($wpdb->get_var($wpdb->prepare(
      "SELECT year FROM $ti WHERE group_id=%d AND year<%d ORDER BY year DESC LIMIT 1", $group_id, $year
    )) ?: 0);
    return ['prevYear'=>$prevYear,'nextYear'=>$nextYear];
  }
}
