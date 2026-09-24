<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout.php';
Auth::requireAdmin();

$pdo = Database::connection();
$stats = [
    'orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'customers' => (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "CUSTOMER"')->fetchColumn(),
    'products' => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
    'revenue' => (float)$pdo->query('SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> "CANCELLED"')->fetchColumn(),
];
$recent = $pdo->query('SELECT public_id, customer_name, status, payment_status, total, created_at FROM orders ORDER BY id DESC LIMIT 8')->fetchAll();

render_header('Admin — SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex items-end justify-between"><div><p class="text-sm text-slate-500">Administração</p><h1 class="mt-1 text-3xl font-bold">Dashboard</h1></div></div>
    <div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]">
        <?php admin_nav('dashboard'); ?>
        <section>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Encomendas</p><p class="mt-2 text-3xl font-bold"><?= $stats['orders'] ?></p></div>
                <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Clientes</p><p class="mt-2 text-3xl font-bold"><?= $stats['customers'] ?></p></div>
                <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Serviços ativos</p><p class="mt-2 text-3xl font-bold"><?= $stats['products'] ?></p></div>
                <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Volume de encomendas</p><p class="mt-2 text-3xl font-bold"><?= e(money($stats['revenue'])) ?></p></div>
            </div>

            <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-bold">Encomendas recentes</h2></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Referência</th><th class="px-5 py-3">Cliente</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Pagamento</th><th class="px-5 py-3">Total</th></tr></thead><tbody class="divide-y divide-slate-200">
                    <?php foreach ($recent as $o): ?><tr><td class="px-5 py-4 font-semibold"><?= e($o['public_id']) ?></td><td class="px-5 py-4"><?= e($o['customer_name']) ?></td><td class="px-5 py-4"><?= e($o['status']) ?></td><td class="px-5 py-4"><?= e($o['payment_status']) ?></td><td class="px-5 py-4 font-semibold"><?= e(money((float)$o['total'])) ?></td></tr><?php endforeach; ?>
                    </tbody></table>
                </div>
            </div>
        </section>
    </div>
</main>
<?php render_footer(); ?>
