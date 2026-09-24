<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout.php';
Auth::requireAdmin();

$pdo = Database::connection();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? ''))) throw new RuntimeException('Sessão expirada.');
        $shipping = max(0, (float)($_POST['shipping_price'] ?? 0));
        $threshold = max(0, (float)($_POST['free_shipping_threshold'] ?? 0));
        $support = trim((string)($_POST['support_email'] ?? ''));
        if (!filter_var($support,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email de suporte inválido.');
        $stmt = $pdo->prepare('INSERT INTO store_settings (`key`,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
        foreach(['shipping_price'=>(string)$shipping,'free_shipping_threshold'=>(string)$threshold,'support_email'=>$support] as $k=>$v) $stmt->execute([$k,$v]);
        $success='Configurações guardadas. Recarrega as páginas para refletir os novos valores.';
    } catch(Throwable $e){$error=$e->getMessage();}
}
$settings=[];
foreach($pdo->query('SELECT `key`,value FROM store_settings')->fetchAll() as $row)$settings[$row['key']]=$row['value'];

render_header('Configurações — Admin SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"><h1 class="text-3xl font-bold">Configurações</h1><div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]"><?php admin_nav('settings'); ?><section>
<?php if($success): ?><div data-flash class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"><?= e($success) ?></div><?php endif; ?><?php if($error): ?><div data-flash class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><h2 class="font-bold">Loja</h2><div class="mt-5 grid gap-4 sm:grid-cols-2"><label><span class="mb-2 block text-sm font-medium">Preço dos portes (€)</span><input name="shipping_price" type="number" min="0" step="0.01" value="<?= e($settings['shipping_price']??'4.90') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label><label><span class="mb-2 block text-sm font-medium">Portes grátis a partir de (€)</span><input name="free_shipping_threshold" type="number" min="0" step="0.01" value="<?= e($settings['free_shipping_threshold']??'75.00') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label><label class="sm:col-span-2"><span class="mb-2 block text-sm font-medium">Email de suporte</span><input name="support_email" type="email" value="<?= e($settings['support_email']??'hello@shift.test') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label></div><button class="mt-5 rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Guardar</button></form>
</section></div></main>
<?php render_footer(); ?>
