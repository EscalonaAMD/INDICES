<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap indices-estar-admin">
  <h1><?php echo $group ? esc_html__('Editar índice', 'indices-estar') : esc_html__('Nuevo índice', 'indices-estar'); ?></h1>

  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="indices_estar_save_group" />
    <input type="hidden" name="id" value="<?php echo esc_attr($group['id'] ?? 0); ?>" />
    <?php wp_nonce_field('indices_estar_save_group'); ?>

    <div class="ie-card">
      <div class="ie-grid" style="grid-template-columns: repeat(2, minmax(0,1fr));">
        <label>
          <span><?php echo esc_html__('Nombre', 'indices-estar'); ?></span>
          <input type="text" name="name" required value="<?php echo esc_attr($group['name'] ?? ''); ?>" />
        </label>
        <label>
          <span><?php echo esc_html__('Slug', 'indices-estar'); ?></span>
          <input type="text" name="slug" value="<?php echo esc_attr($group['slug'] ?? ''); ?>" placeholder="indices-revista" />
        </label>
      </div>

      <?php if (!empty($group['id'])): ?>
        <p class="description">
          <?php echo esc_html__('Shortcode:', 'indices-estar'); ?>
          <code>[indices_estar group="<?php echo (int)$group['id']; ?>"]</code>
          <code style="margin-left:8px;">[indices_estar slug="<?php echo esc_html($group['slug']); ?>"]</code>
        </p>
      <?php endif; ?>
    </div>

    <p>
      <button type="submit" class="button button-primary"><?php echo esc_html__('Guardar', 'indices-estar'); ?></button>
      <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=indices-estar')); ?>"><?php echo esc_html__('Volver', 'indices-estar'); ?></a>
    </p>
  </form>
</div>
