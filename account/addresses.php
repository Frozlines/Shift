<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout.php';
Auth::requireLogin();

$pdo = Database::connection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? ''))) {
            throw new RuntimeException('Sessão expirada.');
        }

        $action = (string)($_POST['action'] ?? 'save');

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, Auth::id()]);
            $success = 'Morada removida.';
        } else {
            $label = trim((string)($_POST['label'] ?? 'Principal'));
            $recipient = trim((string)($_POST['recipient_name'] ?? ''));
            $line1 = trim((string)($_POST['line1'] ?? ''));
            $line2 = trim((string)($_POST['line2'] ?? ''));
            $postal = trim((string)($_POST['postal_code'] ?? ''));
            $city = trim((string)($_POST['city'] ?? ''));
            $country = trim((string)($_POST['country'] ?? 'Portugal'));
            $default = isset($_POST['is_default']) ? 1 : 0;

            if ($recipient === '' || $line1 === '' || $postal === '' || $city === '' || $country === '') {
                throw new RuntimeException('Preenche os campos obrigatórios.');
            }

            if ($default) {
                $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([Auth::id()]);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO addresses (user_id, label, recipient_name, line1, line2, postal_code, city, country, is_default)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([Auth::id(), $label ?: 'Principal', $recipient, $line1, $line2 ?: null, $postal, $city, $country, $default]);
            $success = 'Morada adicionada.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$stmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
$stmt->execute([Auth::id()]);
$addresses = $stmt->fetchAll();

render_header('Moradas — SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold">Moradas</h1>
    <div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]">
        <?php account_nav('addresses'); ?>
        <section class="space-y-6">
            <?php if ($success): ?><div data-flash class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div data-flash class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

            <div class="grid gap-4 sm:grid-cols-2">
            <?php foreach ($addresses as $a): ?>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div><p class="font-semibold"><?= e($a['label']) ?><?= (int)$a['is_default'] ? ' · Principal' : '' ?></p><p class="mt-2 text-sm leading-6 text-slate-500"><?= e($a['recipient_name']) ?><br><?= e($a['line1']) ?><?= $a['line2'] ? '<br>' . e($a['line2']) : '' ?><br><?= e($a['postal_code']) ?> <?= e($a['city']) ?><br><?= e($a['country']) ?></p></div>
                        <form method="post" data-confirm data-confirm-title="Remover morada?" data-confirm-message="A morada deixa de estar disponível para futuros checkouts. As encomendas antigas não são alteradas." data-confirm-text="Remover" data-confirm-danger="true">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                            <button class="text-xs font-semibold text-red-600 hover:underline">Remover</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>

            <form method="post" class="rounded-xl border border-slate-200 bg-white p-6">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <h2 class="text-lg font-bold">Adicionar morada</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label><span class="mb-2 block text-sm font-medium">Etiqueta</span><input name="label" value="Principal" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium">Nome do destinatário *</span><input name="recipient_name" required value="<?= e(Auth::user()['name']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label class="sm:col-span-2"><span class="mb-2 block text-sm font-medium">Morada *</span><input name="line1" required class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label class="sm:col-span-2"><span class="mb-2 block text-sm font-medium">Complemento</span><input name="line2" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium">Código postal *</span><input name="postal_code" required placeholder="3000-000" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium">Cidade *</span><input name="city" required class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium">País *</span><input name="country" required value="Portugal" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label class="flex items-center gap-2 pt-7 text-sm"><input type="checkbox" name="is_default" value="1"> Definir como principal</label>
                </div>
                <button class="mt-5 rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white">Guardar morada</button>
            </form>
        </section>
    </div>
</main>
<?php render_footer(); ?>
