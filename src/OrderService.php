<?php
declare(strict_types=1);

final class OrderService
{
    public static function create(int $userId, int $addressId, ?int $paymentMethodId, string $notes = ''): array
    {
        $pdo = Database::connection();
        $items = CartService::getItems();
        if (!$items) throw new RuntimeException('O carrinho está vazio.');

        $addressStmt = $pdo->prepare('SELECT * FROM addresses WHERE id = ? AND user_id = ? LIMIT 1');
        $addressStmt->execute([$addressId, $userId]);
        $address = $addressStmt->fetch();
        if (!$address) throw new RuntimeException('Morada inválida.');

        $payment = null;
        if ($paymentMethodId !== null) {
            $paymentStmt = $pdo->prepare('SELECT * FROM payment_methods WHERE id = ? AND user_id = ? AND is_active = 1 LIMIT 1');
            $paymentStmt->execute([$paymentMethodId, $userId]);
            $payment = $paymentStmt->fetch();
            if (!$payment) throw new RuntimeException('Método de pagamento inválido.');
        }

        $totals = CartService::totals();
        $pdo->beginTransaction();
        try {
            foreach ($items as $item) {
                $lock = $pdo->prepare('SELECT stock, price, name FROM products WHERE id = ? AND is_active = 1 FOR UPDATE');
                $lock->execute([$item['id']]);
                $fresh = $lock->fetch();
                if (!$fresh || (int)$fresh['stock'] < $item['quantity']) {
                    throw new RuntimeException('Stock insuficiente para ' . $item['name'] . '.');
                }
                if (abs((float)$fresh['price'] - (float)$item['price']) > 0.001) {
                    throw new RuntimeException('O preço de ' . $item['name'] . ' foi atualizado. Revê o carrinho.');
                }
            }

            $publicId = 'SHIFT-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $user = Auth::user();
            $orderStmt = $pdo->prepare(
                'INSERT INTO orders (
                    public_id,user_id,status,payment_status,payment_method_id,
                    customer_name,customer_email,customer_phone,address_line1,address_line2,postal_code,city,country,
                    subtotal,shipping,total,notes
                 ) VALUES (?, ?, "PENDING", "PENDING", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $orderStmt->execute([
                $publicId,$userId,$paymentMethodId,$address['recipient_name'],$user['email'],$user['phone'] ?? '',
                $address['line1'],$address['line2'],$address['postal_code'],$address['city'],$address['country'],
                $totals['subtotal'],$totals['shipping'],$totals['total'],trim($notes)
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,unit_price,quantity,line_total) VALUES (?,?,?,?,?,?)');
            $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
            foreach ($items as $item) {
                $lineTotal = round($item['price']*$item['quantity'],2);
                $itemStmt->execute([$orderId,$item['id'],$item['name'],$item['price'],$item['quantity'],$lineTotal]);
                $stockStmt->execute([$item['quantity'],$item['id'],$item['quantity']]);
                if ($stockStmt->rowCount() !== 1) throw new RuntimeException('O stock mudou durante o checkout.');
            }

            $pdo->prepare('INSERT INTO order_status_history (order_id,status,note) VALUES (?,"PENDING","Encomenda criada")')->execute([$orderId]);
            $pdo->prepare('INSERT INTO payments (order_id,provider,provider_reference,amount,status) VALUES (?,?,?,?,"PENDING")')
                ->execute([$orderId,$payment ? $payment['provider'] : 'manual_demo',$payment ? $payment['provider_token'] : 'demo_'.bin2hex(random_bytes(8)),$totals['total']]);

            $cartStmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = ? AND status = "ACTIVE" LIMIT 1');
            $cartStmt->execute([$userId]);
            $cartId = $cartStmt->fetchColumn();
            if ($cartId !== false) {
                $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([(int)$cartId]);
                $pdo->prepare('UPDATE carts SET status = "CONVERTED" WHERE id = ?')->execute([(int)$cartId]);
            }

            $pdo->commit();
            return ['public_id'=>$publicId,'total'=>$totals['total'],'status'=>'PENDING'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
