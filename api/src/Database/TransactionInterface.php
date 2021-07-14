<?php

namespace App\Database;

/**
 * Interface for database transactions.
 */
interface TransactionInterface
{
    /**
     * Starts a new transaction.
     */
    public function begin(): void;

    /**
     * Commits the current transaction.
     */
    public function commit(): void;

    /**
     * Rollback the current transaction.
     */
    public function rollback(): void;
}
