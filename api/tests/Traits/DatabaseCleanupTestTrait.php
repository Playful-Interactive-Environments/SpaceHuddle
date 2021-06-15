<?php

namespace App\Test\Traits;

use PDO;

trait DatabaseCleanupTestTrait
{
    /**
     * Before each test.
     *
     * @return void
     */
    protected function cleanup(): void
    {
        if (method_exists($this, "setUpDatabase")) {
            $this->setUpDatabase(__DIR__ . "/../../resources/schema/schema.sql");
        }
    }

    /**
     * Clean up database. Truncate tables.
     *
     * @return void
     */
    protected function dropViews(): void
    {
        $pdo = $this->getConnection();

        $pdo->exec('SET unique_checks=0; SET foreign_key_checks=0;');

        $statement = $this->createQueryStatement(
            'SELECT TABLE_NAME
                FROM information_schema.tables
                WHERE table_schema = database() AND table_type = "VIEW"'
        );

        $sql = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $sql[] = sprintf('DROP VIEW `%s`;', $row['TABLE_NAME']);
        }

        if ($sql) {
            $pdo->exec(implode("\n", $sql));
        }

        $pdo->exec('SET unique_checks=1; SET foreign_key_checks=1;');
    }

    /**
     * Clean up database. Truncate tables.
     *
     * @return void
     */
    protected function dropTables(): void
    {
        $pdo = $this->getConnection();

        $pdo->exec('SET unique_checks=0; SET foreign_key_checks=0;');

        $statement = $this->createQueryStatement(
            'SELECT TABLE_NAME
                FROM information_schema.tables
                WHERE table_schema = database() AND table_type = "BASE TABLE"'
        );

        $sql = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $sql[] = sprintf('DROP TABLE `%s`;', $row['TABLE_NAME']);
        }

        if ($sql) {
            $pdo->exec(implode("\n", $sql));
        }

        $pdo->exec('SET unique_checks=1; SET foreign_key_checks=1;');

        $this->dropViews();
    }
}
