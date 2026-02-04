<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap indices-estar-admin">
  <h1>
    <?php echo $issue ? esc_html__('Editar número', 'indices-estar') : esc_html__('Nuevo número', 'indices-estar'); ?>
    <?php if (!empty($group['name'])): ?>
      <span style="font-weight:400; opacity:.75;">— <?php echo esc_html($group['name']); ?></span>
    <?php endif; ?>
  </h1>

  <?php if (isset($_GET['updated'])): ?>
    <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Guardado correctamente.', 'indices-estar'); ?></p></div>
  <?php endif; ?>

  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="indices_estar_save_issue" />
    <input type="hidden" name="id" value="<?php echo esc_attr($issue['id'] ?? 0); ?>" />
    <input type="hidden" name="group_id" value="<?php echo esc_attr((int)$group_id); ?>" />
    <?php wp_nonce_field('indices_estar_save_issue'); ?>

    <div class="ie-card">
      <h2><?php echo esc_html__('Datos del número', 'indices-estar'); ?></h2>

      <div class="ie-grid">
        <label>
          <span><?php echo esc_html__('Año', 'indices-estar'); ?></span>
          <input type="number" name="year" required value="<?php echo esc_attr($issue['year'] ?? ''); ?>" />
        </label>

        <label>
          <span><?php echo esc_html__('Número', 'indices-estar'); ?></span>
          <input type="number" name="number" required value="<?php echo esc_attr($issue['number'] ?? ''); ?>" />
        </label>

        <label>
          <span><?php echo esc_html__('Fecha', 'indices-estar'); ?></span>
          <input type="date" name="index_date" value="<?php echo esc_attr($issue['index_date'] ?? ''); ?>" />
        </label>
      </div>

      <div class="ie-media">
        <input type="hidden" name="image_id" id="ie_image_id" value="<?php echo esc_attr($issue['image_id'] ?? 0); ?>" />
        <div class="ie-media-preview" id="ie_media_preview">
          <?php
            $img_url = !empty($issue['image_id']) ? wp_get_attachment_url((int)$issue['image_id']) : '';
            if ($img_url) echo '<img src="' . esc_url($img_url) . '" alt="" />';
          ?>
        </div>
        <div class="ie-media-actions">
          <button type="button" class="button" id="ie_select_image"><?php echo esc_html__('Seleccionar imagen', 'indices-estar'); ?></button>
          <button type="button" class="button" id="ie_remove_image"><?php echo esc_html__('Quitar', 'indices-estar'); ?></button>
        </div>
        <p class="description"><?php echo esc_html__('En frontend se usa la imagen original.', 'indices-estar'); ?></p>
      </div>

      <div class="ie-grid">
        <label class="ie-wide">
          <span><?php echo esc_html__('URL opcional (imagen + título)', 'indices-estar'); ?></span>
          <input type="url" name="url" value="<?php echo esc_attr($issue['url'] ?? ''); ?>" placeholder="https://..." />
        </label>

        <label class="ie-check">
          <input type="checkbox" name="url_blank" <?php checked(!empty($issue['url_blank'])); ?> />
          <span><?php echo esc_html__('Abrir en nueva pestaña', 'indices-estar'); ?></span>
        </label>
      </div>
    </div>

    <div class="ie-card">
      <h2><?php echo esc_html__('Contenidos', 'indices-estar'); ?></h2>

      <div id="ie_items">
        <?php if (!empty($items)): ?>
          <?php foreach ($items as $i => $it): ?>
            <div class="ie-item">
              <div class="ie-drag" title="<?php echo esc_attr__('Arrastrar para reordenar', 'indices-estar'); ?>" aria-label="<?php echo esc_attr__('Arrastrar para reordenar', 'indices-estar'); ?>">⋮⋮</div>

              <div class="ie-move-controls" role="group" aria-label="<?php echo esc_attr__('Reordenar fila', 'indices-estar'); ?>">
                <button type="button" class="button ie-move-up"><?php echo esc_html__('Subir', 'indices-estar'); ?></button>
                <button type="button" class="button ie-move-down"><?php echo esc_html__('Bajar', 'indices-estar'); ?></button>
              </div>

              <div class="ie-grid ie-children">
                <label>
                  <span><?php echo esc_html__('Sección', 'indices-estar'); ?></span>
                  <input type="text" name="items[<?php echo (int)$i; ?>][section]" value="<?php echo esc_attr($it['section'] ?? ''); ?>" />
                </label>
                <label>
                  <span><?php echo esc_html__('Título', 'indices-estar'); ?></span>
                  <input type="text" name="items[<?php echo (int)$i; ?>][title]" value="<?php echo esc_attr($it['title'] ?? ''); ?>" />
                </label>
                <label>
                  <span><?php echo esc_html__('Enlace (opcional)', 'indices-estar'); ?></span>
                  <input type="url" name="items[<?php echo (int)$i; ?>][item_url]" value="<?php echo esc_attr($it['item_url'] ?? ''); ?>" placeholder="https://..." />
                </label>
                <label>
                  <span><?php echo esc_html__('Autor', 'indices-estar'); ?></span>
                  <input type="text" name="items[<?php echo (int)$i; ?>][author]" value="<?php echo esc_attr($it['author'] ?? ''); ?>" />
                </label>
              </div>
              <button type="button" class="button ie-remove-item"><?php echo esc_html__('Eliminar fila', 'indices-estar'); ?></button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <button type="button" class="button button-secondary" id="ie_add_item"><?php echo esc_html__('Añadir fila', 'indices-estar'); ?></button>
    </div>

    <p>
      <button type="submit" class="button button-primary"><?php echo esc_html__('Guardar', 'indices-estar'); ?></button>
      <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=indices-estar-issues&group_id='.(int)$group_id)); ?>"><?php echo esc_html__('Volver', 'indices-estar'); ?></a>
    </p>
  </form>
</div>

<script type="text/template" id="ie_item_tpl">
  <div class="ie-item">
    <div class="ie-drag" title="<?php echo esc_attr__('Arrastrar para reordenar', 'indices-estar'); ?>" aria-label="<?php echo esc_attr__('Arrastrar para reordenar', 'indices-estar'); ?>">⋮⋮</div>
    <div class="ie-move-controls" role="group" aria-label="<?php echo esc_attr__('Reordenar fila', 'indices-estar'); ?>">
      <button type="button" class="button ie-move-up"><?php echo esc_html__('Subir', 'indices-estar'); ?></button>
      <button type="button" class="button ie-move-down"><?php echo esc_html__('Bajar', 'indices-estar'); ?></button>
    </div>
    <div class="ie-grid ie-children">
      <label><span><?php echo esc_html__('Sección', 'indices-estar'); ?></span><input type="text" name="items[__i__][section]" value="" /></label>
      <label><span><?php echo esc_html__('Título', 'indices-estar'); ?></span><input type="text" name="items[__i__][title]" value="" /></label>
      <label><span><?php echo esc_html__('Enlace (opcional)', 'indices-estar'); ?></span><input type="url" name="items[__i__][item_url]" value="" placeholder="https://..." /></label>
      <label><span><?php echo esc_html__('Autor', 'indices-estar'); ?></span><input type="text" name="items[__i__][author]" value="" /></label>
    </div>
    <button type="button" class="button ie-remove-item"><?php echo esc_html__('Eliminar fila', 'indices-estar'); ?></button>
  </div>
</script>
