<?php

namespace App\Database;
use Cake\Database\Connection;
/**
 * Transaction handler.
 */
final class Transaction implements TransactionInterface
{
    /**
     * @var Connection The database connection
     */
    private Connection $connection;

    /**
     * Transaction constructor.
     * @param Connection $connection The database connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Starts a new transaction.
     */
    public function begin(): void
    {
        $this->connection->begin();
    }

    /**
     * Commits current transaction.
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Rollback current transaction.
     */
    public function rollback(): void
    {
        $this->connection->rollback();
    }
}
