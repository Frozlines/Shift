document.addEventListener('DOMContentLoaded', () => {
  const placeOrder = document.getElementById('placeOrder');
  const message = document.getElementById('checkoutMessage');
  let currentTotal = 0;

  async function loadSummary() {
    try {
      document.getElementById('checkoutItems').innerHTML = '<div class="space-y-3 py-3">' + Array.from({length:2}, () => '<div class="flex gap-3"><div class="h-16 w-16 animate-pulse rounded-lg bg-slate-100"></div><div class="flex-1"><div class="h-4 w-2/3 animate-pulse rounded bg-slate-100"></div><div class="mt-2 h-3 w-1/3 animate-pulse rounded bg-slate-100"></div></div></div>').join('') + '</div>';
      const data = await shiftFetch('api/cart.php');
      const items = data.items || [];
      const totals = data.totals || {};
      currentTotal = Number(totals.total || 0);

      if (!items.length) {
        document.getElementById('checkoutLayout').classList.add('hidden');
        message.className = 'mt-6 rounded-xl bg-amber-50 p-5 text-sm text-amber-800';
        message.textContent = 'O carrinho está vazio.';
        return;
      }

      document.getElementById('checkoutItems').innerHTML = items.map(item => `
        <div class="flex items-center gap-3 py-3">
          <div class="relative shrink-0">
            <img src="${shiftUrl(item.image)}" alt="" class="h-16 w-16 rounded-lg border border-slate-200 bg-slate-50 object-cover">
            <span class="absolute -right-2 -top-2 min-w-5 rounded-full bg-slate-900 px-1.5 py-0.5 text-center text-[10px] font-bold text-white">${item.quantity}</span>
          </div>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium">${shiftEscape(item.name)}</p>
            <p class="mt-1 text-xs text-slate-500">${shiftMoney(item.price)} / unidade</p>
          </div>
          <span class="text-sm font-semibold">${shiftMoney(Number(item.price) * Number(item.quantity))}</span>
        </div>
      `).join('');

      document.getElementById('checkoutSubtotal').textContent = shiftMoney(totals.subtotal);
      document.getElementById('checkoutShipping').textContent = Number(totals.shipping) === 0 ? 'Grátis' : shiftMoney(totals.shipping);
      document.getElementById('checkoutTotal').textContent = shiftMoney(totals.total);
    } catch (error) {
      message.className = 'mt-6 rounded-xl bg-red-50 p-5 text-sm text-red-700';
      message.textContent = error.message;
    }
  }

  placeOrder?.addEventListener('click', async () => {
    const address = document.querySelector('input[name="address_id"]:checked');
    const payment = document.querySelector('input[name="payment_method_id"]:checked');

    if (!address) {
      shiftToast('Escolhe ou adiciona uma morada antes de continuares.', 'error', { title: 'Falta a morada' });
      return;
    }

    const confirmed = await shiftConfirm({
      title: 'Confirmar encomenda?',
      message: `Vais criar uma encomenda no valor de ${shiftMoney(currentTotal)}. Nesta versão o pagamento ficará pendente.`,
      confirmText: 'Confirmar encomenda',
      cancelText: 'Voltar'
    });
    if (!confirmed) return;

    shiftSetButtonBusy(placeOrder, true, 'A criar encomenda...');

    try {
      const data = await shiftFetch('api/checkout.php', {
        method: 'POST',
        body: JSON.stringify({
          address_id: Number(address.value),
          payment_method_id: payment?.value ? Number(payment.value) : null,
          notes: document.getElementById('orderNotes')?.value || ''
        })
      });

      message.className = 'mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-800';
      message.innerHTML = `
        <div class="flex items-start gap-3">
          <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700">✓</div>
          <div><strong class="block text-base text-emerald-900">Encomenda criada com sucesso</strong><p class="mt-1">Referência <strong>${shiftEscape(data.order.public_id)}</strong> · Total <strong>${shiftMoney(data.order.total)}</strong>. O pagamento continua pendente nesta versão demo.</p><a href="${shiftUrl('account/orders.php')}" class="mt-3 inline-flex font-bold text-emerald-800 underline underline-offset-2">Ver as minhas encomendas →</a></div>
        </div>`;
      document.getElementById('checkoutLayout').classList.add('hidden');
      await shiftRefreshCartCount();
      shiftToast('A encomenda foi criada e já aparece na tua conta.', 'success', { title: 'Encomenda confirmada' });
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
      message.className = 'mt-6 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-700';
      message.textContent = error.message;
      shiftToast(error.message, 'error');
      shiftSetButtonBusy(placeOrder, false);
    }
  });

  document.querySelectorAll('input[name="address_id"], input[name="payment_method_id"]').forEach(input => {
    input.addEventListener('change', () => {
      const card = input.closest('label');
      card?.classList.add('scale-[1.01]');
      setTimeout(() => card?.classList.remove('scale-[1.01]'), 160);
    });
  });

  loadSummary();
});
