<?php
declare(strict_types=1);

final class CartService
{
    public static function getItems(): array
    {
        if (Auth::check()) {
            $stmt = Database::connection()->prepare(
                'SELECT ci.product_id AS id, ci.quantity, p.name, p.slug, p.price, p.old_price,
                        p.image, p.stock, p.is_active, c.name AS category
                 FROM cart_items ci
                 JOIN carts ca ON ca.id = ci.cart_id
                 JOIN products p ON p.id = ci.product_id
                 JOIN categories c ON c.id = p.category_id
                 WHERE ca.user_id = ? AND ca.status = "ACTIVE" AND p.is_active = 1 AND c.is_active = 1
                 ORDER BY ci.id DESC'
            );
            $stmt->execute([Auth::id()]);
            return array_map([self::class, 'normalizeItem'], $stmt->fetchAll());
        }

        $guestCart = $_SESSION['guest_cart'] ?? [];
        if (!$guestCart) return [];

        $ids = array_values(array_map('intval', array_keys($guestCart)));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT p.id, p.name, p.slug, p.price, p.old_price, p.image, p.stock, p.is_active, c.name AS category
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE p.id IN ($placeholders) AND p.is_active = 1 AND c.is_active = 1"
        );
        $stmt->execute($ids);

        $items = [];
        foreach ($stmt->fetchAll() as $product) {
            $product['quantity'] = min((int)($guestCart[$product['id']] ?? 0), (int)$product['stock']);
            if ($product['quantity'] > 0) $items[] = self::normalizeItem($product);
        }
        return $items;
    }

    public static function add(int $productId, int $quantity = 1): void
    {
        $product = self::getProduct($productId);
        $quantity = max(1, min($quantity, (int)$product['stock']));

        if (Auth::check()) {
            $cartId = self::userCartId(Auth::id());
            $stmt = Database::connection()->prepare(
                'INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)'
            );
            $stmt->execute([$cartId, $productId, $quantity, (int)$product['stock']]);
            return;
        }

        $_SESSION['guest_cart'] ??= [];
        $current = (int)($_SESSION['guest_cart'][$productId] ?? 0);
        $_SESSION['guest_cart'][$productId] = min($current + $quantity, (int)$product['stock']);
    }

    public static function update(int $productId, int $quantity): void
    {
        if ($quantity <= 0) { self::remove($productId); return; }
        $product = self::getProduct($productId);
        $quantity = min($quantity, (int)$product['stock']);

        if (Auth::check()) {
            $cartId = self::userCartId(Auth::id());
            Database::connection()->prepare('UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?')
                ->execute([$quantity, $cartId, $productId]);
            return;
        }
        $_SESSION['guest_cart'][$productId] = $quantity;
    }

    public static function remove(int $productId): void
    {
        if (Auth::check()) {
            $cartId = self::userCartId(Auth::id());
            Database::connection()->prepare('DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?')
                ->execute([$cartId, $productId]);
            return;
        }
        unset($_SESSION['guest_cart'][$productId]);
    }

    public static function totals(): array
    {
        $items = self::getItems();
        $subtotal = 0.0; $count = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
            $count += $item['quantity'];
        }
        $subtotal = round($subtotal, 2);
        $shipping = $subtotal >= free_shipping_threshold() ? 0.0 : ($subtotal > 0 ? shipping_price() : 0.0);
        return ['subtotal'=>$subtotal,'shipping'=>round($shipping,2),'total'=>round($subtotal+$shipping,2),'count'=>$count];
    }

    public static function mergeGuestCartIntoUser(int $userId): void
    {
        $guest = $_SESSION['guest_cart'] ?? [];
        if (!$guest) return;
        $cartId = self::userCartId($userId);
        foreach ($guest as $productId => $qty) {
            try {
                $product = self::getProduct((int)$productId);
                $stmt = Database::connection()->prepare(
                    'INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)'
                );
                $stmt->execute([$cartId,(int)$productId,min((int)$qty,(int)$product['stock']),(int)$product['stock']]);
            } catch (Throwable) {}
        }
        unset($_SESSION['guest_cart']);
    }

    private static function userCartId(int $userId): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = ? AND status = "ACTIVE" LIMIT 1');
        $stmt->execute([$userId]);
        $cartId = $stmt->fetchColumn();
        if ($cartId !== false) return (int)$cartId;
        $pdo->prepare('INSERT INTO carts (user_id, status) VALUES (?, "ACTIVE")')->execute([$userId]);
        return (int)$pdo->lastInsertId();
    }

    private static function getProduct(int $productId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.id, p.stock
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.id = ? AND p.is_active = 1 AND c.is_active = 1
             LIMIT 1'
        );
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) throw new RuntimeException('Produto não encontrado.');
        if ((int)$product['stock'] <= 0) throw new RuntimeException('Produto sem stock.');
        return $product;
    }

    private static function normalizeItem(array $item): array
    {
        $item['id'] = (int)$item['id'];
        $item['quantity'] = (int)$item['quantity'];
        $item['price'] = (float)$item['price'];
        $item['old_price'] = isset($item['old_price']) && $item['old_price'] !== null ? (float)$item['old_price'] : null;
        $item['stock'] = (int)$item['stock'];
        return $item;
    }
}
