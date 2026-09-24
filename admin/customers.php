<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout.php';
Auth::requireAdmin();

$pdo = Database::connection();

$users = $pdo->query(
    'SELECT u.id, u.name, u.email, u.phone, u.role, u.previous_role, u.is_active, u.created_at,
            COUNT(DISTINCT o.id) AS order_count,
            COALESCE(SUM(CASE WHEN o.status <> "CANCELLED" THEN o.total ELSE 0 END), 0) AS total_spent
     FROM users u
     LEFT JOIN orders o ON o.user_id = u.id
     GROUP BY u.id
     ORDER BY FIELD(u.role, "ADMIN", "CUSTOMER", "BLOCKED", "DELETED"), u.id DESC'
)->fetchAll();

function user_status_badge(array $user): string
{
    return match ($user['role']) {
        'ADMIN' => '<span class="inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">Admin</span>',
        'CUSTOMER' => '<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Ativo</span>',
        'BLOCKED' => '<span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Bloqueado</span>',
        'DELETED' => '<span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Eliminado</span>',
        default => '<span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">' . e($user['role']) . '</span>',
    };
}

render_header('Utilizadores — Admin SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Administração</p>
            <h1 class="mt-1 text-3xl font-bold">Utilizadores</h1>
        </div>
        <p class="text-sm text-slate-500"><?= count($users) ?> conta<?= count($users) === 1 ? '' : 's' ?></p>
    </div>

    <div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]">
        <?php admin_nav('customers'); ?>

        <section>
            <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-800">
                Eliminar uma conta é uma operação lógica: a conta fica com role <strong>DELETED</strong>, deixa de conseguir iniciar sessão e o histórico de encomendas é preservado.
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Utilizador</th>
                                <th class="px-5 py-3">Contacto</th>
                                <th class="px-5 py-3">Encomendas</th>
                                <th class="px-5 py-3">Total</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                        <?php foreach ($users as $user): ?>
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-900"><?= e($user['name']) ?></div>
                                    <div class="mt-1 text-xs text-slate-400">#<?= (int)$user['id'] ?> · <?= e(date('d/m/Y', strtotime($user['created_at']))) ?></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div><?= e($user['email']) ?></div>
                                    <div class="mt-1 text-xs text-slate-400"><?= e($user['phone'] ?: 'Sem telefone') ?></div>
                                </td>
                                <td class="px-5 py-4"><?= (int)$user['order_count'] ?></td>
                                <td class="px-5 py-4 font-semibold"><?= e(money((float)$user['total_spent'])) ?></td>
                                <td class="px-5 py-4"><?= user_status_badge($user) ?></td>
                                <td class="px-5 py-4 text-right">
                                    <a href="<?= e(app_url('admin/customer.php?id=' . (int)$user['id'])) ?>" class="inline-flex rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">Ver / editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>
<?php render_footer(); ?>
