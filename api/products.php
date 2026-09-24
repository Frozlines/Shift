<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_method('GET');

$pdo = Database::connection();
$where = ['p.is_active = 1', 'c.is_active = 1'];
$params = [];
if (!empty($_GET['category'])) { $where[]='c.slug = ?'; $params[]=trim((string)$_GET['category']); }
if (!empty($_GET['search'])) {
    $where[]='(p.name LIKE ? OR p.short_description LIKE ? OR c.name LIKE ?)';
    $search='%'.trim((string)$_GET['search']).'%'; array_push($params,$search,$search,$search);
}
$sql='SELECT p.id,p.name,p.slug,p.short_description,p.description,p.price,p.old_price,p.image,p.stock,p.rating,p.reviews_count,p.badge,p.is_featured,c.name AS category,c.slug AS category_slug FROM products p JOIN categories c ON c.id=p.category_id WHERE '.implode(' AND ',$where).' ORDER BY p.is_featured DESC,p.id DESC';
$stmt=$pdo->prepare($sql); $stmt->execute($params); $products=$stmt->fetchAll();
foreach($products as &$product){
    $product['id']=(int)$product['id']; $product['price']=(float)$product['price'];
    $product['old_price']=$product['old_price']!==null?(float)$product['old_price']:null;
    $product['stock']=(int)$product['stock']; $product['rating']=(float)$product['rating'];
    $product['reviews_count']=(int)$product['reviews_count']; $product['is_featured']=(bool)$product['is_featured'];
}
unset($product);
$categories=$pdo->query('SELECT id,name,slug FROM categories WHERE is_active=1 ORDER BY name')->fetchAll();
json_response(['products'=>$products,'categories'=>$categories,'shipping_price'=>shipping_price(),'free_shipping_threshold'=>free_shipping_threshold()]);
