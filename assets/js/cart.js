document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('cartItems');
  const emptyState = document.getElementById('cartEmpty');
  const content = document.getElementById('cartContent');

  async function changeQuantity(id, quantity, button = null) {
    shiftSetButtonBusy(button, true, '');
    try {
      await shiftFetch('api/cart.php', {
        method: 'PATCH',
        body: JSON.stringify({ product_id: id, quantity })
      });
      await render();
      await shiftRefreshCartCount();
    } catch (error) {
      shiftSetButtonBusy(button, false);
      shiftToast(error.message, 'error');
    }
  }

  async function removeItem(id) {
    const ok = await shiftConfirm({
      title: 'Remover do carrinho?',
      message: 'O serviço será retirado do carrinho. Podes voltar a adicioná-lo mais tarde.',
      confirmText: 'Remover',
      cancelText: 'Manter',
      danger: true
    });
    if (!ok) return;

    try {
      await shiftFetch('api/cart.php', {
        method: 'DELETE',
        body: JSON.stringify({ product_id: id })
      });
      shiftToast('O serviço foi removido.', 'info', { title: 'Carrinho atualizado' });
      await render();
      await shiftRefreshCartCount();
    } catch (error) {
      shiftToast(error.message, 'error');
    }
  }

  async function render() {
    try {
      container.innerHTML = '<div class="space-y-5 py-5">' + Array.from({length:2}, () => '<div class="flex gap-4"><div class="h-24 w-24 animate-pulse rounded-xl bg-slate-100"></div><div class="flex-1"><div class="h-4 w-20 animate-pulse rounded bg-slate-100"></div><div class="mt-3 h-5 w-1/2 animate-pulse rounded bg-slate-100"></div><div class="mt-3 h-4 w-24 animate-pulse rounded bg-slate-100"></div></div></div>').join('') + '</div>';
      const data = await shiftFetch('api/cart.php');
      const items = data.items || [];
      const totals = data.totals || {};

      if (!items.length) {
        content.classList.add('hidden');
        emptyState.classList.remove('hidden');
        return;
      }

      content.classList.remove('hidden');
      emptyState.classList.add('hidden');

      container.innerHTML = items.map(item => `
        <div data-reveal class="flex flex-col gap-4 border-b border-slate-200 py-5 sm:flex-row sm:items-center">
          <a href="${shiftUrl(`product.php?id=${item.id}`)}" class="shrink-0 overflow-hidden rounded-xl">
            <img src="${shiftUrl(item.image)}" alt="${shiftEscape(item.name)}" class="h-28 w-28 border border-slate-200 bg-slate-50 object-cover transition duration-300 hover:scale-105">
          </a>

          <div class="min-w-0 flex-1">
            <p class="text-xs font-medium text-slate-500">${shiftEscape(item.category)}</p>
            <a href="${shiftUrl(`product.php?id=${item.id}`)}" class="mt-1 block font-semibold transition hover:text-blue-600">${shiftEscape(item.name)}</a>
            <p class="mt-2 text-sm text-slate-500">${shiftMoney(item.price)} / unidade</p>
          </div>

          <div class="flex items-center justify-between gap-5 sm:justify-end">
            <div class="flex items-center overflow-hidden rounded-lg border border-slate-300 bg-white">
              <button data-minus="${item.id}" class="grid h-9 w-9 place-items-center text-slate-600 transition hover:bg-slate-50">−</button>
              <span class="min-w-9 text-center text-sm font-medium">${item.quantity}</span>
              <button data-plus="${item.id}" class="grid h-9 w-9 place-items-center text-slate-600 transition hover:bg-slate-50">+</button>
            </div>
            <div class="w-24 text-right">
              <p class="font-semibold">${shiftMoney(Number(item.price) * Number(item.quantity))}</p>
              <button data-remove="${item.id}" class="mt-1 text-xs font-medium text-red-500 transition hover:text-red-700 hover:underline">Remover</button>
            </div>
          </div>
        </div>
      `).join('');

      container.querySelectorAll('[data-minus]').forEach(button => {
        const item = items.find(i => Number(i.id) === Number(button.dataset.minus));
        button.addEventListener('click', () => changeQuantity(Number(item.id), Number(item.quantity) - 1, button));
      });
      container.querySelectorAll('[data-plus]').forEach(button => {
        const item = items.find(i => Number(i.id) === Number(button.dataset.plus));
        button.addEventListener('click', () => changeQuantity(Number(item.id), Math.min(Number(item.stock), Number(item.quantity) + 1), button));
      });
      container.querySelectorAll('[data-remove]').forEach(button =>
        button.addEventListener('click', () => removeItem(Number(button.dataset.remove)))
      );

      document.getElementById('cartSubtotal').textContent = shiftMoney(totals.subtotal);
      document.getElementById('cartShipping').textContent = Number(totals.shipping) === 0 ? 'Grátis' : shiftMoney(totals.shipping);
      document.getElementById('cartTotal').textContent = shiftMoney(totals.total);
      shiftObserveReveals(container);
    } catch (error) {
      container.innerHTML = `<div class="py-8 text-sm text-red-700">${shiftEscape(error.message)}</div>`;
    }
  }

  render();
});
