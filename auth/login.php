<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

if (Auth::check()) {
    redirect('');
}

$error = '';
$notice = (string)($_SESSION['_auth_notice'] ?? '');
unset($_SESSION['_auth_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? ''))) {
        $error = 'Sessão expirada. Tenta novamente.';
    } else {
        $result = Auth::attempt((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''));

        if ($result === 'SUCCESS') {
            $target = $_SESSION['login_redirect'] ?? '';
            unset($_SESSION['login_redirect']);
            header('Location: ' . ($target ?: app_url('')));
            exit;
        }

        $error = match ($result) {
            'BLOCKED' => 'A tua conta está bloqueada e não pode iniciar sessão. Contacta o suporte da SHIFT se precisares de ajuda.',
            'DELETED' => 'Esta conta foi eliminada e já não pode iniciar sessão.',
            'INACTIVE' => 'Esta conta encontra-se indisponível. Contacta o suporte da SHIFT.',
            default => 'Email ou password incorretos.',
        };
    }
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Entrar — SHIFT</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<main class="grid min-h-screen place-items-center px-4 py-10">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <a href="<?= e(app_url('')) ?>" class="text-2xl font-black tracking-tight">SHIFT.</a>
        <h1 class="mt-8 text-3xl font-bold">Entrar</h1>
        <p class="mt-2 text-sm text-slate-500">Acede à tua conta, favoritos, carrinho e encomendas.</p>

        <?php if ($notice): ?>
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800"><?= e($notice) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-700"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="mt-6 space-y-4">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <label class="block"><span class="mb-2 block text-sm font-medium">Email</span><input type="email" name="email" required class="w-full rounded-lg border border-slate-300 px-3 py-3 outline-none focus:border-blue-500"></label>
            <label class="block"><span class="mb-2 block text-sm font-medium">Password</span><input type="password" name="password" required class="w-full rounded-lg border border-slate-300 px-3 py-3 outline-none focus:border-blue-500"></label>
            <button class="w-full rounded-lg bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700">Entrar</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">Ainda não tens conta? <a href="<?= e(app_url('auth/register.php')) ?>" class="font-semibold text-blue-600 hover:underline">Criar conta</a></p>
    </div>
</main>
</body>
</html>
