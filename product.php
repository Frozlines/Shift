<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = Database::connection()->prepare(
    'SELECT p.*, c.name AS category, c.slug AS category_slug
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.id = ? AND p.is_active = 1 AND c.is_active = 1 LIMIT 1'
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    render_header('Serviço não encontrado — SHIFT');
    echo '<main class="mx-auto max-w-4xl px-4 py-20 text-center"><h1 class="text-3xl font-bold">Serviço não encontrado</h1><a class="mt-6 inline-flex text-blue-600" href="' . e(app_url('shop.php')) . '">Voltar à loja</a></main>';
    render_footer();
    exit;
}

render_header(e($product['name']) . ' — SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <nav class="text-sm text-slate-500">
        <a href="<?= e(app_url('')) ?>" class="hover:text-blue-600">Início</a><span class="mx-2">/</span>
        <a href="<?= e(app_url('shop.php')) ?>" class="hover:text-blue-600">Serviços</a><span class="mx-2">/</span>
        <span class="text-slate-900"><?= e($product['name']) ?></span>
    </nav>

    <section class="mt-6 grid gap-10 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <img src="<?= e(app_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" class="aspect-square w-full object-cover">
        </div>

        <div class="lg:py-5">
            <p class="text-sm font-semibold text-blue-600"><?= e($product['category']) ?></p>
            <h1 class="mt-2 text-4xl font-bold tracking-tight"><?= e($product['name']) ?></h1>
            <div class="mt-4 flex items-center gap-3"><div class="text-sm text-amber-400">★★★★★</div><span class="text-sm text-slate-500"><?= e((string)$product['rating']) ?> (<?= e((string)$product['reviews_count']) ?> avaliações)</span></div>
            <p class="mt-5 max-w-xl text-base leading-7 text-slate-600"><?= e($product['short_description']) ?></p>

            <div class="mt-6 flex items-baseline gap-3">
                <span class="text-3xl font-bold"><?= e(money((float)$product['price'])) ?></span>
                <?php if ($product['old_price'] !== null): ?><span class="text-lg text-slate-400 line-through"><?= e(money((float)$product['old_price'])) ?></span><?php endif; ?>
            </div>

            <p class="mt-3 text-sm font-medium text-emerald-600"><?= (int)$product['stock'] > 0 ? 'Disponível — ' . e((string)$product['stock']) . ' vagas/unidades' : 'Indisponível' ?></p>

            <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                <div class="flex h-12 w-fit items-center rounded-lg border border-slate-300 bg-white">
                    <button id="decreaseQty" type="button" class="h-full w-11 text-lg text-slate-600 hover:bg-slate-50">−</button>
                    <input id="quantity" value="1" inputmode="numeric" class="h-full w-12 border-x border-slate-300 text-center text-sm font-semibold outline-none">
                    <button id="increaseQty" type="button" class="h-full w-11 text-lg text-slate-600 hover:bg-slate-50">+</button>
                </div>
                <button id="addProductToCart" data-product-id="<?= (int)$product['id'] ?>" data-stock="<?= (int)$product['stock'] ?>" type="button" class="flex h-12 flex-1 items-center justify-center rounded-lg bg-blue-600 px-6 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50" <?= (int)$product['stock'] < 1 ? 'disabled' : '' ?>>Adicionar ao carrinho</button>
                <button data-wishlist="<?= (int)$product['id'] ?>" type="button" class="grid h-12 w-12 shrink-0 place-items-center rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50" aria-label="Favorito">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25s-7.5-4.35-7.5-10.125A4.125 4.125 0 0 1 12 7.73a4.125 4.125 0 0 1 7.5 2.395C19.5 15.9 12 20.25 12 20.25Z"/></svg>
                </button>
            </div>

            <div class="mt-8 grid gap-3 border-t border-slate-200 pt-6 text-sm text-slate-600 sm:grid-cols-3">
                <div><strong class="block text-slate-900">Briefing</strong>Após a compra</div>
                <div><strong class="block text-slate-900">Revisões</strong>Conforme serviço</div>
                <div><strong class="block text-slate-900">Acompanhamento</strong>Na tua conta</div>
            </div>
        </div>
    </section>

    <section class="mt-12 rounded-xl border border-slate-200 bg-white p-6 sm:p-8">
        <h2 class="text-xl font-bold">O que está incluído</h2>
        <p class="mt-4 max-w-3xl leading-7 text-slate-600"><?= nl2br(e($product['description'])) ?></p>
    </section>

    <section class="mt-14">
        <h2 class="text-2xl font-bold">Também podes gostar</h2>
        <div id="relatedGrid" data-current-id="<?= (int)$product['id'] ?>" class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"></div>
    </section>
</main>
<script src="<?= e(app_url('assets/js/product.js')) ?>"></script>
<?php render_footer(); ?>
