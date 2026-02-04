<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap indices-estar-admin">
  <h1 class="wp-heading-inline">
    <?php echo esc_html__('Números', 'indices-estar'); ?>
    <?php if (!empty($group['name'])): ?><span style="font-weight:400; opacity:.75;">— <?php echo esc_html($group['name']); ?></span><?php endif; ?>
  </h1>

  <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=indices-estar-issue-edit&group_id='.(int)$group_id)); ?>"><?php echo esc_html__('Añadir número', 'indices-estar'); ?></a>
  <a class="page-title-action" style="margin-left:8px;" href="<?php echo esc_url(admin_url('admin.php?page=indices-estar')); ?>"><?php echo esc_html__('Volver a índices', 'indices-estar'); ?></a>

  <hr class="wp-header-end"/>

  <?php if (empty($issues)): ?>
    <p><?php echo esc_html__('No hay números para este índice.', 'indices-estar'); ?></p>
  <?php else: ?>
    <table class="widefat striped">
      <thead>
        <tr>
          <th><?php echo esc_html__('Año', 'indices-estar'); ?></th>
          <th><?php echo esc_html__('Número', 'indices-estar'); ?></th>
          <th><?php echo esc_html__('Fecha', 'indices-estar'); ?></th>
          <th><?php echo esc_html__('Acciones', 'indices-estar'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($issues as $row): ?>
          <?php
            $edit = admin_url('admin.php?page=indices-estar-issue-edit&id='.(int)$row['id'].'&group_id='.(int)$group_id);
            $del = wp_nonce_url(admin_url('admin-post.php?action=indices_estar_delete_issue&id='.(int)$row['id'].'&group_id='.(int)$group_id), 'indices_estar_delete_issue');
          ?>
          <tr>
            <td><?php echo esc_html((int)$row['year']); ?></td>
            <td><?php echo esc_html((int)$row['number']); ?></td>
            <td><?php echo !empty($row['index_date']) ? esc_html(date_i18n(get_option('date_format'), strtotime($row['index_date']))) : '—'; ?></td>
            <td>
              <a class="button button-small" href="<?php echo esc_url($edit); ?>"><?php echo esc_html__('Editar', 'indices-estar'); ?></a>
              <a class="button button-small button-link-delete" href="<?php echo esc_url($del); ?>" onclick="return confirm('<?php echo esc_js(__('¿Eliminar este número? Esta acción no se puede deshacer.', 'indices-estar')); ?>');"><?php echo esc_html__('Eliminar', 'indices-estar'); ?></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
