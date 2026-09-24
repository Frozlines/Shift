<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';

render_header('SHIFT — Marketing que move marcas');
?>
<main>
    <section class="bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1.15fr_.85fr] lg:px-8 lg:py-14">
            <div class="flex min-h-[430px] flex-col justify-center rounded-2xl bg-gradient-to-br from-blue-700 to-violet-600 p-8 text-white sm:p-12">
                <span class="w-fit rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide">Marketing on demand</span>
                <h1 class="mt-5 max-w-2xl text-4xl font-black leading-tight sm:text-5xl lg:text-6xl">
                    Faz a tua marca mudar de velocidade.
                </h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-blue-50 sm:text-lg">
                    Estratégia, branding, conteúdo, SEO e campanhas num formato simples de contratar.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="<?= e(app_url('shop.php')) ?>" class="rounded-lg bg-white px-6 py-3 text-sm font-bold text-blue-700 transition hover:bg-blue-50">Explorar serviços</a>
                    <a href="#featured" class="rounded-lg border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white hover:bg-white/15">Ver destaques</a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                <a href="<?= e(app_url('shop.php?category=branding')) ?>" class="group grid min-h-[220px] grid-cols-[minmax(0,1.08fr)_minmax(0,.92fr)] overflow-hidden rounded-2xl bg-slate-900 text-white transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-900/10">
                    <div class="min-w-0 p-6 sm:p-7">
                        <p class="text-xs font-semibold uppercase tracking-wide text-white/60">Branding</p>
                        <h2 class="mt-2 break-words text-xl font-bold leading-snug sm:text-2xl">Identidade com direção</h2>
                        <span class="mt-5 inline-flex text-sm font-semibold text-blue-300 group-hover:underline">Ver serviços →</span>
                    </div>
                    <div class="min-w-0 overflow-hidden">
                        <img src="<?= e(app_url('assets/images/branding.svg')) ?>" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    </div>
                </a>

                <a href="<?= e(app_url('shop.php?category=paid-media')) ?>" class="group grid min-h-[220px] grid-cols-[minmax(0,1.08fr)_minmax(0,.92fr)] overflow-hidden rounded-2xl bg-amber-100 text-slate-900 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-900/10">
                    <div class="min-w-0 p-6 sm:p-7">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Performance</p>
                        <h2 class="mt-2 break-words text-xl font-bold leading-snug sm:text-2xl">Campanhas com foco no que importa</h2>
                        <span class="mt-5 inline-flex text-sm font-semibold text-blue-700 group-hover:underline">Descobrir →</span>
                    </div>
                    <div class="min-w-0 overflow-hidden">
                        <img src="<?= e(app_url('assets/images/ads.svg')) ?>" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    </div>
                </a>
            </div>
        </div>
    </section>

    <section class="border-y border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div class="flex items-center gap-3"><div class="grid h-10 w-10 place-items-center rounded-full bg-blue-50 text-blue-600">✓</div><div><p class="text-sm font-semibold">Briefing simples</p><p class="text-xs text-slate-500">Sem processos pesados</p></div></div>
            <div class="flex items-center gap-3"><div class="grid h-10 w-10 place-items-center rounded-full bg-blue-50 text-blue-600">↺</div><div><p class="text-sm font-semibold">Revisões claras</p><p class="text-xs text-slate-500">Conforme cada serviço</p></div></div>
            <div class="flex items-center gap-3"><div class="grid h-10 w-10 place-items-center rounded-full bg-blue-50 text-blue-600">€</div><div><p class="text-sm font-semibold">Portes grátis</p><p class="text-xs text-slate-500">Acima de <?= e(number_format(free_shipping_threshold(), 0)) ?> €</p></div></div>
            <div class="flex items-center gap-3"><div class="grid h-10 w-10 place-items-center rounded-full bg-blue-50 text-blue-600">?</div><div><p class="text-sm font-semibold">Acompanhamento</p><p class="text-xs text-slate-500">Do pedido à entrega</p></div></div>
        </div>
    </section>

    <section id="featured" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-blue-600">Em destaque</p>
                <h2 class="mt-1 text-3xl font-bold tracking-tight">Serviços mais procurados</h2>
            </div>
            <a href="<?= e(app_url('shop.php')) ?>" class="hidden text-sm font-semibold text-blue-600 hover:underline sm:block">Ver todos →</a>
        </div>
        <div id="featuredGrid" class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"></div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-slate-100 p-8 transition duration-300 hover:shadow-lg sm:flex sm:items-center sm:justify-between sm:gap-8 sm:p-10">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-blue-600">Campanha SHIFT</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Da ideia à campanha, sem andar às voltas</h2>
                    <p class="mt-3 text-slate-600">Escolhe um serviço, adiciona ao carrinho e acompanha o projeto diretamente pela tua conta.</p>
                    <a href="<?= e(app_url('shop.php')) ?>" class="mt-6 inline-flex rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">Começar</a>
                </div>
                <img src="<?= e(app_url('assets/images/analytics.svg')) ?>" alt="" class="mx-auto mt-8 h-52 w-52 rounded-2xl object-cover sm:mt-0">
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-semibold text-blue-600">Popular agora</p>
            <h2 class="mt-1 text-3xl font-bold tracking-tight">Escolhas da comunidade</h2>
        </div>
        <div id="popularGrid" class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"></div>
    </section>
</main>
<script src="<?= e(app_url('assets/js/home.js')) ?>"></script>
<?php render_footer(); ?>
