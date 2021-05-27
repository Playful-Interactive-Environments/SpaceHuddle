<?php

namespace PieLab\GAB\Models;

/**
 * Represents a group.
 * @OA\Schema(description="group category description")
 */
class Group
{
    /**
     * The idea id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * Time of group storage.
     * @var Date|string|null
     * @OA\Property(property="time_stamp")
     */
    public Date|string|null $timestamp;
    // TODO: I still don't think, that Date is correct here. Is it a Unix Timestamp? Then it should probably be int?

    /**
     * Description of the group.
     * @var string|null
     * @OA\Property()
     */
    public ?string $description;

    /**
     * Short description or keywords that describe the group.
     * @var string|null
     * @OA\Property()
     */
    public ?string $keywords;

    /**
     * Create a new Group.
     * @param array|null $data The group data.
     */
    public function __construct(array $data = null)
    {
        $this->id = $data["id"] ?? null;
        $this->timestamp = $data["time_stamp"] ?? null;
        $this->description = $data["description"] ?? null;
        $this->keywords = $data["keywords"] ?? null;
    }
}
