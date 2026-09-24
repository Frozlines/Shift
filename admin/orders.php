<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout.php';
Auth::requireAdmin();

$pdo = Database::connection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? ''))) throw new RuntimeException('Sessão expirada.');
        $id = (int)($_POST['id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');
        $paymentStatus = (string)($_POST['payment_status'] ?? '');
        $allowedStatus = ['PENDING','PROCESSING','SHIPPED','DELIVERED','CANCELLED','REFUNDED'];
        $allowedPayment = ['PENDING','PAID','FAILED','REFUNDED'];
        if (!in_array($status,$allowedStatus,true) || !in_array($paymentStatus,$allowedPayment,true)) throw new RuntimeException('Estado inválido.');
        $pdo->prepare('UPDATE orders SET status=?, payment_status=? WHERE id=?')->execute([$status,$paymentStatus,$id]);
        $pdo->prepare('INSERT INTO order_status_history (order_id,status,note) VALUES (?,?,?)')->execute([$id,$status,'Atualizado por administrador']);
    } catch(Throwable $e){$error=$e->getMessage();}
}
$orders = $pdo->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll();

render_header('Encomendas — Admin SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"><h1 class="text-3xl font-bold">Encomendas</h1><div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]"><?php admin_nav('orders'); ?><section>
<?php if($error): ?><div data-flash class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white"><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Ref.</th><th class="px-4 py-3">Cliente</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Pagamento</th><th class="px-4 py-3">Atualizar</th></tr></thead><tbody class="divide-y divide-slate-200"><?php foreach($orders as $o): ?><tr>
<td class="px-4 py-4 font-semibold"><a class="text-blue-600 hover:underline" href="<?= e(app_url('admin/order.php?id=' . (int)$o['id'])) ?>"><?= e($o['public_id']) ?></a></td><td class="px-4 py-4"><?= e($o['customer_name']) ?><div class="text-xs text-slate-400"><?= e($o['customer_email']) ?></div></td><td class="px-4 py-4 font-semibold"><?= e(money((float)$o['total'])) ?></td><td class="px-4 py-4"><?= e($o['status']) ?></td><td class="px-4 py-4"><?= e($o['payment_status']) ?></td>
<td class="px-4 py-4"><form method="post" class="flex min-w-[340px] gap-2" data-confirm data-confirm-title="Atualizar encomenda?" data-confirm-message="O estado da encomenda e do pagamento será alterado e ficará registado no histórico." data-confirm-text="Guardar alteração"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>"><select name="status" class="rounded-lg border border-slate-300 px-2 py-2 text-xs"><?php foreach(['PENDING','PROCESSING','SHIPPED','DELIVERED','CANCELLED','REFUNDED'] as $s): ?><option <?= $o['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select><select name="payment_status" class="rounded-lg border border-slate-300 px-2 py-2 text-xs"><?php foreach(['PENDING','PAID','FAILED','REFUNDED'] as $s): ?><option <?= $o['payment_status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select><button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">Guardar</button></form></td>
</tr><?php endforeach; ?></tbody></table></div></div>
</section></div></main>
<?php render_footer(); ?>
