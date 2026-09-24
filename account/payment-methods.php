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
            $stmt = $pdo->prepare('UPDATE payment_methods SET is_active = 0 WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, Auth::id()]);
            $success = 'Método removido.';
        } else {
            $brand = strtolower(trim((string)($_POST['brand'] ?? 'visa')));
            $lastFour = preg_replace('/\D+/', '', (string)($_POST['last_four'] ?? ''));
            $month = (int)($_POST['expiry_month'] ?? 0);
            $year = (int)($_POST['expiry_year'] ?? 0);
            $default = isset($_POST['is_default']) ? 1 : 0;

            if (!in_array($brand, ['visa', 'mastercard', 'amex', 'other'], true)) {
                throw new RuntimeException('Marca inválida.');
            }

            if (!preg_match('/^\d{4}$/', $lastFour)) {
                throw new RuntimeException('Indica apenas os últimos 4 dígitos.');
            }

            if ($month < 1 || $month > 12 || $year < (int)date('Y') || $year > (int)date('Y') + 20) {
                throw new RuntimeException('Validade inválida.');
            }

            if ($default) {
                $pdo->prepare('UPDATE payment_methods SET is_default = 0 WHERE user_id = ?')->execute([Auth::id()]);
            }

            $token = 'demo_pm_' . bin2hex(random_bytes(12));

            $stmt = $pdo->prepare(
                'INSERT INTO payment_methods (user_id, provider, provider_token, brand, last_four, expiry_month, expiry_year, is_default)
                 VALUES (?, "demo_tokenized", ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([Auth::id(), $token, $brand, $lastFour, $month, $year, $default]);
            $success = 'Método tokenizado de demonstração guardado.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$stmt = $pdo->prepare('SELECT * FROM payment_methods WHERE user_id = ? AND is_active = 1 ORDER BY is_default DESC, id DESC');
$stmt->execute([Auth::id()]);
$methods = $stmt->fetchAll();

render_header('Métodos de pagamento — SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold">Métodos de pagamento</h1>
    <div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]">
        <?php account_nav('payments'); ?>
        <section class="space-y-6">
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-800">
                Esta versão não recebe números completos de cartão nem CVV. Em produção, Stripe/Mollie/Adyen tokenizaria o cartão e a SHIFT guardaria apenas o token e metadados seguros.
            </div>

            <?php if ($success): ?><div data-flash class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div data-flash class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

            <div class="grid gap-4 sm:grid-cols-2">
                <?php foreach ($methods as $m): ?>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <p class="text-sm font-semibold"><?= e(strtoupper($m['brand'])) ?> •••• <?= e($m['last_four']) ?><?= (int)$m['is_default'] ? ' · Principal' : '' ?></p>
                        <p class="mt-2 text-sm text-slate-500">Expira <?= e(sprintf('%02d/%d', $m['expiry_month'], $m['expiry_year'])) ?></p>
                        <p class="mt-1 truncate text-xs text-slate-400"><?= e($m['provider']) ?> · <?= e($m['provider_token']) ?></p>
                        <form method="post" class="mt-4" data-confirm data-confirm-title="Remover método de pagamento?" data-confirm-message="O token guardado será desativado e deixará de aparecer no checkout." data-confirm-text="Remover" data-confirm-danger="true">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                            <button class="text-xs font-semibold text-red-600">Remover</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="post" class="rounded-xl border border-slate-200 bg-white p-6">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <h2 class="text-lg font-bold">Adicionar método demo tokenizado</h2>
                <p class="mt-2 text-sm text-slate-500">Introduz apenas informação não sensível para simular o resultado de um PSP.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label><span class="mb-2 block text-sm font-medium">Marca</span><select name="brand" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm"><option value="visa">Visa</option><option value="mastercard">Mastercard</option><option value="amex">Amex</option><option value="other">Outro</option></select></label>
                    <label><span class="mb-2 block text-sm font-medium">Últimos 4 dígitos</span><input name="last_four" maxlength="4" pattern="\d{4}" inputmode="numeric" required placeholder="4242" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium">Mês</span><input name="expiry_month" type="number" min="1" max="12" required value="<?= date('m') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium">Ano</span><input name="expiry_year" type="number" min="<?= date('Y') ?>" max="<?= date('Y') + 20 ?>" required value="<?= date('Y') + 3 ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label class="sm:col-span-2 flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1"> Definir como principal</label>
                </div>
                <button class="mt-5 rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white">Guardar token demo</button>
            </form>
        </section>
    </div>
</main>
<?php render_footer(); ?>
