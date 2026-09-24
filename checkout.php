<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';

Auth::requireLogin();

$pdo = Database::connection();

$addressStmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
$addressStmt->execute([Auth::id()]);
$addresses = $addressStmt->fetchAll();

$paymentStmt = $pdo->prepare('SELECT * FROM payment_methods WHERE user_id = ? AND is_active = 1 ORDER BY is_default DESC, id DESC');
$paymentStmt->execute([Auth::id()]);
$paymentMethods = $paymentStmt->fetchAll();

render_header('Checkout — SHIFT');
?>
<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <nav class="text-sm text-slate-500">
        <a href="<?= e(app_url('')) ?>" class="hover:text-blue-600">Início</a><span class="mx-2">/</span>
        <a href="<?= e(app_url('cart.php')) ?>" class="hover:text-blue-600">Carrinho</a><span class="mx-2">/</span><span class="text-slate-900">Checkout</span>
    </nav>
    <h1 class="mt-5 text-4xl font-bold tracking-tight">Checkout</h1>

    <div id="checkoutMessage" class="mt-6 hidden rounded-xl p-5 text-sm"></div>

    <div id="checkoutLayout" class="mt-8 grid gap-8 lg:grid-cols-[1fr_390px]">
        <div class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-6">
                <div class="flex items-center justify-between gap-4">
                    <div><h2 class="text-lg font-bold">1. Morada</h2><p class="mt-1 text-sm text-slate-500">Escolhe onde queres associar a entrega/faturação.</p></div>
                    <a href="<?= e(app_url('account/addresses.php')) ?>" class="text-sm font-semibold text-blue-600">Gerir</a>
                </div>

                <?php if (!$addresses): ?>
                    <div class="mt-5 rounded-lg bg-amber-50 p-4 text-sm text-amber-800">Ainda não tens moradas. <a class="font-semibold underline" href="<?= e(app_url('account/addresses.php')) ?>">Adicionar morada</a></div>
                <?php else: ?>
                    <div class="mt-5 grid gap-3">
                    <?php foreach ($addresses as $address): ?>
                        <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-300 p-4 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="address_id" value="<?= (int)$address['id'] ?>" <?= (int)$address['is_default'] ? 'checked' : '' ?>>
                            <span class="text-sm">
                                <strong class="block"><?= e($address['label']) ?> — <?= e($address['recipient_name']) ?></strong>
                                <span class="mt-1 block text-slate-500"><?= e($address['line1']) ?><?= $address['line2'] ? ', ' . e($address['line2']) : '' ?> · <?= e($address['postal_code']) ?> <?= e($address['city']) ?> · <?= e($address['country']) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6">
                <div class="flex items-center justify-between gap-4">
                    <div><h2 class="text-lg font-bold">2. Pagamento</h2><p class="mt-1 text-sm text-slate-500">Métodos tokenizados. Nunca guardamos número completo ou CVV.</p></div>
                    <a href="<?= e(app_url('account/payment-methods.php')) ?>" class="text-sm font-semibold text-blue-600">Gerir</a>
                </div>

                <div class="mt-5 grid gap-3">
                    <?php if ($paymentMethods): foreach ($paymentMethods as $method): ?>
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-300 p-4 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="payment_method_id" value="<?= (int)$method['id'] ?>" <?= (int)$method['is_default'] ? 'checked' : '' ?>>
                            <div><p class="text-sm font-semibold"><?= e(strtoupper($method['brand'])) ?> •••• <?= e($method['last_four']) ?></p><p class="text-xs text-slate-500">Expira <?= e(sprintf('%02d/%d', $method['expiry_month'], $method['expiry_year'])) ?> · <?= e($method['provider']) ?></p></div>
                        </label>
                    <?php endforeach; endif; ?>

                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-300 p-4 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <input type="radio" name="payment_method_id" value="" <?= !$paymentMethods ? 'checked' : '' ?>>
                        <div><p class="text-sm font-semibold">Pagamento demo/manual</p><p class="text-xs text-slate-500">Cria a encomenda como pagamento pendente. Não cobra nada.</p></div>
                    </label>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-bold">3. Notas</h2>
                <textarea id="orderNotes" rows="4" maxlength="1000" placeholder="Briefing inicial ou alguma indicação..." class="mt-4 w-full resize-none rounded-lg border border-slate-300 px-3 py-3 text-sm outline-none focus:border-blue-500"></textarea>
            </section>

            <button id="placeOrder" type="button" <?= !$addresses ? 'disabled' : '' ?> class="w-full rounded-lg bg-blue-600 px-6 py-4 text-sm font-bold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">Confirmar encomenda</button>
        </div>

        <aside class="h-fit rounded-xl border border-slate-200 bg-white p-6 lg:sticky lg:top-6">
            <h2 class="text-lg font-bold">A tua encomenda</h2>
            <div id="checkoutItems" class="mt-4 divide-y divide-slate-200"></div>
            <div class="mt-5 space-y-3 border-t border-slate-200 pt-5 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span id="checkoutSubtotal" class="font-medium">—</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Portes</span><span id="checkoutShipping" class="font-medium">—</span></div>
                <div class="flex justify-between border-t border-slate-200 pt-4 text-base"><strong>Total</strong><strong id="checkoutTotal">—</strong></div>
            </div>
        </aside>
    </div>
</main>
<script src="<?= e(app_url('assets/js/checkout.js')) ?>"></script>
<?php render_footer(); ?>
