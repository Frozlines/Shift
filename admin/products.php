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
        if (!hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? ''))) throw new RuntimeException('Sessão expirada.');

        $id = (int)($_POST['id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $short = trim((string)($_POST['short_description'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $oldPriceRaw = trim((string)($_POST['old_price'] ?? ''));
        $oldPrice = $oldPriceRaw !== '' ? (float)$oldPriceRaw : null;
        $existingImage = trim((string)($_POST['existing_image'] ?? ''));
        $image = $existingImage !== '' ? $existingImage : 'assets/images/content.svg';
        $stock = max(0, (int)($_POST['stock'] ?? 0));
        $badge = trim((string)($_POST['badge'] ?? ''));
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        $active = isset($_POST['is_active']) ? 1 : 0;

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['image_file'];

            if ($upload['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Não foi possível enviar a imagem.');
            }

            $maxBytes = 5 * 1024 * 1024;
            if ((int)$upload['size'] > $maxBytes) {
                throw new RuntimeException('A imagem não pode ter mais de 5 MB.');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($upload['tmp_name']);
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($allowed[$mime])) {
                throw new RuntimeException('Formato de imagem inválido. Usa JPG, PNG ou WebP.');
            }

            $uploadDir = dirname(__DIR__) . '/uploads/products';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                throw new RuntimeException('Não foi possível criar a pasta de uploads.');
            }

            $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
            $destination = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($upload['tmp_name'], $destination)) {
                throw new RuntimeException('Não foi possível guardar a imagem enviada.');
            }

            $newImage = 'uploads/products/' . $filename;

            // Remove apenas uploads antigos da própria aplicação; nunca apaga assets do projeto.
            if ($existingImage !== '' && str_starts_with($existingImage, 'uploads/products/')) {
                $oldPath = dirname(__DIR__) . '/' . $existingImage;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $image = $newImage;
        }

        if ($name === '' || $slug === '' || $short === '' || $description === '' || $price <= 0 || $categoryId < 1) {
            throw new RuntimeException('Preenche os campos obrigatórios.');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE products SET category_id=?, name=?, slug=?, short_description=?, description=?, price=?, old_price=?, image=?, stock=?, badge=?, is_featured=?, is_active=? WHERE id=?'
            );
            $stmt->execute([$categoryId,$name,$slug,$short,$description,$price,$oldPrice,$image,$stock,$badge ?: null,$featured,$active,$id]);
            $success = 'Produto/serviço atualizado.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO products (category_id,name,slug,short_description,description,price,old_price,image,stock,badge,is_featured,is_active,rating,reviews_count)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,5.00,0)'
            );
            $stmt->execute([$categoryId,$name,$slug,$short,$description,$price,$oldPrice,$image,$stock,$badge ?: null,$featured,$active]);
            $success = 'Produto/serviço criado.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

$categories = $pdo->query('SELECT id, name, is_active FROM categories ORDER BY is_active DESC, name')->fetchAll();
$products = $pdo->query('SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC')->fetchAll();

render_header('Produtos — Admin SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold">Produtos / serviços</h1>
    <div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]">
        <?php admin_nav('products'); ?>
        <section class="space-y-6">
            <?php if ($success): ?><div data-flash class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div data-flash class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-white p-6">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><input type="hidden" name="existing_image" value="<?= e($edit['image'] ?? '') ?>">
                <div class="flex items-center justify-between"><h2 class="text-lg font-bold"><?= $edit ? 'Editar serviço' : 'Novo serviço' ?></h2><?php if ($edit): ?><a href="<?= e(app_url('admin/products.php')) ?>" class="text-sm text-blue-600">Cancelar edição</a><?php endif; ?></div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label><span class="mb-2 block text-sm font-medium">Nome *</span><input name="name" required value="<?= e($edit['name'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium">Slug *</span><input name="slug" required value="<?= e($edit['slug'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium">Categoria *</span><select name="category_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm"><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($edit['category_id'] ?? 0)===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?><?= (int)$c['is_active'] === 1 ? '' : ' (inativa)' ?></option><?php endforeach; ?></select></label>
                    <div>
                        <span class="mb-2 block text-sm font-medium">Imagem</span>
                        <label class="flex cursor-pointer items-center justify-center gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm transition hover:border-blue-400 hover:bg-blue-50/40">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4 4 4M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/>
                            </svg>
                            <span><strong>Escolher imagem</strong><span class="block text-xs font-normal text-slate-500">JPG, PNG ou WebP · máximo 5 MB</span></span>
                            <input id="imageFile" name="image_file" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only">
                        </label>
                        <div id="imagePreviewWrap" class="<?= empty($edit['image']) ? 'hidden ' : '' ?>mt-3 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img id="imagePreview" src="<?= !empty($edit['image']) ? e(app_url($edit['image'])) : '' ?>" alt="Pré-visualização" class="h-40 w-full object-cover">
                        </div>
                    </div>
                    <label><span class="mb-2 block text-sm font-medium">Preço *</span><input name="price" type="number" step="0.01" min="0.01" required value="<?= e((string)($edit['price'] ?? '')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium">Preço anterior</span><input name="old_price" type="number" step="0.01" min="0" value="<?= e((string)($edit['old_price'] ?? '')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium">Stock / vagas</span><input name="stock" type="number" min="0" value="<?= e((string)($edit['stock'] ?? 10)) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label><span class="mb-2 block text-sm font-medium">Badge</span><input name="badge" value="<?= e($edit['badge'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label>
                    <label class="sm:col-span-2"><span class="mb-2 block text-sm font-medium">Resumo *</span><textarea name="short_description" required rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"><?= e($edit['short_description'] ?? '') ?></textarea></label>
                    <label class="sm:col-span-2"><span class="mb-2 block text-sm font-medium">Descrição *</span><textarea name="description" required rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"><?= e($edit['description'] ?? '') ?></textarea></label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" <?= (int)($edit['is_featured'] ?? 0)?'checked':'' ?>> Destaque</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" <?= !isset($edit['is_active']) || (int)$edit['is_active']?'checked':'' ?>> Ativo</label>
                </div>
                <button class="mt-5 rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white"><?= $edit ? 'Guardar alterações' : 'Criar serviço' ?></button>
            </form>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Serviço</th><th class="px-5 py-3">Categoria</th><th class="px-5 py-3">Preço</th><th class="px-5 py-3">Stock</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3"></th></tr></thead><tbody class="divide-y divide-slate-200">
                    <?php foreach ($products as $p): ?><tr><td class="px-5 py-4"><div class="flex items-center gap-3"><img src="<?= e(app_url($p['image'])) ?>" alt="" class="h-11 w-11 rounded-lg border border-slate-200 bg-slate-50 object-cover"><span class="font-semibold"><?= e($p['name']) ?></span></div></td><td class="px-5 py-4 text-slate-500"><?= e($p['category']) ?></td><td class="px-5 py-4"><?= e(money((float)$p['price'])) ?></td><td class="px-5 py-4"><?= (int)$p['stock'] ?></td><td class="px-5 py-4"><?= (int)$p['is_active']?'Ativo':'Inativo' ?></td><td class="px-5 py-4 text-right"><a href="<?= e(app_url('admin/products.php?edit=' . (int)$p['id'])) ?>" class="font-semibold text-blue-600">Editar</a></td></tr><?php endforeach; ?>
                    </tbody></table>
                </div>
            </div>
        </section>
    </div>
</main>
<script>
const imageFile = document.getElementById('imageFile');
const imagePreview = document.getElementById('imagePreview');
const imagePreviewWrap = document.getElementById('imagePreviewWrap');

imageFile?.addEventListener('change', () => {
    const file = imageFile.files?.[0];
    if (!file) return;

    imagePreview.src = URL.createObjectURL(file);
    imagePreviewWrap.classList.remove('hidden');
});
</script>
<?php render_footer(); ?>
