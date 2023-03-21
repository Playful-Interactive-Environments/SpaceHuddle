<?php

namespace App\Factory;

use Cake\Database\Connection;
use Cake\Database\Query;
use RuntimeException;

/**
 * Factory.
 */
final class QueryFactory
{
    private Connection $connection;

    /**
     * The constructor.
     *
     * @param Connection $connection The database connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Create a new 'select' query for the given table.
     *
     * @param string $table The table name
     *
     * @throws RuntimeException
     *
     * @return Query A new select query
     */
    public function newSelect(string $table): Query
    {
        return $this->newQuery()->from($table);
    }

    /**
     * Create a new query.
     *
     * @return Query The query
     */
    public function newQuery(): Query
    {
        return $this->connection->newQuery();
    }

    /**
     * Create an 'update' statement for the given table.
     *
     * @param string $table The table to update rows from
     * @param array<mixed> $data The values to be updated
     *
     * @return Query The new update query
     */
    public function newUpdate(string $table, array $data): Query
    {
        return $this->newQuery()->update($table)->set($data);
    }

    /**
     * Create an 'insert' statement for the given table.
     *
     * @param string $table The table to insert rows from
     * @param array<mixed> $data The values to be updated
     *
     * @return Query The new insert query
     */
    public function newInsert(string $table, array $data): Query
    {
        return $this->newQuery()->insert(array_keys($data))
            ->into($table)
            ->values($data);
    }

    /**
     * Create a 'delete' query for the given table.
     *
     * @param string $table The table to delete from
     *
     * @return Query A new delete query
     */
    public function newDelete(string $table): Query
    {
        return $this->newQuery()->delete($table);
    }

    /**
     * Clone the given query entry.
     *
     * @param string $table The table to insert rows from
     * @param array $conditions The WHERE conditions to add with AND.
     * @param string | null $parentIdColumn Name of the parent column if there is a new parent key value
     * @param string | null $newParentId New parent key value to be inserted
     * @param array<string> $columns Columns to be cloned
     * @param bool $createUuid Table has unique id column
     *
     * @return string | null newId
     */
    public function newClone(
        string $table,
        array $conditions = [],
        array $columns = [],
        ?string $parentIdColumn = null,
        ?string $newParentId = null,
        bool $createUuid = true
    ): ?string
    {
        $specialColumns = [];
        $specialColumnsValues = [];
        if ($createUuid) {
            $id = uuid_create();
            array_push($specialColumns, "id");
            array_push($specialColumnsValues, "'$id' as id");
        }
        if ($parentIdColumn) {
            array_push($specialColumns, $parentIdColumn);
            if ($newParentId) {
                array_push($specialColumnsValues, "'$newParentId' as $parentIdColumn");
            } else {
                array_push($specialColumnsValues, $parentIdColumn);
            }
        }
        $select = $this->newQuery()->from($table)
            ->select(array_merge($specialColumnsValues, $columns))
            ->where($conditions);
        $itemCount = $this->newQuery()->insert(array_merge($specialColumns, $columns))
            ->into($table)
            ->values($select)
            ->execute()
            ->rowCount();

        if ($itemCount > 0)
            return $id;
        return null;
    }
}
