<?php
declare(strict_types=1);

final class Migrations
{
    public static function run(): void
    {
        $pdo = Database::connection();

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                migration VARCHAR(190) PRIMARY KEY,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );

        self::apply($pdo, '20260924_user_states', static function (PDO $pdo): void {
            $pdo->exec(
                "ALTER TABLE users MODIFY role ENUM('CUSTOMER','ADMIN','BLOCKED','DELETED') NOT NULL DEFAULT 'CUSTOMER'"
            );

            $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'previous_role'")->fetch();
            if (!$column) {
                $pdo->exec(
                    "ALTER TABLE users ADD COLUMN previous_role ENUM('CUSTOMER','ADMIN') NULL AFTER role"
                );
            }
        });
    }

    private static function apply(PDO $pdo, string $name, callable $migration): void
    {
        $stmt = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE migration = ? LIMIT 1');
        $stmt->execute([$name]);

        if ($stmt->fetchColumn()) {
            return;
        }

        $migration($pdo);
        $insert = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
        $insert->execute([$name]);
    }
}
