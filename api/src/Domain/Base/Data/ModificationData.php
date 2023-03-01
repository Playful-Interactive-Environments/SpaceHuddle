<?php

namespace App\Domain\Base\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Describes token information.
 * @OA\Schema(description="token description")
 */
class ModificationData
{
    /**
     * Last changes timestamp.
     * @var int|null
     * @OA\Property(example="-1")
     */
    public ?int $lastModification;

    /**
     * Call date.
     * @var int|null
     * @OA\Property(example="86400")
     */
    public ?int $callTimestamp;

    /**
     * Row count.
     * @var int|null
     * @OA\Property(example="1")
     */
    public ?int $rowCount;

    /**
     * Creates a new Token.
     * @param array $data Participant data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->lastModification = $reader->findInt("lastModification");
        $this->callTimestamp = $reader->findInt("callTimestamp");
        $this->rowCount = $reader->findInt("rowCount");
    }

    /**
     * create an empty instance
     * @return ModificationData
     */
    public static function getEmpty(): ModificationData
    {
        return new ModificationData([
            "lastModification" => -1,
            "callTimestamp" => date_create()->getTimestamp(),
            "rowCount" => 0
        ]);
    }
}
