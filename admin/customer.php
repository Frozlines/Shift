<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout.php';
Auth::requireAdmin();

$pdo = Database::connection();
$userId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

function load_admin_user(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT u.*,
                (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
                (SELECT COALESCE(SUM(o.total), 0) FROM orders o WHERE o.user_id = u.id AND o.status <> "CANCELLED") AS total_spent
         FROM users u WHERE u.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function active_admin_count(PDO $pdo): int
{
    return (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "ADMIN" AND is_active = 1')->fetchColumn();
}

function deleted_name(PDO $pdo): string
{
    do {
        $name = 'DELETED-' . random_int(1000000, 9999999);
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE name = ? LIMIT 1');
        $stmt->execute([$name]);
    } while ($stmt->fetchColumn());

    return $name;
}

$user = load_admin_user($pdo, $userId);
if (!$user) {
    http_response_code(404);
    render_header('Utilizador não encontrado — SHIFT');
    echo '<main class="mx-auto max-w-4xl px-4 py-20 text-center"><h1 class="text-3xl font-bold">Utilizador não encontrado</h1><a class="mt-6 inline-flex font-semibold text-blue-600" href="' . e(app_url('admin/customers.php')) . '">Voltar aos utilizadores</a></main>';
    render_footer();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? ''))) {
            throw new RuntimeException('Sessão expirada. Atualiza a página e tenta novamente.');
        }

        $action = (string)($_POST['action'] ?? 'save');
        $currentAdminId = (int)Auth::id();
        $isSelf = $userId === $currentAdminId;

        if ($action === 'save') {
            if ($user['role'] === 'DELETED') {
                throw new RuntimeException('Uma conta eliminada não pode ser editada.');
            }

            $name = trim((string)($_POST['name'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $selectedRole = (string)($_POST['role'] ?? 'CUSTOMER');

            if (mb_strlen($name) < 2) {
                throw new RuntimeException('Indica um nome válido.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Email inválido.');
            }
            if (!in_array($selectedRole, ['CUSTOMER', 'ADMIN'], true)) {
                throw new RuntimeException('Role inválida.');
            }
            if ($isSelf && $selectedRole !== 'ADMIN') {
                throw new RuntimeException('Não podes retirar a tua própria role de administrador.');
            }
            if ($user['role'] === 'ADMIN' && $selectedRole !== 'ADMIN' && active_admin_count($pdo) <= 1) {
                throw new RuntimeException('Tem de existir pelo menos um administrador ativo.');
            }

            if ($user['role'] === 'BLOCKED') {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, previous_role = ? WHERE id = ?');
                $stmt->execute([$name, $email, $phone ?: null, $selectedRole, $userId]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, role = ?, previous_role = NULL, is_active = 1 WHERE id = ?');
                $stmt->execute([$name, $email, $phone ?: null, $selectedRole, $userId]);
            }

            flash('admin_user_success', 'Utilizador atualizado com sucesso.');
        }

        if ($action === 'block') {
            if ($isSelf) {
                throw new RuntimeException('Não podes bloquear a tua própria conta.');
            }
            if (!in_array($user['role'], ['CUSTOMER', 'ADMIN'], true)) {
                throw new RuntimeException('Esta conta não pode ser bloqueada neste estado.');
            }
            if ($user['role'] === 'ADMIN' && active_admin_count($pdo) <= 1) {
                throw new RuntimeException('Não podes bloquear o último administrador ativo.');
            }

            $stmt = $pdo->prepare('UPDATE users SET previous_role = role, role = "BLOCKED", is_active = 0 WHERE id = ?');
            $stmt->execute([$userId]);
            flash('admin_user_success', 'Conta bloqueada. O utilizador deixou de poder iniciar sessão.');
        }

        if ($action === 'unblock') {
            if ($user['role'] !== 'BLOCKED') {
                throw new RuntimeException('A conta não está bloqueada.');
            }

            $restoreRole = in_array($user['previous_role'], ['CUSTOMER', 'ADMIN'], true) ? $user['previous_role'] : 'CUSTOMER';
            $stmt = $pdo->prepare('UPDATE users SET role = ?, previous_role = NULL, is_active = 1 WHERE id = ?');
            $stmt->execute([$restoreRole, $userId]);
            flash('admin_user_success', 'Conta reativada. O utilizador já pode iniciar sessão novamente.');
        }

        if ($action === 'delete') {
            if ($isSelf) {
                throw new RuntimeException('Não podes eliminar a tua própria conta.');
            }
            if ($user['role'] === 'DELETED') {
                throw new RuntimeException('A conta já está eliminada.');
            }

            $effectivePreviousRole = in_array($user['role'], ['CUSTOMER', 'ADMIN'], true)
                ? $user['role']
                : (in_array($user['previous_role'], ['CUSTOMER', 'ADMIN'], true) ? $user['previous_role'] : 'CUSTOMER');

            if ($effectivePreviousRole === 'ADMIN' && $user['role'] === 'ADMIN' && active_admin_count($pdo) <= 1) {
                throw new RuntimeException('Não podes eliminar o último administrador ativo.');
            }

            $newName = deleted_name($pdo);
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET name = ?, previous_role = ?, role = "DELETED", is_active = 0
                 WHERE id = ?'
            );
            $stmt->execute([$newName, $effectivePreviousRole, $userId]);
            flash('admin_user_success', 'Conta eliminada logicamente. O histórico foi preservado.');
        }

        redirect('admin/customer.php?id=' . $userId);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            flash('admin_user_error', 'Já existe outra conta com esse email.');
        } else {
            flash('admin_user_error', 'Não foi possível guardar as alterações.');
        }
        redirect('admin/customer.php?id=' . $userId);
    } catch (Throwable $e) {
        flash('admin_user_error', $e->getMessage());
        redirect('admin/customer.php?id=' . $userId);
    }
}

$user = load_admin_user($pdo, $userId);
$success = flash('admin_user_success');
$error = flash('admin_user_error');
$displayRole = $user['role'] === 'BLOCKED'
    ? (in_array($user['previous_role'], ['CUSTOMER', 'ADMIN'], true) ? $user['previous_role'] : 'CUSTOMER')
    : $user['role'];
$isSelf = (int)Auth::id() === (int)$user['id'];

render_header('Editar utilizador — Admin SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <button type="button" data-history-back data-fallback="<?= e(app_url('admin/customers.php')) ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-blue-600">← Voltar</button>

    <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Utilizador #<?= (int)$user['id'] ?></p>
            <h1 class="mt-1 text-3xl font-bold"><?= e($user['name']) ?></h1>
        </div>
        <div>
            <?php if ($user['role'] === 'ADMIN'): ?><span class="rounded-full bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700">ADMIN</span><?php endif; ?>
            <?php if ($user['role'] === 'CUSTOMER'): ?><span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">ATIVO</span><?php endif; ?>
            <?php if ($user['role'] === 'BLOCKED'): ?><span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">BLOCKED</span><?php endif; ?>
            <?php if ($user['role'] === 'DELETED'): ?><span class="rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700">DELETED</span><?php endif; ?>
        </div>
    </div>

    <div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]">
        <?php admin_nav('customers'); ?>

        <section class="space-y-6">
            <?php if ($success): ?><div data-flash class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div data-flash class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Encomendas</p><p class="mt-2 text-3xl font-bold"><?= (int)$user['order_count'] ?></p></div>
                <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Total encomendado</p><p class="mt-2 text-3xl font-bold"><?= e(money((float)$user['total_spent'])) ?></p></div>
            </div>

            <?php if ($user['role'] !== 'DELETED'): ?>
            <form method="post" class="rounded-xl border border-slate-200 bg-white p-6">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                <input type="hidden" name="action" value="save">
                <div class="flex items-center justify-between gap-4"><h2 class="text-lg font-bold">Dados do utilizador</h2><?php if ($isSelf): ?><span class="text-xs font-semibold text-blue-600">A tua conta</span><?php endif; ?></div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label><span class="mb-2 block text-sm font-medium">Nome</span><input name="name" required value="<?= e($user['name']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium">Email</span><input type="email" name="email" required value="<?= e($user['email']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium">Telefone</span><input name="phone" value="<?= e($user['phone'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium"><?= $user['role'] === 'BLOCKED' ? 'Role quando for reativado' : 'Role' ?></span><select name="role" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm"><option value="CUSTOMER" <?= $displayRole === 'CUSTOMER' ? 'selected' : '' ?>>Customer</option><option value="ADMIN" <?= $displayRole === 'ADMIN' ? 'selected' : '' ?>>Admin</option></select></label>
                </div>

                <button class="mt-5 rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white" data-loading-text="A guardar...">Guardar alterações</button>
            </form>
            <?php else: ?>
                <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm leading-6 text-red-800">Esta conta foi eliminada logicamente. O nome foi anonimizado para <strong><?= e($user['name']) ?></strong> e a conta já não pode iniciar sessão. O histórico de encomendas continua associado ao registo.</div>
            <?php endif; ?>

            <?php if ($user['role'] !== 'DELETED'): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-bold">Estado da conta</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Bloquear é reversível. Eliminar transforma a conta em DELETED e mantém o histórico para referência administrativa.</p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <?php if ($user['role'] === 'BLOCKED'): ?>
                        <form method="post" data-confirm data-confirm-title="Reativar conta?" data-confirm-message="Este utilizador poderá voltar a iniciar sessão na SHIFT." data-confirm-text="Reativar">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"><input type="hidden" name="action" value="unblock">
                            <button class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Reativar conta</button>
                        </form>
                    <?php elseif (!$isSelf): ?>
                        <form method="post" data-confirm data-confirm-title="Bloquear utilizador?" data-confirm-message="O utilizador deixará imediatamente de conseguir iniciar sessão. Esta ação pode ser revertida." data-confirm-text="Bloquear" data-confirm-danger="true">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"><input type="hidden" name="action" value="block">
                            <button class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 hover:bg-amber-100">Bloquear conta</button>
                        </form>
                    <?php endif; ?>

                    <?php if (!$isSelf): ?>
                        <form method="post" data-confirm data-confirm-title="Eliminar utilizador?" data-confirm-message="A conta será marcada como DELETED, o nome será anonimizado e o utilizador deixará de conseguir iniciar sessão. O histórico de encomendas será preservado." data-confirm-text="Eliminar conta" data-confirm-danger="true">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"><input type="hidden" name="action" value="delete">
                            <button class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Eliminar conta</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php render_footer(); ?>
