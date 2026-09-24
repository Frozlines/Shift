<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout.php';
Auth::requireLogin();

$pdo = Database::connection();
$profileSuccess=''; $profileError='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        if(!hash_equals(csrf_token(),(string)($_POST['_csrf']??''))) throw new RuntimeException('Sessão expirada.');
        $name=trim((string)($_POST['name']??'')); $phone=trim((string)($_POST['phone']??''));
        if(mb_strlen($name)<2) throw new RuntimeException('Indica um nome válido.');
        $pdo->prepare('UPDATE users SET name=?,phone=? WHERE id=?')->execute([$name,$phone?:null,Auth::id()]);
        Auth::refresh(); $profileSuccess='Dados atualizados.';
    }catch(Throwable $e){$profileError=$e->getMessage();}
}

$stmt=$pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id=?'); $stmt->execute([Auth::id()]); $orderCount=(int)$stmt->fetchColumn();
$stmt=$pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id=?'); $stmt->execute([Auth::id()]); $favCount=(int)$stmt->fetchColumn();
$stmt=$pdo->prepare('SELECT COUNT(*) FROM addresses WHERE user_id=?'); $stmt->execute([Auth::id()]); $addressCount=(int)$stmt->fetchColumn();

render_header('Minha conta — SHIFT');
?>
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
<div class="mb-7"><p class="text-sm text-slate-500">Conta</p><h1 class="mt-1 text-3xl font-bold">Olá, <?= e(explode(' ',Auth::user()['name'])[0]) ?></h1></div>
<div class="grid gap-8 lg:grid-cols-[240px_1fr]"><?php account_nav('overview'); ?><section>
<div class="grid gap-4 sm:grid-cols-3"><a href="<?= e(app_url('account/orders.php')) ?>" class="rounded-xl border border-slate-200 bg-white p-5 hover:shadow-sm"><p class="text-sm text-slate-500">Encomendas</p><p class="mt-2 text-3xl font-bold"><?= $orderCount ?></p></a><a href="<?= e(app_url('account/favorites.php')) ?>" class="rounded-xl border border-slate-200 bg-white p-5 hover:shadow-sm"><p class="text-sm text-slate-500">Favoritos</p><p class="mt-2 text-3xl font-bold"><?= $favCount ?></p></a><a href="<?= e(app_url('account/addresses.php')) ?>" class="rounded-xl border border-slate-200 bg-white p-5 hover:shadow-sm"><p class="text-sm text-slate-500">Moradas</p><p class="mt-2 text-3xl font-bold"><?= $addressCount ?></p></a></div>
<form method="post" class="mt-6 rounded-xl border border-slate-200 bg-white p-6"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><h2 class="text-lg font-bold">Dados da conta</h2><?php if($profileSuccess): ?><div data-flash class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800"><?= e($profileSuccess) ?></div><?php endif; ?><?php if($profileError): ?><div data-flash class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"><?= e($profileError) ?></div><?php endif; ?><div class="mt-5 grid gap-4 sm:grid-cols-2"><label><span class="mb-2 block text-sm font-medium">Nome</span><input name="name" required value="<?= e(Auth::user()['name']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label><label><span class="mb-2 block text-sm font-medium">Telefone</span><input name="phone" value="<?= e(Auth::user()['phone']??'') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm"></label><label><span class="mb-2 block text-sm font-medium">Email</span><input value="<?= e(Auth::user()['email']) ?>" disabled class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-500"></label><label><span class="mb-2 block text-sm font-medium">Tipo de conta</span><input value="<?= e(Auth::user()['role']) ?>" disabled class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-500"></label></div><button class="mt-5 rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white">Guardar dados</button></form>
</section></div></main>
<?php render_footer(); ?>
