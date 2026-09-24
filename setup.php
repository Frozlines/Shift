<?php
declare(strict_types=1);

$config = require __DIR__ . '/config/app.php';
$lockFile = __DIR__ . '/storage/installed.lock';
$alreadyInstalled = is_file($lockFile);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyInstalled) {
    try {
        $db = $config['db'];
        $serverDsn = sprintf('mysql:host=%s;port=%s;charset=%s', $db['host'], $db['port'], $db['charset']);
        $pdo = new PDO($serverDsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $db['name']) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        if ($schema === false) throw new RuntimeException('Não foi possível ler database/schema.sql.');

        $appPdo = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'],$db['port'],$db['name'],$db['charset']),
            $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        foreach (preg_split('/;\s*(?:\r?\n|$)/', $schema) as $statement) {
            $statement = trim($statement);
            if ($statement !== '') $appPdo->exec($statement);
        }

        seed($appPdo, trim($_POST['admin_email'] ?? ''), (string)($_POST['admin_password'] ?? ''));
        file_put_contents($lockFile, date(DATE_ATOM));
        $alreadyInstalled = true;
        $message = 'Base de dados configurada. A SHIFT está pronta.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

function seed(PDO $pdo, string $adminEmail, string $adminPassword): void
{
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Indica um email válido para o administrador.');
    if (strlen($adminPassword) < 10) throw new RuntimeException('A password de administrador deve ter pelo menos 10 caracteres.');

    $settings = [
        'shipping_price' => '4.90',
        'free_shipping_threshold' => '75.00',
        'store_name' => 'SHIFT',
        'support_email' => 'hello@shift.test',
    ];
    $stmt = $pdo->prepare('INSERT INTO store_settings (`key`,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
    foreach ($settings as $key=>$value) $stmt->execute([$key,$value]);

    $categories = [
        ['Social Media','social-media'],['Branding','branding'],['SEO','seo'],['Paid Media','paid-media'],
        ['Web & Landing Pages','web-landing-pages'],['Content','content']
    ];
    $stmt = $pdo->prepare('INSERT INTO categories (name,slug) VALUES (?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)');
    foreach ($categories as $c) $stmt->execute($c);

    $ids = [];
    foreach ($pdo->query('SELECT id,slug FROM categories')->fetchAll(PDO::FETCH_ASSOC) as $row) $ids[$row['slug']] = (int)$row['id'];

    $products = [
        ['social-media-starter','Social Media Starter','social-media',89.90,109.90,'assets/images/social.svg',25,4.8,124,'-18%',1,'Planeamento de conteúdo e calendário editorial para uma presença consistente.','Inclui auditoria inicial, definição de pilares de conteúdo, calendário de 30 dias e recomendações de publicação para uma marca pequena ou projeto pessoal.'],
        ['brand-identity-kit','Brand Identity Kit','branding',149.00,null,'assets/images/branding.svg',18,4.9,96,'Mais vendido',1,'Base visual para lançar ou reorganizar uma identidade de marca.','Inclui direção visual, paleta de cores, tipografia, mini guia de utilização e duas propostas de aplicação da identidade.'],
        ['seo-audit','SEO Audit','seo',119.00,139.00,'assets/images/seo.svg',20,4.7,71,'-14%',1,'Auditoria técnica e editorial com prioridades claras para melhorar visibilidade.','Analisamos estrutura, indexação, performance, metadados, conteúdo e oportunidades de keywords, com relatório priorizado e próximos passos.'],
        ['ads-strategy','Ads Strategy Session','paid-media',79.00,null,'assets/images/ads.svg',30,4.6,58,'Novo',0,'Sessão estratégica para organizar campanhas de Meta Ads ou Google Ads.','Revisão do funil, audiência, criativos, orçamento e métricas. Inclui resumo escrito e plano recomendado para 30 dias.'],
        ['landing-page-campaign','Landing Page Campaign','web-landing-pages',249.00,299.00,'assets/images/landing.svg',12,4.9,43,'-17%',1,'Landing page focada em conversão para campanhas e lançamentos.','Estratégia, wireframe, copy base e implementação frontend de uma landing page responsiva para um produto, serviço ou campanha.'],
        ['content-pack','Content Pack','content',129.00,null,'assets/images/content.svg',22,4.8,87,null,0,'Pacote de conteúdos para alimentar redes sociais e comunicação da marca.','Inclui ideias, headlines, legendas e linhas de conteúdo adaptadas ao tom da marca para várias semanas de comunicação.'],
        ['email-campaign-kit','Email Campaign Kit','content',99.00,null,'assets/images/email.svg',24,4.7,54,null,0,'Sequência de emails preparada para lançamento, promoção ou recuperação.','Estrutura de campanha, assuntos, copy e recomendações de segmentação para uma sequência de até cinco emails.'],
        ['analytics-setup','Analytics Setup','paid-media',139.00,159.00,'assets/images/analytics.svg',15,4.8,62,'Promo',0,'Configuração e revisão de tracking para campanhas e website.','Estrutura de eventos, UTMs, objetivos e validação base de analytics para melhorar a leitura de resultados de marketing.'],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO products (category_id,name,slug,short_description,description,price,old_price,image,stock,rating,reviews_count,badge,is_featured,is_active)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)
         ON DUPLICATE KEY UPDATE category_id=VALUES(category_id),name=VALUES(name),short_description=VALUES(short_description),description=VALUES(description),price=VALUES(price),old_price=VALUES(old_price),image=VALUES(image),stock=VALUES(stock),rating=VALUES(rating),reviews_count=VALUES(reviews_count),badge=VALUES(badge),is_featured=VALUES(is_featured)'
    );
    foreach ($products as $p) {
        $stmt->execute([$ids[$p[2]],$p[1],$p[0],$p[11],$p[12],$p[3],$p[4],$p[5],$p[6],$p[7],$p[8],$p[9],$p[10]]);
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $stmt->execute([strtolower($adminEmail)]);
    $id = $stmt->fetchColumn();
    if ($id === false) {
        $pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,"ADMIN")')
            ->execute(['SHIFT Admin',strtolower($adminEmail),password_hash($adminPassword,PASSWORD_DEFAULT)]);
    } else {
        $pdo->prepare('UPDATE users SET role="ADMIN",password_hash=?,is_active=1 WHERE id=?')
            ->execute([password_hash($adminPassword,PASSWORD_DEFAULT),(int)$id]);
    }
}
?>
<!doctype html>
<html lang="pt-PT"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalar SHIFT</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen bg-slate-100 px-4 py-12 text-slate-900">
<main class="mx-auto max-w-lg rounded-2xl bg-white p-8 shadow-xl">
<p class="text-sm font-semibold text-blue-600">SHIFT</p><h1 class="mt-2 text-3xl font-bold">Configuração inicial</h1>
<p class="mt-3 text-sm leading-6 text-slate-500">Cria a base de dados, tabelas, catálogo de marketing e a primeira conta de administrador. Em XAMPP, root sem password já corresponde à configuração predefinida.</p>
<?php if ($message): ?>
<div class="mt-6 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800"><?= htmlspecialchars($message) ?></div><a href="<?= htmlspecialchars($config['base_url']) ?>/" class="mt-4 inline-flex rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white">Abrir loja</a>
<?php elseif ($alreadyInstalled): ?>
<div class="mt-6 rounded-xl bg-blue-50 p-4 text-sm leading-6 text-blue-800">A SHIFT já está instalada. Por segurança, o instalador ficou bloqueado. Para reinstalar deliberadamente, apaga <code>storage/installed.lock</code>.</div><a href="<?= htmlspecialchars($config['base_url']) ?>/" class="mt-4 inline-flex rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white">Abrir loja</a>
<?php else: ?>
<?php if ($error): ?><div class="mt-6 rounded-xl bg-red-50 p-4 text-sm text-red-700"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="mt-6 space-y-4"><label class="block"><span class="mb-2 block text-sm font-medium">Email do administrador</span><input type="email" name="admin_email" required value="admin@shift.local" class="w-full rounded-lg border border-slate-300 px-3 py-3 outline-none focus:border-blue-500"></label><label class="block"><span class="mb-2 block text-sm font-medium">Password do administrador</span><input type="password" name="admin_password" required minlength="10" class="w-full rounded-lg border border-slate-300 px-3 py-3 outline-none focus:border-blue-500"></label><button class="w-full rounded-lg bg-slate-900 px-5 py-3 text-sm font-bold text-white">Instalar base de dados</button></form>
<?php endif; ?>
</main></body></html>
