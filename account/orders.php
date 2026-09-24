<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout.php';
Auth::requireLogin();

$stmt = Database::connection()->prepare(
    'SELECT o.*, COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE o.user_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC'
);
$stmt->execute([Auth::id()]);
$orders = $stmt->fetchAll();

render_header('Encomendas — SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold">Encomendas</h1>
    <div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]">
        <?php account_nav('orders'); ?>
        <section>
            <?php if (!$orders): ?>
                <div class="rounded-xl border border-slate-200 bg-white p-10 text-center"><h2 class="text-lg font-semibold">Ainda não tens encomendas</h2><a href="<?= e(app_url('shop.php')) ?>" class="mt-5 inline-flex text-sm font-semibold text-blue-600">Explorar serviços →</a></div>
            <?php else: ?>
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Referência</th><th class="px-5 py-3">Data</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Pagamento</th><th class="px-5 py-3">Total</th></tr></thead>
                            <tbody class="divide-y divide-slate-200">
                            <?php foreach ($orders as $o): ?>
                                <tr><td class="px-5 py-4 font-semibold"><a class="text-blue-600 hover:underline" href="<?= e(app_url('account/order.php?id=' . urlencode($o['public_id']))) ?>"><?= e($o['public_id']) ?></a></td><td class="px-5 py-4 text-slate-500"><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td><td class="px-5 py-4"><?= e($o['status']) ?></td><td class="px-5 py-4"><?= e($o['payment_status']) ?></td><td class="px-5 py-4 font-semibold"><?= e(money((float)$o['total'])) ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php render_footer(); ?>
