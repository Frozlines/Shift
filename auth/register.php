<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
if(Auth::check()) redirect('account/index.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        if(!hash_equals(csrf_token(),(string)($_POST['_csrf']??''))) throw new RuntimeException('Sessão expirada.');
        $name=trim((string)($_POST['name']??'')); $email=strtolower(trim((string)($_POST['email']??''))); $password=(string)($_POST['password']??'');
        if(mb_strlen($name)<2) throw new RuntimeException('Indica o teu nome.');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email inválido.');
        if(strlen($password)<8) throw new RuntimeException('A password deve ter pelo menos 8 caracteres.');
        Database::connection()->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,"CUSTOMER")')->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
        Auth::login($email,$password); redirect('account/index.php');
    }catch(PDOException $e){$error=$e->getCode()==='23000'?'Já existe uma conta com esse email.':'Não foi possível criar a conta.';}
    catch(Throwable $e){$error=$e->getMessage();}
}
?>
<!doctype html><html lang="pt-PT"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Criar conta — SHIFT</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen bg-slate-50 text-slate-900"><main class="grid min-h-screen place-items-center px-4 py-10"><div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"><a href="<?= e(app_url('')) ?>" class="text-2xl font-black tracking-tight">SHIFT.</a><h1 class="mt-8 text-3xl font-bold">Criar conta</h1><p class="mt-2 text-sm text-slate-500">Guarda favoritos, moradas e acompanha as tuas encomendas.</p>
<?php if($error): ?><div data-flash class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="mt-6 space-y-4"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><label class="block"><span class="mb-2 block text-sm font-medium">Nome</span><input name="name" required class="w-full rounded-lg border border-slate-300 px-3 py-3 outline-none focus:border-blue-500"></label><label class="block"><span class="mb-2 block text-sm font-medium">Email</span><input type="email" name="email" required class="w-full rounded-lg border border-slate-300 px-3 py-3 outline-none focus:border-blue-500"></label><label class="block"><span class="mb-2 block text-sm font-medium">Password</span><input type="password" name="password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3 py-3 outline-none focus:border-blue-500"></label><button class="w-full rounded-lg bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700">Criar conta</button></form>
<p class="mt-6 text-center text-sm text-slate-500">Já tens conta? <a href="<?= e(app_url('auth/login.php')) ?>" class="font-semibold text-blue-600 hover:underline">Entrar</a></p></div></main></body></html>
