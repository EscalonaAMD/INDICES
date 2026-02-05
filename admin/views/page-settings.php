<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap indices-estar-admin">
  <h1><?php echo esc_html__('Ajustes', 'indices-estar'); ?></h1>

  <?php if (isset($_GET['updated'])): ?>
    <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Ajustes guardados.', 'indices-estar'); ?></p></div>
  <?php endif; ?>

  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="indices_estar_save_settings" />
    <?php wp_nonce_field('indices_estar_save_settings'); ?>

    <div class="ie-card">
      <h2><?php echo esc_html__('Desinstalación', 'indices-estar'); ?></h2>

      <label class="ie-check" style="margin-top:10px;">
        <input type="checkbox" name="delete_on_uninstall" <?php checked(!empty($delete_on_uninstall)); ?> />
        <span><?php echo esc_html__('Eliminar tablas y datos al desinstalar el plugin', 'indices-estar'); ?></span>
      </label>

      <p class="description" style="margin-top:10px;">
        <?php echo esc_html__('Si se activa, al eliminar el plugin se borrarán permanentemente todos los índices, números y contenidos asociados. Esta acción no se puede deshacer.', 'indices-estar'); ?>
      </p>
    </div>

    <p>
      <button type="submit" class="button button-primary"><?php echo esc_html__('Guardar cambios', 'indices-estar'); ?></button>
      <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=indices-estar')); ?>"><?php echo esc_html__('Volver', 'indices-estar'); ?></a>
    </p>
  </form>
</div>
