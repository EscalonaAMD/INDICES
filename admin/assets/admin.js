(function ($) {

  function normalizeItemNames() {
    const items = $('#ie_items .ie-item');
    items.each(function (idx) {
      $(this).find('input[name^="items["]').each(function () {
        const name = $(this).attr('name');
        const newName = name.replace(/^items\[\d+\]/, 'items[' + idx + ']');
        $(this).attr('name', newName);
      });
    });
  }

  function refreshMoveButtons() {
    const items = $('#ie_items .ie-item');
    if (!items.length) return;
    items.find('.ie-move-up, .ie-move-down').prop('disabled', false);
    items.first().find('.ie-move-up').prop('disabled', true);
    items.last().find('.ie-move-down').prop('disabled', true);
  }

  function initDragAndDrop() {
    const wrap = document.getElementById('ie_items');
    if (!wrap) return;

    let dragged = null;
    function setDraggable(el, on) { el.setAttribute('draggable', on ? 'true' : 'false'); }

    wrap.querySelectorAll('.ie-item').forEach(item => {
      setDraggable(item, false);

      const handle = item.querySelector('.ie-drag');
      if (handle) {
        handle.addEventListener('mousedown', () => setDraggable(item, true));
        handle.addEventListener('mouseup', () => setDraggable(item, false));
        handle.addEventListener('mouseleave', () => setDraggable(item, false));
      }

      item.addEventListener('dragstart', (e) => {
        dragged = item;
        item.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', 'drag'); } catch (err) {}
      });

      item.addEventListener('dragend', () => {
        if (dragged) dragged.classList.remove('is-dragging');
        wrap.querySelectorAll('.ie-item').forEach(i => i.classList.remove('is-drop-target'));
        dragged = null;
        setDraggable(item, false);
        refreshMoveButtons();
      });

      item.addEventListener('dragover', (e) => {
        if (!dragged || dragged === item) return;
        e.preventDefault();
        item.classList.add('is-drop-target');
        e.dataTransfer.dropEffect = 'move';
      });

      item.addEventListener('dragleave', () => item.classList.remove('is-drop-target'));

      item.addEventListener('drop', (e) => {
        e.preventDefault();
        item.classList.remove('is-drop-target');
        if (!dragged || dragged === item) return;

        const rect = item.getBoundingClientRect();
        const before = (e.clientY - rect.top) < (rect.height / 2);
        if (before) wrap.insertBefore(dragged, item);
        else wrap.insertBefore(dragged, item.nextSibling);

        normalizeItemNames();
        refreshMoveButtons();
      });
    });
  }

  // Accessible move buttons
  $(document).on('click', '.ie-move-up', function () {
    const item = $(this).closest('.ie-item');
    const prev = item.prev('.ie-item');
    if (!prev.length) return;
    prev.before(item);
    normalizeItemNames();
    refreshMoveButtons();
    $(this).trigger('focus');
  });

  $(document).on('click', '.ie-move-down', function () {
    const item = $(this).closest('.ie-item');
    const next = item.next('.ie-item');
    if (!next.length) return;
    next.after(item);
    normalizeItemNames();
    refreshMoveButtons();
    $(this).trigger('focus');
  });

  // Add/remove rows
  $(document).on('click', '#ie_add_item', function () {
    const $wrap = $('#ie_items');
    const tpl = ($('#ie_item_tpl').html() || '');
    const nextIndex = $wrap.find('.ie-item').length;
    if (!tpl) return;
    $wrap.append(tpl.replaceAll('__i__', String(nextIndex)));
    setTimeout(() => {
      initDragAndDrop();
      normalizeItemNames();
      refreshMoveButtons();
    }, 0);
  });

  $(document).on('click', '.ie-remove-item', function () {
    $(this).closest('.ie-item').remove();
    setTimeout(() => {
      normalizeItemNames();
      refreshMoveButtons();
    }, 0);
  });

  // Media uploader
  let frame = null;
  $(document).on('click', '#ie_select_image', function (e) {
    e.preventDefault();
    if (typeof wp === 'undefined' || !wp.media) return;

    if (frame) { frame.open(); return; }

    frame = wp.media({
      title: 'Seleccionar imagen',
      button: { text: 'Usar esta imagen' },
      multiple: false
    });

    frame.on('select', function () {
      const attachment = frame.state().get('selection').first().toJSON();
      $('#ie_image_id').val(attachment.id);
      $('#ie_media_preview').html('<img src="' + attachment.url + '" alt="" />');
    });

    frame.open();
  });

  $(document).on('click', '#ie_remove_image', function (e) {
    e.preventDefault();
    $('#ie_image_id').val('0');
    $('#ie_media_preview').empty();
  });

  // Copy shortcode (works in non-secure contexts too)
  function fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
  }

  $(document).on('click', '.ie-copy-sc', function () {
    const $row = $(this).closest('.ie-sc-row');
    const $code = $row.length ? $row.find('code.ie-sc').first() : $(this).closest('td').find('code.ie-sc').first();
    const text = (($code.data('sc') || $code.text() || '') + '').trim();
    if (!text) return;

    const $btn = $(this);

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(() => {
        $btn.text('✓'); setTimeout(() => $btn.text('Copiar'), 900);
      }).catch(() => {
        fallbackCopy(text);
        $btn.text('✓'); setTimeout(() => $btn.text('Copiar'), 900);
      });
    } else {
      fallbackCopy(text);
      $btn.text('✓'); setTimeout(() => $btn.text('Copiar'), 900);
    }
  });

  // Before submit: normalize indices
  $(document).on('submit', '.indices-estar-admin form', function () {
    normalizeItemNames();
  });

  $(document).ready(function () {
    initDragAndDrop();
    normalizeItemNames();
    refreshMoveButtons();
  });

})(jQuery);
