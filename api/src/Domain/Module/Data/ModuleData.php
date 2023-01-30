<?php

namespace App\Domain\Module\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Class ModuleData
 * @OA\Schema(description="module description")
 */
class ModuleData
{
    /**
     * The module id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * The name of the module.
     * @var string|null
     * @OA\Property()
     */
    public ?string $name;

    /**
     * Planned module order.
     * @var int|null
     * @OA\Property(example=1)
     */
    public ?int $order;

    /**
     * Current status of the module.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/ModuleState")
     */
    public ?string $state;

    /**
     * Control public screen and participant view synchronously.
     * @var bool|null
     * @OA\Property()
     */
    public ?bool $syncPublicParticipant;

    /**
     * Module parameters.
     * @var object|null
     * @OA\Property()
     */
    public ?object $parameter;

    /**
     * Creates a new module.
     * @param array $data Module data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->name = $reader->findString("module_name");
        $this->order = $reader->findInt("order");
        $this->state = strtoupper($reader->findString("state") ?? "");
        $this->syncPublicParticipant = $reader->findBool("sync_public_participant");

        $parameter = $reader->findString("parameter");
        if ($parameter != null) {
            $this->parameter = (object)json_decode($parameter);
        }
    }
}
