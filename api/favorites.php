<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
if(!Auth::check()) json_response(['error'=>'Inicia sessão para usar favoritos.','login_required'=>true],401);
$pdo=Database::connection();
if(request_method()==='GET'){
    $stmt=$pdo->prepare('SELECT p.id,p.name,p.slug,p.price,p.old_price,p.image,p.stock,p.rating,p.reviews_count,p.badge,c.name AS category FROM favorites f JOIN products p ON p.id=f.product_id JOIN categories c ON c.id=p.category_id WHERE f.user_id=? AND p.is_active=1 ORDER BY f.created_at DESC');
    $stmt->execute([Auth::id()]); json_response(['favorites'=>$stmt->fetchAll()]);
}
$body=json_body(); verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['_csrf'] ?? null)); $productId=(int)($body['product_id']??0);
if($productId<1) json_response(['error'=>'Produto inválido.'],422);
if(request_method()==='POST') $pdo->prepare('INSERT IGNORE INTO favorites (user_id,product_id) VALUES (?,?)')->execute([Auth::id(),$productId]);
elseif(request_method()==='DELETE') $pdo->prepare('DELETE FROM favorites WHERE user_id=? AND product_id=?')->execute([Auth::id(),$productId]);
else json_response(['error'=>'Método não permitido.'],405);
json_response(['success'=>true]);
