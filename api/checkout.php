<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
if(!Auth::check()) json_response(['error'=>'Inicia sessão para finalizar a compra.','login_required'=>true],401);
require_method('POST');
$body=json_body(); verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['_csrf'] ?? null));
try{
    $order=OrderService::create(Auth::id(),(int)($body['address_id']??0),!empty($body['payment_method_id'])?(int)$body['payment_method_id']:null,(string)($body['notes']??''));
    json_response(['success'=>true,'order'=>$order],201);
}catch(Throwable $e){json_response(['error'=>$e->getMessage()],422);}
