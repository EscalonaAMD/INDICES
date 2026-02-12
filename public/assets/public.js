(function () {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  function api(action, params = {}) {
    const url = new URL(IndicesEstar.ajaxUrl);
    url.searchParams.set('action', action);
    url.searchParams.set('nonce', IndicesEstar.nonce);
    Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
    return fetch(url.toString(), { credentials: 'same-origin' }).then(r => r.json());
  }

  function setStatus(root, text) { const el = $('.ie-status', root); if (el) el.textContent = text || ''; }
  function setLoading(root, on) { root.classList.toggle('is-loading', !!on); }
  function hideIfDisabled(btn, enabled) { btn.style.visibility = enabled ? 'visible' : 'hidden'; btn.disabled = !enabled; }

  async function init(container) {
    const state = {
      groupId: parseInt(container.getAttribute('data-group-id') || '0', 10) || 0,
      years: [],
      activeId: 0,
      numbers: [],
      nav: { hasPrev:false, hasNext:false, prevId:0, nextId:0 },
      yearsNav: { hasYearPrev:false, hasYearNext:false, yearPrev:0, yearNext:0 },
      yearNumbersCache: new Map(),
      indexPayloadCache: new Map(),
    };

    const yearPrev = $('.ie-year-prev', container);
    const yearNext = $('.ie-year-next', container);
    const idxPrev  = $('.ie-index-prev', container);
    const idxNext  = $('.ie-index-next', container);

    setLoading(container, true);
    setStatus(container, IndicesEstar.i18n.loading);

    const yearsRes = await api('indices_estar_get_years', { group_id: state.groupId });
    if (!yearsRes?.success || !yearsRes.data?.years?.length || !yearsRes.data?.latestId) {
      setStatus(container, IndicesEstar.i18n.noData);
      setLoading(container, false);
      hideIfDisabled(yearPrev, false); hideIfDisabled(yearNext, false);
      hideIfDisabled(idxPrev, false); hideIfDisabled(idxNext, false);
      return;
    }

    state.years = yearsRes.data.years;
    state.activeId = yearsRes.data.latestId;

    buildSearch(container, state);

    await loadIndexById(container, state, state.activeId);

    yearPrev.addEventListener('click', async () => { if (state.yearsNav.yearPrev) await loadYear(container, state, state.yearsNav.yearPrev, null); });
    yearNext.addEventListener('click', async () => { if (state.yearsNav.yearNext) await loadYear(container, state, state.yearsNav.yearNext, null); });
    idxPrev.addEventListener('click', async () => { if (state.nav.prevId) await loadIndexById(container, state, state.nav.prevId); });
    idxNext.addEventListener('click', async () => { if (state.nav.nextId) await loadIndexById(container, state, state.nav.nextId); });

    setLoading(container, false);
    setStatus(container, '');
  }

  function buildSearch(container, state) {
  const yearSel = $('.ie-search-year', container);
  if (!yearSel) return;
  state.search = { yearSel };

  yearSel.innerHTML = '';
  state.years.forEach(y => {
    const opt = document.createElement('option');
    opt.value = String(y);
    opt.textContent = String(y);
    yearSel.appendChild(opt);
  });

  yearSel.addEventListener('change', async () => {
    const y = parseInt(yearSel.value || '0', 10);
    if (y) await loadYear(container, state, y, null);
  });
}


function syncSearchToYear(container, state, year) {
  if (!state.search?.yearSel) return;
  state.search.yearSel.value = String(year);
}


async function apiCachedNumbers(state, year) {
    if (state.yearNumbersCache.has(year)) return { success:true, data:{ year, numbers: state.yearNumbersCache.get(year) } };
    const res = await api('indices_estar_get_numbers', { group_id: state.groupId, year });
    if (res?.success && Array.isArray(res.data?.numbers)) state.yearNumbersCache.set(year, res.data.numbers);
    return res;
  }

  async function apiCachedIndex(state, id) {
    if (state.indexPayloadCache.has(id)) return { success:true, data: state.indexPayloadCache.get(id) };
    const res = await api('indices_estar_get_index', { group_id: state.groupId, id });
    if (res?.success && res.data) state.indexPayloadCache.set(id, res.data);
    return res;
  }

  async function loadYear(container, state, year, preferredId) {
    setLoading(container, true);
    setStatus(container, IndicesEstar.i18n.loading);

    $('.ie-year', container).textContent = String(year);
    const numsRes = await apiCachedNumbers(state, year);
    state.numbers = (numsRes?.success && numsRes.data?.numbers) ? numsRes.data.numbers : [];
    renderNumbers(container, state);
    syncSearchToYear(container, state, year);
    
    const targetId = preferredId || (state.numbers[0]?.id ? parseInt(state.numbers[0].id, 10) : 0);
    if (targetId) await loadIndexById(container, state, targetId);

    setLoading(container, false);
    setStatus(container, '');
  }

  function renderNumbers(container, state) {
    const ul = $('.ie-numbers', container);
    ul.innerHTML = '';
    state.numbers.forEach(({ id, number }) => {
      const li = document.createElement('li');
      li.className = 'ie-number';
      li.setAttribute('role', 'option');
      li.tabIndex = 0;
      li.dataset.id = String(id);
      li.textContent = String(number);

      li.addEventListener('click', async () => loadIndexById(container, state, parseInt(id,10)));
      li.addEventListener('keydown', async (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); await loadIndexById(container, state, parseInt(id,10)); }
      });

      ul.appendChild(li);
    });
  }

  function markActiveNumber(container, activeId) {
    const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    $$('.ie-number', container).forEach(li => {
      const isActive = li.dataset.id === String(activeId);
      const wasActive = li.classList.contains('is-active');
      li.classList.toggle('is-active', isActive);
      li.setAttribute('aria-selected', isActive ? 'true' : 'false');

      if (!prefersReduced && isActive && !wasActive) {
        li.classList.remove('ie-pulse');
        void li.offsetWidth;
        li.classList.add('ie-pulse');
      }
    });
  }

  function updateNavVisibility(container, state) {
    hideIfDisabled($('.ie-year-prev', container), !!state.yearsNav?.hasYearPrev);
    hideIfDisabled($('.ie-year-next', container), !!state.yearsNav?.hasYearNext);
    hideIfDisabled($('.ie-index-prev', container), !!state.nav?.hasPrev);
    hideIfDisabled($('.ie-index-next', container), !!state.nav?.hasNext);
  }

  async function loadIndexById(container, state, id) {
    setLoading(container, true);
    setStatus(container, IndicesEstar.i18n.loading);

    const idxRes = await apiCachedIndex(state, id);
    if (!idxRes?.success) { setStatus(container, IndicesEstar.i18n.noData); setLoading(container,false); return; }

    const idx = idxRes.data.index;
    state.nav = idxRes.data.nav || state.nav;
    state.yearsNav = idxRes.data.yearsNav || state.yearsNav;

    const currentYearShown = parseInt($('.ie-year', container).textContent || '0', 10);
    if (currentYearShown !== idx.year) { await loadYear(container, state, idx.year, idx.id); return; }

    $('.ie-year', container).textContent = String(idx.year);
    $('.ie-numdate', container).textContent = idx.date ? `${idx.number} · ${idx.date}` : String(idx.number);
    syncSearchToYear(container, state, idx.year);
    
    const itemsWrap = $('.ie-items', container);
    itemsWrap.innerHTML = '';
    const items = idxRes.data.items || [];
    if (!items.length) {
      const p=document.createElement('p'); p.className='ie-empty'; p.textContent=IndicesEstar.i18n.noData; itemsWrap.appendChild(p);
    } else {
      items.forEach(it => {
        const row=document.createElement('div'); row.className='ie-item-row';
        const sec=document.createElement('div'); sec.className='ie-item-sec'; sec.textContent=it.section || '';
        const title=document.createElement(it.url ? 'a' : 'div'); title.className='ie-item-title'; title.textContent=it.title || '';
        if (it.url) { title.href=it.url; title.target='_self'; title.rel='noopener'; }
        const auth=document.createElement('div'); auth.className='ie-item-author'; auth.textContent=it.author || '';
        row.appendChild(sec); row.appendChild(title); row.appendChild(auth);
        itemsWrap.appendChild(row);
      });
    }

    const img=$('.ie-image', container);
    const imgLink=$('.ie-image-link', container);
    const titleLink=$('.ie-image-title-link', container);
    const titleText=$('.ie-image-title-text', container);

    titleText.textContent = idx.imageTitle || '';
    if (idx.imageUrl) { img.src=idx.imageUrl; img.alt=idx.imageTitle || ''; } else { img.removeAttribute('src'); img.alt=''; }

    if (idx.url) {
      // Linked cover
      imgLink.style.display = idx.imageUrl ? 'block' : 'none';
      imgLink.href = idx.url;
      imgLink.target = '_blank';
      imgLink.removeAttribute('aria-disabled');

      titleLink.style.display = 'inline';
      titleLink.href = idx.url;
      titleLink.target = '_blank';
      titleLink.textContent = idx.imageTitle || '';
      titleText.textContent = '';
    } else {
      // Non-linked cover: keep the image visible (if any) but disable link behavior.
      imgLink.style.display = idx.imageUrl ? 'block' : 'none';
      imgLink.removeAttribute('href');
      imgLink.removeAttribute('target');
      imgLink.setAttribute('aria-disabled', 'true');

      titleLink.style.display = 'none';
      titleLink.textContent = '';
      titleText.textContent = idx.imageTitle || '';
    }

    markActiveNumber(container, idx.id);
        updateNavVisibility(container, state);

    setLoading(container, false);
    setStatus(container, '');
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.indices-estar').forEach(init);
  });
})();