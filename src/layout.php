<?php
declare(strict_types=1);

function navigation_categories(): array
{
    static $categories = null;

    if (is_array($categories)) {
        return $categories;
    }

    try {
        $categories = Database::connection()->query(
            'SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY name ASC'
        )->fetchAll();
    } catch (Throwable) {
        $categories = [];
    }

    return $categories;
}

function render_header(string $title = 'SHIFT'): void
{
    $user = Auth::user();
    $authNotice = (string)($_SESSION['_auth_notice'] ?? '');
    unset($_SESSION['_auth_notice']);
    $csrf = csrf_token();
    ?>
<!doctype html>
<html lang="pt-PT" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <title><?= e($title) ?></title>
    <meta name="description" content="SHIFT — serviços de marketing, branding, conteúdo e performance.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        window.SHIFT_CONFIG = {
            baseUrl: <?= json_encode(rtrim(app_url(''), '/'), JSON_UNESCAPED_SLASHES) ?>,
            authenticated: <?= Auth::check() ? 'true' : 'false' ?>,
            admin: <?= Auth::isAdmin() ? 'true' : 'false' ?>
        };
    </script>
</head>
<body class="overflow-x-hidden bg-slate-50 text-slate-900 antialiased">
<header data-shift-header class="sticky top-0 z-50 transition-shadow duration-200">
    <div class="bg-slate-900 text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2 text-xs sm:px-6 lg:px-8">
            <span>Portes grátis em compras superiores a <?= e(number_format(free_shipping_threshold(), 0, ',', '.')) ?> €</span>
            <div class="hidden gap-5 sm:flex">
                <a href="<?= e(app_url('account/orders.php')) ?>" class="text-white/75 hover:text-white">Acompanhar encomenda</a>
                <a href="#" class="text-white/75 hover:text-white">Ajuda</a>
            </div>
        </div>
    </div>

    <div class="border-b border-slate-200 bg-white/95 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center gap-5 px-4 py-4 sm:px-6 lg:px-8">
            <a href="<?= e(app_url('')) ?>" class="shrink-0 text-2xl font-black tracking-tight">SHIFT.</a>

            <form data-site-search class="hidden flex-1 md:flex">
                <div class="flex w-full overflow-hidden rounded-lg border border-slate-300 bg-slate-50 focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500">
                    <input type="search" placeholder="Pesquisar serviços..." class="min-w-0 flex-1 bg-transparent px-4 py-2.5 text-sm outline-none">
                    <button type="submit" class="grid w-12 place-items-center text-slate-500 hover:text-slate-900" aria-label="Pesquisar">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/>
                        </svg>
                    </button>
                </div>
            </form>

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <?php if ($user): ?>
                    <a href="<?= e(app_url('account/index.php')) ?>" class="hidden items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 sm:flex">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="12" cy="8" r="3.5"/><path stroke-linecap="round" d="M5 20c.9-4.2 3.4-6.3 7-6.3s6.1 2.1 7 6.3"/>
                        </svg>
                        <?= e(explode(' ', $user['name'])[0]) ?>
                    </a>
                    <?php if (Auth::isAdmin()): ?>
                        <a href="<?= e(app_url('admin/index.php')) ?>" class="hidden rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 lg:block">Admin</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= e(app_url('auth/login.php')) ?>" class="hidden items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 sm:flex">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="12" cy="8" r="3.5"/><path stroke-linecap="round" d="M5 20c.9-4.2 3.4-6.3 7-6.3s6.1 2.1 7 6.3"/>
                        </svg>
                        Entrar
                    </a>
                <?php endif; ?>

                <a href="<?= e(app_url('account/favorites.php')) ?>" class="grid h-10 w-10 place-items-center rounded-lg text-slate-700 hover:bg-slate-100" aria-label="Favoritos">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25s-7.5-4.35-7.5-10.125A4.125 4.125 0 0 1 12 7.73a4.125 4.125 0 0 1 7.5 2.395C19.5 15.9 12 20.25 12 20.25Z"/>
                    </svg>
                </a>

                <a href="<?= e(app_url('cart.php')) ?>" data-cart-trigger class="relative flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l2.1 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L20.5 7H6"/>
                        <circle cx="9" cy="19" r="1.25"/><circle cx="18" cy="19" r="1.25"/>
                    </svg>
                    <span class="hidden sm:inline">Carrinho</span>
                    <span data-cart-count class="hidden absolute -right-1 -top-1 min-w-5 rounded-full bg-blue-600 px-1.5 py-0.5 text-center text-[10px] font-bold text-white">0</span>
                </a>
            </div>
        </div>

        <div class="px-4 pb-4 md:hidden">
            <form data-site-search>
                <div class="flex overflow-hidden rounded-lg border border-slate-300 bg-slate-50">
                    <input type="search" placeholder="Pesquisar serviços..." class="min-w-0 flex-1 bg-transparent px-4 py-2.5 text-sm outline-none">
                    <button type="submit" class="grid w-12 place-items-center text-slate-500" aria-label="Pesquisar">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <nav class="border-b border-slate-200 bg-white/95 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl gap-6 overflow-x-auto px-4 py-3 text-sm font-medium text-slate-700 sm:px-6 lg:px-8">
            <a href="<?= e(app_url('shop.php')) ?>" class="whitespace-nowrap text-blue-600">Todos os serviços</a>
            <?php foreach (navigation_categories() as $category): ?>
                <a href="<?= e(app_url('shop.php?category=' . urlencode($category['slug']))) ?>" data-category-link="<?= e($category['slug']) ?>" class="whitespace-nowrap transition hover:text-blue-600">
                    <?= e($category['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
</header>
<?php if ($authNotice): ?>
    <div class="border-b border-amber-200 bg-amber-50">
        <div class="mx-auto flex max-w-7xl items-start gap-3 px-4 py-3 text-sm text-amber-800 sm:px-6 lg:px-8">
            <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v.01M12 11v5"/></svg>
            <p><?= e($authNotice) ?></p>
        </div>
    </div>
<?php endif; ?>
<?php
}

function render_footer(): void
{
    ?>
<footer class="mt-16 border-t border-slate-200 bg-white">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
        <div>
            <a href="<?= e(app_url('')) ?>" class="text-2xl font-black tracking-tight">SHIFT.</a>
            <p class="mt-4 max-w-xs text-sm leading-6 text-slate-500">
                Estratégia, conteúdo, branding e performance para marcas que querem avançar.
            </p>
        </div>
        <div>
            <h3 class="text-sm font-semibold">Serviços</h3>
            <div class="mt-4 flex flex-col gap-3 text-sm text-slate-500">
                <?php foreach (navigation_categories() as $category): ?>
                    <a href="<?= e(app_url('shop.php?category=' . urlencode($category['slug']))) ?>" class="hover:text-slate-900">
                        <?= e($category['name']) ?>
                    </a>
                <?php endforeach; ?>
                <?php if (!navigation_categories()): ?>
                    <a href="<?= e(app_url('shop.php')) ?>" class="hover:text-slate-900">Todos os serviços</a>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <h3 class="text-sm font-semibold">Conta</h3>
            <div class="mt-4 flex flex-col gap-3 text-sm text-slate-500">
                <a href="<?= e(app_url('account/index.php')) ?>" class="hover:text-slate-900">Minha conta</a>
                <a href="<?= e(app_url('account/orders.php')) ?>" class="hover:text-slate-900">Encomendas</a>
                <a href="<?= e(app_url('account/favorites.php')) ?>" class="hover:text-slate-900">Favoritos</a>
                <a href="<?= e(app_url('account/addresses.php')) ?>" class="hover:text-slate-900">Moradas</a>
            </div>
        </div>
        <div>
            <h3 class="text-sm font-semibold">Nota técnica</h3>
            <p class="mt-4 text-sm leading-6 text-slate-500">
                Pagamentos reais ainda não estão ligados. A SHIFT guarda apenas tokens/metadados de métodos de pagamento, nunca números completos de cartão ou CVV.
            </p>
        </div>
    </div>
    <div class="border-t border-slate-200">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-5 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <span>© 2026 SHIFT.</span>
            <span>PHP puro · MySQL/MariaDB · JavaScript · Tailwind</span>
        </div>
    </div>
</footer>
<script src="<?= e(app_url('assets/js/common.js')) ?>"></script>
</body>
</html>
<?php
}

function account_nav(string $active): void
{
    $links = [
        'overview' => ['Minha conta', 'account/index.php'],
        'orders' => ['Encomendas', 'account/orders.php'],
        'favorites' => ['Favoritos', 'account/favorites.php'],
        'addresses' => ['Moradas', 'account/addresses.php'],
        'payments' => ['Métodos de pagamento', 'account/payment-methods.php'],
    ];

    echo '<aside class="h-fit rounded-xl border border-slate-200 bg-white p-3">';
    foreach ($links as $key => [$label, $url]) {
        $class = $key === $active
            ? 'bg-blue-50 text-blue-700 font-semibold'
            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';
        echo '<a href="' . e(app_url($url)) . '" class="block rounded-lg px-4 py-3 text-sm transition ' . $class . '">' . e($label) . '</a>';
    }
    echo '<a href="' . e(app_url('auth/logout.php')) . '" data-logout-link class="mt-2 block rounded-lg px-4 py-3 text-sm text-red-600 transition hover:bg-red-50">Terminar sessão</a>';
    echo '</aside>';
}

function admin_nav(string $active): void
{
    $links = [
        'dashboard' => ['Dashboard', 'admin/index.php'],
        'products' => ['Produtos', 'admin/products.php'],
        'categories' => ['Categorias', 'admin/categories.php'],
        'orders' => ['Encomendas', 'admin/orders.php'],
        'customers' => ['Utilizadores', 'admin/customers.php'],
        'settings' => ['Configurações', 'admin/settings.php'],
    ];

    echo '<aside class="h-fit rounded-xl border border-slate-200 bg-white p-3">';
    foreach ($links as $key => [$label, $url]) {
        $class = $key === $active
            ? 'bg-slate-900 text-white font-semibold'
            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';
        echo '<a href="' . e(app_url($url)) . '" class="block rounded-lg px-4 py-3 text-sm transition ' . $class . '">' . e($label) . '</a>';
    }
    echo '<div class="my-2 border-t border-slate-200"></div>';
    echo '<a href="' . e(app_url('')) . '" class="block rounded-lg px-4 py-3 text-sm text-blue-600 transition hover:bg-blue-50">Ver loja</a>';
    echo '</aside>';
}
