<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout.php';
Auth::requireLogin();

$stmt = Database::connection()->prepare(
    'SELECT p.id, p.name, p.price, p.old_price, p.image, p.stock, p.rating, p.reviews_count, p.badge, c.name AS category
     FROM favorites f
     JOIN products p ON p.id = f.product_id
     JOIN categories c ON c.id = p.category_id
     WHERE f.user_id = ? AND p.is_active = 1 AND c.is_active = 1
     ORDER BY f.created_at DESC'
);
$stmt->execute([Auth::id()]);
$favorites = $stmt->fetchAll();

render_header('Favoritos — SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold">Favoritos</h1>
    <div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]">
        <?php account_nav('favorites'); ?>
        <section>
            <?php if (!$favorites): ?>
                <div class="rounded-xl border border-slate-200 bg-white p-10 text-center">
                    <h2 class="text-lg font-semibold">Ainda não tens favoritos</h2>
                    <p class="mt-2 text-sm text-slate-500">Guarda serviços para encontrares tudo rapidamente mais tarde.</p>
                    <a href="<?= e(app_url('shop.php')) ?>" class="mt-5 inline-flex rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white">Explorar serviços</a>
                </div>
            <?php else: ?>
                <div id="favoritesGrid" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <?php foreach ($favorites as $p): ?>
                        <article class="group overflow-hidden rounded-xl border border-slate-200 bg-white transition-all duration-200">
                            <a href="<?= e(app_url('product.php?id=' . (int)$p['id'])) ?>"><img src="<?= e(app_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" class="aspect-square w-full bg-slate-50 object-cover"></a>
                            <div class="p-4">
                                <p class="text-xs text-slate-500"><?= e($p['category']) ?></p>
                                <a href="<?= e(app_url('product.php?id=' . (int)$p['id'])) ?>" class="mt-1 block font-semibold hover:text-blue-600"><?= e($p['name']) ?></a>
                                <p class="mt-3 text-lg font-bold"><?= e(money((float)$p['price'])) ?></p>
                                <div class="mt-4 flex gap-2">
                                    <button data-add-cart="<?= (int)$p['id'] ?>" class="flex-1 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Adicionar</button>
                                    <button data-wishlist="<?= (int)$p['id'] ?>" data-active="true" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600">Remover</button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-add-cart]').forEach(btn => btn.addEventListener('click', () => shiftAddToCart(Number(btn.dataset.addCart), 1, btn)));
    document.querySelectorAll('[data-wishlist]').forEach(btn => btn.addEventListener('click', async () => {
        const confirmed = await shiftConfirm({
            title: 'Remover dos favoritos?',
            message: 'O serviço deixa de aparecer na tua lista de favoritos, mas podes voltar a guardá-lo quando quiseres.',
            confirmText: 'Remover',
            cancelText: 'Manter',
            danger: true
        });
        if (!confirmed) return;

        const ok = await shiftToggleFavorite(Number(btn.dataset.wishlist), true, btn);
        if (!ok) return;

        const card = btn.closest('article');
        card?.classList.add('scale-[.98]', 'opacity-0');
        setTimeout(() => {
            card?.remove();
            if (!document.querySelector('#favoritesGrid article')) location.reload();
        }, 220);
    }));
});
</script>
<?php render_footer(); ?>
