<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
render_header('Serviços — SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <nav class="text-sm text-slate-500">
        <a href="<?= e(app_url('')) ?>" class="hover:text-blue-600">Início</a><span class="mx-2">/</span><span class="text-slate-900">Serviços</span>
    </nav>

    <div class="mt-5 flex flex-col gap-5 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="text-4xl font-bold tracking-tight">Serviços de marketing</h1><p id="resultCount" class="mt-2 text-sm text-slate-500"></p></div>
    </div>

    <div class="mt-7 grid gap-8 lg:grid-cols-[240px_1fr]">
        <aside class="h-fit rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold">Filtros</h2>
            <label class="mt-5 block"><span class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Pesquisar</span><input id="shopSearch" type="search" placeholder="Nome do serviço..." class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-500"></label>
            <label class="mt-5 block"><span class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Categoria</span><select id="categorySelect" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500"></select></label>
            <button id="clearFilters" type="button" class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 12h10M10 17h4"/></svg>
                Limpar filtros
            </button>
        </aside>

        <section>
            <div class="mb-5 flex justify-end">
                <label class="flex items-center gap-3 text-sm"><span class="text-slate-500">Ordenar:</span>
                    <select id="sortSelect" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none">
                        <option value="featured">Recomendados</option>
                        <option value="rating">Melhor avaliação</option>
                        <option value="price-asc">Preço: mais baixo</option>
                        <option value="price-desc">Preço: mais alto</option>
                    </select>
                </label>
            </div>
            <div id="shopGrid" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3"></div>
            <div id="emptyState" class="hidden rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center"><h3 class="text-lg font-semibold">Nenhum serviço encontrado</h3><p class="mt-2 text-sm text-slate-500">Experimenta alterar a pesquisa ou categoria.</p></div>
        </section>
    </div>
</main>
<script src="<?= e(app_url('assets/js/shop.js')) ?>"></script>
<?php render_footer(); ?>
