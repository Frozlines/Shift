<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout.php';
Auth::requireLogin();

$publicId = trim((string)($_GET['id'] ?? ''));
$stmt = Database::connection()->prepare('SELECT * FROM orders WHERE public_id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$publicId, Auth::id()]);
$order = $stmt->fetch();
if (!$order) {
    http_response_code(404);
    render_header('Encomenda não encontrada — SHIFT');
    echo '<main class="mx-auto max-w-4xl px-4 py-20 text-center"><h1 class="text-3xl font-bold">Encomenda não encontrada</h1></main>';
    render_footer();
    exit;
}
$stmt = Database::connection()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
$stmt->execute([(int)$order['id']]);
$items = $stmt->fetchAll();
$stmt = Database::connection()->prepare('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY id DESC');
$stmt->execute([(int)$order['id']]);
$history = $stmt->fetchAll();

render_header($order['public_id'] . ' — SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
<button type="button" data-history-back data-fallback="<?= e(app_url('account/orders.php')) ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-blue-600"><span aria-hidden="true">←</span> Voltar</button>
<div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm text-slate-500">Encomenda</p><h1 class="mt-1 text-3xl font-bold"><?= e($order['public_id']) ?></h1></div><div class="flex gap-2"><span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold"><?= e($order['status']) ?></span><span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700"><?= e($order['payment_status']) ?></span></div></div>
<div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]"><?php account_nav('orders'); ?><section class="space-y-6">
<div class="rounded-xl border border-slate-200 bg-white p-6"><h2 class="font-bold">Itens</h2><div class="mt-4 divide-y divide-slate-200"><?php foreach($items as $item): ?><div class="flex items-center justify-between gap-4 py-4"><div><p class="font-medium"><?= e($item['product_name']) ?></p><p class="mt-1 text-sm text-slate-500"><?= (int)$item['quantity'] ?> × <?= e(money((float)$item['unit_price'])) ?></p></div><p class="font-semibold"><?= e(money((float)$item['line_total'])) ?></p></div><?php endforeach; ?></div><div class="mt-4 space-y-2 border-t border-slate-200 pt-4 text-sm"><div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span><?= e(money((float)$order['subtotal'])) ?></span></div><div class="flex justify-between"><span class="text-slate-500">Portes</span><span><?= (float)$order['shipping']===0.0?'Grátis':e(money((float)$order['shipping'])) ?></span></div><div class="flex justify-between pt-2 text-base"><strong>Total</strong><strong><?= e(money((float)$order['total'])) ?></strong></div></div></div>
<div class="grid gap-6 md:grid-cols-2"><div class="rounded-xl border border-slate-200 bg-white p-6"><h2 class="font-bold">Entrega / faturação</h2><p class="mt-4 text-sm leading-6 text-slate-500"><?= e($order['customer_name']) ?><br><?= e($order['address_line1']) ?><?= $order['address_line2']?'<br>'.e($order['address_line2']):'' ?><br><?= e($order['postal_code']) ?> <?= e($order['city']) ?><br><?= e($order['country']) ?></p></div><div class="rounded-xl border border-slate-200 bg-white p-6"><h2 class="font-bold">Histórico</h2><div class="mt-4 space-y-4"><?php foreach($history as $h): ?><div><p class="text-sm font-semibold"><?= e($h['status']) ?></p><p class="text-xs text-slate-500"><?= e(date('d/m/Y H:i', strtotime($h['created_at']))) ?><?= $h['note']?' · '.e($h['note']):'' ?></p></div><?php endforeach; ?></div></div></div>
</section></div></main>
<?php render_footer(); ?>
