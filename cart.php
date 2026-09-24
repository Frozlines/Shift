<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
render_header('Carrinho — SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <nav class="text-sm text-slate-500"><a href="<?= e(app_url('')) ?>" class="hover:text-blue-600">Início</a><span class="mx-2">/</span><span class="text-slate-900">Carrinho</span></nav>
    <h1 class="mt-5 text-4xl font-bold tracking-tight">O teu carrinho</h1>

    <div id="cartEmpty" class="hidden mt-8 rounded-xl border border-slate-200 bg-white p-12 text-center">
        <h2 class="text-xl font-semibold">O carrinho está vazio</h2><p class="mt-2 text-sm text-slate-500">Ainda não adicionaste nenhum serviço.</p>
        <a href="<?= e(app_url('shop.php')) ?>" class="mt-6 inline-flex rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white">Explorar serviços</a>
    </div>

    <div id="cartContent" class="mt-8 grid gap-8 lg:grid-cols-[1fr_360px]">
        <section class="rounded-xl border border-slate-200 bg-white px-5"><div id="cartItems"></div></section>
        <aside class="h-fit rounded-xl border border-slate-200 bg-white p-6 lg:sticky lg:top-6">
            <h2 class="text-lg font-bold">Resumo</h2>
            <div class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span id="cartSubtotal" class="font-medium">—</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Portes</span><span id="cartShipping" class="font-medium">—</span></div>
                <div class="flex justify-between border-t border-slate-200 pt-4 text-base"><strong>Total</strong><strong id="cartTotal">—</strong></div>
            </div>
            <a href="<?= e(app_url('checkout.php')) ?>" class="mt-6 flex w-full justify-center rounded-lg bg-blue-600 px-5 py-3.5 text-sm font-bold text-white hover:bg-blue-700">Finalizar compra</a>
            <a href="<?= e(app_url('shop.php')) ?>" class="mt-3 flex w-full justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Continuar a comprar</a>
        </aside>
    </div>
</main>
<script src="<?= e(app_url('assets/js/cart.js')) ?>"></script>
<?php render_footer(); ?>
