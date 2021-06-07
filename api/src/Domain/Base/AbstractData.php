<?php


namespace App\Domain\Base;


use Selective\ArrayReader\ArrayReader;

abstract class AbstractData
{
    /**
     * The entity ID.
     * @var string|null
     * @OA\Property()
     */
    public ?string $id = null;

    /**
     * The constructor.
     *
     * @param array $data The data
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);

        $this->id = $reader->findString("id");
        $this->initProperties($reader);
    }

    /**
     * Individual function for initial creation of properties
     * @param ArrayReader $reader The data
     */
    abstract protected function initProperties(ArrayReader $reader) : void;
}
