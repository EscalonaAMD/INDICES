<?php
if (!defined('ABSPATH')) exit;

class Indices_Estar_Shortcode {
  public static function render($atts): string {
    $atts = shortcode_atts(['group'=>'','slug'=>''], $atts, 'indices_estar');

    $group_id = (int)$atts['group'];
    if ($group_id <= 0 && !empty($atts['slug'])) {
      $g = Indices_Estar_DB::get_group_by_slug(sanitize_title($atts['slug']));
      $group_id = $g ? (int)$g['id'] : 0;
    }
    if ($group_id <= 0) {
      $groups = Indices_Estar_DB::get_groups();
      $group_id = !empty($groups[0]['id']) ? (int)$groups[0]['id'] : 0;
    }
    if ($group_id <= 0) return '';

    ob_start(); ?>
    <div class="indices-estar" data-group-id="<?php echo esc_attr($group_id); ?>" data-start-id="0">
      <div class="ie-search-wrap">
      <div class="ie-search" aria-label="<?php echo esc_attr__('Buscar', 'indices-estar'); ?>">
        <label class="ie-search-field">
          <span class="ie-search-label"><?php echo esc_html__('Año', 'indices-estar'); ?></span>
          <select class="ie-search-year" aria-label="<?php echo esc_attr__('Seleccionar año', 'indices-estar'); ?>"></select>
        </label>
      
      </div>
      <div class="indices-estar-grid">
      <div class="ie-col ie-col-1" aria-label="<?php echo esc_attr__('Año y números', 'indices-estar'); ?>">
        <div class="ie-head">
          <button class="ie-nav ie-year-prev" type="button" aria-label="<?php echo esc_attr__('Año más nuevo', 'indices-estar'); ?>">‹</button>
          <div class="ie-title"><span class="ie-year" aria-live="polite">—</span></div>
          <button class="ie-nav ie-year-next" type="button" aria-label="<?php echo esc_attr__('Año más viejo', 'indices-estar'); ?>">›</button>
        </div>
        <div class="ie-body ie-fade">
          <div class="ie-skeleton ie-skel-list" aria-hidden="true">
            <div class="ie-skel-line"></div><div class="ie-skel-line"></div><div class="ie-skel-line"></div><div class="ie-skel-line"></div><div class="ie-skel-line"></div>
          </div>
          <ul class="ie-list ie-numbers" role="listbox" aria-label="<?php echo esc_attr__('Listado de números', 'indices-estar'); ?>"></ul>
        </div>
      </div>

      <div class="ie-col ie-col-2" aria-label="<?php echo esc_attr__('Número, fecha y contenidos', 'indices-estar'); ?>">
        <div class="ie-head">
          <button class="ie-nav ie-index-prev" type="button" aria-label="<?php echo esc_attr__('Índice más nuevo', 'indices-estar'); ?>">‹</button>
          <div class="ie-title"><div class="ie-numdate" aria-live="polite">—</div></div>
          <button class="ie-nav ie-index-next" type="button" aria-label="<?php echo esc_attr__('Índice más viejo', 'indices-estar'); ?>">›</button>
        </div>
        <div class="ie-body ie-fade">
          <div class="ie-skeleton ie-skel-items" aria-hidden="true">
            <div class="ie-skel-card"></div><div class="ie-skel-card"></div><div class="ie-skel-card"></div>
          </div>
          <div class="ie-items" aria-live="polite"></div>
        </div>
      </div>

      <div class="ie-col ie-col-3" aria-label="<?php echo esc_attr__('Imagen', 'indices-estar'); ?>">
        <div class="ie-body ie-image-wrap ie-fade">
          <div class="ie-skeleton ie-skel-image" aria-hidden="true"></div>
          <a class="ie-image-link" href="#" target="_blank" rel="noopener" style="display:none;">
            <img class="ie-image" alt="" loading="lazy" />
          </a>
          <div class="ie-image-title">
            <a class="ie-image-title-link" href="#" target="_blank" rel="noopener" style="display:none;"></a>
            <span class="ie-image-title-text"></span>
          </div>
        </div>
      </div>

      <div class="ie-status" role="status" aria-live="polite"></div>
          </div>
</div>
    <?php return (string)ob_get_clean();
  }
}
