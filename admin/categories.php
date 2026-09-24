<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout.php';
Auth::requireAdmin();

$pdo = Database::connection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? ''))) {
            throw new RuntimeException('Sessão expirada. Atualiza a página e tenta novamente.');
        }

        $action = (string)($_POST['action'] ?? 'save');
        $id = (int)($_POST['id'] ?? 0);

        if ($action === 'delete') {
            if ($id < 1) {
                throw new RuntimeException('Categoria inválida.');
            }

            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
            $countStmt->execute([$id]);
            $productCount = (int)$countStmt->fetchColumn();

            if ($productCount > 0) {
                throw new RuntimeException('Não é possível remover esta categoria porque ainda tem serviços associados. Move esses serviços para outra categoria primeiro.');
            }

            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$id]);
            $success = 'Categoria removida.';
        } elseif ($action === 'toggle') {
            if ($id < 1) {
                throw new RuntimeException('Categoria inválida.');
            }

            $stmt = $pdo->prepare('UPDATE categories SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?');
            $stmt->execute([$id]);
            $success = 'Estado da categoria atualizado.';
        } else {
            $name = trim((string)($_POST['name'] ?? ''));
            $slug = strtolower(trim((string)($_POST['slug'] ?? '')));
            $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug) ?? '';
            $slug = trim(preg_replace('/-+/', '-', $slug) ?? '', '-');
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($name === '' || $slug === '') {
                throw new RuntimeException('Nome e slug são obrigatórios.');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, is_active = ? WHERE id = ?');
                $stmt->execute([$name, $slug, $isActive, $id]);
                $success = 'Categoria atualizada.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO categories (name, slug, is_active) VALUES (?, ?, ?)');
                $stmt->execute([$name, $slug, $isActive]);
                $success = 'Categoria criada. Já está disponível no cabeçalho da loja.';
            }
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $error = 'Já existe uma categoria com esse slug.';
        } else {
            $error = 'Não foi possível guardar a categoria.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT id, name, slug, is_active FROM categories WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

$categories = $pdo->query(
    'SELECT c.id, c.name, c.slug, c.is_active, c.created_at, COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name, c.slug, c.is_active, c.created_at
     ORDER BY c.is_active DESC, c.name ASC'
)->fetchAll();

render_header('Categorias — Admin SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div>
        <p class="text-sm text-slate-500">Administração</p>
        <h1 class="mt-1 text-3xl font-bold">Categorias</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
            As categorias ativas aparecem automaticamente no cabeçalho, no footer e nos filtros da loja.
        </p>
    </div>

    <div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]">
        <?php admin_nav('categories'); ?>

        <section class="space-y-6">
            <?php if ($success): ?>
                <div data-flash class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div data-flash class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" class="rounded-xl border border-slate-200 bg-white p-6">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold"><?= $edit ? 'Editar categoria' : 'Nova categoria' ?></h2>
                        <p class="mt-1 text-sm text-slate-500">O slug é utilizado no URL e no filtro da loja.</p>
                    </div>
                    <?php if ($edit): ?>
                        <a href="<?= e(app_url('admin/categories.php')) ?>" class="text-sm font-semibold text-blue-600 hover:underline">Cancelar edição</a>
                    <?php endif; ?>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label>
                        <span class="mb-2 block text-sm font-medium">Nome *</span>
                        <input id="categoryName" name="name" required value="<?= e($edit['name'] ?? '') ?>" placeholder="Ex.: Influencer Marketing" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm outline-none focus:border-blue-500">
                    </label>
                    <label>
                        <span class="mb-2 block text-sm font-medium">Slug *</span>
                        <input id="categorySlug" name="slug" required value="<?= e($edit['slug'] ?? '') ?>" placeholder="influencer-marketing" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm outline-none focus:border-blue-500">
                    </label>
                    <label class="sm:col-span-2 flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" <?= !isset($edit['is_active']) || (int)$edit['is_active'] === 1 ? 'checked' : '' ?>>
                        Categoria ativa e visível na loja
                    </label>
                </div>

                <button class="mt-5 rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                    <?= $edit ? 'Guardar alterações' : 'Criar categoria' ?>
                </button>
            </form>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-bold">Categorias existentes</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Categoria</th>
                                <th class="px-5 py-3">Slug</th>
                                <th class="px-5 py-3">Serviços</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td class="px-5 py-4 font-semibold"><?= e($category['name']) ?></td>
                                <td class="px-5 py-4 text-slate-500"><?= e($category['slug']) ?></td>
                                <td class="px-5 py-4"><?= (int)$category['product_count'] ?></td>
                                <td class="px-5 py-4">
                                    <?php if ((int)$category['is_active'] === 1): ?>
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Ativa</span>
                                    <?php else: ?>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">Inativa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="<?= e(app_url('admin/categories.php?edit=' . (int)$category['id'])) ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Editar</a>

                                        <form method="post" data-confirm data-confirm-title="Alterar visibilidade?" data-confirm-message="A categoria e os serviços associados podem deixar de aparecer no menu e na loja enquanto estiver inativa." data-confirm-text="Continuar">
                                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                                            <button class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                <?= (int)$category['is_active'] === 1 ? 'Ocultar' : 'Ativar' ?>
                                            </button>
                                        </form>

                                        <?php if ((int)$category['product_count'] === 0): ?>
                                            <form method="post" data-confirm data-confirm-title="Remover categoria definitivamente?" data-confirm-message="Esta ação não pode ser desfeita. A categoria será apagada da base de dados." data-confirm-text="Remover categoria" data-confirm-danger="true">
                                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                                                <button class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100">Remover</button>
                                            </form>
                                        <?php else: ?>
                                            <button disabled title="Move os serviços para outra categoria antes de remover." class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-300">Remover</button>
                                        <?php endif; ?>
                                    </div>
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
<script>
const categoryName = document.getElementById('categoryName');
const categorySlug = document.getElementById('categorySlug');
let slugTouched = <?= $edit ? 'true' : 'false' ?>;

categorySlug?.addEventListener('input', () => { slugTouched = true; });
categoryName?.addEventListener('input', () => {
    if (slugTouched || !categorySlug) return;

    categorySlug.value = categoryName.value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
});
</script>
<?php render_footer(); ?>
