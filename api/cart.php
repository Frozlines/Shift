<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
try {
    if (request_method()==='GET') json_response(['items'=>CartService::getItems(),'totals'=>CartService::totals()]);
    $body=json_body(); verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['_csrf'] ?? null));
    if(request_method()==='POST') CartService::add((int)($body['product_id']??0),(int)($body['quantity']??1));
    elseif(request_method()==='PATCH') CartService::update((int)($body['product_id']??0),(int)($body['quantity']??1));
    elseif(request_method()==='DELETE') CartService::remove((int)($body['product_id']??0));
    else json_response(['error'=>'Método não permitido.'],405);
    json_response(['success'=>true,'items'=>CartService::getItems(),'totals'=>CartService::totals()]);
} catch(Throwable $e){ json_response(['error'=>$e->getMessage()],422); }
