<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap indices-estar-admin">
  <h1 class="wp-heading-inline"><?php echo esc_html__('Índices', 'indices-estar'); ?></h1>
  <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=indices-estar-group-edit')); ?>"><?php echo esc_html__('Añadir índice', 'indices-estar'); ?></a>
  <hr class="wp-header-end"/>

  <?php if (isset($_GET['updated'])): ?>
    <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Guardado.', 'indices-estar'); ?></p></div>
  <?php endif; ?>

  <?php if (empty($groups)): ?>
    <p><?php echo esc_html__('No hay índices.', 'indices-estar'); ?></p>
  <?php else: ?>
    <table class="widefat striped">
      <thead>
        <tr>
          <th><?php echo esc_html__('Nombre', 'indices-estar'); ?></th>
          <th><?php echo esc_html__('Slug', 'indices-estar'); ?></th>
          <th><?php echo esc_html__('Shortcode', 'indices-estar'); ?></th>
          <th><?php echo esc_html__('Acciones', 'indices-estar'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($groups as $g): ?>
          <?php
            $edit = admin_url('admin.php?page=indices-estar-group-edit&id=' . (int)$g['id']);
            $issues = admin_url('admin.php?page=indices-estar-issues&group_id=' . (int)$g['id']);
            $del = wp_nonce_url(admin_url('admin-post.php?action=indices_estar_delete_group&id='.(int)$g['id']), 'indices_estar_delete_group');
            $sc_id = '[indices_estar group="'.(int)$g['id'].'"]';
            $sc_slug = '[indices_estar slug="'.esc_attr($g['slug']).'"]';
          ?>
          <tr>
            <td><?php echo esc_html($g['name']); ?></td>
            <td><code><?php echo esc_html($g['slug']); ?></code></td>
            <td>
              <div><code class="ie-sc" data-sc="<?php echo esc_attr($sc_id); ?>"><?php echo esc_html($sc_id); ?></code> <button class="button button-small ie-copy-sc" type="button"><?php echo esc_html__('Copiar', 'indices-estar'); ?></button></div>
              <div style="margin-top:6px;"><code class="ie-sc" data-sc="<?php echo esc_attr($sc_slug); ?>"><?php echo esc_html($sc_slug); ?></code> <button class="button button-small ie-copy-sc" type="button"><?php echo esc_html__('Copiar', 'indices-estar'); ?></button></div>
            </td>
            <td>
              <a class="button button-small" href="<?php echo esc_url($issues); ?>"><?php echo esc_html__('Gestionar números', 'indices-estar'); ?></a>
              <a class="button button-small" href="<?php echo esc_url($edit); ?>"><?php echo esc_html__('Editar', 'indices-estar'); ?></a>
              <a class="button button-small button-link-delete" href="<?php echo esc_url($del); ?>" onclick="return confirm('<?php echo esc_js(__('¿Eliminar este índice? Sus números se moverán al índice por defecto.', 'indices-estar')); ?>');"><?php echo esc_html__('Eliminar', 'indices-estar'); ?></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
