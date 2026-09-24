const SHIFT_BASE = (window.SHIFT_CONFIG?.baseUrl || '').replace(/\/$/, '');
const SHIFT_CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
window.shiftFavoriteIds = new Set();

let shiftCatalogPromise = null;
let shiftModalOpen = false;

function shiftUrl(path = '') {
  return `${SHIFT_BASE}/${String(path).replace(/^\//, '')}`;
}

function shiftMoney(value) {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'EUR'
  }).format(Number(value));
}

function shiftEscape(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

async function shiftFetch(path, options = {}) {
  const opts = { ...options };
  opts.headers = {
    Accept: 'application/json',
    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
    ...(options.method && options.method !== 'GET' ? { 'X-CSRF-Token': SHIFT_CSRF } : {}),
    ...(options.headers || {})
  };

  const response = await fetch(shiftUrl(path), opts);
  let data = {};

  try {
    data = await response.json();
  } catch {
    data = {};
  }

  if (!response.ok) {
    const error = new Error(data.error || 'Ocorreu um erro.');
    error.status = response.status;
    error.data = data;
    throw error;
  }

  return data;
}

function shiftIcon(type) {
  const icons = {
    success: '<svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>',
    error: '<svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 8v5m0 3.5v.01"/><circle cx="12" cy="12" r="9"/></svg>',
    info: '<svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v5m0-8v.01"/></svg>'
  };
  return icons[type] || icons.info;
}

function shiftToast(message, type = 'success', options = {}) {
  let stack = document.getElementById('shiftToastStack');

  if (!stack) {
    stack = document.createElement('div');
    stack.id = 'shiftToastStack';
    stack.className = 'pointer-events-none fixed right-4 top-4 z-[120] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-3 sm:right-6 sm:top-6';
    document.body.appendChild(stack);
  }

  const toast = document.createElement('div');
  const tone = type === 'error'
    ? 'border-red-200 bg-white text-red-700'
    : type === 'info'
      ? 'border-blue-200 bg-white text-blue-700'
      : 'border-emerald-200 bg-white text-emerald-700';

  toast.className = `pointer-events-auto translate-x-4 rounded-xl border ${tone} p-4 opacity-0 shadow-xl shadow-slate-900/10 transition-all duration-200`;
  toast.innerHTML = `
    <div class="flex items-start gap-3">
      <div class="mt-0.5 shrink-0">${shiftIcon(type)}</div>
      <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold text-slate-900">${shiftEscape(options.title || (type === 'error' ? 'Algo não correu bem' : type === 'info' ? 'Informação' : 'Feito'))}</p>
        <p class="mt-0.5 text-sm leading-5 text-slate-500">${shiftEscape(message)}</p>
        ${options.actionLabel && options.actionHref ? `<a href="${shiftEscape(options.actionHref)}" class="mt-2 inline-flex text-xs font-bold text-blue-600 hover:underline">${shiftEscape(options.actionLabel)} →</a>` : ''}
      </div>
      <button type="button" class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Fechar">×</button>
    </div>
  `;

  stack.appendChild(toast);
  requestAnimationFrame(() => toast.classList.remove('translate-x-4', 'opacity-0'));

  const remove = () => {
    toast.classList.add('translate-x-4', 'opacity-0');
    setTimeout(() => toast.remove(), 220);
  };

  toast.querySelector('button')?.addEventListener('click', remove);
  setTimeout(remove, options.duration || 3600);
  return toast;
}

function shiftConfirm({
  title = 'Confirmar ação',
  message = 'Queres continuar?',
  confirmText = 'Confirmar',
  cancelText = 'Cancelar',
  danger = false
} = {}) {
  return new Promise(resolve => {
    if (shiftModalOpen) {
      resolve(false);
      return;
    }

    shiftModalOpen = true;
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 z-[140] flex items-center justify-center bg-slate-950/40 p-4 opacity-0 backdrop-blur-sm transition-opacity duration-200';
    overlay.innerHTML = `
      <div class="w-full max-w-md translate-y-3 scale-[.98] rounded-2xl bg-white p-6 opacity-0 shadow-2xl transition-all duration-200" role="dialog" aria-modal="true">
        <div class="flex items-start gap-4">
          <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full ${danger ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600'}">
            ${danger
              ? '<svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 4.9 2.9 18a2 2 0 0 0 1.74 3h14.72a2 2 0 0 0 1.74-3L13.7 4.9a2 2 0 0 0-3.4 0Z"/></svg>'
              : '<svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v.01M12 11v5"/></svg>'}
          </div>
          <div class="min-w-0 flex-1">
            <h2 class="text-lg font-bold text-slate-900">${shiftEscape(title)}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">${shiftEscape(message)}</p>
          </div>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button type="button" data-modal-cancel class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">${shiftEscape(cancelText)}</button>
          <button type="button" data-modal-confirm class="rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition ${danger ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'}">${shiftEscape(confirmText)}</button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);
    const panel = overlay.firstElementChild;
    const confirmButton = overlay.querySelector('[data-modal-confirm]');

    const close = result => {
      overlay.classList.add('opacity-0');
      panel.classList.add('translate-y-3', 'scale-[.98]', 'opacity-0');
      document.removeEventListener('keydown', onKeyDown);
      setTimeout(() => {
        overlay.remove();
        document.body.style.overflow = previousOverflow;
        shiftModalOpen = false;
        resolve(result);
      }, 190);
    };

    const onKeyDown = event => {
      if (event.key === 'Escape') close(false);
      if (event.key === 'Enter' && document.activeElement !== overlay.querySelector('[data-modal-cancel]')) close(true);
    };

    overlay.addEventListener('click', event => {
      if (event.target === overlay) close(false);
    });
    overlay.querySelector('[data-modal-cancel]')?.addEventListener('click', () => close(false));
    confirmButton?.addEventListener('click', () => close(true));
    document.addEventListener('keydown', onKeyDown);

    requestAnimationFrame(() => {
      overlay.classList.remove('opacity-0');
      panel.classList.remove('translate-y-3', 'scale-[.98]', 'opacity-0');
      confirmButton?.focus();
    });
  });
}

function shiftSetButtonBusy(button, busy, label = 'A processar...') {
  if (!button) return;

  if (busy) {
    if (!button.dataset.originalHtml) button.dataset.originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `
      <span class="inline-flex items-center justify-center gap-2">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3"></circle>
          <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
        </svg>
        ${shiftEscape(label)}
      </span>`;
  } else {
    button.disabled = false;
    if (button.dataset.originalHtml) {
      button.innerHTML = button.dataset.originalHtml;
      delete button.dataset.originalHtml;
    }
  }
}

function shiftSetButtonSuccess(button, duration = 850) {
  if (!button) return;
  const original = button.dataset.originalHtml || button.innerHTML;
  button.disabled = true;
  button.innerHTML = '<svg viewBox="0 0 24 24" class="mx-auto h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>';
  setTimeout(() => {
    button.innerHTML = original;
    button.disabled = false;
    delete button.dataset.originalHtml;
  }, duration);
}

function shiftSkeletonCards(count = 4) {
  return Array.from({ length: count }, () => `
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div class="aspect-square animate-pulse bg-slate-100"></div>
      <div class="p-4">
        <div class="h-3 w-20 animate-pulse rounded bg-slate-100"></div>
        <div class="mt-3 h-5 w-3/4 animate-pulse rounded bg-slate-100"></div>
        <div class="mt-2 h-4 w-1/2 animate-pulse rounded bg-slate-100"></div>
        <div class="mt-5 flex items-center justify-between"><div class="h-6 w-24 animate-pulse rounded bg-slate-100"></div><div class="h-10 w-10 animate-pulse rounded-lg bg-slate-100"></div></div>
      </div>
    </div>
  `).join('');
}

async function shiftRefreshCartCount() {
  try {
    const data = await shiftFetch('api/cart.php');
    const count = Number(data.totals?.count || 0);

    document.querySelectorAll('[data-cart-count]').forEach(counter => {
      const previous = Number(counter.textContent || 0);
      counter.textContent = count;
      counter.classList.toggle('hidden', count === 0);
      if (count !== previous && count > 0) {
        counter.classList.add('scale-125');
        setTimeout(() => counter.classList.remove('scale-125'), 180);
      }
    });

    return data;
  } catch {
    return null;
  }
}

async function shiftAddToCart(productId, quantity = 1, button = null) {
  shiftSetButtonBusy(button, true, '');

  try {
    const data = await shiftFetch('api/cart.php', {
      method: 'POST',
      body: JSON.stringify({ product_id: Number(productId), quantity: Number(quantity) })
    });

    const count = Number(data.totals?.count || 0);
    document.querySelectorAll('[data-cart-count]').forEach(counter => {
      counter.textContent = count;
      counter.classList.toggle('hidden', count === 0);
      counter.classList.add('scale-125');
      setTimeout(() => counter.classList.remove('scale-125'), 180);
    });

    shiftSetButtonSuccess(button);
    shiftToast('O serviço foi adicionado ao teu carrinho.', 'success', {
      title: 'Adicionado ao carrinho',
      actionLabel: 'Ver carrinho',
      actionHref: shiftUrl('cart.php')
    });
    return true;
  } catch (error) {
    shiftSetButtonBusy(button, false);
    shiftToast(error.message, 'error');
    return false;
  }
}

async function shiftLoadFavorites() {
  if (!window.SHIFT_CONFIG?.authenticated) return;

  try {
    const data = await shiftFetch('api/favorites.php');
    window.shiftFavoriteIds = new Set((data.favorites || []).map(item => Number(item.id)));
    shiftSyncFavoriteButtons();
  } catch {
    // A loja continua funcional mesmo que favoritos falhem.
  }
}

function shiftSyncFavoriteButtons() {
  document.querySelectorAll('[data-wishlist]').forEach(button => {
    const id = Number(button.dataset.wishlist);
    const active = window.shiftFavoriteIds.has(id) || button.dataset.active === 'true';

    button.classList.toggle('bg-red-50', active);
    button.classList.toggle('text-red-600', active);
    button.classList.toggle('border-red-200', active);
    button.setAttribute('aria-pressed', active ? 'true' : 'false');

    const svg = button.querySelector('svg');
    if (svg) svg.setAttribute('fill', active ? 'currentColor' : 'none');
  });
}

async function shiftToggleFavorite(productId, forceRemove = false, button = null) {
  if (!window.SHIFT_CONFIG?.authenticated) {
    const shouldLogin = await shiftConfirm({
      title: 'Guardar nos favoritos',
      message: 'Inicia sessão para guardares serviços nos favoritos e encontrares tudo em qualquer dispositivo.',
      confirmText: 'Entrar',
      cancelText: 'Agora não'
    });
    if (shouldLogin) window.location.href = shiftUrl('auth/login.php');
    return false;
  }

  const id = Number(productId);
  const active = forceRemove || window.shiftFavoriteIds.has(id);
  button?.classList.add('scale-90');

  try {
    await shiftFetch('api/favorites.php', {
      method: active ? 'DELETE' : 'POST',
      body: JSON.stringify({ product_id: id })
    });

    if (active) {
      window.shiftFavoriteIds.delete(id);
      if (button) button.dataset.active = 'false';
      shiftToast('O serviço saiu dos teus favoritos.', 'info', { title: 'Favoritos atualizados' });
    } else {
      window.shiftFavoriteIds.add(id);
      shiftToast('Podes encontrá-lo novamente na tua conta.', 'success', { title: 'Guardado nos favoritos' });
    }

    shiftSyncFavoriteButtons();
    setTimeout(() => button?.classList.remove('scale-90'), 140);
    return true;
  } catch (error) {
    button?.classList.remove('scale-90');
    shiftToast(error.message, 'error');
    return false;
  }
}

function shiftProductCard(product) {
  const activeFavorite = window.shiftFavoriteIds.has(Number(product.id));

  return `
    <article data-reveal class="group overflow-hidden rounded-xl border border-slate-200 bg-white transition duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl hover:shadow-slate-900/5">
      <div class="relative overflow-hidden bg-slate-50">
        <a href="${shiftUrl(`product.php?id=${product.id}`)}" class="block">
          <img src="${shiftUrl(product.image)}" alt="${shiftEscape(product.name)}" class="aspect-square w-full object-cover transition duration-500 group-hover:scale-[1.04]">
        </a>

        ${product.badge ? `<span class="absolute left-3 top-3 rounded-md bg-slate-900 px-2.5 py-1 text-[11px] font-bold text-white shadow-sm">${shiftEscape(product.badge)}</span>` : ''}

        <button type="button" data-wishlist="${product.id}" aria-label="Favorito"
          class="absolute right-3 top-3 grid h-9 w-9 place-items-center rounded-full border border-transparent bg-white text-slate-700 shadow-sm transition duration-200 hover:scale-105 hover:bg-slate-100 ${activeFavorite ? 'border-red-200 bg-red-50 text-red-600' : ''}">
          <svg viewBox="0 0 24 24" class="h-5 w-5" fill="${activeFavorite ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25s-7.5-4.35-7.5-10.125A4.125 4.125 0 0 1 12 7.73a4.125 4.125 0 0 1 7.5 2.395C19.5 15.9 12 20.25 12 20.25Z"/>
          </svg>
        </button>
      </div>

      <div class="p-4">
        <a href="${shiftUrl(`product.php?id=${product.id}`)}" class="block">
          <p class="text-xs font-medium text-slate-500">${shiftEscape(product.category)}</p>
          <h3 class="mt-1 min-h-12 text-base font-semibold leading-6 text-slate-900 transition group-hover:text-blue-600">${shiftEscape(product.name)}</h3>
        </a>

        <div class="mt-2 flex items-center gap-1.5 text-xs">
          <div class="text-amber-400">★★★★★</div>
          <span class="text-slate-500">${Number(product.rating).toFixed(1)} (${Number(product.reviews_count)})</span>
        </div>

        <div class="mt-3 flex items-end justify-between gap-3">
          <div>
            <span class="text-lg font-bold text-slate-900">${shiftMoney(product.price)}</span>
            ${product.old_price ? `<span class="ml-1 text-sm text-slate-400 line-through">${shiftMoney(product.old_price)}</span>` : ''}
          </div>

          <button type="button" data-add-cart="${product.id}" ${Number(product.stock) < 1 ? 'disabled' : ''}
            class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-blue-600 text-white transition duration-200 hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-md disabled:cursor-not-allowed disabled:bg-slate-300"
            aria-label="Adicionar ao carrinho">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l2.1 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L20.5 7H6"/>
              <circle cx="9" cy="19" r="1.25"/><circle cx="18" cy="19" r="1.25"/>
            </svg>
          </button>
        </div>
      </div>
    </article>
  `;
}

function shiftBindProductActions(scope = document) {
  scope.querySelectorAll('[data-add-cart]').forEach(button => {
    button.addEventListener('click', () => shiftAddToCart(Number(button.dataset.addCart), 1, button));
  });

  scope.querySelectorAll('[data-wishlist]').forEach(button => {
    button.addEventListener('click', () => shiftToggleFavorite(Number(button.dataset.wishlist), false, button));
  });

  shiftSyncFavoriteButtons();
  shiftObserveReveals(scope);
}

function shiftGetCatalog() {
  if (!shiftCatalogPromise) shiftCatalogPromise = shiftFetch('api/products.php');
  return shiftCatalogPromise;
}

function shiftSetupSearch() {
  document.querySelectorAll('[data-site-search]').forEach(form => {
    form.classList.add('relative');
    const input = form.querySelector('input');
    if (!input) return;

    const dropdown = document.createElement('div');
    dropdown.className = 'absolute left-0 right-0 top-[calc(100%+.5rem)] z-[80] hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10';
    form.appendChild(dropdown);

    let timer = null;

    const hide = () => dropdown.classList.add('hidden');

    const renderSuggestions = async () => {
      const query = input.value.trim().toLowerCase();
      if (query.length < 2) {
        hide();
        return;
      }

      dropdown.classList.remove('hidden');
      dropdown.innerHTML = '<div class="flex items-center gap-2 px-4 py-4 text-sm text-slate-400"><span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-slate-500"></span>A pesquisar…</div>';

      try {
        const data = await shiftGetCatalog();
        const matches = (data.products || []).filter(product =>
          String(product.name).toLowerCase().includes(query) ||
          String(product.category).toLowerCase().includes(query) ||
          String(product.short_description).toLowerCase().includes(query)
        ).slice(0, 5);

        if (!matches.length) {
          dropdown.innerHTML = `<div class="px-4 py-5 text-sm text-slate-500">Sem resultados para <strong>${shiftEscape(input.value.trim())}</strong>.</div>`;
          return;
        }

        dropdown.innerHTML = `
          <div class="p-2">
            ${matches.map(product => `
              <a href="${shiftUrl(`product.php?id=${product.id}`)}" class="flex items-center gap-3 rounded-lg p-2 transition hover:bg-slate-50">
                <img src="${shiftUrl(product.image)}" alt="" class="h-12 w-12 rounded-lg border border-slate-200 bg-slate-50 object-cover">
                <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-slate-900">${shiftEscape(product.name)}</p><p class="mt-0.5 text-xs text-slate-500">${shiftEscape(product.category)}</p></div>
                <span class="text-sm font-bold text-slate-900">${shiftMoney(product.price)}</span>
              </a>`).join('')}
          </div>
          <a href="${shiftUrl(`shop.php?search=${encodeURIComponent(input.value.trim())}`)}" class="flex items-center justify-between border-t border-slate-100 px-4 py-3 text-xs font-bold text-blue-600 hover:bg-blue-50">
            Ver todos os resultados <span>→</span>
          </a>`;
      } catch {
        hide();
      }
    };

    input.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(renderSuggestions, 220);
    });

    input.addEventListener('focus', () => {
      if (input.value.trim().length >= 2) renderSuggestions();
    });

    form.addEventListener('submit', event => {
      event.preventDefault();
      const query = input.value.trim();
      window.location.href = shiftUrl(query ? `shop.php?search=${encodeURIComponent(query)}` : 'shop.php');
    });

    document.addEventListener('click', event => {
      if (!form.contains(event.target)) hide();
    });

    input.addEventListener('keydown', event => {
      if (event.key === 'Escape') hide();
    });
  });
}

function shiftCreateMiniCart() {
  if (document.getElementById('shiftCartDrawer')) return;

  const wrapper = document.createElement('div');
  wrapper.id = 'shiftCartDrawer';
  wrapper.className = 'pointer-events-none fixed inset-0 z-[130]';
  wrapper.innerHTML = `
    <div data-cart-overlay class="absolute inset-0 bg-slate-950/30 opacity-0 backdrop-blur-[2px] transition-opacity duration-300"></div>
    <aside data-cart-panel class="absolute right-0 top-0 flex h-full w-full max-w-md translate-x-full flex-col bg-white shadow-2xl transition-transform duration-300">
      <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Resumo rápido</p><h2 class="mt-1 text-xl font-bold">Carrinho</h2></div>
        <button data-cart-close class="grid h-10 w-10 place-items-center rounded-full text-xl text-slate-500 hover:bg-slate-100" aria-label="Fechar">×</button>
      </div>
      <div data-cart-body class="flex-1 overflow-y-auto p-5"></div>
      <div data-cart-footer class="border-t border-slate-200 p-5"></div>
    </aside>`;
  document.body.appendChild(wrapper);

  const close = () => shiftCloseMiniCart();
  wrapper.querySelector('[data-cart-overlay]')?.addEventListener('click', close);
  wrapper.querySelector('[data-cart-close]')?.addEventListener('click', close);
}

async function shiftRenderMiniCart() {
  const drawer = document.getElementById('shiftCartDrawer');
  if (!drawer) return;
  const body = drawer.querySelector('[data-cart-body]');
  const footer = drawer.querySelector('[data-cart-footer]');
  body.innerHTML = '<div class="space-y-4">' + Array.from({length:3}, () => '<div class="flex gap-3"><div class="h-16 w-16 animate-pulse rounded-lg bg-slate-100"></div><div class="flex-1"><div class="h-4 w-2/3 animate-pulse rounded bg-slate-100"></div><div class="mt-2 h-3 w-1/3 animate-pulse rounded bg-slate-100"></div></div></div>').join('') + '</div>';
  footer.innerHTML = '';

  try {
    const data = await shiftFetch('api/cart.php');
    const items = data.items || [];
    const totals = data.totals || {};

    if (!items.length) {
      body.innerHTML = '<div class="grid h-full min-h-72 place-items-center text-center"><div><div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-slate-100 text-2xl text-slate-400">○</div><p class="mt-4 font-semibold">O carrinho está vazio</p><p class="mt-1 text-sm text-slate-500">Explora os serviços da SHIFT e adiciona o que precisares.</p></div></div>';
      footer.innerHTML = `<a href="${shiftUrl('shop.php')}" class="flex w-full justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700">Explorar serviços</a>`;
      return;
    }

    body.innerHTML = items.map(item => `
      <div class="flex gap-3 border-b border-slate-100 py-4 first:pt-0">
        <img src="${shiftUrl(item.image)}" alt="" class="h-16 w-16 shrink-0 rounded-lg border border-slate-200 bg-slate-50 object-cover">
        <div class="min-w-0 flex-1"><a href="${shiftUrl(`product.php?id=${item.id}`)}" class="block truncate text-sm font-semibold hover:text-blue-600">${shiftEscape(item.name)}</a><p class="mt-1 text-xs text-slate-500">${item.quantity} × ${shiftMoney(item.price)}</p></div>
        <button data-mini-remove="${item.id}" class="h-8 w-8 shrink-0 rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Remover">×</button>
      </div>`).join('');

    footer.innerHTML = `
      <div class="flex items-center justify-between"><span class="text-sm text-slate-500">Total</span><strong class="text-lg">${shiftMoney(totals.total)}</strong></div>
      <div class="mt-4 grid grid-cols-2 gap-2"><a href="${shiftUrl('cart.php')}" class="flex justify-center rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ver carrinho</a><a href="${shiftUrl('checkout.php')}" class="flex justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700">Checkout</a></div>`;

    body.querySelectorAll('[data-mini-remove]').forEach(button => {
      button.addEventListener('click', async () => {
        const ok = await shiftConfirm({ title: 'Remover do carrinho?', message: 'Este serviço será retirado do teu carrinho.', confirmText: 'Remover', danger: true });
        if (!ok) return;
        try {
          await shiftFetch('api/cart.php', { method: 'DELETE', body: JSON.stringify({ product_id: Number(button.dataset.miniRemove) }) });
          shiftToast('Serviço removido do carrinho.', 'info', { title: 'Carrinho atualizado' });
          await shiftRefreshCartCount();
          await shiftRenderMiniCart();
        } catch (error) {
          shiftToast(error.message, 'error');
        }
      });
    });
  } catch (error) {
    body.innerHTML = `<div class="rounded-lg bg-red-50 p-4 text-sm text-red-700">${shiftEscape(error.message)}</div>`;
  }
}

async function shiftOpenMiniCart() {
  shiftCreateMiniCart();
  const drawer = document.getElementById('shiftCartDrawer');
  const overlay = drawer.querySelector('[data-cart-overlay]');
  const panel = drawer.querySelector('[data-cart-panel]');
  drawer.classList.remove('pointer-events-none');
  document.body.classList.add('overflow-hidden');
  requestAnimationFrame(() => {
    overlay.classList.remove('opacity-0');
    panel.classList.remove('translate-x-full');
  });
  await shiftRenderMiniCart();
}

function shiftCloseMiniCart() {
  const drawer = document.getElementById('shiftCartDrawer');
  if (!drawer) return;
  drawer.querySelector('[data-cart-overlay]')?.classList.add('opacity-0');
  drawer.querySelector('[data-cart-panel]')?.classList.add('translate-x-full');
  document.body.classList.remove('overflow-hidden');
  setTimeout(() => drawer.classList.add('pointer-events-none'), 300);
}

function shiftBindCartTrigger() {
  document.querySelectorAll('[data-cart-trigger]').forEach(trigger => {
    trigger.addEventListener('click', event => {
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      shiftOpenMiniCart();
    });
  });
}

function shiftBindConfirmForms() {
  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', async event => {
      if (form.dataset.confirmed === 'true') return;
      event.preventDefault();

      const ok = await shiftConfirm({
        title: form.dataset.confirmTitle || 'Confirmar ação',
        message: form.dataset.confirmMessage || 'Esta ação pode alterar os dados guardados.',
        confirmText: form.dataset.confirmText || 'Confirmar',
        cancelText: form.dataset.confirmCancel || 'Cancelar',
        danger: form.dataset.confirmDanger === 'true'
      });

      if (!ok) return;
      form.dataset.confirmed = 'true';
      const submit = form.querySelector('[type="submit"], button:not([type])');
      shiftSetButtonBusy(submit, true, submit?.dataset.loadingText || 'A processar...');
      HTMLFormElement.prototype.submit.call(form);
    });
  });

  document.querySelectorAll('form:not([data-confirm])').forEach(form => {
    form.addEventListener('submit', () => {
      const submit = form.querySelector('[type="submit"], button:not([type])');
      if (submit && !form.hasAttribute('data-no-loading')) {
        shiftSetButtonBusy(submit, true, submit.dataset.loadingText || 'A guardar...');
      }
    });
  });
}

function shiftEnhanceFlashMessages() {
  document.querySelectorAll('[data-flash]').forEach(flash => {
    flash.classList.add('relative', 'pr-11', 'transition-all', 'duration-300');
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'absolute right-3 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-full text-current opacity-50 hover:bg-black/5 hover:opacity-100';
    close.textContent = '×';
    close.setAttribute('aria-label', 'Fechar mensagem');
    close.addEventListener('click', () => {
      flash.classList.add('-translate-y-1', 'opacity-0');
      setTimeout(() => flash.remove(), 280);
    });
    flash.appendChild(close);
  });
}

function shiftObserveReveals(scope = document) {
  if (!('IntersectionObserver' in window)) return;

  if (!window.shiftRevealObserver) {
    window.shiftRevealObserver = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        entry.target.classList.remove('translate-y-3', 'opacity-0');
        window.shiftRevealObserver.unobserve(entry.target);
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
  }

  scope.querySelectorAll?.('[data-reveal]:not([data-reveal-bound])').forEach(element => {
    element.dataset.revealBound = 'true';
    element.classList.add('translate-y-3', 'opacity-0', 'transition-all', 'duration-500');
    window.shiftRevealObserver.observe(element);
  });
}

function shiftInitPageMotion() {
  document.querySelectorAll('main > section, main > div').forEach((element, index) => {
    if (index < 8 && !element.hasAttribute('data-no-reveal')) element.setAttribute('data-reveal', '');
  });
  shiftObserveReveals(document);
}

function shiftInitHeader() {
  const header = document.querySelector('[data-shift-header]');
  if (!header) return;

  const update = () => header.classList.toggle('shadow-md', window.scrollY > 8);
  update();
  window.addEventListener('scroll', update, { passive: true });

  const currentCategory = new URLSearchParams(location.search).get('category');
  document.querySelectorAll('[data-category-link]').forEach(link => {
    const active = currentCategory && link.dataset.categoryLink === currentCategory;
    link.classList.toggle('text-blue-600', Boolean(active));
    link.classList.toggle('font-bold', Boolean(active));
  });
}

function shiftInitBackToTop() {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'fixed bottom-5 right-5 z-[70] grid h-11 w-11 translate-y-3 place-items-center rounded-full bg-slate-900 text-white opacity-0 shadow-xl transition-all duration-200 pointer-events-none hover:-translate-y-0.5 hover:bg-slate-800';
  button.setAttribute('aria-label', 'Voltar ao topo');
  button.innerHTML = '<svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 15 6-6 6 6"/></svg>';
  document.body.appendChild(button);

  const update = () => {
    const visible = window.scrollY > 650;
    button.classList.toggle('opacity-0', !visible);
    button.classList.toggle('translate-y-3', !visible);
    button.classList.toggle('pointer-events-none', !visible);
  };
  window.addEventListener('scroll', update, { passive: true });
  button.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

function shiftInitInputFeedback() {
  document.querySelectorAll('input, select, textarea').forEach(input => {
    input.classList.add('transition', 'duration-150');
    input.addEventListener('invalid', () => {
      input.classList.add('border-red-400', 'ring-2', 'ring-red-100');
    });
    input.addEventListener('input', () => {
      if (input.checkValidity()) input.classList.remove('border-red-400', 'ring-2', 'ring-red-100');
    });
  });
}

document.addEventListener('keydown', event => {
  if (event.key === 'Escape') shiftCloseMiniCart();
});

document.addEventListener('DOMContentLoaded', async () => {
  shiftSetupSearch();
  shiftBindCartTrigger();
  shiftBindConfirmForms();
  shiftEnhanceFlashMessages();
  shiftInitPageMotion();
  shiftInitHeader();
  shiftInitBackToTop();
  shiftInitInputFeedback();

  await Promise.all([
    shiftRefreshCartCount(),
    shiftLoadFavorites()
  ]);
});

function shiftBindLogoutLinks() {
  document.querySelectorAll('[data-logout-link]').forEach(link => {
    link.addEventListener('click', async event => {
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      event.preventDefault();

      const ok = await shiftConfirm({
        title: 'Terminar sessão?',
        message: 'Tens a certeza de que queres sair da tua conta SHIFT?',
        confirmText: 'Terminar sessão',
        cancelText: 'Continuar ligado',
        danger: true
      });

      if (ok) window.location.href = link.href;
    });
  });
}

function shiftBindHistoryBack() {
  document.querySelectorAll('[data-history-back]').forEach(button => {
    button.addEventListener('click', () => {
      const fallback = button.dataset.fallback || shiftUrl('account/orders.php');
      if (window.history.length > 1 && document.referrer) {
        window.history.back();
      } else {
        window.location.href = fallback;
      }
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  shiftBindLogoutLinks();
  shiftBindHistoryBack();
});
