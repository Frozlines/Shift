<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout.php';
Auth::requireAdmin();

$id=(int)($_GET['id']??0);
$pdo=Database::connection();
$stmt=$pdo->prepare('SELECT * FROM orders WHERE id=? LIMIT 1'); $stmt->execute([$id]); $order=$stmt->fetch();
if(!$order){http_response_code(404);exit('Encomenda não encontrada.');}
$stmt=$pdo->prepare('SELECT * FROM order_items WHERE order_id=? ORDER BY id'); $stmt->execute([$id]); $items=$stmt->fetchAll();
$stmt=$pdo->prepare('SELECT * FROM order_status_history WHERE order_id=? ORDER BY id DESC'); $stmt->execute([$id]); $history=$stmt->fetchAll();
render_header('Encomenda '.$order['public_id'].' — Admin SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"><a href="<?= e(app_url('admin/orders.php')) ?>" class="text-sm font-semibold text-blue-600">← Voltar</a><h1 class="mt-4 text-3xl font-bold"><?= e($order['public_id']) ?></h1><div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]"><?php admin_nav('orders'); ?><section class="space-y-6"><div class="grid gap-4 sm:grid-cols-3"><div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-400">Estado</p><p class="mt-2 font-semibold"><?= e($order['status']) ?></p></div><div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-400">Pagamento</p><p class="mt-2 font-semibold"><?= e($order['payment_status']) ?></p></div><div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-400">Total</p><p class="mt-2 font-semibold"><?= e(money((float)$order['total'])) ?></p></div></div><div class="rounded-xl border border-slate-200 bg-white p-6"><h2 class="font-bold">Cliente</h2><p class="mt-3 text-sm leading-6 text-slate-500"><?= e($order['customer_name']) ?><br><?= e($order['customer_email']) ?><br><?= e($order['customer_phone']?:'Sem telefone') ?><br><br><?= e($order['address_line1']) ?><?= $order['address_line2']?'<br>'.e($order['address_line2']):'' ?><br><?= e($order['postal_code']) ?> <?= e($order['city']) ?><br><?= e($order['country']) ?></p></div><div class="rounded-xl border border-slate-200 bg-white p-6"><h2 class="font-bold">Itens</h2><div class="mt-4 divide-y divide-slate-200"><?php foreach($items as $item): ?><div class="flex justify-between gap-4 py-4"><div><p class="font-medium"><?= e($item['product_name']) ?></p><p class="mt-1 text-sm text-slate-500"><?= (int)$item['quantity'] ?> × <?= e(money((float)$item['unit_price'])) ?></p></div><p class="font-semibold"><?= e(money((float)$item['line_total'])) ?></p></div><?php endforeach; ?></div></div><div class="rounded-xl border border-slate-200 bg-white p-6"><h2 class="font-bold">Histórico</h2><div class="mt-4 space-y-4"><?php foreach($history as $h): ?><div><p class="text-sm font-semibold"><?= e($h['status']) ?></p><p class="text-xs text-slate-500"><?= e(date('d/m/Y H:i',strtotime($h['created_at']))) ?><?= $h['note']?' · '.e($h['note']):'' ?></p></div><?php endforeach; ?></div></div></section></div></main>
<?php render_footer(); ?>
