<?php

namespace App\Domain\Participant\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Describes a participant info.
 * @OA\Schema(description="participant info description")
 */
class ParticipantInfoData extends ParticipantData
{
    /**
     * The idea count.
     * @var int
     * @OA\Property(example="uuid")
     */
    public int $idea_count;

    /**
     * Creates a new Participant.
     * @param array $data Participant data.
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $reader = new ArrayReader($data);
        $this->idea_count = $reader->findInt("idea_count");
    }
}
