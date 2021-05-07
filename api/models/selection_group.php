<?php
/**
 * @OA\Schema()
 */
class SelectionGroup {

    /**
     * The selection id.
     * @var int
     * @OA\Property()
     */
    public $id;

    /**
     * The selection name.
     * @var string
     * @OA\Property()
     */
    public $name;

    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->name = isset($data['name']) ? $data['name'] : null;
    }
}

?>
